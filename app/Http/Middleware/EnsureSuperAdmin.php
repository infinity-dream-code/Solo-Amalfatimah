<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('auth_is_superadmin')) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Akses ditolak. Master Sekolah hanya untuk Super Admin.');
        }

        return $next($request);
    }
}
