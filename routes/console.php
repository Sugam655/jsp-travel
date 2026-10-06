<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Notifications\BookingNotification;
use Modules\Bookings\Services\BookingWorkflowService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bookings:notify-due-payments', function (): void {
    $notified = 0;

    Booking::query()
        ->where('status', 'payment_pending')
        ->whereNotNull('payment_due_date')
        ->where('payment_due_date', '<=', now()->addDays(7)->toDateString())
        ->with('user')
        ->orderBy('payment_due_date')
        ->get()
        ->each(function (Booking $booking) use (&$notified) {
            if ($booking->user === null || $booking->dueAmount() <= 0) {
                return;
            }

            if ($booking->history()
                ->where('action', 'remaining_due_reminder')
                ->where('created_at', '>=', now()->subDays(3))
                ->exists()) {
                return;
            }

            $due = $booking->currency.' '.number_format($booking->dueAmount(), 2);

            $booking->user->notify(new BookingNotification(
                'Remaining payment due',
                "The remaining balance of {$due} for booking {$booking->booking_reference} is due by {$booking->payment_due_display}. Please make the payment before your service date.",
                [
                    'type' => 'payment',
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'service_title' => $booking->service_title,
                    'total' => $booking->amount_display,
                ],
                route('bookings.payment', $booking->booking_reference)
            ));

            $booking->history()->create([
                'action' => 'remaining_due_reminder',
                'from_status' => 'payment_pending',
                'to_status' => 'payment_pending',
                'performed_role' => 'system',
                'note' => 'Reminder sent for the remaining balance.',
            ]);

            $notified++;
        });

    $this->info("Payment reminders sent: {$notified}.");
})->purpose('Notify customers when the remaining payment for a booking becomes due');

Artisan::command('bookings:expire-overdue', function (): void {
    $expired = app(BookingWorkflowService::class)->expireOverdueBookings();

    $this->info("Bookings expired: {$expired}.");
})->purpose('Expire pending bookings that passed their confirmation/payment deadline');

Schedule::command('bookings:notify-due-payments')->dailyAt('09:00');
Schedule::command('bookings:expire-overdue')->hourly();
