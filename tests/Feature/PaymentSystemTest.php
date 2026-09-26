<?php

use App\Models\User;
use App\Services\Dashboard\DashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Models\Payment;
use Modules\Bookings\Services\BookingWorkflowService;
use Modules\Bookings\Services\PaymentCalculationService;
use Modules\Transport\Models\TransportVehicle;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['Bookings', 'Tours', 'Hotels', 'Transport'] as $module) {
        Artisan::call('module:migrate', ['module' => $module, '--force' => true]);
    }
    Artisan::call('module:seed', ['module' => 'Tours', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Hotels', '--force' => true]);
    Artisan::call('module:seed', ['module' => 'Transport', '--force' => true]);
});

function paymentTestPayload(string $type, $service, string $start, ?string $end, int $travelers, ?string $email = null): array
{
    return [
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
}

/**
 * Create a confirmed booking that has had payment requested, ready to receive
 * a customer payment report. The email distinguishes multiple bookings made by
 * the same customer in one test.
 */
function paymentReadyBooking($user, $admin, ?string $email = null, int $daysFromToday = 30): Booking
{
    $email ??= 'traveller@example.com';
    $vehicle = TransportVehicle::query()->first();
    $start = CarbonImmutable::now()->addDays($daysFromToday)->toDateString();
    $end = CarbonImmutable::now()->addDays($daysFromToday + 2)->toDateString();

    test()->actingAs($user)
        ->post('/bookings', paymentTestPayload('vehicle', $vehicle, $start, $end, 2, $email))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->where('email', $email)->firstOrFail();

    test()->actingAs($admin)->post('/admin/bookings/'.$booking->id.'/confirm')->assertRedirect();
    test()->actingAs($admin)->post('/admin/bookings/'.$booking->id.'/request-payment')->assertRedirect();

    return $booking->fresh();
}

test('a confirmed booking accepts a cash payment report without a reference and keeps it pending', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', paymentTestPayload('vehicle', $vehicle, CarbonImmutable::now()->addDays(10)->toDateString(), CarbonImmutable::now()->addDays(12)->toDateString(), 2, 'cash-customer@example.com'))
        ->assertRedirect();

    $booking = Booking::query()->where('email', 'cash-customer@example.com')->firstOrFail();
    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/confirm')
        ->assertRedirect();

    $booking->refresh();
    $amount = (string) round($booking->dueAmount(), 2);

    $this->actingAs($user)
        ->get(route('bookings.payment', $booking->booking_reference))
        ->assertOk()
        ->assertSee('Cash at Office');

    $this->post(route('bookings.payment.notify', $booking->booking_reference), [
        'method' => 'cash',
        'amount' => $amount,
    ])->assertRedirect(route('bookings.payment', $booking->booking_reference));

    $booking->refresh();
    $payment = $booking->payments()->sole();

    expect($payment->method)->toBe('cash')
        ->and($payment->reference)->toBeNull()
        ->and($payment->status)->toBe('pending')
        ->and((float) $payment->amount)->toBe((float) $amount)
        ->and($payment->recorded_by)->toBe($user->id)
        ->and($booking->status)->toBe('confirmed')
        ->and((float) $booking->paid_amount)->toBe(0.0);

    $notification = $admin->notifications()->where('data->title', 'Payment report')->firstOrFail();

    expect($notification->data['url'])->toBe(route('admin.payments.show', $payment));
});

test('payment summary switches required now from the advance remainder to the remaining balance', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin, 'phase-customer@example.com');
    $workflow = app(BookingWorkflowService::class);
    $calculation = app(PaymentCalculationService::class);
    $payment = $workflow->recordCustomerPayment(
        $booking,
        'bank_transfer',
        'PHASE-1',
        (string) $booking->advance_amount,
        $user,
    );
    $workflow->verifyPayment($payment, $admin);
    $booking->refresh();
    $summary = $calculation->summaryFor($booking);

    expect((float) $summary['required_now'])->toBe((float) $booking->dueAmount())
        ->and((float) $summary['remaining'])->toBe((float) $booking->dueAmount())
        ->and((float) $summary['advance_remaining'])->toBe(0.0)
        ->and((float) $summary['paid'])->toBe((float) $booking->paid_amount)
        ->and($calculation->typeFor($booking, (float) $booking->dueAmount()))->toBe('remaining');
});

test('an admin can correct and verify a payment with a new reference and receipt', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin, 'corrected-customer@example.com');
    $workflow = app(BookingWorkflowService::class);
    $payment = $workflow->recordCustomerPayment(
        $booking,
        'bank_transfer',
        'ORIGINAL-REFERENCE',
        (string) $booking->advance_amount,
        $user,
    );
    $correctedAmount = (string) round($booking->total_amount, 2);
    $receipt = UploadedFile::fake()->create('corrected-receipt.pdf', 100, 'application/pdf');

    $this->actingAs($admin)
        ->get(route('admin.payments.show', $payment))
        ->assertOk()
        ->assertSee('Payment evidence')
        ->assertSee('Awaiting verification');

    $this->actingAs($admin)
        ->post(route('admin.payments.verify', $payment), [
            'amount' => $correctedAmount,
            'reference' => 'CORRECTED-REFERENCE',
            'note' => 'Corrected after receipt review.',
            'receipt' => $receipt,
        ])
        ->assertRedirect(route('admin.payments.show', $payment))
        ->assertSessionHas('success');

    $payment->refresh();
    $booking->refresh();

    expect($payment->status)->toBe('paid')
        ->and((float) $payment->amount)->toBe((float) $correctedAmount)
        ->and($payment->method)->toBe('bank_transfer')
        ->and($payment->reference)->toBe('CORRECTED-REFERENCE')
        ->and($payment->note)->toContain('Corrected after receipt review.')
        ->and($payment->receipt_path)->not->toBeNull()
        ->and($payment->verified_by)->toBe($admin->id)
        ->and($payment->verified_at)->not->toBeNull()
        ->and($payment->payment_type)->toBe('full')
        ->and($booking->status)->toBe('paid')
        ->and((float) $booking->paid_amount)->toBe((float) $correctedAmount)
        ->and($booking->history()->where('action', 'payment_received')->count())->toBe(1);

    Storage::disk('local')->assertExists($payment->receipt_path);

    $notification = $user->notifications()->where('data->title', 'Payment successful')->firstOrFail();

    expect($notification->data['url'])->toBe(route('payments.show', $payment));
});

