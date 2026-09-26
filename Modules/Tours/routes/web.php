<?php

use Illuminate\Support\Facades\Route;
use Modules\Tours\Http\Controllers\Admin\TourController;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('tours', TourController::class)
            ->except(['show'])
            ->names('tours');

        Route::patch('tours/{tour}/toggle-active', [TourController::class, 'toggleActive'])
            ->name('tours.toggle-active');

        Route::patch('tours/{tour}/toggle-featured', [TourController::class, 'toggleFeatured'])
            ->name('tours.toggle-featured');
    });
