<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\AuthController;

// 1. Auth Routes (Public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 2. Exercise Routes (Public)
Route::apiResource('exercises', ExerciseController::class);

// 3. Protected Routes (Harus bawa Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('admin')->group(function () {
        Route::get('/users', [AuthController::class, 'getUsers']);
        Route::post('/exercises', [ExerciseController::class, 'store']);
        Route::put('/exercises/{id}', [ExerciseController::class, 'update']);
        Route::delete('/exercises/{id}', [ExerciseController::class, 'destroy']);
    });
});