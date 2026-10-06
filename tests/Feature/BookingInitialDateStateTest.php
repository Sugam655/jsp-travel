<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;

/*
 * The date a customer has not chosen yet is not a date they got wrong.
 *
 * Every booking opens on a form with empty dates, so an initial page load that
 * complains about them describes the form rather than the customer. These tests
 * read the four ways into the booking form and require each to arrive clean: no
 * required-date warning, nothing blocking, and a Review button that is actually
 * pressable - because pressing it is how the customer is asked.
 *
 * The counterpart is asserted too. Once dates are in play the same messages are
 * owed, and BookingRulesService stays the single place the server decides whether
 * a period is bookable.
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
 * Every word the period can be refused with, in the wording the page shows.
 * A clean opening page contains none of them.
 */
function dateWarningPhrases(): array
{
    return [
        'These dates cannot be booked yet',
        'A start (check-in / pickup) date is required.',
        'An end (check-out / return) date is required.',
        'A start date is required for tour bookings.',
        'The service start date cannot be in the past.',
        'Check-out date must be after the check-in date.',
        'Return date must be after the pickup date.',
        'The end date must be after the start date.',
    ];
}

/**
 * The page as it arrives, before the customer has touched anything.
 */
function initialBookingHtml(string $type): string
{
    $slug = match ($type) {
        'hotel' => Hotel::query()->where('is_active', true)->firstOrFail()->slug,
        'vehicle' => TransportVehicle::query()->where('is_active', true)->where('availability', true)->firstOrFail()->slug,
        'tour' => Tour::query()->where('is_active', true)->firstOrFail()->slug,
    };

    return test()
        ->get(route('bookings.create', ['type' => $type, 'slug' => $slug]))
        ->assertOk()
        ->getContent();
}

/**
 * The opening page with the invisible hand-offs taken back out.
 *
 * Each date rule is worded once, by BookingRulesService, and the page carries that
 * wording in a data attribute so the field beside the date can repeat it verbatim
 * when the rule is broken. An attribute is read by the script and shown to nobody,
 * so it is not a warning on arrival. What a customer can actually read is what
 * this leaves behind, and that is what these tests are about.
 */
function withoutHandedOffWording(string $html): string
{
    return preg_replace(
        '/\sdata-(?:missing-start|missing-end|end-date-order-error)="[^"]*"/',
        '',
        $html
    );
}

/*
|--------------------------------------------------------------------------
| Every way in opens clean
|--------------------------------------------------------------------------
*/

test('an untouched booking form names no date as missing, in any booking type', function (string $type) {
    $html = withoutHandedOffWording(initialBookingHtml($type));

    foreach (dateWarningPhrases() as $phrase) {
        expect($html)->not->toContain($phrase);
    }
})->with(['hotel', 'vehicle', 'tour']);

test('an untouched booking form leaves Review pressable', function (string $type) {
    expect(initialBookingHtml($type))
        ->toContain('data-dates-attempted="false"')
        // A dead Review button on arrival is the other half of the same problem:
        // the form would be refusing to be filled in before being asked.
        ->not->toMatch('/id="reviewBookingButton"[^>]*\sdisabled/s');
})->with(['hotel', 'vehicle', 'tour']);

test('an untouched booking form says nothing in the popup blocker', function (string $type) {
    expect(initialBookingHtml($type))
        ->toMatch('/id="fh-confirmBlocker"/');
})->with(['hotel', 'vehicle', 'tour']);

test('the general Book Now landing opens without a date warning', function () {
    $html = withoutHandedOffWording(test()->get(route('booking.search'))->assertOk()->getContent());

    foreach (dateWarningPhrases() as $phrase) {
        expect($html)->not->toContain($phrase);
    }
});