test('the admin dashboard counts pending evidence and uses settled outstanding totals', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $pendingBooking = paymentReadyBooking($user, $admin, 'pending-dashboard@example.com', 30);
    $verifiedBooking = paymentReadyBooking($user, $admin, 'verified-dashboard@example.com', 60);
    $workflow = app(BookingWorkflowService::class);

    $workflow->recordCustomerPayment(
        $pendingBooking,
        'cash',
        null,
        (string) $pendingBooking->advance_amount,
        $user,
    );

    $payment = $workflow->recordCustomerPayment(
        $verifiedBooking,
        'bank_transfer',
        'DASHBOARD-VERIFIED',
        (string) $verifiedBooking->advance_amount,
        $user,
    );
    $workflow->verifyPayment($payment, $admin);

    $stats = app(DashboardService::class)->paymentStats();
    $overview = app(DashboardService::class)->overview();
    $expectedOutstanding = (float) $pendingBooking->total_amount
        + (float) $verifiedBooking->total_amount
        - (float) $verifiedBooking->advance_amount;

    expect($stats['collected'])->toBe((float) $verifiedBooking->advance_amount)
        ->and($stats['net'])->toBe((float) $verifiedBooking->advance_amount)
        ->and($stats['outstanding'])->toBe($expectedOutstanding)
        ->and(Booking::query()->whereIn('status', Booking::RESERVING_STATUSES)->count())->toBe(2)
        ->and($overview['payments_pending'])->toBe(1);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Payments Pending Verification');
});

test('a booking snapshots the advance amount and due date from the payment rules at creation', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', paymentTestPayload('vehicle', $vehicle, CarbonImmutable::now()->addDays(30)->toDateString(), CarbonImmutable::now()->addDays(32)->toDateString(), 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    $service = new PaymentCalculationService;
    $expectedAdvance = $service->advanceFor($booking);

    expect((float) $booking->advance_amount)->toBe($expectedAdvance)
        ->and((float) $booking->advance_amount)->toBeGreaterThan(0)
        ->and($booking->payment_due_date?->toDateString())->toBe($booking->start_date->toDateString())
        ->and((float) $booking->dueAmount())->toBe((float) round((float) $booking->total_amount, 2));
});

test('a new booking redirects to its payment summary before admin confirmation', function () {
    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $response = $this->actingAs($user)
        ->post('/bookings', paymentTestPayload('vehicle', $vehicle, CarbonImmutable::now()->addDays(30)->toDateString(), CarbonImmutable::now()->addDays(32)->toDateString(), 2));

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();

    $response->assertRedirect(route('bookings.payment', $booking->booking_reference));

    $this->actingAs($user)
        ->get(route('bookings.payment', $booking->booking_reference))
        ->assertOk()
        ->assertSee('Waiting for admin confirmation')
        ->assertDontSee('I have paid &mdash; notify the agency', false);

    expect($booking->status)->toBe('pending');
});

test('an existing booking keeps its snapshotted payment plan after settings change', function () {
    BookingSetting::set('payment_rules', [
        'vehicle' => [
            'payment_mode' => 'percentage_advance',
            'advance_percentage' => 20,
            'advance_fixed_amount' => 0,
            'remaining_due_timing' => 'custom_deadline',
            'custom_deadline_days' => 7,
        ],
    ], 'payments');

    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', paymentTestPayload('vehicle', $vehicle, CarbonImmutable::now()->addDays(30)->toDateString(), CarbonImmutable::now()->addDays(32)->toDateString(), 2))
        ->assertRedirect();

    $booking = Booking::query()->where('user_id', $user->id)->firstOrFail();
    $advance = (float) $booking->advance_amount;
    $dueDate = $booking->payment_due_date->format('M d, Y');

    BookingSetting::set('payment_rules', [
        'vehicle' => [
            'payment_mode' => 'full_payment',
            'advance_percentage' => 0,
            'advance_fixed_amount' => 0,
            'remaining_due_timing' => 'custom_deadline',
            'custom_deadline_days' => 1,
        ],
    ], 'payments');

    $this->actingAs($user)
        ->get(route('bookings.payment', $booking->booking_reference))
        ->assertOk()
        ->assertSee(number_format($advance, 2))
        ->assertSee($dueDate);
});

test('a first payment cannot be less than the required advance', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);
    $amount = max(0.01, round((float) $booking->advance_amount - 1, 2));

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-SHORT-1',
            'amount' => number_format($amount, 2, '.', ''),
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('payment');

    expect(Payment::query()->count())->toBe(0);
});

test('a first payment covering the whole total is recorded as a full payment', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $due = (string) round($booking->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-FULL-1',
            'amount' => $due,
        ])
        ->assertRedirect();

    $payment = $booking->payments()->firstOrFail();

    expect($payment->payment_type)->toBe('full')
        ->and($payment->user_id)->toBe($user->id);

    $this->actingAs($admin)->post('/admin/payments/'.$payment->id.'/verify')->assertRedirect();

    expect($booking->fresh()->status)->toBe('paid')
        ->and((float) $booking->fresh()->paid_amount)->toBe((float) $booking->fresh()->total_amount);
});

