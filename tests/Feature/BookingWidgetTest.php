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

/**
 * The result cards live in their own section, so assertions about what a search
 * returned must not match the filter selects, which always list everything.
 */
function resultCards(string $html): string
{
    preg_match('/<section class="fh-results-section" id="fh-resultsSection">(.*?)<\/section>/s', $html, $matches);

    return $matches[1] ?? $html;
}

/**
 * Just the result cards, excluding the empty state and the "clear this search"
 * footer, so a page can be asserted to have returned no results at all.
 */
function resultCardBlocks(string $html): string
{
    return substr_count(resultCards($html), '<div class="fh-flight-card">') > 0
        ? resultCards($html)
        : '';
}

test('the search page offers every bookable type and shows flight as unavailable', function () {
    $response = $this->get('/book')->assertOk();

    foreach (Booking::TYPES as $type) {
        $response->assertSee('data-booking-type="'.$type.'"', false);
    }

    // Flight has no model, table or pricing source here, so it must never be
    // submitted or resolved to a service.
    $response->assertDontSee('data-tab="flight"', false)
        ->assertDontSee('name="service_id_flight"', false);

    $this->get('/bookings/create?type=flight&service_id=1')->assertNotFound();
});

test('a bare booking confirmation sends the customer back to the search page', function () {
    // Nothing is bookable without a choice, so the confirmation page must not
    // render an empty form that pretends to accept a service.
    $this->get('/bookings/create')
        ->assertRedirect(route('booking.search'));
});

test('the confirmation page locks in the exact service and posts to bookings.store', function () {
    $hotel = Hotel::query()->firstOrFail();

    $response = $this->get('/bookings/create?type=hotel&slug='.$hotel->slug)->assertOk();

    $response->assertSee('id="bookingForm"', false)
        ->assertSee(route('bookings.store'), false)
        ->assertSee('name="booking_type" id="bookingType" value="hotel"', false)
        ->assertSee('name="service_id" id="serviceId" value="'.$hotel->id.'"', false)
        ->assertSee($hotel->title);

    // The service is fixed by the link the customer clicked, so the old picker
    // that let them switch to any other bookable service must be gone.
    $response->assertDontSee('id="bookingType" class', false)
        ->assertDontSee('name="service_id_hotel"', false);
});

test('the confirmation page renders the quote and the period fields on the server', function () {
    $hotel = Hotel::query()->firstOrFail();

    $response = $this->get('/bookings/create?type=hotel&slug='.$hotel->slug.'&start_date=2026-12-01&end_date=2026-12-03&travelers=2')
        ->assertOk();

    // A customer without JavaScript still has to see what they are booking and
    // what it costs before submitting.
    $response->assertSee('id="fh-quoteSection"', false)
        ->assertSee('id="fh-quoteService">'.$hotel->title.'</span>', false)
        ->assertSee('id="fh-quoteNote"', false)
        ->assertSee('id="fh-quoteTotal">', false)
        ->assertSee('2 night(s)')
        ->assertSee('value="2026-12-01"', false)
        ->assertSee('value="2026-12-03"', false)
        ->assertSee('id="party_field"', false)
        ->assertSee('name="policy_accepted"', false)
        ->assertSee('id="bookingSubmit"', false);
});

test('the search page has no permanent booking details or summary panel', function () {
    // The old widget kept a "Your Booking Details" panel on every page, which
    // read as a summary of a booking the customer had not made yet. The search
    // page now only searches.
    $this->get('/book')
        ->assertOk()
        ->assertDontSee('id="fh-detailsSection"', false)
        ->assertDontSee('id="fh-quoteSection"', false)
        ->assertDontSee('id="bookingForm"', false);
});

test('the search page opens on the search form instead of a listing', function () {
    $content = $this->get('/book')->assertOk()->getContent();

    // Nothing is listed before anything has been searched for: inventory that
    // exists must not be presented as "the options" the customer chose.
    expect(resultCardBlocks($content))->toBe('')
        ->and($content)->not->toContain('id="fh-resultsSection"')
        ->and($content)->toContain('id="fh-hotelForm"')
        ->and($content)->not->toContain(Hotel::query()->firstOrFail()->title);
});

test('searching hotels shows real hotel results and no example data', function () {
    $hotel = Hotel::query()->firstOrFail();

    $response = $this->get('/book?type=hotel&travelers=2')->assertOk();

    $cards = resultCards($response->getContent());

    expect($cards)->toContain($hotel->title)
        // The link has to reach the exact-service confirmation page for this
        // result, with every searched filter carried along.
        ->and($cards)->toContain('bookings/create?type=hotel&amp;slug='.$hotel->slug)
        // The design's example properties must never reach the page.
        ->and($cards)->not->toContain('Yeti Mountain Resort')
        ->and($cards)->not->toContain('Pokhara Grand');
});

