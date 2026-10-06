<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Modules\Bookings\Http\Middleware\PreserveBookingDraft;
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

test('guests can open the booking form but must sign in to submit a booking', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);
    $vehicle = TransportVehicle::query()->first();

    $this->get('/bookings/create?type=vehicle&slug='.$vehicle->slug)
        ->assertOk()
        ->assertSee('id="bookingForm"', false);

    $this->get('/bookings/my')->assertRedirect('/login');

    // Submitting while signed out must not create a booking.
    $this->from('/bookings/create?type=vehicle&slug='.$vehicle->slug)
        ->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect('/login');

    expect(Booking::count())->toBe(0);

    // After signing in the customer can submit, and is returned to the form.
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect();

    $this->post('/bookings', bookingPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    expect(Booking::count())->toBe(1);

    $this->get('/bookings/create?type=vehicle&slug='.$vehicle->slug)
        ->assertOk()
        ->assertSee($vehicle->name);
});

test('a guest booking request survives the sign in step with every answer intact', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);
    $hotel = Hotel::query()->first();

    $payload = bookingPayload('hotel', $hotel, '2026-11-04', '2026-11-07', 3, null, [
        'name' => 'Guest Filled This In',
        'phone' => '9812345678',
        'address' => 'Lalitpur',
        'message' => 'Late arrival, quiet room please',
    ]);

    $this->from('/bookings/create?type=hotel&slug='.$hotel->slug)
        ->post('/bookings', $payload)
        ->assertRedirect('/login');

    expect(Booking::count())->toBe(0);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect();

    // The form comes back filled in, not blank.
    $this->get('/bookings/create?type=hotel&slug='.$hotel->slug)
        ->assertOk()
        ->assertSee('Guest Filled This In')
        ->assertSee('9812345678')
        ->assertSee('Lalitpur')
        ->assertSee('Late arrival, quiet room please')
        ->assertSee('2026-11-04')
        ->assertSee('2026-11-07');

    // And the restored request can be submitted unchanged.
    $this->post('/bookings', $payload)->assertRedirect();

    expect(Booking::count())->toBe(1);
});

test('the guest booking draft is restored once then ages out of the session flash window', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);
    $hotel = Hotel::query()->first();

    $payload = bookingPayload('hotel', $hotel, '2026-11-04', '2026-11-07', 3, null, [
        'name' => 'Guest Filled This In',
    ]);

    $this->from('/bookings/create?type=hotel&slug='.$hotel->slug)->post('/bookings', $payload)->assertRedirect('/login');
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();

    // The draft is rendered into the form, then pulled out of the session so it
    // cannot accumulate or be replayed on later visits.
    $this->get('/bookings/create?type=hotel&slug='.$hotel->slug)->assertOk()->assertSee('Guest Filled This In');
    expect(Session::missing(PreserveBookingDraft::SESSION_KEY))->toBeTrue();

    // The restored values ride the ordinary old-input flash window, so they age
    // out instead of sticking around permanently.
    $this->get('/bookings/create?type=hotel&slug='.$hotel->slug)->assertOk()->assertSee('Guest Filled This In');
    $this->get('/bookings/create?type=hotel&slug='.$hotel->slug)->assertOk()->assertDontSee('Guest Filled This In');
});

test('the guest booking draft only keeps the form fields the booking form submits', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);
    $hotel = Hotel::query()->first();

    $this->from('/bookings/create?type=hotel&slug='.$hotel->slug)
        ->post('/bookings', bookingPayload('hotel', $hotel, '2026-11-04', '2026-11-07', 3, null, [
            'name' => 'Real Name',
            // A field the form never sends, plus fields that must not be trusted.
            'total_amount' => 1,
            'user_id' => $user->id,
            'is_admin' => true,
            'status' => 'confirmed',
            'junk' => str_repeat('a', 20000),
        ]))
        ->assertRedirect('/login');

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();

    $response = $this->get('/bookings/create?type=hotel&slug='.$hotel->slug)->assertOk();

    $response->assertSee('Real Name');
    $response->assertDontSee(str_repeat('a', 200));
    expect(Session::get(PreserveBookingDraft::SESSION_KEY))->toBeNull();
});