test('an advance payment leaves the booking awaiting the remaining balance', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $service = new PaymentCalculationService;
    $advance = (string) round($service->advanceFor($booking), 2);
    $total = (float) $booking->total_amount;

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'khalti',
            'reference' => 'TRX-ADV-1',
            'amount' => $advance,
        ])
        ->assertRedirect();

    $payment = $booking->payments()->firstOrFail();
    expect($payment->payment_type)->toBe('advance');

    $this->actingAs($admin)->post('/admin/payments/'.$payment->id.'/verify')->assertRedirect();

    $booking = $booking->fresh();

    expect($booking->status)->toBe('payment_pending')
        ->and((float) $booking->paid_amount)->toBe((float) round((float) $advance, 2))
        ->and($booking->payment_status)->toBe('partially_paid')
        ->and((float) $booking->dueAmount())->toBe((float) round($total - (float) $advance, 2));
});

test('a pending advance report is not counted as paid and only moves the balance once verified', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $calculation = app(PaymentCalculationService::class);
    $total = (float) $booking->total_amount;
    $advance = round($calculation->advanceFor($booking), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'esewa',
            'reference' => 'TRX-PENDING-ADV',
            'amount' => number_format($advance, 2, '.', ''),
        ])
        ->assertRedirect();

    $payment = $booking->payments()->sole();

    expect($payment->status)->toBe('pending')
        ->and($payment->payment_type)->toBe('advance')
        ->and($payment->receipt_path)->toBeNull();

    $pending = $calculation->summaryFor($booking->fresh());

    expect((float) $pending['paid'])->toBe(0.0)
        ->and((float) $pending['remaining'])->toBe($total)
        ->and((float) $pending['advance_remaining'])->toBe($advance)
        ->and($booking->fresh()->payment_status)->toBe('payment_pending');

    $this->actingAs($admin)->post('/admin/payments/'.$payment->id.'/verify')->assertRedirect();

    $verified = $calculation->summaryFor($booking->fresh());

    expect((float) $verified['paid'])->toBe($advance)
        ->and((float) $verified['remaining'])->toBe(round($total - $advance, 2))
        ->and((float) $verified['advance_remaining'])->toBe(0.0)
        ->and((float) $booking->fresh()->paid_amount)->toBe($advance)
        ->and($booking->fresh()->payment_status)->toBe('partially_paid');
});

test('the booking page offers pay remaining with the outstanding amount and hides it once fully paid', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);
    $calculation = app(PaymentCalculationService::class);
    $advance = round($calculation->advanceFor($booking), 2);
    $total = (float) $booking->total_amount;

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'khalti',
            'reference' => 'TRX-REMAIN-1',
            'amount' => number_format($advance, 2, '.', ''),
        ])->assertRedirect();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$booking->payments()->sole()->id.'/verify')
        ->assertRedirect();

    $booking->refresh();
    $outstanding = round($total - $advance, 2);

    $this->actingAs($user)
        ->get(route('bookings.show', $booking->booking_reference))
        ->assertOk()
        ->assertSee('Payment summary')
        ->assertDontSee('Fully Paid')
        ->assertSee('Pay Remaining '.$booking->currency.' '.number_format($outstanding, 2));

    $this->actingAs($user)
        ->get(route('bookings.payment', $booking->booking_reference))
        ->assertOk()
        ->assertDontSee('Payment evidence awaiting verification', false);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'esewa',
            'reference' => 'TRX-REMAIN-2',
            'amount' => number_format($outstanding, 2, '.', ''),
        ])->assertRedirect();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$booking->payments()->latest('id')->firstOrFail()->id.'/verify')
        ->assertRedirect();

    $booking->refresh();

    expect((float) $booking->paid_amount)->toBe($total)
        ->and($booking->status)->toBe('paid')
        ->and($booking->payment_status)->toBe('paid')
        ->and($booking->payments()->count())->toBe(2);

    $this->actingAs($user)
        ->get(route('bookings.show', $booking->booking_reference))
        ->assertOk()
        ->assertSee('Fully Paid')
        ->assertDontSee('Pay Remaining');

    $this->actingAs($user)
        ->get(route('bookings.payment', $booking->booking_reference))
        ->assertOk()
        ->assertSee('no payment remaining', false)
        ->assertDontSee('Waiting for admin confirmation');
});

test('a partial payment against the remaining balance is accepted and leaves the rest payable', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);
    $calculation = app(PaymentCalculationService::class);
    $advance = round($calculation->advanceFor($booking), 2);
    $total = (float) $booking->total_amount;
    $partial = 10.0;

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'cash',
            'amount' => number_format($advance, 2, '.', ''),
        ])->assertRedirect();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$booking->payments()->sole()->id.'/verify')
        ->assertRedirect();

    $booking->refresh();

    $this->actingAs($user)
        ->get(route('bookings.payment', $booking->booking_reference))
        ->assertOk()
        ->assertSee('value="'.number_format($total - $advance, 2, '.', '').'"', false)
        ->assertSee('min="0.01"', false);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-PARTIAL-1',
            'amount' => number_format($partial, 2, '.', ''),
        ])->assertRedirect();

    $partialPayment = $booking->payments()->latest('id')->firstOrFail();

    expect($partialPayment->payment_type)->toBe('partial')
        ->and($partialPayment->status)->toBe('pending');

    $this->actingAs($admin)
        ->post('/admin/payments/'.$partialPayment->id.'/verify')
        ->assertRedirect();

    $booking->refresh();
    $stillOutstanding = round($total - $advance - $partial, 2);

    expect((float) $booking->paid_amount)->toBe(round($advance + $partial, 2))
        ->and($booking->status)->toBe('payment_pending')
        ->and($booking->payments()->count())->toBe(2);

    $this->actingAs($user)
        ->get(route('bookings.show', $booking->booking_reference))
        ->assertOk()
        ->assertSee('Pay Remaining '.$booking->currency.' '.number_format($stillOutstanding, 2));
});

