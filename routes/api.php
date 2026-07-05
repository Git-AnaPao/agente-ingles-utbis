<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProgressController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    Route::get('/progress/levels', [ProgressController::class, 'levels']);
    Route::post('/progress/lessons/{lesson}/complete', [ProgressController::class, 'completeSublevel']);
    Route::post('/progress/lessons/{lesson}/attempt', [ProgressController::class, 'submitAttempt']);
    Route::post('/progress/attempts/batch', [ProgressController::class, 'submitBatch']);
    Route::get('/progress/stats', [ProgressController::class, 'stats']);
});
