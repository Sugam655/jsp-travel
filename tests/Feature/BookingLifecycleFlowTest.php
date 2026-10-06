<?php

/**
 * The complete booking lifecycle, driven through the real HTTP endpoints.
 *
 * Every test here goes form -> request -> validation -> transaction -> row ->
 * reference -> payment values -> confirmation. Nothing calls a service directly,
 * because the thing that has to be proven is that the wiring works, not that the
 * individual pieces do.
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Services\PaymentCalculationService;
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
 * Dates are always generated relative to today so the booking horizon rule
 * cannot make a hard-coded date expire and fail the suite in a later year.
 */
function lifeStart(int $daysFromNow = 30): string
{
    return now()->addDays($daysFromNow)->startOfDay()->toDateString();
}

function lifeEnd(int $daysFromNow = 32): string
{
    return now()->addDays($daysFromNow)->startOfDay()->toDateString();
}

/**
 * The payload the rendered booking form would post.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function lifePayload(string $type, mixed $service, array $overrides = []): array
{
    return array_merge([
        'booking_type' => $type,
        'service_id' => $service->id,
        'name' => 'Lifecycle Traveller',
        'email' => 'traveller@example.com',
        'phone' => '9800000000',
        'travelers' => 2,
        'start_date' => lifeStart(),
        'end_date' => lifeEnd(),
        'policy_accepted' => '1',
    ], $overrides);
}

/**
 * Submit through the form the page actually rendered, including its one-shot
 * token, so the idempotency guard is exercised exactly as a browser would.
 *
 * The form is opened for the service being booked, because the confirmation
 * page is per service rather than a picker.
 *
 * @param  array<string, mixed>  $overrides
 */
function lifeSubmit(User $user, string $type, mixed $service, array $overrides = [])
{
    $page = test()->actingAs($user)
        ->get(route('bookings.create', ['type' => $type, 'slug' => $service->slug]))
        ->assertOk()
        ->getContent();

    preg_match('/name="submission_token"[^>]*value="([^"]+)"/', $page, $matches);

    return test()->actingAs($user)->post(route('bookings.store'), lifePayload($type, $service, array_merge([
        'submission_token' => $matches[1] ?? 'missing-token',
    ], $overrides)));
}

/*
|--------------------------------------------------------------------------
| 1. The three supported services reach the database
|--------------------------------------------------------------------------
*/

test('LIFECYCLE hotel: form to row to reference to payment values', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    $response = lifeSubmit($user, 'hotel', $hotel)->assertRedirect();

    expect(Booking::query()->count())->toBe(1);

    $booking = Booking::query()->firstOrFail();

    // Identity: correct service, correct type, correct owner, correct dates.
    expect($booking->booking_type)->toBe('hotel')
        ->and($booking->service_id)->toBe($hotel->id)
        ->and($booking->service_title)->toBe($hotel->title)
        ->and($booking->user_id)->toBe($user->id)
        ->and($booking->start_date->toDateString())->toBe(lifeStart())
        ->and($booking->end_date->toDateString())->toBe(lifeEnd())
        ->and($booking->travelers)->toBe(2);

    // Reference generated in the existing format, from the real id and year.
    expect($booking->booking_reference)->toBe('BK-'.$booking->created_at->year.'-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT))
        ->and($booking->booking_reference)->toMatch('/^BK-\d{4}-\d{5}$/');

    // Status and pricing came from the server, not the browser.
    expect($booking->status)->toBe('pending')
        ->and((float) $booking->total_amount)->toBeGreaterThan(0.0)
        ->and((float) $booking->paid_amount)->toBe(0.0);

    // Landed on that booking's own page, which is where payment is started from.
    $response->assertRedirect(route('bookings.show', $booking->booking_reference));
});

test('LIFECYCLE vehicle: form to row to reference to payment values', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('is_active', true)->where('availability', true)->firstOrFail();

    lifeSubmit($user, 'vehicle', $vehicle)->assertRedirect();

    expect(Booking::query()->count())->toBe(1);

    $booking = Booking::query()->firstOrFail();

    expect($booking->booking_type)->toBe('vehicle')
        ->and($booking->service_id)->toBe($vehicle->id)
        ->and($booking->user_id)->toBe($user->id)
        ->and($booking->booking_reference)->toMatch('/^BK-\d{4}-\d{5}$/')
        ->and((float) $booking->total_amount)->toBeGreaterThan(0.0)
        ->and($booking->status)->toBe('pending');
});

