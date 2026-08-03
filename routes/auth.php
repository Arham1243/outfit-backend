<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.any:sanctum', 'check.active'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::patch('/auth/ui-preferences', [AuthController::class, 'updateUiPreferences']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
    Route::post('/password/set', [PasswordResetController::class, 'setupPassword']);
    Route::get('/password/set/status', [PasswordResetController::class, 'setupPasswordTokenStatus']);

    Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
    Route::post('/otp/resend', [OtpController::class, 'resendOtp']);
});
