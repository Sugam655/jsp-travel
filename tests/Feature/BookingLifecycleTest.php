<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Models\Booking;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Bookings', 'Tours', 'Hotels', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
    Artisan::call('module:seed', ['module' => 'Tours', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Hotels', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Transport', '--force' => true]);
});

function bookingPayload(string $type, $service, string $start, ?string $end, int $travelers, ?string $email = null, array $extra = []): array
{
    $payload = [
        'booking_type' => $type,
        'service_id' => $service->id,
        'name' => 'Test Traveller',
        'email' => $email ?? 'traveller@example.com',
        'phone' => '9800000000',
        'address' => 'Kathmandu',
        'travelers' => $travelers,
        'start_date' => $start,
        'end_date' => $end,
        'message' => null,
        'policy_accepted' => '1',
    ];

    return array_merge($payload, $extra);
}

test('guests are redirected to login before they can book', function () {
    $vehicle = TransportVehicle::query()->first();

    $this->get('/bookings/create')->assertRedirect('/login');
    $this->get('/bookings/my')->assertRedirect('/login');

    $this->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect('/login');

    expect(Booking::count())->toBe(0);
});

test('a vehicle is bookable for free dates if the customer is signed in', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    expect(Booking::count())->toBe(1);

    $booking = Booking::first();
    expect($booking->status)->toBe('pending')
        ->and($booking->booking_reference)->toStartWith('BK-2026-')
        ->and($booking->user_id)->toBe($user->id)
        ->and($booking->email)->toBe('traveller@example.com');
});

test('a vehicle cannot be double-booked for overlapping dates', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-11', '2026-10-13', 2))
        ->assertRedirect()
        ->assertSessionHasErrors('booking');

    expect(Booking::query()->where('booking_type', 'vehicle')->count())->toBe(1);
});

test('a vehicle is available again once the previous period ends (check-out day is free)', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-12', '2026-10-14', 2))
        ->assertRedirect();

    expect(Booking::query()->where('booking_type', 'vehicle')->count())->toBe(2);
});

test('a cancelled booking releases the vehicle', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->first();
    $reference = $booking->booking_reference;

    $this->actingAs($user)
        ->post('/bookings/'.$reference.'/cancel', ['reason' => 'Changed plans'])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('cancelled');

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();
});

test('a rejected booking releases the vehicle', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->first();

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/reject')
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('rejected');

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();
});

test('two submissions at almost the same time do not double-book', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();
    $payload = bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2);

    $this->actingAs($user)->post('/bookings', $payload)->assertRedirect();
    $this->actingAs($user)->post('/bookings', $payload)
        ->assertRedirect()
        ->assertSessionHasErrors('booking');

    expect(Booking::query()->where('booking_type', 'vehicle')->count())->toBe(1);
});

test('a booking survives a refresh and stays reachable by its reference while signed in', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $reference = Booking::query()->where('booking_type', 'vehicle')->first()->booking_reference;

    $this->actingAs($user)
        ->get('/bookings/'.$reference)
        ->assertOk()
        ->assertSee($reference);
});

test('a signed-in customer sees their bookings again under My Bookings after logging out and back in', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $reference = Booking::query()->where('booking_type', 'vehicle')->first()->booking_reference;

    $this->actingAs($user)->get('/bookings/my')->assertSee($reference);

    $this->flushSession();
    auth()->logout();

    $this->get('/bookings/my')->assertRedirect('/login');

    $this->actingAs($user)->get('/bookings/my')->assertSee($reference);
});

test('confirming a booking as admin is visible to the customer', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->first();

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/confirm')
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('confirmed');

    $this->actingAs($user)
        ->get('/bookings/'.$booking->booking_reference)
        ->assertOk()
        ->assertSee($booking->booking_reference)
        ->assertSee('Confirmed');
});

test('a booking is not accessible to another customer', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($owner)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $reference = Booking::query()->where('booking_type', 'vehicle')->first()->booking_reference;

    $this->flushSession();
    auth()->logout();

    $this->get('/bookings/'.$reference)->assertRedirect('/login');
    $this->actingAs($intruder)->get('/bookings/'.$reference)->assertStatus(403);
    $this->actingAs($owner)->get('/bookings/'.$reference)->assertOk();
});

test('a hotel room cannot be double-booked for overlapping dates', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('hotel', $hotel, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('hotel', $hotel, '2026-10-11', '2026-10-13', 2))
        ->assertRedirect()
        ->assertSessionHasErrors('booking');

    expect(Booking::query()->where('booking_type', 'hotel')->count())->toBe(1);
});

test('the hotel detail page Book Now link pre-selects the hotel on the booking form', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->first();

    $this->actingAs($user)
        ->get('/bookings/create?type=hotel&slug='.$hotel->slug)
        ->assertOk()
        ->assertSee($hotel->title)
        ->assertSee('value="'.$hotel->id.'"', false);
});

