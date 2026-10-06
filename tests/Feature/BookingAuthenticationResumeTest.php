<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Http\Middleware\PreserveBookingDraft;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingSetting;
use Modules\Hotels\Models\Hotel;

/*
|--------------------------------------------------------------------------
| Resuming a confirmation interrupted by sign-in
|--------------------------------------------------------------------------
|
| Creating a booking needs an account, so a guest who presses Confirm Booking is
| sent to sign in. Their answers are saved across that step, and the press itself
| has to be remembered too - otherwise signing in drops them back on the form and
| they have to read and confirm the same booking a second time.
|
| These cover the interrupted press being resumed exactly once, and the cases
| where it must not be: no press at all, an abandoned login, a rejected one, a
| refresh, a replayed link, and a link pointed at a service never confirmed.
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

    // A fixed currency with no tax or service charge, so the totals asserted here
    // are the ones written in the tests.
    BookingSetting::set('currency', 'NPR');
    BookingSetting::set('tax_rate', 0);
    BookingSetting::set('service_charge_rate', 0);
});

/**
 * A hotel priced at a known nightly rate, so the resumed booking's arithmetic can
 * be checked against a number written in the test.
 */
function resumeHotel(): Hotel
{
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();
    $hotel->update(['price' => 4200]);

    return $hotel->fresh();
}

/**
 * What the confirmation popup submits for a stay, which is what a guest pressing
 * Confirm Booking sends.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function resumePayload(array $overrides = []): array
{
    return array_merge([
        'booking_type' => 'hotel',
        'service_id' => resumeHotel()->id,
        'name' => 'Guest Booker',
        'email' => 'guest@example.com',
        'phone' => '9812345678',
        'address' => 'Attariya, Kailali',
        'travelers' => 2,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'message' => 'Late arrival please',
        'policy_accepted' => '1',
        'submission_token' => 'guest-submission-token',
    ], $overrides);
}

/**
 * The URL a guest is handed on the way back from signing in, read from the session
 * the interrupted press left behind rather than rebuilt by the test - so this
 * proves the application really carried the interrupted confirmation through the
 * login redirect rather than the test inventing a way back.
 */
function interruptedResumeUrl(): string
{
    $url = session('url.intended');

    expect($url)->toBeString()->not->toBeEmpty()
        // The one-time token is the whole point: without it the guest is handed a
        // blank form to press a second time.
        ->and(queryValue((string) $url, 'resume'))->toBeString()->not->toBeEmpty();

    return (string) $url;
}

/**
 * One query-string value out of a URL.
 */
function queryValue(string $url, string $key): ?string
{
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    $value = $query[$key] ?? null;

    return is_string($value) ? $value : null;
}

/**
 * A guest presses Confirm Booking, is sent to sign in, signs in, and the browser
 * follows the redirect back - which is the whole customer journey, and the only
 * press of the button in it.
 *
 * @param  array<string, mixed>  $overrides
 */
function guestPressesConfirmAndSignsIn(User $user, array $overrides = []): string
{
    test()->post(route('bookings.store'), resumePayload($overrides))
        ->assertRedirect(route('login'));

    // The press never got past authentication, so nothing exists yet.
    expect(Booking::query()->count())->toBe(0);

    $resume = interruptedResumeUrl();

    test()->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect($resume);

    // Signing in is all the guest does next. The browser follows the redirect on
    // its own, exactly as it does for any other redirect.
    test()->get($resume)->assertRedirect();

    return $resume;
}

/**
 * A signed-in customer with a password the test can log them in with.
 */
function customer(): User
{
    return User::factory()->create(['password' => bcrypt('password')]);
}

test('a guest whose confirmation was interrupted gets the booking once they sign in, without pressing the button again', function () {
    $hotel = resumeHotel();

    guestPressesConfirmAndSignsIn($user = customer());

    // Exactly the one booking the guest confirmed, priced by the server out of the
    // answers they gave before they were asked to sign in.
    $booking = Booking::query()->sole();

    expect(Booking::query()->count())->toBe(1)
        ->and($booking->user_id)->toBe($user->id)
        ->and($booking->service_id)->toBe($hotel->id)
        ->and($booking->service_title)->toBe($hotel->title)
        ->and($booking->name)->toBe('Guest Booker')
        ->and($booking->email)->toBe('guest@example.com')
        ->and($booking->phone)->toBe('9812345678')
        ->and($booking->address)->toBe('Attariya, Kailali')
        ->and($booking->message)->toBe('Late arrival please')
        ->and($booking->travelers)->toBe(2)
        ->and($booking->start_date->toDateString())->toBe('2026-11-02')
        ->and($booking->end_date->toDateString())->toBe('2026-11-05')
        ->and($booking->quantity)->toBe(3)
        ->and((float) $booking->total_amount)->toBe(12600.0);
});

test('the resumed booking lands on its own confirmation page, not back on the form', function () {
    guestPressesConfirmAndSignsIn(customer());

    $reference = Booking::query()->sole()->booking_reference;

    // The customer is sent where a customer who confirmed directly is sent: to the
    // booking they just made, not to a form to fill in again.
    test()->get(route('bookings.show', $reference))->assertOk();

    expect(Booking::query()->count())->toBe(1);
});

