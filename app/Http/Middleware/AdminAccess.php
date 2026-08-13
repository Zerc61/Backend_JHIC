<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->user()->role->value, ['admin', 'manager', 'umkm'])) {
            abort(403, 'Akses ditolak. Halaman ini hanya untuk admin, manager, dan pemilik UMKM.');
        }

        return $next($request);
    }
}