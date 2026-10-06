<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Services\PriceCalculator;
use Modules\Hotels\Models\Hotel;
use Modules\Transport\Models\TransportVehicle;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Bookings', 'Tours', 'Hotels', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
    Artisan::call('module:seed', ['module' => 'Transport', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Hotels', '--force' => true]);
});

function carBookingPayload($service, string $start, ?string $end, int $travelers, array $extra = []): array
{
    return array_merge([
        'booking_type' => 'vehicle',
        'service_id' => $service->id,
        'name' => 'Test Traveller',
        'email' => 'traveller@example.com',
        'phone' => '9800000000',
        'address' => 'Kathmandu',
        'travelers' => $travelers,
        'start_date' => $start,
        'end_date' => $end,
        'message' => null,
        'policy_accepted' => '1',
    ], $extra);
}

test('the car rental detail page Book Now button pre-selects the vehicle on the booking form', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->get('/transport/'.$vehicle->slug)
        ->assertOk()
        ->assertSee('type=vehicle&amp;slug='.$vehicle->slug, false);

    $this->actingAs($user)
        ->get(route('bookings.create', ['type' => 'vehicle', 'slug' => $vehicle->slug]))
        ->assertOk()
        ->assertSee($vehicle->name)
        ->assertSee('value="'.$vehicle->id.'"', false);
});

test('a guest who clicks Rent Now on a car rental reaches the pre-filled form directly', function () {
    $vehicle = TransportVehicle::query()->first();
    $url = route('bookings.create', ['type' => 'vehicle', 'slug' => $vehicle->slug]);

    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    expect($query)->toMatchArray(['type' => 'vehicle', 'slug' => $vehicle->slug]);

    $this->get($url)
        ->assertOk()
        ->assertSee($vehicle->name)
        ->assertSee('value="'.$vehicle->id.'"', false);
});

test('a customer with no phone or address in their profile can book a car rental directly', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->get(route('bookings.create', ['type' => 'vehicle', 'slug' => $vehicle->slug]))
        ->assertOk()
        ->assertSee($vehicle->name);

    $response = $this->actingAs($user)
        ->post('/bookings', carBookingPayload(
            $vehicle,
            '2026-10-10',
            '2026-10-13',
            4,
            ['name' => 'Test Traveller', 'email' => 'traveller@example.com']
        ));

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    expect($booking)
        ->user_id->toBe($user->id)
        ->service_id->toBe($vehicle->id)
        ->service_title->toBe($vehicle->name)
        ->name->toBe('Test Traveller')
        ->email->toBe('traveller@example.com')
        ->phone->toBe('9800000000')
        ->address->toBe('Kathmandu')
        ->travelers->toBe(4)
        ->status->toBe('pending')
        ->start_date->format('Y-m-d')->toBe('2026-10-10')
        ->end_date->format('Y-m-d')->toBe('2026-10-13');

    $quote = (new PriceCalculator)->quote('vehicle', $vehicle, '2026-10-10', '2026-10-13', 4);

    expect((float) $booking->total_amount)->toBe((float) $quote['total'])
        ->and((int) $booking->quantity)->toBe(3);
});

test('a customer with no completed profile is not blocked from reaching a hotel booking form', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->first();

    $this->actingAs($user)
        ->get(route('bookings.create', ['type' => 'hotel', 'slug' => $hotel->slug]))
        ->assertOk()
        ->assertSee($hotel->title);

    $this->actingAs($user)
        ->post('/bookings', [
            'booking_type' => 'hotel',
            'service_id' => $hotel->id,
            'name' => 'Test Traveller',
            'email' => 'traveller@example.com',
            'phone' => '9800000000',
            'address' => 'Kathmandu',
            'travelers' => 2,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'message' => null,
            'policy_accepted' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $booking = Booking::query()->where('booking_type', 'hotel')->firstOrFail();

    expect($booking)
        ->user_id->toBe($user->id)
        ->status->toBe('pending')
        ->email->toBe('traveller@example.com');
});

test('an adjacent car rental can be booked the day the previous rental ends', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();
    $start = now()->addDays(10)->toDateString();
    $end = now()->addDays(13)->toDateString();
    $nextStart = now()->addDays(13)->toDateString();
    $nextEnd = now()->addDays(16)->toDateString();

    $this->actingAs($user)
        ->post('/bookings', carBookingPayload($vehicle, $start, $end, 4))
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/bookings', carBookingPayload($vehicle, $nextStart, $nextEnd, 4))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Booking::query()->where('booking_type', 'vehicle')->count())->toBe(2);
});

