<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Http\Middleware\PreserveBookingDraft;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Services\PriceCalculator;
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
 * The real submitted payload for a booking the form should produce.
 */
function submitPayload(string $type, $service, string $start, ?string $end, int $travelers = 2): array
{
    return [
        'booking_type' => $type,
        'service_id' => $service->id,
        'name' => 'Audit Traveller',
        'email' => 'audit@example.com',
        'phone' => '9800000000',
        'travelers' => $travelers,
        'start_date' => $start,
        'end_date' => $end,
        'policy_accepted' => '1',
    ];
}

/**
 * Every Book Now href inside the rendered results section.
 *
 * @return list<string>
 */
function resultBookNowHrefs(string $html): array
{
    preg_match('/<section class="fh-results-section" id="fh-resultsSection">(.*?)<\/section>/s', $html, $section);

    preg_match_all('/<a href="([^"]+)"[^>]*fh-book-now-btn/', $section[1] ?? '', $links);

    return array_map('html_entity_decode', $links[1] ?? []);
}

test('AUDIT: a hotel result Book Now link targets the booking form, not a dead link', function () {
    $html = $this->get('/book?type=hotel&travelers=2')->assertOk()->getContent();

    $href = resultBookNowHrefs($html)[0] ?? '';

    expect($href)->not->toBe('')->not->toContain('#');

    parse_str((string) parse_url($href, PHP_URL_QUERY), $query);

    expect($query['type'] ?? null)->toBe('hotel');

    $hotel = Hotel::query()->where('slug', $query['slug'] ?? null)->firstOrFail();

    expect($href)->toContain(route('bookings.create', ['type' => 'hotel', 'slug' => $hotel->slug], false));

    // Following it lands on the confirmation form for that hotel.
    $this->get($href)->assertOk()
        ->assertSee('name="service_id" id="serviceId" value="'.$hotel->id.'"', false);
});

test('AUDIT: every result card has a real Book Now href with a slug', function () {
    foreach (['hotel' => Hotel::class, 'vehicle' => TransportVehicle::class, 'tour' => Tour::class] as $type => $model) {
        $hrefs = resultBookNowHrefs($this->get('/book?type='.$type.'&travelers=2')->assertOk()->getContent());

        expect($hrefs)->not->toBeEmpty();

        foreach ($hrefs as $href) {
            parse_str((string) parse_url($href, PHP_URL_QUERY), $query);

            expect($query['type'] ?? null)->toBe($type)
                ->and($query['slug'] ?? null)->not->toBeNull()
                ->and($model::query()->where('slug', $query['slug'])->exists())->toBeTrue()
                ->and($href)->not->toContain('#');
        }
    }
});

test('AUDIT: a guest clicking Book Now is sent to login and the draft is kept', function () {
    $hotel = Hotel::query()->firstOrFail();

    $response = $this->post('/bookings', submitPayload('hotel', $hotel, '2026-10-10', '2026-10-12'));

    $response->assertRedirect(route('login'));

    // Nothing was created for an unauthenticated submission.
    expect(Booking::query()->count())->toBe(0);

    // The answers survive the redirect so the guest does not retype them.
    $draft = session(PreserveBookingDraft::SESSION_KEY);

    expect($draft)->toBeArray()
        ->and($draft['service_id'])->toBe($hotel->id)
        ->and($draft['booking_type'])->toBe('hotel')
        ->and($draft['start_date'])->toBe('2026-10-10')
        ->and($draft['end_date'])->toBe('2026-10-12')
        ->and($draft['email'])->toBe('audit@example.com')
        ->and($draft['phone'])->toBe('9800000000');
});

test('AUDIT: the preserved draft is restored into the form after signing in', function () {
    $hotel = Hotel::query()->firstOrFail();

    $this->post('/bookings', submitPayload('hotel', $hotel, '2026-10-10', '2026-10-12'))
        ->assertRedirect(route('login'));

    // The answers are restored into the confirmation page the sign-in
    // redirect points back at, so the hotel does not have to be picked again.
    $html = $this->get('/bookings/create?type=hotel&service_id='.$hotel->id)->assertOk()->getContent();

    expect($html)->toContain('name="service_id" id="serviceId" value="'.e($hotel->id).'"')
        ->and($html)->toContain('value="2026-10-10"')
        ->and($html)->toContain('value="2026-10-12"')
        ->and($html)->toContain('value="audit@example.com"');
});

test('AUDIT: a hotel booking creates exactly one real database record with server-side pricing', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('price', '>', 0)->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings', submitPayload('hotel', $hotel, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->sole();

    // The selected service is the one booked, not the first in the list.
    expect($booking->user_id)->toBe($user->id)
        ->and($booking->booking_type)->toBe('hotel')
        ->and($booking->service_id)->toBe($hotel->id)
        ->and($booking->service_title)->toBe($hotel->title)
        ->and($booking->booking_reference)->not->toBeEmpty()
        ->and($booking->start_date->toDateString())->toBe('2026-10-10')
        ->and($booking->end_date->toDateString())->toBe('2026-10-12')
        ->and($booking->travelers)->toBe(2)
        ->and($booking->email)->toBe('audit@example.com')
        ->and($booking->phone)->toBe('9800000000');

    // The total must match the server-side calculation for 2 nights.
    $expected = app(PriceCalculator::class)->quote('hotel', $hotel, '2026-10-10', '2026-10-12', 2);

    expect((float) $booking->total_amount)->toBe((float) $expected['total'])
        ->and((float) $booking->base_price)->toBe((float) $expected['subtotal'])
        ->and((int) $booking->quantity)->toBe((int) $expected['quantity'])
        ->and((float) $booking->total_amount)->toBeGreaterThan(0);

    // A new booking starts pending and is not auto-confirmed.
    expect($booking->status)->toBe('pending')
        ->and($booking->status)->toBeIn(Booking::STATUSES);
});