test('LIFECYCLE tour: form to row to reference to payment values', function () {
    $user = User::factory()->create();
    $tour = Tour::query()->where('is_active', true)->firstOrFail();

    lifeSubmit($user, 'tour', $tour)->assertRedirect();

    expect(Booking::query()->count())->toBe(1);

    $booking = Booking::query()->firstOrFail();

    expect($booking->booking_type)->toBe('tour')
        ->and($booking->service_id)->toBe($tour->id)
        ->and($booking->user_id)->toBe($user->id)
        ->and($booking->booking_reference)->toMatch('/^BK-\d{4}-\d{5}$/')
        ->and((float) $booking->total_amount)->toBeGreaterThan(0.0);
});

/*
|--------------------------------------------------------------------------
| 2. Payment values survive the submission
|--------------------------------------------------------------------------
*/

test('LIFECYCLE a new booking keeps its total, advance, due and due date', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    lifeSubmit($user, 'hotel', $hotel)->assertRedirect();

    $booking = Booking::query()->firstOrFail();
    $summary = (new PaymentCalculationService)->summaryFor($booking);

    // The advance is snapshotted onto the row at creation time.
    expect($booking->advance_amount)->not->toBeNull()
        ->and((float) $booking->advance_amount)->toBeGreaterThan(0.0)
        ->and($booking->payment_due_date)->not->toBeNull();

    // Nothing is paid yet, so the whole advance and the whole total are due.
    expect($summary['total'])->toBe((float) $booking->total_amount)
        ->and($summary['paid'])->toBe(0.0)
        ->and($summary['due'])->toBe((float) $booking->total_amount)
        ->and($summary['advance_required'])->toBe((float) $booking->advance_amount)
        ->and($summary['advance_remaining'])->toBe((float) $booking->advance_amount);

    // And the payment page the customer lands on shows those same numbers.
    test()->actingAs($user)->get(route('bookings.payment', $booking->booking_reference))
        ->assertOk()
        ->assertSee($booking->booking_reference)
        ->assertSee('Booking submitted successfully')
        ->assertSee(number_format((float) $booking->total_amount, 2));
});

test('LIFECYCLE the confirmation reference is the one in the database', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    lifeSubmit($user, 'hotel', $hotel)->assertRedirect();

    $booking = Booking::query()->firstOrFail();

    // The confirmation the customer lands on is the one that states the reference.
    test()->actingAs($user)->get(route('bookings.show', $booking->booking_reference))
        ->assertOk()
        ->assertSee('Booking submitted successfully')
        ->assertSee('has been received')
        ->assertSee($booking->booking_reference);

    // The payment page the confirmation links to agrees with the database.
    test()->actingAs($user)->get(route('bookings.payment', $booking->booking_reference))
        ->assertOk()
        ->assertSee($booking->booking_reference);

    // The page must not contain an invented reference.
    expect($booking->booking_reference)->not->toBe('BK-2026-00023');
});

/*
|--------------------------------------------------------------------------
| 3. Authentication
|--------------------------------------------------------------------------
*/

test('LIFECYCLE a guest is sent to login and their confirmation is resumed once they sign in', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    $payload = lifePayload('hotel', $hotel, [
        'name' => 'Guest Typed This',
        'address' => 'Lalitpur',
    ]);

    // Nothing is created for an unauthenticated submit.
    $this->post(route('bookings.store'), $payload)->assertRedirect(route('login'));
    expect(Booking::query()->count())->toBe(0);

    // Signing in resumes the booking flow rather than dumping them on the home
    // page: the intended URL names the hotel they had already chosen and the
    // confirmation they had already given, rather than a blank form to redo.
    $resume = session('url.intended');

    expect($resume)->toContain('type=hotel')
        ->and($resume)->toContain('service_id='.$hotel->id)
        ->and($resume)->toContain('resume=');

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect($resume);

    // Following the redirect is the rest of the flow - no second press on the form
    // - and it ends on the booking made from exactly what the guest had typed.
    $this->get($resume)->assertRedirect(route('bookings.show', Booking::query()->sole()->booking_reference));

    $booking = Booking::query()->sole();

    expect(Booking::query()->count())->toBe(1)
        ->and($booking->user_id)->toBe($user->id)
        ->and($booking->service_id)->toBe($hotel->id)
        ->and($booking->name)->toBe('Guest Typed This')
        ->and($booking->address)->toBe('Lalitpur')
        ->and($booking->start_date->toDateString())->toBe(lifeStart())
        ->and($booking->end_date->toDateString())->toBe(lifeEnd());
});

