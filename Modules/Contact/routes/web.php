<?php

use Illuminate\Support\Facades\Route;
use Modules\Contact\Http\Controllers\Admin\ContactController;
use Modules\Contact\Http\Controllers\Admin\ContactMessageController;
use Modules\Contact\Http\Controllers\Frontend\ContactController as FrontendContactController;

Route::get('/contact', [FrontendContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [FrontendContactController::class, 'store'])->name('contact.store');

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('contact', [ContactController::class, 'index'])->name('contact.index');
        Route::put('contact', [ContactController::class, 'update'])->name('contact.update');

        Route::get('contact/messages', [ContactMessageController::class, 'index'])->name('contact.messages.index');
        Route::get('contact/messages/{message}', [ContactMessageController::class, 'show'])->name('contact.messages.show');
        Route::patch('contact/messages/{message}/status', [ContactMessageController::class, 'status'])->name('contact.messages.status');
        Route::delete('contact/messages/{message}', [ContactMessageController::class, 'destroy'])->name('contact.messages.destroy');
    });
