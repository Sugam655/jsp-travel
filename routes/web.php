<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserDashboardController;
use Illuminate\Support\Facades\Route;
use Modules\Bookings\Http\Controllers\Frontend\BookingSearchController;
use Modules\Home\Http\Controllers\Frontend\HomeController as FrontendHomeController;
use Modules\Hotels\Http\Controllers\Frontend\HotelController;
use Modules\Tours\Http\Controllers\Frontend\TourController;
use Modules\Transport\Http\Controllers\Frontend\TransportVehicleController;

// The home page is the most common way into the public site, and the one an
// administrator reaches by typing "/", so it carries frontend.guest as well.
Route::middleware('frontend.guest')
    ->get('/', [FrontendHomeController::class, 'index'])
    ->name('home');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');
    Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/{user}', [UserManagementController::class, 'show'])->name('admin.users.show');
});

// The public site is a guest area: frontend.guest ends any AdminLTE session the
// visitor is still carrying, so these pages always render the public navbar with
// Login rather than the dashboard and Logout. The booking form, the account
// pages and the whole AdminLTE panel stay outside this group, because those
// routes either require a signed-in customer or are the backend itself.
Route::middleware('frontend.guest')->group(function () {
    Route::get('/about', function () {
        return view('frontend.about');
    })->name('about');
    Route::get('/destinations', [FrontendHomeController::class, 'destinations'])->name('destinations.index');
    Route::get('/transport', [TransportVehicleController::class, 'index'])->name('transport');
    Route::get('/transport/{vehicle:slug}', [TransportVehicleController::class, 'show'])->name('transport.show');
    Route::get('/hotel', [HotelController::class, 'index'])->name('hotel');
    Route::get('/hotels/{hotel:slug}', [HotelController::class, 'show'])->name('hotels.show');
    Route::get('/tours', [TourController::class, 'index'])->name('tours.index');
    Route::get('/tours/{tour:slug}', [TourController::class, 'show'])->name('tours.show');
    Route::get('/book', [BookingSearchController::class, 'index'])->name('booking.search');
});

Route::middleware('auth')->group(function () {
    Route::get('/user/dashboard', [UserDashboardController::class, 'show'])->name('user.dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
