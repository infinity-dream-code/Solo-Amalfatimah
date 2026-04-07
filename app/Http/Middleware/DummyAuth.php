<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DummyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('dummy_logged_in')) {
            return redirect()->route('login');
        }
        return $next($request);
    }
}
