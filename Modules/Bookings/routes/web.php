<?php

use Illuminate\Support\Facades\Route;
use Modules\Bookings\Http\Controllers\Admin\BookingController;
use Modules\Bookings\Http\Controllers\Admin\BookingSettingController;
use Modules\Bookings\Http\Controllers\Frontend\BookingController as FrontendBookingController;
use Modules\Bookings\Http\Controllers\Frontend\PaymentController;
use Modules\Bookings\Http\Controllers\NotificationsController;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('bookings', [BookingController::class, 'index'])
            ->name('bookings.index');

        // Static segments must be registered before the "bookings/{booking}"
        // wildcard, otherwise they are captured by the model-bound show route.
        Route::get('bookings/payments', [BookingController::class, 'payments'])
            ->name('bookings.payments.index');
        Route::get('bookings/change-requests', [BookingController::class, 'changeRequests'])
            ->name('bookings.change-requests.index');

        Route::get('bookings/{booking}', [BookingController::class, 'show'])
            ->name('bookings.show');

        Route::post('bookings/{booking}/confirm', [BookingController::class, 'confirm'])
            ->name('bookings.confirm');
        Route::post('bookings/{booking}/reject', [BookingController::class, 'reject'])
            ->name('bookings.reject');
        Route::post('bookings/{booking}/request-payment', [BookingController::class, 'requestPayment'])
            ->name('bookings.request-payment');
        Route::post('bookings/{booking}/mark-paid', [BookingController::class, 'markPaid'])
            ->name('bookings.mark-paid');
        Route::post('bookings/{booking}/complete', [BookingController::class, 'complete'])
            ->name('bookings.complete');
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])
            ->name('bookings.cancel');
        Route::post('bookings/{booking}/process-refund', [BookingController::class, 'processRefund'])
            ->name('bookings.process-refund');
        Route::post('bookings/{booking}/set-price', [BookingController::class, 'setPrice'])
            ->name('bookings.set-price');
        Route::patch('bookings/{booking}/note', [BookingController::class, 'updateNote'])
            ->name('bookings.note');

        Route::post('change-requests/{changeRequest}/approve', [BookingController::class, 'approveChangeRequest'])
            ->name('bookings.change-requests.approve');
        Route::post('change-requests/{changeRequest}/reject', [BookingController::class, 'rejectChangeRequest'])
            ->name('bookings.change-requests.reject');

        Route::get('payments/{payment}', [BookingController::class, 'paymentDetails'])
            ->name('payments.show');
        Route::post('payments/{payment}/verify', [BookingController::class, 'verifyPayment'])
            ->name('payments.verify');
        Route::post('payments/{payment}/fail', [BookingController::class, 'failPayment'])
            ->name('payments.fail');
        Route::get('payments/{payment}/receipt', [BookingController::class, 'receipt'])
            ->name('payments.receipt');

        Route::get('booking-settings', [BookingSettingController::class, 'index'])
            ->name('bookings.settings.index');
        Route::put('booking-settings', [BookingSettingController::class, 'update'])
            ->name('bookings.settings.update');
    });

Route::middleware('auth')->group(function () {
    Route::prefix('bookings')
        ->name('bookings.')
        ->group(function () {
            Route::get('create', [FrontendBookingController::class, 'create'])
                ->name('create');
            Route::post('quote', [FrontendBookingController::class, 'quote'])
                ->name('quote');
            Route::post('/', [FrontendBookingController::class, 'store'])
                ->name('store');

            Route::get('my', [FrontendBookingController::class, 'my'])
                ->name('my');
            Route::post('lookup', [FrontendBookingController::class, 'lookup'])
                ->name('lookup');
        });

    Route::prefix('bookings')
        ->name('bookings.')
        ->group(function () {
            Route::get('{booking:booking_reference}', [FrontendBookingController::class, 'show'])
                ->name('show');
            Route::get('{booking:booking_reference}/cancel', [FrontendBookingController::class, 'cancelPreview'])
                ->name('cancel.preview');
            Route::post('{booking:booking_reference}/cancel', [FrontendBookingController::class, 'cancel'])
                ->name('cancel');
            Route::get('{booking:booking_reference}/payment', [FrontendBookingController::class, 'payment'])
                ->name('payment');
            Route::post('{booking:booking_reference}/payment/notify', [FrontendBookingController::class, 'notifyPayment'])
                ->name('payment.notify');
            Route::post('{booking:booking_reference}/change-requests', [FrontendBookingController::class, 'changeRequest'])
                ->name('change-request');
        });

    Route::prefix('payments')
        ->name('payments.')
        ->group(function () {
            Route::get('/', [PaymentController::class, 'index'])
                ->name('index');
            Route::get('{payment}', [PaymentController::class, 'show'])
                ->name('show');
            Route::get('{payment}/receipt', [PaymentController::class, 'receipt'])
                ->name('receipt');
        });

    Route::prefix('notifications')
        ->name('notifications.')
        ->group(function () {
            Route::get('/', [NotificationsController::class, 'index'])
                ->name('index');
            Route::get('/data', [NotificationsController::class, 'data'])
                ->name('data');
            Route::get('/read-all', [NotificationsController::class, 'readAll'])
                ->name('read-all');
            Route::get('/{id}/read', [NotificationsController::class, 'read'])
                ->name('read');
        });
});
