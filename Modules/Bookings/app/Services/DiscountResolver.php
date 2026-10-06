<?php

namespace Modules\Bookings\Services;

use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Support\Money;

/**
 * Resolves how much a booking is discounted and how that discount is named.
 *
 * PriceCalculator owns the order of operations in a quote; this class only
 * answers "how much is discounted, and what do we call it". The same
 * description is reused for historical bookings, which read the snapshot on
 * the booking row instead of the current settings.
 */
class DiscountResolver
{
    /**
     * The supported discount types.
     *
     * @var array<int, string>
     */
    public const TYPES = ['percentage', 'fixed'];

    /**
     * Human friendly labels for the discount types.
     *
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        'percentage' => 'Percentage (%)',
        'fixed' => 'Fixed Amount',
    ];

    /**
     * The live discount configuration from booking settings.
     *
     * @return array{enabled: bool, type: string, value: float, label: ?string}
     */
    public static function settings(): array
    {
        $type = (string) BookingSetting::getWithDefault('discount_type');

        return [
            'enabled' => (bool) BookingSetting::getWithDefault('discount_enabled'),
            'type' => in_array($type, self::TYPES, true) ? $type : 'percentage',
            'value' => max(0, (float) BookingSetting::getWithDefault('discount_value')),
            'label' => self::normaliseLabel(BookingSetting::getWithDefault('discount_label')),
        ];
    }

    /**
     * The discount amount for a subtotal, never more than the subtotal itself.
     *
     * @param  array{enabled: bool, type: string, value: float, label: ?string}  $settings
     */
    public static function amountFor(string $subtotal, array $settings): string
    {
        if (! $settings['enabled'] || Money::compare($subtotal, '0') <= 0) {
            return '0.00';
        }

        $amount = $settings['type'] === 'fixed'
            ? Money::round($settings['value'])
            : Money::percent($subtotal, $settings['value']);

        // A discount can never be worth more than what it discounts, so a
        // generous fixed amount settles the booking at zero rather than a
        // negative total.
        return Money::compare($amount, $subtotal) > 0 ? Money::round($subtotal) : $amount;
    }

    /**
     * A short human description of a discount.
     *
     * A custom label always leads, with the basis in brackets, so a booking
     * reads the same whether it was just quoted or stored months ago.
     */
    public static function describe(?string $type, float|string|null $value, ?string $label, string $currency = 'NPR'): string
    {
        $custom = self::normaliseLabel($label);
        $basis = match ($type) {
            'fixed' => $currency.' '.number_format((float) $value, 2),
            default => rtrim(rtrim(number_format((float) $value, 2), '0'), '.').'%',
        };

        return $custom === null ? $basis : $custom.' ('.$basis.')';
    }

    /**
     * A trimmed label, or null when none was provided.
     */
    public static function normaliseLabel(mixed $label): ?string
    {
        $label = is_string($label) ? trim($label) : '';

        return $label === '' ? null : $label;
    }
}
