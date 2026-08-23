<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Hanya Super Admin (is_super_admin = true) yang boleh akses.
     * Admin Cabang biasa TIDAK bisa, walaupun role-nya 'Admin'.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!$request->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin (Pusat) yang bisa mengakses halaman ini.');
        }

        return $next($request);
    }
}
