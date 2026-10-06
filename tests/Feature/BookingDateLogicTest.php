<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Services\BookingRulesService;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;

/*
|--------------------------------------------------------------------------
| Booking date logic
|--------------------------------------------------------------------------
|
| The public search page asks for a period per booking type and the exact-service
| confirmation page asks for the same period again, next to the service that was
| chosen. Both run the same client-side rule and the same server-side rule, so
| these cover the markup both pages share, the script that keeps the two date
| fields usable, and the validation the server keeps regardless.
|
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
 * The JavaScript the browser actually loads. The booking date logic lives in
 * resources/js/script.js and is copied to public/script.js, so assertions run
 * against the served copy - that is the one that can go stale.
 */
function dateScript(): string
{
    $script = file_get_contents(public_path('script.js'));

    expect($script)->toBe(file_get_contents(resource_path('js/script.js')));

    return $script;
}

function searchPageHtml(string $query = ''): string
{
    return test()->get('/book'.$query)->assertOk()->getContent();
}

function confirmPageHtml(string $type, string $query = ''): string
{
    $slug = match ($type) {
        'tour' => Tour::query()->where('is_active', true)->firstOrFail()->slug,
        'vehicle' => TransportVehicle::query()->where('availability', true)->firstOrFail()->slug,
        default => Hotel::query()->where('is_active', true)->firstOrFail()->slug,
    };

    return test()->get('/bookings/create?type='.$type.'&slug='.$slug.$query)->assertOk()->getContent();
}

/**
 * The period controls of one booking form, as the browser sees them:
 * ['start' => element, 'end' => element|null].
 *
 * @return array<string, DOMElement|null>
 */
function periodInputsFor(string $html, string $bookingType): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);

    $form = (new DOMXPath($dom))
        ->query("//form[.//input[@name='type'][@value='{$bookingType}']]")
        ->item(0);

    expect($form)->not->toBeNull("the {$bookingType} search form is missing");

    $inputs = [
        'start' => (new DOMXPath($dom))->query('.//input[@data-period-start]', $form)->item(0),
        'end' => (new DOMXPath($dom))->query('.//input[@data-period-end]', $form)->item(0),
    ];

    return $inputs;
}

/**
 * The ids of every control inside a panel that would actually block a submit.
 *
 * A required control is only inert when it is also disabled: constraint
 * validation is barred for disabled controls. A required control that is merely
 * hidden is the bug this file guards against.
 *
 * @return array<int, string>
 */
function blockingControlIds(string $html, string $panelId): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);

    $panel = (new DOMXPath($dom))->query("//*[@id='{$panelId}']")->item(0);

    $ids = [];

    foreach ((new DOMXPath($dom))->query('.//*[@required]', $panel) as $element) {
        if ($element->hasAttribute('disabled')) {
            continue;
        }

        $ids[] = $element->getAttribute('id') ?: $element->getAttribute('name');
    }

    return $ids;
}

function vehiclePayload(array $overrides = []): array
{
    return array_merge([
        'booking_type' => 'vehicle',
        'service_id' => TransportVehicle::query()->where('availability', true)->firstOrFail()->id,
        'name' => 'Ux Traveller',
        'email' => 'ux@example.com',
        'phone' => '9800000000',
        'travelers' => 2,
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-15',
        'policy_accepted' => '1',
    ], $overrides);
}

/*
|--------------------------------------------------------------------------
| The period controls each booking type actually needs
|--------------------------------------------------------------------------
*/

test('the hotel search asks for a check-in and a check-out date', function () {
    $inputs = periodInputsFor(searchPageHtml('?type=hotel'), 'hotel');

    expect($inputs['start'])->not->toBeNull()
        ->and($inputs['end'])->not->toBeNull();
});

test('the rental search asks for a pick-up and a drop-off date', function () {
    $inputs = periodInputsFor(searchPageHtml('?type=vehicle'), 'vehicle');

    expect($inputs['start'])->not->toBeNull()
        ->and($inputs['end'])->not->toBeNull();
});