test('a remaining payment cannot exceed the outstanding balance or settle another customer booking', function () {
    $user = User::factory()->create();
    $intruder = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);
    $calculation = app(PaymentCalculationService::class);
    $advance = round($calculation->advanceFor($booking), 2);
    $total = (float) $booking->total_amount;

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'cash',
            'amount' => number_format($advance, 2, '.', ''),
        ])->assertRedirect();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$booking->payments()->sole()->id.'/verify')
        ->assertRedirect();

    $booking->refresh();
    $outstanding = round($total - $advance, 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-OVER-1',
            'amount' => number_format($outstanding + 1, 2, '.', ''),
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('amount');

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-ZERO-1',
            'amount' => '0',
        ])
        ->assertSessionHasErrors('amount');

    expect($booking->payments()->count())->toBe(1);

    // store() seeds "booking_email" into the session, and the session survives
    // across requests in a test, so drop it to emulate a genuinely separate
    // visitor with no ownership and no validated email.
    $this->flushSession();

    $this->actingAs($intruder)
        ->get(route('bookings.payment', $booking->booking_reference))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-INTRUDER-1',
            'amount' => number_format($outstanding, 2, '.', ''),
        ])
        ->assertForbidden();

    expect($booking->payments()->count())->toBe(1);
});

test('the booking page reads the outstanding balance from the database on every request', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);
    $calculation = app(PaymentCalculationService::class);
    $advance = round($calculation->advanceFor($booking), 2);
    $total = (float) $booking->total_amount;

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'cash',
            'amount' => number_format($advance, 2, '.', ''),
        ])->assertRedirect();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$booking->payments()->sole()->id.'/verify')
        ->assertRedirect();

    $outstanding = round($total - $advance, 2);

    $this->actingAs($user)
        ->get(route('bookings.show', $booking->booking_reference))
        ->assertOk()
        ->assertSee('Pay Remaining '.$booking->currency.' '.number_format($outstanding, 2));

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'esewa',
            'reference' => 'TRX-SESSION-B',
            'amount' => number_format($outstanding, 2, '.', ''),
        ])->assertRedirect();

    $this->actingAs($user)
        ->get(route('bookings.show', $booking->booking_reference))
        ->assertOk()
        ->assertSee('You have a payment awaiting verification.', false)
        ->assertDontSee('Pay Remaining')
        ->assertSee('View payment status')
        ->assertSee($booking->currency.' '.number_format($outstanding, 2), false);

    $adminUser = User::factory()->create(['is_admin' => true]);

    $this->actingAs($adminUser)
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee($booking->booking_reference);
});

test('the customer payment detail page offers pay remaining and the same totals as the booking page', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);
    $calculation = app(PaymentCalculationService::class);
    $advance = round($calculation->advanceFor($booking), 2);
    $total = (float) $booking->total_amount;
    $outstanding = round($total - $advance, 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'cash',
            'amount' => number_format($advance, 2, '.', ''),
        ])->assertRedirect();

    $payment = $booking->payments()->sole();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$payment->id.'/verify')
        ->assertRedirect();

    $booking->refresh();

    $this->actingAs($user)
        ->get(route('payments.show', $payment))
        ->assertOk()
        ->assertSee('Payment #'.$payment->id)
        ->assertSee($booking->service_title)
        ->assertSee('Advance required')
        ->assertSee('Verified paid')
        ->assertSee('Payment history for this booking')
        // Advance required and verified paid are shown separately so paying more
        // than the advance never looks like a mistake.
        ->assertSee($booking->currency.' '.number_format($advance, 2), false)
        ->assertSee($booking->currency.' '.number_format($outstanding, 2), false)
        ->assertSee('Pay Remaining '.$booking->currency.' '.number_format($outstanding, 2))
        // Reuses the one existing customer payment flow, reached from the
        // booking reference already resolved server-side.
        ->assertSee('href="'.route('bookings.payment', $booking->booking_reference).'"', false);

    // Requirement: both entry points report identical figures for one booking.
    $this->actingAs($user)
        ->get(route('bookings.show', $booking->booking_reference))
        ->assertOk()
        ->assertSee('Pay Remaining '.$booking->currency.' '.number_format($outstanding, 2));
});

test('the customer payment detail page hides pay remaining once the booking is fully paid', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);
    $calculation = app(PaymentCalculationService::class);
    $total = (float) $booking->total_amount;

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'cash',
            'amount' => number_format($total, 2, '.', ''),
        ])->assertRedirect();

    $payment = $booking->payments()->sole();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$payment->id.'/verify')
        ->assertRedirect();

    $booking->refresh();

    expect((float) $booking->paid_amount)->toBe($total)
        ->and($booking->payment_status)->toBe('paid');

    $this->actingAs($user)
        ->get(route('payments.show', $payment))
        ->assertOk()
        ->assertSee('Fully Paid')
        ->assertSee('no payment remaining', false)
        ->assertDontSee('Pay Remaining');
});

