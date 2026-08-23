<?php

namespace App\Http\Controllers;

use App\Models\ServicePrice;
use App\Models\Cabang;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class ServicePriceController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();

        $query = ServicePrice::with(['cabang']);

        // Non-super-admin hanya lihat data cabang sendiri + data global (cabang_id = null)
        if ($cabangId !== null) {
            $query->where(function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId)
                  ->orWhereNull('cabang_id');
            });
        }

        // Filter search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kerusakan', 'like', "%$s%")
                  ->orWhere('merk_hp', 'like', "%$s%")
                  ->orWhere('tipe_hp', 'like', "%$s%")
                  ->orWhere('deskripsi', 'like', "%$s%")
                  ->orWhere('kategori', 'like', "%$s%");
            });
        }

        // Filter kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Filter merk
        if ($request->filled('merk')) {
            $query->where('merk_hp', $request->merk);
        }

        // Hanya aktif (default)
        if ($request->filled('show_all') && $request->show_all) {
            // tampilkan semua termasuk non-aktif
        } else {
            $query->where('aktif', true);
        }

        $prices = $query->orderBy('merk_hp')->orderBy('kerusakan')->paginate(25);

        // Stats
        $totalItems = $query->clone()->where('aktif', true)->count();
        $avgPrice = $query->clone()->where('aktif', true)->avg('harga_jasa');

        // Daftar merk unik untuk filter
        $merkList = ServicePrice::where('aktif', true)
            ->whereNotNull('merk_hp')
            ->where('merk_hp', '!=', '')
            ->distinct()
            ->orderBy('merk_hp')
            ->pluck('merk_hp');

        // Kategori list
        $kategoriList = ServicePrice::where('aktif', true)
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori');

        return view('service-prices.index', compact('prices', 'totalItems', 'avgPrice', 'merkList', 'kategoriList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'merk_hp' => 'nullable|string|max:100',
            'tipe_hp' => 'nullable|string|max:100',
            'kerusakan' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:500',
            'harga_jasa' => 'required|numeric|min:0',
            'kategori' => 'nullable|string|max:100',
            'is_global' => 'nullable',
        ]);

        $cabangId = null;
        if (!($request->has('is_global') && $request->is_global)) {
            $cabangId = auth()->user()->getEffectiveCabangId();
        }

        ServicePrice::create([
            'cabang_id' => $cabangId,
            'merk_hp' => $validated['merk_hp'] ?? null,
            'tipe_hp' => $validated['tipe_hp'] ?? null,
            'kerusakan' => $validated['kerusakan'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'harga_jasa' => $validated['harga_jasa'],
            'kategori' => $validated['kategori'] ?? 'umum',
            'aktif' => true,
            'created_by' => auth()->id(),
        ]);

        AuditLogService::log('service_price', 'create', "Menambahkan harga jasa: {$validated['kerusakan']} - Rp " . number_format($validated['harga_jasa']));

        return back()->with('success', 'Harga jasa berhasil ditambahkan!');
    }

    public function update(Request $request, ServicePrice $servicePrice)
    {
        $validated = $request->validate([
            'merk_hp' => 'nullable|string|max:100',
            'tipe_hp' => 'nullable|string|max:100',
            'kerusakan' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:500',
            'harga_jasa' => 'required|numeric|min:0',
            'kategori' => 'nullable|string|max:100',
            'aktif' => 'nullable',
        ]);

        $servicePrice->update([
            'merk_hp' => $validated['merk_hp'] ?? null,
            'tipe_hp' => $validated['tipe_hp'] ?? null,
            'kerusakan' => $validated['kerusakan'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'harga_jasa' => $validated['harga_jasa'],
            'kategori' => $validated['kategori'] ?? 'umum',
            'aktif' => $request->has('aktif'),
        ]);

        AuditLogService::log('service_price', 'update', "Mengupdate harga jasa: {$validated['kerusakan']} - Rp " . number_format($validated['harga_jasa']));

        return back()->with('success', 'Harga jasa berhasil diupdate!');
    }

    public function destroy(ServicePrice $servicePrice)
    {
        AuditLogService::log('service_price', 'delete', "Menghapus harga jasa: {$servicePrice->kerusakan}");
        $servicePrice->delete();
        return back()->with('success', 'Harga jasa berhasil dihapus!');
    }

    /**
     * API: cari harga jasa berdasarkan keluhan/kerusakan (autocomplete di form input servis)
     */
    public function search(Request $request)
    {
        $q = $request->input('q', '');
        $merk = $request->input('merk', '');
        $cabangId = auth()->user()->getActiveCabangId();

        $query = ServicePrice::where('aktif', true)
            ->where(function ($sq) use ($cabangId) {
                $sq->whereNull('cabang_id')
                  ->orWhere('cabang_id', $cabangId);
            });

        if ($q) {
            $query->where('kerusakan', 'like', "%$q%");
        }
        if ($merk) {
            $query->where(function ($sq) use ($merk) {
                $sq->where('merk_hp', $merk)
                  ->orWhereNull('merk_hp');
            });
        }

        $results = $query->orderBy('kerusakan')->limit(20)->get();

        return response()->json($results);
    }
}
