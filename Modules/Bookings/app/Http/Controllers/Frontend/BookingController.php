<?php

namespace Modules\Bookings\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingChangeRequest;
use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Services\AvailabilityService;
use Modules\Bookings\Services\BookingRulesService;
use Modules\Bookings\Services\BookingWorkflowService;
use Modules\Bookings\Services\CancellationPolicyService;
use Modules\Bookings\Services\PaymentCalculationService;
use Modules\Bookings\Services\PriceCalculator;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingController extends Controller
{
    /**
     * Show the public booking request form. An optional type + slug query
     * parameter pre-fills the form for a specific tour, hotel or vehicle.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $type = $request->query('type');
        $slug = $request->query('slug');

        $service = null;
        $resolvedType = null;

        if ($type !== null || $slug !== null) {
            if (! in_array($type, Booking::TYPES, true) || blank($slug)) {
                abort(404);
            }

            $service = $this->resolveBySlug($type, (string) $slug);

            abort_unless($service !== null, 404);

            $resolvedType = $type;
        }

        $view = auth()->check() && ! auth()->user()->isAdmin()
            ? 'bookings::frontend.create-user'
            : 'bookings::frontend.create';

        return view($view, [
            'service' => $service,
            'serviceType' => $resolvedType,
            'types' => Booking::TYPE_LABELS,
            'tours' => Tour::query()->where('is_active', true)->orderBy('title')->get(['id', 'title', 'duration']),
            'hotels' => Hotel::query()->where('is_active', true)->orderBy('title')->get(['id', 'title', 'price']),
            'vehicles' => TransportVehicle::query()->where('is_active', true)->where('availability', true)->orderBy('name')->get(['id', 'name', 'price', 'price_unit']),
        ]);
    }

    /**
     * Quote + availability endpoint used by the booking form's live review.
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_type' => ['required', Rule::in(Booking::TYPES)],
            'service_id' => ['required', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'travelers' => ['nullable', 'integer', 'min:1'],
        ]);

        $service = $this->resolveById($validated['booking_type'], (int) $validated['service_id']);

        if ($service === null) {
            return response()->json(['available' => false, 'reason' => 'The selected service is not available.', 'quote' => null], 422);
        }

        $rules = (new BookingRulesService)->validate(
            $validated['booking_type'],
            $service,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
            isset($validated['travelers']) ? (int) $validated['travelers'] : null
        );

        if (! $rules['valid']) {
            return response()->json(['available' => false, 'reason' => $rules['errors'][0] ?? 'The requested dates are not valid.', 'quote' => null], 422);
        }

        $availability = (new AvailabilityService)->check(
            $validated['booking_type'],
            (int) $validated['service_id'],
            (string) $validated['start_date'],
            $validated['end_date'] ?? null,
            null,
            isset($validated['travelers']) ? (int) $validated['travelers'] : null
        );

        $quote = (new PriceCalculator)->quote(
            $validated['booking_type'],
            $service,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
            isset($validated['travelers']) ? (int) $validated['travelers'] : null
        );

        return response()->json([
            'available' => $availability['available'],
            'reason' => $availability['reason'],
            'quote' => $availability['available'] ? $quote : null,
        ]);
    }

    /**
     * Store a new public booking request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_type' => ['required', Rule::in(Booking::TYPES)],
            'service_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:500'],
            'travelers' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'message' => ['nullable', 'string', 'max:5000'],
            'policy_accepted' => ['required', 'accepted'],
        ]);

        $service = $this->resolveById($validated['booking_type'], (int) $validated['service_id']);

        if ($service === null) {
            return $this->bookingError('service_id', 'The selected service is not available.');
        }

        $rules = (new BookingRulesService)->validate(
            $validated['booking_type'],
            $service,
            $validated['start_date'],
            $validated['end_date'] ?? null,
            (int) $validated['travelers']
        );

        if (! $rules['valid']) {
            return $this->bookingError('booking', $rules['errors'][0] ?? 'The requested dates are not valid.');
        }

        $availability = (new AvailabilityService)->check(
            $validated['booking_type'],
            (int) $validated['service_id'],
            $validated['start_date'],
            $validated['end_date'] ?? null,
            null,
            (int) $validated['travelers']
        );

        if (! $availability['available']) {
            return $this->bookingError('booking', $availability['reason'] ?? 'This service is not available for the selected dates.');
        }

        try {
            $booking = app(BookingWorkflowService::class)->createBooking([
                'booking_type' => $validated['booking_type'],
                'service_id' => $validated['service_id'],
                'service_title' => $service->title ?? $service->name,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'] ?? null,
                'travelers' => (int) $validated['travelers'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'message' => $validated['message'] ?? null,
                'user_id' => $request->user()->id,
                'source' => 'web',
                'policy_accepted_at' => now(),
            ])['booking'];
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() === 422) {
                return $this->bookingError('booking', $exception->getMessage());
            }

            throw $exception;
        }

        Session::push('my_booking_references', $booking->booking_reference);
        Session::put('booking_email', $booking->email);

        return redirect()
            ->route('bookings.payment', $booking->booking_reference)
            ->with('just_booked', true)
            ->with('success', "Your booking {$booking->booking_reference} has been received.");
    }

    /**
     * The customer area: list bookings linked to this session (by reference)
     * or to the signed-in account.
     */
    public function my(): View
    {
        $references = array_unique(Session::get('my_booking_references', []));

        $bookings = Booking::query()
            ->where(function ($query) use ($references) {
                $query->whereIn('booking_reference', $references);

                if (auth()->check()) {
                    $query->orWhere('user_id', auth()->id());
                }
            })
            ->orderByDesc('created_at')
            ->get();

        $counts = [
            'total' => $bookings->count(),
            'pending' => $bookings->where('status', 'pending')->count(),
            'confirmed' => $bookings->where('status', 'confirmed')->count(),
            'payment_pending' => $bookings->where('status', 'payment_pending')->count(),
            'completed' => $bookings->where('status', 'completed')->count(),
            'cancelled' => $bookings->whereIn('status', ['cancelled', 'rejected', 'expired'])->count(),
        ];

        return view('bookings::frontend.my', [
            'bookings' => $bookings,
            'counts' => $counts,
        ]);
    }

    /**
     * Look up a booking by reference + email to prove access.
     */
    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_reference' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $booking = Booking::query()
            ->where('booking_reference', strtoupper((string) $validated['booking_reference']))
            ->whereRaw('LOWER(email) = ?', [strtolower((string) $validated['email'])])
            ->first();

        abort_unless($booking !== null, 422, 'No booking matches the reference and email you provided.');

        if (auth()->check() && $this->emailMatchesAccount($booking->email, $request->user()?->email)) {
            $booking->user_id = $request->user()?->id;
            $booking->save();
        }

        Session::push('my_booking_references', $booking->booking_reference);
        Session::put('booking_email', $booking->email);

        return redirect()
            ->route('bookings.my')
            ->with('success', 'Booking '.$booking->booking_reference.' linked to your session.');
    }

    /**
     * Show a single booking with its timeline and payment information.
     */
    public function show(Booking $booking): View
    {
        $this->authorizeAccess($booking);

        $view = auth()->check() && ! auth()->user()->isAdmin()
            ? 'bookings::frontend.show-user'
            : 'bookings::frontend.show';

        return view($view, [
            'booking' => $booking,
            'statuses' => Booking::STATUS_LABELS,
            'summary' => (new PaymentCalculationService)->summaryFor($booking),
        ]);
    }

    /**
     * The cancellation confirmation screen with the policy quote.
     */
    public function cancelPreview(Booking $booking): View
    {
        $this->authorizeAccess($booking);

        $view = auth()->check() && ! auth()->user()->isAdmin()
            ? 'bookings::frontend.cancel-user'
            : 'bookings::frontend.cancel';

        return view($view, [
            'booking' => $booking,
            'quote' => (new CancellationPolicyService)->quoteFor($booking),
        ]);
    }

    /**
     * Confirm the cancellation (customer side).
     */
    public function cancel(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        $this->authorizeAccess($booking);

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        $workflow->cancel($booking, 'customer', $validated['reason'] ?? null, $request->user());

        return redirect()
            ->route('bookings.show', $booking->booking_reference)
            ->with('success', 'Your booking has been cancelled.');
    }

    /**
     * The payment page: price breakdown and available methods.
     */
    public function payment(Booking $booking): View
    {
        $this->authorizeAccess($booking);

        $view = auth()->check() && ! auth()->user()->isAdmin()
            ? 'bookings::frontend.payment-user'
            : 'bookings::frontend.payment';
        $methods = collect(BookingSetting::getWithDefault('payment_methods'))
            ->filter(fn ($method) => is_array($method) && filled($method['method'] ?? null))
            ->values()
            ->all();

        return view($view, [
            'booking' => $booking,
            'methods' => $methods,
            'summary' => (new PaymentCalculationService)->summaryFor($booking),
            'pendingPayment' => $booking->payments()->where('status', 'pending')->latest()->first(),
        ]);
    }

    /**
     * Customer reports that they paid (awaiting admin verification).
     */
    public function notifyPayment(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        $this->authorizeAccess($booking);

        abort_unless(in_array($booking->status, ['confirmed', 'payment_pending'], true), 422, 'Payment can only be reported for a confirmed booking.');

        if ($booking->payments()->where('status', 'pending')->exists()) {
            return redirect()
                ->route('bookings.payment', $booking->booking_reference)
                ->withErrors(['payment' => 'You already have a pending payment report for this booking.']);
        }

        $validated = $request->validate([
            'method' => ['required', 'string'],
            'reference' => [Rule::requiredIf(fn () => $request->input('method') !== 'cash'), 'nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'message' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max('5mb')],
        ]);

        try {
            $workflow->recordCustomerPayment(
                $booking,
                $validated['method'],
                $validated['reference'] ?? null,
                (string) $validated['amount'],
                $request->user(),
                note: $validated['message'] ?? null,
                receipt: $request->file('receipt'),
            );
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() === 422) {
                $message = $exception->getMessage();
                $errorKey = str_contains($message, 'method')
                    ? 'method'
                    : (str_contains($message, 'reference') ? 'reference' : 'amount');
                $errors = [
                    $errorKey => $message,
                    'payment' => $message,
                ];

                return redirect()
                    ->route('bookings.payment', $booking->booking_reference)
                    ->withInput($request->except('receipt'))
                    ->withErrors($errors);
            }

            throw $exception;
        }

        return redirect()
            ->route('bookings.payment', $booking->booking_reference)
            ->with('success', 'Thank you! We have recorded your payment evidence and will verify it shortly.');
    }

    /**
     * Raise a change request for a booking.
     */
    public function changeRequest(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        $this->authorizeAccess($booking);

        if ($booking->changeRequests()->where('status', 'pending')->exists()) {
            return redirect()
                ->route('bookings.show', $booking->booking_reference)
                ->withErrors(['change_request' => 'You already have a pending change request for this booking.']);
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(BookingChangeRequest::TYPES)],
            'requested' => ['required', 'array'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $clean = array_filter($validated['requested'], fn ($value) => filled($value));

        $workflow->raiseChangeRequest($booking, $validated['type'], $clean, $validated['reason'] ?? null, $request->user());

        return redirect()
            ->route('bookings.show', $booking->booking_reference)
            ->with('success', 'Your change request has been submitted and will be reviewed.');
    }

    /**
     * Ensure a booking is accessible to the visitor: an authenticated owner
     * match, or the email address they validated via "My Bookings".
     */
    protected function authorizeAccess(Booking $booking): void
    {
        if (auth()->check() && $booking->user_id !== null && (int) $booking->user_id === (int) auth()->id()) {
            return;
        }

        abort_unless(
            $booking->email !== null && strtolower((string) Session::get('booking_email')) === strtolower($booking->email),
            403,
            'This booking is not linked to your session. Use "My Bookings" with your reference and email.'
        );
    }

    /**
     * Resolve a service by slug for the requested booking type.
     */
    private function resolveBySlug(string $type, string $slug): mixed
    {
        return match ($type) {
            'tour' => Tour::query()->where('is_active', true)->where('slug', $slug)->first(),
            'hotel' => Hotel::query()->where('is_active', true)->where('slug', $slug)->first(),
            'vehicle' => TransportVehicle::query()->where('is_active', true)->where('availability', true)->where('slug', $slug)->first(),
            default => null,
        };
    }

    /**
     * Resolve a service by id for the requested booking type.
     */
    private function resolveById(string $type, int $id): mixed
    {
        return match ($type) {
            'tour' => Tour::query()->where('is_active', true)->find($id),
            'hotel' => Hotel::query()->where('is_active', true)->find($id),
            'vehicle' => TransportVehicle::query()->where('is_active', true)->where('availability', true)->find($id),
            default => null,
        };
    }

    /**
     * Whether the booking email belongs to the given account (case-insensitive).
     */
    private function emailMatchesAccount(?string $bookingEmail, ?string $accountEmail): bool
    {
        return $bookingEmail !== null
            && $accountEmail !== null
            && strtolower($bookingEmail) === strtolower($accountEmail);
    }

    /**
     * Redirect back to the booking form with a user-friendly error instead of
     * surfacing a raw 422 page.
     */
    private function bookingError(string $key, string $message): RedirectResponse
    {
        return back()->withInput()->withErrors([$key => $message]);
    }
}
