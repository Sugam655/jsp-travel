<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Support\Coordinates;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\Payment;
use Modules\Contact\Models\ContactMessage;
use Modules\Home\Models\HomeDestination;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;

class DashboardService
{
    /**
     * The supported booking chart ranges, in days.
     *
     * @var array<int, int>
     */
    public const RANGES = [7, 30, 90, 180, 365];

    /**
     * Build the headline counts for the summary cards.
     *
     * @return array<string, int>
     */
    public function overview(): array
    {
        return [
            'bookings_total' => Booking::query()->count(),
            'bookings_pending' => Booking::query()->where('status', 'pending')->count(),
            'bookings_confirmed' => Booking::query()->where('status', 'confirmed')->count(),
            'bookings_payment_pending' => Booking::query()->where('status', 'payment_pending')->count(),
            'payments_pending' => Payment::query()->where('status', 'pending')->count(),
            'bookings_paid' => Booking::query()->where('status', 'paid')->count(),
            'bookings_cancelled' => Booking::query()->where('status', 'cancelled')->count(),
            'bookings_completed' => Booking::query()->where('status', 'completed')->count(),
            'tours_total' => Tour::query()->count(),
            'tours_active' => Tour::query()->active()->count(),
            'hotels_total' => Hotel::query()->count(),
            'hotels_active' => Hotel::query()->active()->count(),
            'vehicles_total' => TransportVehicle::query()->count(),
            'vehicles_available' => TransportVehicle::query()->where('availability', true)->count(),
            'messages_total' => ContactMessage::query()->count(),
            'messages_unread' => ContactMessage::query()->where('status', 'new')->count(),
            'messages_today' => ContactMessage::query()->whereDate('created_at', today())->count(),
            'users_total' => User::query()->count(),
        ];
    }

    /**
     * Payment/refund rollups across the booking ledger.
     *
     * @return array<string, float>
     */
    public function paymentStats(): array
    {
        $paid = (float) Booking::query()
            ->join('payments', 'bookings.id', '=', 'payments.booking_id')
            ->where('payments.status', 'paid')
            ->sum('payments.amount');

        $refundedProcessed = (float) Booking::query()
            ->join('refunds', 'bookings.id', '=', 'refunds.booking_id')
            ->where('refunds.status', 'processed')
            ->sum('refunds.amount');

        $refundedPending = (float) Booking::query()
            ->join('refunds', 'bookings.id', '=', 'refunds.booking_id')
            ->where('refunds.status', 'pending')
            ->sum('refunds.amount');

        $outstandingBookings = Booking::query()
            ->whereIn('status', Booking::RESERVING_STATUSES)
            ->withSum([
                'payments as ledger_paid' => fn ($query) => $query->where('status', 'paid'),
            ], 'amount')
            ->withSum([
                'refunds as ledger_refunded' => fn ($query) => $query->where('status', 'processed'),
            ], 'amount')
            ->get(['total_amount']);

        $outstanding = $outstandingBookings->sum(fn (Booking $booking) => max(0.0, (float) $booking->total_amount - ((float) $booking->ledger_paid - (float) $booking->ledger_refunded)));

        return [
            'collected' => $paid,
            'outstanding' => $outstanding,
            'processed_refunds' => $refundedProcessed,
            'pending_refunds' => $refundedPending,
            'net' => $paid - $refundedProcessed,
        ];
    }

    /**
     * Daily booking and inquiry counts for a date range.
     *
     * @return array{labels: array<int, string>, bookings: array<int, int>, messages: array<int, int>, bookings_total: int}
     */
    public function series(string $from, string $to): array
    {
        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->startOfDay();

        $labels = [];
        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $labels[] = $date->format('d M');
        }

