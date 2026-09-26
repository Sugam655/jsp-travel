<?php

namespace Modules\Bookings\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingChangeRequest;
use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Models\Payment;
use Modules\Bookings\Models\Refund;
use Modules\Bookings\Services\BookingWorkflowService;
use Modules\Bookings\Services\CancellationPolicyService;
use Modules\Bookings\Services\PaymentCalculationService;
use Modules\Hotels\Models\Hotel;
use Modules\Tours\Models\Tour;
use Modules\Transport\Models\TransportVehicle;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingController extends Controller
{
    /**
     * Display a listing of the bookings, optionally filtered by status
     * and/or booking type (hotel, vehicle, tour).
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $type = $request->query('type');

        $bookings = Booking::query()
            ->when($status !== null && in_array($status, Booking::STATUSES, true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($type !== null && in_array($type, Booking::TYPES, true), function ($query) use ($type) {
                $query->where('booking_type', $type);
            })
            ->orderByDesc('created_at')
            ->get();

        return view('bookings::admin.bookings.index', [
            'bookings' => $bookings,
            'status' => $status !== null && in_array($status, Booking::STATUSES, true) ? $status : null,
            'type' => $type !== null && in_array($type, Booking::TYPES, true) ? $type : null,
            'statuses' => Booking::STATUS_LABELS,
            'typeLabels' => Booking::TYPE_LABELS,
            'heads' => [
                'Booking',
                'Service',
                'Customer',
                'Travel Date',
                'Amount',
                'Paid',
                'Status',
                'Created',
                ['label' => 'Actions', 'no-export' => true],
            ],
            'config' => [
                'order' => [[7, 'desc']],
                'pageLength' => 10,
                'lengthMenu' => [[5, 10, 25, 50], [5, 10, 25, 50]],
                'autoWidth' => false,
                'layout' => [
                    'topStart' => 'pageLength',
                    'topEnd' => 'search',
                    'bottomStart' => 'info',
                    'bottomEnd' => 'paging',
                ],
                'columnDefs' => [
                    ['orderable' => false, 'searchable' => false, 'targets' => 8],
                ],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'emptyTable' => 'No bookings found.',
                ],
            ],
        ]);
    }

    /**
     * Show the specified booking with the full timeline and ledger.
     */
    public function show(Booking $booking): View
    {
        return view('bookings::admin.bookings.show', [
            'booking' => $booking,
            'statuses' => Booking::STATUS_LABELS,
            'service' => $this->resolveService($booking->booking_type, $booking->service_id),
            'cancellation_quote' => (new CancellationPolicyService)->quoteFor($booking),
            'paymentMethods' => BookingSetting::getWithDefault('payment_methods'),
        ]);
    }

    /**
     * Confirm a pending booking after availability has been verified.
     */
    public function confirm(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        abort_unless($booking->status === 'pending', 422, 'Only pending bookings can be confirmed.');

        $workflow->confirm($booking, $request->user(), $request->input('note'));

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Booking confirmed. The customer can now report payment.');
    }

    /**
     * Reject a pending booking.
     */
    public function reject(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        abort_unless($booking->status === 'pending', 422, 'Only pending bookings can be rejected.');

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        $workflow->reject($booking, $request->user(), $validated['reason'] ?? null);

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Booking rejected.');
    }

    /**
     * Move a confirmed booking to payment_pending.
     */
    public function requestPayment(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        abort_unless($booking->status === 'confirmed', 422, 'Only confirmed bookings can be moved to payment.');

        $workflow->requestPayment($booking, $request->user());

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Payment requested from the customer.');
    }

    /**
     * Mark a booking as paid (admin verifies the customer's payment).
     */
    public function markPaid(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        abort_unless(in_array($booking->status, ['confirmed', 'payment_pending'], true), 422, 'Only confirmed bookings awaiting payment can be marked paid.');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string', 'max:60'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max('5mb')],
        ]);

        $pending = $booking->payments()->where('status', 'pending')->latest()->first();
        $payment = $pending;
        $receipt = $request->file('receipt');

        if ($pending === null) {
            $payment = $workflow->recordCustomerPayment(
                $booking,
                $validated['method'] ?? 'cash',
                $validated['reference'] ?? null,
                (string) $validated['amount'],
                $request->user(),
                note: $validated['note'] ?? 'Recorded and verified by agency staff.',
                receipt: $receipt,
                allowCash: true,
            );
            $receipt = null;
        }

        $workflow->verifyPayment(
            $payment,
            $request->user(),
            $validated['note'] ?? 'Verified manually by admin.',
            $validated['amount'],
            $validated['reference'] ?? null,
            $receipt,
        );

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('success', 'Payment recorded and verified.');
    }

    /**
     * List the payments reported by customers, awaiting admin verification.
     */
    public function payments(Request $request): View
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));
        $bookingReference = trim((string) $request->query('booking', ''));
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));
        $method = trim((string) $request->query('method', ''));
        $configuredMethods = collect(BookingSetting::getWithDefault('payment_methods'))
            ->map(fn ($entry) => is_array($entry) ? ($entry['method'] ?? null) : $entry)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values();
        $existingMethods = Payment::query()->whereNotNull('method')->distinct()->pluck('method');
        $methodLabels = $configuredMethods->mapWithKeys(fn (string $value) => [$value => $this->paymentMethodLabel($value)]);
        $existingMethods->each(function (string $value) use ($methodLabels): void {
            if (! $methodLabels->has($value)) {
                $methodLabels->put($value, Str::headline($value));
            }
        });
        $status = in_array($status, Payment::STATUSES, true) ? $status : null;
        $method = $methodLabels->has($method) ? $method : null;

        $payments = Payment::query()
            ->with('booking.user')
            ->whereHas('booking')
            ->when($status !== null, fn ($query) => $query->where('payments.status', $status))
            ->when($method !== null, fn ($query) => $query->where('payments.method', $method))
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.$search.'%';

                $query->where(function ($query) use ($term): void {
                    $query->where('payments.id', 'like', $term)
                        ->orWhere('payments.reference', 'like', $term)
                        ->orWhere('payments.method', 'like', $term)
                        ->orWhereHas('booking', function ($bookingQuery) use ($term): void {
                            $bookingQuery->where('booking_reference', 'like', $term)
                                ->orWhere('name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('phone', 'like', $term)
                                ->orWhere('service_title', 'like', $term);
                        });
                });
            })
            ->when($bookingReference !== '', fn ($query) => $query->whereHas(
                'booking',
                fn ($bookingQuery) => $bookingQuery->where('booking_reference', 'like', $bookingReference)
            ))
            ->when($from !== '', fn ($query) => $query->whereDate('payments.created_at', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('payments.created_at', '<=', $to))
            ->orderByDesc('payments.created_at')
            ->paginate(20)
            ->withQueryString();

        return view('bookings::admin.bookings.payments', [
            'payments' => $payments,
            'status' => $status,
            'search' => $search,
            'bookingReference' => $bookingReference,
            'from' => $from,
            'to' => $to,
            'method' => $method,
            'statuses' => Payment::STATUSES,
            'statusLabels' => Payment::STATUS_LABELS,
            'methods' => $methodLabels,
            'highlightedPaymentId' => (int) $request->query('payment', 0),
        ]);
    }

    public function paymentDetails(Payment $payment, PaymentCalculationService $paymentCalculation): View
    {
        abort_unless($payment->booking !== null, 404, 'This payment is no longer linked to a booking.');

        $payment->load(['booking.user', 'recorder', 'verifier']);
        $booking = $payment->booking;

        return view('bookings::admin.bookings.payment', [
            'payment' => $payment,
            'booking' => $booking,
            'summary' => $paymentCalculation->summaryFor($booking),
            'paymentHistory' => $booking->payments()->latest('id')->get(),
            'methodLabel' => $this->paymentMethodLabel((string) $payment->method),
            'statusLabels' => Payment::STATUS_LABELS,
        ]);
    }

    /**
     * Admin verifies a customer-reported payment.
     */
    public function verifyPayment(Request $request, Payment $payment, BookingWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max('5mb')],
        ]);

        $workflow->verifyPayment(
            $payment,
            $request->user(),
            $validated['note'] ?? null,
            $validated['amount'] ?? null,
            $validated['reference'] ?? null,
            $request->file('receipt'),
        );

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('success', 'Payment verified.');
    }

    /**
     * Admin rejects a customer-reported payment.
     */
    public function failPayment(Request $request, Payment $payment, BookingWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        $workflow->failPayment($payment, $request->user(), $validated['reason'] ?? null);

        return redirect()
            ->route('admin.bookings.payments.index')
            ->with('success', 'Payment rejected.');
    }

    public function receipt(Payment $payment): BinaryFileResponse
    {
        abort_unless(filled($payment->receipt_path), 404, 'No receipt is available for this payment.');
        abort_unless(Storage::disk('local')->exists($payment->receipt_path), 404, 'The payment receipt is no longer available.');

        $extension = pathinfo($payment->receipt_path, PATHINFO_EXTENSION);
        $filename = 'payment-'.($payment->reference ?: (string) $payment->id).($extension !== '' ? '.'.$extension : '');

        return response()->download(Storage::disk('local')->path($payment->receipt_path), $filename);
    }

    /**
     * Complete a paid booking.
     */
    public function complete(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        abort_unless($booking->status === 'paid', 422, 'Only paid bookings can be completed.');

        $workflow->complete($booking, $request->user(), $request->input('note'));

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Booking marked as completed.');
    }

    /**
     * Cancel a booking (admin), applying the cancellation policy.
     */
    public function cancel(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        $workflow->cancel($booking, 'admin', $validated['reason'] ?? null, $request->user());

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Booking cancelled and refund calculated.');
    }

    /**
     * Process a pending refund for a cancelled booking.
     */
    public function processRefund(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        abort_unless($booking->status === 'cancelled', 422, 'Refunds can only be processed for cancelled bookings.');

        $refund = $booking->refunds()->where('status', 'pending')->first();

        abort_unless($refund !== null, 422, 'There is no pending refund for this booking.');

        $validated = $request->validate([
            'method' => ['nullable', 'string', 'max:60'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $workflow->processRefund($refund, $request->user(), $validated['method'] ?? null, $validated['reference'] ?? null, $validated['note'] ?? null);

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Refund processed.');
    }

    /**
     * Set a manual price for bookings priced on request.
     */
    public function setPrice(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validate([
            'base_price' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $workflow->setPrice($booking, $request->user(), $validated['base_price'], $validated['note'] ?? null);

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Price updated and total recalculated.');
    }

    /**
     * Update the internal admin note.
     */
    public function updateNote(Request $request, Booking $booking, BookingWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validate(['admin_note' => ['nullable', 'string', 'max:5000']]);

        $workflow->addNote($booking, $request->user(), $validated['admin_note'] ?? '');

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Note updated.');
    }

    /**
     * List customer change requests awaiting review.
     */
    public function changeRequests(Request $request): View
    {
        $status = $request->query('status');

        $changeRequests = BookingChangeRequest::query()
            ->with('booking')
            ->when($status !== null && in_array($status, BookingChangeRequest::STATUSES, true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (BookingChangeRequest $changeRequest) => $changeRequest->booking !== null);

        return view('bookings::admin.bookings.change-requests', [
            'changeRequests' => $changeRequests,
            'status' => $status !== null && in_array($status, BookingChangeRequest::STATUSES, true) ? $status : null,
            'statuses' => BookingChangeRequest::STATUSES,
            'statusLabels' => BookingChangeRequest::STATUS_LABELS,
            'typeLabels' => BookingChangeRequest::TYPE_LABELS,
        ]);
    }

    /**
     * Approve a customer change request.
     */
    public function approveChangeRequest(Request $request, BookingChangeRequest $changeRequest, BookingWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        try {
            $result = $workflow->approveChangeRequest($changeRequest, $request->user(), $validated['note'] ?? null);
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() === 422) {
                return redirect()
                    ->route('admin.bookings.change-requests.index')
                    ->withErrors(['change_request' => $exception->getMessage()]);
            }

            throw $exception;
        }

        return redirect()
            ->route('admin.bookings.change-requests.index')
            ->with('success', 'Change request approved. Amount difference: '.$result['booking']->currency.' '.$result['difference'].'.');
    }

    /**
     * Reject a customer change request.
     */
    public function rejectChangeRequest(Request $request, BookingChangeRequest $changeRequest, BookingWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        try {
            $workflow->rejectChangeRequest($changeRequest, $request->user(), $validated['note'] ?? null);
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() === 422) {
                return redirect()
                    ->route('admin.bookings.change-requests.index')
                    ->withErrors(['change_request' => $exception->getMessage()]);
            }

            throw $exception;
        }

        return redirect()
            ->route('admin.bookings.change-requests.index')
            ->with('success', 'Change request rejected.');
    }

    /**
     * The first matching service model for a booking type and id, or null.
     */
    private function paymentMethodLabel(string $method): string
    {
        $entry = collect(BookingSetting::getWithDefault('payment_methods'))
            ->first(fn ($value) => is_array($value) && ($value['method'] ?? null) === $method);

        return is_array($entry)
            ? ($entry['label'] ?? Str::headline($method))
            : Str::headline($method);
    }

    private function resolveService(string $type, ?int $id): mixed
    {
        if ($id === null) {
            return null;
        }

        return match ($type) {
            'tour' => Tour::query()->find($id),
            'hotel' => Hotel::query()->find($id),
            'vehicle' => TransportVehicle::query()->find($id),
            default => null,
        };
    }
}