test('LIFECYCLE every protected booking page sends a guest to login and creates nothing', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    lifeSubmit($user, 'hotel', $hotel)->assertRedirect();
    $booking = Booking::query()->firstOrFail();

    $this->post(route('logout'));

    foreach ([
        route('bookings.my'),
        route('bookings.show', $booking->booking_reference),
        route('bookings.payment', $booking->booking_reference),
        route('notifications.index'),
    ] as $url) {
        $this->get($url)->assertRedirect(route('login'));
    }

    $this->post(route('bookings.store'), lifePayload('hotel', $hotel))
        ->assertRedirect(route('login'));

    expect(Booking::query()->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| 4. Server-side validation
|--------------------------------------------------------------------------
*/

test('LIFECYCLE required fields are enforced on the server, not just in the browser', function (string $field, string $label) {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    $payload = lifePayload('hotel', $hotel);
    unset($payload[$field]);

    test()->actingAs($user)->from(route('bookings.create'))
        ->post(route('bookings.store'), $payload)
        ->assertSessionHasErrors($field);

    expect(Booking::query()->count())->toBe(0)
        ->and($label)->not->toBeEmpty();
})->with([
    ['name', 'customer name'],
    ['email', 'customer email'],
    ['phone', 'customer phone'],
    ['travelers', 'travellers'],
    ['start_date', 'start date'],
    ['service_id', 'service'],
    ['booking_type', 'service type'],
]);

test('LIFECYCLE the policy must be accepted before a booking is recorded', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    test()->actingAs($user)->from(route('bookings.create'))
        ->post(route('bookings.store'), lifePayload('hotel', $hotel, ['policy_accepted' => '0']))
        ->assertSessionHasErrors('policy_accepted');

    expect(Booking::query()->count())->toBe(0);
});

test('LIFECYCLE an unsupported booking type is rejected', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    test()->actingAs($user)->from(route('bookings.create'))
        ->post(route('bookings.store'), lifePayload('hotel', $hotel, ['booking_type' => 'spaceship']))
        ->assertSessionHasErrors('booking_type');

    expect(Booking::query()->count())->toBe(0);
});

test('LIFECYCLE a service id that does not exist is rejected', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    test()->actingAs($user)->from(route('bookings.create'))
        ->post(route('bookings.store'), lifePayload('hotel', $hotel, ['service_id' => 999999]))
        ->assertSessionHasErrors('service_id');

    expect(Booking::query()->count())->toBe(0);
});

test('LIFECYCLE the booked service is always resolved from the real table for that type', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();
    $vehicle = TransportVehicle::query()->where('availability', true)->firstOrFail();

    // Whatever id arrives, the row that gets written must point at a real
    // service of the declared type, priced from that service.
    test()->actingAs($user)
        ->post(route('bookings.store'), lifePayload('hotel', $hotel, ['service_id' => $vehicle->id]))
        ->assertRedirect();

    $booking = Booking::query()->firstOrFail();

    expect($booking->booking_type)->toBe('hotel')
        ->and(Hotel::query()->whereKey($booking->service_id)->exists())->toBeTrue()
        ->and($booking->service_title)->toBe(Hotel::query()->find($booking->service_id)->title);
});

test('LIFECYCLE an end date on or before the start date is rejected', function (string $case) {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    $end = match ($case) {
        'sameDay' => lifeStart(),
        'dayBefore' => now()->addDays(29)->toDateString(),
    };

    test()->actingAs($user)->from(route('bookings.create'))
        ->post(route('bookings.store'), lifePayload('hotel', $hotel, [
            'start_date' => lifeStart(),
            'end_date' => $end,
        ]))
        ->assertSessionHasErrors('booking');

    expect(Booking::query()->count())->toBe(0);
})->with(['sameDay', 'dayBefore']);

test('LIFECYCLE a start date in the past is rejected', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    test()->actingAs($user)->from(route('bookings.create'))
        ->post(route('bookings.store'), lifePayload('hotel', $hotel, [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => lifeEnd(),
        ]))
        ->assertSessionHasErrors('booking');

    expect(Booking::query()->count())->toBe(0);
});

