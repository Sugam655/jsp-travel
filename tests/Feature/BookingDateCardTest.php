<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Models\Booking;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;

/*
|--------------------------------------------------------------------------
| Booking date cards
|--------------------------------------------------------------------------
|
| A date field is presented the way a traveller reads one: a small uppercase
| label over a formatted day, "Sun, 22 Mar", with the whole card opening the
| browser's own calendar. Underneath that presentation the control has to stay a
| real <input type="date">, because the search, the live quote, the popup and
| bookings.store all read the ISO value it holds. These cover the presentation,
| the label per booking type, and that the real date still reaches the server.
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
 * The JavaScript the browser actually loads. The date cards live in
 * resources/js/script.js and are copied to public/script.js, so assertions run
 * against the served copy - that is the one that can go stale.
 */
function dateCardScript(): string
{
    $script = file_get_contents(public_path('script.js'));

    expect($script)->toBe(file_get_contents(resource_path('js/script.js')));

    return $script;
}

/**
 * The stylesheet the browser actually loads, mirrored the same way as the script.
 */
function dateCardStyles(): string
{
    $css = file_get_contents(public_path('style.css'));

    expect($css)->toBe(file_get_contents(resource_path('css/style.css')));

    return $css;
}

function dateCardSearchHtml(string $query = ''): string
{
    return test()->get('/book'.$query)->assertOk()->getContent();
}

function dateCardConfirmHtml(string $type, string $query = ''): string
{
    $slug = match ($type) {
        'tour' => Tour::query()->where('is_active', true)->firstOrFail()->slug,
        'vehicle' => TransportVehicle::query()->where('availability', true)->firstOrFail()->slug,
        default => Hotel::query()->where('is_active', true)->firstOrFail()->slug,
    };

    return test()->get('/bookings/create?type='.$type.'&slug='.$slug.$query)->assertOk()->getContent();
}

/**
 * Every date card on a page, as the browser sees them.
 *
 * @return array<int, array{input: DOMElement, label: string, display: string, fieldId: string}>
 */
function dateCards(string $html): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);

    $cards = [];

    foreach ((new DOMXPath($dom))->query('//*[@data-date-field]') as $field) {
        $input = (new DOMXPath($dom))->query('.//input[@type="date"]', $field)->item(0);
        $label = (new DOMXPath($dom))->query('.//label', $field)->item(0);
        $display = (new DOMXPath($dom))->query('.//*[@data-date-display]', $field)->item(0);

        expect($input)->not->toBeNull('a date card is missing its real date input')
            ->and($label)->not->toBeNull('a date card is missing its label')
            ->and($display)->not->toBeNull('a date card is missing its formatted day');

        $cards[] = [
            'input' => $input,
            'fieldId' => $input->getAttribute('id'),
            'label' => trim($label->textContent),
            'display' => trim($display->textContent),
        ];
    }

    return $cards;
}

/**
 * The date cards of one search panel, keyed by the field they belong to.
 *
 * @return array<string, array<string, string>>
 */
function dateCardsFor(string $html, string $bookingType): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);

    $form = (new DOMXPath($dom))
        ->query("//form[.//input[@name='type'][@value='{$bookingType}']]")
        ->item(0);

    expect($form)->not->toBeNull("the {$bookingType} search form is missing");

    $cards = [];

    foreach ((new DOMXPath($dom))->query('.//*[@data-date-field]', $form) as $field) {
        $label = (new DOMXPath($dom))->query('.//label', $field)->item(0);
        $display = (new DOMXPath($dom))->query('.//*[@data-date-display]', $field)->item(0);
        $input = (new DOMXPath($dom))->query('.//input[@type="date"]', $field)->item(0);

        $cards[$input->getAttribute('id')] = [
            'label' => trim($label->textContent),
            'display' => trim($display->textContent),
            'value' => $input->getAttribute('value'),
        ];
    }

    return $cards;
}