test('a pending payment never reduces the outstanding balance on the payment detail page', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);
    $calculation = app(PaymentCalculationService::class);
    $advance = round($calculation->advanceFor($booking), 2);
    $total = (float) $booking->total_amount;
    $outstanding = round($total - $advance, 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'cash',
            'amount' => number_format($advance, 2, '.', ''),
        ])->assertRedirect();

    $payment = $booking->payments()->sole();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$payment->id.'/verify')
        ->assertRedirect();

    // A second report that covers the whole remaining balance is still pending.
    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'esewa',
            'reference' => 'TRX-DETAIL-PENDING',
            'amount' => number_format($outstanding, 2, '.', ''),
        ])->assertRedirect();

    $booking->refresh();
    $pending = $booking->payments()->where('status', 'pending')->sole();

    // Outstanding is unchanged, and the pending row is visible but not counted.
    // The action becomes "View payment status" rather than a second Pay button,
    // because the workflow rejects a new report while one is still pending.
    $this->actingAs($user)
        ->get(route('payments.show', $payment))
        ->assertOk()
        ->assertSee('Payment awaiting verification')
        ->assertSee($pending->reference)
        ->assertSee($booking->currency.' '.number_format($outstanding, 2), false)
        ->assertSee('View payment status')
        ->assertDontSee('Pay Remaining');

    // Once verified the very same page reflects the new balance with no restart.
    $this->actingAs($admin)
        ->post('/admin/payments/'.$pending->id.'/verify')
        ->assertRedirect();

    $booking->refresh();

    expect((float) $booking->paid_amount)->toBe($total)
        ->and($booking->payment_status)->toBe('paid');

    $this->actingAs($user)
        ->get(route('payments.show', $payment))
        ->assertOk()
        ->assertSee('Fully Paid')
        ->assertDontSee('Pay Remaining');
});

test('a partial payment updates the pay remaining amount on the payment detail page', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);
    $calculation = app(PaymentCalculationService::class);
    $advance = round($calculation->advanceFor($booking), 2);
    $total = (float) $booking->total_amount;
    $partial = 10.0;

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'cash',
            'amount' => number_format($advance, 2, '.', ''),
        ])->assertRedirect();

    $payment = $booking->payments()->sole();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$payment->id.'/verify')
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-DETAIL-PARTIAL',
            'amount' => number_format($partial, 2, '.', ''),
        ])->assertRedirect();

    $partialPayment = $booking->payments()->where('status', 'pending')->sole();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$partialPayment->id.'/verify')
        ->assertRedirect();

    $booking->refresh();
    $stillOutstanding = round($total - $advance - $partial, 2);

    expect((float) $booking->paid_amount)->toBe(round($advance + $partial, 2));

    $this->actingAs($user)
        ->get(route('payments.show', $payment))
        ->assertOk()
        ->assertSee('Pay Remaining '.$booking->currency.' '.number_format($stillOutstanding, 2))
        ->assertDontSee(number_format($total - $advance, 2).'</td>');
});

test('a customer cannot open another customer payment detail page', function () {
    $user = User::factory()->create();
    $intruder = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);
    $calculation = app(PaymentCalculationService::class);
    $advance = round($calculation->advanceFor($booking), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'cash',
            'amount' => number_format($advance, 2, '.', ''),
        ])->assertRedirect();

    $payment = $booking->payments()->sole();

    // store() seeds booking_email into the session, and the session survives
    // across requests in a test, so drop it to emulate a separate visitor.
    $this->flushSession();

    $this->actingAs($intruder)
        ->get(route('payments.show', $payment))
        ->assertNotFound();

    $this->actingAs($intruder)
        ->get(route('payments.receipt', $payment))
        ->assertNotFound();

    // The Pay Remaining action resolves the booking from the payment server-side,
    // so a borrowed payment id cannot be used to reach the payment form either.
    $this->actingAs($intruder)
        ->get(route('bookings.payment', $booking->booking_reference))
        ->assertForbidden();

    expect($booking->payments()->count())->toBe(1);
});

test('the payment that clears the remaining balance after an advance is a remaining payment', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $service = new PaymentCalculationService;
    $advance = (string) round($service->advanceFor($booking), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'khalti',
            'reference' => 'TRX-ADV-2',
            'amount' => $advance,
        ])->assertRedirect();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$booking->payments()->firstOrFail()->id.'/verify')
        ->assertRedirect();

    $remaining = (string) round($booking->fresh()->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-REM-2',
            'amount' => $remaining,
        ])
        ->assertRedirect();

    $payment = $booking->payments()->where('reference', 'TRX-REM-2')->firstOrFail();
    expect($payment->payment_type)->toBe('remaining');

    $this->actingAs($admin)
        ->post('/admin/payments/'.$payment->id.'/verify')
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('paid')
        ->and((float) $booking->fresh()->dueAmount())->toBe(0.0);
});

test('a customer cannot report more than the outstanding balance', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $over = number_format($booking->dueAmount() + 100, 2, '.', '');

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-OVER-1',
            'amount' => $over,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('amount');

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-OVER-2',
            'amount' => '-50',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('amount');

    expect(Payment::query()->count())->toBe(0);
});

test('a transaction reference on record cannot be claimed again on another booking', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $bookingA = paymentReadyBooking($user, $admin);
    $bookingB = paymentReadyBooking($user, $admin, 'second@example.com', 50);

    $dueA = (string) round($bookingA->dueAmount(), 2);
    $dueB = (string) round($bookingB->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$bookingA->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-DUP-1',
            'amount' => $dueA,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/bookings/'.$bookingB->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-DUP-1',
            'amount' => $dueB,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('payment');

    expect(Payment::query()->count())->toBe(1);
});