test('a signed in customer never has a booking draft written to the session', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('hotel', $hotel, '2026-11-04', '2026-11-07', 2))
        ->assertRedirect();

    expect(Session::get(PreserveBookingDraft::SESSION_KEY))->toBeNull();
});

test('a guest can read the live quote behind the booking form', function () {
    $vehicle = TransportVehicle::query()->first();

    $this->postJson('/bookings/quote', [
        'booking_type' => 'vehicle',
        'service_id' => $vehicle->id,
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-12',
        'travelers' => 2,
    ])->assertOk()->assertJsonPath('available', true);
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

test('a guest opens a specific hotel booking form directly and it stays pre-filled once signed in', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);
    $hotel = Hotel::query()->first();
    $url = '/bookings/create?type=hotel&slug='.$hotel->slug;

    $this->get($url)
        ->assertOk()
        ->assertSee($hotel->title)
        ->assertSee('value="'.$hotel->id.'"', false);

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

test('search only offers active hotels', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();
    $other = Hotel::query()->where('is_active', true)->where('id', '!=', $hotel->id)->firstOrFail();

    $hotel->update(['is_active' => false]);

    // The listing of what can be booked lives on the search page now, so that is
    // where a withdrawn hotel has to disappear from.
    $this->actingAs($user)
        ->get('/book?type=hotel&travelers=2')
        ->assertOk()
        ->assertSee($other->title)
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

test('search only offers active services', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('is_active', true)->firstOrFail();
    $other = TransportVehicle::query()->where('is_active', true)->where('id', '!=', $vehicle->id)->firstOrFail();

    $vehicle->update(['is_active' => false]);

    $this->actingAs($user)
        ->get('/book?type=vehicle&travelers=2')
        ->assertOk()
        ->assertSee($other->name)
        ->assertDontSee($vehicle->name);
});

test('login redirects a guest back to the protected bookings page they were viewing', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);
    $this->get('/bookings/my')->assertRedirect('/login');

    $this->from('/bookings/my')
        ->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/bookings/my');
});

