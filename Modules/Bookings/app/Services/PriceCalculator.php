<?php

namespace Modules\Bookings\Services;

use Carbon\CarbonImmutable;
use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Support\Money;

/**
 * Rebuilds the booking price from the actual service data on the server.
 *
 * Customers can never submit their own totals: subtotal, taxes, service
 * charge and the final total are always recalculated here from the service
 * price and the selected dates/passengers.
 */
class PriceCalculator
{
    /**
     * A validated quote for a booking request.
     *
     * @return array<string, mixed>
     */
    public function quote(
        string $bookingType,
        mixed $service,
        ?string $startDate,
        ?string $endDate,
        ?int $travelers
    ): array {
        $currency = (string) BookingSetting::getWithDefault('currency');

        if ($service === null) {
            return $this->emptyQuote($currency);
        }

        $start = $startDate ? CarbonImmutable::parse($startDate)->startOfDay() : null;
        $end = $endDate ? CarbonImmutable::parse($endDate)->startOfDay() : null;

        $rules = new BookingRulesService;
        $unitPrice = (float) ($service->price ?? 0);

        [$quantity, $durationLabel] = $this->quantityFor(
            $bookingType,
            $service,
            $start,
            $end,
            $travelers
        );

        $subtotal = Money::mul($unitPrice, $quantity);

        return [
            'currency' => $currency,
            'unit_price' => Money::round($unitPrice),
            'unit_label' => $this->unitLabel($bookingType, $service),
            'quantity' => $quantity,
            'duration_label' => $durationLabel,
            ...$this->priceSubtotal($subtotal, $currency),
            'recalc_note' => $this->recalcNote($bookingType, $service, $quantity),
        ];
    }

    /**
     * Turn a subtotal into the final payable total.
     *
     * The order is fixed for every price the customer ever sees: discount
     * first, then tax and service charge on what is left, so the total is
     * always the arithmetic sum of the rows shown next to it.
     *
     * @return array<string, mixed>
     */
    public function priceSubtotal(string|int|float $subtotal, ?string $currency = null): array
    {
        $currency ??= (string) BookingSetting::getWithDefault('currency');
        $subtotal = Money::round($subtotal);
        $discount = DiscountResolver::settings();
        $discounted = Money::compare($subtotal, '0') <= 0 ? '0.00' : $subtotal;
        $applied = DiscountResolver::amountFor($discounted, $discount);

        $discountedSubtotal = Money::sub($discounted, $applied);
        $taxRate = (float) BookingSetting::getWithDefault('tax_rate');
        $serviceChargeRate = (float) BookingSetting::getWithDefault('service_charge_rate');
        $taxAmount = Money::percent($discountedSubtotal, $taxRate);
        $serviceCharge = Money::percent($discountedSubtotal, $serviceChargeRate);
        $total = Money::add(Money::add($discountedSubtotal, $taxAmount), $serviceCharge);
        $wasApplied = Money::compare($applied, '0') > 0;

        return [
            'subtotal' => $discounted,
            'discount' => $wasApplied ? $applied : '0.00',
            'discount_type' => $wasApplied ? $discount['type'] : null,
            'discount_value' => $wasApplied ? $discount['value'] : null,
            'discount_label' => $wasApplied ? $discount['label'] : null,
            'discount_applied' => $wasApplied,
            'discount_description' => $wasApplied
                ? DiscountResolver::describe($discount['type'], $discount['value'], $discount['label'], $currency)
                : null,
            'discounted_subtotal' => $discountedSubtotal,
            'tax_rate' => $taxRate,
            'service_charge_rate' => $serviceChargeRate,
            'tax_amount' => $taxAmount,
            'service_charge' => $serviceCharge,
            'total' => $total,
        ];
    }

    /**
     * The quantity unit applied for the given service type.
     */
    protected function quantityFor(
        string $bookingType,
        mixed $service,
        ?CarbonImmutable $start,
        ?CarbonImmutable $end,
        ?int $travelers
    ): array {
        if ($bookingType === 'hotel' && $start && $end) {
            $nights = (new BookingRulesService)->nights($start, $end);
            $label = $nights.' night'.($nights > 1 ? 's' : '');

            return [$nights, $label];
        }

        if ($bookingType === 'vehicle' && $start && $end) {
            $days = (new BookingRulesService)->days($start, $end);

            return match ($service->price_unit ?? 'per_day') {
                'per_trip' => [1, 'Per trip'],
                // Hourly and "contact us" vehicles have no quantity the booking
                // form can express, so they are quoted at zero and left for an
                // admin to set via the set-price action. Billing them as days
                // would fabricate a total nobody agreed to.
                'per_hour', 'contact' => [0, 'To be quoted'],
                default => [$days, $days.' day(s)'],
            };
        }

        $pax = max(1, (int) ($travelers ?? 1));

        return [$pax, $pax.' traveller(s)'];
    }

    /**
     * The human label for the unit price.
     */
    protected function unitLabel(string $bookingType, mixed $service): string
    {
        return match ($bookingType) {
            'hotel' => 'per night',
            'vehicle' => isset($service->price_unit) ? (string) str_replace('_', ' ', $service->price_unit) : 'per day',
            default => 'per person',
        };
    }

    /**
     * A short explanation of how the subtotal was derived.
     */
    protected function recalcNote(string $bookingType, mixed $service, int $quantity): string
    {
        return match (true) {
            $bookingType === 'hotel' => $quantity.' night(s) × room rate',
            $bookingType === 'vehicle' && in_array($service->price_unit ?? 'per_day', ['per_hour', 'contact'], true) => 'Quoted by our team',
            $bookingType === 'vehicle' && ($service->price_unit ?? null) === 'per_trip' => 'Flat trip rate',
            $bookingType === 'vehicle' => $quantity.' day(s) × rental rate',
            default => $quantity.' traveller(s) × per-person rate',
        };
    }

    /**
     * An all-zero quote used as a safe fallback.
     *
     * @return array<string, mixed>
     */
    protected function emptyQuote(string $currency): array
    {
        return [
            'currency' => $currency,
            'unit_price' => '0.00',
            'unit_label' => '',
            'quantity' => 0,
            'duration_label' => '',
            ...$this->priceSubtotal('0.00'),
            'recalc_note' => '',
        ];
    }
}
