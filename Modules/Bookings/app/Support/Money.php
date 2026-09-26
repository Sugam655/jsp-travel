<?php

namespace Modules\Bookings\Support;

/**
 * Decimal-safe money arithmetic built on bcmath (falls back to float when the
 * extension is unavailable). All money amounts are stored as decimal columns,
 * never as floating point, matching the database schema.
 */
class Money
{
    /**
     * The arithmetic scale (2 decimal places for rupees/paisa).
     */
    private const SCALE = 2;

    public static function add(string|int|float $a, string|int|float $b): string
    {
        if (function_exists('bcadd')) {
            return bcadd(self::normalise($a), self::normalise($b), self::SCALE);
        }

        return number_format((float) $a + (float) $b, self::SCALE, '.', '');
    }

    public static function sub(string|int|float $a, string|int|float $b): string
    {
        if (function_exists('bcsub')) {
            return bcsub(self::normalise($a), self::normalise($b), self::SCALE);
        }

        return number_format((float) $a - (float) $b, self::SCALE, '.', '');
    }

    public static function mul(string|int|float $a, string|int|float $b): string
    {
        if (function_exists('bcmul')) {
            return bcmul(self::normalise($a), self::normalise($b), self::SCALE);
        }

        return number_format((float) $a * (float) $b, self::SCALE, '.', '');
    }

    public static function div(string|int|float $a, string|int|float $b, int $scale = self::SCALE): string
    {
        if ((float) $b == 0) {
            return '0.00';
        }

        if (function_exists('bcdiv')) {
            return bcdiv(self::normalise($a), self::normalise($b), $scale);
        }

        return number_format((float) $a / (float) $b, $scale, '.', '');
    }

    /**
     * Percentage of an amount, rounded to 2 decimals.
     */
    public static function percent(string|int|float $amount, string|int|float $rate): string
    {
        return self::round(self::mul($amount, self::div($rate, '100')));
    }

    /**
     * Round a decimal string to the money scale.
     */
    public static function round(string|int|float $value): string
    {
        if (function_exists('bcscale')) {
            return bcadd(self::normalise($value), '0', self::SCALE);
        }

        return number_format((float) $value, self::SCALE, '.', '');
    }

    /**
     * A signed number string (used by the change request price diff display).
     */
    public static function signed(string|int|float $value): string
    {
        $value = self::round($value);

        return (str_starts_with($value, '-') ? '' : '+').$value;
    }

    /**
     * Normalise a numeric input into a plain decimal string.
     */
    protected static function normalise(string|int|float $value): string
    {
        return number_format((float) $value, self::SCALE, '.', '');
    }
}
