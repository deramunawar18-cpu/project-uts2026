<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah user ber-role admin
        if ($request->user() && $request->user()->role === 'admin') {
            return $next($request);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Akses ditolak. Anda bukan Admin!'
        ], 403);
    }
}