<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManagerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized. Silakan login terlebih dahulu.',
            ], 401);
        }

        if ($request->user()->role->value !== 'manager') {
            return response()->json([
                'message' => 'Forbidden. Hanya manager yang bisa mengakses.',
            ], 403);
        }

        if ($request->user()->status->value !== 'active') {
            return response()->json([
                'message' => 'Akun Anda tidak aktif. Hubungi super admin.',
            ], 403);
        }

        return $next($request);
    }
}