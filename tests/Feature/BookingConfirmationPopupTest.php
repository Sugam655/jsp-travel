<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingSetting;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;

/*
 * The confirmation popup on the exact-service booking page.
 *
 * The page itself only chooses dates and a party size; the price breakdown, the
 * customer details and the policy are the final review and live in one popup. These
 * tests read that popup out of the rendered page and submit what it would submit,
 * so the popup can never drift from the one booking form it belongs to.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Bookings', 'Tours', 'Hotels', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
    Artisan::call('module:seed', ['module' => 'Tours', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Hotels', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Transport', '--force' => true]);

    // A fixed currency and no tax or service charge, so the totals asserted below
    // are the ones written in the tests rather than whatever the settings tables
    // happen to hold.
    BookingSetting::set('currency', 'NPR');
    BookingSetting::set('tax_rate', 0);
    BookingSetting::set('service_charge_rate', 0);
});

/**
 * The markup of the one confirmation popup on a rendered booking page.
 *
 * The popup is the last thing inside #bookingForm, so the capture runs to the
 * form's own closing tag; the test below counts the forms, so there is only ever
 * one end to run to.
 */
function popupMarkup(string $html): string
{
    preg_match('/<div class="modal fade" id="fh-confirmBookingModal".*?<\/form>/s', $html, $matches);

    return $matches[0] ?? '';
}

/**
 * The booking page with the confirmation popup taken out of it: what the customer
 * actually reads before they choose to open the popup.
 */
function pageOutsidePopup(string $html): string
{
    return str_replace(popupMarkup($html), '', $html);
}

/**
 * A hotel priced at a known nightly rate, so the popup's arithmetic can be checked
 * against a number written in the test.
 */
function hotelPricedAt(float $nightly = 4200): Hotel
{
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();
    $hotel->update(['price' => $nightly]);

    return $hotel->fresh();
}

/**
 * A rentable vehicle priced at a known daily rate.
 */
function vehiclePricedAt(float $daily = 3000): TransportVehicle
{
    $vehicle = TransportVehicle::query()->where('is_active', true)->where('availability', true)->firstOrFail();
    $vehicle->update(['price' => $daily, 'price_unit' => 'per_day']);

    return $vehicle->fresh();
}

/**
 * A tour package priced at a known per-person rate.
 */
function tourPricedAt(float $perPerson = 5000): Tour
{
    $tour = Tour::query()->where('is_active', true)->firstOrFail();
    $tour->update(['price' => $perPerson, 'duration_days' => 2, 'duration' => '2 Days', 'capacity' => 20]);

    return $tour->fresh();
}

function hotelConfirmation(array $query = []): string
{
    return route('bookings.create', array_merge([
        'type' => 'hotel',
        'slug' => hotelPricedAt()->slug,
    ], $query));
}

function confirmationHtml(array $query = []): string
{
    return test()->get(hotelConfirmation($query))->assertOk()->getContent();
}

/*
|--------------------------------------------------------------------------
| The page stays clean
|--------------------------------------------------------------------------
*/

test('the booking page carries no price breakdown or customer details outside the confirmation popup', function () {
    $outside = pageOutsidePopup(confirmationHtml([
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'travelers' => 2,
    ]));

    // The page is the dates and the party size. Everything a customer reviews is
    // behind the popup, so the page cannot read as a half-finished booking.
    expect($outside)->not->toContain('Price breakdown')
        ->not->toContain('Your details')
        ->not->toContain('fh-quoteSection')
        ->not->toContain('fh-quoteTotal')
        ->not->toContain('name="name"')
        ->not->toContain('name="email"')
        ->not->toContain('name="phone"')
        ->not->toContain('name="policy_accepted"')
        ->not->toContain('id="bookingSubmit"')
        // The dates and the party are the page's whole job.
        ->toContain('id="party_field"', false)
        ->toContain('value="2026-11-02"', false);
});

test('the booking page opens the popup from a Review button instead of submitting', function () {
    $this->get(hotelConfirmation(['start_date' => '2026-11-02', 'end_date' => '2026-11-05']))
        ->assertOk()
        ->assertSee('id="reviewBookingButton"', false)
        ->assertSee('data-bs-target="#fh-confirmBookingModal"', false)
        // type="button", so the review never posts anything on its own.
        ->assertSee('type="button" class="fh-search-btn" id="reviewBookingButton"', false);
});

