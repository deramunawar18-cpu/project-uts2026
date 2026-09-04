<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;

class AdminExerciseController extends Controller
{
    /**
     * 1. GET ALL EXERCISES
     * Menampilkan semua data latihan (bisa difilter pencarian dan grup otot)
     * URL: GET /api/admin/exercises?search=bench&muscle=Chest
     */
    public function index(Request $request)
    {
        $query = Exercise::query();

        // Fitur pencarian nama latihan
        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Fitur filter berdasarkan grup otot
        if ($request->has('muscle') && $request->muscle != '') {
            $query->where('muscle_group', $request->muscle);
        }

        $exercises = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'status'  => 'success',
            'count'   => $exercises->count(),
            'data'    => $exercises
        ], 200);
    }

    /**
     * 2. STORE EXERCISE
     * Menambahkan latihan baru ke database
     * URL: POST /api/admin/exercises
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:exercises,name',
            'muscle_group' => 'required|string|max:100',
            'equipment'    => 'required|string|max:100',
            'instructions' => 'nullable|string',
        ]);

        $exercise = Exercise::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Latihan berhasil ditambahkan ke master data',
            'data'    => $exercise
        ], 201);
    }

    /**
     * 3. SHOW DETAIL EXERCISE
     * Menampilkan 1 data spesifik berdasarkan ID
     * URL: GET /api/admin/exercises/{id}
     */
    public function show($id)
    {
        $exercise = Exercise::find($id);

        if (!$exercise) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Latihan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $exercise
        ], 200);
    }

    /**
     * 4. UPDATE EXERCISE
     * Mengubah data latihan yang sudah ada
     * URL: PUT /api/admin/exercises/{id}
     */
    public function update(Request $request, $id)
    {
        $exercise = Exercise::find($id);

        if (!$exercise) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Latihan tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:exercises,name,' . $id,
            'muscle_group' => 'required|string|max:100',
            'equipment'    => 'required|string|max:100',
            'instructions' => 'nullable|string',
        ]);

        $exercise->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data latihan berhasil diperbarui',
            'data'    => $exercise
        ], 200);
    }

    /**
     * 5. DELETE EXERCISE
     * Menghapus latihan dari database
     * URL: DELETE /api/admin/exercises/{id}
     */
    public function destroy($id)
    {
        $exercise = Exercise::find($id);

        if (!$exercise) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Latihan tidak ditemukan'
            ], 404);
        }

        $exercise->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Latihan berhasil dihapus dari sistem'
        ], 200);
    }
}