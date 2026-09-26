<?php

namespace Modules\Bookings\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingChangeRequest;
use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Models\Payment;
use Modules\Bookings\Models\Refund;
use Modules\Bookings\Notifications\BookingNotification;
use Modules\Bookings\Support\Money;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;
use Throwable;

/**
 * The single place that performs booking lifecycle operations.
 *
 * Every mutation passes through here so that status transitions are validated,
 * the audit trail is always appended, the payment ledger stays consistent,
 * and customers are notified. Callers never mutate a booking directly.
 */
class BookingWorkflowService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{booking: Booking, payment: Payment|null}
     */
    public function createBooking(array $data): array
    {
        return \DB::transaction(function () use ($data) {
            [$type, $service] = $this->resolveService($data['booking_type'], $data['service_id'], true);

            abort_unless($service !== null, 422, 'The selected service is not available.');

            $start = $data['start_date'] ?? null;
            $end = $data['end_date'] ?? null;
            $travelers = isset($data['travelers']) ? (int) $data['travelers'] : null;

            if ($type === 'tour' && $end === null) {
                $end = CarbonImmutable::parse($start)->addDay()->toDateString();
            }

            $availability = (new AvailabilityService)->check(
                $type,
                (int) $data['service_id'],
                (string) $start,
                $end,
                null,
                $travelers,
                true
            );

            abort_unless($availability['available'], 422, $availability['reason'] ?? 'This service is not available for the selected dates.');

            $quote = (new PriceCalculator)->quote($type, $service, $start, $end, $travelers);

            $payment = null;

            $booking = Booking::query()->create(
                array_merge($data, [
                    'status' => 'pending',
                    'end_date' => $end,
                    'amount' => $quote['total'],
                    'base_price' => $quote['subtotal'],
                    'quantity' => $quote['quantity'],
                    'tax_rate' => $quote['tax_rate'],
                    'service_charge_rate' => $quote['service_charge_rate'],
                    'tax_amount' => $quote['tax_amount'],
                    'service_charge' => $quote['service_charge'],
                    'discount' => $quote['discount'],
                    'total_amount' => $quote['total'],
                    'currency' => $quote['currency'],
                    'paid_amount' => '0.00',
                    'policy_snapshot' => $this->policySnapshot((string) $data['booking_type']),
                ])
            );

            $booking->forceFill(['booking_reference' => $this->referenceFor($booking)])->saveQuietly();
            $booking->expires_at = now()->addHours((int) BookingSetting::getWithDefault('payment_deadline_hours'));
            $booking->saveQuietly();

            $this->snapshotPaymentPlan($booking);

            $this->recordHistory($booking, 'created', null, 'pending', 'customer', null, ['reference' => $booking->booking_reference]);
            $this->recordHistory($booking, 'availability_checked', 'pending', 'pending', 'system', null, ['available' => true]);
            $this->recordHistory($booking, 'quoted', 'pending', 'pending', 'system', null, ['total' => $quote['total']]);

            $this->notifyCustomer($booking, 'Booking received', "Your booking {$booking->booking_reference} has been received and is waiting for confirmation.", route('bookings.show', $booking->booking_reference));
            $this->notifyAdmins($booking, 'New booking request', "New {$booking->booking_type_label} booking {$booking->booking_reference} ({$booking->service_title}) is awaiting your review.", route('admin.bookings.show', $booking));

            return ['booking' => $booking, 'payment' => $payment];
        });
    }

    /**
     * Transition a booking to a new status if allowed.
     */
    public function transition(
        Booking $booking,
        string $to,
        ?User $performer = null,
        string $role = 'system',
        ?string $note = null
    ): bool {
        if (! $booking->canTransitionTo($to)) {
            return false;
        }

        $from = $booking->status;
        $booking->status = $to;

        if (in_array($to, ['confirmed', 'rejected'], true)) {
            $booking->reviewed_by = $performer?->id;
            $booking->reviewed_at = now();
        }

        if ($to === 'cancelled') {
            $booking->cancelled_at = now();
        }

        $booking->save();

        $this->recordHistory($booking, $this->historyActionFor($to, $role), $from, $to, $role, $note);

        if ($to === 'rejected') {
            $this->notifyCustomer($booking, 'Booking rejected', "Your booking {$booking->booking_reference} could not be confirmed. {$note}".trim($note ? ' '.$note : ''), route('bookings.show', $booking->booking_reference));
        } elseif ($to === 'confirmed') {
            $this->notifyCustomer($booking, 'Booking confirmed', "Your booking {$booking->booking_reference} has been confirmed.", route('bookings.show', $booking->booking_reference));
        }

        return true;
    }

    /**
     * Admin confirms availability for a pending booking.
     */
    public function confirm(Booking $booking, User $admin, ?string $note = null): array
    {
        $confirmed = $this->transition($booking, 'confirmed', $admin, 'admin', $note);

        abort_unless($confirmed, 422, 'This booking can no longer be confirmed.');

        return ['booking' => $booking];
    }

    /**
     * Admin rejects a pending booking.
     */
    public function reject(Booking $booking, User $admin, ?string $reason = null): array
    {
        $rejected = $this->transition($booking, 'rejected', $admin, 'admin', $reason);

        abort_unless($rejected, 422, 'This booking can no longer be rejected.');

        return ['booking' => $booking];
    }

    /**
     * Admin requests payment, moving a confirmed booking to payment_pending.
     */
    public function requestPayment(Booking $booking, User $admin): array
    {
        return \DB::transaction(function () use ($booking, $admin) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $requested = $this->transition($booking, 'payment_pending', $admin, 'admin');

            abort_unless($requested, 422, 'Payment can only be requested for a confirmed booking.');

            $booking->expires_at = now()->addHours((int) BookingSetting::getWithDefault('payment_deadline_hours'));
            $booking->save();

            $this->recordHistory($booking, 'payment_requested', 'payment_pending', 'payment_pending', 'admin', 'Payment requested for this booking.');
            $this->notifyCustomer($booking, 'Payment requested', "Please complete the outstanding balance for booking {$booking->booking_reference}.", route('bookings.payment', $booking->booking_reference));

            return ['booking' => $booking];
        });
    }

    /**
     * A customer notifies the agency they have (manually) paid. Creates a
     * pending payment record that still requires verification.
     *
     * Every amount is validated server-side: it must be positive and may not
     * exceed the outstanding balance, and a transaction reference may only be
     * used once per payment method.
     *
     * @param  array<string, mixed>|null  $gatewayResponse
     */
    public function recordCustomerPayment(
        Booking $booking,
        string $method,
        ?string $reference,
        string $maskedAmount,
        ?User $customer = null,
        ?string $gateway = null,
        ?array $gatewayResponse = null,
        ?string $note = null,
        ?UploadedFile $receipt = null,
        bool $allowCash = false
    ): Payment {
        $receiptPath = null;

        try {
            return \DB::transaction(function () use ($booking, $method, $reference, $maskedAmount, $customer, $gateway, $gatewayResponse, $note, $receipt, $allowCash, &$receiptPath) {
                $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

                abort_unless(in_array($booking->status, ['confirmed', 'payment_pending'], true), 422, 'Payment can only be reported for a confirmed booking.');
                $this->assertSupportedPaymentMethod($method);

                abort_unless(! $booking->payments()->where('status', 'pending')->exists(), 422, 'You already have a pending payment request for this booking.');

                $amount = (float) $maskedAmount;
                $summary = (new PaymentCalculationService)->summaryFor($booking);

                abort_unless($amount >= 0.01, 422, 'The payment amount must be at least 0.01.');
                abort_unless($amount >= $summary['advance_remaining'] - 0.005, 422, 'The first payment must be at least the required advance amount.');
                abort_unless($amount <= 9999999999.99, 422, 'The payment amount is too large.');

                if ($amount > $summary['due'] + 0.005) {
                    abort(422, 'The payment amount cannot exceed the outstanding balance.');
                }

                if (filled($reference)) {
                    $duplicate = Payment::query()
                        ->where('method', $method)
                        ->where('reference', (string) $reference)
                        ->whereIn('status', ['pending', 'paid'])
                        ->exists();

                    abort_unless(! $duplicate, 422, 'This transaction reference has already been reported.');
                }

                $type = (new PaymentCalculationService)->typeFor($booking, $amount);
                $receiptPath = $receipt?->store('payment-receipts', 'local');
                abort_unless($receipt === null || is_string($receiptPath), 422, 'The payment receipt could not be stored.');

                $paymentNote = $allowCash
                    ? 'Recorded by agency staff; awaiting verification.'
                    : 'Reported by customer; awaiting verification.';

                if (filled($note)) {
                    $paymentNote .= "\n".$note;
                }

                $payment = Payment::query()->create([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id ?? $customer?->id,
                    'method' => $method,
                    'payment_type' => $type,
                    'gateway' => $gateway,
                    'gateway_response' => $gatewayResponse,
                    'reference' => $reference,
                    'amount' => $maskedAmount,
                    'status' => 'pending',
                    'recorded_by' => $customer?->id,
                    'note' => $paymentNote,
                    'receipt_path' => $receiptPath,
                ]);

                $role = $allowCash ? 'admin' : 'customer';
                $this->recordHistory($booking, 'payment_pending', $booking->status, $booking->status, $role, "Payment of {$payment->amount_display} ({$payment->type_label}) recorded via {$method} and awaiting verification.");
                $this->notifyCustomer($booking, 'Payment received', "We received your payment report for {$booking->booking_reference} ({$payment->amount_display}). We will verify it shortly.", route('payments.show', $payment), 'payment', ['payment_id' => $payment->id]);
                $this->notifyAdmins($booking, 'Payment report', "A {$payment->type_label} payment of {$payment->amount_display} for {$booking->booking_reference} is waiting for verification.", route('admin.payments.show', $payment), 'payment', ['payment_id' => $payment->id]);

                return $payment;
            });
        } catch (Throwable $exception) {
            if (is_string($receiptPath) && $receiptPath !== '') {
                Storage::disk('local')->delete($receiptPath);
            }

            throw $exception;
        }
    }

    /**
     * Admin verifies a payment; the money counts as received only now.
     */
    public function verifyPayment(
        Payment $payment,
        User $admin,
        ?string $note = null,
        string|int|float|null $amount = null,
        ?string $reference = null,
        ?UploadedFile $receipt = null
    ): Booking {
        $receiptPath = null;
        $verified = false;

        try {
            $booking = \DB::transaction(function () use ($payment, $admin, $note, $amount, $reference, $receipt, &$receiptPath, &$verified) {
                $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                $booking = Booking::query()->lockForUpdate()->findOrFail($payment->booking_id);

                if ($payment->status === 'paid') {
                    return $booking;
                }

                abort_unless($payment->status === 'pending', 422, 'Only pending payments can be verified.');
                abort_unless(in_array($booking->status, ['confirmed', 'payment_pending'], true), 422, 'This booking is no longer awaiting payment.');

                $summary = (new PaymentCalculationService)->summaryFor($booking);
                $verifiedAmount = $amount === null ? (float) $payment->amount : (float) $amount;

                abort_unless($verifiedAmount >= 0.01, 422, 'The payment amount must be at least 0.01.');
                abort_unless($verifiedAmount >= $summary['advance_remaining'] - 0.005, 422, 'The verified amount is below the required advance.');
                abort_unless($verifiedAmount <= $summary['due'] + 0.005, 422, 'The verified amount cannot exceed the outstanding balance.');

                if ($amount !== null) {
                    $payment->amount = Money::round($verifiedAmount);
                }

                if (filled($reference)) {
                    $payment->reference = $reference;
                }

                if ($receipt !== null) {
                    $receiptPath = $receipt->store('payment-receipts', 'local');
                    abort_unless(is_string($receiptPath), 422, 'The payment receipt could not be stored.');
                    $payment->receipt_path = $receiptPath;
                }

                $payment->payment_type = (new PaymentCalculationService)->typeFor($booking, $verifiedAmount);
                $payment->status = 'paid';
                $payment->verified_by = $admin->id;
                $payment->paid_at = now();
                $payment->verified_at = $payment->paid_at;

                if (filled($note)) {
                    $payment->note = filled($payment->note)
                        ? $payment->note."\n".$note
                        : $note;
                }

                $payment->save();

                $booking->paid_amount = $this->settledAmount($booking);
                $booking->save();
                $this->recordHistory($booking, 'payment_received', $booking->status, $booking->status, 'admin', "Payment of {$payment->amount_display} verified ({$payment->method}).");

                if ($booking->dueAmount() <= 0.005) {
                    $transitioned = $this->transition($booking, 'paid', $admin, 'admin', 'Payment received in full.');

                    abort_unless($transitioned, 422, 'The booking payment state could not be updated.');
                }

                $verified = true;
                $payment->setRelation('booking', $booking);

                return $booking;
            });
        } catch (Throwable $exception) {
            if (is_string($receiptPath) && $receiptPath !== '') {
                Storage::disk('local')->delete($receiptPath);
            }

            throw $exception;
        }

        if ($verified) {
            $payment->refresh();
            $booking->refresh();
            $this->notifyCustomer($booking, 'Payment successful', "Payment of {$payment->amount_display} for booking {$booking->booking_reference} has been verified.", route('payments.show', $payment), 'payment', ['payment_id' => $payment->id]);
            $this->notifyAdmins($booking, 'Payment verified', "Payment #{$payment->id} for booking {$booking->booking_reference} was verified.", route('admin.payments.show', $payment), 'payment', ['payment_id' => $payment->id]);
        }

        return $booking;
    }

    /**
     * A payment that could not be verified (archived, redacted).
     */
    public function failPayment(Payment $payment, ?User $admin = null, ?string $reason = null): void
    {
        \DB::transaction(function () use ($payment, $reason) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $booking = Booking::query()->lockForUpdate()->findOrFail($payment->booking_id);

            if ($payment->status === 'failed') {
                return;
            }

            abort_unless($payment->status === 'pending', 422, 'Only pending payments can be rejected.');
            abort_unless(in_array($booking->status, ['confirmed', 'payment_pending'], true), 422, 'This booking is no longer awaiting payment.');

            $payment->status = 'failed';
            $payment->note = filled($reason)
                ? (filled($payment->note) ? $payment->note."\n".$reason : $reason)
                : $payment->note;
            $payment->save();

            $this->recordHistory($booking, 'payment_failed', $booking->status, $booking->status, 'admin', "Payment reference {$payment->reference} could not be verified.".($reason !== null ? " Reason: {$reason}" : ''));
            $this->notifyCustomer($booking, 'Payment failed', "We could not verify the payment for booking {$booking->booking_reference}. Please contact us to resolve this.", route('payments.show', $payment), 'payment', ['payment_id' => $payment->id]);
            $this->notifyAdmins($booking, 'Payment rejected', "Payment #{$payment->id} for booking {$booking->booking_reference} was rejected.", route('admin.payments.show', $payment), 'payment', ['payment_id' => $payment->id]);
        });
    }

    /**
     * Cancel a booking by a customer or an admin, applying the cancellation
     * policy and opening a refund when the booking was paid.
     */
    public function cancel(Booking $booking, string $role, ?string $reason = null, ?User $actor = null): array
    {
        $allowed = $role === 'admin' ? $booking->isAdminCancellable() : $booking->isCustomerCancellable();

        abort_unless($allowed, 422, 'This booking can no longer be cancelled.');

        return \DB::transaction(function () use ($booking, $role, $reason, $actor) {
            $canceller = $role === 'admin' ? 'cancelled_by_admin' : 'cancelled_by_customer';

            $quote = (new CancellationPolicyService)->quoteFor($booking);

            $from = $booking->status;

            $booking->status = 'cancelled';
            $booking->cancelled_by = $role === 'admin' ? 'admin' : 'customer';
            $booking->cancelled_reason = $reason;
            $booking->cancelled_at = now();
            $collected = (float) ($quote['collected'] ?? 0);

            $booking->cancellation_fee = $quote['refund_pct'] >= 0
                ? Money::round(max(0, $collected - (float) $quote['refund_amount']))
                : null;
            $booking->refund_amount = $quote['refund_pct'] >= 0 ? $quote['refund_amount'] : null;
            $booking->save();

            $this->recordHistory($booking, $canceller, $from, 'cancelled', $role, $reason, [
                'fee' => $booking->cancellation_fee,
                'refund' => $booking->refund_amount,
                'days_before' => $quote['days_before'],
            ]);

            if ((float) $quote['refund_amount'] > 0 && (float) ($booking->paid_amount ?? 0) > 0) {
                $refund = Refund::query()->create([
                    'booking_id' => $booking->id,
                    'amount' => $quote['refund_amount'],
                    'status' => 'pending',
                    'processed_by' => $actor?->id,
                    'note' => 'Refund calculated from the cancellation policy snapshot.',
                ]);

                $this->recordHistory($booking, 'refund_calculated', 'cancelled', 'cancelled', 'system', "Refund of {$refund->amount_display} calculated according to the cancellation policy.");
                $this->recordHistory($booking, 'refund_pending', 'cancelled', 'cancelled', 'system', 'Refund is pending processing.');
            }

            $this->notifyCustomer($booking, 'Booking cancelled', "Booking {$booking->booking_reference} has been cancelled".($booking->refund_amount !== null && (float) $booking->refund_amount > 0 ? ' and a refund of '.($booking->currency.' '.number_format((float) $booking->refund_amount)).' is being arranged.' : '.'), route('bookings.show', $booking->booking_reference));

            if ($role === 'customer') {
                $this->notifyAdmins($booking, 'Booking cancelled by customer', "Booking {$booking->booking_reference} was cancelled by the customer.", route('admin.bookings.show', $booking));
            }

            return ['booking' => $booking];
        });
    }

    /**
     * Admin processes a pending refund against a cancelled booking.
     */
    public function processRefund(Refund $refund, User $admin, ?string $method = null, ?string $reference = null, ?string $note = null): Refund
    {
        \DB::transaction(function () use ($refund, $admin, $method, $reference, $note) {
            if ($refund->status === 'processed') {
                return;
            }

            $refund->status = 'processed';
            $refund->method = $method;
            $refund->reference = $reference;
            $refund->processed_by = $admin->id;
            $refund->processed_at = now();
            $refund->note = $note;
            $refund->save();

            $booking = $refund->booking;
            $booking->paid_amount = $this->settledAmount($booking);
            $booking->save();

            $this->recordHistory($booking, 'refund_processed', 'cancelled', 'cancelled', 'admin', "Refund of {$refund->amount_display} processed via {$method}.");
            $this->notifyCustomer($booking, 'Refund processed', "Your refund of {$refund->amount_display} for {$booking->booking_reference} has been processed.", route('bookings.show', $booking->booking_reference));
        });

        return $refund;
    }

    /**
     * Admin completes a paid booking after the service has been delivered.
     */
    public function complete(Booking $booking, User $admin, ?string $note = null): array
    {
        $completed = $this->transition($booking, 'completed', $admin, 'admin', $note);

        abort_unless($completed, 422, 'This booking can no longer be completed.');

        $this->notifyCustomer($booking, 'Booking completed', "Your trip {$booking->booking_reference} has been marked as completed. Thank you for travelling with us!", route('bookings.show', $booking->booking_reference));

        return ['booking' => $booking];
    }

    /**
     * Admin sets a price for bookings that use a "contact for price" service
     * or that started without a price. The total is always recomputed.
     */
    public function setPrice(Booking $booking, User $admin, string|int|float $basePrice, ?string $note = null): array
    {
        abort_unless(in_array($booking->status, ['pending', 'confirmed', 'payment_pending'], true), 422, 'The price can only be set before the booking is paid.');

        $currency = BookingSetting::getWithDefault('currency');
        $taxRate = (float) BookingSetting::getWithDefault('tax_rate');
        $serviceChargeRate = (float) BookingSetting::getWithDefault('service_charge_rate');

        $tax = Money::percent($basePrice, $taxRate);
        $serviceCharge = Money::percent($basePrice, $serviceChargeRate);
        $total = Money::add(Money::add(Money::round($basePrice), $tax), $serviceCharge);

        $booking->base_price = Money::round($basePrice);
        $booking->amount = $total;
        $booking->total_amount = $total;
        $booking->tax_rate = $taxRate;
        $booking->service_charge_rate = $serviceChargeRate;
        $booking->tax_amount = $tax;
        $booking->service_charge = $serviceCharge;
        $booking->save();

        $this->snapshotPaymentPlan($booking);

        $this->recordHistory($booking, 'quoted', $booking->status, $booking->status, 'admin', $note, ['total' => $total]);

        return ['booking' => $booking];
    }

    /**
     * Add or replace the internal admin note.
     */
    public function addNote(Booking $booking, User $admin, string $note): Booking
    {
        $booking->admin_note = $note;
        $booking->save();

        $this->recordHistory($booking, 'note_added', $booking->status, $booking->status, 'admin', 'Admin note updated.');

        return $booking;
    }

    /**
     * Raise a customer change request.
     *
     * @param  array<string, mixed>  $requested
     */
    public function raiseChangeRequest(Booking $booking, string $type, array $requested, ?string $reason = null, ?User $customer = null): BookingChangeRequest
    {
        abort_unless($booking->canBeChanged(), 422, 'This booking cannot be changed in its current state.');
        abort_unless(! $booking->changeRequests()->where('status', 'pending')->exists(), 422, 'You already have a pending change request for this booking.');

        $changeRequest = BookingChangeRequest::query()->create([
            'booking_id' => $booking->id,
            'type' => $type,
            'requested' => $requested,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        $this->recordHistory($booking, 'change_requested', $booking->status, $booking->status, 'customer', ($reason ?: $type).' change requested.', ['request' => $requested]);
        $this->notifyCustomer($booking, 'Change request received', "We received your change request for {$booking->booking_reference} and will review it.", route('bookings.show', $booking->booking_reference));
        $this->notifyAdmins($booking, 'Change request', "Customer raised a change request ({$type}) for {$booking->booking_reference}.", route('admin.bookings.change-requests.index'), 'change_request', ['change_request_id' => $changeRequest->id]);

        return $changeRequest;
    }

    /**
     * Admin approves a change request, applying the requested changes safely
     * and recalculating the price when the request affects it.
     *
     * The booking row is locked for update and the requested values are
     * re-validated and re-checked for availability at the moment of approval,
     * so a change that has since become impossible is never applied.
     */
    public function approveChangeRequest(BookingChangeRequest $changeRequest, User $admin, ?string $note = null): array
    {
        abort_unless($changeRequest->booking !== null, 422, 'This change request is no longer linked to a booking.');

        return \DB::transaction(function () use ($changeRequest, $admin, $note) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($changeRequest->booking_id);

            $changeRequest->refresh();

            abort_unless($changeRequest->status === 'pending', 422, 'This change request can no longer be approved.');
            abort_unless($booking->canBeChanged(), 422, 'This booking can no longer be changed in its current state.');

            $requested = $changeRequest->requested ?? [];
            $oldTotal = (float) ($booking->total_amount ?? 0);

            $datesRequested = array_key_exists('start_date', $requested) || array_key_exists('end_date', $requested);
            $serviceIdRequested = array_key_exists('service_id', $requested) && filled($requested['service_id']);

            $targetStart = $datesRequested
                ? ($requested['start_date'] ?? $booking->start_date?->toDateString())
                : $booking->start_date?->toDateString();

            $targetEnd = $datesRequested
                ? ($requested['end_date'] ?? $booking->end_date?->toDateString())
                : $booking->end_date?->toDateString();

            if ($targetEnd === '') {
                $targetEnd = null;
            }

            $targetTravelers = array_key_exists('travelers', $requested)
                ? (int) $requested['travelers']
                : $booking->travelers;

            // Resolve the (possibly new) service so validation, availability
            // and pricing all run against the real target service.
            $targetServiceId = $serviceIdRequested ? (int) $requested['service_id'] : $booking->service_id;

            [, $targetService] = $this->resolveService($booking->booking_type, $targetServiceId);

            abort_unless($targetService !== null, 422, 'The requested service is no longer available.');

            $rules = (new BookingRulesService)->validate(
                $booking->booking_type,
                $targetService,
                $targetStart,
                $targetEnd,
                $targetTravelers
            );

            abort_unless($rules['valid'], 422, $rules['errors'][0] ?? 'The requested change is not valid.');

            // Availability is re-checked whenever the booking moves to new
            // dates or to a different service, excluding this booking itself.
            if ($datesRequested || $serviceIdRequested) {
                $availability = (new AvailabilityService)->check(
                    $booking->booking_type,
                    (int) $targetServiceId,
                    (string) $targetStart,
                    $targetEnd,
                    $booking->booking_reference,
                    $targetTravelers,
                    true
                );

                abort_unless($availability['available'], 422, $availability['reason'] ?? 'The requested change is not available.');
            }

            $booking->start_date = $targetStart;
            $booking->end_date = $targetEnd;
            $booking->travelers = $targetTravelers;

            if ($serviceIdRequested) {
                $booking->service_id = $targetServiceId;
                $booking->service_title = $targetService->title ?? $targetService->name ?? $booking->service_title;
            }

            if (array_key_exists('service_title', $requested) && filled($requested['service_title'])) {
                $booking->service_title = $requested['service_title'];
            }

            $quote = (new PriceCalculator)->quote(
                $booking->booking_type,
                $targetService,
                $booking->start_date?->toDateString(),
                $booking->end_date?->toDateString(),
                $booking->travelers
            );

            $booking->base_price = $quote['subtotal'];
            $booking->quantity = $quote['quantity'];
            $booking->tax_rate = $quote['tax_rate'];
            $booking->service_charge_rate = $quote['service_charge_rate'];
            $booking->tax_amount = $quote['tax_amount'];
            $booking->service_charge = $quote['service_charge'];
            $booking->total_amount = $quote['total'];
            $booking->amount = $quote['total'];
            $booking->save();

            $this->snapshotPaymentPlan($booking);

            $newTotal = (float) $booking->total_amount;
            $diff = Money::signed(Money::sub($newTotal, $oldTotal));

            $changeRequest->status = 'approved';
            $changeRequest->reviewed_by = $admin->id;
            $changeRequest->reviewed_at = now();
            $changeRequest->response_note = $note;
            $changeRequest->save();

            $this->recordHistory($booking, 'change_approved', $booking->status, $booking->status, 'admin', $note, [
                'old_amount' => Money::round($oldTotal),
                'new_amount' => Money::round($newTotal),
                'difference' => $diff,
            ]);

            $this->notifyCustomer(
                $booking,
                'Change approved',
                "Your change request for {$booking->booking_reference} was approved. Amount difference: {$booking->currency} {$diff}.",
                route('bookings.show', $booking->booking_reference),
                'change_request',
                ['change_request_id' => $changeRequest->id]
            );

            return ['booking' => $booking, 'difference' => $diff];
        });
    }

    /**
     * Admin rejects a change request. The original booking is never touched.
     */
    public function rejectChangeRequest(BookingChangeRequest $changeRequest, User $admin, ?string $note = null): BookingChangeRequest
    {
        abort_unless($changeRequest->booking !== null, 422, 'This change request is no longer linked to a booking.');

        return \DB::transaction(function () use ($changeRequest, $admin, $note) {
            $changeRequest->refresh();

            abort_unless($changeRequest->status === 'pending', 422, 'This change request has already been reviewed.');

            $booking = $changeRequest->booking;

            $changeRequest->status = 'rejected';
            $changeRequest->reviewed_by = $admin->id;
            $changeRequest->reviewed_at = now();
            $changeRequest->response_note = $note;
            $changeRequest->save();

            $this->recordHistory($booking, 'change_rejected', $booking->status, $booking->status, 'admin', $note);

            $this->notifyCustomer(
                $booking,
                'Change rejected',
                "Your change request for {$booking->booking_reference} was not approved.".($note ? ' Reason: '.$note : ''),
                route('bookings.show', $booking->booking_reference),
                'change_request',
                ['change_request_id' => $changeRequest->id]
            );

            return $changeRequest;
        });
    }

    /**
     * Expire pending bookings that passed their confirmation/payment deadline.
     */
    public function expireOverdueBookings(): int
    {
        $count = 0;

        Booking::query()
            ->whereIn('status', ['pending', 'payment_pending'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->chunkById(100, function ($bookings) use (&$count) {
                foreach ($bookings as $booking) {
                    $from = $booking->status;
                    $booking->status = 'expired';
                    $booking->save();

                    $this->recordHistory($booking, 'expired', $from, 'expired', 'system', 'No action taken before the booking deadline.');
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Append an immutable audit entry.
     *
     * @param  array<string, mixed>|null  $meta
     */
    public function recordHistory(
        Booking $booking,
        string $action,
        ?string $from,
        ?string $to,
        string $role = 'system',
        ?string $note = null,
        ?array $meta = null
    ): void {
        $booking->history()->create([
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'performed_by' => auth()->id(),
            'performed_role' => $role,
            'note' => $note,
            'meta' => $meta,
        ]);
    }

    /**
     * The settled amount actually received from the customer (paid minus
     * refunds).
     */
    protected function settledAmount(Booking $booking): string
    {
        return Money::round($booking->settledAmount());
    }

    /**
     * The payment methods a customer may report on, from the settings allowlist.
     *
     * @return array<int, string>
     */
    protected function supportedPaymentMethods(): array
    {
        return collect(BookingSetting::getWithDefault('payment_methods'))
            ->map(fn ($entry) => is_array($entry) ? ($entry['method'] ?? null) : $entry)
            ->filter(fn ($method) => is_string($method) && $method !== '')
            ->values()
            ->all();
    }

    /**
     * Reject a payment method that is not part of the configured allowlist.
     */
    protected function assertSupportedPaymentMethod(string $method): void
    {
        abort_unless(in_array($method, $this->supportedPaymentMethods(), true), 422, 'The selected payment method is not available.');
    }

    /**
     * Snapshot the advance amount and remaining-balance due date on the
     * booking row so the payment plan stays stable if the rules change later.
     */
    protected function snapshotPaymentPlan(Booking $booking): void
    {
        $paymentCalculation = new PaymentCalculationService;
        $rules = $paymentCalculation->rulesFor($booking->booking_type);
        $policy = BookingSetting::getWithDefault('payment_overpayment_policy');
        $overpaymentPolicy = in_array($policy, PaymentCalculationService::OVERPAYMENT_POLICIES, true)
            ? $policy
            : 'reject';
        $snapshot = is_array($booking->policy_snapshot) ? $booking->policy_snapshot : [];
        $snapshot['payment'] = [
            'rules' => $rules,
            'overpayment_policy' => $overpaymentPolicy,
        ];
        $booking->policy_snapshot = $snapshot;
        $booking->advance_amount = $paymentCalculation->advanceFor($booking);
        $booking->payment_due_date = $paymentCalculation->dueDateFor($booking, $rules);
        $booking->saveQuietly();
    }

    /**
     * The policy + pricing snapshot stored on the booking row.
     *
     * @return array<string, mixed>
     */
    protected function policySnapshot(string $bookingType): array
    {
        $settings = BookingSetting::class;
        $paymentCalculation = new PaymentCalculationService;
        $overpaymentPolicy = $settings::getWithDefault('payment_overpayment_policy');

        return [
            'cancellation_policy' => $settings::getWithDefault('cancellation_policy'),
            'tax_rate' => $settings::getWithDefault('tax_rate'),
            'service_charge_rate' => $settings::getWithDefault('service_charge_rate'),
            'currency' => $settings::getWithDefault('currency'),
            'payment' => [
                'rules' => $paymentCalculation->rulesFor($bookingType),
                'overpayment_policy' => in_array($overpaymentPolicy, PaymentCalculationService::OVERPAYMENT_POLICIES, true)
                    ? $overpaymentPolicy
                    : 'reject',
            ],
        ];
    }

    /**
     * The human-friendly action recorded in the audit trail for a transition.
     */
    protected function historyActionFor(string $to, string $role): string
    {
        if ($to === 'cancelled') {
            return $role === 'admin' ? 'cancelled_by_admin' : 'cancelled_by_customer';
        }

        return $to;
    }

    /**
     * Generate a public booking reference like BK-2026-00042.
     */
    protected function referenceFor(Booking $booking): string
    {
        return 'BK-'.$booking->created_at->year.'-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Resolve the service model for a booking type. When $lock is true the
     * row is locked for update so two simultaneous first bookings for the
     * same hotel/vehicle/tour cannot both pass the availability check.
     *
     * @return array{0: string, 1: mixed}
     */
    protected function resolveService(string $type, ?int $serviceId, bool $lock = false): array
    {
        $service = match ($type) {
            'tour' => $this->lockedQuery(Tour::query(), $lock)->find($serviceId),
            'hotel' => $this->lockedQuery(Hotel::query(), $lock)->find($serviceId),
            'vehicle' => $this->lockedQuery(TransportVehicle::query(), $lock)->find($serviceId),
            default => null,
        };

        return [$type, $service];
    }

    /**
     * Apply a row lock when requested.
     */
    protected function lockedQuery($query, bool $lock)
    {
        return $lock ? $query->lockForUpdate() : $query;
    }

    /**
     * Send the in-app notification to the customer account when one exists.
     *
     * @param  array<string, mixed>  $ids
     */
    protected function notifyCustomer(Booking $booking, string $title, string $message, ?string $url = null, string $type = 'booking', array $ids = []): void
    {
        $booking->loadMissing('user');

        if ($booking->user === null) {
            return;
        }

        $booking->user->notify(new BookingNotification($title, $message, array_merge([
            'type' => $type,
            'booking_id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
            'service_title' => $booking->service_title,
            'total' => $booking->amount_display,
        ], $ids), $url));

        $booking->unsetRelation('user');
    }

    /**
     * Send the in-app notification to every administrator account.
     *
     * @param  array<string, mixed>  $ids
     */
    protected function notifyAdmins(Booking $booking, string $title, string $message, ?string $url = null, string $type = 'booking', array $ids = []): void
    {
        $payload = array_merge([
            'type' => $type,
            'booking_id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
            'service_title' => $booking->service_title,
            'total' => $booking->amount_display,
        ], $ids);

        foreach (User::query()->where('is_admin', true)->get() as $admin) {
            $admin->notify(new BookingNotification($title, $message, $payload, $url));
        }
    }
}