        $bookings = Booking::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end->copy()->endOfDay()])
            ->groupBy('day')
            ->pluck('total', 'day');

        $messages = ContactMessage::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end->copy()->endOfDay()])
            ->groupBy('day')
            ->pluck('total', 'day');

        $bookingsSeries = [];
        $messagesSeries = [];

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $key = $date->format('Y-m-d');
            $bookingsSeries[] = (int) ($bookings[$key] ?? 0);
            $messagesSeries[] = (int) ($messages[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'bookings' => $bookingsSeries,
            'messages' => $messagesSeries,
            'bookings_total' => array_sum($bookingsSeries),
        ];
    }

    /**
     * Booking counts grouped by status, in display order.
     *
     * @return array<int, array{key: string, label: string, color: string, count: int}>
     */
    public function bookingStatusCounts(): array
    {
        $counts = Booking::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $chartColors = [
            'pending' => '#f39c12',
            'confirmed' => '#0dcaf0',
            'cancelled' => '#dc3545',
            'completed' => '#198754',
        ];

        return collect(Booking::STATUSES)->map(fn ($status) => [
            'key' => $status,
            'label' => Booking::STATUS_LABELS[$status],
            'color' => $chartColors[$status] ?? '#6c757d',
            'count' => (int) ($counts[$status] ?? 0),
        ])->all();
    }

    /**
     * Inquiry counts grouped by message status, in display order.
     *
     * @return array<int, array{key: string, label: string, color: string, count: int}>
     */
    public function messageStatusCounts(): array
    {
        $counts = ContactMessage::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $chartColors = [
            'new' => '#0d6efd',
            'read' => '#0dcaf0',
            'replied' => '#198754',
            'archived' => '#6c757d',
        ];

        return collect(ContactMessage::STATUSES)->map(fn ($status) => [
            'key' => $status,
            'label' => ContactMessage::STATUS_LABELS[$status],
            'color' => $chartColors[$status] ?? '#6c757d',
            'count' => (int) ($counts[$status] ?? 0),
        ])->all();
    }

    /**
     * The most booked services for a booking type.
     *
     * @return array<int, array{service: string, count: int}>
     */
    public function mostBooked(string $type, int $limit = 5): array
    {
        $rows = Booking::query()
            ->selectRaw('service_title as service, COUNT(*) as count')
            ->where('booking_type', $type)
            ->whereNotNull('service_title')
            ->where('service_title', '!=', '')
            ->groupBy('service_title')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($row) => [
            'service' => $row->service,
            'count' => (int) $row->count,
        ])->all();
    }

    /**
     * Vehicle counts grouped by vehicle type, in display order.
     *
     * @return array<int, array{key: string, label: string, count: int}>
     */
    public function vehicleTypeCounts(): array
    {
        $counts = TransportVehicle::query()
            ->selectRaw('vehicle_type, COUNT(*) as total')
            ->groupBy('vehicle_type')
            ->pluck('total', 'vehicle_type');

        return collect(TransportVehicle::TYPES)->map(fn ($type) => [
            'key' => $type,
            'label' => TransportVehicle::TYPE_LABELS[$type] ?? $type,
            'count' => (int) ($counts[$type] ?? 0),
        ])->all();
    }

    /**
     * Content counts (tours, hotels, vehicles) grouped by destination.
     *
     * @return array<int, array{name: string, tours: int, hotels: int, vehicles: int}>
     */
    public function contentByDestination(): array
    {
        return HomeDestination::query()
            ->withCount(['tours', 'hotels', 'vehicles'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (HomeDestination $destination) => [
                'name' => $destination->name,
                'tours' => $destination->tours_count,
                'hotels' => $destination->hotels_count,
                'vehicles' => $destination->vehicles_count,
            ])
            ->values()
            ->all();
    }

    /**
     * Destination markers for the map, resolving stored or reference
     * coordinates and the real content counts per destination.
     *
     * @return array<int, array{name: string, location: string|null, lat: float, lng: float, tours: int, hotels: int, vehicles: int, url: string}>
     */
    public function destinationMarkers(): array
    {
        return HomeDestination::query()
            ->withCount(['tours', 'hotels', 'vehicles'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (HomeDestination $destination) {
                $coordinates = $destination->coordinates
                    ?? Coordinates::fromName($destination->name ?? '')
                    ?? Coordinates::fromName($destination->location ?? '');

                if ($coordinates === null) {
                    return null;
                }

                return [
                    'name' => $destination->name,
                    'location' => $destination->location,
                    'lat' => $coordinates['lat'],
                    'lng' => $coordinates['lng'],
                    'tours' => $destination->tours_count,
                    'hotels' => $destination->hotels_count,
                    'vehicles' => $destination->vehicles_count,
                    'url' => route('admin.home.destinations.edit', $destination),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The most recent real activity across bookings and content changes.
     *
     * @return array<int, array{type: string, label: string, value: string|null, time: Carbon, url: string|null}>
     */
    public function recentActivity(int $limit = 12): array
    {
        $sources = [];

        Booking::query()
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'name', 'service_title', 'created_at'])
            ->each(function (Booking $booking) use (&$sources) {
                $sources[] = [
                    'type' => 'booking',
                    'label' => 'New booking from '.$booking->name,
                    'value' => $booking->service_title,
                    'time' => $booking->created_at,
                    'url' => route('admin.bookings.show', $booking),
                ];
            });

        ContactMessage::query()
            ->latest('created_at')
            ->limit(4)
            ->get(['id', 'name', 'subject', 'created_at'])
            ->each(function (ContactMessage $message) use (&$sources) {
                $sources[] = [
                    'type' => 'message',
                    'label' => 'New inquiry from '.$message->name,
                    'value' => $message->subject,
                    'time' => $message->created_at,
                    'url' => route('admin.contact.messages.show', $message),
                ];
            });

        $this->contentActivity(Tour::class, 'tour', 'tour', 'admin.tours.edit', $sources, 3);
        $this->contentActivity(Hotel::class, 'hotel', 'hotel', 'admin.hotels.edit', $sources, 3);
        $this->contentActivity(TransportVehicle::class, 'vehicle', 'vehicle', 'admin.transport.edit', $sources, 3);

        return collect($sources)
            ->sortByDesc('time')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * The most recent bookings.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Booking>
     */
    public function recentBookings(int $limit = 8): Collection
    {
        return Booking::query()
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * The most recent contact messages.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ContactMessage>
     */
    public function recentInquiries(int $limit = 6): Collection
    {
        return ContactMessage::query()
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Merge new and recently updated records of a content model into the
     * activity feed using real timestamps.
     *
     * @param  class-string  $model
     * @param  array<int, mixed>  $sources
     */
    protected function contentActivity(string $model, string $type, string $labelVerb, string $editRoute, array &$sources, int $limit): void
    {
        $titleColumn = $model === TransportVehicle::class ? 'name' : 'title';

        $model::query()
            ->latest('created_at')
            ->limit($limit)
            ->get(['id', $titleColumn, 'created_at', 'updated_at'])
            ->each(function ($record) use (&$sources, $type, $labelVerb, $editRoute, $titleColumn) {
                $sources[] = [
                    'type' => $type,
                    'label' => "New {$labelVerb}: {$record->{$titleColumn}}",
                    'value' => null,
                    'time' => $record->created_at,
                    'url' => route($editRoute, $record),
                ];

                if ($record->updated_at->greaterThan($record->created_at)) {
                    $sources[] = [
                        'type' => $type,
                        'label' => "Updated {$labelVerb}: {$record->{$titleColumn}}",
                        'value' => null,
                        'time' => $record->updated_at,
                        'url' => route($editRoute, $record),
                    ];
                }
            });
    }
}
