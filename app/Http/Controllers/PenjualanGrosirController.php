<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Setting;
use App\Models\PelangganGrosir;
use App\Models\PenjualanGrosir;
use App\Models\PenjualanGrosirItem;
use App\Models\Stok;
use App\Services\AuditLogService;
use App\Services\GrosirService;
use App\Services\SparepartMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanGrosirController extends Controller
{
    // ================= RIWAYAT =================

    public function index(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.penjualan.index')]);
        }
        $cabangId = $gate;

        // Riwayat penjualan HANYA cabang aktif
        $query = PenjualanGrosir::with(['pelanggan', 'user', 'sumberCabang'])
            ->where('cabang_id', $cabangId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('no_nota', 'like', "%$s%")
                    ->orWhere('nama_pelanggan', 'like', "%$s%")
                    ->orWhereHas('pelanggan', fn($pq) => $pq->where('no_hp', 'like', "%$s%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('dari')) {
            $query->whereDate('tanggal', '>=', $request->dari);
        }
        if ($request->filled('sampai')) {
            $query->whereDate('tanggal', '<=', $request->sampai);
        }

        $penjualans = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        // Statistik ringkas
        $stats = PenjualanGrosir::where('cabang_id', $cabangId)->where('status', '!=', 'Dibatalkan');
        $omsetBulan = (clone $stats)->where('tanggal', '>=', now()->startOfMonth())->sum('total');
        $piutangAktif = (clone $stats)->whereIn('status', ['Piutang', 'Sebagian'])->with('payments')->get()->sum(fn($p) => $p->sisaPiutang());

        return view('grosir.penjualan.index', compact('penjualans', 'omsetBulan', 'piutangAktif'));
    }

    // ================= POS KASIR GROSIR =================

    public function create(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.penjualan.create')]);
        }
        $cabangId = $gate;

        $pelanggans = PelangganGrosir::where('cabang_id', $cabangId)
            ->where('aktif', true)->orderBy('nama')->get();

        $gudangs = GrosirService::gudangOptions(auth()->user());
        $cabangAktif = Cabang::find($cabangId);

        // Prefill dari pesanan (konversi pesanan → penjualan)
        $pesanan = null;
        if ($request->filled('pesanan')) {
            $pesanan = \App\Models\PesananGrosir::with('items.stok')->find($request->pesanan);
            if ($pesanan) {
                GrosirService::assertAksesCabang($pesanan->cabang_id);
            }
        }

        return view('grosir.penjualan.create', compact('pelanggans', 'gudangs', 'cabangAktif', 'pesanan'));
    }

    /**
     * API: cari produk untuk POS grosir.
     * Stok difilter cabang sumber (toko aktif atau gudang yang dipilih) — TIDAK CAMPUR toko lain.
     * Harga otomatis mengikuti level harga + harga khusus pelanggan.
     */
    public function apiProduk(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return response()->json(['products' => [], 'message' => 'Pilih toko/cabang terlebih dahulu']);
        }
        $cabangId = $gate;

        // Sumber stok: toko aktif (default) atau gudang (jika dipilih & valid untuk grup user)
        $sumberCabangId = (int) $request->get('sumber', $cabangId);
        $allowed = array_merge([$cabangId], array_column(GrosirService::gudangOptions(auth()->user()), 'id'));
        if (!in_array($sumberCabangId, $allowed, true)) {
            $sumberCabangId = $cabangId;
        }

        $q = trim((string) $request->get('q', ''));
        $level = (string) $request->get('level', 'eceran');
        $pelangganId = $request->get('pelanggan_id');

        $pelanggan = $pelangganId ? PelangganGrosir::find($pelangganId) : null;
        if ($pelanggan && (int) $pelanggan->cabang_id !== (int) $cabangId) {
            $pelanggan = null; // pelanggan toko lain tidak boleh dipakai
        }

        $products = Stok::where('cabang_id', $sumberCabangId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('kode', 'like', "%{$q}%")
                        ->orWhere('barcode', 'like', "%{$q}%")
                        ->orWhere('merk_hp', 'like', "%{$q}%");
                });
            })
            ->orderByRaw("CASE WHEN stok > 0 THEN 0 ELSE 1 END")
            ->orderBy('nama')
            ->limit(20)
            ->get();

        return response()->json([
            'sumber' => $sumberCabangId,
            'products' => $products->map(function ($p) use ($pelanggan, $level) {
                $harga = GrosirService::resolveHarga($p, $pelanggan, $level);
                return [
                    'id' => $p->id,
                    'kode' => $p->kode,
                    'barcode' => $p->barcode,
                    'nama' => $p->nama,
                    'satuan' => $p->satuan ?? 'pcs',
                    'stok' => (int) $p->stok,
                    'reserved' => (int) ($p->reserved ?? 0),
                    'tersedia' => $p->stok_tersedia,
                    'harga' => $harga['harga'],
                    'sumber_harga' => $harga['sumber'],
                ];
            }),
        ]);
    }

    /** API: data pelanggan grosir (untuk dropdown POS) */
    public function apiPelanggan(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return response()->json(['pelanggans' => []]);
        }
        $q = trim((string) $request->get('q', ''));
        $pelanggans = PelangganGrosir::where('cabang_id', $gate)
            ->where('aktif', true)
            ->when($q !== '', fn($query) => $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%")
                    ->orWhere('no_hp', 'like', "%{$q}%");
            }))
            ->orderBy('nama')->limit(15)->get();

        return response()->json([
            'pelanggans' => $pelanggans->map(fn($p) => [
                'id' => $p->id,
                'kode' => $p->kode,
                'nama' => $p->nama,
                'no_hp' => $p->no_hp,
                'level_harga' => $p->level_harga,
                'level_label' => $p->labelLevelHarga(),
                'alamat' => $p->alamat,
                'alamat_kirim' => $p->alamat_kirim,
                'limit_piutang' => (float) $p->limit_piutang,
            ]),
        ]);
    }

    // ================= SIMPAN TRANSAKSI =================

    public function store(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return response()->json(['success' => false, 'message' => 'Pilih toko/cabang terlebih dahulu.'], 422);
        }
        $cabangId = $gate;

        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.stok_id' => 'required|exists:stoks,id',
                'items.*.qty' => 'required|integer|min:1',
                'items.*.harga_satuan' => 'required|numeric|min:0',
                'pelanggan_grosir_id' => 'nullable|exists:pelanggan_grosirs,id',
                'level_harga' => 'required|in:' . implode(',', array_keys(GrosirService::LEVELS)),
                'sumber' => 'nullable|integer',
                'diskon' => 'nullable|numeric|min:0',
                'bayar' => 'nullable|numeric|min:0',
                'metode_bayar' => 'required|in:Cash,Transfer,QRIS,Tempo',
                'jatuh_tempo' => 'nullable|date',
                'alamat_kirim' => 'nullable|string',
                'catatan' => 'nullable|string',
                'pesanan_grosir_id' => 'nullable|exists:pesanan_grosirs,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal: ' . collect($e->errors())->flatten()->join(', ')], 422);
        }

        $user = auth()->user();

        // ===== Sumber stok: toko aktif atau gudang grup sendiri =====
        $sumberCabangId = (int) ($validated['sumber'] ?? $cabangId);
        $allowedSumber = array_merge([$cabangId], array_column(GrosirService::gudangOptions($user), 'id'));
        if (!in_array($sumberCabangId, $allowedSumber, true)) {
            return response()->json(['success' => false, 'message' => 'Sumber stok tidak valid.'], 403);
        }

        // ===== Pelanggan =====
        $pelanggan = null;
        if (!empty($validated['pelanggan_grosir_id'])) {
            $pelanggan = PelangganGrosir::find($validated['pelanggan_grosir_id']);
            if (!$pelanggan || (int) $pelanggan->cabang_id !== (int) $cabangId) {
                return response()->json(['success' => false, 'message' => 'Pelanggan tidak ditemukan di cabang ini.'], 403);
            }
        }
        $level = $validated['level_harga'];

        // ===== Validasi stok milik cabang sumber + ketersediaan =====
        foreach ($validated['items'] as $item) {
            $stok = Stok::find($item['stok_id']);
            if (!$stok || (int) $stok->cabang_id !== $sumberCabangId) {
                return response()->json(['success' => false, 'message' => 'Barang tidak ditemukan di sumber stok yang dipilih.'], 403);
            }
            if ((int) $stok->stok < (int) $item['qty']) {
                return response()->json(['success' => false, 'message' => "Stok {$stok->nama} tidak cukup. Tersedia: {$stok->stok}"], 422);
            }
        }

        // ===== Hitung total =====
        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += $item['qty'] * $item['harga_satuan'];
        }
        $diskon = (float) ($validated['diskon'] ?? 0);
        $total = max(0, $subtotal - $diskon);
        $bayar = (float) ($validated['bayar'] ?? 0);
        $piutang = max(0, $total - $bayar);
        $status = $piutang <= 0 ? 'Lunas' : ($bayar > 0 ? 'Sebagian' : 'Piutang');

        // Metode Tempo wajib punya jatuh tempo kalau ada piutang
        $jatuhTempo = $validated['jatuh_tempo'] ?? null;
        if ($piutang > 0 && !$jatuhTempo) {
            return response()->json(['success' => false, 'message' => 'Piutang wajib punya tanggal jatuh tempo.'], 422);
        }

        // Cek limit piutang pelanggan
        if ($piutang > 0 && $pelanggan && !GrosirService::bolehPiutang($pelanggan, $piutang)) {
            return response()->json(['success' => false, 'message' => 'Melebihi limit piutang pelanggan (' . formatRp($pelanggan->limit_piutang) . ').'], 422);
        }

        DB::beginTransaction();
        try {
            $noNota = PenjualanGrosir::generateNoNota();

            $penjualan = PenjualanGrosir::create([
                'no_nota' => $noNota,
                'cabang_id' => $cabangId,
                'sumber_cabang_id' => $sumberCabangId,
                'user_id' => auth()->id(),
                'pelanggan_grosir_id' => $pelanggan?->id,
                'nama_pelanggan' => $pelanggan?->nama ?? 'Umum',
                'level_harga' => $level,
                'tanggal' => now(),
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'total' => $total,
                'bayar' => $bayar,
                'piutang' => $piutang,
                'jatuh_tempo' => $jatuhTempo,
                'metode_bayar' => $validated['metode_bayar'],
                'status' => $status,
                'alamat_kirim' => $validated['alamat_kirim'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
                'pesanan_grosir_id' => $validated['pesanan_grosir_id'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $stok = Stok::find($item['stok_id']);

                PenjualanGrosirItem::create([
                    'penjualan_grosir_id' => $penjualan->id,
                    'stok_id' => $stok->id,
                    'kode' => $stok->kode,
                    'nama' => $stok->nama,
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'modal_satuan' => $stok->modal,
                    'subtotal' => $item['qty'] * $item['harga_satuan'],
                ]);

                // Kurangi stok dari sumber (toko/gudang)
                $stok->decrement('stok', $item['qty']);

                // Kartu stok
                SparepartMovementService::record($stok, 'keluar', 'penjualan_grosir', (int) $item['qty'], [
                    'referensi' => $noNota,
                    'referensi_id' => $penjualan->id,
                    'referensi_model' => $penjualan,
                    'harga_satuan' => $item['harga_satuan'],
                    'cabang_id' => $sumberCabangId,
                    'catatan' => 'Penjualan Grosir' . ($pelanggan ? ' — ' . $pelanggan->nama : ''),
                ]);
            }

            // Kas masuk hanya untuk uang yang benar-benar dibayar
            if ($bayar > 0) {
                GrosirService::kasMasuk(
                    $cabangId,
                    $bayar,
                    "Grosir {$noNota}" . ($pelanggan ? " — {$pelanggan->nama}" : ''),
                    $validated['metode_bayar'] === 'Tempo' ? 'Cash' : $validated['metode_bayar'],
                    $noNota
                );
            }

            // Link balik dari pesanan (kalau transaksi dari pesanan)
            if (!empty($validated['pesanan_grosir_id'])) {
                $pesanan = \App\Models\PesananGrosir::find($validated['pesanan_grosir_id']);
                if ($pesanan && $pesanan->status !== 'Selesai') {
                    // Lepaskan reservasi stok pesanan
                    foreach ($pesanan->items as $pi) {
                        if ($pi->stok_id && $pesanan->status === 'Diproses') {
                            $stokPesanan = Stok::find($pi->stok_id);
                            $stokPesanan?->decrement('reserved', min((int) ($stokPesanan->reserved ?? 0), (int) $pi->qty));
                        }
                    }
                    $pesanan->update([
                        'status' => 'Selesai',
                        'penjualan_grosir_id' => $penjualan->id,
                        'tanggal_selesai' => now()->format('Y-m-d'),
                    ]);
                }
            }

            DB::commit();

            AuditLogService::log('penjualan_grosir', 'create', "Nota grosir {$noNota}: " . count($validated['items']) . " item, Total: Rp " . number_format($total) . ($piutang > 0 ? ", Piutang: Rp " . number_format($piutang) : ''));

            return response()->json([
                'success' => true,
                'message' => "Nota {$noNota} berhasil dibuat!",
                'data' => [
                    'id' => $penjualan->id,
                    'no_nota' => $noNota,
                    'total' => $total,
                    'piutang' => $piutang,
                    'status' => $status,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    // ================= DETAIL & CETAK =================

    public function show(PenjualanGrosir $penjualan_grosir)
    {
        GrosirService::assertAksesCabang($penjualan_grosir->cabang_id);
        $penjualan_grosir->load(['items.stok', 'pelanggan', 'user', 'cabang', 'sumberCabang', 'payments.user', 'returs.items']);

        $sisaPiutang = $penjualan_grosir->sisaPiutang();
        return view('grosir.penjualan.show', compact('penjualan_grosir', 'sisaPiutang'));
    }

    /** Nota grosir ukuran struk/thermal — desain sesuai permintaan */
    public function nota(PenjualanGrosir $penjualan_grosir)
    {
        GrosirService::assertAksesCabang($penjualan_grosir->cabang_id);
        $penjualan_grosir->load(['items', 'pelanggan', 'user', 'cabang', 'sumberCabang']);
        $settings = $this->tokoSettings($penjualan_grosir->cabang);
        $sisaPiutang = $penjualan_grosir->sisaPiutang();

        return view('grosir.penjualan.nota', compact('penjualan_grosir', 'settings', 'sisaPiutang'));
    }

    /** Invoice A4 */
    public function invoice(PenjualanGrosir $penjualan_grosir)
    {
        GrosirService::assertAksesCabang($penjualan_grosir->cabang_id);
        $penjualan_grosir->load(['items', 'pelanggan', 'user', 'cabang', 'sumberCabang', 'payments']);
        $settings = $this->tokoSettings($penjualan_grosir->cabang);
        $sisaPiutang = $penjualan_grosir->sisaPiutang();

        return view('grosir.penjualan.invoice', compact('penjualan_grosir', 'settings', 'sisaPiutang'));
    }

    /** Surat jalan (delivery order) */
    public function suratJalan(PenjualanGrosir $penjualan_grosir)
    {
        GrosirService::assertAksesCabang($penjualan_grosir->cabang_id);
        $penjualan_grosir->load(['items', 'pelanggan', 'user', 'cabang', 'sumberCabang']);
        $settings = $this->tokoSettings($penjualan_grosir->cabang);

        return view('grosir.penjualan.surat-jalan', compact('penjualan_grosir', 'settings'));
    }

    // ================= PEMBATALAN =================

    public function batal(Request $request, PenjualanGrosir $penjualan_grosir)
    {
        GrosirService::assertAksesCabang($penjualan_grosir->cabang_id);

        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Hanya admin yang boleh membatalkan nota grosir.'], 403);
        }
        if ($penjualan_grosir->status === 'Dibatalkan') {
            return response()->json(['success' => false, 'message' => 'Nota sudah dibatalkan.'], 400);
        }

        $request->validate(['alasan' => 'required|string|min:3|max:500']);

        DB::beginTransaction();
        try {
            // Kembalikan stok ke sumber semula
            foreach ($penjualan_grosir->items as $item) {
                if (!$item->stok) continue;
                $item->stok->increment('stok', $item->qty);
                SparepartMovementService::record($item->stok, 'masuk', 'batal_penjualan_grosir', (int) $item->qty, [
                    'referensi' => 'BATAL-' . $penjualan_grosir->no_nota,
                    'referensi_id' => $penjualan_grosir->id,
                    'cabang_id' => $penjualan_grosir->sumber_cabang_id,
                    'catatan' => 'Pembatalan: ' . $request->alasan,
                ]);
            }

            // Kas keluar untuk uang yang sudah dibayar
            $sudahBayar = (float) $penjualan_grosir->bayar + (float) $penjualan_grosir->payments()->sum('jml')
                - (float) $penjualan_grosir->returs()->where('metode', 'Potong Piutang')->sum('total');
            if ($sudahBayar > 0) {
                GrosirService::kasKeluar(
                    $penjualan_grosir->cabang_id,
                    $sudahBayar,
                    "Pembatalan nota grosir {$penjualan_grosir->no_nota}",
                    $penjualan_grosir->metode_bayar === 'Tempo' ? 'Cash' : $penjualan_grosir->metode_bayar,
                    'BATAL-' . $penjualan_grosir->no_nota
                );
            }

            $penjualan_grosir->update([
                'status' => 'Dibatalkan',
                'alasan_pembatalan' => $request->alasan,
                'dibatalkan_oleh' => $user->id,
                'dibatalkan_pada' => now(),
            ]);

            DB::commit();
            AuditLogService::log('penjualan_grosir', 'batal', "Membatalkan nota grosir {$penjualan_grosir->no_nota}. Alasan: {$request->alasan}");

            return response()->json(['success' => true, 'message' => "Nota {$penjualan_grosir->no_nota} dibatalkan. Stok dikembalikan."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    // ================= HELPER =================

    private function tokoSettings(?Cabang $cabang): array
    {
        $cabangId = $cabang?->id;
        return [
            'nama_toko' => Setting::get("nama_toko_{$cabangId}") ?? Setting::get('nama_toko') ?? ($cabang?->nama ?? 'FIXPRO'),
            'alamat' => Setting::get("alamat_{$cabangId}") ?? Setting::get('alamat') ?? ($cabang?->alamat ?? ''),
            'telp' => Setting::get("telp_{$cabangId}") ?? Setting::get('telp') ?? ($cabang?->telp ?? ''),
            'wa' => Setting::get("wa_{$cabangId}") ?? Setting::get('wa') ?? ($cabang?->telp ?? ''),
            'logo' => Setting::get("logo_{$cabangId}") ?? Setting::get('logo') ?? '',
            'tagline' => Setting::get("tagline_{$cabangId}") ?? Setting::get('tagline') ?? '',
        ];
    }
}
