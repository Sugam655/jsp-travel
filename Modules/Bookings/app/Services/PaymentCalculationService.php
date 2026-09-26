<?php

namespace Modules\Bookings\Services;

use Carbon\CarbonImmutable;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Support\Money;

/**
 * The single place that turns the per-service payment rules into the concrete
 * advance amount, the due date, and the type of each payment attempt. Every
 * amount is computed here server-side from the stored total, never from a
 * value submitted by the customer.
 */
class PaymentCalculationService
{
    /**
     * The supported payment modes.
     *
     * @var array<int, string>
     */
    public const MODES = ['full_payment', 'percentage_advance', 'fixed_advance'];

    /**
     * Human friendly labels for the payment modes.
     *
     * @var array<string, string>
     */
    public const MODE_LABELS = [
        'full_payment' => 'Full payment up front',
        'percentage_advance' => 'Percentage advance',
        'fixed_advance' => 'Fixed advance amount',
    ];

    /**
     * The supported remaining-due timings.
     *
     * @var array<int, string>
     */
    public const TIMINGS = ['before_service', 'on_service_start', 'custom_deadline'];

    /**
     * Human friendly labels for the remaining-due timings.
     *
     * @var array<string, string>
     */
    public const TIMING_LABELS = [
        'before_service' => 'Before the service date',
        'on_service_start' => 'On the service start date',
        'custom_deadline' => 'Custom deadline before service',
    ];

    public const OVERPAYMENT_POLICIES = ['reject'];

    public const OVERPAYMENT_POLICY_LABELS = [
        'reject' => 'Reject payment reports above the outstanding balance',
    ];

    /**
     * The resolved, normalised rules for a service type (tour/hotel/vehicle).
     * When a type has no configured rules a safe default of a 30% advance is
     * used, and the "configured" flag allows the UI to surface that fact.
     *
     * @return array<string, mixed>
     */
    public function rulesFor(string $bookingType): array
    {
        $all = BookingSetting::getWithDefault('payment_rules');
        $raw = is_array($all) && isset($all[$bookingType]) && is_array($all[$bookingType])
            ? $all[$bookingType]
            : [];

        return $this->normaliseRules($raw, $raw !== []);
    }

    public function rulesForBooking(Booking $booking): array
    {
        $raw = data_get($booking->policy_snapshot, 'payment.rules');

        if (! is_array($raw) || $raw === []) {
            return $this->rulesFor($booking->booking_type);
        }

        return $this->normaliseRules($raw, true);
    }

    public function overpaymentPolicyFor(Booking $booking): string
    {
        $policy = data_get($booking->policy_snapshot, 'payment.overpayment_policy');

        if (! in_array($policy, self::OVERPAYMENT_POLICIES, true)) {
            $policy = BookingSetting::getWithDefault('payment_overpayment_policy');
        }

        return in_array($policy, self::OVERPAYMENT_POLICIES, true) ? $policy : 'reject';
    }

    /**
     * The minimum amount the customer must pay before the booking is confirmed.
     */
    public function advanceFor(Booking $booking): float
    {
        $total = (float) ($booking->total_amount ?? 0);

        if ($total <= 0) {
            return 0.0;
        }

        $rules ??= $this->rulesForBooking($booking);

        return match ($rules['payment_mode']) {
            'full_payment' => $total,
            'fixed_advance' => min($total, $rules['advance_fixed_amount']),
            default => min($total, (float) Money::percent($total, $rules['advance_percentage'])),
        };
    }

    /**
     * The date the remaining balance becomes due, or null when no start date
     * exists (e.g. charge-as-you-go service bookings).
     */
    public function dueDateFor(Booking $booking, ?array $rules = null): ?string
    {
        if ($booking->start_date === null) {
            return null;
        }

        $rules ??= $this->rulesForBooking($booking);

        $start = CarbonImmutable::parse($booking->start_date->toDateString())->startOfDay();

        if ($rules['remaining_due_timing'] === 'custom_deadline') {
            return $start->subDays((int) $rules['custom_deadline_days'])->toDateString();
        }

        return $start->toDateString();
    }

    /**
     * Classify a payment attempt: the first payment on an unpaid booking is
     * either "full" (covers the whole total) or "advance"; later attempts that
     * clear the balance become "remaining"; anything in between is "partial".
     */
    public function typeFor(Booking $booking, float $amount): string
    {
        $total = (float) ($booking->total_amount ?? 0);
        $paid = $booking->settledAmount();
        $remaining = max(0.0, $total - $paid);

        if ($amount > $remaining + 0.005) {
            return 'overpayment';
        }

        if ($paid < 0.005 && $total > 0 && $amount >= $total - 0.005) {
            return 'full';
        }

        if ($paid < 0.005) {
            return 'advance';
        }

        if ($amount >= max(0.0, $total - $paid) - 0.005) {
            return 'remaining';
        }

        return 'partial';
    }

    /**
     * A durable snapshot of the payment plan for a booking, used by the admin
     * Payment Summary and the customer payment pages.
     *
     * @return array<string, mixed>
     */
    public function summaryFor(Booking $booking): array
    {
        $rules = $this->rulesForBooking($booking);
        $total = (float) ($booking->total_amount ?? 0);
        $advance = $booking->advance_amount !== null
            ? (float) $booking->advance_amount
            : $this->advanceFor($booking);
        $paid = $booking->settledAmount();
        $due = max(0.0, round($total - $paid, 2));
        $advanceRemaining = max(0.0, round($advance - $paid, 2));
        $requiredNow = $advanceRemaining > 0.005 ? $advanceRemaining : $due;
        $overpaymentPolicy = $this->overpaymentPolicyFor($booking);

        return [
            'total' => $total,
            'advance_required' => $advance,
            'advance_remaining' => $advanceRemaining,
            'required_now' => $requiredNow,
            'paid' => $paid,
            'due' => $due,
            'remaining' => $due,
            'due_date' => $booking->payment_due_date ?? $this->dueDateFor($booking, $rules),
            'overpayment' => max(0.0, round($paid - $total, 2)),
            'overpayment_policy' => $overpaymentPolicy,
            'overpayment_policy_label' => self::OVERPAYMENT_POLICY_LABELS[$overpaymentPolicy],
            'status' => $booking->payment_status,
            'status_label' => $booking->payment_status_label,
            'status_color' => $booking->payment_status_color,
            'mode' => $rules['payment_mode'],
            'timing' => $rules['remaining_due_timing'],
            'configured' => $rules['configured'],
            'mode_label' => self::MODE_LABELS[$rules['payment_mode']] ?? $rules['payment_mode'],
            'timing_label' => self::TIMING_LABELS[$rules['remaining_due_timing']] ?? $rules['remaining_due_timing'],
        ];
    }

    protected function normaliseRules(array $raw, bool $configured): array
    {
        return [
            'payment_mode' => in_array($raw['payment_mode'] ?? null, self::MODES, true)
                ? $raw['payment_mode']
                : 'percentage_advance',
            'advance_percentage' => max(0, min(100, (float) ($raw['advance_percentage'] ?? 30))),
            'advance_fixed_amount' => max(0, (float) ($raw['advance_fixed_amount'] ?? 0)),
            'remaining_due_timing' => in_array($raw['remaining_due_timing'] ?? null, self::TIMINGS, true)
                ? $raw['remaining_due_timing']
                : 'before_service',
            'custom_deadline_days' => max(0, (int) ($raw['custom_deadline_days'] ?? 7)),
            'configured' => $configured,
        ];
    }
}
