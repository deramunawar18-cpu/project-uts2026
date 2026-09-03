<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExerciseController;

Route::apiResource('exercises', ExerciseController::class);