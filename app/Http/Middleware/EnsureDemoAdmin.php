<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDemoAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->get('admin_demo_authenticated')) {
            return redirect()->route('admin.login');
        }

        return $next($request)->header('Cache-Control', 'no-store, private');
    }
}
