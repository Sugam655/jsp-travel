<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingChangeRequest;
use Modules\Bookings\Models\Payment;
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

function bookingTestPayload(string $type, $service, string $start, ?string $end, int $travelers, ?string $email = null, array $extra = []): array
{
    $payload = [
        'booking_type' => $type,
        'service_id' => $service->id,
        'name' => 'Test Traveller',
        'email' => $email ?? 'traveller@example.com',
        'phone' => '9800000000',
        'address' => 'Kathmandu',
        'travelers' => $travelers,
        'start_date' => $start,
        'end_date' => $end,
        'message' => null,
        'policy_accepted' => '1',
    ];

    return array_merge($payload, $extra);
}

test('an admin can approve a customer change request and the new dates are applied', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'dates',
            'requested' => ['start_date' => '2026-10-20', 'end_date' => '2026-10-22'],
            'reason' => 'Trip pushed back a week',
        ])
        ->assertRedirect();

    $changeRequest = BookingChangeRequest::query()->firstOrFail();
    expect($changeRequest->status)->toBe('pending');

    $this->actingAs($admin)
        ->post('/admin/change-requests/'.$changeRequest->id.'/approve', ['note' => 'Approved'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($changeRequest->fresh()->status)->toBe('approved')
        ->and($changeRequest->fresh()->reviewed_by)->toBe($admin->id)
        ->and($changeRequest->fresh()->reviewed_at)->not->toBeNull()
        ->and($booking->fresh()->start_date->format('Y-m-d'))->toBe('2026-10-20')
        ->and($booking->fresh()->end_date->format('Y-m-d'))->toBe('2026-10-22')
        ->and($booking->fresh()->history()->where('action', 'change_approved')->exists())->toBeTrue()
        ->and($user->notifications()->where('data->title', 'Change approved')->exists())->toBeTrue();
});

test('an already reviewed change request cannot be approved again', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'travelers',
            'requested' => ['travelers' => 3],
        ])
        ->assertRedirect();

    $changeRequest = BookingChangeRequest::query()->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/change-requests/'.$changeRequest->id.'/approve')
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->post('/admin/change-requests/'.$changeRequest->id.'/approve')
        ->assertRedirect()
        ->assertSessionHasErrors('change_request');

    expect(BookingChangeRequest::query()->count())->toBe(1);
});

test('a customer cannot raise a second change request while one is pending', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'travelers',
            'requested' => ['travelers' => 3],
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'dates',
            'requested' => ['start_date' => '2026-10-20', 'end_date' => '2026-10-22'],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('change_request');

    expect(BookingChangeRequest::query()->where('status', 'pending')->count())->toBe(1);
});

test('an admin can reject a change request and the customer is notified', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'travelers',
            'requested' => ['travelers' => 3],
        ])
        ->assertRedirect();

    $changeRequest = BookingChangeRequest::query()->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/change-requests/'.$changeRequest->id.'/reject', ['note' => 'No larger vehicle available'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($changeRequest->fresh()->status)->toBe('rejected')
        ->and($booking->fresh()->history()->where('action', 'change_rejected')->exists())->toBeTrue()
        ->and($user->notifications()->where('data->title', 'Change rejected')->exists())->toBeTrue();
});

test('change approval is blocked when the requested dates are already booked', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($owner)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $this->actingAs($other)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-20', '2026-10-22', 2, 'other@example.com'))
        ->assertRedirect();

    $target = Booking::query()->where('email', 'traveller@example.com')->firstOrFail();
    $moving = Booking::query()->where('email', 'other@example.com')->firstOrFail();

    $this->actingAs($other)
        ->post('/bookings/'.$moving->booking_reference.'/change-requests', [
            'type' => 'dates',
            'requested' => ['start_date' => '2026-10-10', 'end_date' => '2026-10-12'],
        ])
        ->assertRedirect();

    $changeRequest = $moving->changeRequests()->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/change-requests/'.$changeRequest->id.'/approve')
        ->assertRedirect()
        ->assertSessionHasErrors('change_request');

    expect($moving->fresh()->start_date->format('Y-m-d'))->toBe('2026-10-20')
        ->and($changeRequest->fresh()->status)->toBe('pending')
        ->and($target->fresh()->start_date->format('Y-m-d'))->toBe('2026-10-10');
});

