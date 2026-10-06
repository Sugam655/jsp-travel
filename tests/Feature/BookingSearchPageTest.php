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
    foreach (['Home', 'Bookings', 'Tours', 'Hotels', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
    Artisan::call('module:seed', ['module' => 'Tours', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Hotels', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Transport', '--force' => true]);
});

test('the booking search page is public and renders the shared public layout', function () {
    $this->get(route('booking.search'))
        ->assertOk()
        ->assertSee('id="mainNavbar"', false)
        ->assertSee('id="jsp-footer-main"', false)
        ->assertDontSee('id="adminlte-sidebar-menu"', false);
});

test('the booking search page offers every booking type the system supports', function () {
    $response = $this->get(route('booking.search'))->assertOk();

    foreach (Booking::TYPES as $type) {
        $response->assertSee('name="type" value="'.$type.'"', false)
            ->assertSee('data-booking-type="'.$type.'"', false);
    }

    // Booking::TYPES has no "flight", so the search page must not offer one.
    $response->assertDontSee('name="type" value="flight"', false)
        ->assertDontSee('data-booking-type="flight"', false);
});

test('each tab lists the real bookable services from the database', function () {
    // The page shows one booking type at a time, so each type has to be asked for
    // rather than assumed to be on the same response, and nothing is listed until
    // a filter was actually submitted.
    $tour = Tour::query()->firstOrFail();

    $this->get(route('booking.search', ['type' => 'tour', 'travelers' => 1]))
        ->assertOk()
        ->assertSee($tour->title)
        ->assertSee(e(route('bookings.create', ['type' => 'tour', 'slug' => $tour->slug])), false);

    $this->get(route('booking.search', ['type' => 'hotel', 'travelers' => 1]))
        ->assertOk()
        ->assertSee(Hotel::query()->firstOrFail()->title);

    $this->get(route('booking.search', ['type' => 'vehicle', 'travelers' => 1]))
        ->assertOk()
        ->assertSee(TransportVehicle::query()->where('availability', true)->firstOrFail()->name);
});

test('the search page lists nothing until a search has actually been submitted', function () {
    // Inventory that exists must not be presented as "the options" before the
    // customer has asked for anything, so the page opens as the search form only.
    $this->get(route('booking.search', ['type' => 'hotel']))
        ->assertOk()
        ->assertDontSee(Hotel::query()->firstOrFail()->title)
        ->assertDontSee('id="fh-resultsSection"', false);

    $this->get(route('booking.search', ['type' => 'tour']))
        ->assertOk()
        ->assertDontSee(Tour::query()->firstOrFail()->title);

    $this->get(route('booking.search', ['type' => 'vehicle']))
        ->assertOk()
        ->assertDontSee(TransportVehicle::query()->where('availability', true)->firstOrFail()->name);
});

test('each search result links into the confirmation page for that specific service', function () {
    $tour = Tour::query()->firstOrFail();
    $hotel = Hotel::query()->firstOrFail();
    $vehicle = TransportVehicle::query()->where('availability', true)->firstOrFail();

    $this->get(route('booking.search', ['type' => 'tour', 'travelers' => 1]))
        ->assertOk()
        ->assertSee(e(route('bookings.create', ['type' => 'tour', 'slug' => $tour->slug])), false);

    $this->get(route('booking.search', ['type' => 'hotel', 'travelers' => 1]))
        ->assertOk()
        ->assertSee(e(route('bookings.create', ['type' => 'hotel', 'slug' => $hotel->slug])), false);

    $this->get(route('booking.search', ['type' => 'vehicle', 'travelers' => 1]))
        ->assertOk()
        ->assertSee(e(route('bookings.create', ['type' => 'vehicle', 'slug' => $vehicle->slug])), false);
});

test('inactive services are never offered for booking', function () {
    $retired = Tour::query()->firstOrFail();
    $retired->update(['is_active' => false, 'title' => 'Retired Tour Package']);

    $this->get(route('booking.search', ['type' => 'tour', 'travelers' => 1]))
        ->assertOk()
        ->assertDontSee('Retired Tour Package');
});

test('each search tab shows an empty state instead of invented data when nothing is bookable', function () {
    Tour::query()->delete();
    Hotel::query()->delete();
    TransportVehicle::query()->delete();

    $this->get(route('booking.search', ['type' => 'tour', 'travelers' => 1]))
        ->assertOk()
        ->assertSee('No matching tours found.');

    $this->get(route('booking.search', ['type' => 'hotel', 'travelers' => 1]))
        ->assertOk()
        ->assertSee('No matching hotels found.');

    $this->get(route('booking.search', ['type' => 'vehicle', 'travelers' => 1]))
        ->assertOk()
        ->assertSee('No matching cars found.');
});

