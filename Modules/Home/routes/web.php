<?php

use Illuminate\Support\Facades\Route;
use Modules\Home\Http\Controllers\Admin\DestinationController;
use Modules\Home\Http\Controllers\Admin\HomeController as AdminHomeController;
use Modules\Home\Http\Controllers\Admin\ServiceController;
use Modules\Home\Http\Controllers\Admin\StoryController;
use Modules\Home\Http\Controllers\Admin\WhyChooseUsController;
use Modules\Home\Http\Controllers\HomeController;

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::resource('homes', HomeController::class)->names('home');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('home', [AdminHomeController::class, 'index'])->name('home.index');
        Route::put('home/hero', [AdminHomeController::class, 'update'])->name('home.hero.update');
        Route::resource('home/destinations', DestinationController::class)
            ->except(['show'])
            ->names('home.destinations');
        Route::patch('home/destinations/{destination}/toggle-active', [DestinationController::class, 'toggleActive'])
            ->name('home.destinations.toggle-active');
        Route::get('home/why-choose-us', [WhyChooseUsController::class, 'index'])->name('home.why_choose_us.index');
        Route::put('home/why-choose-us', [WhyChooseUsController::class, 'update'])->name('home.why_choose_us.update');
        Route::resource('home/stories', StoryController::class)
            ->except(['show'])
            ->names('home.stories');
        Route::patch('home/stories/{story}/toggle-active', [StoryController::class, 'toggleActive'])
            ->name('home.stories.toggle-active');
        Route::resource('home/services', ServiceController::class)
            ->except(['show'])
            ->names('home.services');
        Route::patch('home/services/{service}/toggle-active', [ServiceController::class, 'toggleActive'])
            ->name('home.services.toggle-active');
    });
