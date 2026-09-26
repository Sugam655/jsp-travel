<?php

namespace Modules\Bookings\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Bookings\Models\BookingSetting;
use Modules\Bookings\Models\Payment;
use Modules\Bookings\Models\Refund;
use Modules\Bookings\Services\PaymentCalculationService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The signed-in customer's own payment history (AdminLTE area). Every query
 * is scoped to the authenticated user so customers can never browse each
 * other's records.
 */
class PaymentController extends Controller
{
    /**
     * List the payments belonging to the signed-in customer. Ownership is
     * matched on the payment row itself or, for historical rows, through the
     * owning booking.
     */
    public function index(): View
    {
        $owned = function ($query) {
            $query->where('user_id', auth()->id());
            $query->orWhereHas('booking', fn ($booking) => $booking->where('user_id', auth()->id()));
        };

        $payments = Payment::query()
            ->with('booking')
            ->where($owned)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $ownedPaymentBookingIds = Payment::query()
            ->where($owned)
            ->whereNotNull('booking_id')
            ->select('booking_id');
        $verifiedTotal = (float) Payment::query()->where($owned)->where('status', 'paid')->sum('amount')
            - (float) Refund::query()
                ->where('status', 'processed')
                ->whereIn('booking_id', $ownedPaymentBookingIds)
                ->sum('amount');

        return view('bookings::frontend.payments.index', [
            'payments' => $payments,
            'recordCount' => (int) Payment::query()->where($owned)->count(),
            'pendingCount' => (int) Payment::query()->where($owned)->where('status', 'pending')->count(),
            'verifiedTotal' => max(0, $verifiedTotal),
            'currency' => BookingSetting::getWithDefault('currency'),
        ]);
    }

    /**
     * Show a single payment with the booking context, the booking's current
     * balance, and the rest of the payment ledger so the customer can continue
     * paying the same booking from here.
     */
    public function show(Payment $payment): View
    {
        $this->authorizeAccess($payment);

        $booking = $payment->booking;

        abort_unless($booking !== null, 404, 'This payment is no longer linked to a booking.');

        return view('bookings::frontend.payments.show', [
            'payment' => $payment,
            'booking' => $booking,
            // Recalculated from the ledger on every request, never from a value
            // carried in the URL, so the amount can never go stale.
            'summary' => (new PaymentCalculationService)->summaryFor($booking),
            'bookingPayments' => $booking->payments()->latest('id')->get(),
            'pendingPayment' => $booking->payments()->where('status', 'pending')->latest()->first(),
        ]);
    }

    public function receipt(Payment $payment): BinaryFileResponse
    {
        $this->authorizeAccess($payment);

        abort_unless(filled($payment->receipt_path), 404, 'No receipt is available for this payment.');
        abort_unless(Storage::disk('local')->exists($payment->receipt_path), 404, 'The payment receipt is no longer available.');

        $extension = pathinfo($payment->receipt_path, PATHINFO_EXTENSION);
        $filename = 'payment-'.($payment->reference ?: (string) $payment->id).($extension !== '' ? '.'.$extension : '');

        return response()->download(Storage::disk('local')->path($payment->receipt_path), $filename);
    }

    /**
     * A payment is visible when it is linked to the signed-in customer
     * directly or through a booking they own.
     */
    protected function authorizeAccess(Payment $payment): void
    {
        $booking = $payment->booking;

        $owned = $payment->user_id !== null && (int) $payment->user_id === (int) auth()->id();
        $bookingOwned = $booking !== null && $booking->user_id !== null && (int) $booking->user_id === (int) auth()->id();

        throw_unless($owned || $bookingOwned, NotFoundHttpException::class);
    }
}
