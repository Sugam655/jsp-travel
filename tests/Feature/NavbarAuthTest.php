<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

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

test('an authenticated customer sees My Dashboard and Logout instead of Login', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertSee('href="'.route('user.dashboard').'"', false)
        ->assertSee('My Dashboard')
        ->assertSee('Logout')
        ->assertDontSee('href="'.route('login').'"', false)
        ->assertDontSee('href="'.route('register').'"', false)
        ->assertDontSee('Admin Dashboard');
});

test('an authenticated admin sees Admin Dashboard and Logout', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/')
        ->assertOk()
        ->assertSee('href="'.route('dashboard').'"', false)
        ->assertSee('Admin Dashboard')
        ->assertSee('Logout')
        ->assertDontSee('href="'.route('login').'"', false)
        ->assertDontSee('My Dashboard');
});

test('logging out from the navbar restores the public guest navbar', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/')->assertSee('My Dashboard');

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();

    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.route('login').'"', false)
        ->assertDontSee('href="'.route('register').'"', false)
        ->assertDontSee('My Dashboard')
        ->assertDontSee('Logout');
});

test('the booking form page opens inside the user dashboard layout for a logged-in customer', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('bookings.create'))
        ->assertOk()
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertSee('class="app-sidebar bg-body-secondary shadow"', false)
        ->assertSee('class="app-header navbar navbar-expand bg-body"', false)
        ->assertSee('Log Out')
        ->assertSee('id="bookingForm"', false)
        ->assertDontSee('id="mainNavbar"', false)
        ->assertDontSee('id="mobileNav"', false);
});

test('public pages keep the public navbar and footer for guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('id="mainNavbar"', false)
        ->assertSee('id="jsp-footer-main"', false);
});