/*
|--------------------------------------------------------------------------
| What the popup states
|--------------------------------------------------------------------------
*/

test('the popup states a multi-night stay, its nightly rate and the total for the chosen dates', function () {
    $popup = popupMarkup(confirmationHtml([
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'travelers' => 2,
    ]));

    // Three nights at the hotel's own rate, priced by PriceCalculator rather than
    // by anything written into the view.
    expect($popup)->toContain('NPR 4,200.00 per night')
        ->toContain('3 night(s) × room rate')
        ->toContain('NPR 12,600.00')
        // The dates the price is for, read back rather than left on the page.
        ->toContain('Mon, 02 Nov')
        ->toContain('Thu, 05 Nov')
        // The service itself, named in the popup rather than only in the hero.
        ->toContain(hotelPricedAt()->title);
});

test('the popup states a one-night stay as one night', function () {
    $popup = popupMarkup(confirmationHtml([
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-03',
        'travelers' => 2,
    ]));

    expect($popup)->toContain('1 night(s) × room rate')
        ->toContain('NPR 4,200.00');
});

test('the popup states a car rental as its rental days times the daily rate', function () {
    $vehicle = vehiclePricedAt();

    $popup = popupMarkup($this->get(route('bookings.create', [
        'type' => 'vehicle',
        'slug' => $vehicle->slug,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-13',
        'travelers' => 2,
    ]))->assertOk()->getContent());

    expect($popup)->toContain('NPR 3,000.00 per day')
        ->toContain('3 day(s) × rental rate')
        ->toContain('NPR 9,000.00')
        ->toContain($vehicle->name);
});

test('the popup states a tour as its travellers times the per-person rate', function () {
    $tour = tourPricedAt();

    $popup = popupMarkup($this->get(route('bookings.create', [
        'type' => 'tour',
        'slug' => $tour->slug,
        'start_date' => '2026-11-20',
        'travelers' => 3,
    ]))->assertOk()->getContent());

    expect($popup)->toContain('NPR 5,000.00 per person')
        ->toContain('3 traveller(s) × per-person rate')
        ->toContain('NPR 15,000.00')
        ->toContain($tour->title);
});

test('the popup names the booking type the customer is making', function () {
    $hotel = popupMarkup(confirmationHtml(['start_date' => '2026-11-02', 'end_date' => '2026-11-05']));
    $vehicle = popupMarkup($this->get(route('bookings.create', [
        'type' => 'vehicle',
        'slug' => vehiclePricedAt()->slug,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-13',
    ]))->assertOk()->getContent());
    $tour = popupMarkup($this->get(route('bookings.create', [
        'type' => 'tour',
        'slug' => tourPricedAt()->slug,
        'start_date' => '2026-11-20',
    ]))->assertOk()->getContent());

    expect($hotel)->toContain('>Hotel</span>')
        ->and($vehicle)->toContain('>Vehicle</span>')
        ->and($tour)->toContain('>Tour</span>');
});

test('the popup total agrees with the quote the server gives for the same booking', function () {
    $html = confirmationHtml(['start_date' => '2026-11-02', 'end_date' => '2026-11-05', 'travelers' => 2]);

    // What the popup refreshes itself from when a date changes must say the same
    // thing the popup was rendered with, or a customer would review one price and
    // be charged another.
    $quote = $this->postJson(route('bookings.quote'), [
        'booking_type' => 'hotel',
        'service_id' => hotelPricedAt()->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'travelers' => 2,
    ])->assertOk()->json('quote');

    expect(popupMarkup($html))
        ->toContain(number_format((float) $quote['unit_price'], 2).' '.$quote['unit_label'])
        ->toContain('NPR '.number_format((float) $quote['total'], 2));
});

/*
|--------------------------------------------------------------------------
| The details and the policy
|--------------------------------------------------------------------------
*/

test('the popup collects every field the booking store validates', function () {
    $popup = popupMarkup(confirmationHtml(['start_date' => '2026-11-02', 'end_date' => '2026-11-05']));

    expect($popup)->toContain('name="name" id="name"', false)
        ->toContain('name="email" id="email"', false)
        ->toContain('name="phone" id="phone"', false)
        ->toContain('name="address" id="address"', false)
        ->toContain('name="message" id="message"', false)
        ->toContain('I have read and accept the booking and cancellation policy.')
        ->toContain('Back / Edit');
});

