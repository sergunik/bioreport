<?php

declare(strict_types=1);

use App\Account\Controllers\AccountController;
use App\Auth\Controllers\ForgotPasswordController;
use App\Auth\Controllers\LoginController;
use App\Auth\Controllers\LogoutController;
use App\Auth\Controllers\RefreshController;
use App\Auth\Controllers\RegisterController;
use App\Auth\Controllers\ResetPasswordController;
use App\DiagnosticReport\Controllers\DiagnosticReportController;
use App\Me\Controllers\PrivacyController;
use App\Me\Controllers\ProfileController;
use App\Me\Controllers\SecurityController;
use App\Observation\Controllers\ObservationController;
use App\System\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('health', [HealthController::class, 'index']);

Route::prefix('auth')->group(function (): void {
    Route::post('register', RegisterController::class);
    Route::post('login', LoginController::class)
        ->middleware('throttle:5,1');
    Route::post('refresh', RefreshController::class)
        ->middleware('throttle:10,1');
    Route::post('logout', LogoutController::class);

    Route::post('password/forgot', ForgotPasswordController::class)
        ->middleware('throttle:3,10');
    Route::post('password/reset', ResetPasswordController::class);
});

Route::middleware('auth:jwt')->group(function (): void {
    Route::prefix('account')->group(function (): void {
        Route::post('/', [AccountController::class, 'store']);
        Route::get('/', [AccountController::class, 'show']);
        Route::patch('/', [AccountController::class, 'update']);
        Route::delete('/', [AccountController::class, 'destroy']);
    });

    Route::prefix('me')->group(function (): void {
        Route::get('/', [ProfileController::class, 'show']);
        Route::patch('/', [ProfileController::class, 'update']);
        Route::delete('/', [PrivacyController::class, 'destroy']);
        Route::patch('/security', [SecurityController::class, 'update']);
    });

    Route::prefix('diagnostic-reports')->group(function (): void {
        Route::post('/', [DiagnosticReportController::class, 'store']);
        Route::get('/', [DiagnosticReportController::class, 'index']);
        Route::get('/{id}', [DiagnosticReportController::class, 'show']);
        Route::patch('/{id}', [DiagnosticReportController::class, 'update']);
        Route::delete('/{id}', [DiagnosticReportController::class, 'destroy']);
        Route::post('/{id}/observations', [ObservationController::class, 'store']);
    });

    Route::prefix('observations')->group(function (): void {
        Route::get('/{id}', [ObservationController::class, 'show']);
        Route::patch('/{id}', [ObservationController::class, 'update']);
        Route::delete('/{id}', [ObservationController::class, 'destroy']);
    });
});