test('an overlapping car rental request shows a clear error and is not created', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();
    $start = now()->addDays(10)->toDateString();
    $end = now()->addDays(13)->toDateString();
    $overlapStart = now()->addDays(11)->toDateString();
    $overlapEnd = now()->addDays(14)->toDateString();

    $this->actingAs($user)
        ->post('/bookings', carBookingPayload($vehicle, $start, $end, 4))
        ->assertRedirect();

    $response = $this->actingAs($user)
        ->post('/bookings', carBookingPayload($vehicle, $overlapStart, $overlapEnd, 4))
        ->assertRedirect()
        ->assertSessionHasErrors('booking');

    expect(collect(session('errors')->get('booking'))->first())->toContain('not available');

    expect(Booking::query()->where('booking_type', 'vehicle')->count())->toBe(1);
});

test('a per day vehicle is priced by the number of rental days', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('price_unit', 'per_day')->firstOrFail();

    $quote = (new PriceCalculator)->quote('vehicle', $vehicle, '2026-10-10', '2026-10-13', 2);

    expect($quote['quantity'])->toBe(3)
        ->and($quote['duration_label'])->toBe('3 day(s)')
        ->and((float) $quote['subtotal'])->toBe(round((float) $vehicle->price * 3, 2));
});

test('a per trip vehicle is quoted as a flat rate regardless of duration', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('price_unit', 'per_trip')->firstOrFail();

    $quote = (new PriceCalculator)->quote('vehicle', $vehicle, '2026-10-10', '2026-10-13', 2);

    expect($quote['quantity'])->toBe(1)
        ->and($quote['duration_label'])->toBe('Per trip')
        ->and((float) $quote['subtotal'])->toBe(round((float) $vehicle->price, 2));
});

test('a per hour vehicle is left at zero for an admin to quote instead of billing days', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('price_unit', 'per_day')->firstOrFail();
    $vehicle->update(['price_unit' => 'per_hour']);

    $quote = (new PriceCalculator)->quote('vehicle', $vehicle, '2026-10-10', '2026-10-13', 2);

    expect($quote['quantity'])->toBe(0)
        ->and($quote['duration_label'])->toBe('To be quoted')
        ->and((float) $quote['subtotal'])->toBe(0.0)
        ->and((float) $quote['total'])->toBe(0.0)
        ->and($quote['recalc_note'])->toBe('Quoted by our team');
});

test('a contact us vehicle is left at zero for an admin to quote', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('price_unit', 'per_day')->firstOrFail();
    $vehicle->update(['price_unit' => 'contact']);

    $quote = (new PriceCalculator)->quote('vehicle', $vehicle, '2026-10-10', '2026-10-13', 2);

    expect($quote['quantity'])->toBe(0)
        ->and((float) $quote['total'])->toBe(0.0);
});

test('a per hour vehicle booking reaches an admin with no fabricated total so set price applies', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('price_unit', 'per_day')->firstOrFail();
    $vehicle->update(['price_unit' => 'per_hour']);

    $this->actingAs($user)
        ->post('/bookings', carBookingPayload($vehicle, '2026-10-10', '2026-10-13', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    expect((float) $booking->total_amount)->toBe(0.0);

    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/set-price', ['base_price' => 15000])
        ->assertRedirect();

    expect((float) $booking->fresh()->total_amount)->toBeGreaterThan(0.0);
});

test('an admin cancellation of a car rental is visible to the customer and notifies them', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', carBookingPayload($vehicle, '2026-10-10', '2026-10-12', 4))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/cancel', ['reason' => 'Fleet unavailability'])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('cancelled');

    $this->actingAs($user)
        ->get('/bookings/'.$booking->booking_reference)
        ->assertOk()
        ->assertSee('Cancelled');

    $user->refresh();
    expect($user->notifications()->count())->toBe(2)
        ->and($user->notifications()->get()->pluck('data.title'))->toContain('Booking cancelled');
});