test('a customer payment retains its note and private receipt evidence', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $intruder = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);
    $receipt = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-RECEIPT-1',
            'amount' => number_format($booking->dueAmount(), 2, '.', ''),
            'message' => 'Paid from the joint account.',
            'receipt' => $receipt,
        ])
        ->assertRedirect(route('bookings.payment', $booking->booking_reference));

    $payment = $booking->payments()->firstOrFail();

    expect($payment->note)->toContain('Paid from the joint account.')
        ->and($payment->receipt_path)->not->toBeNull();

    Storage::disk('local')->assertExists($payment->receipt_path);

    $this->actingAs($user)->get(route('payments.receipt', $payment))->assertOk();
    $this->actingAs($intruder)->get(route('payments.receipt', $payment))->assertNotFound();
    $this->actingAs($admin)->get(route('admin.payments.receipt', $payment))->assertOk();
});

test('a customer can list and open their own payment records', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $due = (string) round($booking->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'esewa',
            'reference' => 'TRX-MINE-1',
            'amount' => $due,
        ])
        ->assertRedirect();

    $payment = $booking->payments()->firstOrFail();

    $this->actingAs($user)
        ->get('/payments')
        ->assertOk()
        ->assertSee('#'.$payment->id);

    $this->actingAs($user)
        ->get('/payments/'.$payment->id)
        ->assertOk()
        ->assertSee('Payment details')
        ->assertSee($booking->booking_reference);
});

test('a customer cannot open another customer payment record', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($owner, $admin);

    $due = (string) round($booking->dueAmount(), 2);

    $this->actingAs($owner)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-PRIV-1',
            'amount' => $due,
        ])
        ->assertRedirect();

    $payment = $booking->payments()->firstOrFail();

    $this->actingAs($intruder)
        ->get('/payments/'.$payment->id)
        ->assertStatus(404);

    expect($payment->fresh()->status)->toBe('pending');
});

test('the customer payment history only lists their own bookings payments', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $ownerBooking = paymentReadyBooking($owner, $admin);
    $due = (string) round($ownerBooking->dueAmount(), 2);

    $this->actingAs($owner)
        ->post('/bookings/'.$ownerBooking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-LIST-1',
            'amount' => $due,
        ])
        ->assertRedirect();

    $this->flushSession();
    auth()->logout();

    $this->actingAs($intruder)
        ->get('/payments')
        ->assertOk()
        ->assertSee('No payments found', false)
        ->assertDontSee($ownerBooking->booking_reference);
});

test('admin payment rules can be changed and new pricing uses them', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/booking-settings')
        ->assertOk()
        ->assertSee('Per-Service Payment Rules');

    $defaults = BookingSetting::DEFAULTS;

    $payload = [
        'currency' => 'NPR',
        'tax_rate' => 13,
        'service_charge_rate' => 0,
        'booking_approval_required' => 1,
        'payment_deadline_hours' => 48,
        'max_booking_horizon' => 365,
        'max_hotel_stay' => 30,
        'max_rental_days' => 30,
        'rental_price_unit_limit_days' => 14,
    ];

    foreach ($defaults['cancellation_policy'] as $i => $tier) {
        $payload['cancellation_policy'][$i]['days'] = $tier['days'];
        $payload['cancellation_policy'][$i]['refund'] = $tier['refund'];
    }

    foreach ($defaults['payment_methods'] as $method) {
        $payload['payment_methods'][] = $method['method'];
    }

    $payload['payment_rules'] = [
        'tour' => ['payment_mode' => 'percentage_advance', 'advance_percentage' => 50, 'advance_fixed_amount' => 0, 'remaining_due_timing' => 'custom_deadline', 'custom_deadline_days' => 5],
        'hotel' => ['payment_mode' => 'fixed_advance', 'advance_percentage' => 0, 'advance_fixed_amount' => 1000, 'remaining_due_timing' => 'before_service', 'custom_deadline_days' => 7],
        'vehicle' => ['payment_mode' => 'full_payment', 'advance_percentage' => 0, 'advance_fixed_amount' => 0, 'remaining_due_timing' => 'before_service', 'custom_deadline_days' => 7],
    ];

    $this->actingAs($admin)
        ->put('/admin/booking-settings', $payload)
        ->assertRedirect()
        ->assertSessionHas('success');

    $rules = BookingSetting::getWithDefault('payment_rules');

    expect((float) $rules['tour']['advance_percentage'])->toBe(50.0)
        ->and($rules['tour']['remaining_due_timing'])->toBe('custom_deadline')
        ->and($rules['hotel']['payment_mode'])->toBe('fixed_advance')
        ->and($rules['vehicle']['payment_mode'])->toBe('full_payment');

    unset($payload['payment_methods']);

    $this->actingAs($admin)
        ->put('/admin/booking-settings', $payload)
        ->assertSessionHasErrors('payment_methods');
});

test('the payment rules are applied to new bookings after the settings change', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    BookingSetting::set('payment_rules', [
        'tour' => ['payment_mode' => 'percentage_advance', 'advance_percentage' => 50, 'advance_fixed_amount' => 0, 'remaining_due_timing' => 'custom_deadline', 'custom_deadline_days' => 5],
        'hotel' => ['payment_mode' => 'fixed_advance', 'advance_percentage' => 0, 'advance_fixed_amount' => 1000, 'remaining_due_timing' => 'before_service', 'custom_deadline_days' => 7],
        'vehicle' => ['payment_mode' => 'full_payment', 'advance_percentage' => 0, 'advance_fixed_amount' => 0, 'remaining_due_timing' => 'before_service', 'custom_deadline_days' => 7],
    ], 'payments');

    $user = User::factory()->create();
    $vehicle = TransportVehicle::query()->first();

    $this->actingAs($user)
        ->post('/bookings', paymentTestPayload('vehicle', $vehicle, CarbonImmutable::now()->addDays(30)->toDateString(), CarbonImmutable::now()->addDays(32)->toDateString(), 2))
        ->assertRedirect();

    $booking = Booking::query()->where('booking_type', 'vehicle')->firstOrFail();

    expect((float) $booking->advance_amount)->toBe((float) round((float) $booking->total_amount, 2))
        ->and($booking->payment_due_date?->toDateString())->toBe($booking->start_date->toDateString());

    BookingSetting::flush();
});