test('a search that matches nothing says so instead of showing invented results', function () {
    $this->get('/book?type=hotel&location=No Such Place Anywhere')
        ->assertOk()
        ->assertSee('No matching hotels found.')
        ->assertSee('0 found')
        ->assertDontSee('Yeti Mountain Resort');
});

test('searching vehicles filters by the vehicle type the customer chose', function () {
    $suv = TransportVehicle::query()->where('vehicle_type', 'suv')->firstOrFail();
    $bike = TransportVehicle::query()->where('vehicle_type', 'bike')->firstOrFail();

    $cards = resultCards($this->get('/book?type=vehicle&vehicle_type=suv')->assertOk()->getContent());

    expect($cards)->toContain($suv->name)
        ->and($cards)->not->toContain($bike->name);

    $cards = resultCards($this->get('/book?type=vehicle&vehicle_type=bike')->assertOk()->getContent());

    expect($cards)->toContain($bike->name)
        ->and($cards)->not->toContain($suv->name);
});

test('a vehicle that cannot take the party is not offered for those travellers', function () {
    $small = TransportVehicle::query()
        ->where('availability', true)
        ->whereNotNull('seating_capacity')
        ->orderBy('seating_capacity')
        ->first();

    // A party larger than the smallest car in the fleet must not be shown a car
    // that cannot seat it, because it would be rejected at submission instead.
    $cards = resultCards(
        $this->get('/book?type=vehicle&travelers='.((int) $small->seating_capacity + 10))->assertOk()->getContent()
    );

    expect($cards)->not->toContain($small->name);
});

test('searching tours filters by duration', function () {
    // The shipped tour defaults have no duration_days, so create the two
    // packages the filter is meant to separate.
    $short = Tour::query()->create([
        'title' => 'Short Duration Package',
        'slug' => 'short-duration-package',
        'location' => 'Dhangadhi',
        'duration' => '2 Days / 1 Night',
        'duration_days' => 2,
        'price' => 5000,
        'is_active' => true,
    ]);

    $long = Tour::query()->create([
        'title' => 'Long Duration Package',
        'slug' => 'long-duration-package',
        'location' => 'Dhangadhi',
        'duration' => '9 Days / 8 Nights',
        'duration_days' => 9,
        'price' => 9000,
        'is_active' => true,
    ]);

    $cards = resultCards($this->get('/book?type=tour&duration_days=2')->assertOk()->getContent());

    expect($cards)->toContain('Short Duration Package')
        ->and($cards)->not->toContain('Long Duration Package');

    $cards = resultCards($this->get('/book?type=tour&duration_days=9')->assertOk()->getContent());

    expect($cards)->toContain('Long Duration Package')
        ->and($cards)->not->toContain('Short Duration Package');
});

test('a Rent Now link carries the searched period, party and duration into the booking form', function () {
    $vehicle = TransportVehicle::query()->where('availability', true)->firstOrFail();

    $cards = resultCardBlocks(
        $this->get('/book?type=vehicle&vehicle_type='.$vehicle->vehicle_type
            .'&start_date=2026-12-01&end_date=2026-12-04&travelers=3')->assertOk()->getContent()
    );

    expect($cards)->not->toBe('')
        // The vehicle result carries its own action label rather than "Book Now".
        ->and($cards)->toMatch('/fh-book-now-btn[^>]*>\s*Rent\s*</');

    // The customer already chose these during the search, so the confirmation
    // page must not make them pick them a second time. The link is written as
    // escaped HTML, so the ampersands have to be compared encoded.
    preg_match('/href="([^"]*bookings\/create[^"]*)"/', $cards, $matches);
    $rentNowUrl = html_entity_decode($matches[1]);

    parse_str((string) parse_url($rentNowUrl, PHP_URL_QUERY), $query);

    expect($query)->toMatchArray([
        'type' => 'vehicle',
        'slug' => $vehicle->slug,
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-04',
        'travelers' => '3',
    ]);

    $form = $this->get($rentNowUrl)->assertOk()->getContent();

    expect($form)->toContain('name="service_id" id="serviceId" value="'.$vehicle->id.'"')
        ->and($form)->toContain('value="2026-12-01"')
        ->and($form)->toContain('value="2026-12-04"')
        ->and($form)->toContain('id="party_field"')
        ->and($form)->toMatch('/id="party_field"[^>]*value="3"/s');
});