test('the popup pre-fills the signed-in customer profile', function () {
    $user = User::factory()->create([
        'name' => 'Sita Bhandari',
        'email' => 'sita@example.com',
        'phone' => '9812345678',
        'address' => 'Attariya, Kailali',
    ]);

    $popup = popupMarkup($this->actingAs($user)->get(hotelConfirmation([
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
    ]))->assertOk()->getContent());

    expect($popup)->toContain('value="Sita Bhandari"', false)
        ->toContain('value="sita@example.com"', false)
        ->toContain('value="9812345678"', false)
        ->toContain('value="Attariya, Kailali"', false);
});

test('a guest can fill the popup in, and the policy is required before confirming', function () {
    $popup = popupMarkup(confirmationHtml(['start_date' => '2026-11-02', 'end_date' => '2026-11-05']));

    // A guest is told what is coming rather than having the form withheld.
    expect($popup)->toContain('You can fill this in as a guest')
        // Required in the browser, and refused by bookings.store without it.
        ->toContain('name="policy_accepted" value="1"', false);

    // A guest is sent to sign in before the store ever sees the booking, so the
    // policy is enforced where it can be: on a customer who is already signed in.
    $this->actingAs(User::factory()->create())
        ->post(route('bookings.store'), guestPayload([
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-05',
            'policy_accepted' => null,
        ]))
        ->assertSessionHasErrors('policy_accepted');

    expect(Booking::query()->count())->toBe(0);
});

test('the policy has to be accepted, and one that already was does not have to be accepted twice', function () {
    $html = confirmationHtml(['start_date' => '2026-11-02', 'end_date' => '2026-11-05']);

    // Nothing is accepted on arrival, so Confirm Booking cannot be pressed yet even
    // though the dates themselves are bookable.
    expect(popupMarkup($html))->toMatch('/id="bookingSubmit"[^>]*\sdisabled/s');

    // A customer whose booking was refused for another reason has already accepted
    // the policy, and must not be made to accept it a second time to try again.
    $this->actingAs(User::factory()->create())
        ->from(hotelConfirmation(['start_date' => '2026-11-02', 'end_date' => '2026-11-05']))
        ->post(route('bookings.store'), guestPayload([
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-05',
            'phone' => '',
            'policy_accepted' => '1',
        ]))->assertSessionHasErrors('phone');

    $retry = test()->get(hotelConfirmation(['start_date' => '2026-11-02', 'end_date' => '2026-11-05']))
        ->assertOk()
        ->getContent();

    // The already-accepted policy has to leave Confirm Booking live, or a corrected
    // booking could never be retried.
    expect(popupMarkup($retry))->toMatch('/id="bookingSubmit"[^>]*>/')
        ->not->toMatch('/id="bookingSubmit"[^>]*\sdisabled/s')
        ->toMatch('/name="policy_accepted" value="1"\s*\n\s*id="policy_accepted"\s+checked/s');
});

test('the popup says why a booking cannot be confirmed', function () {
    // A check-out before the check-in is a period the rules refuse, so the popup
    // has to explain itself rather than offer a dead Confirm Booking.
    $html = $this->get(hotelConfirmation([
        'start_date' => '2026-11-05',
        'end_date' => '2026-11-02',
    ]))->assertOk()->getContent();

    $popup = popupMarkup($html);

    expect($popup)->toContain('Check-out date must be after the check-in date.')
        ->toMatch('/id="bookingSubmit"[^>]*\sdisabled/s')
        // The Review button is refused too: there is no review to be had yet.
        ->and($html)->toMatch('/id="reviewBookingButton"[^>]*\sdisabled/s');
});

/*
|--------------------------------------------------------------------------
| Confirming is the existing booking flow
|--------------------------------------------------------------------------
*/

test('Confirm Booking is the one existing booking form posting to the booking endpoint', function () {
    $html = confirmationHtml(['start_date' => '2026-11-02', 'end_date' => '2026-11-05']);

    // One form, the real one, and no second booking path introduced by the popup.
    expect(substr_count($html, '<form'))->toBe(1)
        ->and($html)->toContain('<form id="bookingForm" method="POST" action="'.route('bookings.store').'"', false)
        // Confirm Booking is that form's own submit, inside the popup.
        ->and(popupMarkup($html))->toContain('type="submit" class="fh-search-btn" id="bookingSubmit"', false)
        // The service and dates are still the hidden fields the store reads.
        ->and($html)->toContain('name="booking_type" id="bookingType" value="hotel"', false)
        ->and($html)->toContain('name="service_id" id="serviceId" value="'.hotelPricedAt()->id.'"', false)
        ->and($html)->toContain('name="start_date" id="start_date" value="2026-11-02"', false)
        ->and($html)->toContain('name="submission_token" id="submissionToken"', false);
});

