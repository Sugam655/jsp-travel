<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Services\BookingWorkflowService;
use Modules\Hotels\Models\Hotel;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Bookings', 'Tours', 'Hotels', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
    Artisan::call('module:seed', ['module' => 'Tours', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Hotels', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Transport', '--force' => true]);
});

function dashboardBookingPayload($hotel): array
{
    return [
        'booking_type' => 'hotel',
        'service_id' => $hotel->id,
        'name' => 'Test Traveller',
        'email' => 'owner@example.com',
        'phone' => '9800000000',
        'address' => 'Kathmandu',
        'travelers' => 2,
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-12',
        'message' => null,
        'policy_accepted' => '1',
    ];
}

test('the user dashboard requires authentication', function () {
    $this->get('/user/dashboard')->assertRedirect('/login');
});

test('a customer sees their own bookings on the user dashboard and nobody else receives them', function () {
    $owner = User::factory()->create(['is_admin' => false]);
    $other = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->first();

    $this->actingAs($owner)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $owner->id)->firstOrFail();

    $this->actingAs($owner)->get('/user/dashboard')
        ->assertOk()
        ->assertSee('Welcome, '.$owner->name)
        ->assertSee($booking->booking_reference)
        ->assertSee($hotel->title);

    $this->actingAs($other)->get('/user/dashboard')
        ->assertOk()
        ->assertDontSee($booking->booking_reference);
});

test('logout from the user dashboard ends the session and returns to the public home', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $this->actingAs($user)->post('/logout')->assertRedirect('/');
    $this->assertGuest();

    $this->get('/user/dashboard')->assertRedirect('/login');
});

test('logging back in shows the user dashboard with the previous booking still present', function () {
    $user = User::factory()->create(['is_admin' => false, 'password' => bcrypt('password')]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    $this->post('/logout');

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/user/dashboard');

    $this->get('/user/dashboard')
        ->assertOk()
        ->assertSee($booking->booking_reference)
        ->assertSee($hotel->title);
});

test('an administrator can still open the user dashboard without losing admin access', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/user/dashboard')->assertOk();
    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
});

test('the dashboard confirms a completed profile when the completion status is flashed', function () {
    $user = User::factory()->create([
        'is_admin' => false,
        'phone' => '9800000000',
        'address' => 'Dhankuta',
        'city' => 'Dhankuta',
        'country' => 'Nepal',
    ]);

    $this->actingAs($user)
        ->withSession(['status' => 'profile-completed'])
        ->get('/user/dashboard')
        ->assertOk()
        ->assertSee('Your profile is complete')
        ->assertDontSee('Complete Your Profile');
});

test('the user dashboard uses the shared AdminLTE shell with the narrowed customer sidebar', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/user/dashboard')
        ->assertOk()
        ->assertSee('Welcome, '.$user->name)
        ->assertSee('href="'.route('user.dashboard').'"', false)
        ->assertSee('href="'.route('bookings.my').'"', false)
        ->assertSee('href="'.route('payments.index').'"', false)
        ->assertSee('href="'.route('notifications.index').'"', false)
        ->assertSee('href="'.route('profile.edit').'"', false)
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertSee('class="app-header navbar navbar-expand bg-body"', false)
        ->assertDontSee('id="mainNavbar"', false)
        ->assertDontSee('id="jsp-footer-main"', false)
        ->assertDontSee('Admin Dashboard')
        ->assertDontSee(route('admin.dashboard'), false);
});

test('the customer sidebar exposes no administrative links at all', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->get('/user/dashboard')->assertOk();

    // The customer panel must not advertise a single admin destination. The
    // sidebar entries are resolved to absolute URLs by AdminLTE, so compare
    // against the generated routes rather than the raw config strings.
    foreach ([
        'admin.dashboard',
        'admin.home.index',
        'admin.tours.index',
        'admin.hotels.index',
        'admin.transport.index',
        'admin.bookings.index',
        'admin.bookings.payments.index',
        'admin.bookings.settings.index',
        'admin.contact.index',
        'admin.users.index',
    ] as $adminRoute) {
        $response->assertDontSee(route($adminRoute), false);
    }

    // The dedicated sidebar headers are administrator-only grouping labels and
    // must not survive into the customer panel either. These track the current
    // admin grouping labels, so the leak guard keeps its meaning after the
    // sidebar was regrouped into BOOKING MANAGEMENT and SETTINGS.
    $response->assertDontSee('BOOKING MANAGEMENT');
    $response->assertDontSee('SETTINGS');
    $response->assertDontSee('MAIN NAVIGATION');
});

test('an administrator still sees the full administrative sidebar', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/admin/dashboard')
        ->assertOk()
        ->assertSee(route('admin.bookings.index'), false)
        ->assertSee(route('admin.users.index'), false)
        ->assertSee(route('admin.bookings.change-requests.index'), false)
        ->assertSee(route('admin.bookings.payments.index'), false)
        ->assertSee(route('admin.bookings.settings.index'), false)
        ->assertSee('BOOKING MANAGEMENT')
        ->assertSee('SETTINGS')
        ->assertSee('Payments')
        ->assertSee('Booking Settings');
});

test('the My Bookings page uses the same customer AdminLTE sidebar', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('bookings.my'))
        ->assertOk()
        ->assertSee('My Bookings')
        ->assertSee('href="'.route('user.dashboard').'"', false)
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertSee('class="app-header navbar navbar-expand bg-body"', false)
        ->assertDontSee('id="mainNavbar"', false)
        ->assertDontSee('id="jsp-footer-main"', false)
        ->assertDontSee('Admin Dashboard');
});

