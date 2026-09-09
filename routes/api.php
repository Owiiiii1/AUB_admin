<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/health', HealthController::class)->name('health');

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:api-login')
        ->name('auth.login');

    Route::middleware(['auth:sanctum', 'mobile.actor', 'throttle:api-mobile'])->group(function (): void {
        Route::get('/me', MeController::class)->name('me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');
        Route::get('/schedule', [ScheduleController::class, 'student'])->name('schedule.student');
        Route::get('/children/{student}/schedule', [ScheduleController::class, 'child'])->name('schedule.child');
        Route::get('/teacher/schedule', [ScheduleController::class, 'teacher'])->name('schedule.teacher');
        Route::get('/attendance', [AttendanceController::class, 'student'])->name('attendance.student');
        Route::get('/children/{student}/attendance', [AttendanceController::class, 'child'])->name('attendance.child');
        Route::get('/teacher/lessons/{scheduledLesson}/attendance', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::put('/teacher/lessons/{scheduledLesson}/attendance', [AttendanceController::class, 'update'])->name('attendance.update');
    });
});
