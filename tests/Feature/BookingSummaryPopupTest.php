<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Models\Booking;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;

/*
 * The confirmation popup that opens from a search result.
 *
 * Everything it shows has to come from the server's own summary of that result,
 * and what it posts has to go through the same booking path the confirmation page
 * uses, so these tests read the popup out of the rendered search page and submit
 * what the popup would submit.
 */

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
 * The server summaries the popup is filled in from, keyed as "type-id".
 *
 * @return array<string, array<string, mixed>>
 */
function popupSummaries(string $html): array
{
    preg_match('/<script type="application\/json" id="fh-bookingSummaries">(.*?)<\/script>/s', $html, $matches);

    return json_decode(html_entity_decode($matches[1] ?? '', ENT_QUOTES), true) ?: [];
}

/**
 * The labelled rows of one popup summary, as "label => value".
 *
 * @param  array<string, mixed>  $summary
 * @return array<string, string>
 */
function popupRows(array $summary): array
{
    $rows = [];

    foreach ($summary['rows'] ?? [] as $row) {
        $rows[$row['label']] = $row['value'];
    }

    return $rows;
}

/**
 * The payload the popup form submits for a summary.
 *
 * @param  array<string, mixed>  $summary
 * @return array<string, mixed>
 */
function popupPayload(array $summary, array $overrides = []): array
{
    return array_merge([
        'booking_type' => $summary['bookingType'],
        'service_id' => $summary['serviceId'],
        'start_date' => $summary['startDate'],
        'end_date' => $summary['endDate'],
        'travelers' => $summary['travelers'],
        'submission_token' => $summary['submissionToken'],
    ], $overrides);
}

/**
 * The summary keys every result button on the page points at.
 *
 * @return list<string>
 */
function popupTriggerKeys(string $html): array
{
    preg_match_all('/data-booking-summary="([^"]+)"/', $html, $matches);

    return $matches[1];
}

/**
 * A search of the given type, as the navbar's Book Now leads to it.
 *
 * @param  array<string, mixed>  $filters
 * @return array{html: string, summaries: array<string, array<string, mixed>>}
 */
function popupSearch(string $type, array $filters): array
{
    $html = test()->get(route('booking.search', array_merge(['type' => $type], $filters)))
        ->assertOk()
        ->getContent();

    return ['html' => $html, 'summaries' => popupSummaries($html)];
}

test('opening the booking search shows the form only, with no results and no popup', function () {
    Hotel::query()->firstOrFail();

    $html = $this->get(route('booking.search', ['type' => 'hotel']))->assertOk()->getContent();

    // No inventory is listed, and there is no popup to open, before a search.
    expect($html)->not->toContain('fh-book-now-btn')
        ->and($html)->not->toContain('id="fh-bookingSummaryModal"')
        ->and($html)->not->toContain('id="fh-bookingSummaries"');
});

test('every result button has a summary waiting for it, so the popup is never empty', function (string $type, array $filters) {
    $search = popupSearch($type, $filters);

    $keys = popupTriggerKeys($search['html']);

    // The page has results to click...
    expect($keys)->not->toBeEmpty();

    // ...and every one of those buttons names a summary the page actually carries.
    // A button whose key is missing is what leaves the popup open and empty: the
    // browser has nothing to fill it with, whatever the server rendered.
    foreach ($keys as $key) {
        expect($search['summaries'])->toHaveKey($key);
    }

    // And that summary is complete enough to render a whole popup from.
    $summary = $search['summaries'][$keys[0]];

    expect($summary['popupTitle'])->not->toBeEmpty()
        ->and($summary['serviceTitle'])->not->toBeEmpty()
        ->and($summary['rows'])->not->toBeEmpty()
        ->and($summary['total'])->not->toBeEmpty()
        ->and($summary['bookingType'])->toBe($type)
        ->and($summary['submissionToken'])->not->toBeEmpty();
})->with([
    'hotel' => ['hotel', ['start_date' => '2026-11-02', 'end_date' => '2026-11-05', 'travelers' => 2]],
    'tour' => ['tour', ['start_date' => '2026-11-20', 'travelers' => 2]],
    'rent a car' => ['vehicle', ['start_date' => '2026-11-10', 'end_date' => '2026-11-13', 'travelers' => 2]],
]);