test('Book Now opens the public booking search and never a bare booking form', function () {
    $user = User::factory()->create(['is_admin' => false]);

    // Searching for a booking is a public act, so the search page keeps the public
    // frontend shell for a signed-in customer too; only managing an existing
    // booking belongs to the AdminLTE panel.
    $this->actingAs($user)->get(route('booking.search'))
        ->assertOk()
        ->assertSee('id="mainNavbar"', false)
        ->assertSee('id="jsp-footer-main"', false)
        ->assertDontSee('id="adminlte-sidebar-menu"', false);

    // Without a type and a service there is nothing to confirm, so the form page
    // hands the customer back to the search instead of inventing a selection.
    $this->actingAs($user)->get(route('bookings.create'))
        ->assertRedirect(route('booking.search'));
});

test('the confirmation form is pre-filled from the logged-in customer profile', function () {
    $user = User::factory()->create([
        'is_admin' => false,
        'name' => 'Sugam Rai',
        'phone' => '9812345678',
        'address' => 'Ward 5, Dhangadhi',
    ]);
    $hotel = Hotel::query()->first();

    // Pre-filling belongs to the confirmation page for the exact service the
    // customer picked, so the profile arrives there rather than on a bare form.
    $this->actingAs($user)->get(route('bookings.create', ['type' => 'hotel', 'slug' => $hotel->slug]))
        ->assertOk()
        ->assertSee('value="Sugam Rai"', false)
        ->assertSee('value="'.$user->email.'"', false)
        ->assertSee('value="9812345678"', false)
        ->assertSee('value="Ward 5, Dhangadhi"', false);
});

test('an administrator gets the shared public search and the admin panel, never a separate booking form', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    // There is exactly one booking flow: the public search is not forked for
    // admins, and the bare form page still redirects to the search.
    $this->actingAs($admin)->get(route('booking.search'))
        ->assertOk()
        ->assertSee('id="mainNavbar"', false)
        ->assertDontSee('id="adminlte-sidebar-menu"', false);

    $this->actingAs($admin)->get(route('bookings.create'))
        ->assertRedirect(route('booking.search'));

    // Booking management for an administrator stays in the admin AdminLTE panel.
    $this->actingAs($admin)->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertDontSee('id="mainNavbar"', false);
});

test('the Booking Details page stays inside the customer AdminLTE panel', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)->get('/bookings/'.$booking->booking_reference)
        ->assertOk()
        ->assertSee($booking->booking_reference)
        ->assertSee($hotel->title)
        // The confirmation of a just-submitted booking lands here, inside AdminLTE,
        // and quotes the reference that was actually persisted.
        ->assertSee('Booking request received')
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertSee('class="app-header navbar navbar-expand bg-body"', false)
        ->assertSee('href="'.route('bookings.my').'"', false)
        ->assertDontSee('id="mainNavbar"', false)
        ->assertDontSee('id="jsp-footer-main"', false)
        ->assertDontSee('Admin Dashboard');
});

test('the Payment page stays inside the customer AdminLTE panel', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)->get('/bookings/'.$booking->booking_reference.'/payment')
        ->assertOk()
        ->assertSee('Amount due')
        ->assertSee($booking->booking_reference)
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertSee('class="app-header navbar navbar-expand bg-body"', false)
        ->assertDontSee('id="mainNavbar"', false)
        ->assertDontSee('id="jsp-footer-main"', false);
});

test('the customer dashboard settled total subtracts processed refunds', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $admin = User::factory()->create(['is_admin' => true]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();
    $this->actingAs($admin)->post('/admin/bookings/'.$booking->id.'/confirm')->assertRedirect();
    $this->actingAs($admin)->post('/admin/bookings/'.$booking->id.'/request-payment')->assertRedirect();

    $workflow = app(BookingWorkflowService::class);
    $payment = $workflow->recordCustomerPayment(
        $booking->fresh(),
        'bank_transfer',
        'DASHBOARD-REFUND',
        (string) $booking->advance_amount,
        $user,
    );
    $workflow->verifyPayment($payment, $admin);
    $workflow->cancel($booking->fresh(), 'admin', 'Refund dashboard test', $admin);

    $refund = $booking->refunds()->where('status', 'pending')->firstOrFail();
    $workflow->processRefund($refund, $admin, 'cash', 'DASHBOARD-REFUND-1', 'Processed refund');

    $response = $this->actingAs($user)->get('/user/dashboard')->assertOk();
    $expected = max(0, (float) $payment->fresh()->amount - (float) $refund->amount);

    expect($response->viewData('paymentsTotal'))->toBe($expected);
});

test('the Cancellation preview page stays inside the customer AdminLTE panel', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)->get('/bookings/'.$booking->booking_reference.'/cancel')
        ->assertOk()
        ->assertSee('Cancel '.$booking->booking_reference)
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertSee('class="app-header navbar navbar-expand bg-body"', false)
        ->assertSee('action="'.route('bookings.cancel', $booking->booking_reference).'"', false)
        ->assertDontSee('id="mainNavbar"', false)
        ->assertDontSee('id="jsp-footer-main"', false);
});

test('an administrator manages another user booking through the admin panel, not the customer pages', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $admin = User::factory()->create(['is_admin' => true]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    // The customer booking pages are owner-only. An admin is a signed-in
    // non-owner, so a matching booking_email session must not grant access;
    // admins reach this booking through admin.bookings.show instead.
    $this->withSession(['booking_email' => $booking->email])->actingAs($admin);

    $this->get('/bookings/'.$booking->booking_reference)->assertForbidden();
    $this->get('/bookings/'.$booking->booking_reference.'/payment')->assertForbidden();
    $this->get('/bookings/'.$booking->booking_reference.'/cancel')->assertForbidden();

    $this->get('/admin/bookings/'.$booking->id)
        ->assertOk()
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertDontSee('id="mainNavbar"', false);
});
