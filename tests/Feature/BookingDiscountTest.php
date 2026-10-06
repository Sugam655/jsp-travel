<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Services\PriceCalculator;
use Modules\Transport\Models\TransportVehicle;

/*
 * The booking discount.
 *
 * The discount is configured once in Booking Settings and then applied by
 * PriceCalculator to every new quote, so what matters is the order of the
 * arithmetic (discount, then tax and service charge on what is left), that the
 * booking keeps a snapshot of how the discount was configured, and that turning
 * the discount off leaves pricing exactly as it was.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Bookings', 'Tours', 'Hotels', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
    Artisan::call('module:seed', ['module' => 'Tours', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Hotels', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Transport', '--force' => true]);
});

/**
 * A complete, valid booking settings form payload, discount included.
 *
 * @param  array<string, mixed>  $discount
 * @return array<string, mixed>
 */
function discountSettingsPayload(array $discount = []): array
{
    return array_merge([
        'currency' => 'NPR',
        'tax_rate' => 13,
        'service_charge_rate' => 0,
        'discount_enabled' => '1',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'discount_label' => null,
        'booking_approval_required' => '1',
        'payment_deadline_hours' => 48,
        'max_booking_horizon' => 365,
        'max_hotel_stay' => 30,
        'max_rental_days' => 30,
        'rental_price_unit_limit_days' => 14,
        'cancellation_policy' => [
            ['days' => 30, 'refund' => 100],
            ['days' => 0, 'refund' => 0],
        ],
        'payment_methods' => ['cash'],
        'payment_rules' => [
            'vehicle' => [
                'payment_mode' => 'percentage_advance',
                'advance_percentage' => 30,
                'advance_fixed_amount' => 0,
                'remaining_due_timing' => 'before_service',
                'custom_deadline_days' => 7,
            ],
        ],
    ], $discount);
}

/**
 * Turn the discount on in the settings, through the real admin form.
 *
 * @param  array<string, mixed>  $discount
 */
function enableDiscount(User $admin, array $discount = []): void
{
    test()->actingAs($admin)
        ->put(route('admin.bookings.settings.update'), discountSettingsPayload($discount))
        ->assertRedirect(route('admin.bookings.settings.index'));
}

/**
 * A per-day vehicle priced so the arithmetic in these tests stays readable.
 */
function discountVehicle(): TransportVehicle
{
    $vehicle = TransportVehicle::query()->first();
    $vehicle->forceFill(['price' => 5000, 'price_unit' => 'per_day'])->save();

    return $vehicle->fresh();
}

/**
 * The quote a customer would see for a two day rental.
 *
 * @return array<string, mixed>
 */
function discountQuote(TransportVehicle $vehicle): array
{
    return (new PriceCalculator)->quote('vehicle', $vehicle, '2026-11-10', '2026-11-12', 1);
}

test('discounts are off by default and leave pricing untouched', function () {
    $quote = discountQuote(discountVehicle());

    expect(BookingSetting::getWithDefault('discount_enabled'))->toBeFalse()
        ->and($quote['discount'])->toBe('0.00')
        ->and($quote['discount_applied'])->toBeFalse()
        ->and($quote['discount_description'])->toBeNull()
        // A disabled discount leaves the subtotal untouched, so tax is still
        // charged on the full subtotal and the total is unchanged.
        ->and($quote['discounted_subtotal'])->toBe('10000.00')
        ->and($quote['tax_amount'])->toBe('1300.00')
        ->and($quote['total'])->toBe('11300.00');
});

test('a percentage discount is taken off before tax and service charge', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    enableDiscount($admin, ['discount_type' => 'percentage', 'discount_value' => 10]);

    $quote = discountQuote(discountVehicle());

    expect($quote['subtotal'])->toBe('10000.00')
        ->and($quote['discount'])->toBe('1000.00')
        ->and($quote['discount_applied'])->toBeTrue()
        ->and($quote['discount_description'])->toBe('10%')
        ->and($quote['discounted_subtotal'])->toBe('9000.00')
        // Tax is charged on what is left, not on the original subtotal.
        ->and($quote['tax_amount'])->toBe('1170.00')
        ->and($quote['total'])->toBe('10170.00');
});

test('a fixed discount reduces the subtotal by that exact amount', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    enableDiscount($admin, [
        'discount_type' => 'fixed',
        'discount_value' => 1500,
        'discount_label' => 'Dashain Offer',
    ]);

    $quote = discountQuote(discountVehicle());

    expect($quote['discount'])->toBe('1500.00')
        ->and($quote['discount_description'])->toBe('Dashain Offer (NPR 1,500.00)')
        ->and($quote['discounted_subtotal'])->toBe('8500.00')
        ->and($quote['tax_amount'])->toBe('1105.00')
        ->and($quote['total'])->toBe('9605.00');
});