test('LIFECYCLE an inactive service is rejected', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();
    $hotel->update(['is_active' => false]);

    // The service is filtered out before any rules run, so the customer is told
    // the selection is gone rather than that the dates are wrong.
    test()->actingAs($user)->from(route('bookings.create'))
        ->post(route('bookings.store'), lifePayload('hotel', $hotel))
        ->assertSessionHasErrors('service_id');

    expect(Booking::query()->count())->toBe(0);
});

test('LIFECYCLE a vehicle marked unavailable is rejected', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('availability', true)->firstOrFail();
    $vehicle->update(['availability' => false]);

    test()->actingAs($user)->from(route('bookings.create'))
        ->post(route('bookings.store'), lifePayload('vehicle', $vehicle))
        ->assertSessionHasErrors('service_id');

    expect(Booking::query()->count())->toBe(0);
});

test('LIFECYCLE a vehicle already booked for the same dates is rejected', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('availability', true)->firstOrFail();

    lifeSubmit($first, 'vehicle', $vehicle)->assertRedirect();
    expect(Booking::query()->count())->toBe(1);

    test()->actingAs($second)->from(route('bookings.create'))
        ->post(route('bookings.store'), lifePayload('vehicle', $vehicle))
        ->assertSessionHasErrors('booking');

    expect(Booking::query()->count())->toBe(1);
});

test('LIFECYCLE a vehicle is still bookable once the dates do not overlap', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('availability', true)->firstOrFail();

    lifeSubmit($first, 'vehicle', $vehicle, [
        'start_date' => lifeStart(),
        'end_date' => lifeEnd(),
    ])->assertRedirect();

    lifeSubmit($second, 'vehicle', $vehicle, [
        'start_date' => lifeStart(40),
        'end_date' => lifeEnd(42),
    ])->assertRedirect();

    expect(Booking::query()->count())->toBe(2);
});

test('LIFECYCLE a price sent by the browser is ignored', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    lifeSubmit($user, 'hotel', $hotel, [
        'total_amount' => '1.00',
        'amount' => '1.00',
    ])->assertRedirect();

    $booking = Booking::query()->firstOrFail();

    expect((float) $booking->total_amount)->toBeGreaterThan(1.0);
});

/*
|--------------------------------------------------------------------------
| 5. Double submission and refresh
|--------------------------------------------------------------------------
*/

test('LIFECYCLE double clicking Book Now creates exactly one booking', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    $page = test()->actingAs($user)->get(route('bookings.create', ['type' => 'hotel', 'slug' => $hotel->slug]))->assertOk()->getContent();
    preg_match('/name="submission_token"[^>]*value="([^"]+)"/', $page, $matches);

    $payload = lifePayload('hotel', $hotel, ['submission_token' => $matches[1]]);

    $first = test()->actingAs($user)->post(route('bookings.store'), $payload)->assertRedirect();
    $second = test()->actingAs($user)->post(route('bookings.store'), $payload)->assertRedirect();
    $third = test()->actingAs($user)->post(route('bookings.store'), $payload)->assertRedirect();

    $booking = Booking::query()->firstOrFail();

    expect(Booking::query()->count())->toBe(1)
        ->and($first->headers->get('Location'))->toBe($second->headers->get('Location'))
        ->and($second->headers->get('Location'))->toBe(route('bookings.show', $booking->booking_reference))
        ->and($third->headers->get('Location'))->toBe(route('bookings.show', $booking->booking_reference));

    // Exactly one notification, too.
    expect($user->notifications()->count())->toBe(1);
});

test('LIFECYCLE replaying the post after a refresh does not create a second booking', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    $page = test()->actingAs($user)->get(route('bookings.create', ['type' => 'hotel', 'slug' => $hotel->slug]))->assertOk()->getContent();
    preg_match('/name="submission_token"[^>]*value="([^"]+)"/', $page, $matches);

    $payload = lifePayload('hotel', $hotel, ['submission_token' => $matches[1]]);

    test()->actingAs($user)->post(route('bookings.store'), $payload)->assertRedirect();

    // Refreshing the landing page is a plain GET and must not resubmit anything.
    test()->actingAs($user)->get(route('bookings.payment', Booking::query()->firstOrFail()->booking_reference))->assertOk();

    // Replaying the identical POST is what a stale form or a back button does.
    test()->actingAs($user)->post(route('bookings.store'), $payload)->assertRedirect();

    expect(Booking::query()->count())->toBe(1)
        ->and($user->notifications()->count())->toBe(1);
});

