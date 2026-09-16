<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkoutLog;

class WorkoutLogController extends Controller
{
    // 1. Ambil daftar catatan latihan milik user yang login
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $date = $request->query('date', now()->toDateString()); // Default hari ini

        $logs = WorkoutLog::with('exercise')
            ->where('user_id', $userId)
            ->whereDate('workout_date', $date)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $logs
        ]);
    }

    // 2. Simpan set latihan baru (Mendukung simpan 1 set maupun banyak set sekaligus)
    public function store(Request $request)
{
    $request->validate([
        'exercise_id'       => 'required|exists:exercises,id',
        'reps'              => 'required|integer|min:1',
        'duration_seconds'  => 'nullable|integer|min:0', // Durasi waktu latihan
        'weight'            => 'nullable|numeric|min:0',
        'workout_date'      => 'nullable|date',
    ]);

    $log = WorkoutLog::create([
        'user_id'          => $request->user()->id,
        'exercise_id'      => $request->exercise_id,
        'set_number'       => $request->set_number ?? 1,
        'weight'           => $request->weight ?? 0,
        'reps'             => $request->reps,
        'duration_seconds' => $request->duration_seconds ?? 0, // Simpan durasi
        'workout_date'     => $request->workout_date ?? now()->toDateString(),
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Sesi latihan berhasil diselesaikan dan dicatat!',
        'data'    => $log->load('exercise')
    ], 201);
}

    // 3. Hapus catatan set latihan
    public function destroy(Request $request, $id)
    {
        $log = WorkoutLog::where('user_id', $request->user()->id)->findOrFail($id);
        $log->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Set latihan berhasil dihapus!'
        ]);
    }
}