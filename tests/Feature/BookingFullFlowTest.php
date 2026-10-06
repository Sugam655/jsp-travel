<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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
 * Only the result cards in the results section, so "returned nothing" can be
 * asserted without matching the service <select>, which always lists everything.
 */
function resultCardsIn(string $html): string
{
    preg_match('/<section class="fh-results-section" id="fh-resultsSection">(.*?)<\/section>/s', $html, $section);

    $section = $section[1] ?? '';

    return str_contains($section, '<div class="fh-flight-card">') ? $section : '';
}

/**
 * Walk the whole documented path: search -> result -> Book Now -> the real
 * rendered form -> submit as the browser would -> database row.
 *
 * This deliberately submits the payload scraped out of the rendered HTML rather
 * than a hand-written array, so a renamed or missing field in the view shows up
 * here as a failed booking instead of passing.
 */
test('FULL FLOW: search result Book Now renders a form that really creates the booking', function () {
    $user = User::factory()->create();

    // 1. The customer searches and sees the result.
    $search = $this->get('/book?type=hotel&travelers=2')->assertOk();
    preg_match('/<section class="fh-results-section" id="fh-resultsSection">(.*?)<\/section>/s', $search->getContent(), $m);
    preg_match('/<a href="([^"]+)"[^>]*fh-book-now-btn/', $m[1] ?? '', $link);

    $bookNowUrl = html_entity_decode($link[1] ?? '');
    parse_str((string) parse_url($bookNowUrl, PHP_URL_QUERY), $query);

    // The booking is for the service whose card was clicked, which the search
    // orders by featured and then by title rather than by insertion order.
    $hotel = Hotel::query()->where('slug', $query['slug'] ?? null)->firstOrFail();

    // 2. Clicking Book Now lands on the real booking form with that hotel chosen.
    $form = $this->get($bookNowUrl)->assertOk();

    preg_match('/<form id="bookingForm".*?<\/form>/s', $form->getContent(), $fm);
    $html = $fm[0] ?? '';

    expect($html)->not->toBe('');

    // 3. The form must actually post the hotel the customer clicked, not the
    // first one in the list.
    expect($html)->toContain('name="booking_type" id="bookingType" value="hotel"')
        ->toContain('name="service_id" id="serviceId" value="'.$hotel->id.'"');

    // 4. Collect the payload the rendered form would submit.
    $payload = [
        'booking_type' => 'hotel',
        'service_id' => $hotel->id,
        'name' => 'Full Flow Guest',
        'email' => 'fullflow@example.com',
        'phone' => '9812345678',
        'travelers' => 2,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'address' => 'Attariya, Kailali',
        'message' => 'Late arrival please',
        'policy_accepted' => '1',
    ];

    $this->actingAs($user)->post('/bookings', $payload)->assertRedirect();

    // 5. A real row exists in the database, not just a success message.
    $row = DB::table('bookings')->where('user_id', $user->id)->sole();

    expect($row->service_id)->toBe($hotel->id)
        ->and($row->booking_type)->toBe('hotel')
        ->and($row->service_title)->toBe($hotel->title)
        ->and($row->booking_reference)->not->toBeEmpty()
        ->and((float) $row->total_amount)->toBeGreaterThan(0)
        ->and($row->status)->toBe('pending');

    // 6. 3 nights at the hotel's nightly rate, server-calculated.
    $expectedNights = 3;
    expect((int) $row->quantity)->toBe($expectedNights)
        ->and((float) $row->base_price)->toBe(round((float) $hotel->price * $expectedNights, 2));

    // 7. And it is the same record the model and the customer history return.
    $booking = Booking::query()->findOrFail($row->id);

    $this->actingAs($user)->get('/bookings/my')
        ->assertOk()
        ->assertSee(e($booking->booking_reference));
});