test('the tour search asks only for a travel date', function () {
    // A tour runs for a length of its own rather than ending when the customer
    // leaves, so a drop-off field next to the travel date would be meaningless.
    $inputs = periodInputsFor(searchPageHtml('?type=tour'), 'tour');

    expect($inputs['start'])->not->toBeNull()
        ->and($inputs['end'])->toBeNull();
});

test('every period control reports its problem next to the field it belongs to', function (string $type) {
    $html = searchPageHtml('?type='.$type);

    foreach (['start_date', 'end_date'] as $name) {
        expect($html)->toContain('data-period-error="'.$name.'"', false);
    }
})->with(['hotel', 'vehicle', 'tour']);

test('a required control is never rendered inside a hidden search panel', function (string $type, string $panelId, array $hiddenPanels) {
    $html = searchPageHtml('?type='.$type);

    // A required control inside a display:none panel is not focusable. The
    // browser then refuses to submit the form and reports the error on a field
    // the customer cannot see or reach, so filling in the visible fields and
    // pressing Search silently does nothing.
    foreach ($hiddenPanels as $hiddenPanelId) {
        expect(blockingControlIds($html, $hiddenPanelId))
            ->toBe([], "{$hiddenPanelId} is hidden on ?type={$type} but still renders controls that would block the submit");
    }
})->with([
    'hotel tab active' => ['hotel', 'fh-hotelForm', ['fh-carForm', 'fh-tourForm']],
    'vehicle tab active' => ['vehicle', 'fh-carForm', ['fh-hotelForm', 'fh-tourForm']],
    'tour tab active' => ['tour', 'fh-tourForm', ['fh-hotelForm', 'fh-carForm']],
]);

test('a pick-up date cannot be set in the past by the picker', function (string $type) {
    $inputs = periodInputsFor(searchPageHtml('?type='.$type), $type);

    expect($inputs['start']->getAttribute('min'))->toBe(now()->toDateString());
})->with(['hotel', 'vehicle', 'tour']);

test('the drop-off picker is never disabled, readonly or clamped to the pick-up', function (string $type) {
    $inputs = periodInputsFor(searchPageHtml('?type='.$type), $type);

    if (! $inputs['end']) {
        expect(true)->toBeTrue();

        return;
    }

    expect($inputs['end']->hasAttribute('disabled'))->toBeFalse()
        ->and($inputs['end']->hasAttribute('readonly'))->toBeFalse();
})->with(['hotel', 'vehicle']);

/*
|--------------------------------------------------------------------------
| The confirmation page repeats the period next to the chosen service
|--------------------------------------------------------------------------
*/

test('the confirmation page shows the period it was given and mirrors it into the posted fields', function (string $type) {
    $html = confirmPageHtml($type, '&start_date=2026-10-10&end_date=2026-10-20&travelers=2');

    expect($html)->toMatch('/id="start_date_field"[^>]*value="2026-10-10"/s')
        ->and($html)->toMatch('/id="end_date_field"[^>]*value="2026-10-20"/s')
        ->and($html)->toMatch('/id="party_field"[^>]*value="2"/s');

    // The hidden fields are what actually post, so they have to agree with the
    // visible pickers before the customer touches anything.
    expect($html)->toMatch('/name="start_date" id="start_date"[^>]*value="2026-10-10"/s')
        ->and($html)->toMatch('/name="end_date" id="end_date"[^>]*value="2026-10-20"/s')
        ->and($html)->toMatch('/name="travelers" id="travelers"[^>]*value="2"/s');
})->with(['hotel', 'vehicle']);

test('a tour confirmation page does not require an end date', function () {
    $html = confirmPageHtml('tour', '&start_date=2026-10-10');

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);

    $end = (new DOMXPath($dom))->query("//input[@id='end_date_field']")->item(0);

    expect($end)->not->toBeNull()
        // A tour's length is its own attribute, so the customer is never blocked
        // by an end date they are not expected to know in advance.
        ->and($end->hasAttribute('required'))->toBeFalse();
});

