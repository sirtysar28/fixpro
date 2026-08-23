<?php

namespace App\Http\Controllers;

use App\Models\Servis;
use App\Models\Teknisi;
use App\Models\Kas;
use App\Models\PenjualanSparepart;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        // ====== LOGIKA TAHUN AKTIF (Feature #4) ======
        $tahunParam = $request->input('tahun');
        $tahunAktif = ($tahunParam === 'all') ? null : (is_numeric($tahunParam) ? (int) $tahunParam : (int) now()->format('Y'));

        $query = Servis::with(['pelanggan', 'teknisi']);
        if ($cabangId !== null) {
            $query->where('cabang_id', $cabangId);
        }

        // Default filter mengikuti tahun aktif kecuali user isi dari/sampai sendiri
        if ($request->filled('dari')) {
            $query->whereDate('tanggal', '>=', $request->dari);
        } elseif ($tahunAktif !== null) {
            $query->whereYear('tanggal', '>=', $tahunAktif);
        }
        if ($request->filled('sampai')) {
            $query->whereDate('tanggal', '<=', $request->sampai);
        } elseif ($tahunAktif !== null) {
            $query->whereYear('tanggal', '<=', $tahunAktif);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $servis = $query->orderBy('tanggal', 'desc')->get();

        // Daftar tahun tersedia untuk dropdown
        $tahunTersedia = $this->getAvailableYears($cabangId);
        $tahunSekarang = (int) now()->format('Y');

        // Hitung harga jual & laba sparepart per servis (dari field JSON spareparts)
        $servis->each(function ($s) {
            $hargaJualSp = 0;
            if (is_array($s->spareparts)) {
                foreach ($s->spareparts as $sp) {
                    $hargaJualSp += (float) ($sp['harga'] ?? 0) * (int) ($sp['qty'] ?? 1);
                }
            }
            $s->harga_jual_sp  = $hargaJualSp;
            $s->modal_sp       = (float) ($s->modal_sparepart ?? 0);
            $s->laba_sp_servis = $hargaJualSp - $s->modal_sp;             // Laba Sparepart = Harga Jual - Modal
            $s->laba_servis    = (float) $s->biaya - $hargaJualSp;        // Laba Servis = Biaya Servis - Harga Jual Sparepart
            $s->laba_total     = $s->laba_servis + $s->laba_sp_servis;    // Total Laba = biaya - modal
        });

        // Total (mengikuti data yg ditampilkan di tabel)
        $totalBiayaJasa   = $servis->sum('biaya');
        $totalHargaJualSp = $servis->sum('harga_jual_sp');
        $totalModalSp     = $servis->sum('modal_sp');
        $totalLabaSp      = $servis->sum('laba_sp_servis');
        $totalLabaServis  = $servis->sum('laba_servis');    // Laba Servis (biaya - harga jual sparepart)
        $totalLabaBersih  = $servis->sum('laba_total');     // Total laba (laba servis + laba sparepart)

        $totalOmset = $servis->where('status', 'Selesai')->sum('biaya');
        $totalServis = $servis->count();
        $totalSelesai = $servis->where('status', 'Selesai')->count();

        // Popular device
        $popular = $servis->groupBy('perangkat')->sortDesc()->keys()->first() ?? '-';

        // Teknisi performance filtered by cabang
        $teknisiPerf = Teknisi::with(['servis' => function ($q) use ($request, $cabangId) {
            if ($cabangId !== null) {
                $q->where('cabang_id', $cabangId);
            }
            $q->when($request->filled('dari'), fn ($q) => $q->whereDate('tanggal', '>=', $request->dari))
              ->when($request->filled('sampai'), fn ($q) => $q->whereDate('tanggal', '<=', $request->sampai));
        }]);
        if ($cabangId !== null) {
            $teknisiPerf = $teknisiPerf->where('cabang_id', $cabangId);
        }
        $teknisiPerf = $teknisiPerf->get()->map(function ($t) {
            $selesai = $t->servis->where('status', 'Selesai');
            $t->total = $t->servis->count();
            $t->selesai = $selesai->count();
            $t->omset = $selesai->sum('biaya');
            $t->proses = $t->servis->where('status', 'Proses')->count();
            $t->pending = $t->servis->where('status', 'Pending')->count();
            return $t;
        });

        // Laba chart 14 days filtered by cabang
        $labaChart = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayServisQuery = Servis::whereDate('tanggal', $date)->where('status', 'Selesai');
            if ($cabangId !== null) $dayServisQuery->where('cabang_id', $cabangId);
            $dayServis = $dayServisQuery->get();
            $omset = $dayServis->sum('biaya');
            $modal = $dayServis->sum('modal_sparepart');
            // Sparepart daily
            $spQuery = PenjualanSparepart::whereDate('tanggal', $date);
            if ($cabangId !== null) $spQuery->where('cabang_id', $cabangId);
            $sparepartOmset = $spQuery->sum('total');
            $sparepartModal = (clone $spQuery)->sum('modal_total');
            $omset += $sparepartOmset;
            $modal += $sparepartModal;
            $labaChart[] = [
                'date' => now()->subDays($i)->format('d/m'),
                'omset' => (float) $omset,
                'modal' => (float) $modal,
                'laba' => (float) ($omset - $modal),
            ];
        }

        // Sparepart stats for report period
        $sparepartQuery = PenjualanSparepart::query();
        if ($cabangId !== null) $sparepartQuery->where('cabang_id', $cabangId);
        if ($request->filled('dari')) $sparepartQuery->whereDate('tanggal', '>=', $request->dari);
        if ($request->filled('sampai')) $sparepartQuery->whereDate('tanggal', '<=', $request->sampai);
        $sparepartAll = (clone $sparepartQuery)->get();
        $omsetSparepart = $sparepartAll->sum('total');
        $diskonSparepart = $sparepartAll->unique('no_transaksi')->sum('diskon');
        $omsetBersihSparepart = $omsetSparepart - $diskonSparepart;
        $modalSparepart = $sparepartAll->sum('modal_total');
        $labaSparepart = $omsetBersihSparepart - $modalSparepart;

        return view('laporan.index', compact('servis', 'totalOmset', 'totalServis', 'totalSelesai', 'popular', 'teknisiPerf', 'labaChart', 'omsetSparepart', 'omsetBersihSparepart', 'diskonSparepart', 'labaSparepart', 'totalBiayaJasa', 'totalHargaJualSp', 'totalModalSp', 'totalLabaSp', 'totalLabaServis', 'totalLabaBersih', 'tahunAktif', 'tahunTersedia', 'tahunSekarang'));

    }

    /**
     * Daftar tahun unik dari transaksi servis & penjualan (dropdown tahun aktif).
     * Feature #4: Logika Tahunan.
     */
    private function getAvailableYears(?int $cabangId): array
    {
        try {
            $servisQuery = Servis::query();
            if ($cabangId !== null) $servisQuery->where('cabang_id', $cabangId);
            $yearsServis = $servisQuery->selectRaw('DISTINCT YEAR(tanggal) as y')
                ->whereNotNull('tanggal')->pluck('y')->toArray();

            $spQuery = PenjualanSparepart::query();
            if ($cabangId !== null) $spQuery->where('cabang_id', $cabangId);
            $yearsSp = $spQuery->selectRaw('DISTINCT YEAR(tanggal) as y')
                ->pluck('y')->toArray();

            $years = array_unique(array_filter(array_merge($yearsServis, $yearsSp), fn($y) => $y !== null));
            rsort($years);
            $current = (int) now()->format('Y');
            if (!in_array($current, $years, true)) $years[] = $current;
            rsort($years);
            return array_map('intval', $years);
        } catch (\Exception $e) {
            return [(int) now()->format('Y')];
        }
    }
}
