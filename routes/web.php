<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\PlacementController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\ProfileController;
use App\Models\Lesson;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/dashboard', function () {
    $user = request()->user();

    if ($user->role === 'professor') {
        return redirect()->route('professor.dashboard');
    }
    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    if (!$user->placementTests()->exists()) {
        return redirect()->route('placement.index');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/levels', [LevelController::class, 'index'])->name('levels.index');
    Route::post('/lessons/{lesson}/complete', [LevelController::class, 'complete'])->name('lessons.complete');

    Route::get('/lessons/{lesson}/learn', function (Lesson $lesson) {
        return view('levels.learn', compact('lesson'));
    })->name('lessons.learn');

    Route::middleware('verified')->group(function () {
        Route::get('/placement', [PlacementController::class, 'index'])->name('placement.index');
        Route::post('/placement', [PlacementController::class, 'submit'])->name('placement.submit');
        Route::get('/placement/skip', [PlacementController::class, 'skip'])->name('placement.skip');
    });

    Route::middleware('role:professor,admin')->prefix('professor')->group(function () {
        Route::get('/dashboard', [ProfessorController::class, 'dashboard'])->name('professor.dashboard');
        Route::get('/students/{user}/progress', [ProfessorController::class, 'studentProgress'])->name('professor.student-progress');
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
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
