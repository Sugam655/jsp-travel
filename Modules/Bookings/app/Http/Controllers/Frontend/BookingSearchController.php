<?php

namespace Modules\Bookings\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Services\AvailabilityService;
use Modules\Bookings\Services\BookingRulesService;
use Modules\Bookings\Services\PriceCalculator;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;

/**
 * The public booking search: find a bookable hotel, car or tour.
 *
 * This page only ever *searches*. It renders the widget, runs the real filtered
 * queries against the database and hands each result a confirmation popup, which
 * posts to bookings.create or bookings.store exactly like the confirmation page
 * does. Nothing is listed until a filter was actually submitted.
 *
 * The popup's dates, totals and availability are produced by the same rules,
 * price calculator and availability service the booking path uses, so the popup
 * can only ever show what the server would accept. bookings.store still
 * re-validates every one of them before anything is recorded.
 */
class BookingSearchController extends Controller
{
    /**
     * The query parameters that make a request a search rather than a browse.
     *
     * @var list<string>
     */
    private const SEARCH_KEYS = [
        'location',
        'start_date',
        'end_date',
        'travelers',
        'duration_days',
        'rating',
        'brand',
        'model',
        'vehicle_type',
        'max_price',
    ];

    public function index(Request $request, AvailabilityService $availability): View
    {
        $filters = $this->validatedFilters($request);

        $type = $filters['type'];

        // A bare /book visit is the search form itself; a visit carrying at least
        // one filter is a search. Request::filled() cannot be used to decide this:
        // validate() only returns the keys the request actually carried, so a
        // missing key would read as "not filled" even when the customer did narrow
        // the search down.
        $searched = collect(self::SEARCH_KEYS)->contains(fn (string $key) => $request->filled($key));

        // Nothing is listed until something was actually searched for. Opening the
        // page must not present whatever happens to be in the database as "these
        // are your options", so the search form is the whole page until the
        // customer asks a question.
        $results = $searched
            ? $this->search($type, $filters, $availability)
            : new Collection;

        return view('frontend.booking-search', [
            'type' => $type,
            'searched' => $searched,
            'filters' => $filters,
            'results' => $results,
            'summaries' => $searched
                ? $this->summaries($type, $results, $filters, $availability)
                : [],
            'types' => Booking::TYPE_LABELS,
            'vehicleTypeLabels' => TransportVehicle::TYPE_LABELS,
            'hotelLocations' => Hotel::query()->active()->pluck('location')->filter()->unique()->sort()->values(),
            'tourLocations' => Tour::query()->active()->pluck('location')->filter()->unique()->sort()->values(),
            'vehicleBrands' => TransportVehicle::query()->active()->where('availability', true)->pluck('brand')->filter()->unique()->sort()->values(),
            'tourDurations' => Tour::query()->active()->distinct()->orderBy('duration_days')->pluck('duration_days')->filter()->values(),
            'hotelPriceOptions' => $this->priceOptions(Hotel::query()->active()->pluck('price')),
            'vehiclePriceOptions' => $this->priceOptions(TransportVehicle::query()->active()->where('availability', true)->pluck('price')),
            'tourPriceOptions' => $this->priceOptions(Tour::query()->active()->pluck('price')),
            'rentalDurations' => $this->rentalDurationOptions(),
            'maxVehiclePassengers' => $this->maxVehiclePassengers(),
            'maxTourTravelers' => $this->maxTourTravelers(),
        ]);
    }

