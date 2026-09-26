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
        $taxRate = (float) BookingSetting::getWithDefault('tax_rate');
        $serviceChargeRate = (float) BookingSetting::getWithDefault('service_charge_rate');
        $taxAmount = Money::percent($subtotal, $taxRate);
        $serviceCharge = Money::percent($subtotal, $serviceChargeRate);
        $discount = '0.00';
        $total = Money::add(Money::add($subtotal, $taxAmount), $serviceCharge);

        return [
            'currency' => $currency,
            'unit_price' => Money::round($unitPrice),
            'unit_label' => $this->unitLabel($bookingType, $service),
            'quantity' => $quantity,
            'duration_label' => $durationLabel,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'service_charge_rate' => $serviceChargeRate,
            'tax_amount' => $taxAmount,
            'service_charge' => $serviceCharge,
            'discount' => $discount,
            'total' => $total,
            'recalc_note' => $this->recalcNote($bookingType, $service, $quantity),
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
                'per_hour' => [max(1, $days), $days.' day(s)'],
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
        return match ($bookingType) {
            'hotel' => $quantity.' night(s) × room rate',
            'vehicle' => isset($service->price_unit) && $service->price_unit === 'per_trip'
                ? 'Flat trip rate'
                : $quantity.' day(s) × rental rate',
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
            'subtotal' => '0.00',
            'tax_rate' => (float) BookingSetting::getWithDefault('tax_rate'),
            'service_charge_rate' => (float) BookingSetting::getWithDefault('service_charge_rate'),
            'tax_amount' => '0.00',
            'service_charge' => '0.00',
            'discount' => '0.00',
            'total' => '0.00',
            'recalc_note' => '',
        ];
    }
}