test('unconfigured service types fall back to a safe default advance', function () {
    BookingSetting::set('payment_rules', [
        'hotel' => ['payment_mode' => 'fixed_advance', 'advance_percentage' => 0, 'advance_fixed_amount' => 1000, 'remaining_due_timing' => 'before_service', 'custom_deadline_days' => 7],
    ], 'payments');

    $service = new PaymentCalculationService;
    $rules = $service->rulesFor('vehicle');

    expect($rules['configured'])->toBeFalse()
        ->and($rules['payment_mode'])->toBe('percentage_advance')
        ->and($rules['advance_percentage'])->toBe(30.0);

    BookingSetting::flush();
});

test('a cancelled booking with a pending refund shows refund pending, fully processed shows refunded', function () {
    BookingSetting::set('cancellation_policy', [['days' => 10, 'refund' => 100]]);
    BookingSetting::flush();

    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $due = (string) round($booking->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-REFUND-1',
            'amount' => $due,
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$booking->payments()->firstOrFail()->id.'/verify')
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('paid');

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/cancel')
        ->assertRedirect();

    $booking = $booking->fresh();

    expect($booking->status)->toBe('cancelled')
        ->and($booking->refunds()->where('status', 'pending')->exists())->toBeTrue()
        ->and($booking->payment_status)->toBe('refund_pending');

    $refund = $booking->refunds()->firstOrFail();

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/process-refund', ['method' => 'bank_transfer', 'reference' => 'REF-1'])
        ->assertRedirect();

    expect($refund->fresh()->status)->toBe('processed')
        ->and($booking->fresh()->payment_status)->toBe('refunded');
});

test('an admin can manually record a full payment for a payment pending booking', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $due = (string) round($booking->dueAmount(), 2);

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/mark-paid', [
            'amount' => $due,
            'method' => 'cash',
            'reference' => 'CASH-1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $payment = $booking->payments()->firstOrFail();
    expect($payment->status)->toBe('paid')
        ->and($payment->payment_type)->toBe('full')
        ->and($payment->user_id)->toBe($user->id)
        ->and($booking->fresh()->status)->toBe('paid');
});

test('payment reports above the outstanding balance are rejected', function () {
    BookingSetting::set('payment_overpayment_policy', 'record');

    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);
    $amount = $booking->dueAmount() + 100;

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-OVERPAY-1',
            'amount' => number_format($amount, 2, '.', ''),
        ])
        ->assertRedirect(route('bookings.payment', $booking->booking_reference))
        ->assertSessionHasErrors(['amount']);

    expect($booking->payments()->count())->toBe(0);
});

test('admin payments can be filtered by booking date method and status', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $bookingA = paymentReadyBooking($user, $admin, 'first@example.com', 30);
    $bookingB = paymentReadyBooking($user, $admin, 'second@example.com', 50);

    $this->actingAs($user)
        ->post('/bookings/'.$bookingA->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-FILTER-A',
            'amount' => number_format($bookingA->dueAmount(), 2, '.', ''),
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post('/bookings/'.$bookingB->booking_reference.'/payment/notify', [
            'method' => 'esewa',
            'reference' => 'TRX-FILTER-B',
            'amount' => number_format($bookingB->dueAmount(), 2, '.', ''),
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('admin.bookings.payments.index', [
            'booking' => $bookingB->booking_reference,
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
            'method' => 'esewa',
            'status' => 'pending',
        ]))
        ->assertOk()
        ->assertSee($bookingB->booking_reference)
        ->assertDontSee($bookingA->booking_reference);
});

test('the admin payment list and detail page resolve customer and service from the booking', function () {
    $user = User::factory()->create(['name' => 'Sita Gurung']);
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin, 'sita@example.com');
    $vehicle = TransportVehicle::query()->findOrFail($booking->service_id);

    expect($booking->service_admin_url)->toBe(route('admin.transport.edit', $vehicle->id));

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'khalti',
            'reference' => 'TRX-CONTEXT-1',
            'amount' => number_format((float) $booking->advance_amount, 2, '.', ''),
        ])
        ->assertRedirect();

    $payment = $booking->payments()->sole();

    $this->actingAs($admin)
        ->get(route('admin.bookings.payments.index'))
        ->assertOk()
        ->assertSee('#'.$payment->id)
        ->assertSee($booking->booking_reference)
        ->assertSee($booking->name)
        ->assertSee('sita@example.com')
        ->assertSee('Sita Gurung')
        ->assertSee($booking->service_title)
        ->assertSee($booking->booking_type_label)
        ->assertSee($vehicle->name)
        ->assertSee($booking->service_admin_url);

    $this->actingAs($admin)
        ->get(route('admin.payments.show', $payment))
        ->assertOk()
        ->assertSee('Payment context')
        ->assertSee($booking->name)
        ->assertSee('Sita Gurung')
        ->assertSee($booking->service_title)
        ->assertSee($booking->booking_type_label);
});

