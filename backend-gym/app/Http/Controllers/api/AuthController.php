<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // 1. REGISTER API
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Buat Sanctum Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'       => 'success',
            'message'      => 'Registrasi berhasil',
            'data'         => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 201);
    }

    // 2. LOGIN API
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($validated)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email atau password salah',
            ], 401);
        }

        $user  = User::where('email', $request->email)->firstOrFail();
        
        // Hapus token lama & buat token baru
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'       => 'success',
            'message'      => 'Login berhasil',
            'data'         => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }

    // 3. LOGOUT API
    public function logout(Request $request)
    {
        // Hapus token user yang sedang login
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil logout',
        ]);
    }

    // 4. GET PROFILE USER LOGGED IN
    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $request->user(),
        ]);
    }
}