/*
|--------------------------------------------------------------------------
| A date reads as a travel-booking card
|--------------------------------------------------------------------------
*/

test('every date field is a card with a label and a real date input inside it', function () {
    $cards = dateCards(dateCardSearchHtml('?type=vehicle&start_date=2026-03-22&end_date=2026-04-02'));

    expect($cards)->toHaveCount(2);

    foreach ($cards as $card) {
        expect($card['input']->getAttribute('type'))->toBe('date')
            // A card is only worth having if the calendar is still the browser's
            // own and the value is still a date the server can validate.
            ->and($card['label'])->not->toBe('')
            ->and($card['display'])->not->toBe('');
    }
});

test('a searched date is shown as a formatted day rather than as a raw date', function () {
    $cards = dateCardsFor(dateCardSearchHtml('?type=vehicle&start_date=2026-03-22&end_date=2026-04-02'), 'vehicle');

    // 22 March 2026 is a Sunday and 2 April 2026 a Thursday. The card shows the
    // day and the month a traveller reads, and nothing of the raw 2026-03-22.
    expect($cards['searchVehicleStart']['display'])->toBe('Sun, 22 Mar')
        ->and($cards['searchVehicleEnd']['display'])->toBe('Thu, 02 Apr')
        ->and($cards['searchVehicleStart']['value'])->toBe('2026-03-22')
        ->and($cards['searchVehicleEnd']['value'])->toBe('2026-04-02');
});

test('the raw ISO date is never the only thing a date field shows', function (string $type) {
    $html = dateCardSearchHtml('?type='.$type.'&start_date=2026-03-22&end_date=2026-04-02');

    foreach (dateCards($html) as $card) {
        expect($card['display'])->not->toMatch('/\d{4}-\d{2}-\d{2}/')
            ->and($card['display'])->not->toMatch('/\d{1,2}\/\d{1,2}\/\d{2,4}/');
    }
})->with(['hotel', 'vehicle', 'tour']);

test('an unselected date shows a placeholder rather than an empty box', function () {
    $cards = dateCardsFor(dateCardSearchHtml('?type=hotel'), 'hotel');

    foreach ($cards as $card) {
        expect($card['display'])->toBe('Select date')
            ->and($card['value'])->toBe('');
    }
});

test('the confirmation page renders the period it was given as formatted days', function () {
    $cards = dateCards(dateCardConfirmHtml('vehicle', '&start_date=2026-03-22&end_date=2026-04-02'));

    expect($cards)->toHaveCount(2)
        ->and($cards[0]['display'])->toBe('Sun, 22 Mar')
        ->and($cards[1]['display'])->toBe('Thu, 02 Apr');
});

test('a tour confirmation page shows its one date as a formatted day', function () {
    $cards = dateCards(dateCardConfirmHtml('tour', '&start_date=2026-03-22'));

    expect($cards)->toHaveCount(1)
        ->and($cards[0]['display'])->toBe('Sun, 22 Mar');
});

/*
|--------------------------------------------------------------------------
| The label a traveller expects per booking type
|--------------------------------------------------------------------------
*/

test('a stay is checked into and out of', function () {
    $cards = dateCardsFor(dateCardSearchHtml('?type=hotel'), 'hotel');

    expect($cards['searchHotelStart']['label'])->toBe('CHECK IN')
        ->and($cards['searchHotelEnd']['label'])->toBe('CHECK OUT');
});

test('a rental is departed from and returned to', function () {
    $html = dateCardSearchHtml('?type=vehicle');
    $cards = dateCardsFor($html, 'vehicle');

    expect($cards['searchVehicleStart']['label'])->toBe('DEPARTURE')
        ->and($cards['searchVehicleEnd']['label'])->toBe('RETURN')
        // The rental wording belongs to the quote and to the confirmation popup,
        // but the field a customer picks a date in reads as a trip.
        ->and($html)->not->toContain('PICK-UP DATE', false)
        ->not->toContain('DROP-OFF DATE', false);
});