test('the navbar Book Now leads to each service search with its type kept', function (string $type, string $marker) {
    // The navbar's Book Now is one plain link to the search, and the service tabs
    // are links that carry the type. Nothing here needs a popup to get a customer
    // from the navbar into a search, so none is rendered at this stage.
    $html = $this->get(route('booking.search', ['type' => $type]))->assertOk()->getContent();

    expect($html)->toContain('href="'.route('booking.search', ['type' => $type]).'"')
        ->and($html)->toContain($marker)
        // The form for this service is the one shown, not merely present.
        ->and($html)->toContain($marker)
        ->and($html)->not->toContain('id="fh-bookingSummaryModal"')
        ->and($html)->not->toContain('data-bs-target="#fh-bookingSummaryModal"');
})->with([
    'hotel' => ['hotel', 'id="fh-hotelForm"'],
    'tour' => ['tour', 'id="fh-tourForm"'],
    'rent a car' => ['vehicle', 'id="fh-carForm"'],
]);

test('every search result opens the one shared popup with the server summary of that result', function () {
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    $html = $this->get(route('booking.search', [
        'type' => 'hotel',
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'travelers' => 2,
    ]))->assertOk()->getContent();

    // One popup for the whole page, shared by every card.
    expect(substr_count($html, 'id="fh-bookingSummaryModal"'))->toBe(1)
        ->and(substr_count($html, 'data-bs-target="#fh-bookingSummaryModal"'))->toBeGreaterThan(1)
        // The popup posts to the one booking endpoint, not to a new one.
        ->and($html)->toContain('id="fh-bookingSummaryForm" method="POST" action="'.route('bookings.store').'"');

    // The popup is the only place the booking and price summary appear: there is
    // no duplicate details panel or price breakdown beside the results.
    expect($html)->not->toContain('id="fh-detailsSection"')
        ->and($html)->not->toContain('id="fh-quoteSection"')
        ->and($html)->not->toContain('Your booking details')
        ->and($html)->not->toContain('Price breakdown');

    // Close and the prominent Confirm Booking are the popup's own actions.
    expect($html)->toContain('>Close</button>')
        ->and($html)->toContain('data-summary-submit')
        ->and($html)->toContain('>Confirm Booking</span>');

    $summary = popupSummaries($html)['hotel-'.$hotel->id] ?? null;

    expect($summary)->not->toBeNull()
        ->and($summary['bookingType'])->toBe('hotel')
        ->and($summary['popupTitle'])->toBe('Confirm Hotel Booking')
        ->and($summary['serviceTitle'])->toBe($hotel->title)
        ->and($summary['startDate'])->toBe('2026-11-02')
        ->and($summary['endDate'])->toBe('2026-11-05')
        ->and((int) $summary['travelers'])->toBe(2)
        ->and($summary['available'])->toBeTrue()
        ->and($summary['submissionToken'])->toBeString()->not->toBeEmpty();

    // The price breakdown is the server's own, not browser arithmetic.
    expect($summary['unit'])->toContain('per night')
        ->and($summary['total'])->not->toBeEmpty()
        ->and($summary['total'])->toMatch('/^NPR [\d,]+\.\d{2}$/');
});

test('the popup describes a stay, a rental and a tour with the rows that apply to each', function () {
    $hotel = hotelSummary();
    $vehicle = vehicleSummary();
    $tour = tourSummary();

    $hotelRows = popupRows($hotel);
    $vehicleRows = popupRows($vehicle);
    $tourRows = popupRows($tour);

    expect(array_keys($hotelRows))->toContain('Check-in', 'Check-out', 'Number of nights', 'Guests')
        ->and($hotelRows['Check-in'])->toBe('Nov 2, 2026')
        ->and($hotelRows['Check-out'])->toBe('Nov 5, 2026')
        ->and($hotelRows['Number of nights'])->toBe('3')
        ->and($hotelRows['Guests'])->toBe('2');

    $vehicleModel = TransportVehicle::query()->findOrFail($vehicle['serviceId']);

    expect(array_keys($vehicleRows))->toContain('Vehicle name', 'Vehicle type', 'Pick-up', 'Drop-off', 'Rental duration', 'Passengers')
        ->and($vehicleRows['Vehicle name'])->toBe($vehicleModel->name)
        ->and($vehicleRows['Vehicle type'])->toBe($vehicleModel->type_label)
        ->and($vehicleRows['Rental duration'])->not->toBeEmpty()
        ->and($vehicleRows['Passengers'])->toBe('2');

    expect(array_keys($tourRows))->toContain('Travel date', 'Duration', 'Travellers')
        ->and($tourRows['Duration'])->toBe(Tour::query()->findOrFail($tour['serviceId'])->duration);

    // Each type names itself in the popup heading, and no type borrows another's
    // date labels.
    expect($vehicle['popupTitle'])->toBe('Confirm Car Booking')
        ->and($tour['popupTitle'])->toBe('Confirm Tour Booking')
        ->and(array_keys($hotelRows))->not->toContain('Pick-up')
        ->and(array_keys($tourRows))->not->toContain('Check-in');
});

