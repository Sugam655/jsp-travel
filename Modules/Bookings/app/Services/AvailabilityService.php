<?php

namespace Modules\Bookings\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Bookings\Models\Booking;
use Modules\Tours\Models\Tour;

/**
 * Real availability checking for hotels, vehicles and tours.
 *
 * Availability is always resolved server-side and re-checked inside a
 * transaction with row locks so two customers cannot book the same room or
 * vehicle for overlapping dates.
 */
class AvailabilityService
{
    /**
     * Check whether a service is available for the requested period.
     *
     * @return array{available: bool, reason: string|null, conflicts: int}
     */
    public function check(
        string $bookingType,
        int $serviceId,
        string $startDate,
        ?string $endDate,
        ?string $excludeBooking = null,
        ?int $travelers = null,
        bool $lock = false
    ): array {
        if ($bookingType === 'tour') {
            // A single-day tour occupies exactly the selected date (half-open
            // [start, start + 1 day) so two tours on the same day conflict).
            $windowEnd = $endDate ?: CarbonImmutable::parse($startDate)->addDay()->toDateString();

            return $this->checkTour($serviceId, $startDate, $windowEnd, $excludeBooking, $travelers ?? 1, $lock);
        }

        return $this->checkOverlap($bookingType, $serviceId, $startDate, $endDate, $excludeBooking, $lock);
    }

    /**
     * Hotels and vehicles: overlapping reservations for the same item.
     *
     * @return array{available: bool, reason: string|null, conflicts: int}
     */
    protected function checkOverlap(
        string $bookingType,
        int $serviceId,
        string $startDate,
        ?string $endDate,
        ?string $excludeBooking,
        bool $lock
    ): array {
        if ($endDate === null || $startDate >= $endDate) {
            return ['available' => false, 'reason' => 'Invalid date range.', 'conflicts' => 0];
        }

        $conflicts = $this->overlappingReservations($bookingType, $serviceId, $startDate, $endDate, $excludeBooking, $lock);

        if ($conflicts->isNotEmpty()) {
            $label = $bookingType === 'hotel' ? 'room' : 'vehicle';

            return [
                'available' => false,
                'reason' => "Sorry, this {$label} is not available for the selected dates.",
                'conflicts' => $conflicts->count(),
            ];
        }

        return ['available' => true, 'reason' => null, 'conflicts' => 0];
    }

    /**
     * Tours: capacity check based on the configured available seats.
     *
     * @return array{available: bool, reason: string|null, conflicts: int}
     */
    protected function checkTour(int $serviceId, string $startDate, ?string $endDate, ?string $excludeBooking, int $travelers, bool $lock = false): array
    {
        $tour = Tour::query()->find($serviceId);

        if ($tour?->capacity === null) {
            return ['available' => true, 'reason' => null, 'conflicts' => 0];
        }

        $range = $this->overlappingReservations('tour', $serviceId, $startDate, $endDate ?? $startDate, $excludeBooking, $lock);

        $booked = (int) $range->sum('travelers');

        if ($booked + $travelers > $tour->capacity) {
            $remaining = max(0, $tour->capacity - $booked);

            return [
                'available' => false,
                'reason' => $remaining > 0
                    ? "Sorry, only {$remaining} seat(s) remain on this tour for the selected dates."
                    : 'Sorry, this tour is fully booked for the selected dates.',
                'conflicts' => $range->count(),
            ];
        }

        return ['available' => true, 'reason' => null, 'conflicts' => 0];
    }

    /**
     * The currently-holding reservations that overlap the requested period.
     *
     * Pending bookings that have passed their payment/confirmation deadline are
     * treated as released (they are also lazily marked expired). Pass $lock to
     * lock the matched rows for update (used inside the booking transaction to
     * prevent double-booking).
     *
     * @return Collection<int, Booking>
     */
    public function overlappingReservations(
        string $bookingType,
        int $serviceId,
        string $startDate,
        string $endDate,
        ?string $excludeBooking = null,
        bool $lock = false
    ): Collection {
        return $this->overlappingQuery($bookingType, [$serviceId], $startDate, $endDate, $excludeBooking, $lock)->get();
    }

    /**
     * The ids from $serviceIds that cannot be reserved for the requested period.
     *
     * The search page filters a whole result set rather than one service, so it
     * asks which ids are blocked in a single query instead of running the
     * per-service check once per card.
     *
     * Tours share seats rather than being taken whole, so a blocked tour is not
     * a single id and stays with the per-booking check() call.
     *
     * @param  list<int>  $serviceIds
     * @return list<int>
     */
    public function unavailableServiceIds(
        string $bookingType,
        array $serviceIds,
        string $startDate,
        ?string $endDate,
        ?string $excludeBooking = null
    ): array {
        if ($serviceIds === [] || $bookingType === 'tour') {
            return [];
        }

        // Without a complete, forward-moving range nothing can overlap.
        if ($endDate === null || $startDate >= $endDate) {
            return [];
        }

        return $this->overlappingQuery($bookingType, $serviceIds, $startDate, $endDate, $excludeBooking, false)
            ->pluck('service_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The one definition of "this reservation still holds the item", shared by
     * the per-service check and the bulk search filter so both agree on which
     * bookings count.
     *
     * @param  list<int>  $serviceIds
     * @return Builder<Booking>
     */
    protected function overlappingQuery(
        string $bookingType,
        array $serviceIds,
        string $startDate,
        string $endDate,
        ?string $excludeBooking,
        bool $lock
    ): Builder {
        $query = Booking::query()
            ->where('booking_type', $bookingType)
            ->whereIn('service_id', $serviceIds)
            ->whereIn('status', Booking::RESERVING_STATUSES)
            ->where(fn ($q) => $q
                ->whereDate('start_date', '<', $endDate)
                ->where(fn ($q) => $q
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>', $startDate)))
            ->where(fn ($q) => $q
                ->where('status', '!=', 'pending')
                ->orWhereNull('expires_at')
                ->orWhere('expires_at', '>', now()));

        if ($excludeBooking !== null) {
            $query->where('booking_reference', '!=', $excludeBooking);
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query;
    }
}