test('reviewing a booking without confirming books nothing', function () {
    $user = User::factory()->create();

    // Opening and dismissing the popup is a read: the page itself can create a
    // booking only through an explicit POST, and it renders exactly one form.
    $this->actingAs($user)
        ->get(hotelConfirmation(['start_date' => '2026-11-02', 'end_date' => '2026-11-05']))
        ->assertOk();

    expect(Booking::query()->count())->toBe(0);
});

test('a customer confirming from the popup gets the booking created with their details', function () {
    $user = User::factory()->create();
    $hotel = hotelPricedAt();

    $this->actingAs($user)->post(route('bookings.store'), guestPayload([
        'service_id' => $hotel->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'name' => 'Popup Customer',
        'email' => $user->email,
        'phone' => '9812345678',
        'address' => 'Attariya, Kailali',
        'message' => 'Late arrival please',
    ]))->assertRedirect();

    $booking = Booking::query()->sole();

    expect($booking->service_id)->toBe($hotel->id)
        ->and($booking->service_title)->toBe($hotel->title)
        ->and($booking->start_date->toDateString())->toBe('2026-11-02')
        ->and($booking->end_date->toDateString())->toBe('2026-11-05')
        ->and($booking->name)->toBe('Popup Customer')
        ->and($booking->phone)->toBe('9812345678')
        ->and($booking->address)->toBe('Attariya, Kailali')
        ->and($booking->message)->toBe('Late arrival please')
        // The three nights the popup showed, priced by the server.
        ->and($booking->quantity)->toBe(3)
        ->and((float) $booking->total_amount)->toBe(12600.0);
});

test('a guest confirming from the popup signs in and their answers become the booking, with no second press', function () {
    $hotel = hotelPricedAt();

    $this->post(route('bookings.store'), guestPayload([
        'service_id' => $hotel->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'name' => 'Guest Booker',
        'phone' => '9812345678',
    ]))->assertRedirect(route('login'));

    $user = User::factory()->create(['password' => bcrypt('password')]);

    // The trip back through sign-in carries the interrupted confirmation - their
    // service, their dates and a one-time token - rather than a blank form.
    $resume = session('url.intended');

    expect($resume)->toContain('type=hotel')
        ->and($resume)->toContain('service_id='.$hotel->id)
        ->and($resume)->toContain('resume=');

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect($resume);

    // The browser following that link is the whole of the rest of the journey: no
    // second press on Review & Book, and the booking made from the answers the
    // guest gave before they were asked to sign in.
    $this->get($resume)->assertRedirect(route('bookings.show', Booking::query()->sole()->booking_reference));

    $booking = Booking::query()->sole();

    expect(Booking::query()->count())->toBe(1)
        ->and($booking->name)->toBe('Guest Booker')
        ->and($booking->phone)->toBe('9812345678')
        ->and($booking->start_date->toDateString())->toBe('2026-11-02')
        ->and($booking->end_date->toDateString())->toBe('2026-11-05')
        // The three nights and the total the popup showed.
        ->and($booking->quantity)->toBe(3)
        ->and((float) $booking->total_amount)->toBe(12600.0);
});

/*
|--------------------------------------------------------------------------
| The popup is a checkout, not a long vertical form
|--------------------------------------------------------------------------
*/

test('the popup puts the stay and the price beside the customer details', function () {
    $columns = popupColumns(confirmationHtml([
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'travelers' => 2,
    ]));

    // Left: what is being booked, and what it costs.
    expect($columns['left'])
        ->toContain('Your stay / journey')
        ->toContain('Price breakdown')
        ->toContain('id="fh-periodStart"', false)
        ->toContain('id="fh-periodEnd"', false)
        ->toContain('id="fh-periodParty"', false)
        ->toContain('id="fh-quoteUnit"', false)
        ->toContain('id="fh-quoteSubtotal"', false)
        ->toContain('id="fh-quoteTotal"', false)
        // Nothing the customer has to type lives on this side.
        ->not->toContain('name="name"', false)
        ->not->toContain('name="policy_accepted"', false);

    // Right: who it is for, and the terms they have to accept.
    expect($columns['right'])
        ->toContain('Your details')
        ->toContain('You can fill this in as a guest')
        ->toContain('name="name" id="name"', false)
        ->toContain('name="email" id="email"', false)
        ->toContain('name="phone" id="phone"', false)
        ->toContain('name="address" id="address"', false)
        ->toContain('name="message" id="message"', false)
        ->toContain('name="policy_accepted" value="1"', false)
        // The summary is not repeated down this side.
        ->not->toContain('Price breakdown')
        ->not->toContain('id="fh-quoteTotal"', false);
});