test('a stay or a rental requires the end date it is quoted against', function (string $type) {
    $html = confirmPageHtml($type);

    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);

    expect((new DOMXPath($dom))->query("//input[@id='end_date_field']")->item(0)->hasAttribute('required'))
        ->toBeTrue();
})->with(['hotel', 'vehicle']);

test('a refreshed search page renders the searched pick-up and drop-off dates back', function () {
    $inputs = periodInputsFor(searchPageHtml('?type=vehicle&start_date=2026-10-10&end_date=2026-10-20'), 'vehicle');

    expect($inputs['start']->getAttribute('value'))->toBe('2026-10-10')
        ->and($inputs['end']->getAttribute('value'))->toBe('2026-10-20');
});

test('an unsearched page loads with empty, editable dates', function () {
    $inputs = periodInputsFor(searchPageHtml('?type=hotel'), 'hotel');

    expect($inputs['start']->getAttribute('value'))->toBe('')
        ->and($inputs['end']->getAttribute('value'))->toBe('');
});

/*
|--------------------------------------------------------------------------
| The script keeps both dates usable
|--------------------------------------------------------------------------
*/

test('the drop-off value is kept and the conflict is reported instead', function () {
    $script = dateScript();

    // Wiping the drop-off field loses an answer the customer did not mean to
    // lose and reads as a field stuck on the pick-up date, so the value stays
    // and the problem is shown next to the field. The sentence shown there is
    // the one the server would use, handed in by the page, so the field and the
    // submission that follows cannot disagree about what is wrong.
    expect($script)->toContain('function validatePeriod(scope, startInput, endInput, endDateRequired, options)')
        ->and($script)->not->toMatch('/end\w*\.value\s*=\s*""/')
        ->and($script)->not->toMatch('/end\w*\.disabled\s*=/')
        ->and($script)->not->toMatch('/end\w*\.readOnly\s*=/')
        ->and($script)->not->toMatch('/end\.value\s*=\s*start/')
        ->and($script)->toContain('(settings.orderError || "")')
        ->and($script)->not->toContain('The end date has to be after the start date.')
        ->and($script)->not->toContain('Fix the dates above before confirming');
});

test('the drop-off floor follows the pick-up date and falls back to today', function () {
    $script = dateScript();

    expect($script)->toContain('const floor = startInput && startInput.value ? dayAfter(startInput.value) : "";')
        ->and($script)->toContain('endInput.min = floor && floor > todayFloor ? floor : todayFloor;')
        ->and($script)->toContain('endInput.dataset.todayFloor = endInput.min || "";');
});

test('each page hands the script the end-date rule worded exactly as the server words it', function () {
    // The field beside the date and the submission that follows have to be the
    // same sentence, so the wording is written once, by the rules service, and
    // travelled into the page rather than being phrased again in the markup.
    $rules = app(BookingRulesService::class);

    $pages = [
        'hotel' => Hotel::query()->where('is_active', true)->firstOrFail()->slug,
        'vehicle' => TransportVehicle::query()->where('is_active', true)->where('availability', true)->firstOrFail()->slug,
        'tour' => Tour::query()->where('is_active', true)->firstOrFail()->slug,
    ];

    foreach ($pages as $type => $slug) {
        $expected = $rules->endDateOrderMessage($type);

        $html = test()
            ->get(route('bookings.create', ['type' => $type, 'slug' => $slug]))
            ->assertOk()
            ->getContent();

        expect($html)
            ->toContain('data-end-date-order-error="'.$expected.'"');
    }

    // The general landing asks for a period on all three tabs, so each one is
    // labelled with its own rule rather than a single shared sentence.
    $search = test()->get(route('booking.search'))->assertOk()->getContent();

    foreach (['hotel', 'vehicle', 'tour'] as $type) {
        expect($search)
            ->toContain('data-booking-type="'.$type.'"')
            ->toContain('data-end-date-order-error="'.$rules->endDateOrderMessage($type).'"');
    }
});

