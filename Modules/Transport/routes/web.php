<?php

use Illuminate\Support\Facades\Route;
use Modules\Transport\Http\Controllers\Admin\TransportVehicleController;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('transport', TransportVehicleController::class)
            ->except(['show'])
            ->parameters(['transport' => 'vehicle'])
            ->names('transport');

        Route::patch('transport/{vehicle}/toggle-active', [TransportVehicleController::class, 'toggleActive'])
            ->name('transport.toggle-active');

        Route::patch('transport/{vehicle}/toggle-featured', [TransportVehicleController::class, 'toggleFeatured'])
            ->name('transport.toggle-featured');

        Route::patch('transport/{vehicle}/toggle-availability', [TransportVehicleController::class, 'toggleAvailability'])
            ->name('transport.toggle-availability');
    });