test('a tour result carries the searched duration into the booking form', function () {
    // A searched length is matched against the tours themselves, so the tour is
    // given the length that is then searched for.
    $tour = Tour::query()->where('is_active', true)->firstOrFail();
    $tour->update(['duration_days' => 3]);

    $response = $this->get('/book?type=tour&duration_days=3&start_date=2026-12-01')->assertOk();

    preg_match('/href="([^"]*bookings\/create[^"]*)"/', resultCardBlocks($response->getContent()), $matches);

    expect($matches[1] ?? null)->not->toBeNull();

    parse_str((string) parse_url(html_entity_decode($matches[1]), PHP_URL_QUERY), $query);

    expect($query)->toMatchArray([
        'type' => 'tour',
        'duration_days' => '3',
    ]);

    // The link still names a real tour of that length, not just the right length.
    expect(Tour::query()->where('slug', $query['slug'] ?? null)
        ->where('duration_days', 3)
        ->exists())->toBeTrue();

    // And the form the customer lands on has already worked the end date out
    // from the length they searched for.
    $form = $this->get(html_entity_decode($matches[1]))->assertOk()->getContent();

    expect($form)->toContain('name="service_id" id="serviceId" value="'.$tour->id.'"')
        ->and($form)->toContain('value="2026-12-01"')
        ->and($form)->toContain('value="2026-12-04"');
});

test('the guest draft is restored into the search form dates and travellers', function () {
    $this->withSession([
        '_old_input' => [
            'booking_type' => 'vehicle',
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-25',
            'travelers' => 4,
        ],
    ])->get('/book?type=vehicle')
        ->assertOk()
        ->assertSee('value="2026-11-20"', false)
        ->assertSee('value="2026-11-25"', false)
        ->assertSee('value="4"', false);
});

test('an invalid search filter is rejected rather than silently ignored', function () {
    $this->get('/book?type=vehicle&vehicle_type=spaceship')
        ->assertSessionHasErrors('vehicle_type');
});

test('an unknown booking type is rejected', function () {
    $this->get('/book?type=flight')->assertSessionHasErrors('type');
});

/**
 * Results have to be visible, not merely present in the markup. These read the
 * stylesheet the page actually loads, because a section rendered into the HTML
 * but hidden by CSS still looks exactly like a search that returned nothing.
 */
function styleSheet(): string
{
    return (string) file_get_contents(public_path('style.css'));
}

/**
 * @return array<string, string> the declarations for a class in the fh-* stylesheet
 */
function classRule(string $selector): string
{
    $css = styleSheet();

    preg_match_all('/\.fh-[a-zA-Z0-9_-]+[^{}]*\{[^}]*\}/', $css, $groups);

    foreach ($groups[0] as $group) {
        [$block] = explode('{', $group, 2);

        $selectors = array_map('trim', explode(',', $block));

        if (in_array($selector, $selectors, true)) {
            return $group;
        }
    }

    return '';
}

test('the results section is visible without relying on JavaScript to reveal it', function () {
    $rule = classRule('.fh-results-section');

    expect($rule)->not->toBe('')
        ->and($rule)->not->toMatch('/display:\s*none/');
});

test('a searched result section is not hidden by an inline style', function () {
    $hotel = Hotel::query()->firstOrFail();

    $cards = resultCards(
        $this->get('/book?type=hotel&location='.$hotel->location)->assertOk()->getContent()
    );

    // showStaticResults() used to toggle this section from hidden via an inline
    // style; the server-rendered results must not carry that.
    expect($cards)->not->toMatch('/id="fh-resultsSection"[^>]*style="[^"]*display:\s*none/');
});

test('the three search panels are still toggled by the active class', function () {
    // These are the only fh-* rules allowed to default to hidden, because
    // exactly one panel may show at a time.
    foreach (['.fh-hotel-form', '.fh-car-form', '.fh-tour-form'] as $selector) {
        expect(classRule($selector))->toMatch('/display:\s*none/');
    }

    expect(classRule('.fh-hotel-form.active'))->toMatch('/display:\s*block/');
});

test('the search page still submits a real booking', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->firstOrFail();

    $this->actingAs($user)->post('/bookings', [
        'booking_type' => 'hotel',
        'service_id' => $hotel->id,
        'name' => 'Test Guest',
        'email' => 'guest@example.com',
        'phone' => '9800000000',
        'travelers' => 2,
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-12',
        'policy_accepted' => '1',
    ])->assertRedirect();

    expect(Booking::query()->where('user_id', $user->id)->count())->toBe(1);
});
