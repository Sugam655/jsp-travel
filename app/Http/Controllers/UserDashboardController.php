<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\Payment;
use Modules\Bookings\Models\Refund;

class UserDashboardController extends Controller
{
    /**
     * Display the customer dashboard: the signed-in user's profile summary,
     * their own bookings and their recent payments. Ownership is always
     * derived from the authenticated user, never from the request input.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        $bookings = Booking::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $ownedPayments = function ($query) use ($user): void {
            $query->where('payments.user_id', $user->id)
                ->orWhereHas('booking', fn ($bookingQuery) => $bookingQuery->where('user_id', $user->id));
        };

        $payments = Payment::query()
            ->with('booking')
            ->where($ownedPayments)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $ownedPaymentBookingIds = Payment::query()
            ->where($ownedPayments)
            ->whereNotNull('booking_id')
            ->select('booking_id');
        $verifiedTotal = (float) Payment::query()->where($ownedPayments)->where('status', 'paid')->sum('amount');
        $processedRefunds = (float) Refund::query()
            ->where('status', 'processed')
            ->whereIn('booking_id', $ownedPaymentBookingIds)
            ->sum('amount');

        return view('frontend.dashboard', [
            'user' => $user,
            'bookings' => $bookings,
            'payments' => $payments,
            'paymentsTotal' => max(0, $verifiedTotal - $processedRefunds),
            'paymentsPending' => Payment::query()->where($ownedPayments)->where('status', 'pending')->count(),
        ]);
    }
}
