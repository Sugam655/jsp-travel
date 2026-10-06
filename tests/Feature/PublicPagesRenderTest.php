<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Home', 'Bookings', 'Tours', 'Hotels', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
    Artisan::call('module:seed', ['module' => 'Tours', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Hotels', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Transport', '--force' => true]);
});

test('every public marketing page renders for a guest without legacy static links', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertDontSee('.html"', false);
})->with([
    'home',
    'about',
    'contact.index',
    'destinations.index',
    'tours.index',
    'hotel',
    'transport',
    'booking.search',
]);

test('every public detail page renders for a guest', function () {
    $this->get(route('tours.show', Tour::query()->firstOrFail()))
        ->assertOk()
        ->assertDontSee('.html"', false);

    $this->get(route('hotels.show', Hotel::query()->firstOrFail()))
        ->assertOk()
        ->assertDontSee('.html"', false);

    $this->get(route('transport.show', TransportVehicle::query()->firstOrFail()))
        ->assertOk()
        ->assertDontSee('.html"', false);
});

test('public pages keep the shared navbar and footer partials', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('id="mainNavbar"', false)
        ->assertSee('id="jsp-footer-main"', false);
});
