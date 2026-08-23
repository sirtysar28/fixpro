<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            // Cek akun aktif
            if (!$request->user()->is_active) {
                auth()->logout();
                return redirect()->route('login')->with('error', 'Akun Anda belum diaktifkan. Hubungi admin.');
            }

            // Cek masa berlaku login (Super Admin tidak pernah expired)
            // Admin Cabang yang trial BISA expired
            if ($request->user()->isLoginExpired()) {
                auth()->logout();
                $msg = 'Masa aktif akun Anda sudah habis. Klik "Minta Kode Aktivasi via WhatsApp" di halaman login untuk memperpanjang.';
                if ($request->user()->isAdminCabang()) {
                    $msg .= ' Atau lakukan Request Aktivasi di menu Aktivasi Lisensi.';
                }
                $request->session()->flash('error', $msg);
                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