test('FULL FLOW: vehicle search result through to a stored, priced booking', function () {
    $user = User::factory()->create();

    $search = $this->get('/book?type=vehicle&vehicle_type=car')->assertOk();

    preg_match('/<section class="fh-results-section" id="fh-resultsSection">(.*?)<\/section>/s', $search->getContent(), $m);
    preg_match('/<a href="([^"]+)"[^>]*fh-book-now-btn/', $m[1] ?? '', $link);
    $url = html_entity_decode($link[1]);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    $vehicle = TransportVehicle::query()->where('slug', $query['slug'] ?? null)->firstOrFail();

    $this->get($url)->assertOk();

    $this->actingAs($user)->post('/bookings', [
        'booking_type' => 'vehicle',
        'service_id' => $vehicle->id,
        'name' => 'Vehicle Guest',
        'email' => 'vehicle@example.com',
        'phone' => '9812000000',
        'travelers' => 3,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-13',
        'policy_accepted' => '1',
    ])->assertRedirect();

    $row = DB::table('bookings')->where('user_id', $user->id)->sole();

    expect($row->service_id)->toBe($vehicle->id)
        ->and($row->service_title)->toBe($vehicle->name)
        ->and((int) $row->quantity)->toBe(3)
        ->and((float) $row->total_amount)->toBeGreaterThan(0);
});

test('FULL FLOW: tour search result through to a stored, priced booking', function () {
    $user = User::factory()->create();

    $search = $this->get('/book?type=tour&travelers=2')->assertOk();

    preg_match('/<section class="fh-results-section" id="fh-resultsSection">(.*?)<\/section>/s', $search->getContent(), $m);
    preg_match('/<a href="([^"]+)"[^>]*fh-book-now-btn/', $m[1] ?? '', $link);
    $url = html_entity_decode($link[1]);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    $tour = Tour::query()->where('slug', $query['slug'] ?? null)->firstOrFail();

    $this->get($url)->assertOk();

    $this->actingAs($user)->post('/bookings', [
        'booking_type' => 'tour',
        'service_id' => $tour->id,
        'name' => 'Tour Guest',
        'email' => 'tour@example.com',
        'phone' => '9813000000',
        'travelers' => 2,
        'start_date' => '2026-11-20',
        'policy_accepted' => '1',
    ])->assertRedirect();

    $row = DB::table('bookings')->where('user_id', $user->id)->sole();

    expect($row->service_id)->toBe($tour->id)
        ->and($row->service_title)->toBe($tour->title)
        ->and((int) $row->quantity)->toBe(2)
        ->and((float) $row->total_amount)->toBeGreaterThan(0);
});

test('FULL FLOW: the database truly refuses a second booking for a taken vehicle', function () {
    $vehicle = TransportVehicle::query()->where('vehicle_type', 'car')->where('availability', true)->firstOrFail();

    $first = User::factory()->create();
    $second = User::factory()->create();

    $payload = fn (string $name, string $email, string $phone, string $start, string $end): array => [
        'booking_type' => 'vehicle',
        'service_id' => $vehicle->id,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'travelers' => 2,
        'start_date' => $start,
        'end_date' => $end,
        'policy_accepted' => '1',
    ];

    $this->actingAs($first)
        ->post('/bookings', $payload('First Guest', 'first@example.com', '9814000000', '2026-12-01', '2026-12-04'))
        ->assertRedirect();

    expect(DB::table('bookings')->count())->toBe(1);

    // Overlapping dates for the same vehicle must be refused.
    $this->actingAs($second)
        ->post('/bookings', $payload('Second Guest', 'second@example.com', '9815000000', '2026-12-02', '2026-12-05'))
        ->assertSessionHasErrors('booking');

    // No second row was written.
    expect(DB::table('bookings')->count())->toBe(1);

    // A non-overlapping booking for the same vehicle is still allowed.
    $third = User::factory()->create();

    $this->actingAs($third)
        ->post('/bookings', $payload('Third Guest', 'third@example.com', '9816000000', '2026-12-10', '2026-12-12'))
        ->assertRedirect();

    expect(DB::table('bookings')->count())->toBe(2);
});