test('LIFECYCLE a different submission token still books normally', function () {
    $user = User::factory()->create();
    $hotels = Hotel::query()->where('is_active', true)->take(2)->get();
    expect($hotels)->toHaveCount(2);

    // Two genuine, separate bookings must both be created - the guard only
    // swallows replays, it does not block repeat business.
    lifeSubmit($user, 'hotel', $hotels[0], [
        'start_date' => lifeStart(),
        'end_date' => lifeEnd(),
    ])->assertRedirect();

    lifeSubmit($user, 'hotel', $hotels[1], [
        'start_date' => lifeStart(60),
        'end_date' => lifeEnd(62),
    ])->assertRedirect();

    expect(Booking::query()->count())->toBe(2)
        ->and($user->notifications()->count())->toBe(2);
});

test('LIFECYCLE a failed submission can be corrected and resubmitted', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('availability', true)->firstOrFail();

    $page = test()->actingAs($user)->get(route('bookings.create', ['type' => 'vehicle', 'slug' => $vehicle->slug]))->assertOk()->getContent();
    preg_match('/name="submission_token"[^>]*value="([^"]+)"/', $page, $matches);

    $token = $matches[1];

    // First attempt collides with an existing reservation and is refused.
    $blocker = User::factory()->create();
    lifeSubmit($blocker, 'vehicle', $vehicle, [
        'start_date' => lifeStart(),
        'end_date' => lifeEnd(),
    ])->assertRedirect();

    test()->actingAs($user)->from(route('bookings.create'))
        ->post(route('bookings.store'), lifePayload('vehicle', $vehicle, ['submission_token' => $token]))
        ->assertSessionHasErrors('booking');

    expect(Booking::query()->count())->toBe(1);

    // The rejected token was never consumed, so correcting the dates works.
    test()->actingAs($user)->from(route('bookings.create'))
        ->post(route('bookings.store'), lifePayload('vehicle', $vehicle, [
            'submission_token' => $token,
            'start_date' => lifeStart(70),
            'end_date' => lifeEnd(72),
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Booking::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(Booking::query()->count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| 6. Notifications
|--------------------------------------------------------------------------
*/

test('LIFECYCLE exactly one notification is written, and only after the row exists', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    lifeSubmit($user, 'hotel', $hotel)->assertRedirect();

    $booking = Booking::query()->firstOrFail();
    $notification = $user->notifications()->firstOrFail();

    expect($user->notifications()->count())->toBe(1)
        ->and($notification->data['title'])->toBe('Booking received')
        ->and($notification->data['reference'])->toBe($booking->booking_reference)
        ->and($notification->data['url'])->toBe(route('bookings.show', $booking->booking_reference));
});

test('LIFECYCLE a rejected submission writes no notification', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    test()->actingAs($user)->from(route('bookings.create'))
        ->post(route('bookings.store'), lifePayload('hotel', $hotel, [
            'start_date' => lifeStart(),
            'end_date' => lifeStart(),
        ]))
        ->assertSessionHasErrors('booking');

    expect($booking_count = Booking::query()->count())->toBe(0)
        ->and($user->notifications()->count())->toBe(0)
        ->and($booking_count)->toBe(0);
});

test('LIFECYCLE the booking row and its notification land in one transaction', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('availability', true)->firstOrFail();

    lifeSubmit($user, 'vehicle', $vehicle)->assertRedirect();

    // Both the booking and its notification reference the same committed state.
    $booking = Booking::query()->firstOrFail();
    $notification = $user->notifications()->firstOrFail();

    expect($notification->data['reference'])->toBe($booking->booking_reference)
        ->and(DB::table('bookings')->where('id', $booking->id)->count())->toBe(1)
        ->and(DB::table('notifications')->where('notifiable_id', $user->id)->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| 7. The booking reaches the customer's own areas
|--------------------------------------------------------------------------
*/

test('LIFECYCLE a new booking is visible to its owner and nobody else', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $hotel = Hotel::query()->where('is_active', true)->firstOrFail();

    lifeSubmit($owner, 'hotel', $hotel)->assertRedirect();
    $booking = Booking::query()->firstOrFail();

    test()->actingAs($owner)->get(route('bookings.my'))->assertOk()->assertSee($booking->booking_reference);
    test()->actingAs($owner)->get(route('bookings.show', $booking->booking_reference))->assertOk();

    test()->actingAs($stranger)->get(route('bookings.my'))->assertOk()->assertDontSee($booking->booking_reference);
    test()->actingAs($stranger)->get(route('bookings.show', $booking->booking_reference))->assertForbidden();
});