test('a search without usable dates cannot be confirmed from the popup', function () {
    $summary = hotelSummary(['start_date' => null, 'end_date' => null, 'travelers' => 2]);

    expect($summary['available'])->toBeFalse()
        ->and($summary['message'])->toContain('date is required')
        // The result's own link stays the way to choose dates.
        ->and($this->get(route('booking.search', ['type' => 'hotel', 'travelers' => 2]))
            ->assertOk()
            ->getContent())->toContain('bookings/create?type=hotel&amp;slug='.Hotel::query()->findOrFail($summary['serviceId'])->slug);
});

test('a tour that has no seats left is not confirmable, and the server still refuses it', function () {
    $user = User::factory()->create();

    // The summary is read first, so the seats are taken on the very tour the popup
    // describes rather than on whichever tour happens to be first.
    $tour = Tour::query()->findOrFail(tourSummary()['serviceId']);
    $tour->update(['capacity' => 2]);

    // Fill the only two seats through the real booking path.
    $this->actingAs($user)->post(route('bookings.store'), [
        'booking_type' => 'tour',
        'service_id' => $tour->id,
        'name' => 'Seat Filler',
        'email' => 'seats@example.com',
        'phone' => '9800000000',
        'travelers' => 2,
        'start_date' => '2026-11-20',
        'policy_accepted' => '1',
    ])->assertRedirect();

    $summary = tourSummary(['start_date' => '2026-11-20', 'travelers' => 2]);

    expect($summary['serviceId'])->toBe($tour->id)
        ->and($summary['available'])->toBeFalse()
        ->and($summary['message'])->not->toBeEmpty();

    // The popup is only the first check: bookings.store refuses it as well.
    $this->actingAs($user)->post(route('bookings.store'), popupPayload($summary, [
        'name' => 'Late Booker',
        'email' => 'late@example.com',
        'phone' => '9800000000',
        'policy_accepted' => '1',
    ]))->assertSessionHasErrors('booking');

    expect(Booking::query()->where('booking_type', 'tour')->count())->toBe(1);
});

test('a guest confirming from the popup signs in first and the booking is made from what they confirmed', function () {
    $summary = hotelSummary();
    $hotel = Hotel::query()->findOrFail($summary['serviceId']);

    // The popup never shows a signed-in customer's details to a guest.
    $search = $this->get(route('booking.search', [
        'type' => 'hotel',
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'travelers' => 2,
    ]))->assertOk();

    $search->assertDontSee('name="policy_accepted"', false)
        ->assertSee('>Confirm Booking</span>', false);

    $response = $this->post(route('bookings.store'), popupPayload($summary, [
        'name' => 'Popup Customer',
        'email' => 'guest@example.com',
        'phone' => '9812345678',
        'policy_accepted' => '1',
    ]));

    $response->assertRedirect(route('login'));

    $user = User::factory()->create(['password' => bcrypt('password')]);

    // Signing in brings the guest back to the confirmation they interrupted, still
    // naming their service and dates, rather than to a blank form to start again.
    $resume = session('url.intended');

    expect($resume)->toContain('type=hotel')
        ->and($resume)->toContain('service_id='.$hotel->id)
        ->and($resume)->toContain('resume=');

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect($resume);

    // Following that link is the whole of the rest of the journey: the booking
    // exists, made from what the guest confirmed, with no second press.
    $this->get($resume)->assertRedirect(route('bookings.show', Booking::query()->sole()->booking_reference));

    $booking = Booking::query()->sole();

    expect(Booking::query()->count())->toBe(1)
        ->and($booking->service_id)->toBe($hotel->id)
        ->and($booking->start_date->toDateString())->toBe('2026-11-02')
        ->and($booking->end_date->toDateString())->toBe('2026-11-05')
        ->and($booking->travelers)->toBe(2);
});

