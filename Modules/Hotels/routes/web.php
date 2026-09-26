<?php

use Illuminate\Support\Facades\Route;
use Modules\Hotels\Http\Controllers\Admin\HotelController;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('hotels', HotelController::class)
            ->except(['show'])
            ->names('hotels');

        Route::patch('hotels/{hotel}/toggle-active', [HotelController::class, 'toggleActive'])
            ->name('hotels.toggle-active');

        Route::patch('hotels/{hotel}/toggle-featured', [HotelController::class, 'toggleFeatured'])
            ->name('hotels.toggle-featured');
    });
