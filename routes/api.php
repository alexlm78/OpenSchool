<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Alumno\EnrollmentController;
use App\Http\Controllers\Api\Alumno\EvaluationController;
use App\Http\Controllers\Api\Alumno\GradeController;
use App\Http\Controllers\Api\Alumno\SubmissionController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(static function (): void {
    Route::middleware(['guest:sanctum', 'throttle:api.auth'])->group(static function (): void {
        Route::post('/auth/login', [AuthController::class, 'login'])->name('api.auth.login');
    });

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(static function (): void {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

        Route::apiResource('/enrollments', EnrollmentController::class)->only([
            'index', 'show',
        ]);

        Route::apiResource('/evaluations', EvaluationController::class)->only([
            'index', 'show',
        ]);

        Route::apiResource('/submissions', SubmissionController::class)->only([
            'index', 'show', 'store',
        ]);

        Route::apiResource('/grades', GradeController::class)->only([
            'index', 'show',
        ]);
    });
});