test('the booking form takes its floor from the server, never from the browser clock', function () {
    // Both date controls render the server's min, and the script captures it into
    // todayFloor before it adjusts either floor. That is what stops a browser whose
    // clock disagrees with the server from raising or lowering what is bookable.
    $html = initialBookingHtml('hotel');

    expect($html)->toContain('min="'.now()->toDateString().'"');

    $script = file_get_contents(resource_path('js/script.js'));

    expect($script)
        // Captured from the rendered attribute, not from today() in here.
        ->toContain('dataset.todayFloor = visibleEnd.min || "";')
        ->not->toMatch('/new Date\(\)\.toISOString\(\)/');
});

test('the general Book Now landing keeps its search form open to be filled in', function () {
    $html = test()->get(route('booking.search'))->assertOk()->getContent();

    expect($html)
        ->toContain('class="fh-search-form"')
        ->not->toMatch('/<button[^>]*\sdisabled[^>]*>[^<]*Search/s');
});

/*
|--------------------------------------------------------------------------
| Once the customer is asked, they are answered
|--------------------------------------------------------------------------
*/

test('a booking form opened with dates reports that period as engaged', function () {
    $html = initialBookingHtml('hotel');
    expect($html)->toContain('data-dates-attempted="false"');

    $withDates = test()
        ->get(route('bookings.create', [
            'type' => 'hotel',
            'slug' => Hotel::query()->where('is_active', true)->firstOrFail()->slug,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-05',
        ]))
        ->assertOk()
        ->getContent();

    expect($withDates)->toContain('data-dates-attempted="true"');
});

test('a period the rules refuse is reported and Review is refused', function () {
    // A check-out before the check-in is a period the rules reject, so the page has
    // to explain itself rather than offer a review of dates that cannot be booked.
    $html = test()
        ->get(route('bookings.create', [
            'type' => 'hotel',
            'slug' => Hotel::query()->where('is_active', true)->firstOrFail()->slug,
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-02',
        ]))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('These dates cannot be booked yet')
        ->toContain('Check-out date must be after the check-in date.')
        ->toMatch('/id="reviewBookingButton"[^>]*\sdisabled/s');
});

test('the server still refuses a booking submitted without an end date', function () {
    // Presentation was relaxed; the submission path was not. This is the proof that
    // a clean opening page is not a permissive one.
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    $this->actingAs($user)
        ->post(route('bookings.store'), [
            'booking_type' => 'hotel',
            'service_id' => $hotel->id,
            'start_date' => '2026-10-10',
            'end_date' => null,
            'travelers' => 2,
            'name' => 'A Customer',
            'email' => 'customer@example.com',
            'phone' => '9800000000',
            'policy_accepted' => '1',
        ])
        ->assertSessionHasErrors('booking');

    expect(collect(session('errors')->get('booking'))->first())
        ->toContain('end (check-out / return) date is required');
});

/*
|--------------------------------------------------------------------------
| The script asks only when pressed
|--------------------------------------------------------------------------
*/

test('the script quotes nothing until there are dates to quote', function () {
    $script = file_get_contents(resource_path('js/script.js'));

    // Without this, the opening scheduleQuote asks the server about a period the
    // customer has not chosen and paints the refusal onto the page.
    expect($script)
        ->toContain('if (visibleStart && visibleStart.value) {')
        ->not->toContain('        scheduleQuote();'."\n".'    });'."\n".'})();');
});

test('the opening search pass validates without publishing', function () {
    $script = file_get_contents(resource_path('js/script.js'));

    // The floor still has to be set before anyone reads a day, but an empty form
    // has not been answered wrongly and must not be told it has.
    expect($script)->toContain('revalidate(false);');
});

test('Review is the press that asks for the period', function () {
    $script = file_get_contents(resource_path('js/script.js'));

    expect($script)
        ->toContain('reviewButton.addEventListener("click"')
        // Refused, so the popup cannot open over dates that cannot be booked.
        ->toContain('event.preventDefault();')
        ->toContain('firstUnanswered.focus();');
});
