<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
   public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthorized.'], 401);
    }

    // Bandingkan nilai enum
    if ($user->role->value !== 'admin') {
        return response()->json(['message' => 'Forbidden. Hanya admin yang bisa mengakses.'], 403);
    }

    if ($user->status->value !== 'active') {
        return response()->json(['message' => 'Akun Anda tidak aktif.'], 403);
    }

    return $next($request);
}
}