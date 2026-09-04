<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Hitung metrik sistem
        $totalUsers = User::where('role', 'user')->count();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'totalUsers'          => $totalUsers,
                'totalWorkouts'       => 142, // ganti dengan Workout::count() jika sudah ada modelnya
                'totalVolumeKg'       => 45200, // total kg angkatan
                'popularExercises'    => [
                    ['name' => 'Barbell Bench Press', 'logs' => 84],
                    ['name' => 'Barbell Squat', 'logs' => 65],
                    ['name' => 'Deadlift', 'logs' => 52],
                ]
            ]
        ], 200);
    }
}