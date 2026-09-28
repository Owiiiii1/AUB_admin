<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PrivacyPageController;
use Illuminate\Support\Facades\Route;

Route::get('/privacy', [PrivacyPageController::class, 'policy'])->name('privacy.policy');
Route::get('/data-deletion', [PrivacyPageController::class, 'deletion'])->name('privacy.deletion');
Route::post('/data-deletion', [PrivacyPageController::class, 'storeDeletion'])
    ->middleware('throttle:privacy-deletion')
    ->name('privacy.deletion.store');

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/', [AuthenticatedSessionController::class, 'store']);
});

// Owl Admin routes
require __DIR__.'/owl-admin-pages.php';
require __DIR__.'/owl-admin-auth.php';
