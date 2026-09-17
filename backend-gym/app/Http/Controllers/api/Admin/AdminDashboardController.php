<?php

namespace App\Http\Controllers\Api\Admin; // 👈 Perbarui namespace ini

use App\Http\Controllers\Controller; // 👈 Import base controller
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Hitung total seluruh user terdaftar di tabel users
        $totalUsers = User::count();

        // Hitung total sesi log latihan yang tersimpan
        $totalWorkouts = WorkoutLog::count();

        // Hitung total volume akumulasi angkatan (weight * reps)
        $totalVolume = WorkoutLog::selectRaw('SUM(weight * reps) as total')->value('total') ?? 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_users'     => $totalUsers,
                'totalUsers'      => $totalUsers,
                'total_workouts'  => $totalWorkouts,
                'totalWorkouts'   => $totalWorkouts,
                'total_volume_kg' => (float) $totalVolume,
                'totalVolumeKg'   => (float) $totalVolume,
            ]
        ]);
    }
}