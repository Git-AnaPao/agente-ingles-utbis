<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ProgressController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/auth/refresh', [AuthController::class, 'refresh'])->middleware('throttle:10,1');

Route::middleware(['auth:api', 'active'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me'])->middleware('throttle:60,1');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('throttle:10,1');
});

Route::middleware(['auth:api', 'active', 'verified', 'role:student'])->group(function () {
    Route::get('/progress/levels', [ProgressController::class, 'levels'])->middleware('throttle:60,1');
    Route::post('/progress/lessons/{lesson}/complete', [ProgressController::class, 'completeSublevel'])
        ->middleware('throttle:10,1');
    Route::post('/progress/lessons/{lesson}/attempt', [ProgressController::class, 'submitAttempt'])
        ->middleware('throttle:30,1');
    Route::post('/progress/attempts/batch', [ProgressController::class, 'submitBatch'])
        ->middleware('throttle:10,1');
    Route::get('/progress/stats', [ProgressController::class, 'stats'])->middleware('throttle:60,1');

    // Examenes: envio de respuestas + evaluacion IA
    Route::post('/exam/submit', [ExamController::class, 'submit'])->middleware('throttle:3,10');
    Route::get('/exam/{attempt}/result', [ExamController::class, 'result'])->middleware('throttle:30,1');
});