test('a customer payment report can be verified by an admin', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/confirm')
        ->assertRedirect();
    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/request-payment')
        ->assertRedirect();

    $due = (string) round($booking->fresh()->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-ABC-123',
            'amount' => $due,
        ])
        ->assertRedirect();

    $payment = Payment::query()->firstOrFail();
    expect($payment->status)->toBe('pending')
        ->and($booking->fresh()->status)->toBe('payment_pending');

    $this->actingAs($admin)
        ->get('/admin/bookings/payments')
        ->assertOk()
        ->assertSee($booking->booking_reference);

    $this->actingAs($admin)
        ->post('/admin/payments/'.$payment->id.'/verify', ['note' => 'Bank statement confirmed'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($payment->fresh()->status)->toBe('paid')
        ->and($payment->fresh()->paid_at)->not->toBeNull()
        ->and($booking->fresh()->paid_amount)->toBe($booking->fresh()->total_amount)
        ->and($booking->fresh()->status)->toBe('paid')
        ->and($booking->fresh()->history()->where('action', 'payment_received')->exists())->toBeTrue()
        ->and($user->notifications()->where('data->title', 'Payment successful')->exists())->toBeTrue();
});

test('a customer payment report can be rejected by an admin', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/confirm')
        ->assertRedirect();
    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/request-payment')
        ->assertRedirect();

    $due = (string) round($booking->fresh()->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-FAKE-999',
            'amount' => $due,
        ])
        ->assertRedirect();

    $payment = Payment::query()->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$payment->id.'/fail', ['reason' => 'Reference not found in bank records'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($payment->fresh()->status)->toBe('failed')
        ->and($booking->fresh()->status)->toBe('payment_pending')
        ->and((float) $booking->fresh()->paid_amount)->toBe(0.0)
        ->and($booking->fresh()->history()->where('action', 'payment_failed')->exists())->toBeTrue()
        ->and($user->notifications()->where('data->title', 'Payment failed')->exists())->toBeTrue();
});

test('a customer cannot report a second payment while one is still pending', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/confirm')
        ->assertRedirect();
    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/request-payment')
        ->assertRedirect();

    $due = (string) round($booking->fresh()->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-1',
            'amount' => $due,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-2',
            'amount' => $due,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('payment');

    expect(Payment::query()->where('booking_id', $booking->id)->where('status', 'pending')->count())->toBe(1);
});

test('admins and customers both receive notifications for the booking events', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/confirm')
        ->assertRedirect();
    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/request-payment')
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'travelers',
            'requested' => ['travelers' => 3],
        ])
        ->assertRedirect();

    $due = (string) round($booking->fresh()->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-123',
            'amount' => $due,
        ])
        ->assertRedirect();

    $adminTitles = $admin->notifications()->get()->pluck('data.title');
    expect($adminTitles)->toContain('New booking request')
        ->and($adminTitles)->toContain('Change request')
        ->and($adminTitles)->toContain('Payment report');

    $customerTitles = $user->notifications()->get()->pluck('data.title');
    expect($customerTitles)->toContain('Booking received')
        ->and($customerTitles)->toContain('Payment requested')
        ->and($customerTitles)->toContain('Change request received')
        ->and($customerTitles)->toContain('Payment received');
});

test('a customer can open their notification centre and mark notifications as read', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($user)
        ->get('/notifications')
        ->assertOk()
        ->assertSee('Booking received');

    $notification = $user->notifications()->firstOrFail();
    expect($notification->read_at)->toBeNull();

    $this->actingAs($user)
        ->get('/notifications/'.$notification->getKey().'/read')
        ->assertRedirect(route('bookings.show', $booking->booking_reference));

    expect($notification->fresh()->read_at)->not->toBeNull();

    $this->flushSession();
    auth()->logout();

    $this->actingAs($user)
        ->get('/notifications')
        ->assertOk()
        ->assertSee('Booking received');
});

test('mark all as read clears the unread badge', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(1);

    $this->actingAs($user)
        ->get('/notifications/read-all')
        ->assertRedirect(route('notifications.index'));

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('customers cannot access the admin payment queue or process payments', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/confirm')
        ->assertRedirect();
    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/request-payment')
        ->assertRedirect();

    $due = (string) round($booking->fresh()->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-1',
            'amount' => $due,
        ])
        ->assertRedirect();

    $payment = Payment::query()->firstOrFail();

    $this->actingAs($user)
        ->get('/admin/bookings/payments')
        ->assertForbidden();

    $this->actingAs($user)
        ->post('/admin/payments/'.$payment->id.'/verify')
        ->assertForbidden();

    $this->actingAs($user)
        ->post('/admin/payments/'.$payment->id.'/fail')
        ->assertForbidden();

    expect($payment->fresh()->status)->toBe('pending');
});

test('one customer cannot read another customer notifications', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($owner)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $notification = $owner->notifications()->firstOrFail();

    $this->actingAs($intruder)
        ->get('/notifications/'.$notification->getKey().'/read')
        ->assertStatus(404);

    expect($notification->fresh()->read_at)->toBeNull();
});

