<?php

namespace App\Http\Controllers;

use App\Models\PelangganGrosir;
use App\Models\PesananGrosir;
use App\Models\PesananGrosirItem;
use App\Models\Stok;
use App\Services\AuditLogService;
use App\Services\GrosirService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PesananGrosirController extends Controller
{
    public function index(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.pesanan.index')]);
        }
        $cabangId = $gate;

        $query = PesananGrosir::with(['pelanggan', 'user'])
            ->where('cabang_id', $cabangId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('no_pesanan', 'like', "%$s%")
                    ->orWhere('nama_pelanggan', 'like', "%$s%");
            });
        }

        $pesanans = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('grosir.pesanan.index', compact('pesanans'));
    }

    public function create()
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.pesanan.create')]);
        }
        $cabangId = $gate;

        $pelanggans = PelangganGrosir::where('cabang_id', $cabangId)
            ->where('aktif', true)->orderBy('nama')->get();
        $gudangs = GrosirService::gudangOptions(auth()->user());

        return view('grosir.pesanan.create', compact('pelanggans', 'gudangs'));
    }

    /**
     * API: produk untuk form pesanan (harga sesuai level, stok tersedia = stok - reserved)
     */
    public function apiProduk(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return response()->json(['products' => []]);
        }
        $cabangId = $gate;
        $sumberCabangId = (int) $request->get('sumber', $cabangId);
        $allowed = array_merge([$cabangId], array_column(GrosirService::gudangOptions(auth()->user()), 'id'));
        if (!in_array($sumberCabangId, $allowed, true)) $sumberCabangId = $cabangId;

        $q = trim((string) $request->get('q', ''));
        $level = (string) $request->get('level', 'grosir1');
        $pelangganId = $request->get('pelanggan_id');
        $pelanggan = $pelangganId ? PelangganGrosir::where('cabang_id', $cabangId)->find($pelangganId) : null;

        $products = Stok::where('cabang_id', $sumberCabangId)
            ->when($q !== '', fn($query) => $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%");
            }))
            ->orderBy('nama')->limit(20)->get();

        return response()->json([
            'products' => $products->map(function ($p) use ($pelanggan, $level) {
                $harga = GrosirService::resolveHarga($p, $pelanggan, $level);
                return [
                    'id' => $p->id,
                    'kode' => $p->kode,
                    'nama' => $p->nama,
                    'stok' => (int) $p->stok,
                    'tersedia' => $p->stok_tersedia,
                    'harga' => $harga['harga'],
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return redirect()->route('grosir.pesanan.index')->with('error', 'Pilih toko terlebih dahulu.');
        }
        $cabangId = $gate;

        $validated = $request->validate([
            'pelanggan_grosir_id' => 'nullable|exists:pelanggan_grosirs,id',
            'level_harga' => 'required|in:' . implode(',', array_keys(GrosirService::LEVELS)),
            'sumber' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.stok_id' => 'required|exists:stoks,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0',
            'alamat_kirim' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        $sumberCabangId = (int) ($validated['sumber'] ?? $cabangId);
        $allowed = array_merge([$cabangId], array_column(GrosirService::gudangOptions(auth()->user()), 'id'));
        if (!in_array($sumberCabangId, $allowed, true)) {
            return back()->with('error', 'Sumber stok tidak valid.');
        }

        $pelanggan = null;
        if (!empty($validated['pelanggan_grosir_id'])) {
            $pelanggan = PelangganGrosir::where('cabang_id', $cabangId)->find($validated['pelanggan_grosir_id']);
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['qty'] * $item['harga_satuan'];
            }
            $diskon = (float) ($validated['diskon'] ?? 0);

            $pesanan = PesananGrosir::create([
                'no_pesanan' => PesananGrosir::generateNoPesanan(),
                'cabang_id' => $cabangId,
                'sumber_cabang_id' => $sumberCabangId,
                'user_id' => auth()->id(),
                'pelanggan_grosir_id' => $pelanggan?->id,
                'nama_pelanggan' => $pelanggan?->nama ?? 'Umum',
                'level_harga' => $validated['level_harga'],
                'tanggal' => now(),
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'total' => max(0, $subtotal - $diskon),
                'status' => 'Menunggu',
                'alamat_kirim' => $validated['alamat_kirim'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $stok = Stok::find($item['stok_id']);
                PesananGrosirItem::create([
                    'pesanan_grosir_id' => $pesanan->id,
                    'stok_id' => $stok->id,
                    'kode' => $stok->kode,
                    'nama' => $stok->nama,
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['qty'] * $item['harga_satuan'],
                ]);
            }

            DB::commit();
            AuditLogService::created('pesanan_grosir', "Pesanan grosir {$pesanan->no_pesanan} ({$pesanan->nama_pelanggan})", $pesanan);

            return redirect()->route('grosir.pesanan.show', $pesanan)
                ->with('success', "Pesanan {$pesanan->no_pesanan} dibuat. Status: Menunggu Konfirmasi.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    public function show(PesananGrosir $pesanan_grosir)
    {
        GrosirService::assertAksesCabang($pesanan_grosir->cabang_id);
        $pesanan_grosir->load(['items.stok', 'pelanggan', 'user', 'penjualan']);

        return view('grosir.pesanan.show', compact('pesanan_grosir'));
    }

    /**
     * Proses pesanan → stok direservasi (reserved naik, stok fisik tetap).
     */
    public function proses(PesananGrosir $pesanan_grosir)
    {
        GrosirService::assertAksesCabang($pesanan_grosir->cabang_id);

        if ($pesanan_grosir->status !== 'Menunggu') {
            return back()->with('error', 'Hanya pesanan berstatus Menunggu yang bisa diproses.');
        }

        // Cek stok tersedia cukup untuk reservasi
        foreach ($pesanan_grosir->items as $item) {
            $stok = $item->stok;
            if (!$stok || $stok->stok_tersedia < $item->qty) {
                return back()->with('error', "Stok {$item->nama} tidak cukup untuk reservasi (tersedia: " . ($stok?->stok_tersedia ?? 0) . ").");
            }
        }

        foreach ($pesanan_grosir->items as $item) {
            $item->stok->increment('reserved', $item->qty);
        }
        $pesanan_grosir->update(['status' => 'Diproses']);

        AuditLogService::log('pesanan_grosir', 'update', "Pesanan {$pesanan_grosir->no_pesanan} diproses — stok direservasi");
        return back()->with('success', 'Pesanan diproses. Stok direservasi.');
    }

    /**
     * Batalkan pesanan → lepas reservasi (jika sempat diproses).
     */
    public function batal(Request $request, PesananGrosir $pesanan_grosir)
    {
        GrosirService::assertAksesCabang($pesanan_grosir->cabang_id);

        if (!in_array($pesanan_grosir->status, ['Menunggu', 'Diproses'])) {
            return back()->with('error', 'Pesanan ini sudah selesai/dibatalkan.');
        }

        if ($pesanan_grosir->status === 'Diproses') {
            foreach ($pesanan_grosir->items as $item) {
                if ($item->stok) {
                    $item->stok->decrement('reserved', min((int) ($item->stok->reserved ?? 0), (int) $item->qty));
                }
            }
        }

        $pesanan_grosir->update(['status' => 'Dibatalkan']);
        AuditLogService::log('pesanan_grosir', 'update', "Pesanan {$pesanan_grosir->no_pesanan} dibatalkan");

        return redirect()->route('grosir.pesanan.index')->with('success', "Pesanan {$pesanan_grosir->no_pesanan} dibatalkan.");
    }

    /**
     * Checkout pesanan → buat nota penjualan grosir (lewat POS, prefilled).
     */
    public function checkout(PesananGrosir $pesanan_grosir)
    {
        GrosirService::assertAksesCabang($pesanan_grosir->cabang_id);

        if (!in_array($pesanan_grosir->status, ['Menunggu', 'Diproses'])) {
            return back()->with('error', 'Pesanan ini tidak bisa di-checkout.');
        }

        return redirect()->route('grosir.penjualan.create', ['pesanan' => $pesanan_grosir->id]);
    }
}
