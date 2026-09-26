<?php

namespace Modules\Bookings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BookingSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['key', 'value', 'group'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'string',
    ];

    /**
     * The defaults used before any settings are stored.
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'currency' => 'NPR',
        'tax_rate' => 13,
        'service_charge_rate' => 0,
        'booking_approval_required' => true,
        'payment_deadline_hours' => 48,
        'max_booking_horizon' => 365,
        'max_hotel_stay' => 30,
        'max_rental_days' => 30,
        'rental_price_unit_limit_days' => 14,
        'cancellation_policy' => [
            ['days' => 30, 'refund' => 100],
            ['days' => 15, 'refund' => 75],
            ['days' => 7, 'refund' => 50],
            ['days' => 2, 'refund' => 25],
            ['days' => 0, 'refund' => 0],
        ],
        'payment_methods' => [
            ['method' => 'bank_transfer', 'label' => 'Bank Transfer', 'details' => 'Bank: NMB Bank, A/C: Jay Shiv Parvati Travel & Tour, A/C No: 1234567890123, Branch: Dhangadhi'],
            ['method' => 'esewa', 'label' => 'eSewa', 'details' => 'Complete payment in eSewa, then submit the transaction ID for verification.'],
            ['method' => 'khalti', 'label' => 'Khalti', 'details' => 'Complete payment in Khalti, then submit the transaction ID for verification.'],
            ['method' => 'fonepay', 'label' => 'Fonepay', 'details' => 'Complete payment in Fonepay, then submit the transaction ID for verification.'],
            ['method' => 'cash', 'label' => 'Cash at Office', 'details' => 'Pay at our office, then report the payment for staff verification.'],
        ],
        'payment_overpayment_policy' => 'reject',
        'payment_rules' => [
            'tour' => [
                'payment_mode' => 'percentage_advance',
                'advance_percentage' => 20,
                'advance_fixed_amount' => 0,
                'remaining_due_timing' => 'before_service',
                'custom_deadline_days' => 7,
            ],
            'hotel' => [
                'payment_mode' => 'percentage_advance',
                'advance_percentage' => 30,
                'advance_fixed_amount' => 0,
                'remaining_due_timing' => 'before_service',
                'custom_deadline_days' => 7,
            ],
            'vehicle' => [
                'payment_mode' => 'percentage_advance',
                'advance_percentage' => 30,
                'advance_fixed_amount' => 0,
                'remaining_due_timing' => 'on_service_start',
                'custom_deadline_days' => 7,
            ],
        ],
    ];

    /**
     * The cache key used for the settings lookup.
     */
    private const CACHE_KEY = 'booking_settings';

    /**
     * Read a booking setting, falling back to the shipped default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $values = Cache::rememberForever(self::CACHE_KEY, function () {
            return self::query()->pluck('value', 'key')->all();
        });

        if (array_key_exists($key, $values)) {
            return self::decode($values[$key]);
        }

        return self::DEFAULTS[$key] ?? $default;
    }

    /**
     * Read a booking setting, returning the shipped default when unset.
     */
    public static function getWithDefault(string $key): mixed
    {
        return self::get($key, self::DEFAULTS[$key] ?? null);
    }

    /**
     * Store a scalar or JSON value for a booking setting.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => self::encode($value), 'group' => $group]
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Flush the cached settings (after a seeding or bulk update).
     */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Type-aware decoding of a stored setting value.
     */
    protected static function decode(string $value): mixed
    {
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $lower = strtolower($value);

            return match ($lower) {
                'true' => true,
                'false' => false,
                'null' => null,
                default => is_numeric($value) ? (float) $value : $value,
            };
        }

        return $decoded;
    }

    /**
     * Type-aware encoding of a setting value for storage.
     */
    protected static function encode(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
