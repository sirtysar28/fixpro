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
     * Generate harga service secara massal/otomatis berdasarkan merk dan estimasi harga dasar.
     */
    public function generateMassal(Request $request)
    {
        $request->validate([
            'merk' => 'required|string|max:100',
            'base_price' => 'required|string',
        ]);

        $merk = $request->merk;
        // Bersihkan format rupiah menjadi angka integer (hapus titik dan koma)
        $basePrice = (int) str_replace(['.', ','], '', $request->base_price); 

        // Daftar kategori tipe HP umum untuk variasi harga
        $tipes = ['Seri A / Entry', 'Seri Note / Mid', 'Seri Flagship / Pro', 'Seri Lite / SE']; 
        
        // Template jasa otomatis dengan multiplier harga berdasarkan base_price
        $jasaTemplates = [
            ['kerusakan' => 'Ganti LCD Original', 'kategori' => 'ganti-sparepart', 'multiplier' => 1.5],
            ['kerusakan' => 'Ganti LCD OEM (High Quality)', 'kategori' => 'ganti-sparepart', 'multiplier' => 0.8],
            ['kerusakan' => 'Ganti Baterai', 'kategori' => 'ganti-sparepart', 'multiplier' => 0.4],
            ['kerusakan' => 'Ganti Kamera Belakang', 'kategori' => 'ganti-sparepart', 'multiplier' => 0.6],
            ['kerusakan' => 'Ganti Kamera Depan', 'kategori' => 'ganti-sparepart', 'multiplier' => 0.3],
            ['kerusakan' => 'Ganti Speaker / Buzzer', 'kategori' => 'ganti-sparepart', 'multiplier' => 0.3],
            ['kerusakan' => 'Ganti Flexing Charger', 'kategori' => 'ganti-sparepart', 'multiplier' => 0.3],
            ['kerusakan' => 'Ganti Housing / Casing Belakang', 'kategori' => 'ganti-sparepart', 'multiplier' => 0.5],
            ['kerusakan' => 'Flash Ulang / Reset Factory', 'kategori' => 'software', 'multiplier' => 0.2],
            ['kerusakan' => 'Atasi Bootloop / Stuck Logo', 'kategori' => 'software', 'multiplier' => 0.25],
            ['kerusakan' => 'Perbaikan Kena Air (Water Damage)', 'kategori' => 'water-damage', 'multiplier' => 0.5],
            ['kerusakan' => 'Unlock / Bypass Akun (Jika tersedia)', 'kategori' => 'unlock', 'multiplier' => 0.4],
        ];

        $cabangId = null;
        // Cek apakah user ingin global atau spesifik cabang (konsisten dengan method store)
        if (!($request->has('is_global') && $request->is_global)) {
            $cabangId = auth()->user()->getEffectiveCabangId();
        }

        $dataToInsert = [];
        $now = now();
        $userId = auth()->id();

        foreach ($tipes as $tipe) {
            foreach ($jasaTemplates as $template) {
                $harga = round($basePrice * $template['multiplier'], -3); // Pembulatan ke ribuan terdekat
                if ($harga < 50000) $harga = 50000; // Harga minimum Rp 50.000

                $dataToInsert[] = [
                    'cabang_id'    => $cabangId,
                    'merk_hp'      => $merk,
                    'tipe_hp'      => trim($merk . ' ' . $tipe), 
                    'kerusakan'    => $template['kerusakan'],
                    'kategori'     => $template['kategori'],
                    'harga_jasa'   => $harga,
                    'deskripsi'    => 'Harga estimasi otomatis untuk ' . $merk . '. Termasuk ongkos pasang.',
                    'aktif'        => 1,
                    'created_by'   => $userId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        // Insert ke database (gunakan chunk untuk menghindari limit query SQL)
        foreach (array_chunk($dataToInsert, 500) as $chunk) {
            ServicePrice::insert($chunk);
        }

        AuditLogService::log('service_price', 'generate', "Men-generate massal harga jasa untuk merk: {$merk} (Base: Rp " . number_format($basePrice) . ")");

        return redirect()->route('service-prices.index')->with('success', "✅ Berhasil men-generate " . count($dataToInsert) . " daftar harga service untuk merk {$merk}!");
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