test('the navbar dropdown feed only contains unread notifications', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $this->actingAs($user)
        ->getJson('/notifications/data')
        ->assertJsonPath('label', 1)
        ->assertJsonPath('label_color', 'danger');

    $dropdown = $this->getJson('/notifications/data')->json('dropdown');
    expect($dropdown)->toContain('Booking received');

    $notification = $user->notifications()->firstOrFail();
    $this->get('/notifications/'.$notification->getKey().'/read')->assertRedirect();

    $this->getJson('/notifications/data')
        ->assertJsonPath('label', 0)
        ->assertJsonPath('label_color', 'light');

    $dropdown = $this->getJson('/notifications/data')->json('dropdown');
    expect($dropdown)->toContain('No new notifications')
        ->and($dropdown)->not->toContain('Booking received');
});

test('marking one notification read never marks the others', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/confirm')
        ->assertRedirect();

    $confirmNotification = $user->notifications()->where('data->title', 'Booking confirmed')->firstOrFail();
    $receivedNotification = $user->notifications()->where('data->title', 'Booking received')->firstOrFail();

    $this->actingAs($user)
        ->get('/notifications/'.$confirmNotification->getKey().'/read')
        ->assertRedirect(route('bookings.show', $booking->booking_reference));

    expect($confirmNotification->fresh()->read_at)->not->toBeNull()
        ->and($receivedNotification->fresh()->read_at)->toBeNull()
        ->and($user->unreadNotifications()->count())->toBe(1);
});

test('an admin change request notification opens the exact change request row', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'travelers',
            'requested' => ['travelers' => 3],
        ])
        ->assertRedirect();

    $changeRequest = BookingChangeRequest::query()->firstOrFail();
    $notification = $admin->notifications()->where('data->title', 'Change request')->firstOrFail();

    expect($notification->data['payload']['type'])->toBe('change_request')
        ->and($notification->data['payload']['change_request_id'])->toBe($changeRequest->id);

    $this->actingAs($admin)
        ->get('/notifications/'.$notification->getKey().'/read')
        ->assertRedirect(route('admin.bookings.change-requests.index').'#change-request-'.$changeRequest->id);

    expect($notification->fresh()->read_at)->not->toBeNull()
        ->and($user->unreadNotifications()->count())->toBe(2);
});

test('an admin payment report notification opens the exact payment request row', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/confirm')
        ->assertRedirect();
    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/request-payment')
        ->assertRedirect();

    $due = (string) round($booking->fresh()->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-1',
            'amount' => $due,
        ])
        ->assertRedirect();

    $payment = Payment::query()->firstOrFail();
    $notification = $admin->notifications()->where('data->title', 'Payment report')->firstOrFail();

    expect($notification->data['payload']['type'])->toBe('payment')
        ->and($notification->data['payload']['payment_id'])->toBe($payment->id);

    $this->actingAs($admin)
        ->get('/notifications/'.$notification->getKey().'/read')
        ->assertRedirect(route('admin.payments.show', $payment));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('a customer notification always opens their own booking details', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/cancel')
        ->assertRedirect();

    $cancelled = $booking->fresh();
    expect($cancelled->status)->toBe('cancelled');

    $notification = $user->notifications()->where('data->title', 'Booking cancelled')->firstOrFail();
    expect($notification->data['payload']['type'])->toBe('booking')
        ->and($notification->data['payload']['booking_id'])->toBe($booking->id);

    $this->actingAs($user)
        ->get('/notifications/'.$notification->getKey().'/read')
        ->assertRedirect(route('bookings.show', $booking->booking_reference));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('the bell and the view-all footer point to the notification list, not the data feed', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $footer = url('/notifications').'" class="dropdown-item dropdown-footer';
    $listUrl = url('/notifications').'"';
    $dataAnchor = 'href="'.url('/notifications/data').'"';

    // Administrators keep the AdminLTE navbar bell, and its footer link points
    // at the list rather than the polling data feed.
    $adminPage = $this->actingAs($admin)->get('/admin/bookings/payments');
    $adminPage->assertOk()->assertSee($footer, false);

    $adminNotifications = $this->actingAs($admin)->get('/notifications');
    $adminNotifications->assertOk()
        ->assertSee('id="adminlte-sidebar-menu"', false);

    expect($adminPage->getContent())->toContain($listUrl)
        ->and(strpos($adminPage->getContent(), $dataAnchor))->toBeFalse()
        ->and(strpos($adminNotifications->getContent(), $dataAnchor))->toBeFalse();

    // Customers share the same AdminLTE notification centre, which links to the
    // same list and never to the data feed. The role-specific sidebar is covered
    // by its own test below, because AdminLTE compiles the menu once per request
    // in the container, so a role can only be asserted on the first render.
    $customerPage = $this->actingAs($user)->get('/notifications');
    $customerPage->assertOk()
        ->assertSee($listUrl, false);

    expect(strpos($customerPage->getContent(), $dataAnchor))->toBeFalse();
});