test('each search tab submits its own filters back to the search page', function () {
    $this->get(route('booking.search', ['type' => 'hotel']))
        ->assertOk()
        ->assertSee('action="'.route('booking.search').'"', false)
        ->assertSee('name="location"', false)
        ->assertSee('name="max_price"', false)
        ->assertSee('name="rating"', false);

    $this->get(route('booking.search', ['type' => 'vehicle']))
        ->assertOk()
        ->assertSee('name="vehicle_type"', false)
        ->assertSee('name="brand"', false)
        ->assertSee('name="duration_days"', false);

    $this->get(route('booking.search', ['type' => 'tour']))
        ->assertOk()
        ->assertSee('name="location"', false)
        ->assertSee('name="duration_days"', false)
        ->assertSee('name="max_price"', false);
});

test('a max price filter narrows the results to services inside that budget', function () {
    // Priced above every seeded hotel, so the control's top "up to" step becomes a
    // budget that genuinely excludes it.
    // Priced above every seeded hotel, so the budget the control offers as its top
    // "up to" step still excludes it.
    // Priced above every seeded hotel and below the next "up to" step, so the
    // control's top budget genuinely excludes it.
    $expensive = Hotel::query()->create([
        'title' => 'Over Budget Lodge',
        'slug' => 'over-budget-lodge',
        'location' => 'Dhangadhi',
        'price' => 80000,
        'rating' => 5,
        'is_active' => true,
    ]);

    $affordable = Hotel::query()->create([
        'title' => 'Within Budget Lodge',
        'slug' => 'within-budget-lodge',
        'location' => 'Dhangadhi',
        'price' => 4000,
        'rating' => 3,
        'is_active' => true,
    ]);

    // A budget the control offers, and one the lodge above does not fit inside.
    $budget = 50000;

    $content = $this->get(route('booking.search', ['type' => 'hotel', 'max_price' => $budget]))->assertOk()->getContent();

    expect($content)->toContain($affordable->title)
        ->and($content)->not->toContain($expensive->title);

    // The chosen filter has to survive the round trip, otherwise the customer
    // cannot tell which budget produced the list they are looking at.
    expect($content)->toMatch('/<option value="'.$budget.'"\s+selected/');
});

test('a rating filter narrows the results to matching star ratings', function () {
    $fiveStar = Hotel::query()->create([
        'title' => 'Five Star Hotel',
        'slug' => 'five-star-hotel',
        'location' => 'Dhangadhi',
        'price' => 20000,
        'rating' => 5,
        'is_active' => true,
    ]);

    $content = $this->get(route('booking.search', ['type' => 'hotel', 'rating' => 4]))->assertOk()->getContent();

    expect($content)->not->toContain($fiveStar->title);
});

test('a brand filter narrows the rental results to matching cars', function () {
    $toyota = TransportVehicle::query()->where('brand', 'Toyota')->firstOrFail();
    $other = TransportVehicle::query()->where('brand', '!=', 'Toyota')->firstOrFail();

    $content = $this->get(route('booking.search', ['type' => 'vehicle', 'brand' => 'Toyota']))->assertOk()->getContent();

    expect($content)->toContain($toyota->name)
        ->and($content)->not->toContain($other->name);
});

test('a guest clicking Book Now reaches the exact-service confirmation page directly', function () {
    $tour = Tour::query()->firstOrFail();
    $intended = route('bookings.create', ['type' => 'tour', 'slug' => $tour->slug]);

    $this->get($intended)
        ->assertOk()
        ->assertSee($tour->title)
        ->assertSee('id="serviceId" value="'.$tour->id.'"', false)
        ->assertSee('id="mainNavbar"', false)
        ->assertDontSee('id="adminlte-sidebar-menu"', false);
});

test('an authenticated customer reaches the same public confirmation page', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('bookings.create', ['type' => 'hotel', 'slug' => $hotel->slug]))
        ->assertOk()
        ->assertSee('id="bookingForm"', false)
        ->assertDontSee('id="adminlte-sidebar-menu"', false);
});

test('a confirmation request without a service is sent back to the search page', function () {
    $this->get(route('bookings.create'))
        ->assertRedirect(route('booking.search'));
});

test('frontend.booking is the only booking form and the search page is separate', function () {
    $hotel = Hotel::query()->firstOrFail();

    // The booking form lives in exactly one view, reached through bookings.create.
    $this->get(route('bookings.create', ['type' => 'hotel', 'slug' => $hotel->slug]))
        ->assertOk()
        ->assertViewIs('frontend.booking');

    // The search page is a distinct view that only links into the form.
    $this->get(route('booking.search'))
        ->assertOk()
        ->assertViewIs('frontend.booking-search')
        ->assertDontSee('id="bookingForm"', false)
        ->assertDontSee('action="'.route('bookings.store').'"', false);
});
