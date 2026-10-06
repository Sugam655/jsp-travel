<?php

namespace Modules\Bookings\Services;

use Carbon\CarbonImmutable;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingSetting;

/**
 * Server-side date/period validation for booking requests.
 *
 * The frontend never owns these rules: every booking is re-validated here
 * against the real service configuration before anything is recorded.
 */
class BookingRulesService
{
    /**
     * Validate a booking request's core fields against the service rules.
     *
     * @return array{valid: bool, errors: array<int, string>}
     */
    public function validate(
        string $bookingType,
        mixed $service,
        ?string $startDate,
        ?string $endDate,
        ?int $travelers
    ): array {
        $errors = [];

        $start = $startDate ? CarbonImmutable::parse($startDate)->startOfDay() : null;
        $end = $endDate ? CarbonImmutable::parse($endDate)->startOfDay() : null;
        $today = CarbonImmutable::today();

        if ($service === null) {
            return ['valid' => false, 'errors' => ['The selected service is not available.']];
        }

        if (isset($service->is_active) && ! (bool) $service->is_active) {
            $errors[] = 'This '.$bookingType.' is not currently accepting bookings.';
        }

        if ($bookingType === 'vehicle' && isset($service->availability) && ! (bool) $service->availability) {
            $errors[] = 'This vehicle is not currently available for rent.';
        }

        if ($bookingType === 'vehicle' && $travelers !== null) {
            $capacity = (int) ($service->seating_capacity ?? 0);
            if ($capacity > 0 && $travelers > $capacity) {
                $errors[] = 'This vehicle can accommodate a maximum of '.$capacity.' passengers.';
            }
        }

        if ($bookingType === 'tour' && $start === null) {
            $errors[] = 'A start date is required for tour bookings.';
        }

        if (in_array($bookingType, ['hotel', 'vehicle'], true)) {
            if ($start === null) {
                $errors[] = 'A start (check-in / pickup) date is required.';
            }
            if ($end === null) {
                $errors[] = 'An end (check-out / return) date is required.';
            }
        }

        if ($start !== null) {
            if ($start->lessThan($today)) {
                $errors[] = 'The service start date cannot be in the past.';
            }

            $maxHorizon = (int) BookingSetting::getWithDefault('max_booking_horizon');
            if ($start->greaterThan($today->addDays($maxHorizon))) {
                $errors[] = "Bookings can only be placed up to {$maxHorizon} days in advance.";
            }
        }

        if ($start !== null && $end !== null) {
            if (! $end->greaterThan($start)) {
                $errors[] = $this->endDateOrderMessage($bookingType);
            }

            if ($bookingType === 'tour' && ! $this->tourDurationMatches($service, $start, $end)) {
                $errors[] = 'The selected dates do not match the tour duration ('.$service->duration.').';
            }

            if ($bookingType === 'hotel' && ! $this->hotelStayWithinLimits($start, $end)) {
                $errors[] = 'The requested stay '.$this->nights($start, $end).' night(s) exceeds the maximum allowed stay of '.(int) BookingSetting::getWithDefault('max_hotel_stay').' nights.';
            }

            if ($bookingType === 'vehicle' && ! $this->rentalWithinLimits($service, $start, $end)) {
                $errors[] = 'The requested rental duration exceeds the maximum allowed rental period of '.(int) BookingSetting::getWithDefault('max_rental_days').' days.';
            }
        }

        if (in_array($bookingType, ['tour', 'hotel', 'vehicle'], true) && ($travelers === null || $travelers < 1)) {
            $errors[] = 'Please enter the number of travelers/guests.';
        }

        return ['valid' => count($errors) === 0, 'errors' => $errors];
    }

    /**
     * The wording this rule reports when the end date is not after the start.
     *
     * Exposed so a page can label the field with exactly what the server is
     * going to say, rather than restating the rule in a second, different set of
     * words that then contradicts it.
     */
    public function endDateOrderMessage(string $bookingType): string
    {
        return match ($bookingType) {
            'hotel' => 'Check-out date must be after the check-in date.',
            'vehicle' => 'Return date must be after the pickup date.',
            default => 'The end date must be after the start date.',
        };
    }

    /**
     * The largest party this service can actually take.
     *
     * A vehicle's seat count is a hard limit the rules enforce anyway; hotels and
     * tours have no stored capacity unless a tour is capped, so they use the same
     * ceiling the booking request validates against.
     */
    public function maxTravelers(string $bookingType, mixed $service): int
    {
        $maxTravelers = (int) config('booking.max_travelers', 100);

        if ($bookingType === 'vehicle') {
            $capacity = (int) ($service->seating_capacity ?? 0);

            return $capacity > 0 ? $capacity : $maxTravelers;
        }

        if ($bookingType === 'tour' && (int) ($service->capacity ?? 0) > 0) {
            return (int) $service->capacity;
        }

        return $maxTravelers;
    }

    /**
     * The party size a brand new booking request starts from.
     *
     * Derived per service rather than fixed, so a small vehicle never opens on a
     * party it cannot seat. The search page starts its confirmation popups from
     * the same number, so the two can never disagree about the party being
     * booked.
     */
    public function defaultTravelers(string $bookingType, mixed $service): int
    {
        return min(2, $this->maxTravelers($bookingType, $service));
    }

    /**
     * The number of nights between two dates.
     */
    public function nights(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return (int) $start->diffInDays($end);
    }

    /**
     * The number of date-days between two dates (min 1).
     */
    public function days(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return max(1, (int) $start->diffInDays($end));
    }

    /**
     * Tours with a configured numeric duration must match it exactly.
     */
    protected function tourDurationMatches(mixed $service, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        if (! isset($service->duration_days) || $service->duration_days === null) {
            return true;
        }

        return (int) $service->duration_days === $this->days($start, $end);
    }

    /**
     * Hotel stays must not exceed the configurable limit.
     */
    protected function hotelStayWithinLimits(CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $this->nights($start, $end) <= (int) BookingSetting::getWithDefault('max_hotel_stay');
    }

    /**
     * Vehicle rentals must not exceed the configurable limit.
     */
    protected function rentalWithinLimits(mixed $service, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $this->days($start, $end) <= (int) BookingSetting::getWithDefault('max_rental_days');
    }

    /**
     * A convenience guard reused by tests/documentation.
     */
    public static function statusHoldsInventory(string $status): bool
    {
        return in_array($status, Booking::RESERVING_STATUSES, true);
    }
}
