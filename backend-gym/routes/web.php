<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExerciseController;

Route::get('/', function () {
    return view('welcome');
});

// Route Web untuk Exercises
Route::get('/exercises', [ExerciseController::class, 'index']);
Route::get('/exercises/{id}', [ExerciseController::class, 'show']);