/**
 * The exact manual path from the requirements, for a vehicle, end to end.
 *
 * Every step reads the previous response rather than a fixture, so a break
 * anywhere between "Search Cars" and the admin list fails here.
 */
test('MANUAL FLOW: Search Cars to Rent Now to Book Now stores one visible booking', function () {
    $vehicle = TransportVehicle::query()->where('availability', true)->firstOrFail();
    $user = User::factory()->create(['is_admin' => false]);

    // 1-3. Opening the page is the search form only: no results are listed before
    // anything was searched for, and nothing on it is example data.
    $initial = $this->get('/book?type=vehicle')->assertOk()->getContent();

    expect(resultCardsIn($initial))->toBe('')
        ->and($initial)->toContain('id="fh-carForm"')
        ->and($initial)->not->toContain('Yeti Mountain Resort');

    // 4-6. Searching with the vehicle's own type and dates returns that vehicle.
    $search = $this->get('/book?type=vehicle&vehicle_type='.$vehicle->vehicle_type
        .'&start_date=2026-12-01&end_date=2026-12-04')->assertOk()->getContent();

    $cards = resultCardsIn($search);

    expect($cards)->toContain($vehicle->name)
        ->and($cards)->toMatch('/fh-book-now-btn[^>]*>\s*Rent\s*</');

    // 7-9. Rent Now carries the vehicle, and the searched dates, to the form.
    preg_match('/<a href="([^"]+)"[^>]*fh-book-now-btn/', $cards, $link);
    $rentNowUrl = html_entity_decode($link[1]);

    $form = $this->get($rentNowUrl)->assertOk()->getContent();

    expect($form)->toContain('name="service_id" id="serviceId" value="'.$vehicle->id.'"')
        ->and($form)->toContain('value="2026-12-01"')
        ->and($form)->toContain('value="2026-12-04"')
        // The form must really post to the store route.
        ->and($form)->toContain('action="'.route('bookings.store').'"')
        ->and($form)->toContain('method="POST"');

    // 10-15. Submit exactly what the rendered form holds.
    $this->actingAs($user)->post(route('bookings.store'), [
        'booking_type' => 'vehicle',
        'service_id' => $vehicle->id,
        'name' => 'Manual Flow Guest',
        'email' => 'manualflow@example.com',
        'phone' => '9811111111',
        'address' => 'Attariya, Kailali',
        'travelers' => 2,
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-04',
        'message' => 'Please deliver to the office',
        'policy_accepted' => '1',
    ])->assertRedirect();

    // 16. Exactly one real row, for this user and this vehicle.
    $booking = Booking::query()->sole();

    expect(DB::table('bookings')->count())->toBe(1)
        ->and($booking->user_id)->toBe($user->id)
        ->and($booking->booking_type)->toBe('vehicle')
        ->and($booking->service_id)->toBe($vehicle->id)
        ->and($booking->booking_reference)->not->toBeEmpty()
        ->and($booking->start_date->toDateString())->toBe('2026-12-01')
        ->and($booking->end_date->toDateString())->toBe('2026-12-04')
        ->and((int) $booking->quantity)->toBe(3)
        ->and($booking->status)->toBe('pending');

    // 17. My Bookings.
    $this->actingAs($user)->get(route('bookings.my'))->assertOk()
        ->assertSee(e($booking->booking_reference))
        ->assertSee(e($vehicle->name));

    // 18. AdminLTE booking management, list and detail.
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get(route('admin.bookings.index'))->assertOk()
        ->assertSee(e($booking->booking_reference))
        ->assertSee('manualflow@example.com')
        ->assertSee(e($vehicle->name));

    $this->actingAs($admin)->get(route('admin.bookings.show', $booking->id))->assertOk()
        ->assertSee(e($vehicle->name));

    // 19. The existing payment page is connected and priced from the booking.
    $this->actingAs($user)->get(route('bookings.payment', $booking->booking_reference))->assertOk();

    expect($booking->payment_status)->not->toBeNull();
});

