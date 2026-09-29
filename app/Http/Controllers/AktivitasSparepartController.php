<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Models\SparepartMovement;
use App\Models\Cabang;
use App\Services\AuditLogService;
use App\Services\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AktivitasSparepartController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();

        if ($cabangId === null) {
            return view('stok.pilih-cabang', ['redirectTo' => $request->fullUrl()]);
        }

        // 1. Query dasar sparepart milik cabang aktif
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

        // 2. INI KUNCINYA: Hitung ringkasan pergerakan per sparepart (total masuk & keluar)
        $ids = $stoks->getCollection()->pluck('id');
        $summary = $this->movementSummary($ids, $cabangId);

        // 3. Suntikkan hasil akumulasi ke dalam setiap item $stoks
        $stoks->getCollection()->transform(function ($s) use ($summary) {
            $key = $s->id;
            $s->total_masuk  = $summary[$key]['masuk'] ?? 0;
            $s->total_keluar = $summary[$key]['keluar'] ?? 0;
            $s->terakhir     = $summary[$key]['terakhir'] ?? null;
            return $s;
        });

        $stats = $this->globalStats($cabangId);

        $kategoriQuery = Stok::where('cabang_id', $cabangId);
        $kategoris = $kategoriQuery->distinct()->orderBy('kategori')->pluck('kategori');

        return view('aktivitas-sparepart.index', compact('stoks', 'stats', 'kategoris'));
    }

    // ... (Metode show(), riwayat(), export() tetap sama seperti kode Anda) ...

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
     * Metode ini sangat efisien karena hanya melakukan 3 query agregat sederhana.
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