<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\AdminExerciseController;

// 1. Tes Koneksi / Health Check
Route::get('/ping', function () {
    return response()->json([
        'status'  => 'success',
        'message' => 'Koneksi ke backend Laravel BERHASIL! 🚀',
        'time'    => now()->toDateTimeString()
    ]);
});

// 2. Auth Routes (Public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 3. Exercise Routes (Public untuk Member / Frontend App)
Route::apiResource('exercises', ExerciseController::class)->only(['index', 'show']);

// 4. Protected Routes (User Wajib Login)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Khusus Admin (Bisa Pakai Prefix /admin)
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users', [AuthController::class, 'getUsers']);
        
        // CRUD Exercise Khusus Admin
        Route::get('/exercises', [AdminExerciseController::class, 'index']);
        Route::post('/exercises', [AdminExerciseController::class, 'store']);
        Route::get('/exercises/{id}', [AdminExerciseController::class, 'show']);
        Route::put('/exercises/{id}', [AdminExerciseController::class, 'update']);
        Route::delete('/exercises/{id}', [AdminExerciseController::class, 'destroy']);
    });
});