<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Hotels\Models\Hotel;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Home', 'Bookings', 'Tours', 'Hotels', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
    Artisan::call('module:seed', ['module' => 'Tours', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Hotels', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Transport', '--force' => true]);
});

test('the public navbar shows Login but no separate Register link, plus Book Now', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.route('login').'"', false)
        ->assertDontSee('href="'.route('register').'"', false)
        ->assertSee('Book Now')
        ->assertDontSee('My Dashboard')
        ->assertDontSee('Admin Dashboard');
});

test('the login page is public and shows a Register link to the registration page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('href="'.route('register').'"', false)
        ->assertSee('Register');
});

test('an authenticated customer browsing the frontend is signed out and gets the guest navbar', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/')
        ->assertOk()
        // Browsing the public site ends the session, so the navbar is the guest
        // one: Login, and no account or dashboard entry of any kind.
        ->assertSee('href="'.route('login').'"', false)
        ->assertDontSee('Logout')
        ->assertDontSee('My Dashboard')
        ->assertDontSee('>My Bookings</a>')
        ->assertDontSee('>Notifications</a>')
        ->assertDontSee('Admin Dashboard');

    $this->assertGuest();
});

test('an authenticated admin browsing the frontend is signed out and gets the guest navbar', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/')
        ->assertOk()
        ->assertSee('href="'.route('login').'"', false)
        ->assertDontSee('href="'.route('admin.dashboard').'"', false)
        ->assertDontSee('Admin Dashboard')
        ->assertDontSee('Logout');

    $this->assertGuest();
});

test('the navbar Tours link opens the tours listing page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.route('tours.index').'"', false);

    $this->get(route('tours.index'))
        ->assertOk()
        ->assertSee('id="mainNavbar"', false);
});

test('the public navbar carries no logout form and the logout route still ends the session', function () {
    $user = User::factory()->create(['is_admin' => false]);

    // The navbar is a guest interface: it links to Login, never to a logout form.
    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.route('login').'"', false)
        ->assertDontSee('action="'.route('logout').'"', false);

    // The panel still has a way to sign out, which is where the route is used.
    $this->actingAs($user)->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

test('the navbar Book Now link opens the public booking search for a guest', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.route('booking.search').'"', false);

    // Book Now starts the booking, it does not pick a service for the customer:
    // the form itself is reached from a search result.
    $this->get(route('booking.search'))
        ->assertOk()
        ->assertSee('id="fh-hotelForm"', false)
        ->assertSee('id="mainNavbar"', false)
        ->assertSee('id="jsp-footer-main"', false)
        ->assertSee('Login')
        ->assertDontSee('Logout');
});

test('the confirmation page opens in the public layout and keeps the customer signed in', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    // The form is the one public page a signed-in customer must stay signed in
    // for, otherwise their submission could never be completed.
    $this->actingAs($user)
        ->get(route('bookings.create', ['type' => 'hotel', 'slug' => $hotel->slug]))
        ->assertOk()
        ->assertSee('id="bookingForm"', false)
        ->assertSee('id="mainNavbar"', false)
        ->assertSee('id="jsp-footer-main"', false)
        ->assertSee('href="'.route('login').'"', false)
        ->assertDontSee('Logout')
        ->assertDontSee('id="adminlte-sidebar-menu"', false)
        ->assertDontSee('class="app-sidebar bg-body-secondary shadow"', false);

    $this->assertAuthenticatedAs($user);
});

test('public pages keep the public navbar and footer for guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="mainNavbar"', false)
        ->assertSee('id="jsp-footer-main"', false);
});