    /**
     * The filter set, validated the same way for every booking type.
     *
     * @return array<string, mixed>
     */
    protected function validatedFilters(Request $request): array
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(Booking::TYPES)],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'travelers' => ['nullable', 'integer', 'min:1', 'max:'.config('booking.max_travelers', 100)],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'vehicle_type' => ['nullable', Rule::in(TransportVehicle::TYPES)],
            'max_price' => ['nullable', 'integer', 'min:0'],
        ], [
            'end_date.after_or_equal' => 'The end date cannot be before the start date.',
            'travelers.max' => 'Please contact us for groups larger than '.config('booking.max_travelers', 100).' travellers.',
        ]);

        $filters['type'] = $filters['type'] ?? 'hotel';

        $this->validatePeriod($filters);

        return $filters;
    }

    /**
     * Reject a period the booking rules would refuse anyway.
     *
     * A stay and a rental both have to end after they start, and the rental length
     * control describes the pick-up/drop-off pair it sits next to, so the two
     * cannot disagree. Catching it here means the search reports the problem
     * instead of quietly returning results for a request that can never be booked.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function validatePeriod(array $filters): void
    {
        $start = filled($filters['start_date'] ?? null)
            ? CarbonImmutable::parse($filters['start_date'])->startOfDay()
            : null;
        $end = filled($filters['end_date'] ?? null)
            ? CarbonImmutable::parse($filters['end_date'])->startOfDay()
            : null;

        if ($start === null || $end === null) {
            return;
        }

        if (in_array($filters['type'], ['hotel', 'vehicle'], true) && ! $end->greaterThan($start)) {
            throw ValidationException::withMessages([
                'end_date' => match ($filters['type']) {
                    'hotel' => 'Check-out date must be after the check-in date.',
                    default => 'Return date must be after the pick-up date.',
                },
            ]);
        }

        if ($filters['type'] !== 'vehicle' || blank($filters['duration_days'] ?? null)) {
            return;
        }

        $rentalDays = (int) (new BookingRulesService)->days($start, $end);

        if ($rentalDays !== (int) $filters['duration_days']) {
            throw ValidationException::withMessages([
                'duration_days' => "These dates are a {$rentalDays}-day rental. Choose the matching rental duration or adjust the drop-off date.",
            ]);
        }
    }

    /**
     * The real, filtered result set for the searched type.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Hotel|TransportVehicle|Tour>
     */
    protected function search(string $type, array $filters, AvailabilityService $availability): Collection
    {
        if ($type === 'vehicle') {
            return $this->searchVehicles($filters, $availability);
        }

        if ($type === 'tour') {
            return $this->searchTours($filters);
        }

        return $this->searchHotels($filters, $availability);
    }

    /**
     * Hotels matching the location, rating and price filters that are also free
     * for the requested stay.
     *
     * Availability is resolved in one query for the whole page rather than once
     * per card, so the search stays a single round trip as the inventory grows.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Hotel>
     */
    protected function searchHotels(array $filters, AvailabilityService $availability): Collection
    {
        $results = Hotel::query()
            ->active()
            ->with('destination')
            ->when(
                filled($filters['location'] ?? null),
                fn (Builder $query) => $query->where(fn (Builder $query) => $query
                    ->where('location', $filters['location'])
                    ->orWhereHas('destination', fn (Builder $query) => $query->where('name', $filters['location'])))
            )
            ->when(
                filled($filters['rating'] ?? null),
                fn (Builder $query) => $query->where('rating', $filters['rating'])
            )
            ->when(
                filled($filters['max_price'] ?? null),
                fn (Builder $query) => $query->where('price', '<=', $filters['max_price'])
            )
            ->orderByDesc('featured')
            ->orderBy('title')
            ->get();

        $unavailable = $availability->unavailableServiceIds(
            'hotel',
            $results->modelKeys(),
            (string) ($filters['start_date'] ?? ''),
            $filters['end_date'] ?? null
        );

        return $results->reject(fn (Hotel $hotel) => in_array($hotel->id, $unavailable, true))->values();
    }

    /**
     * Rentable vehicles matching the type/brand/price filters that can carry the
     * requested party and are free for the requested rental period.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, TransportVehicle>
     */
    protected function searchVehicles(array $filters, AvailabilityService $availability): Collection
    {
        $travelers = isset($filters['travelers']) ? (int) $filters['travelers'] : null;

        $results = TransportVehicle::query()
            ->active()
            ->where('availability', true)
            ->with('destination')
            ->when(
                filled($filters['vehicle_type'] ?? null),
                fn (Builder $query) => $query->where('vehicle_type', $filters['vehicle_type'])
            )
            ->when(
                filled($filters['brand'] ?? null),
                fn (Builder $query) => $query->where('brand', $filters['brand'])
            )
            ->when(
                filled($filters['model'] ?? null),
                fn (Builder $query) => $query->where('model', $filters['model'])
            )
            ->when(
                filled($filters['max_price'] ?? null),
                fn (Builder $query) => $query->where('price', '<=', $filters['max_price'])
            )
            // A vehicle that cannot seat the party is not a match for it, so the
            // seat count is a filter rather than something only the booking form
            // rejects later.
            ->when(
                $travelers !== null,
                fn (Builder $query) => $query->where('seating_capacity', '>=', $travelers)
            )
            ->orderByDesc('featured')
            ->orderBy('name')
            ->get();

        $unavailable = $availability->unavailableServiceIds(
            'vehicle',
            $results->modelKeys(),
            (string) ($filters['start_date'] ?? ''),
            $filters['end_date'] ?? null
        );

        return $results->reject(fn (TransportVehicle $vehicle) => in_array($vehicle->id, $unavailable, true))->values();
    }

    /**
     * Tour packages matching the location, duration, capacity and price filters.
     *
     * Tours share their seats rather than being taken whole, so a partly booked
     * tour is still offered here and the remaining seats are settled by the
     * availability check when the booking is created.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Tour>
     */
    protected function searchTours(array $filters): Collection
    {
        $travelers = isset($filters['travelers']) ? (int) $filters['travelers'] : null;

        return Tour::query()
            ->active()
            ->with('destination')
            ->when(
                filled($filters['location'] ?? null),
                fn (Builder $query) => $query->where(fn (Builder $query) => $query
                    ->where('location', $filters['location'])
                    ->orWhereHas('destination', fn (Builder $query) => $query->where('name', $filters['location'])))
            )
            ->when(
                filled($filters['duration_days'] ?? null),
                fn (Builder $query) => $query->where('duration_days', $filters['duration_days'])
            )
            ->when(
                filled($filters['max_price'] ?? null),
                fn (Builder $query) => $query->where('price', '<=', $filters['max_price'])
            )
            ->when(
                $travelers !== null,
                fn (Builder $query) => $query->where(fn (Builder $query) => $query
                    ->whereNull('capacity')
                    ->orWhere('capacity', '>=', $travelers))
            )
            ->orderByDesc('featured')
            ->orderBy('title')
            ->get();
    }

    /**
     * Everything the shared confirmation popup shows for one result.
     *
     * Keyed by booking type and service id, which is what a result link carries,
     * so the popup can look up the summary of the card that opened it.
     *
     * @param  Collection<int, Hotel|TransportVehicle|Tour>  $results
     * @param  array<string, mixed>  $filters
     * @return array<string, array<string, mixed>>
     */
    protected function summaries(
        string $type,
        Collection $results,
        array $filters,
        AvailabilityService $availability
    ): array {
        $rules = new BookingRulesService;

        return $results->mapWithKeys(fn (Hotel|TransportVehicle|Tour $service) => [
            $type.'-'.$service->id => $this->summary($type, $service, $filters, $availability, $rules),
        ])->all();
    }

    /**
     * The confirmation popup data for a single result.
     *
     * Every amount, every date label and the availability verdict come from the
     * same services bookings.store uses, so what the customer confirms is what the
     * server will create. Nothing is recalculated in the browser, and the popup
     * therefore cannot offer a total the server would refuse.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function summary(
        string $type,
        mixed $service,
        array $filters,
        AvailabilityService $availability,
        BookingRulesService $rules
    ): array {
        $travelers = isset($filters['travelers'])
            ? max(1, min((int) $filters['travelers'], $rules->maxTravelers($type, $service)))
            : $rules->defaultTravelers($type, $service);

        $start = $filters['start_date'] ?? null;
        $end = $filters['end_date'] ?? null;

        $validation = $rules->validate($type, $service, $start, $end, $travelers);

        // Whether this particular result can still be booked for the searched period.
        if (! $validation['valid']) {
            $available = false;
            $message = $validation['errors'][0] ?? 'The requested dates are not valid.';
        } elseif ($type === 'tour') {
            // Tours share a seat pool, so only a per-tour check can say whether the
            // searched party still fits. Hotels and vehicles were already reduced to
            // the available ones by the search itself.
            $check = $availability->check($type, (int) $service->id, (string) $start, $end, null, $travelers);

            $available = $check['available'];
            $message = $check['reason'];
        } else {
            $available = true;
            $message = null;
        }

        $quote = (new PriceCalculator)->quote($type, $service, $start, $end, $travelers);
        $money = fn (mixed $amount) => $quote['currency'].' '.number_format((float) $amount, 2);

        return [
            'popupTitle' => match ($type) {
                'hotel' => 'Confirm Hotel Booking',
                'vehicle' => 'Confirm Car Booking',
                default => 'Confirm Tour Booking',
            },
            'serviceTitle' => $type === 'vehicle' ? $service->name : $service->title,
            'rows' => $this->summaryRows($type, $service, $filters, $travelers, $quote),
            'unit' => trim($money($quote['unit_price']).' '.$quote['unit_label']),
            'note' => $quote['recalc_note'],
            'subtotal' => $money($quote['subtotal']),
            'discountLabel' => $quote['discount_description'] === null
                ? 'Discount'
                : 'Discount ('.$quote['discount_description'].')',
            'discount' => $money($quote['discount']),
            'hasDiscount' => $quote['discount_applied'],
            'taxLabel' => 'Tax ('.number_format((float) $quote['tax_rate'], 2).'%)',
            'tax' => $money($quote['tax_amount']),
            'hasTax' => (float) $quote['tax_amount'] > 0,
            'chargeLabel' => 'Service charge ('.number_format((float) $quote['service_charge_rate'], 2).'%)',
            'charge' => $money($quote['service_charge']),
            'hasCharge' => (float) $quote['service_charge'] > 0,
            'total' => $money($quote['total']),
            'available' => $available,
            'message' => $message,
            'bookingType' => $type,
            'serviceId' => (int) $service->id,
            'startDate' => $start,
            'endDate' => $end,
            'travelers' => $travelers,
            // One token per result, so a replayed popup submission can only ever
            // resolve back to the booking that same card created.
            'submissionToken' => Str::random(40),
        ];
    }

    /**
     * The labelled detail rows a popup shows, per booking type.
     *
     * Each type answers with the details that actually apply to it: a car rental is
     * described by its pick-up and drop-off, a tour by its travel date and fixed
     * package length, and a stay by its check-in and check-out.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $quote
     * @return list<array{label: string, value: string}>
     */
    protected function summaryRows(string $type, mixed $service, array $filters, int $travelers, array $quote): array
    {
        $location = $service->location_label ?? null;
        $vehicleName = $service->name ?? null;
        $vehicleType = $service->type_label ?? null;

        $date = function (string $key) use ($filters): string {
            $value = $filters[$key] ?? null;

            return filled($value)
                ? CarbonImmutable::parse($value)->format('M j, Y')
                : 'Not selected';
        };

        // A length is only meaningful once both ends of the period are chosen.
        $hasPeriod = filled($filters['start_date'] ?? null) && filled($filters['end_date'] ?? null);

        // A row with nothing behind it is left out rather than shown empty.
        return array_values(array_filter(match ($type) {
            'hotel' => [
                $location ? ['label' => 'Destination', 'value' => $location] : null,
                $service->rating ? ['label' => 'Rating', 'value' => $service->rating.' star'] : null,
                ['label' => 'Check-in', 'value' => $date('start_date')],
                ['label' => 'Check-out', 'value' => $date('end_date')],
                $hasPeriod ? ['label' => 'Number of nights', 'value' => (string) $quote['quantity']] : null,
                ['label' => 'Guests', 'value' => (string) $travelers],
            ],
            'vehicle' => [
                $vehicleName ? ['label' => 'Vehicle name', 'value' => $vehicleName] : null,
                $vehicleType ? ['label' => 'Vehicle type', 'value' => $vehicleType] : null,
                $location ? ['label' => 'Location', 'value' => $location] : null,
                ['label' => 'Pick-up', 'value' => $date('start_date')],
                ['label' => 'Drop-off', 'value' => $date('end_date')],
                $hasPeriod ? ['label' => 'Rental duration', 'value' => (string) $quote['duration_label']] : null,
                ['label' => 'Passengers', 'value' => (string) $travelers],
            ],
            default => [
                $location ? ['label' => 'Destination', 'value' => $location] : null,
                ['label' => 'Travel date', 'value' => $date('start_date')],
                $service->duration ? ['label' => 'Duration', 'value' => (string) $service->duration] : null,
                ['label' => 'Travellers', 'value' => (string) $travelers],
            ],
        }));
    }

    /**
     * Readable "up to" steps spanning the real prices in the inventory, so the
     * max-price control can express a budget that actually excludes the more
     * expensive services rather than only the price of the dearest one.
     *
     * @param  Collection<int, mixed>  $prices
     * @return list<int>
     */
    protected function priceOptions(Collection $prices): array
    {
        $prices = $prices->map(fn (mixed $price) => (float) $price)->filter()->values();

        if ($prices->isEmpty()) {
            return [];
        }

        $steps = collect([2500, 5000, 10000, 25000, 50000, 100000]);

        // Keep only the steps the inventory can actually match on both sides: a
        // budget below the cheapest service is a no-op, and a step above the
        // dearest one filters nothing out.
        $cheapest = $prices->min();
        $dearest = $prices->max();

        return $steps
            ->filter(fn (int $step) => $step >= $cheapest && $step < $dearest)
            ->push((int) ceil($dearest / 1000) * 1000)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * The rental lengths a customer may pick, bounded by the configured maximum
     * so the control cannot offer a booking the rules will reject.
     *
     * @return list<int>
     */
    protected function rentalDurationOptions(): array
    {
        $max = (int) BookingSetting::getWithDefault('max_rental_days');

        return collect([1, 2, 3, 5, 7, 14, 30])
            ->filter(fn (int $days) => $days <= $max)
            ->values()
            ->all();
    }

    /**
     * The largest party a rentable vehicle in the inventory can seat.
     */
    protected function maxVehiclePassengers(): int
    {
        return max(1, (int) TransportVehicle::query()
            ->active()
            ->where('availability', true)
            ->max('seating_capacity'));
    }

    /**
     * The largest party an active tour can still take, where capacity is tracked.
     */
    protected function maxTourTravelers(): int
    {
        return max(1, (int) Tour::query()->active()->whereNotNull('capacity')->max('capacity'));
    }
}
