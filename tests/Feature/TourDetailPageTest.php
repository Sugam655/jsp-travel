<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Tours\Models\Tour;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Home', 'Bookings', 'Tours', 'Hotels', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
    Artisan::call('module:seed', ['module' => 'Tours', '--force' => true]);
});

test('the tour detail page shows the stored tour data instead of static content', function () {
    $tour = Tour::query()->firstOrFail();

    $this->get(route('tours.show', $tour))
        ->assertOk()
        ->assertSee($tour->title)
        ->assertSee('Rs.'.number_format((float) $tour->price))
        ->assertDontSee('Morning Cultural Walk')
        ->assertDontSee('tour-detail.html');
});

test('the tour detail page Book Now button targets the booking form for that tour', function () {
    $tour = Tour::query()->firstOrFail();

    $this->get(route('tours.show', $tour))
        ->assertOk()
        ->assertSee(
            e(route('bookings.create', ['type' => 'tour', 'slug' => $tour->slug])),
            false
        );
});

test('the tour detail page recommends other active tours', function () {
    $tour = Tour::query()->firstOrFail();

    $other = Tour::query()
        ->whereKeyNot($tour->getKey())
        ->firstOrFail();

    $this->get(route('tours.show', $tour))
        ->assertOk()
        ->assertSee($other->title)
        ->assertSee(route('tours.show', $other), false);
});

test('an inactive tour is not publicly reachable', function () {
    $tour = Tour::query()->firstOrFail();
    $tour->update(['is_active' => false]);

    $this->get(route('tours.show', $tour))->assertNotFound();
});
