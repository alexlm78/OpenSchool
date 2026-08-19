<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(static function (): void {
    Route::middleware('guest:sanctum')->group(static function (): void {
        Route::post('/auth/login', [AuthController::class, 'login'])->name('api.auth.login');
    });

    Route::middleware('auth:sanctum')->group(static function (): void {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    });
});
