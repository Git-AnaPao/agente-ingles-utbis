<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\ListeningController;
use App\Http\Controllers\PlacementController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'active', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('verified')->group(function () {
        Route::get('/levels', [LevelController::class, 'index'])->name('levels.index');
        Route::get('/lessons/{lesson}/learn', [LevelController::class, 'learn'])->name('lessons.learn');
        Route::post('/lessons/{lesson}/check-practice', [LevelController::class, 'checkPractice'])
            ->middleware('throttle:10,1')
            ->name('lessons.check-practice');
        Route::post('/lessons/{lesson}/speaking-feedback/{listeningLesson}', [LevelController::class, 'speakingFeedback'])
            ->middleware('throttle:6,1')
            ->name('lessons.speaking-feedback');

        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::post('/chat/send', [ChatController::class, 'send'])
            ->middleware('throttle:12,1')
            ->name('chat.send');

        Route::get('/placement', [PlacementController::class, 'index'])->name('placement.index');
        Route::post('/placement', [PlacementController::class, 'submit'])
            ->middleware('throttle:3,1')
            ->name('placement.submit');
        Route::post('/placement/skip', [PlacementController::class, 'skip'])->name('placement.skip');
        Route::post('/placement/retake', [PlacementController::class, 'retake'])->name('placement.retake');

        Route::get('/listening', [ListeningController::class, 'index'])->name('listening.index');
        Route::get('/listening/{listeningLesson}', [ListeningController::class, 'show'])->name('listening.show');
        Route::get('/listening/{listeningLesson}/audio', [ListeningController::class, 'streamAudio'])->name('listening.audio');
        Route::post('/listening/{listeningLesson}/check', [ListeningController::class, 'checkAnswers'])
            ->middleware('throttle:10,1')
            ->name('listening.check');
    });

    Route::middleware(['verified', 'role:professor,admin'])->prefix('professor')->group(function () {
        Route::get('/dashboard', [ProfessorController::class, 'dashboard'])->name('professor.dashboard');
        Route::get('/students/{user}/progress', [ProfessorController::class, 'studentProgress'])->name('professor.student-progress');
    });

    Route::middleware(['verified', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::get('/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
        Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    });
});

require __DIR__.'/auth.php';