test('registration preserves the intended bookings URL', function () {
    $email = 'new-user@example.com';

    $this->get('/bookings/my')->assertRedirect('/login');

    $this->from('/bookings/my')
        ->post('/register', [
            'name' => 'New User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect('/bookings/my');

    expect(Booking::count())->toBe(0)
        ->and(User::query()->where('email', $email)->exists())->toBeTrue();
});

test('a signed in non owner cannot access another users booking via session email', function () {
    $owner = User::factory()->create(['is_admin' => false]);
    $other = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->first();

    $this->actingAs($owner)->post('/bookings', bookingPayload('hotel', $hotel, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('user_id', $owner->id)->firstOrFail();

    $this->actingAs($other)
        ->withSession(['booking_email' => $booking->email])
        ->get('/bookings/'.$booking->booking_reference)
        ->assertForbidden();
});

test('lookup does not reassign a booking that already has an owner', function () {
    $owner = User::factory()->create(['is_admin' => false]);
    // Same address as the booking email, which is exactly the case the
    // ownership check has to refuse.
    $other = User::factory()->create(['is_admin' => false, 'email' => 'traveller@example.com']);
    $hotel = Hotel::query()->first();

    $this->actingAs($owner)->post('/bookings', bookingPayload('hotel', $hotel, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('user_id', $owner->id)->firstOrFail();

    // The lookup email matches the signed-in account, but the booking is already
    // owned, so it must not be handed over.
    $this->actingAs($other)
        ->post('/bookings/lookup', ['booking_reference' => $booking->booking_reference, 'email' => $booking->email])
        ->assertRedirect();

    $booking->refresh();
    expect($booking->user_id)->toBe($owner->id);
});

test('lookup can still claim an unowned booking made with the same email', function () {
    $user = User::factory()->create(['is_admin' => false, 'email' => 'solo@example.com']);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('hotel', $hotel, '2026-10-10', '2026-10-12', 2, 'solo@example.com'))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'hotel')->firstOrFail();
    $booking->update(['user_id' => null]);

    $this->actingAs($user)
        ->post('/bookings/lookup', ['booking_reference' => $booking->booking_reference, 'email' => 'solo@example.com'])
        ->assertRedirect();

    expect($booking->fresh()->user_id)->toBe($user->id);
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
        ->assertRedirect('/user/dashboard');

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

    $this->actingAs($customer)->get('/admin/dashboard')->assertForbidden();
    $this->actingAs($customer)->get('/admin/bookings')->assertForbidden();
    $this->actingAs($customer)->get('/admin/hotels')->assertForbidden();
    $this->actingAs($customer)->get('/homes')->assertForbidden();
});

test('administrators can access the admin modules', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    $this->actingAs($admin)->get('/admin/bookings')->assertOk();
});

test('a newly registered customer never receives admin access', function () {
    $this->post('/register', [
        'name' => 'Fresh Customer',
        'email' => 'fresh@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('/user/dashboard');

    $user = User::query()->where('email', 'fresh@example.com')->firstOrFail();
    expect($user->is_admin)->toBeFalse();
});

test('the server ignores client supplied ownership and money fields when creating a booking', function () {
    $attacker = User::factory()->create(['is_admin' => false]);
    $victim = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->first();

    $this->actingAs($attacker)
        ->post('/bookings', bookingPayload('hotel', $hotel, '2026-10-10', '2026-10-12', 2, null, [
            'user_id' => $victim->id,
            'total_amount' => 1,
            'advance_amount' => 0,
            'discount' => 9999,
            'status' => 'confirmed',
            'is_admin' => true,
        ]))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'hotel')->firstOrFail();

    // Ownership is derived from the session, never from the payload, and the
    // money columns come from the server-side quote.
    expect($booking->user_id)->toBe($attacker->id)
        ->and($booking->status)->toBe('pending')
        ->and((float) $booking->total_amount)->toBeGreaterThan(0.0)
        ->and((float) $booking->discount)->toBe(0.0)
        ->and($attacker->fresh()->is_admin)->toBeFalse();
});

test('the booking form marks the end date required for hotels and vehicles but not tours', function () {
    // The end date control is required wherever the stay or the rental has a
    // drop-off, and optional for a single-day tour.
    $this->get('/bookings/create?type=hotel&slug='.Hotel::query()->first()->slug)
        ->assertOk()
        ->assertSee('id="end_date"', false)
        ->assertSee('data-period-end required', false);

    $this->get('/bookings/create?type=vehicle&slug='.TransportVehicle::query()->first()->slug)
        ->assertOk()
        ->assertSee('id="end_date"', false)
        ->assertSee('data-period-end required', false);

    $tour = Tour::query()->first();
    if ($tour !== null) {
        // The end date is optional for a tour, and it says so by not being
        // required rather than by explaining itself underneath the field.
        $this->get('/bookings/create?type=tour&slug='.$tour->slug)
            ->assertOk()
            ->assertSee('data-period-end', false)
            ->assertDontSee('data-period-end required', false)
            ->assertDontSee('Optional for single-day tours.');
    }
});

test('a hotel or vehicle booking without an end date is rejected with a clear message', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('hotel', $hotel, '2026-10-10', null, 2))
        ->assertRedirect()
        ->assertSessionHasErrors('booking');

    expect(collect(session('errors')->get('booking'))->first())
        ->toContain('end (check-out / return) date is required');
});

test('overdue pending bookings are expired by the scheduled command', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingPayload('hotel', $hotel, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    expect($booking->expires_at)->not->toBeNull();

    // Still pending before the deadline.
    Artisan::call('bookings:expire-overdue');
    expect($booking->fresh()->status)->toBe('pending');

    $booking->update(['expires_at' => now()->subMinute()]);

    Artisan::call('bookings:expire-overdue');

    expect($booking->fresh()->status)->toBe('expired')
        ->and($booking->history()->where('action', 'expired')->exists())->toBeTrue();
});