test('a signed-in customer confirming from the popup gets the booking straight away', function () {
    $user = User::factory()->create();
    $summary = hotelSummary();
    $hotel = Hotel::query()->findOrFail($summary['serviceId']);

    $this->actingAs($user)->post(route('bookings.store'), popupPayload($summary, [
        'name' => 'Popup Customer',
        'email' => $user->email,
        'phone' => '9812345678',
        'policy_accepted' => '1',
    ]))->assertRedirect();

    $booking = Booking::query()->sole();

    expect($booking->service_id)->toBe($hotel->id)
        ->and($booking->service_title)->toBe($hotel->title)
        ->and($booking->start_date->toDateString())->toBe('2026-11-02')
        ->and($booking->end_date->toDateString())->toBe('2026-11-05')
        // The three nights of the stay, priced exactly as the popup showed them.
        ->and($booking->quantity)->toBe(3)
        ->and((float) $booking->total_amount)->toBeGreaterThan(0);
});

test('each result carries its own submission token so a replay cannot book a different service', function () {
    $summaries = popupSummaries($this->get(route('booking.search', [
        'type' => 'hotel',
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'travelers' => 2,
    ]))->assertOk()->getContent());

    $tokens = array_column($summaries, 'submissionToken');

    expect(count($tokens))->toBeGreaterThan(1)
        ->and(array_unique($tokens))->toHaveCount(count($tokens));

    // A double-clicked confirmation posts the same token twice, which resolves
    // back to the booking that token already created.
    $user = User::factory()->create();
    $summary = hotelSummary();

    $payload = popupPayload($summary, [
        'name' => 'Double Clicker',
        'email' => 'double@example.com',
        'phone' => '9800000000',
        'policy_accepted' => '1',
    ]);

    $this->actingAs($user)->post(route('bookings.store'), $payload)->assertRedirect();
    $this->actingAs($user)->post(route('bookings.store'), $payload)->assertRedirect();

    expect(Booking::query()->count())->toBe(1);
});

test('the popup leaves the public navbar exactly as it was', function () {
    $this->get(route('booking.search', [
        'type' => 'hotel',
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'travelers' => 2,
    ]))
        ->assertOk()
        ->assertSee('id="mainNavbar"', false)
        ->assertSee('Login')
        ->assertDontSee('Logout')
        ->assertDontSee('id="adminlte-sidebar-menu"', false);
});

/**
 * The popup summary of a searched hotel stay.
 *
 * @param  array<string, mixed>  $filters
 * @return array<string, mixed>
 */
function hotelSummary(array $filters = []): array
{
    return summaryFor('hotel', array_merge([
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'travelers' => 2,
    ], $filters));
}

/**
 * The popup summary of a searched car rental.
 *
 * @param  array<string, mixed>  $filters
 * @return array<string, mixed>
 */
function vehicleSummary(array $filters = []): array
{
    return summaryFor('vehicle', array_merge([
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-13',
        'travelers' => 2,
    ], $filters));
}

/**
 * The popup summary of a searched tour.
 *
 * @param  array<string, mixed>  $filters
 * @return array<string, mixed>
 */
function tourSummary(array $filters = []): array
{
    return summaryFor('tour', array_merge([
        'start_date' => '2026-11-20',
        'travelers' => 2,
    ], $filters));
}

/**
 * The summary the server rendered for the first result of a search.
 *
 * @param  array<string, mixed>  $filters
 * @return array<string, mixed>
 */
function summaryFor(string $type, array $filters): array
{
    $query = array_merge(['type' => $type], array_filter($filters, fn ($value) => $value !== null));

    $html = test()->get(route('booking.search', $query))->assertOk()->getContent();

    $summaries = popupSummaries($html);

    expect($summaries)->not->toBeEmpty();

    return array_values($summaries)[0];
}