test('a customer sees the narrowed customer sidebar in the notification centre', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/notifications')
        ->assertOk()
        ->assertSee('id="adminlte-sidebar-menu"', false)
        ->assertSee('href="'.route('user.dashboard').'"', false)
        ->assertDontSee(route('admin.dashboard'), false);
});

test('a customer can reach the notification centre from the customer sidebar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/user/dashboard')
        ->assertOk()
        ->assertSee('href="'.route('notifications.index').'"', false);
});

test('an admin can open the change requests inbox and see the pending request', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'travelers',
            'requested' => ['travelers' => 3],
        ])
        ->assertRedirect();

    $changeRequest = BookingChangeRequest::query()->firstOrFail();

    $this->actingAs($admin)
        ->get('/admin/bookings/change-requests')
        ->assertOk()
        ->assertSee($booking->booking_reference)
        ->assertSee('Pending')
        ->assertSee('Approve')
        ->assertSee('Reject')
        ->assertSee('change-request-'.$changeRequest->id, false);
});

test('the customer sees the change request result on their booking page after it is processed', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'dates',
            'requested' => ['start_date' => '2026-10-20', 'end_date' => '2026-10-22'],
        ])
        ->assertRedirect();

    $changeRequest = BookingChangeRequest::query()->firstOrFail();

    $this->actingAs($user)
        ->get('/bookings/'.$booking->booking_reference)
        ->assertOk()
        ->assertSee('Your change requests')
        ->assertSee('Pending');

    $this->actingAs($admin)
        ->post('/admin/change-requests/'.$changeRequest->id.'/approve', ['note' => 'Enjoy your trip'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->get('/bookings/'.$booking->booking_reference)
        ->assertOk()
        ->assertSee('Approved')
        ->assertSee('2026-10-20')
        ->assertSee('Enjoy your trip');
});

test('a change result notification opens the customer booking page', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'travelers',
            'requested' => ['travelers' => 3],
        ])
        ->assertRedirect();

    $changeRequest = BookingChangeRequest::query()->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/change-requests/'.$changeRequest->id.'/reject', ['note' => 'Not possible'])
        ->assertRedirect();

    $notification = $user->notifications()->where('data->title', 'Change rejected')->firstOrFail();

    expect($notification->data['payload']['type'])->toBe('change_request')
        ->and($notification->data['payload']['change_request_id'])->toBe($changeRequest->id);

    $this->actingAs($user)
        ->get('/notifications/'.$notification->getKey().'/read')
        ->assertRedirect(route('bookings.show', $booking->booking_reference));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('a customer cannot process another customer change request', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($owner)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($owner)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'travelers',
            'requested' => ['travelers' => 3],
        ])
        ->assertRedirect();

    $changeRequest = BookingChangeRequest::query()->firstOrFail();

    $this->actingAs($intruder)
        ->post('/admin/change-requests/'.$changeRequest->id.'/approve')
        ->assertForbidden();

    $this->actingAs($intruder)
        ->post('/admin/change-requests/'.$changeRequest->id.'/reject')
        ->assertForbidden();

    expect($changeRequest->fresh()->status)->toBe('pending');
});

test('an approved service change request updates the booked service title', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'service',
            'requested' => ['service_title' => 'Premium Airport Transfer'],
        ])
        ->assertRedirect();

    $changeRequest = BookingChangeRequest::query()->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/change-requests/'.$changeRequest->id.'/approve')
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($booking->fresh()->service_title)->toBe('Premium Airport Transfer')
        ->and($changeRequest->fresh()->status)->toBe('approved');
});

test('an admin approving a change with new travelers updates the booking count', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', bookingTestPayload('vehicle', $vehicle, '2026-10-10', '2026-10-12', 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/change-requests', [
            'type' => 'travelers',
            'requested' => ['travelers' => 3],
        ])
        ->assertRedirect();

    $changeRequest = BookingChangeRequest::query()->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/change-requests/'.$changeRequest->id.'/approve')
        ->assertRedirect();

    expect($booking->fresh()->travelers)->toBe(3)
        ->and($changeRequest->fresh()->reviewed_by)->toBe($admin->id);
});