test('a tour departs, and the confirmation page never offers a second date', function () {
    $html = dateCardSearchHtml('?type=tour');

    expect(dateCardsFor($html, 'tour')['searchTourStart']['label'])->toBe('DEPARTURE')
        ->and(dateCards(dateCardConfirmHtml('tour'))[0]['label'])->toBe('DEPARTURE')
        ->and(dateCardsFor($html, 'tour'))->not->toHaveKey('searchTourEnd');
});

test('the confirmation page labels the period the same way the search page does', function (string $type, string $start, string $end) {
    $labels = array_column(dateCards(dateCardConfirmHtml($type, '&start_date=2026-03-22&end_date=2026-04-02')), 'label');

    expect($labels)->toBe([$start, $end]);
})->with([
    'hotel' => ['hotel', 'CHECK IN', 'CHECK OUT'],
    'vehicle' => ['vehicle', 'DEPARTURE', 'RETURN'],
    'tour' => ['tour', 'DEPARTURE', null],
]);

test('the date card label is the small uppercase label a traveller reads', function () {
    $styles = dateCardStyles();

    expect($styles)->toContain('.fh-date-label')
        ->toMatch('/\.fh-date-label\s*\{[^}]*text-transform:\s*uppercase/s')
        ->toMatch('/\.fh-date-label\s*\{[^}]*font-size:\s*0\.7rem/s');
});

/*
|--------------------------------------------------------------------------
| The whole card opens the browser's own calendar
|--------------------------------------------------------------------------
*/

test('the real date input is stretched over the whole card, so the label opens the calendar too', function () {
    $styles = dateCardStyles();

    // The label, the formatted day and the calendar glyph all sit inside the card
    // the input covers, so one click anywhere on it reaches the input itself. A
    // customer should never have to aim for a small icon.
    expect($styles)->toMatch('/\.fh-date-field\s*\{[^}]*position:\s*relative/s')
        ->toMatch('/\.fh-date-input\s*\{[^}]*position:\s*absolute/s')
        ->toMatch('/\.fh-date-input\s*\{[^}]*top:\s*0/s')
        ->toMatch('/\.fh-date-input\s*\{[^}]*left:\s*0/s')
        ->toMatch('/\.fh-date-input\s*\{[^}]*width:\s*100%/s')
        ->toMatch('/\.fh-date-input\s*\{[^}]*height:\s*100%/s')
        ->toMatch('/\.fh-date-input\s*\{[^}]*cursor:\s*pointer/s')
        // Transparent rather than display:none or visibility:hidden, which would
        // take the control out of the page and stop the calendar opening at all.
        ->toMatch('/\.fh-date-input\s*\{[^}]*opacity:\s*0/s')
        ->and($styles)->not->toMatch('/\.fh-date-input\s*\{[^}]*(display:\s*none|visibility:\s*hidden)/s');
});

test('the script asks the browser for its own calendar and keeps the day in step', function () {
    $script = dateCardScript();

    expect($script)->toContain('function openNativeDatePicker(input)')
        // The real input opens the real picker: no hand-built calendar, no static
        // text pretending to be a date.
        ->toContain('input.showPicker();')
        ->toContain('if (typeof input.showPicker !== "function") {')
        // Focus first, because a click that does not focus is not the user gesture
        // showPicker() requires.
        ->toContain('input.focus();')
        ->toContain('dateInputs.forEach(enhanceDateCard);')
        ->toContain('card.addEventListener("click", function () {')
        ->toContain('input.addEventListener("input", paint);')
        ->and($script)->not->toContain('flatpickr')
        ->not->toContain('pickadate');
});

