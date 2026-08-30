<?php

namespace App\Http\Controllers;

use App\Models\HargaGrosir;
use App\Models\HargaKhusus;
use App\Models\PelangganGrosir;
use App\Models\Stok;
use App\Services\AuditLogService;
use App\Services\GrosirService;
use Illuminate\Http\Request;

class HargaGrosirController extends Controller
{
    /**
     * Tabel Harga Grosir per produk (Harga Eceran, Grosir 1-3, Reseller, Distributor).
     * Selalu per cabang aktif — tidak campur antar toko.
     */
    public function index(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.harga.index')]);
        }
        $cabangId = $gate;

        $query = Stok::where('cabang_id', $cabangId)->with('hargaGrosir');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', "%$s%")
                    ->orWhere('kode', 'like', "%$s%")
                    ->orWhere('barcode', 'like', "%$s%");
            });
        }
        if ($request->filled('status')) {
            if ($request->status === 'sudah') {
                $query->whereHas('hargaGrosir', fn($q) => $q->where('aktif', true));
            } elseif ($request->status === 'belum') {
                $query->whereDoesntHave('hargaGrosir', fn($q) => $q->where('aktif', true));
            }
        }

        $stoks = $query->orderBy('nama')->paginate(20)->withQueryString();

        return view('grosir.harga.index', compact('stoks'));
    }

    /**
     * Simpan / update harga grosir satu produk (upsert).
     */
    public function store(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return redirect()->route('grosir.harga.index')->with('error', 'Pilih toko terlebih dahulu.');
        }
        $cabangId = $gate;

        $validated = $request->validate([
            'stok_id' => 'required|exists:stoks,id',
            'harga_grosir1' => 'nullable|numeric|min:0',
            'harga_grosir2' => 'nullable|numeric|min:0',
            'harga_grosir3' => 'nullable|numeric|min:0',
            'harga_reseller' => 'nullable|numeric|min:0',
            'harga_member' => 'nullable|numeric|min:0',
            'harga_distributor' => 'nullable|numeric|min:0',
            'min_qty_grosir1' => 'nullable|integer|min:1',
            'min_qty_grosir2' => 'nullable|integer|min:1',
            'min_qty_grosir3' => 'nullable|integer|min:1',
            'aktif' => 'nullable|boolean',
        ]);

        $stok = Stok::findOrFail($validated['stok_id']);
        // Guard: harga hanya untuk produk milik cabang sendiri
        if ((int) $stok->cabang_id !== (int) $cabangId) {
            return back()->with('error', 'Produk ini bukan milik cabang Anda.');
        }

        $data = [
            'cabang_id' => $cabangId,
            'stok_id' => $stok->id,
            'harga_grosir1' => $validated['harga_grosir1'] ?? null,
            'harga_grosir2' => $validated['harga_grosir2'] ?? null,
            'harga_grosir3' => $validated['harga_grosir3'] ?? null,
            'harga_reseller' => $validated['harga_reseller'] ?? null,
            'harga_member' => $validated['harga_member'] ?? null,
            'harga_distributor' => $validated['harga_distributor'] ?? null,
            'min_qty_grosir1' => $validated['min_qty_grosir1'] ?? 3,
            'min_qty_grosir2' => $validated['min_qty_grosir2'] ?? 6,
            'min_qty_grosir3' => $validated['min_qty_grosir3'] ?? 12,
            'aktif' => $request->boolean('aktif', true),
        ];

        $hg = HargaGrosir::updateOrCreate(
            ['stok_id' => $stok->id, 'cabang_id' => $cabangId],
            $data
        );

        AuditLogService::updated('harga_grosir', "Update harga grosir: {$stok->nama}", $hg);
        return back()->with('success', "Harga grosir {$stok->nama} berhasil disimpan.");
    }

    /**
     * Terapkan harga massal: turunkan harga eceran dengan persentase tertentu
     * untuk SEMUA produk cabang aktif yang belum punya harga level tsb.
     */
    public function massal(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return redirect()->route('grosir.harga.index')->with('error', 'Pilih toko terlebih dahulu.');
        }
        $cabangId = $gate;

        $validated = $request->validate([
            'level' => 'required|in:grosir1,grosir2,grosir3,reseller,member,distributor',
            'persen' => 'required|numeric|min:0|max:90',
        ]);

        $col = 'harga_' . $validated['level'];
        $stoks = Stok::where('cabang_id', $cabangId)->get();
        $count = 0;
        foreach ($stoks as $stok) {
            if ((float) $stok->jual <= 0) continue;
            $harga = round((float) $stok->jual * (1 - $validated['persen'] / 100), 0);
            HargaGrosir::updateOrCreate(
                ['stok_id' => $stok->id, 'cabang_id' => $cabangId],
                [$col => $harga]
            );
            $count++;
        }

        AuditLogService::log('harga_grosir', 'update', "Harga massal {$validated['level']} (diskon {$validated['persen']}%) untuk {$count} produk");
        return back()->with('success', "Harga " . GrosirService::LEVELS[$validated['level']] . " dibuat otomatis (eceran -{$validated['persen']}%) untuk {$count} produk.");
    }

    /**
     * Harga khusus pelanggan (prioritas tertinggi di atas level harga).
     */
    public function khusus(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.harga.khusus')]);
        }
        $cabangId = $gate;

        $pelanggans = PelangganGrosir::where('cabang_id', $cabangId)->orderBy('nama')->get();
        $selected = $request->filled('pelanggan')
            ? $pelanggans->firstWhere('id', $request->pelanggan)
            : $pelanggans->first();

        $hargaKhusus = collect([]);
        if ($selected) {
            $hargaKhusus = HargaKhusus::with('stok')
                ->where('pelanggan_grosir_id', $selected->id)
                ->whereHas('stok', fn($q) => $q->where('cabang_id', $cabangId))
                ->get();
        }

        return view('grosir.harga.khusus', compact('pelanggans', 'selected', 'hargaKhusus'));
    }

    /** Simpan harga khusus satu produk untuk satu pelanggan */
    public function storeKhusus(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return redirect()->route('grosir.harga.khusus')->with('error', 'Pilih toko terlebih dahulu.');
        }
        $cabangId = $gate;

        $validated = $request->validate([
            'pelanggan_grosir_id' => 'required|exists:pelanggan_grosirs,id',
            'stok_id' => 'required|exists:stoks,id',
            'harga' => 'required|numeric|min:0',
        ]);

        $pelanggan = PelangganGrosir::findOrFail($validated['pelanggan_grosir_id']);
        GrosirService::assertAksesCabang($pelanggan->cabang_id);

        $stok = Stok::findOrFail($validated['stok_id']);
        if ((int) $stok->cabang_id !== (int) $cabangId) {
            return back()->with('error', 'Produk ini bukan milik cabang Anda.');
        }

        HargaKhusus::updateOrCreate(
            ['pelanggan_grosir_id' => $pelanggan->id, 'stok_id' => $stok->id],
            ['harga' => $validated['harga']]
        );

        AuditLogService::log('harga_khusus', 'create', "Harga khusus {$stok->nama} untuk {$pelanggan->nama}: Rp " . number_format($validated['harga']));
        return back()->with('success', "Harga khusus {$stok->nama} untuk {$pelanggan->nama} disimpan.");
    }

    public function destroyKhusus(HargaKhusus $harga_khusus)
    {
        $pelanggan = $harga_khusus->pelanggan;
        GrosirService::assertAksesCabang($pelanggan?->cabang_id);
        $nama = $harga_khusus->stok?->nama ?? 'produk';
        $harga_khusus->delete();
        AuditLogService::deleted('harga_khusus', "Hapus harga khusus {$nama} untuk {$pelanggan?->nama}");
        return back()->with('success', 'Harga khusus dihapus.');
    }
}