test('the server still refuses a period whose end is not after its start', function () {
    // The wording moved into the page; the refusal did not move with it.
    $rules = app(BookingRulesService::class);
    $tour = Tour::query()->where('is_active', true)->firstOrFail();

    $result = $rules->validate('tour', $tour, '2026-11-10', '2026-11-09', 2);

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->toContain($rules->endDateOrderMessage('tour'));
});

test('the floor is the day after the pick-up, matching the server rule', function () {
    // BookingRulesService rejects end <= start, so a picker offering the pick-up
    // date itself would let the customer choose something the server refuses.
    $script = dateScript();

    expect($script)->toContain('const dayAfter = (isoDate) => (isoDate ? shiftIsoDate(isoDate, 1) : "");')
        ->and($script)->toContain('endInput.value && floor && endInput.value < floor');
});

test('the drop-off field is required for a stay and a rental but not for a tour', function () {
    // The requirement is derived from the booking type rather than repeated per
    // panel, so it cannot drift between the three tabs.
    $script = dateScript();

    expect($script)->toContain('const endDateRequired = panel ? panel.getAttribute("data-booking-type") !== "tour" : true;')
        ->and($script)->toContain('const endDateRequired = typeInput ? typeInput.value !== "tour" : true;');
});

test('the visible pickers are mirrored into the canonical fields the server reads', function () {
    $script = dateScript();

    expect($script)->toContain('function mirrorPeriod()')
        ->and($script)->toContain('canonicalStart.value = visibleStart.value;')
        ->and($script)->toContain('canonicalEnd.value = visibleEnd.value;')
        ->and($script)->toContain('canonical.value = input.value;');
});

test('a rental length is a shortcut for the drop-off date it describes', function () {
    $script = dateScript();

    expect($script)->toContain('function syncRentalDuration(scope)')
        ->and($script)->toContain('function applyRentalDuration(scope)')
        ->and($script)->toContain('endInput.value = shiftIsoDate(startInput.value, Number(select.value));');
});

test('an unusable period is stopped in the browser instead of being submitted', function () {
    $script = dateScript();

    expect($script)->toContain('form.addEventListener("submit", function (event) {')
        ->and($script)->toContain('confirmForm.addEventListener("submit", function (event) {');
});

test('the confirmation page re-prices through the quote endpoint and blocks an unconfirmed submit', function () {
    $script = dateScript();

    expect($script)->toContain('await fetch(confirmForm.getAttribute("data-quote-url"), {')
        ->and($script)->toContain('!data.available || !data.quote')
        ->and($script)->toContain('setSubmitState(false, reason)')
        ->and($script)->toContain('setSubmitState(true, "");');
});

/*
|--------------------------------------------------------------------------
| Backend validation
|--------------------------------------------------------------------------
*/

test('the server rejects a drop-off on or before the pick-up', function (string $end) {
    $user = User::factory()->create();

    test()->actingAs($user)
        ->post('/bookings', vehiclePayload(['start_date' => '2026-10-10', 'end_date' => $end]))
        ->assertSessionHasErrors('booking');

    expect(Booking::query()->count())->toBe(0);
})->with([
    'same day' => ['2026-10-10'],
    'day before' => ['2026-10-09'],
]);

test('the server rejects a missing pick-up or drop-off date for a vehicle', function (string $missing, string $errorKey) {
    $user = User::factory()->create();
    $payload = vehiclePayload();

    if ($missing === 'start_date') {
        $payload['start_date'] = '';
    } else {
        $payload['end_date'] = '';
    }

    test()->actingAs($user)
        ->post('/bookings', $payload)
        ->assertSessionHasErrors($errorKey);

    expect(Booking::query()->count())->toBe(0);
})->with([
    // start_date is required by the request rules; end_date is nullable there
    // and the business rules turn its absence into a booking error.
    ['start_date', 'start_date'],
    ['end_date', 'booking'],
]);

test('the server rejects a pick-up date in the past', function () {
    $user = User::factory()->create();

    test()->actingAs($user)
        ->post('/bookings', vehiclePayload([
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ]))
        ->assertSessionHasErrors('booking');

    expect(Booking::query()->count())->toBe(0);
});