test('an authenticated customer confirms in one press, with no sign-in at all', function () {
    $user = customer();

    test()->actingAs($user)->post(route('bookings.store'), resumePayload([
        'name' => 'Signed In Booker',
        'email' => $user->email,
    ]))->assertRedirect(route('bookings.show', Booking::query()->sole()->booking_reference));

    expect(Booking::query()->sole()->user_id)->toBe($user->id)
        ->and(Booking::query()->count())->toBe(1);
});

test('a guest who abandons the login books nothing', function () {
    test()->post(route('bookings.store'), resumePayload())
        ->assertRedirect(route('login'));

    // The guest leaves the login page without signing in, so the confirmation is
    // never resumed and nothing is written.
    expect(Booking::query()->count())->toBe(0);
});

test('a rejected sign-in books nothing and keeps the booking for the next attempt', function () {
    test()->post(route('bookings.store'), resumePayload())
        ->assertRedirect(route('login'));

    $user = customer();

    // Wrong password: still on the login page, and still nothing booked.
    test()->post(route('login'), ['email' => $user->email, 'password' => 'not-the-password'])
        ->assertSessionHasErrors('email');

    expect(Booking::query()->count())->toBe(0)
        ->and(session(PreserveBookingDraft::SESSION_KEY))->toBeArray()
        ->and(session(PreserveBookingDraft::SESSION_INTENT_KEY))->toBeString();

    // The next, correct attempt carries the booking through with it.
    $resume = interruptedResumeUrl();

    test()->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect($resume);

    test()->get($resume)->assertRedirect();

    expect(Booking::query()->count())->toBe(1)
        ->and(Booking::query()->sole()->name)->toBe('Guest Booker');
});

test('refreshing the booking page after signing in does not book a second time', function () {
    $resume = guestPressesConfirmAndSignsIn($user = customer());

    $reference = Booking::query()->sole()->booking_reference;

    // The resume link is now spent: reloading it shows the form rather than
    // creating a second booking for the same stay.
    test()->actingAs($user)->get($resume)->assertOk();

    expect(Booking::query()->count())->toBe(1)
        ->and(Booking::query()->sole()->booking_reference)->toBe($reference);
});

test('a resume link cannot be followed twice to book twice', function () {
    $resume = guestPressesConfirmAndSignsIn($user = customer());

    $reference = Booking::query()->sole()->booking_reference;

    test()->actingAs($user)->get($resume)->assertOk();
    test()->actingAs($user)->get($resume)->assertOk();

    expect(Booking::query()->count())->toBe(1)
        ->and(Booking::query()->sole()->booking_reference)->toBe($reference);
});

test('a resume token issued for one service cannot book a different one', function () {
    $confirmed = resumeHotel();
    $other = Hotel::query()->where('is_active', true)->where('id', '!=', $confirmed->id)->firstOrFail();

    test()->post(route('bookings.store'), resumePayload())
        ->assertRedirect(route('login'));

    $user = customer();
    $token = queryValue(interruptedResumeUrl(), 'resume');

    test()->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    // The service the guest confirmed is swapped for another one that happens to
    // be bookable. The saved draft still names the first, so nothing is booked.
    test()->actingAs($user)->get(route('bookings.create', [
        'type' => 'hotel',
        'service_id' => $other->id,
        'resume' => $token,
    ]))->assertOk();

    expect(Booking::query()->count())->toBe(0);
});

test('a resume token invented by hand books nothing', function () {
    test()->post(route('bookings.store'), resumePayload())
        ->assertRedirect(route('login'));

    $user = customer();
    $hotel = resumeHotel();

    test()->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    // A URL carrying a token this session was never issued is inert, and the form
    // is all the customer gets.
    test()->actingAs($user)->get(route('bookings.create', [
        'type' => 'hotel',
        'service_id' => $hotel->id,
        'resume' => 'a-token-nobody-issued',
    ]))->assertOk();

    expect(Booking::query()->count())->toBe(0);
});

test('an anonymous visit to a resume link books nothing', function () {
    test()->post(route('bookings.store'), resumePayload())
        ->assertRedirect(route('login'));

    // Opening the link in a fresh session, without ever signing in, leaves a guest
    // with a confirmation to fill in - never a booking.
    test()->get(interruptedResumeUrl())->assertOk();

    expect(Booking::query()->count())->toBe(0);
});

test('a guest who only looked at the form is not booked by signing in later', function () {
    $hotel = resumeHotel();

    // No press at all: the guest browses the confirmation page, then signs in from
    // somewhere else entirely.
    test()->get(route('bookings.create', [
        'type' => 'hotel',
        'service_id' => $hotel->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
    ]))->assertOk();

    test()->post(route('login'), ['email' => customer()->email, 'password' => 'password'])
        ->assertRedirect(route('user.dashboard'));

    expect(Booking::query()->count())->toBe(0);
});

test('a guest who had not accepted the policy is not booked by signing in', function () {
    test()->post(route('bookings.store'), resumePayload([
        'phone' => '',
        'policy_accepted' => null,
    ]))->assertRedirect(route('login'));

    $user = customer();
    $resume = interruptedResumeUrl();

    test()->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect($resume);

    // The resumed submission is validated exactly as a fresh press would be, so an
    // incomplete booking comes back as the form to correct rather than as a
    // half-made booking.
    test()->actingAs($user)->get($resume)->assertRedirect(route('bookings.create', [
        'type' => 'hotel',
        'service_id' => resumeHotel()->id,
    ]))->assertSessionHasErrors('phone');

    expect(Booking::query()->count())->toBe(0);
});
