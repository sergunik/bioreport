<?php

declare(strict_types=1);

use App\Account\Controllers\AccountController;
use App\Auth\Controllers\ForgotPasswordController;
use App\Auth\Controllers\LoginController;
use App\Auth\Controllers\LogoutController;
use App\Auth\Controllers\RefreshController;
use App\Auth\Controllers\RegisterController;
use App\Auth\Controllers\ResetPasswordController;
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

Route::middleware('auth:jwt')->prefix('account')->group(function (): void {
    Route::post('/', [AccountController::class, 'store']);
    Route::get('/', [AccountController::class, 'show']);
    Route::patch('/', [AccountController::class, 'update']);
    Route::delete('/', [AccountController::class, 'destroy']);
});
