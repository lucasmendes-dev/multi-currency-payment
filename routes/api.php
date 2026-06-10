<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Authentication Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api automatically by Laravel 12.
| Public routes (no token required): register, login.
| Protected routes (Bearer token required): me, logout, refresh.
|
*/

// ── Auth ────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::get('/me',       [AuthController::class, 'me']);
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

// ── Payments ─────────────────────────────────────────────────────────────
Route::prefix('payments')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [PaymentRequestController::class, 'index']);
    Route::post('/', [PaymentRequestController::class, 'store']);
});
