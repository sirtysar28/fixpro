<?php

namespace App\Http\Controllers;

use App\Models\Servis;
use App\Models\Pelanggan;
use Illuminate\Http\Request;

class ArsipServisController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // ============ BUILD QUERY BERDASARKAN ROLE ============

        // Super Admin → lihat SEMUA data dari semua cabang
        // Admin / Staff → lihat data cabang sendiri saja
        // User biasa → lihat histori servis miliknya sendiri saja (berdasarkan no_hp → pelanggan)

        if ($user->isUser() && !$user->isAdmin() && !$user->isStaff()) {
            // ---------- USER BIASA ----------
            $pelanggan = Pelanggan::where('no_hp', $user->phone)->first();

            $query = Servis::with(['pelanggan', 'teknisi', 'cabang']);

            if ($pelanggan) {
                $query->where('pelanggan_id', $pelanggan->id);
            } else {
                // Kalau belum ada pelanggan, return kosong
                $query->whereRaw('1 = 0');
            }

            // Stats user
            $baseQ = clone $query;
            $totalSelesai = (clone $baseQ)->where('status', 'Selesai')->count();
            $totalDiambil = (clone $baseQ)->where('diambil', true)->count();
            $totalGaransi = (clone $baseQ)
                ->where('status', 'Selesai')
                ->where('tanggal_garansi', '>=', now())
                ->count();

            $viewRole = 'user';

        } elseif ($user->isSuperAdmin()) {
            // ---------- SUPER ADMIN → SEMUA CABANG ----------
            $query = Servis::with(['pelanggan', 'teknisi', 'cabang']);

            // Kalau ada filter cabang dari request
            if ($request->filled('cabang_id')) {
                $query->where('cabang_id', $request->cabang_id);
            }

            $baseQ = clone $query;
            $totalSelesai = (clone $baseQ)->where('status', 'Selesai')->count();
            $totalDiambil = (clone $baseQ)->where('diambil', true)->count();
            $totalGaransi = (clone $baseQ)
                ->where('status', 'Selesai')
                ->where('tanggal_garansi', '>=', now())
                ->count();

            $viewRole = 'superadmin';

        } else {
            // ---------- ADMIN CABANG / STAFF ----------
            $cabangId = $user->getActiveCabangId();
            $query = Servis::with(['pelanggan', 'teknisi', 'cabang'])
                ->where('cabang_id', $cabangId);

            $baseQ = clone $query;
            $totalSelesai = (clone $baseQ)->where('status', 'Selesai')->count();
            $totalDiambil = (clone $baseQ)->where('diambil', true)->count();
            $totalGaransi = (clone $baseQ)
                ->where('status', 'Selesai')
                ->where('tanggal_garansi', '>=', now())
                ->count();

            $viewRole = 'admin';
        }

        // ============ FILTERS ============
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode', 'like', "%$s%")
                    ->orWhere('perangkat', 'like', "%$s%")
                    ->orWhere('imei', 'like', "%$s%")
                    ->orWhereHas('pelanggan', fn($q) => $q->where('nama', 'like', "%$s%")->orWhere('no_hp', 'like', "%$s%"));
            });
        }

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('dari')) {
            $query->whereDate('tanggal', '>=', $request->dari);
        }

        if ($request->filled('sampai')) {
            $query->whereDate('tanggal', '<=', $request->sampai);
        }

        $servis = $query->orderBy('created_at', 'desc')->paginate(20);

        // Ambil list cabang untuk filter super admin
        $cabangs = $user->isSuperAdmin()
            ? \App\Models\Cabang::where('aktif', true)->orderBy('id')->get()
            : collect();

        return view('arsip-servis.index', compact(
            'servis', 'totalSelesai', 'totalDiambil', 'totalGaransi', 'viewRole', 'cabangs'
        ));
    }

    public function lacak($kode)
    {
        $user = auth()->user();
        $query = Servis::with(['pelanggan', 'teknisi', 'cabang'])->where('kode', $kode);

        // Security: User biasa hanya bisa lacak servis miliknya
        if ($user->isUser() && !$user->isAdmin() && !$user->isStaff()) {
            $pelanggan = Pelanggan::where('no_hp', $user->phone)->first();
            if ($pelanggan) {
                $query->where('pelanggan_id', $pelanggan->id);
            } else {
                abort(404);
            }
        }
        // Admin/Staff hanya bisa lacak cabang sendiri (kecuali super admin)
        elseif (!$user->isSuperAdmin() && ($user->isAdmin() || $user->isStaff())) {
            $query->where('cabang_id', $user->getActiveCabangId());
        }
        // Super Admin bisa lacak semua

        $servis = $query->firstOrFail();

        $viewRole = 'user';
        if ($user->isSuperAdmin()) $viewRole = 'superadmin';
        elseif ($user->isAdmin() || $user->isStaff()) $viewRole = 'admin';

        return view('arsip-servis.lacak', compact('servis', 'viewRole'));
    }

    public function print($id)
    {
        $user = auth()->user();
        $query = Servis::with(['pelanggan', 'teknisi', 'cabang']);

        // Security check sama seperti lacak
        if ($user->isUser() && !$user->isAdmin() && !$user->isStaff()) {
            $pelanggan = Pelanggan::where('no_hp', $user->phone)->first();
            if ($pelanggan) {
                $query->where('pelanggan_id', $pelanggan->id);
            } else {
                abort(404);
            }
        } elseif (!$user->isSuperAdmin() && ($user->isAdmin() || $user->isStaff())) {
            $query->where('cabang_id', $user->getActiveCabangId());
        }

        $servis = $query->findOrFail($id);
        return view('arsip-servis.print', compact('servis'));
    }
}
