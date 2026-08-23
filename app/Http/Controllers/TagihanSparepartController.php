<?php

namespace App\Http\Controllers;

use App\Models\TagihanSparepart;
use App\Models\TagihanSparepartItem;
use App\Models\Stok;
use App\Services\AuditLogService;
use App\Services\SparepartMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagihanSparepartController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();

        // Super Admin mode "Semua Cabang": stok tidak boleh campur antar toko,
        // wajib pilih toko dulu
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => $request->fullUrl()]);
        }

        $query = TagihanSparepart::with(['user', 'items'])
            ->where('cabang_id', $cabangId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode', 'like', "%$s%")
                    ->orWhere('nama_toko', 'like', "%$s%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('tanggal', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal', '<=', $request->date_to);
        }

        $tagihans = $query->orderBy('created_at', 'desc')->paginate(20);

        // Dropdown sparepart HANYA milik cabang sendiri (tidak campur toko lain)
        $stoks = Stok::where('cabang_id', $cabangId)->where('stok', '>', 0)->orderBy('nama')->get();

        // Summary stats (akurat: totalLunas mencakup nilai yg sudah dibayar, termasuk sebagian)
        $totalBelumBayar = TagihanSparepart::where('cabang_id', $cabangId)->where('status', '!=', 'Dibatalkan')->sum('sisa');
        $totalTagihan = TagihanSparepart::where('cabang_id', $cabangId)->where('status', '!=', 'Dibatalkan')->sum('total');
        $totalDibayar = TagihanSparepart::where('cabang_id', $cabangId)->where('status', '!=', 'Dibatalkan')->sum('dibayar');
        $totalLunas = TagihanSparepart::where('cabang_id', $cabangId)->where('status', 'Lunas')->sum('total');

        return view('penjualan-sparepart.tagihan', compact('tagihans', 'stoks', 'totalBelumBayar', 'totalTagihan', 'totalLunas', 'totalDibayar'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_toko' => 'required|string|max:255',
            'kontak_toko' => 'nullable|string|max:50',
            'alamat_toko' => 'nullable|string|max:500',
            'tanggal' => 'required|date',
            'tanggal_jatuh_tempo' => 'nullable|date|after_or_equal:tanggal',
            'diskon_persen' => 'nullable|numeric|min:0|max:100',
            'diskon_nominal' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.stok_id' => 'required|exists:stoks,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0',
        ]);

        $cabangId = auth()->user()->getActiveCabangId();

        // Validate stock availability + kepemilikan cabang
        foreach ($validated['items'] as $item) {
            $stok = Stok::find($item['stok_id']);
            // Stok harus milik cabang sendiri — tidak boleh mengurangi stok toko lain
            // (kompatibilitas data lama: cabang_id NULL dianggap milik cabang default 1)
            $stokMilikSendiri = $stok && (
                (int) ($stok->cabang_id ?? 0) === (int) $cabangId
                || ($stok->cabang_id === null && (int) $cabangId === 1)
            );
            if (!$stok || !$stokMilikSendiri) {
                return response()->json([
                    'success' => false,
                    'message' => $stok
                        ? "Barang {$stok->nama} bukan milik cabang Anda."
                        : "Barang tidak ditemukan."
                ], 403);
            }
            if ($stok->stok < $item['qty']) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok tidak cukup untuk {$stok->nama}. Tersedia: {$stok->stok}"
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['qty'] * $item['harga_satuan'];
            }

            $diskonPersen = $validated['diskon_persen'] ?? 0;
            $diskonNominal = $validated['diskon_nominal'] ?? 0;
            $diskonHitung = ($subtotal * $diskonPersen / 100) + $diskonNominal;
            $total = $subtotal - $diskonHitung;

            $kode = TagihanSparepart::generateKode();

            $tagihan = TagihanSparepart::create([
                'kode' => $kode,
                'cabang_id' => $cabangId,
                'user_id' => auth()->id(),
                'nama_toko' => $validated['nama_toko'],
                'kontak_toko' => $validated['kontak_toko'],
                'alamat_toko' => $validated['alamat_toko'],
                'tanggal' => $validated['tanggal'],
                'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
                'subtotal' => $subtotal,
                'diskon_persen' => $diskonPersen,
                'diskon_nominal' => $diskonNominal,
                'total' => $total,
                'dibayar' => 0,
                'sisa' => $total,
                'catatan' => $validated['catatan'],
            ]);

            foreach ($validated['items'] as $item) {
                $stok = Stok::find($item['stok_id']);
                TagihanSparepartItem::create([
                    'tagihan_id' => $tagihan->id,
                    'stok_id' => $stok->id,
                    'nama_barang' => $stok->nama,
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['qty'] * $item['harga_satuan'],
                ]);

                // Decrease stock
                $stok->decrement('stok', $item['qty']);

                // Catat pergerakan stok (Kartu Stok): penjualan antar toko
                SparepartMovementService::record($stok, 'keluar', 'penjualan', (int) $item['qty'], [
                    'referensi'      => $kode,
                    'referensi_id'   => $tagihan->id,
                    'referensi_model'=> $tagihan,
                    'harga_satuan'   => $item['harga_satuan'],
                    'pelaku_nama'    => $validated['nama_toko'],
                    'cabang_id'      => $stok->cabang_id,
                    'catatan'        => 'Tagihan sparepart ke toko: ' . $validated['nama_toko'],
                ]);
            }

            DB::commit();

            AuditLogService::log('tagihan_sparepart', 'create', "Tagihan {$kode} ke {$validated['nama_toko']}, Total: Rp " . number_format($total));

            return response()->json([
                'success' => true,
                'message' => "Tagihan {$kode} berhasil dibuat!",
                'data' => ['kode' => $kode, 'id' => $tagihan->id]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat tagihan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(TagihanSparepart $tagihan)
    {
        $tagihan->load(['items.stok', 'user', 'cabang']);
        return response()->json([
            'success' => true,
            'data' => $tagihan
        ]);
    }

    public function bayar(Request $request, TagihanSparepart $tagihan)
    {
        $request->validate([
            'jumlah' => 'required|numeric|min:1|max:' . $tagihan->sisa,
            'metode' => 'required|in:Cash,Transfer,QRIS',
        ]);

        DB::beginTransaction();
        try {
            $tagihan->increment('dibayar', $request->jumlah);
            $tagihan->decrement('sisa', $request->jumlah);

            if ($tagihan->sisa <= 0) {
                $tagihan->update(['status' => 'Lunas', 'sisa' => 0]);
            } else {
                $tagihan->update(['status' => 'Sebagian']);
            }

            // Record to Kas
            $cabangId = $tagihan->cabang_id;
            $lastKas = \App\Models\Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
            $lastSaldo = $lastKas ? $lastKas->saldo : 0;
            \App\Models\Kas::create([
                'tipe' => 'masuk',
                'cabang_id' => $cabangId,
                'jml' => $request->jumlah,
                'kategori' => 'Pembayaran Tagihan',
                'ket' => "Pembayaran tagihan {$tagihan->kode} ke {$tagihan->nama_toko}",
                'metode' => $request->metode,
                'ref' => 'BAYAR-' . $tagihan->kode,
                'waktu' => now(),
                'saldo' => $lastSaldo + $request->jumlah,
            ]);

            DB::commit();

            AuditLogService::log('tagihan_sparepart', 'bayar', "Pembayaran tagihan {$tagihan->kode}: Rp " . number_format($request->jumlah));

            return response()->json([
                'success' => true,
                'message' => "Pembayaran Rp " . number_format($request->jumlah) . " berhasil dicatat.",
                'status' => $tagihan->fresh()->status,
                'sisa' => $tagihan->fresh()->sisa,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function batal(TagihanSparepart $tagihan)
    {
        if ($tagihan->status === 'Dibatalkan') {
            return response()->json(['success' => false, 'message' => 'Tagihan sudah dibatalkan.'], 400);
        }
        if ($tagihan->dibayar > 0) {
            return response()->json(['success' => false, 'message' => 'Tagihan yang sudah dibayar tidak bisa dibatalkan.'], 400);
        }

        DB::beginTransaction();
        try {
            // Return stock
            foreach ($tagihan->items as $item) {
                if ($item->stok) {
                    $item->stok->increment('stok', $item->qty);
                }
            }

            $tagihan->update(['status' => 'Dibatalkan']);

            DB::commit();

            AuditLogService::log('tagihan_sparepart', 'batal', "Membatalkan tagihan {$tagihan->kode}");

            return response()->json(['success' => true, 'message' => "Tagihan {$tagihan->kode} dibatalkan. Stok dikembalikan."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function print(TagihanSparepart $tagihan)
    {
        $tagihan->load(['items.stok', 'user', 'cabang']);
        return view('penjualan-sparepart.tagihan-print', compact('tagihan'));
    }
}
