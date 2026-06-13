<?php

use App\Http\Controllers\CalculationExerciseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SqlExerciseController;
use App\Http\Controllers\UmlExerciseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->prefix('it')->group(function () {
    Route::get('sql-uebung', [SqlExerciseController::class, 'index'])
        ->name('sql-uebung');
    Route::post('sql-uebung/{difficulty}', [SqlExerciseController::class, 'generate'])
        ->name('sql-uebung.generate');
    Route::post('sql-uebung/{exercise}/execute', [SqlExerciseController::class, 'executeUserQuery'])
        ->whereNumber('exercise')
        ->name('sql-uebung.execute');

    Route::get('calculation-exercises', [CalculationExerciseController::class, 'overview'])
        ->name('calculation-exercises.index');
    Route::post('calculation-exercises/check', [CalculationExerciseController::class, 'check'])
        ->name('calculation-exercises.check');
    Route::post('calculation-exercises/{topic}', [CalculationExerciseController::class, 'generate'])
        ->name('calculation-exercises.generate');
    Route::get('/uml', [UmlExerciseController::class, 'create'])->name('uml.form');
    Route::post('/uml', [UmlExerciseController::class, 'render'])->name('uml.render');
});

Route::prefix('wiso')->group(function () {});

require __DIR__.'/auth.php';