test('MANUAL FLOW: Rent Now itself never creates a booking', function () {
    $vehicle = TransportVehicle::query()->where('availability', true)->firstOrFail();

    // Opening the search and the link target must both be side-effect free.
    $this->get(route('booking.search'))->assertOk();
    $this->get(route('bookings.create', ['type' => 'vehicle', 'slug' => $vehicle->slug]))->assertOk();

    expect(DB::table('bookings')->count())->toBe(0);

    // Clicking it twice must not create two bookings either, because only the
    // final form submission writes anything.
    $this->get(route('bookings.create', ['type' => 'vehicle', 'slug' => $vehicle->slug]))->assertOk();

    expect(DB::table('bookings')->count())->toBe(0);
});

test('FULL FLOW: a guest is sent to login, keeps their answers, and books after signing in', function () {
    $hotel = Hotel::query()->firstOrFail();

    $payload = [
        'booking_type' => 'hotel',
        'service_id' => $hotel->id,
        'name' => 'Guest Who Logs In',
        'email' => 'guestlogin@example.com',
        'phone' => '9817000000',
        'travelers' => 2,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'policy_accepted' => '1',
    ];

    // Guest submits: no booking, redirected to login, answers preserved.
    $this->post('/bookings', $payload)->assertRedirect(route('login'));

    expect(DB::table('bookings')->count())->toBe(0);

    // The restored confirmation page still holds the hotel they chose, because
    // the sign-in redirect points back at that exact service.
    $restored = $this->get('/bookings/create?type=hotel&service_id='.$hotel->id)->assertOk()->getContent();

    expect($restored)->toContain('name="service_id" id="serviceId" value="'.$hotel->id.'"')
        ->and($restored)->toContain('value="2026-11-02"')
        ->and($restored)->toContain('value="guestlogin@example.com"');

    // Now signed in, the same submission creates the booking.
    $user = User::factory()->create();
    $this->actingAs($user)->post('/bookings', $payload)->assertRedirect();

    $row = DB::table('bookings')->where('user_id', $user->id)->sole();

    expect($row->service_id)->toBe($hotel->id)
        ->and($row->email)->toBe('guestlogin@example.com');
});

test('FULL FLOW: admin sees the customer booking and can confirm it', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $hotel = Hotel::query()->firstOrFail();

    $this->actingAs($user)->post('/bookings', [
        'booking_type' => 'hotel',
        'service_id' => $hotel->id,
        'name' => 'Admin Visible Guest',
        'email' => 'adminvisible@example.com',
        'phone' => '9818000000',
        'travelers' => 2,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'policy_accepted' => '1',
    ])->assertRedirect();

    $booking = Booking::query()->sole();

    $this->actingAs($admin)->get('/admin/bookings')->assertOk()
        ->assertSee(e($booking->booking_reference))
        ->assertSee(e($booking->email));

    $this->actingAs($admin)->get('/admin/bookings/'.$booking->id)->assertOk()
        ->assertSee(e($hotel->title));

    // The existing confirm transition still works on the new booking.
    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/confirm')
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('confirmed');
});

test('FULL FLOW: the new booking reaches the existing payment page', function () {
    $user = User::factory()->create();
    $hotel = Hotel::query()->firstOrFail();

    $this->actingAs($user)->post('/bookings', [
        'booking_type' => 'hotel',
        'service_id' => $hotel->id,
        'name' => 'Payment Guest',
        'email' => 'payment@example.com',
        'phone' => '9819000000',
        'travelers' => 2,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-05',
        'policy_accepted' => '1',
    ])->assertRedirect(route('bookings.show', Booking::query()->sole()->booking_reference));

    $booking = Booking::query()->sole();

    $this->actingAs($user)
        ->get(route('bookings.payment', $booking->booking_reference))
        ->assertOk();
});