test('the formatted day is derived from the chosen date, never hard-coded', function () {
    $script = dateCardScript();

    expect($script)->toContain('function formatDateForDisplay(isoDate)')
        ->toContain('Date.UTC(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]))')
        // UTC for the same reason the period arithmetic is: a local-time Date would
        // print the wrong day, or the wrong weekday, either side of UTC.
        ->toContain('timeZone: "UTC"')
        ->toContain('[piece("weekday"), piece("day"), piece("month")].filter(Boolean).join(", ")')
        ->toContain('display.textContent = formatted || display.dataset.datePlaceholder || DATE_DISPLAY_PLACEHOLDER;')
        ->and($script)->not->toMatch('/"(Sun|Mon|Tue|Wed|Thu|Fri|Sat), \d{2} [A-Z][a-z]{2}"/');
});

test('a date the rental-length shortcut writes still repaints the card', function () {
    $script = dateCardScript();

    // The end date can be set by choosing a rental length rather than by opening
    // the calendar, and a value assigned in script fires no event of its own, so
    // the card would otherwise keep showing the previous day.
    expect($script)->toContain('function announceDateChange(input)')
        ->toContain('input.dispatchEvent(new Event("change", { bubbles: true }));')
        ->toMatch('/endInput\.value = shiftIsoDate\(startInput\.value, Number\(select\.value\)\);\s*\n\s*announceDateChange\(endInput\);/');
});

/*
|--------------------------------------------------------------------------
| The real date still drives everything behind the card
|--------------------------------------------------------------------------
*/

test('a search submitted through a date card still receives the real dates', function () {
    $html = dateCardSearchHtml('?type=vehicle&start_date=2026-03-22&end_date=2026-04-02');

    // The card is a presentation of the control, never a replacement for it, so
    // the control keeps the name the controller validates.
    expect($html)->toMatch('/<input[^>]*type="date"[^>]*name="start_date"[^>]*id="searchVehicleStart"/s')
        ->and($html)->toMatch('/<input[^>]*type="date"[^>]*name="end_date"[^>]*id="searchVehicleEnd"/s');

    $this->get('/book?type=vehicle&start_date=2026-03-22&end_date=2026-04-02')
        ->assertOk()
        ->assertSessionHasNoErrors();
});

test('search results still come back for dates picked through a card', function () {
    $this->get('/book?type=hotel&start_date=2026-03-22&end_date=2026-04-02')
        ->assertOk()
        ->assertSee('id="fh-resultsSection"', false);
});

test('a booking created from the card dates stores those exact dates', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('availability', true)->firstOrFail();

    test()->actingAs($user)->post('/bookings', [
        'booking_type' => 'vehicle',
        'service_id' => $vehicle->id,
        'name' => 'Ux Traveller',
        'email' => 'ux@example.com',
        'phone' => '9800000000',
        'travelers' => 2,
        'start_date' => '2026-03-22',
        'end_date' => '2026-04-02',
        'policy_accepted' => '1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $booking = Booking::query()->firstOrFail();

    expect($booking->start_date->toDateString())->toBe('2026-03-22')
        ->and($booking->end_date->toDateString())->toBe('2026-04-02');
});

test('the confirmation page keeps one date card per period and no duplicate fields', function () {
    $cards = dateCards(dateCardConfirmHtml('vehicle'));

    expect($cards)->toHaveCount(2)
        ->and(array_column($cards, 'fieldId'))->toBe(['start_date_field', 'end_date_field']);

    // The visible controls are cards; the canonical values are hidden. Exactly one
    // of each, so a second booking UI or a second copy of a date cannot creep in.
    expect(substr_count(dateCardConfirmHtml('vehicle'), 'name="start_date"'))->toBe(1)
        ->and(substr_count(dateCardConfirmHtml('vehicle'), 'name="end_date"'))->toBe(1);
});

test('the card date input stays an editable real date control', function () {
    // A card that disabled, read-only or substituted its control would still look
    // right and would quietly stop being bookable.
    foreach (dateCards(dateCardConfirmHtml('hotel')) as $card) {
        expect($card['input']->hasAttribute('disabled'))->toBeFalse()
            ->and($card['input']->hasAttribute('readonly'))->toBeFalse()
            ->and($card['input']->getAttribute('class'))->not->toContain('fh-date-input--faux');
    }
});
