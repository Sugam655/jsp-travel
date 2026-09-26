<?php

namespace Modules\Bookings\Services;

use Carbon\CarbonImmutable;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Support\Money;

/**
 * Server-side calculation of cancellation charges and refund eligibility.
 *
 * The policy tiers used for a booking are snapshotted on the booking itself
 * when it is created, so historical cancellations always reflect the policy
 * that was actually in force at booking time.
 */
class CancellationPolicyService
{
    /**
     * The policy tiers in force for a booking (snapshot first, settings after).
     *
     * @return array<int, array{days: int, refund: int}>
     */
    public function policyFor(Booking $booking): array
    {
        $snapshot = $booking->policy_snapshot ?? null;

        if (is_array($snapshot) && isset($snapshot['cancellation_policy'])) {
            return $snapshot['cancellation_policy'];
        }

        $tiers = BookingSetting::getWithDefault('cancellation_policy');

        return is_array($tiers) ? $tiers : [];
    }

    /**
     * The full cancellation quote for a booking.
     *
     * @return array<string, mixed>
     */
    public function quoteFor(Booking $booking): array
    {
        $total = (float) ($booking->total_amount ?? 0);
        $start = $booking->start_date ? CarbonImmutable::parse($booking->start_date)->startOfDay() : null;

        if ($start === null || $start->isPast()) {
            return $this->notEligible('Cancellation is no longer available for this booking (the service date has passed).');
        }

        if (! $booking->isCustomerCancellable() && ! $booking->isAdminCancellable()) {
            return $this->notEligible('This booking can no longer be cancelled.');
        }

        $daysBefore = abs((int) CarbonImmutable::today()->startOfDay()->diffInDays($start->startOfDay()));
        $tier = $this->resolveTier($this->policyFor($booking), $daysBefore);

        $collected = (float) ($booking->paid_amount ?? 0);

        $refundAmount = Money::percent($total, $tier['refund']);

        if ($collected > 0) {
            $refundAmount = Money::round(min((float) $refundAmount, $collected));
        }

        $feeAmount = Money::round(max(0, $collected - (float) $refundAmount));

        return [
            'eligible' => true,
            'days_before' => $daysBefore,
            'refund_pct' => $tier['refund'],
            'fee_pct' => 100 - $tier['refund'],
            'refund_amount' => $refundAmount,
            'fee_amount' => $feeAmount,
            'collected' => $collected,
            'deadline' => $start->endOfDay(),
            'policy_used' => $this->policyFor($booking),
        ];
    }

    /**
     * The refund that applies after cancellation has been calculated.
     */
    public function plannedRefundFor(Booking $booking): float
    {
        $quote = $this->quoteFor($booking);

        return $quote['eligible'] ? (float) $quote['refund_amount'] : 0.0;
    }

    /**
     * The matching tier for a number of days before the service date.
     *
     * @param  array<int, array{days: int, refund: int}>  $tiers
     * @return array{days: int, refund: int}
     */
    protected function resolveTier(array $tiers, int $daysBefore): array
    {
        usort($tiers, fn ($a, $b) => (int) $b['days'] <=> (int) $a['days']);

        return collect($tiers)->first(
            fn ($tier) => $daysBefore >= (int) $tier['days'],
            ['days' => 0, 'refund' => 0]
        );
    }

    /**
     * A not-eligible quote payload.
     *
     * @return array<string, mixed>
     */
    protected function notEligible(string $reason): array
    {
        return [
            'eligible' => false,
            'reason' => $reason,
            'days_before' => 0,
            'refund_pct' => 0,
            'fee_pct' => 0,
            'refund_amount' => '0.00',
            'fee_amount' => '0.00',
            'collected' => 0.0,
            'deadline' => null,
            'policy_used' => [],
        ];
    }

    /**
     * Assert a quote is eligible, returning the quote payload.
     *
     * @return array<string, mixed>
     */
    public function eligibleOrFail(Booking $booking): array
    {
        $quote = $this->quoteFor($booking);

        abort_unless($quote['eligible'], 422, $quote['reason'] ?? 'Cancellation is not available for this booking.');

        return $quote;
    }
}