test('the popup reads the period back as days, named the way each service is travelled', function () {
    $stay = popupColumns(confirmationHtml([
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'travelers' => 2,
    ]))['left'];

    // A stay is begun and ended, and the raw 2026-11-02 the input holds never
    // reaches the review.
    expect($stay)->toContain('CHECK IN')
        ->toContain('CHECK OUT')
        ->toContain('Guests')
        ->toContain('Mon, 02 Nov')
        ->toContain('Thu, 05 Nov')
        ->not->toContain('2026-11-02');

    $rental = popupColumns($this->get(route('bookings.create', [
        'type' => 'vehicle',
        'slug' => vehiclePricedAt()->slug,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-13',
        'travelers' => 2,
    ]))->assertOk()->getContent())['left'];

    expect($rental)->toContain('DEPARTURE')
        ->toContain('RETURN')
        ->toContain('Passengers')
        ->toContain('Tue, 10 Nov')
        ->toContain('Fri, 13 Nov');

    $trip = popupColumns($this->get(route('bookings.create', [
        'type' => 'tour',
        'slug' => tourPricedAt()->slug,
        'start_date' => '2026-11-20',
        'travelers' => 3,
    ]))->assertOk()->getContent())['left'];

    expect($trip)->toContain('DEPARTURE')
        ->toContain('Travellers')
        ->toContain('Fri, 20 Nov')
        // A tour is not a stay, so it is never described as one.
        ->not->toContain('CHECK IN')
        ->not->toContain('Guests');
});

test('the popup sets the total apart from the rows that produced it', function () {
    $left = popupColumns(confirmationHtml([
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'travelers' => 2,
    ]))['left'];

    // The total is its own block rather than one more line of the breakdown, so it
    // cannot be read as another row the customer has to add up.
    expect($left)
        ->toMatch('/id="fh-quoteTotalRow"/')
        ->toMatch('/fh-total-label">Total</')
        ->toMatch('/fh-quote-grand-total" id="fh-quoteTotal">\s*NPR 12,600\.00/');
});

test('the popup is a large dialog that scrolls rather than overflowing', function () {
    $popup = popupMarkup(confirmationHtml([
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'travelers' => 2,
    ]));

    // Wide enough for two columns, and scrollable so a short screen still reaches
    // the footer buttons.
    expect($popup)
        ->toMatch('/class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"/')
        // The two columns only stand side by side from the large breakpoint up, so
        // a phone stacks them instead of squeezing both.
        ->toMatch('/class="row g-4"/')
        ->toMatch('/class="col-12 col-lg-5"/')
        ->toMatch('/class="col-12 col-lg-7"/');
});

/**
 * The two halves of the popup's checkout grid.
 *
 * A test that only proves both columns exist proves nothing about which is which,
 * so this splits the popup at its own column boundaries: what is on the left of the
 * divider and what is on the right.
 *
 * @return array{left: string, right: string}
 */
function popupColumns(string $html): array
{
    preg_match(
        '/<div class="col-12 col-lg-5">(.*?)<div class="col-12 col-lg-7">(.*)$/s',
        popupMarkup($html),
        $matches
    );

    return ['left' => $matches[1] ?? '', 'right' => $matches[2] ?? ''];
}

/**
 * What the popup's form would submit for a hotel stay: the service and the dates
 * from the page, the customer details they typed.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function guestPayload(array $overrides = []): array
{
    return array_merge([
        'booking_type' => 'hotel',
        'service_id' => hotelPricedAt()->id,
        'name' => 'Guest Booker',
        'email' => 'guest@example.com',
        'phone' => '9812345678',
        'travelers' => 2,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'policy_accepted' => '1',
    ], $overrides);
}