test('a guest who logs in from a specific hotel booking returns to the same pre-filled form', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);
    $hotel = Hotel::query()->first();
    $url = '/bookings/create?type=hotel&slug='.$hotel->slug;

    $this->get($url)->assertRedirect('/login');

    $login = $this->from($url)
        ->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect()
        ->assertRedirectContains('bookings/create');

    parse_str((string) parse_url($login->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($query)->toMatchArray(['type' => 'hotel', 'slug' => $hotel->slug]);

    $this->actingAs($user)->get($url)->assertOk()->assertSee($hotel->title);
});

test('a customer completes a hotel booking from the hotel page flow (Book Now → form → submit → confirmation)', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->first();

    $this->actingAs($user)
        ->get('/bookings/create?type=hotel&slug='.$hotel->slug)
        ->assertOk()
        ->assertSee($hotel->title);

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('hotel', $hotel, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    expect(Booking::query()->where('booking_type', 'hotel')->count())->toBe(1);

    $booking = Booking::query()->where('booking_type', 'hotel')->firstOrFail();
    expect($booking->service_id)->toBe($hotel->id)
        ->and($booking->service_title)->toBe($hotel->title)
        ->and($booking->user_id)->toBe($user->id)
        ->and($booking->status)->toBe('pending');

    $this->actingAs($user)
        ->get('/bookings/'.$booking->booking_reference)
        ->assertOk()
        ->assertSee($booking->booking_reference)
        ->assertSee($hotel->title);
});

test('the booking form only offers active hotels', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->first();
    $hotel->update(['is_active' => false]);

    $this->actingAs($user)
        ->get('/bookings/create')
        ->assertOk()
        ->assertDontSee($hotel->title);
});

test('an administrator can manage hotels', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/admin/hotels')->assertOk();
    $this->actingAs($admin)->get('/admin/hotels/create')->assertOk();
});

test('tour capacity is enforced across bookings for the same day', function () {
    $user = User::factory()->create();
    $tour = Tour::query()->first();
    $tour->update(['capacity' => 3]);

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('tour', $tour, '2026-10-10', null, 2))
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('tour', $tour, '2026-10-10', null, 2))
        ->assertRedirect()
        ->assertSessionHasErrors('booking');

    expect(Booking::query()->where('booking_type', 'tour')->count())->toBe(1);
});

test('a vehicle cannot take more passengers than its seating capacity', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', ($vehicle->seating_capacity ?? 4) + 1))
        ->assertRedirect()
        ->assertSessionHasErrors('booking');

    expect(Booking::count())->toBe(0);
});

test('the booking form only offers active services', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();
    $vehicle->update(['is_active' => false]);

    $this->actingAs($user)
        ->get('/bookings/create')
        ->assertOk()
        ->assertDontSee($vehicle->name);
});

test('login redirects a guest back to where they were booking', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);
    $this->get('/bookings/create')->assertRedirect('/login');

    $this->from('/bookings/create')
        ->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/bookings/create');
});

test('registration preserves the intended booking URL', function () {
    $email = 'new-user@example.com';

    $this->get('/bookings/create')->assertRedirect('/login');

    $this->from('/bookings/create')
        ->post('/register', [
            'name' => 'New User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect('/bookings/create');

    expect(Booking::count())->toBe(0)
        ->and(User::query()->where('email', $email)->exists())->toBeTrue();
});

test('guests can browse hotels and car rentals without logging in', function () {
    $hotel = Hotel::query()->first();
    $vehicle = TransportVehicle::query()->first();

    $this->get('/')->assertOk();
    $this->get('/hotel')->assertOk();
    $this->get('/hotels/'.$hotel->slug)->assertOk();
    $this->get('/transport')->assertOk();
    $this->get('/transport/'.$vehicle->slug)->assertOk();
});

test('a customer login is redirected to the user dashboard, never the admin dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/my-account');

    $this->assertAuthenticatedAs($user);
});

test('an administrator login is redirected to the admin dashboard', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticatedAs($admin);
});

test('customers cannot access the admin dashboard or admin modules', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)->get('/dashboard')->assertForbidden();
    $this->actingAs($customer)->get('/admin/bookings')->assertForbidden();
    $this->actingAs($customer)->get('/admin/hotels')->assertForbidden();
    $this->actingAs($customer)->get('/homes')->assertForbidden();
});

test('administrators can access the admin modules', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/dashboard')->assertOk();
    $this->actingAs($admin)->get('/admin/bookings')->assertOk();
});

test('a newly registered customer never receives admin access', function () {
    $this->post('/register', [
        'name' => 'Fresh Customer',
        'email' => 'fresh@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('/my-account');

    $user = User::query()->where('email', 'fresh@example.com')->firstOrFail();
    expect($user->is_admin)->toBeFalse();
});
