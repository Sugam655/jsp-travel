<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Http\Middleware\PreserveBookingDraft;
use Modules\Bookings\Models\Booking;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Bookings', 'Hotels', 'Tours', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
    Artisan::call('module:seed', ['module' => 'Hotels', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Tours', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Transport', '--force' => true]);
});

/**
 * The four states the public site has to support.
 *
 * The AdminLTE panel and the public frontend are separate areas: the panel keeps
 * its own authentication, and opening a public page ends the session, so the
 * frontend is always rendered for a guest.
 */
test('an administrator working in the panel is signed out by opening the public home page', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    // 1-3. The panel works as it always has.
    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('id="adminlte-sidebar-menu"', false);

    // 4-7. Entering the public frontend signs the session out for real, not just
    // hides the links.
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('login').'"', false)
        ->assertDontSee('Admin Dashboard')
        ->assertDontSee('Logout')
        ->assertDontSee('action="'.route('logout').'"', false);

    $this->assertGuest();
});

test('a signed in customer is signed out by opening the public home page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('user.dashboard'))->assertOk();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('login').'"', false)
        ->assertDontSee('Logout');

    $this->assertGuest();
});

test('every public browsing page ends the session and renders the guest navbar', function (string $path) {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get($path)
        ->assertOk()
        ->assertSee('href="'.route('login').'"', false)
        ->assertDontSee('Admin Dashboard')
        ->assertDontSee('Logout');

    $this->assertGuest();
})->with([
    'about' => '/about',
    'tours' => '/tours',
    'hotels' => '/hotel',
    'car rental' => '/transport',
    'destinations' => '/destinations',
    'booking search' => '/book',
]);

test('a public service detail page ends the session too', function (string $path) {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get($path)
        ->assertOk()
        ->assertSee('href="'.route('login').'"', false)
        ->assertDontSee('Admin Dashboard')
        ->assertDontSee('Logout');

    $this->assertGuest();
})->with([
    'hotel detail' => fn () => '/hotels/'.Hotel::query()->firstOrFail()->slug,
    'tour detail' => fn () => '/tours/'.Tour::query()->firstOrFail()->slug,
    'vehicle detail' => fn () => '/transport/'.TransportVehicle::query()->firstOrFail()->slug,
]);

test('the public home page renders normally for a guest', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="mainNavbar"', false)
        ->assertSee('href="'.route('login').'"', false)
        ->assertSee('Book Now')
        ->assertDontSee('Logout');

    $this->assertGuest();
});

test('the backend stays protected after the frontend signed the visitor out', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get(route('home'))->assertOk();

    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    $this->get(route('admin.bookings.index'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('a public page also ends the session of a user signed in on another tab', function () {
    $user = User::factory()->create(['is_admin' => false]);

    // The session is gone for the panel too, not just for the rendered navbar:
    // a refresh of a protected page no longer works.
    $this->actingAs($user)->get(route('home'))->assertOk();

    $this->get(route('bookings.my'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('the signing-in and booking flow survives the frontend sign-out rule', function () {
    $hotel = Hotel::query()->where('price', '>', 0)->firstOrFail();
    $user = User::factory()->create(['is_admin' => false]);

    $payload = [
        'booking_type' => 'hotel',
        'service_id' => $hotel->id,
        'name' => 'Separation Guest',
        'email' => $user->email,
        'phone' => '9812345678',
        'travelers' => 2,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'policy_accepted' => '1',
    ];

    // A guest fills the form in and is asked to sign in, keeping every answer.
    $this->post(route('bookings.store'), $payload)
        ->assertRedirect(route('login'));

    expect(session(PreserveBookingDraft::SESSION_KEY))->toBeArray()
        ->and(Booking::query()->count())->toBe(0);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect();

    // The login returns the customer to the confirmation page for the hotel they
    // chose, still signed in, because that page is where the request is made.
    $this->get(route('bookings.create', ['type' => 'hotel', 'service_id' => $hotel->id]))
        ->assertOk()
        ->assertSee('id="bookingForm"', false)
        ->assertSee('value="'.e($hotel->id).'"', false)
        ->assertSee('value="2026-11-02"', false);

    $this->assertAuthenticatedAs($user);

    $this->post(route('bookings.store'), $payload)
        ->assertRedirect(route('bookings.show', Booking::query()->sole()->booking_reference));
});
