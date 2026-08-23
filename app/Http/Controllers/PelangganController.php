<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class PelangganController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        // Super Admin (tanpa session filter): lihat semua pelanggan
        // Admin Cabang: pelanggan yang punya cabang_id ini, servis di cabang ini, ATAU usernya di cabang ini
        $query = Pelanggan::query();

        if ($user->isSuperAdmin() && $cabangId === null) {
            // Super Admin lihat semua
        } else {
            $query->where(function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId)
                  ->orWhereHas('servis', function ($sq) use ($cabangId) {
                      $sq->where('cabang_id', $cabangId);
                  })
                  ->orWhereHas('user', function ($sq) use ($cabangId) {
                      $sq->where('cabang_id', $cabangId);
                  });
            });
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', "%$s%")->orWhere('no_hp', 'like', "%$s%");
            });
        }
        $pelanggans = $query->orderBy('created_at', 'desc')->paginate(20);
        return view('pelanggan.index', compact('pelanggans'));
    }

    public function create()
    {
        return view('pelanggan.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required',
            'no_hp' => 'required',
            'alamat' => 'nullable',
        ]);

        // Cek duplikat no_hp di cabang ini saja (bukan global)
        $cabangId = auth()->user()->getEffectiveCabangId();
        $existing = Pelanggan::where('no_hp', $validated['no_hp'])->where('cabang_id', $cabangId)->first();
        if ($existing) {
            return redirect()->route('pelanggan.index')->with('error', 'No. HP sudah terdaftar sebagai pelanggan di cabang ini.');
        }

        // Set cabang_id dari user yang login
        $validated['cabang_id'] = auth()->user()->getActiveCabangId();

        $p = Pelanggan::create($validated);
        AuditLogService::created('pelanggan', "Menambahkan pelanggan: {$validated['nama']} ({$validated['no_hp']})", $p);

        return redirect()->route('pelanggan.index')->with('success', 'Pelanggan berhasil ditambahkan!');
    }

    public function edit(Pelanggan $pelanggan)
    {
        return view('pelanggan.edit', compact('pelanggan'));
    }

    public function update(Request $request, Pelanggan $pelanggan)
    {
        $validated = $request->validate([
            'nama' => 'required',
            'no_hp' => 'required',
            'alamat' => 'nullable',
        ]);

        $pelanggan->update($validated);
        AuditLogService::updated('pelanggan', "Mengupdate pelanggan: {$pelanggan->nama}", $pelanggan);
        return redirect()->route('pelanggan.index')->with('success', 'Pelanggan berhasil diupdate!');
    }

    public function destroy(Pelanggan $pelanggan)
    {
        AuditLogService::deleted('pelanggan', "Menghapus pelanggan: {$pelanggan->nama}", $pelanggan);
        $pelanggan->delete();
        return redirect()->route('pelanggan.index')->with('success', 'Pelanggan berhasil dihapus!');
    }

    public function search(Request $request)
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        // Prioritas: cari dulu di pelanggan cabang ini
        $pelanggan = Pelanggan::where('no_hp', $request->q);
        if ($cabangId !== null) {
            $pelanggan->where('cabang_id', $cabangId);
        }
        $pelanggan = $pelanggan->first();

        // Kalau tidak ketemu di cabang, cari global (untuk pelanggan baru yang belum punya servis)
        if (!$pelanggan) {
            $pelanggan = Pelanggan::where('no_hp', $request->q)->first();
        }

        return response()->json($pelanggan);
    }
}
