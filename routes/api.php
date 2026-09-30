<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\PasskeyController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // Public Routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);

        Route::post('login', [AuthController::class, 'login']);

        Route::post('forgot-password', [AuthController::class, 'sendPasswordResetLink']);

        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        Route::get('/{provider}', [AuthController::class, 'redirectToProvider'])->where('provider', 'google|facebook');

        Route::get('/{provider}/callback', [AuthController::class, 'handleProviderCallback'])->where('provider', 'google|facebook');

        Route::post('two-factor-challenge', [AuthController::class, 'twoFactorChallenge']);

        Route::get('passkeys/options', [PasskeyController::class, 'loginOptions']);

        Route::post('passkeys/login', [PasskeyController::class, 'login']);
    });

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])
        ->name('password.reset');

    Route::get('/email/verify', function () {
        return view('auth.verify-email', ['status' => 'notice']);
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed'])
        ->name('verification.verify');

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::post('auth/two-factor/enable', [TwoFactorController::class, 'enable']);
        Route::post('auth/two-factor/confirm', [TwoFactorController::class, 'confirm']);
        Route::delete('auth/two-factor/disable', [TwoFactorController::class, 'disable']);
        Route::get('auth/two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes']);
        Route::post('auth/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);

        Route::get('passkeys/options', [PasskeyController::class, 'registrationOptions']);
        Route::post('passkeys', [PasskeyController::class, 'store']);
        Route::get('passkeys', [PasskeyController::class, 'index']);
        Route::delete('passkeys/{passkey}', [PasskeyController::class, 'destroy']);

        Route::post('email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
            ->name('verification.send');

        Route::apiResource('job-applications', JobApplicationController::class);
    });
});
