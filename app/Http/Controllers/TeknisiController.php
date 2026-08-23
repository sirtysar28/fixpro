<?php

namespace App\Http\Controllers;

use App\Models\Teknisi;
use App\Models\Cabang;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class TeknisiController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        $query = Teknisi::withCount(['servis', 'servis as selesai_count' => fn ($q) => $q->where('status', 'Selesai')])
            ->with(['servis' => fn ($q) => $q->where('status', 'Selesai')]);

        // Super Admin + "Semua Cabang" = tampilkan semua, kelompokkan per cabang
        // Admin cabang / Super Admin pilih cabang spesifik = filter cabang itu saja
        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }

        $teknisis = $query->orderBy('nama')
            ->get()
            ->map(function ($t) {
                $t->omset = $t->servis->sum('biaya');
                $aktifCount = $t->servis()->whereIn('status', ['Masuk', 'Proses'])->count();
                $t->aktif_count = $aktifCount;
                return $t;
            });

        // Kelompokkan per cabang (hanya kalau Super Admin mode semua cabang)
        $showAll = $user->isSuperAdmin() && $cabangId === null;
        $teknisiByCabang = $showAll
            ? $teknisis->groupBy(fn($t) => $t->cabang?->nama ?? 'Tanpa Cabang')
            : null;

        return view('teknisi.index', compact('teknisis', 'teknisiByCabang', 'showAll'));
    }

    public function create()
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        // Super Admin (mode semua cabang): bisa pilih cabang
        // Admin Cabang / Super Admin pilih cabang spesifik: cabang otomatis, tidak perlu pilih
        $showCabangPicker = $user->isSuperAdmin() && $cabangId === null;
        $cabangs = Cabang::where('aktif', true)->orderBy('nama')->get();
        $activeCabang = $cabangId ? Cabang::find($cabangId) : null;

        return view('teknisi.create', compact('cabangs', 'showCabangPicker', 'activeCabang'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isSuperAll = $user->isSuperAdmin() && $user->getActiveCabangId() === null;

        $rules = [
            'nama' => 'required',
            'no_wa' => 'nullable',
            'spesialisasi' => 'required',
            'alamat' => 'nullable',
            'bagi_hasil' => 'nullable|numeric|min:0|max:100',
        ];

        // Cabang wajib cuma kalau Super Admin mode semua cabang
        if ($isSuperAll) {
            $rules['cabang_id'] = 'required|exists:cabang,id';
        }

        $validated = $request->validate($rules);

        // Admin Cabang / Super Admin pilih cabang: otomatis set cabang_id
        if (!$isSuperAll) {
            $validated['cabang_id'] = $user->getActiveCabangId();
        }

        $t = Teknisi::create($validated);
        AuditLogService::created('teknisi', "Menambahkan teknisi: {$validated['nama']} (Cabang: " . ($t->cabang?->nama ?? '-') . ")", $t);
        return redirect()->route('teknisi.index')->with('success', 'Teknisi berhasil ditambahkan!');
    }

    public function edit(Teknisi $teknisi)
    {
        $user = auth()->user();
        $isSuperAll = $user->isSuperAdmin() && $user->getActiveCabangId() === null;

        $cabangs = Cabang::where('aktif', true)->orderBy('nama')->get();
        $showCabangPicker = $isSuperAll;

        return view('teknisi.edit', compact('teknisi', 'cabangs', 'showCabangPicker'));
    }

    public function update(Request $request, Teknisi $teknisi)
    {
        $user = auth()->user();
        $isSuperAll = $user->isSuperAdmin() && $user->getActiveCabangId() === null;

        $rules = [
            'nama' => 'required',
            'no_wa' => 'nullable',
            'spesialisasi' => 'required',
            'alamat' => 'nullable',
            'aktif' => 'boolean',
            'bagi_hasil' => 'nullable|numeric|min:0|max:100',
            'link_user_id' => 'nullable|exists:users,id',
        ];

        // Cabang wajib cuma kalau Super Admin mode semua cabang
        if ($isSuperAll) {
            $rules['cabang_id'] = 'required|exists:cabang,id';
        }

        $validated = $request->validate($rules);

        // Admin Cabang / Super Admin pilih cabang: cabang_id tidak bisa diubah
        if (!$isSuperAll) {
            $validated['cabang_id'] = $user->getActiveCabangId();
        }

        $linkUserId = $validated['link_user_id'] ?? null;
        unset($validated['link_user_id']);

        $teknisi->update($validated);

        // Link/unlink user account
        \App\Models\User::where('teknisi_id', $teknisi->id)->update(['teknisi_id' => null]);

        if ($linkUserId) {
            \App\Models\User::where('id', $linkUserId)->update(['teknisi_id' => $teknisi->id]);
        }

        AuditLogService::updated('teknisi', "Mengupdate teknisi: {$teknisi->nama}" . ($linkUserId ? ' (linked user)' : ''), $teknisi);
        return redirect()->route('teknisi.index')->with('success', 'Teknisi berhasil diupdate!');
    }

    public function destroy(Teknisi $teknisi)
    {
        AuditLogService::deleted('teknisi', "Menghapus teknisi: {$teknisi->nama}", $teknisi);
        $teknisi->delete();
        return redirect()->route('teknisi.index')->with('success', 'Teknisi berhasil dihapus!');
    }
}