test('a failed payment cannot later be verified', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-FAILED-1',
            'amount' => number_format($booking->dueAmount(), 2, '.', ''),
        ])
        ->assertRedirect();

    $payment = $booking->payments()->firstOrFail();

    $this->actingAs($admin)->post('/admin/payments/'.$payment->id.'/fail')->assertRedirect();
    $this->actingAs($admin)->post('/admin/payments/'.$payment->id.'/verify')->assertStatus(422);

    expect($payment->fresh()->status)->toBe('failed')
        ->and($booking->fresh()->status)->toBe('payment_pending')
        ->and((float) $booking->fresh()->paid_amount)->toBe(0.0)
        ->and($booking->fresh()->history()->where('action', 'payment_received')->exists())->toBeFalse()
        ->and($user->notifications()->where('data->title', 'Payment successful')->exists())->toBeFalse();

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-RETRY-1',
            'amount' => number_format($booking->fresh()->dueAmount(), 2, '.', ''),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($booking->payments()->count())->toBe(2)
        ->and($booking->payments()->where('status', 'pending')->exists())->toBeTrue();
});

test('gateway metadata is stored for future payment gateway integration', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $service = app(BookingWorkflowService::class);
    $payment = $service->recordCustomerPayment(
        $booking,
        'esewa',
        'ESEWA-FUTURE-1',
        (string) round($booking->dueAmount(), 2),
        $user,
        'esewa',
        ['transaction_id' => 'ESEWA-FUTURE-1', 'verified' => false]
    );

    expect($payment->gateway)->toBe('esewa')
        ->and($payment->gateway_response)->toBe(['transaction_id' => 'ESEWA-FUTURE-1', 'verified' => false])
        ->and($payment->user_id)->toBe($user->id);
});

test('fonepay references remain unverified evidence without a gateway callback', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $this->actingAs($admin)
        ->get(route('admin.bookings.settings.index'))
        ->assertOk()
        ->assertSee('Fonepay');

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'fonepay',
            'reference' => 'FONEPAY-REF-1',
            'amount' => number_format($booking->dueAmount(), 2, '.', ''),
        ])
        ->assertRedirect();

    $payment = $booking->payments()->firstOrFail();

    expect($payment->method)->toBe('fonepay')
        ->and($payment->gateway)->toBeNull()
        ->and($payment->status)->toBe('pending')
        ->and((float) $booking->fresh()->paid_amount)->toBe(0.0)
        ->and($user->notifications()->where('data->title', 'Payment successful')->exists())->toBeFalse();
});

test('a payment method outside the configured allowlist cannot be reported', function () {
    BookingSetting::set('payment_methods', [
        ['method' => 'bank_transfer', 'label' => 'Bank Transfer', 'details' => 'Account details'],
    ]);
    BookingSetting::flush();

    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $due = (string) round($booking->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bitcoin',
            'reference' => 'TRX-BAD-1',
            'amount' => $due,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('payment');

    expect(Payment::query()->count())->toBe(0);

    BookingSetting::flush();
});

test('two payment reports at almost the same time create only one pending payment', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $workflow = app(BookingWorkflowService::class);
    $due = (string) round($booking->dueAmount(), 2);

    $workflow->recordCustomerPayment($booking, 'bank_transfer', 'TRX-RACE-1', $due, $user);

    expect(fn () => $workflow->recordCustomerPayment($booking, 'bank_transfer', 'TRX-RACE-2', $due, $user))
        ->toThrow(HttpException::class);

    expect($booking->fresh()->payments()->where('status', 'pending')->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(1);
});

test('verifying an already verified payment never changes the settled amount', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $due = (string) round($booking->dueAmount(), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-IDEM-1',
            'amount' => $due,
        ])
        ->assertRedirect();

    $payment = $booking->payments()->firstOrFail();

    $this->actingAs($admin)->post('/admin/payments/'.$payment->id.'/verify')->assertRedirect();
    $this->actingAs($admin)->post('/admin/payments/'.$payment->id.'/verify')->assertRedirect();

    expect($payment->fresh()->status)->toBe('paid')
        ->and((float) $booking->fresh()->paid_amount)->toBe((float) round((float) $booking->total_amount, 2))
        ->and($booking->fresh()->history()->where('action', 'payment_received')->count())->toBe(1)
        ->and($user->notifications()->where('data->title', 'Payment successful')->count())->toBe(1);
});

test('a refund can never exceed the amount actually collected', function () {
    BookingSetting::set('cancellation_policy', [['days' => 10, 'refund' => 100]]);
    BookingSetting::flush();

    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $booking = paymentReadyBooking($user, $admin);

    $service = new PaymentCalculationService;
    $advance = (string) round($service->advanceFor($booking), 2);

    $this->actingAs($user)
        ->post('/bookings/'.$booking->booking_reference.'/payment/notify', [
            'method' => 'bank_transfer',
            'reference' => 'TRX-CAP-1',
            'amount' => $advance,
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post('/admin/payments/'.$booking->payments()->firstOrFail()->id.'/verify')
        ->assertRedirect();

    $booking = $booking->fresh();

    expect((float) $booking->paid_amount)->toBe((float) $advance)
        ->and((float) $booking->paid_amount)->toBeLessThan((float) $booking->total_amount);

    $this->actingAs($admin)
        ->post('/admin/bookings/'.$booking->id.'/cancel')
        ->assertRedirect();

    $booking = $booking->fresh();

    $refund = $booking->refunds()->firstOrFail();
    expect($booking->status)->toBe('cancelled')
        ->and((float) $refund->amount)->toBe((float) $booking->paid_amount)
        ->and((float) $refund->amount)->toBeLessThan((float) $booking->total_amount);

    BookingSetting::flush();
});