test('a discount is never worth more than the subtotal it discounts', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = discountVehicle();

    enableDiscount($admin, ['discount_type' => 'fixed', 'discount_value' => 5000]);
    expect(discountQuote($vehicle)['total'])->toBe('5650.00');

    // A generous fixed amount settles the booking at zero rather than a
    // negative total.
    enableDiscount($admin, ['discount_type' => 'fixed', 'discount_value' => 1000000]);
    $quote = discountQuote($vehicle);

    expect($quote['discount'])->toBe('10000.00')
        ->and($quote['discounted_subtotal'])->toBe('0.00')
        ->and($quote['tax_amount'])->toBe('0.00')
        ->and($quote['service_charge'])->toBe('0.00')
        ->and($quote['total'])->toBe('0.00');
});

test('service charge is charged on the discounted subtotal as well', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->put(route('admin.bookings.settings.update'), discountSettingsPayload([
        'service_charge_rate' => 5,
        'discount_type' => 'percentage',
        'discount_value' => 20,
    ]))->assertRedirect();

    $quote = discountQuote(discountVehicle());

    expect($quote['discount'])->toBe('2000.00')
        ->and($quote['discounted_subtotal'])->toBe('8000.00')
        ->and($quote['tax_amount'])->toBe('1040.00')
        ->and($quote['service_charge'])->toBe('400.00')
        ->and($quote['total'])->toBe('9440.00');
});

test('a percentage above one hundred is rejected', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->from(route('admin.bookings.settings.index'))
        ->put(route('admin.bookings.settings.update'), discountSettingsPayload([
            'discount_type' => 'percentage',
            'discount_value' => 150,
        ]))
        ->assertRedirect(route('admin.bookings.settings.index'))
        ->assertSessionHasErrors('discount_value');

    // A negative discount is rejected too.
    $this->actingAs($admin)
        ->from(route('admin.bookings.settings.index'))
        ->put(route('admin.bookings.settings.update'), discountSettingsPayload([
            'discount_type' => 'fixed',
            'discount_value' => -50,
        ]))
        ->assertSessionHasErrors('discount_value');
});

test('the discount settings are stored and shown back on the form', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    enableDiscount($admin, [
        'discount_type' => 'percentage',
        'discount_value' => 15,
        'discount_label' => 'Dashain Offer',
    ]);

    expect(BookingSetting::getWithDefault('discount_enabled'))->toBeTrue()
        ->and(BookingSetting::getWithDefault('discount_type'))->toBe('percentage')
        ->and((float) BookingSetting::getWithDefault('discount_value'))->toBe(15.0)
        ->and(BookingSetting::getWithDefault('discount_label'))->toBe('Dashain Offer');

    $this->actingAs($admin)->get(route('admin.bookings.settings.index'))
        ->assertOk()
        ->assertSee('Discount Label', false)
        ->assertSee('Dashain Offer')
        ->assertSee('value="15"', false);
});

test('a booking stores a snapshot of the discount it was quoted with', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $vehicle = discountVehicle();

    enableDiscount($admin, [
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'discount_label' => 'Dashain Offer',
    ]);

    $this->actingAs($user)->post('/bookings', [
        'booking_type' => 'vehicle',
        'service_id' => $vehicle->id,
        'name' => 'Test Traveller',
        'email' => 'traveller@example.com',
        'phone' => '9800000000',
        'address' => 'Kathmandu',
        'travelers' => 1,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-12',
        'policy_accepted' => '1',
    ])->assertRedirect();

    $booking = Booking::firstOrFail();

    expect((float) $booking->discount)->toBe(1000.0)
        ->and($booking->discount_type)->toBe('percentage')
        ->and((float) $booking->discount_value)->toBe(10.0)
        ->and($booking->discount_label)->toBe('Dashain Offer')
        ->and((float) $booking->base_price)->toBe(10000.0)
        ->and((float) $booking->tax_amount)->toBe(1170.0)
        ->and((float) $booking->total_amount)->toBe(10170.0);
});

test('changing the global discount does not reprice an existing booking', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $vehicle = discountVehicle();

    enableDiscount($admin, ['discount_type' => 'percentage', 'discount_value' => 10, 'discount_label' => 'Dashain Offer']);

    $this->actingAs($user)->post('/bookings', [
        'booking_type' => 'vehicle',
        'service_id' => $vehicle->id,
        'name' => 'Test Traveller',
        'email' => 'traveller@example.com',
        'phone' => '9800000000',
        'address' => 'Kathmandu',
        'travelers' => 1,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-12',
        'policy_accepted' => '1',
    ])->assertRedirect();

    $reference = Booking::firstOrFail()->booking_reference;

    // The offer ends, and a different one starts.
    enableDiscount($admin, ['discount_type' => 'fixed', 'discount_value' => 2000, 'discount_label' => 'New Year Offer']);

    $booking = Booking::firstOrFail();

    expect((float) $booking->discount)->toBe(1000.0)
        ->and($booking->discount_type)->toBe('percentage')
        ->and((float) $booking->total_amount)->toBe(10170.0)
        // The stored booking still explains itself with its own snapshot.
        ->and($booking->discount_display)->toBe('Discount (Dashain Offer (10%))');

    $this->actingAs($user)->get(route('bookings.show', $reference))
        ->assertOk()
        ->assertSee('Discount (Dashain Offer (10%))')
        ->assertSee('1,000.00')
        ->assertDontSee('New Year Offer');
});