test('AUDIT: a browser-supplied total is ignored because the server recalculates', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->where('price', '>', 0)->firstOrFail();

    $payload = submitPayload('hotel', $hotel, '2026-10-10', '2026-10-12', 2);

    // A tampered form trying to book 2 nights for 1 rupee.
    $payload['total_amount'] = 1;
    $payload['subtotal'] = 1;

    $this->actingAs($user)->post('/bookings', $payload)->assertRedirect();

    $booking = Booking::query()->sole();

    expect((float) $booking->total_amount)->toBeGreaterThan(1);
});

test('AUDIT: a vehicle booking stores the right vehicle and keeps double-booking protection', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('vehicle_type', 'car')->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings', submitPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 3))
        ->assertRedirect();

    $booking = Booking::query()->sole();

    expect($booking->booking_type)->toBe('vehicle')
        ->and($booking->service_id)->toBe($vehicle->id)
        ->and($booking->service_title)->toBe($vehicle->name)
        ->and($booking->travelers)->toBe(3);

    // The same vehicle over the same dates must be refused.
    $second = User::factory()->create();

    $this->actingAs($second)
        ->post('/bookings', submitPayload('vehicle', $vehicle, '2026-10-11', '2026-10-13', 3))
        ->assertSessionHasErrors('booking');

    // Still only the first booking exists.
    expect(Booking::query()->count())->toBe(1);
});

test('AUDIT: a tour booking stores the selected tour and its duration', function () {
    $user = User::factory()->create();
    $tour = Tour::query()->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings', submitPayload('tour', $tour, '2026-10-10', null, 2))
        ->assertRedirect();

    $booking = Booking::query()->sole();

    expect($booking->booking_type)->toBe('tour')
        ->and($booking->service_id)->toBe($tour->id)
        ->and($booking->service_title)->toBe($tour->title)
        // A single-day tour is submitted with no end date; the existing workflow
        // derives the single night rather than the controller inventing one.
        ->and($booking->end_date->toDateString())->toBe('2026-10-11')
        // Tours are priced per traveller, so 2 travellers is the billed quantity.
        ->and((int) $booking->quantity)->toBe(2)
        ->and((float) $booking->total_amount)->toBeGreaterThan(0);
});

test('AUDIT: the new booking is visible to the customer in My Bookings', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings', submitPayload('hotel', $hotel, '2026-10-10', '2026-10-12'));

    $booking = Booking::query()->sole();

    $this->actingAs($user)
        ->get('/bookings/my')
        ->assertOk()
        ->assertSee(e($booking->booking_reference))
        ->assertSee(e($hotel->title));
});

test('AUDIT: the new booking appears in admin booking management', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->firstOrFail();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->post('/bookings', submitPayload('hotel', $hotel, '2026-10-10', '2026-10-12'));

    $booking = Booking::query()->sole();

    $this->actingAs($admin)
        ->get('/admin/bookings')
        ->assertOk()
        ->assertSee(e($booking->booking_reference));

    $this->actingAs($admin)
        ->get('/admin/bookings/'.$booking->id)
        ->assertOk()
        ->assertSee(e($hotel->title));
});

test('AUDIT: the new booking redirects into the existing payment flow', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings', submitPayload('hotel', $hotel, '2026-10-10', '2026-10-12'))
        ->assertRedirect();

    $booking = Booking::query()->sole();

    $this->actingAs($user)
        ->get('/bookings/'.$booking->booking_reference.'/payment')
        ->assertOk();
});

test('AUDIT: another customer cannot see or pay for the new booking', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $hotel = Hotel::query()->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings', submitPayload('hotel', $hotel, '2026-10-10', '2026-10-12'));

    $booking = Booking::query()->sole();

    $this->actingAs($other)
        ->get('/bookings/'.$booking->booking_reference)
        ->assertForbidden();

    $this->actingAs($other)
        ->get('/bookings/'.$booking->booking_reference.'/payment')
        ->assertForbidden();
});

test('AUDIT: the redesigned form still provides every field bookings.store validates', function () {
    $hotel = Hotel::query()->firstOrFail();

    $html = $this->get('/bookings/create?type=hotel&service_id='.$hotel->id)->assertOk()->getContent();

    // The hidden canonical inputs are what actually post.
    foreach (['booking_type', 'service_id', 'start_date', 'end_date', 'travelers', 'name', 'email', 'phone', 'address', 'message', 'policy_accepted'] as $field) {
        expect($html)->toContain('name="'.$field.'"');
    }
});

test('AUDIT: submitting the same booking twice does not create a duplicate', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->where('vehicle_type', 'car')->firstOrFail();

    $payload = submitPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2);

    $this->actingAs($user)->post('/bookings', $payload)->assertRedirect();

    // A resubmit (refresh / double click) is refused by availability, not doubled.
    $this->actingAs($user)->post('/bookings', $payload)->assertSessionHasErrors('booking');

    expect(Booking::query()->count())->toBe(1);
});

test('AUDIT: a booking without an end date is refused for a hotel but allowed for a tour', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->firstOrFail();
    $tour = Tour::query()->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings', submitPayload('hotel', $hotel, '2026-10-10', null))
        ->assertSessionHasErrors('booking');

    $this->actingAs($user)
        ->post('/bookings', submitPayload('tour', $tour, '2026-10-10', null))
        ->assertRedirect();

    expect(Booking::query()->count())->toBe(1);
});
