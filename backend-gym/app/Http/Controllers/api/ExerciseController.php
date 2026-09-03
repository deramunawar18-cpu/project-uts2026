<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    // 1. Get All Exercises
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Exercise::all()
        ]);
    }

    // 2. Get Detail Exercise
    public function show($id)
    {
        $exercise = Exercise::find($id);

        if (!$exercise) {
            return response()->json(['message' => 'Latihan tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $exercise
        ]);
    }

    // 3. Create New Exercise
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_muscle' => 'required|string|max:255',
            'equipment' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $exercise = Exercise::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Latihan berhasil ditambahkan',
            'data' => $exercise
        ], 201);
    }

    // 4. Update Exercise
    public function update(Request $request, $id)
    {
        $exercise = Exercise::find($id);

        if (!$exercise) {
            return response()->json(['message' => 'Latihan tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'target_muscle' => 'sometimes|required|string|max:255',
            'equipment' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $exercise->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Latihan berhasil diperbarui',
            'data' => $exercise
        ]);
    }

    // 5. Delete Exercise
    public function destroy($id)
    {
        $exercise = Exercise::find($id);

        if (!$exercise) {
            return response()->json(['message' => 'Latihan tidak ditemukan'], 404);
        }

        $exercise->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Latihan berhasil dihapus'
        ]);
    }
}