test('a booking quoted with no discount shows no discount row at all', function () {
    $user = User::factory()->create();
    $vehicle = discountVehicle();

    $this->actingAs($user)->post('/bookings', [
        'booking_type' => 'vehicle',
        'service_id' => $vehicle->id,
        'name' => 'Test Traveller',
        'email' => 'traveller@example.com',
        'phone' => '9800000000',
        'address' => 'Kathmandu',
        'travelers' => 1,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-12',
        'policy_accepted' => '1',
    ])->assertRedirect();

    $booking = Booking::firstOrFail();

    expect($booking->has_discount)->toBeFalse()
        ->and($booking->discount_display)->toBe('Discount')
        ->and($booking->discount_type)->toBeNull();

    $this->actingAs($user)->get(route('bookings.show', $booking->booking_reference))
        ->assertOk()
        ->assertDontSee('Discount (');
});

test('the advance and remaining balance are based on the discounted total', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $vehicle = discountVehicle();

    enableDiscount($admin, ['discount_type' => 'percentage', 'discount_value' => 50]);

    $this->actingAs($user)->post('/bookings', [
        'booking_type' => 'vehicle',
        'service_id' => $vehicle->id,
        'name' => 'Test Traveller',
        'email' => 'traveller@example.com',
        'phone' => '9800000000',
        'address' => 'Kathmandu',
        'travelers' => 1,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-12',
        'policy_accepted' => '1',
    ])->assertRedirect();

    $booking = Booking::firstOrFail();

    // 10,000 subtotal less 50% is 5,000, plus 13% tax on the 5,000.
    expect((float) $booking->total_amount)->toBe(5650.0);

    $this->actingAs($admin)->post('/admin/bookings/'.$booking->id.'/confirm')->assertRedirect();

    $booking->refresh();

    // The 30% vehicle advance is a share of the discounted total, not of the
    // full price the customer would otherwise have paid.
    expect((float) $booking->advance_amount)->toBe(1695.0)
        ->and((float) $booking->dueAmount())->toBe(5650.0);
});

/**
 * The server summaries the search popup is filled in from.
 *
 * @return array<string, array<string, mixed>>
 */
function discountPopupSummaries(string $html): array
{
    preg_match('/<script type="application\/json" id="fh-bookingSummaries">(.*?)<\/script>/s', $html, $matches);

    return json_decode(html_entity_decode($matches[1] ?? '', ENT_QUOTES), true) ?: [];
}

/**
 * The vehicle summary a search result carries.
 *
 * @return array<string, mixed>
 */
function discountVehicleSummary(string $html): array
{
    return collect(discountPopupSummaries($html))->firstWhere('bookingType', 'vehicle');
}

test('a settings submission without discount fields keeps the configured discount', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    enableDiscount($admin, ['discount_type' => 'percentage', 'discount_value' => 25, 'discount_label' => 'Dashain Offer']);

    // Saving an unrelated group of settings must not quietly drop the offer.
    $this->actingAs($admin)
        ->put(route('admin.bookings.settings.update'), Arr::except(discountSettingsPayload(), [
            'discount_enabled',
            'discount_type',
            'discount_value',
            'discount_label',
        ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(BookingSetting::getWithDefault('discount_enabled'))->toBeFalse()
        ->and(BookingSetting::getWithDefault('discount_type'))->toBe('percentage')
        ->and((float) BookingSetting::getWithDefault('discount_value'))->toBe(25.0)
        ->and(BookingSetting::getWithDefault('discount_label'))->toBe('Dashain Offer');
});

test('the confirmation popup tells the customer which discount was applied', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    enableDiscount($admin, ['discount_type' => 'percentage', 'discount_value' => 25, 'discount_label' => 'Dashain Offer']);

    $html = $this->get(route('booking.search', [
        'type' => 'vehicle',
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-13',
        'travelers' => 2,
    ]))
        ->assertOk()
        ->getContent();

    $summary = discountVehicleSummary($html);

    expect($summary)->not->toBeEmpty()
        ->and($summary['hasDiscount'])->toBeTrue()
        ->and($summary['discountLabel'])->toBe('Discount (Dashain Offer (25%))')
        ->and($summary['discount'])->not->toBe('');
});

test('the confirmation popup hides the discount row when none applies', function () {
    $html = $this->get(route('booking.search', [
        'type' => 'vehicle',
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-13',
        'travelers' => 2,
    ]))
        ->assertOk()
        ->getContent();

    $summary = discountVehicleSummary($html);

    expect($summary)->not->toBeEmpty()
        ->and($summary['hasDiscount'])->toBeFalse()
        ->and($summary['discountLabel'])->toBe('Discount')
        // The popup row ships hidden and is only revealed by the summary flag.
        ->and($html)->toContain('class="fh-quote-row d-none" data-summary-discount-row');
});