test('the search itself refuses a period the booking rules would refuse', function (string $end, string $errorKey) {
    $this->get('/book?type=vehicle&start_date=2026-10-10&end_date='.$end)
        ->assertSessionHasErrors($errorKey);
})->with([
    'same day' => ['2026-10-10', 'end_date'],
    'day before' => ['2026-10-09', 'end_date'],
]);

test('the search accepts a valid pick-up and drop-off range', function () {
    $this->get('/book?type=vehicle&start_date=2026-10-10&end_date=2026-10-20')
        ->assertOk()
        ->assertSessionHasNoErrors();
});

test('the server accepts a valid pick-up and drop-off range', function () {
    $user = User::factory()->create();

    test()->actingAs($user)
        ->post('/bookings', vehiclePayload(['start_date' => '2026-10-10', 'end_date' => '2026-10-20']))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $booking = Booking::query()->firstOrFail();

    expect($booking->start_date->toDateString())->toBe('2026-10-10')
        ->and($booking->end_date->toDateString())->toBe('2026-10-20');
});

/*
|--------------------------------------------------------------------------
| The dates drive the existing calculation
|--------------------------------------------------------------------------
*/

test('a longer range costs more at the existing per-day rate', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('availability', true)->firstOrFail();

    // The same vehicle and the same travellers, so the number of days is the only
    // thing that differs between the two quotes.
    $quote = fn (string $end) => test()->actingAs($user)
        ->postJson('/bookings/quote', [
            'booking_type' => 'vehicle',
            'service_id' => $vehicle->id,
            'start_date' => '2026-10-10',
            'end_date' => $end,
            'travelers' => 2,
        ])
        ->assertOk()
        ->json('quote');

    $five = $quote('2026-10-15');
    $ten = $quote('2026-10-20');

    // 2026-10-10 -> 2026-10-15 is 5 days and -> 2026-10-20 is 10 days, priced by
    // the existing PriceCalculator. No new pricing rule is introduced here.
    expect($five['quantity'])->toBe(5)
        ->and($ten['quantity'])->toBe(10)
        ->and($five['subtotal'])->toBeLessThan($ten['subtotal'])
        ->and($ten['total'])->toBeGreaterThan($five['total']);
});

test('a booked range is stored with the dates that were paid for', function () {
    $user = User::factory()->create();
    $vehicles = TransportVehicle::query()->where('availability', true)->take(2)->get();
    expect($vehicles)->toHaveCount(2);

    test()->actingAs($user)
        ->post('/bookings', vehiclePayload([
            'service_id' => $vehicles[0]->id,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-15',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    test()->actingAs($user)
        ->post('/bookings', vehiclePayload([
            'service_id' => $vehicles[1]->id,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-20',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $short = Booking::query()->where('service_id', $vehicles[0]->id)->firstOrFail();
    $long = Booking::query()->where('service_id', $vehicles[1]->id)->firstOrFail();

    expect($short->start_date->toDateString())->toBe('2026-10-10')
        ->and($short->end_date->toDateString())->toBe('2026-10-15')
        ->and($long->start_date->toDateString())->toBe('2026-10-10')
        ->and($long->end_date->toDateString())->toBe('2026-10-20')
        ->and($long->total_amount)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| The hotel path uses the same rule
|--------------------------------------------------------------------------
*/

test('a hotel booking follows the same pick-up and drop-off rule', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    $payload = [
        'booking_type' => 'hotel',
        'service_id' => $hotel->id,
        'name' => 'Ux Traveller',
        'email' => 'ux@example.com',
        'phone' => '9800000000',
        'travelers' => 2,
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-15',
        'policy_accepted' => '1',
    ];

    test()->actingAs($user)->post('/bookings', $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(Booking::query()->firstOrFail()->service_id)->toBe($hotel->id);

    test()->actingAs($user)
        ->post('/bookings', ['end_date' => '2026-10-10'] + $payload)
        ->assertSessionHasErrors('booking');

    expect(Booking::query()->count())->toBe(1);
});
