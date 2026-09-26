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
    $this->get('/my-account')->assertRedirect('/login');
});

test('a customer sees their own bookings on the user dashboard and nobody else receives them', function () {
    $owner = User::factory()->create(['is_admin' => false]);
    $other = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->first();

    $this->actingAs($owner)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $owner->id)->firstOrFail();

    $this->actingAs($owner)->get('/my-account')
        ->assertOk()
        ->assertSee('Welcome, '.$owner->name)
        ->assertSee($booking->booking_reference)
        ->assertSee($hotel->title);

    $this->actingAs($other)->get('/my-account')
        ->assertOk()
        ->assertDontSee($booking->booking_reference);
});

test('logout from the user dashboard ends the session and returns to the public home', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $this->actingAs($user)->post('/logout')->assertRedirect('/');
    $this->assertGuest();

    $this->get('/my-account')->assertRedirect('/login');
});

test('logging back in shows the user dashboard with the previous booking still present', function () {
    $user = User::factory()->create(['is_admin' => false, 'password' => bcrypt('password')]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    $this->post('/logout');

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/my-account');

    $this->get('/my-account')
        ->assertOk()
        ->assertSee($booking->booking_reference)
        ->assertSee($hotel->title);
});

test('an administrator can still open the user dashboard without losing admin access', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/my-account')->assertOk();
    $this->actingAs($admin)->get('/dashboard')->assertOk();
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
        ->get('/my-account')
        ->assertOk()
        ->assertSee('Your profile is complete')
        ->assertDontSee('Complete Your Profile');
});

test('the user dashboard uses its own authenticated layout without the public navbar or footer', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/my-account')
        ->assertOk()
        ->assertSee('Welcome, '.$user->name)
        ->assertSee('href="'.route('user.dashboard').'"', false)
        ->assertSee('href="'.route('bookings.my').'"', false)
        ->assertSee('href="'.route('profile.edit').'"', false)
        ->assertSee('action="'.route('logout').'"', false)
        ->assertDontSee('id="mainNavbar"', false)
        ->assertDontSee('id="mobileNav"', false)
        ->assertDontSee('id="jsp-footer-main"', false);
});

test('the My Bookings page uses the same user dashboard layout without the public navbar or footer', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('bookings.my'))
        ->assertOk()
        ->assertSee('My Bookings')
        ->assertSee('href="'.route('user.dashboard').'"', false)
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertSee('class="app-sidebar bg-body-secondary shadow"', false)
        ->assertSee('class="app-header navbar navbar-expand bg-body"', false)
        ->assertDontSee('id="mainNavbar"', false)
        ->assertDontSee('id="mobileNav"', false)
        ->assertDontSee('id="jsp-footer-main"', false)
        ->assertDontSee('Admin Dashboard');
});

test('the Book Now page opens inside the user dashboard layout for a logged-in customer', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('bookings.create'))
        ->assertOk()
        ->assertSee('Book Now')
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertSee('class="app-sidebar bg-body-secondary shadow"', false)
        ->assertSee('class="app-header navbar navbar-expand bg-body"', false)
        ->assertSee('href="'.route('user.dashboard').'"', false)
        ->assertSee('href="'.route('bookings.my').'"', false)
        ->assertSee('action="'.route('bookings.store').'"', false)
        ->assertSee('id="bookingForm"', false)
        ->assertDontSee('id="mainNavbar"', false)
        ->assertDontSee('id="mobileNav"', false)
        ->assertDontSee('id="jsp-footer-main"', false)
        ->assertDontSee('Admin Dashboard');
});

test('the booking form is pre-filled from the logged-in customer profile', function () {
    $user = User::factory()->create([
        'is_admin' => false,
        'name' => 'Sugam Rai',
        'phone' => '9812345678',
        'address' => 'Ward 5, Dhangadhi',
    ]);

    $this->actingAs($user)->get(route('bookings.create'))
        ->assertOk()
        ->assertSee('value="Sugam Rai"', false)
        ->assertSee('value="'.$user->email.'"', false)
        ->assertSee('value="9812345678"', false)
        ->assertSee('value="Ward 5, Dhangadhi"', false);
});

test('an administrator still receives the original public layout on the Book Now page', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get(route('bookings.create'))
        ->assertOk()
        ->assertSee('id="mainNavbar"', false)
        ->assertSee('id="jsp-footer-main"', false)
        ->assertDontSee('adminlte-sidebar-menu', false)
        ->assertDontSee('app-sidebar', false);
});

test('the Booking Details page uses the user dashboard layout for a customer', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)->get('/bookings/'.$booking->booking_reference)
        ->assertOk()
        ->assertSee($booking->booking_reference)
        ->assertSee($hotel->title)
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertSee('class="app-sidebar bg-body-secondary shadow"', false)
        ->assertSee('class="app-header navbar navbar-expand bg-body"', false)
        ->assertDontSee('id="mainNavbar"', false)
        ->assertDontSee('id="mobileNav"', false)
        ->assertDontSee('id="jsp-footer-main"', false)
        ->assertDontSee('Admin Dashboard');
});

test('the Payment page uses the user dashboard layout for a customer', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)->get('/bookings/'.$booking->booking_reference.'/payment')
        ->assertOk()
        ->assertSee('Amount due')
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertSee('class="app-sidebar bg-body-secondary shadow"', false)
        ->assertSee('class="app-header navbar navbar-expand bg-body"', false)
        ->assertDontSee('id="mainNavbar"', false)
        ->assertDontSee('id="mobileNav"', false)
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

    $response = $this->actingAs($user)->get('/my-account')->assertOk();
    $expected = max(0, (float) $payment->fresh()->amount - (float) $refund->amount);

    expect($response->viewData('paymentsTotal'))->toBe($expected);
});

test('the Cancellation preview page uses the user dashboard layout for a customer', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)->get('/bookings/'.$booking->booking_reference.'/cancel')
        ->assertOk()
        ->assertSee('Cancel '.$booking->booking_reference)
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertSee('class="app-sidebar bg-body-secondary shadow"', false)
        ->assertDontSee('id="mainNavbar"', false)
        ->assertDontSee('id="jsp-footer-main"', false);
});

test('an administrator still receives the public layout on booking detail, payment and cancel pages', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $admin = User::factory()->create(['is_admin' => true]);
    $hotel = Hotel::query()->first();

    $this->actingAs($user)->post('/bookings', dashboardBookingPayload($hotel))->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    $this->withSession(['booking_email' => $booking->email])->actingAs($admin);

    $this->get('/bookings/'.$booking->booking_reference)
        ->assertOk()
        ->assertSee('id="mainNavbar"', false)
        ->assertSee('id="jsp-footer-main"', false)
        ->assertDontSee('id="adminlte-sidebar-menu"', false);

    $this->get('/bookings/'.$booking->booking_reference.'/payment')
        ->assertOk()
        ->assertSee('Amount due')
        ->assertSee('id="mainNavbar"', false)
        ->assertDontSee('id="adminlte-sidebar-menu"', false);

    $this->get('/bookings/'.$booking->booking_reference.'/cancel')
        ->assertOk()
        ->assertSee('id="mainNavbar"', false)
        ->assertDontSee('id="adminlte-sidebar-menu"', false);
});
