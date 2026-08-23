<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Models\SparepartMovement;
use App\Models\Cabang;
use App\Services\AuditLogService;
use App\Services\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Fitur Aktivitas Sparepart (Kartu Stok).
 *
 * Memungkinkan admin melihat riwayat pembelian & penjualan setiap
 * sparepart dalam satu tempat — mirip fitur "Stock Movement / Kartu
 * Stok" pada aplikasi Erzap.
 *
 *  - index()   : daftar sparepart + ringkasan total masuk/keluar/stok
 *  - show()    : kartu stok detail per sparepart (timeline + saldo)
 *  - riwayat() : timeline global seluruh pergerakan (semua sparepart)
 *  - export()  : unduh kartu stok ke Excel
 */
class AktivitasSparepartController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();

        // Stok sparepart tidak boleh campur antar toko:
        // Super Admin mode "Semua Cabang" wajib pilih toko dulu
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => $request->fullUrl()]);
        }

        // Query sparepart milik cabang aktif SAJA (tidak campur toko lain)
        $query = Stok::query()->with('cabang')->where('cabang_id', $cabangId);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', "%$s%")
                  ->orWhere('kode', 'like', "%$s%")
                  ->orWhere('barcode', 'like', "%$s%")
                  ->orWhere('merk_hp', 'like', "%$s%");
            });
        }
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $stoks = $query->orderBy('nama')->paginate(25)->appends($request->query());

        // Ringkasan pergerakan per sparepart (total masuk & keluar)
        $ids = $stoks->getCollection()->pluck('id');
        $summary = $this->movementSummary($ids, $cabangId);

        $stoks->getCollection()->transform(function ($s) use ($summary) {
            $key = $s->id;
            $s->total_masuk  = $summary[$key]['masuk'] ?? 0;
            $s->total_keluar = $summary[$key]['keluar'] ?? 0;
            $s->terakhir     = $summary[$key]['terakhir'] ?? null;
            return $s;
        });

        // Statistik global
        $stats = $this->globalStats($cabangId);

        // Daftar kategori untuk filter
        $kategoriQuery = Stok::where('cabang_id', $cabangId);
        $kategoris = $kategoriQuery->distinct()->orderBy('kategori')->pluck('kategori');

        return view('aktivitas-sparepart.index', compact('stoks', 'stats', 'kategoris'));
    }

    /**
     * Kartu stok detail untuk satu sparepart.
     */
    public function show(Request $request, Stok $stok)
    {
        $this->checkCabangAccess($stok);

        $query = SparepartMovement::where('stok_id', $stok->id);

        // Filter cabang (super admin bisa pilih cabang)
        $cabangId = auth()->user()->getActiveCabangId();
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        } elseif ($request->filled('cabang')) {
            $query->where('cabang_id', $request->cabang);
        }

        // Filter rentang tanggal
        if ($request->filled('dari')) {
            $query->whereDate('waktu', '>=', $request->dari);
        }
        if ($request->filled('sampai')) {
            $query->whereDate('waktu', '<=', $request->sampai);
        }
        // Filter jenis
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $movements = (clone $query)->with(['cabang', 'user'])->orderBy('waktu', 'desc')->orderBy('id', 'desc')->paginate(50)->appends($request->query());

        // Ringkasan
        $baseQuery = SparepartMovement::where('stok_id', $stok->id);
        if ($cabangId !== null) {
            $baseQuery->where('cabang_id', $cabangId);
        }
        $totalMasuk  = (clone $baseQuery)->where('tipe', 'masuk')->sum('qty');
        $totalKeluar = (clone $baseQuery)->where('tipe', 'keluar')->sum('qty');
        $nilaiBeli   = (clone $baseQuery)->where('jenis', 'pembelian')->selectRaw('SUM(qty * harga_satuan) as v')->value('v') ?? 0;
        $nilaiJual   = (clone $baseQuery)->where('jenis', 'penjualan')->selectRaw('SUM(qty * harga_satuan) as v')->value('v') ?? 0;

        $cabangs = Cabang::orderBy('nama')->get();

        return view('aktivitas-sparepart.show', compact(
            'stok', 'movements', 'totalMasuk', 'totalKeluar', 'nilaiBeli', 'nilaiJual', 'cabangs'
        ));
    }

    /**
     * Timeline global: semua pergerakan sparepart.
     */
    public function riwayat(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();

        // Pergerakan stok tidak boleh campur antar toko:
        // Super Admin mode "Semua Cabang" wajib pilih toko dulu
        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => $request->fullUrl()]);
        }

        $query = SparepartMovement::with(['stok', 'cabang', 'user']);

        $query->where('cabang_id', $cabangId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('referensi', 'like', "%$s%")
                  ->orWhere('pelaku_nama', 'like', "%$s%")
                  ->orWhereHas('stok', fn($sq) => $sq->where('nama', 'like', "%$s%")->orWhere('kode', 'like', "%$s%"));
            });
        }
        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('dari')) {
            $query->whereDate('waktu', '>=', $request->dari);
        }
        if ($request->filled('sampai')) {
            $query->whereDate('waktu', '<=', $request->sampai);
        }

        $movements = $query->orderBy('waktu', 'desc')->orderBy('id', 'desc')->paginate(40)->appends($request->query());

        $stats = $this->globalStats($cabangId);

        return view('aktivitas-sparepart.riwayat', compact('movements', 'stats'));
    }

    /**
     * Export kartu stok satu sparepart ke Excel.
     */
    public function export(Request $request, Stok $stok)
    {
        $this->checkCabangAccess($stok);

        $query = SparepartMovement::where('stok_id', $stok->id);
        $cabangId = auth()->user()->getActiveCabangId();
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }
        $movements = (clone $query)->orderBy('waktu', 'desc')->orderBy('id', 'desc')->get();

        $w = new XlsxWriter();
        $s = $w->sheet('Kartu Stok');
        $s->widths([140, 110, 70, 70, 70, 90, 130, 120]);
        $s->headerRow(['Tanggal', 'Jenis', 'Masuk', 'Keluar', 'Saldo', 'Harga Satuan', 'Referensi', 'Pelaku']);

        foreach ($movements as $m) {
            $s->row([
                optional($m->waktu)->format('Y-m-d H:i'),
                $m->labelJenis(),
                $m->tipe === 'masuk' ? $m->qty : '',
                $m->tipe === 'keluar' ? $m->qty : '',
                $m->saldo,
                (float) $m->harga_satuan,
                $m->referensi ?? '',
                $m->pelaku_nama ?? '',
            ]);
        }

        $nama = 'Kartu_Stok_' . str_replace([' ', '/', '\\'], '_', $stok->nama) . '_' . date('Y-m-d') . '.xlsx';
        AuditLogService::log('aktivitas_sparepart', 'export', "Export kartu stok {$stok->nama}");

        return $w->download($nama);
    }

    // ==================== HELPERS ====================

    private function checkCabangAccess(Stok $stok): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return;
        }
        $cabangId = $user->getActiveCabangId();
        if ($stok->cabang_id != $cabangId) {
            abort(403, 'Anda hanya bisa mengakses data cabang Anda sendiri.');
        }
    }

    /**
     * Ringkasan total masuk/keluar + pergerakan terakhir per sparepart.
     */
    private function movementSummary($stokIds, ?int $cabangId): array
    {
        if ($stokIds->isEmpty()) {
            return [];
        }

        $base = SparepartMovement::whereIn('stok_id', $stokIds);
        if ($cabangId !== null) {
            $base->where('cabang_id', $cabangId);
        }

        $masuk = (clone $base)->selectRaw('stok_id, SUM(qty) as total')
            ->where('tipe', 'masuk')->groupBy('stok_id')->pluck('total', 'stok_id');
        $keluar = (clone $base)->selectRaw('stok_id, SUM(qty) as total')
            ->where('tipe', 'keluar')->groupBy('stok_id')->pluck('total', 'stok_id');
        $terakhir = (clone $base)->selectRaw('stok_id, MAX(waktu) as waktu')
            ->groupBy('stok_id')->pluck('waktu', 'stok_id');

        $result = [];
        foreach ($stokIds as $id) {
            $result[$id] = [
                'masuk'    => $masuk[$id] ?? 0,
                'keluar'   => $keluar[$id] ?? 0,
                'terakhir' => isset($terakhir[$id]) ? \Carbon\Carbon::parse($terakhir[$id]) : null,
            ];
        }
        return $result;
    }

    /**
     * Statistik global pergerakan untuk cabang aktif.
     */
    private function globalStats(?int $cabangId): array
    {
        $base = SparepartMovement::query();
        if ($cabangId !== null) {
            $base->where('cabang_id', $cabangId);
        }

        $bulanIni = now()->startOfMonth();
        $today = now()->format('Y-m-d');

        return [
            'masuk_bulan_ini'  => (clone $base)->where('tipe', 'masuk')->where('waktu', '>=', $bulanIni)->sum('qty'),
            'keluar_bulan_ini' => (clone $base)->where('tipe', 'keluar')->where('waktu', '>=', $bulanIni)->sum('qty'),
            'jual_hari_ini'    => (clone $base)->where('jenis', 'penjualan')->whereDate('waktu', $today)->sum('qty'),
            'beli_hari_ini'    => (clone $base)->where('jenis', 'pembelian')->whereDate('waktu', $today)->sum('qty'),
            'total_pembelian'  => (clone $base)->where('jenis', 'pembelian')->sum('qty'),
            'total_penjualan'  => (clone $base)->where('jenis', 'penjualan')->sum('qty'),
        ];
    }
}
