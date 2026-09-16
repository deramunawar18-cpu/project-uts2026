<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Exercise;

class AdminExerciseController extends Controller
{
    // 1. TAMBAH LATIHAN
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'equipment'    => 'required|string',
            'muscle_group' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);

        $exercise = Exercise::create([
            'name'          => $request->name,
            'equipment'     => $request->equipment,
            'target_muscle' => $request->muscle_group ?? $request->target_muscle,
            'description'   => $request->instructions ?? $request->description,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Latihan berhasil ditambahkan!',
            'data'    => $exercise
        ], 201);
    }

    // 2. EDIT LATIHAN
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'equipment'    => 'required|string',
            'muscle_group' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);

        $exercise = Exercise::findOrFail($id);

        $exercise->update([
            'name'          => $request->name,
            'equipment'     => $request->equipment,
            'target_muscle' => $request->muscle_group ?? $request->target_muscle,
            'description'   => $request->instructions ?? $request->description,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Latihan berhasil diperbarui!',
            'data'    => $exercise
        ]);
    }

    // 3. HAPUS LATIHAN (YANG TADI KURANG)
    public function destroy($id)
    {
        $exercise = $id instanceof Exercise ? $id : Exercise::findOrFail($id);
        $exercise->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Latihan berhasil dihapus!'
        ]);
    }
}