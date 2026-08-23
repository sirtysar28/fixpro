<?php

namespace App\Http\Controllers;

use App\Models\Servis;
use App\Models\PenjualanSparepart;
use App\Models\Teknisi;
use App\Models\Kas;
use App\Services\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LaporanKeuanganController extends Controller
{
    public function index(Request $request)
    {
        try {
            $data = $this->getReportData($request);
            return view('laporan-keuangan.index', $data);
        } catch (\Exception $e) {
            Log::error('LaporanKeuangan error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Gagal memuat Laporan Keuangan. Silakan coba lagi atau hubungi admin.');
        }
    }

    /**
     * Export Laporan Keuangan ke Excel .xlsx (Office Open XML — kompatibel semua office app).
     */
    public function export(Request $request)
    {
        try {
            $data = $this->getReportData($request);

            $periode = 'Semua Tahun';
            if (!empty($data['tahunAktif'])) {
                $periode = 'Tahun_' . $data['tahunAktif'];
            } elseif (!empty($data['dari']) && !empty($data['sampai'])) {
                $periode = $data['dari'] . '_sd_' . $data['sampai'];
            } elseif (!empty($data['dari'])) {
                $periode = 'dri_' . $data['dari'];
            } elseif (!empty($data['sampai'])) {
                $periode = 'smp_' . $data['sampai'];
            }

            $cabang = auth()->user()->getActiveCabangId();
            $namaCabang = 'Semua_Cabang';
            if ($cabang) {
                $c = \App\Models\Cabang::find($cabang);
                if ($c) $namaCabang = str_replace([' ', '/', '\\'], '_', $c->nama);
            }

            $filename = 'Laporan_Keuangan_FixPro_' . $namaCabang . '_' . $periode . '.xlsx';

            $w = new XlsxWriter();
            $this->buildExcelSheets($w, $data);

            return $w->download($filename);
        } catch (\Exception $e) {
            Log::error('LaporanKeuangan export error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Gagal export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Kumpulkan seluruh data laporan berdasarkan filter (dipakai index() & export()).
     */
    private function getReportData(Request $request): array
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();
        $isSuperAdmin = $user->isSuperAdmin();
        $showAll = $isSuperAdmin && session('cabang_id') === 'all';

        // ====== LOGIKA TAHUN AKTIF (Feature #4) ======
        // Seluruh data transaksi, laporan & statistik mengikuti periode tahun aktif secara konsisten.
        // Default: tahun berjalan. Bisa diganti via ?tahun=YYYY atau ?tahun=all untuk semua tahun.
        $tahunParam = $request->input('tahun');
        $tahunAktif = ($tahunParam === 'all') ? null : (is_numeric($tahunParam) ? (int) $tahunParam : (int) now()->format('Y'));

        // Daftar tahun yang tersedia (dari transaksi) untuk dropdown
        $tahunTersedia = $this->getAvailableYears($cabangId, $showAll);

        // ====== FILTER DATE ======
        // Jika user isi dari/sampai eksplisit, gunakan itu. Jika tidak, ikut tahun aktif.
        if ($request->filled('dari') || $request->filled('sampai')) {
            $dari   = $request->filled('dari') ? $request->dari : null;
            $sampai = $request->filled('sampai') ? $request->sampai : now()->format('Y-m-d');
        } elseif ($tahunAktif !== null) {
            // Scope ke tahun aktif: 1 Januari s/d 31 Desember
            $dari   = sprintf('%04d-01-01', $tahunAktif);
            $sampai = sprintf('%04d-12-31', $tahunAktif);
        } else {
            // tahun = all → semua data
            $dari   = null;
            $sampai = now()->format('Y-m-d');
        }

        // ====== SERVIS QUERY ======
        $servisQuery = Servis::with(['pelanggan', 'teknisi']);
        if (!$showAll && $cabangId !== null) {
            $servisQuery->where('cabang_id', $cabangId);
        }
        if ($dari) {
            $servisQuery->whereDate('tgl_diambil', '>=', $dari);
        }
        if ($sampai) {
            $servisQuery->whereDate('tgl_diambil', '<=', $sampai);
        }
        $servisQuery->where('status', 'Selesai');
        $servisSelesai = $servisQuery->orderBy('tgl_diambil', 'desc')->get();

        // ====== SPAREPART QUERY ======
        $spQuery = PenjualanSparepart::query();
        if (!$showAll && $cabangId !== null) {
            $spQuery->where('cabang_id', $cabangId);
        }
        if ($dari) {
            $spQuery->whereDate('tanggal', '>=', $dari);
        }
        if ($sampai) {
            $spQuery->whereDate('tanggal', '<=', $sampai);
        }
        $spQuery->where('status', '!=', 'Dibatalkan');
        $penjualanSP = (clone $spQuery)->orderBy('tanggal', 'desc')->get();

        // ====== HITUNG TOTAL DISKON SPAREPART (per no_transaksi, ambil diskon dari item pertama) ======
        $totalDiskonSP = $penjualanSP->unique('no_transaksi')->sum('diskon');

        // ====== HITUNG HARGA JUAL & LABA SPAREPART PER SERVIS (dari field JSON spareparts) ======
        $servisSelesai->each(function ($s) {
            $hargaJualSp = 0;
            if (is_array($s->spareparts)) {
                foreach ($s->spareparts as $sp) {
                    $hargaJualSp += (float) ($sp['harga'] ?? 0) * (int) ($sp['qty'] ?? 1);
                }
            }
            $s->harga_jual_sp   = $hargaJualSp;                            // omset sparepart (harga jual)
            $s->modal_sp        = (float) ($s->modal_sparepart ?? 0);      // modal sparepart (HPP)
            $s->laba_sp_servis  = $hargaJualSp - $s->modal_sp;             // Laba Sparepart = harga jual - modal
            $s->laba_servis     = (float) $s->biaya - $hargaJualSp;        // Laba Servis = biaya - harga jual sparepart
            $s->laba_total      = $s->laba_servis + $s->laba_sp_servis;    // Total laba servis = biaya - modal
        });

        // ====== STATS SERVIS ======
        $totalOmsetServis       = $servisSelesai->sum('biaya');             // omset jasa servis
        $totalHargaJualSpServis = $servisSelesai->sum('harga_jual_sp');    // omset sparepart dari servis
        $totalModalServisSP     = $servisSelesai->sum('modal_sp');         // modal sparepart dari servis
        $labaSpServis           = $servisSelesai->sum('laba_sp_servis');   // laba sparepart dari servis
        $labaServis             = $servisSelesai->sum('laba_servis');      // laba servis = biaya - harga jual sparepart

        // ====== STATS SPAREPART (POS / Kasir) ======
        $totalOmsetSP = $penjualanSP->sum('total');         // omset KOTOR (belum dikurangi diskon)
        $totalModalSP = $penjualanSP->sum('modal_total');
        $labaSP = ($totalOmsetSP - $totalDiskonSP) - $totalModalSP; // laba BERSIH setelah diskon (omset bersih - modal)

        // ====== RINGKASAN ======
        $totalOmsetBersihSP = $totalOmsetSP - $totalDiskonSP;
        // Omset total = biaya servis (sudah termasuk harga jual sparepart) + omset bersih SP POS
        $totalOmset = $totalOmsetServis + $totalOmsetBersihSP;
        $totalModal = $totalModalServisSP + $totalModalSP;
        // Laba bersih = laba jasa + laba SP servis + laba SP POS (setelah diskon)
        $labaBersih = $labaServis + $labaSpServis + $labaSP;
        $margin = $totalOmset > 0 ? round(($labaBersih / $totalOmset) * 100) : 0;

        $totalTransaksiServis = $servisSelesai->count();
        $totalTransaksiSP = $penjualanSP->count();
        $totalTransaksi = $totalTransaksiServis + $totalTransaksiSP;

        // ====== KAS ======
        try {
            $kasQuery = $showAll ? Kas::query() : (($cabangId !== null) ? Kas::where('cabang_id', $cabangId) : Kas::query());
            if ($dari) $kasQuery->whereDate('waktu', '>=', $dari);
            if ($sampai) $kasQuery->whereDate('waktu', '<=', $sampai);
            $kasMasuk = (clone $kasQuery)->where('tipe', 'masuk')->sum('jml');
            $kasKeluar = (clone $kasQuery)->where('tipe', 'keluar')->sum('jml');
        } catch (\Exception $e) {
            Log::warning('Kas query failed in LaporanKeuangan: ' . $e->getMessage());
            $kasMasuk = 0;
            $kasKeluar = 0;
        }

        // ====== PER BULAN BREAKDOWN (12 bulan terakhir) ======
        $monthlyBreakdown = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();
            $monthName = now()->subMonths($i)->format('M Y');

            $mServisQuery = Servis::where('status', 'Selesai')->whereBetween('tgl_diambil', [$monthStart, $monthEnd]);
            if (!$showAll && $cabangId !== null) $mServisQuery->where('cabang_id', $cabangId);
            $mServisAll = $mServisQuery->get();
            $mOmsetServis = $mServisAll->sum('biaya');
            $mHargaJualSpServis = 0;
            $mModalSpServis = 0;
            foreach ($mServisAll as $s) {
                if (is_array($s->spareparts)) {
                    foreach ($s->spareparts as $sp) {
                        $mHargaJualSpServis += (float) ($sp['harga'] ?? 0) * (int) ($sp['qty'] ?? 1);
                    }
                }
                $mModalSpServis += (float) ($s->modal_sparepart ?? 0);
            }
            $mLabaServis = $mOmsetServis - $mHargaJualSpServis; // laba servis = biaya - harga jual sparepart
            $mLabaSpServis = $mHargaJualSpServis - $mModalSpServis;

            $mSpQuery = PenjualanSparepart::where('status', '!=', 'Dibatalkan')->whereBetween('tanggal', [$monthStart, $monthEnd]);
            if (!$showAll && $cabangId !== null) $mSpQuery->where('cabang_id', $cabangId);
            $mSpAll = $mSpQuery->get();
            $mOmsetSP = $mSpAll->sum('total');
            $mDiskonSP = $mSpAll->unique('no_transaksi')->sum('diskon');
            $mModalSP = $mSpAll->sum('modal_total');
            $mOmsetBersihSP = $mOmsetSP - $mDiskonSP;

            $monthlyBreakdown[] = [
                'month' => $monthName,
                'omset_servis' => (float) $mOmsetServis,
                'omset_sp' => (float) $mOmsetSP,
                'diskon_sp' => (float) $mDiskonSP,
                'omset_bersih_sp' => (float) $mOmsetBersihSP,
                'modal_sp' => (float) $mModalSP,
                'laba_sp' => (float) ($mOmsetBersihSP - $mModalSP),
                'laba_servis' => (float) $mLabaServis,
                'laba_total' => (float) ($mLabaServis + $mLabaSpServis + ($mOmsetBersihSP - $mModalSP)),
            ];
        }

        // ====== TEKNISI PERFORMANCE ======
        $teknisiPerf = Teknisi::with(['servis' => function ($q) use ($dari, $sampai, $cabangId, $showAll) {
            $q->where('status', 'Selesai');
            if (!$showAll && $cabangId !== null) $q->where('cabang_id', $cabangId);
            if ($dari) $q->whereDate('tgl_diambil', '>=', $dari);
            if ($sampai) $q->whereDate('tgl_diambil', '<=', $sampai);
        }]);
        if (!$showAll && $cabangId !== null) $teknisiPerf->where('cabang_id', $cabangId);
        $teknisiPerf = $teknisiPerf->get()->map(function ($t) {
            $selesai = $t->servis;
            $t->total = $selesai->count();
            $t->omset = $selesai->sum('biaya');
            return $t;
        });

        return array_merge(
            compact(
                'servisSelesai', 'penjualanSP',
                'totalOmsetServis', 'totalHargaJualSpServis', 'totalOmsetSP', 'totalOmsetBersihSP', 'totalOmset',
                'totalModalServisSP', 'totalModalSP', 'totalModal',
                'labaServis', 'labaSpServis', 'labaSP', 'labaBersih', 'margin',
                'totalDiskonSP',
                'totalTransaksiServis', 'totalTransaksiSP', 'totalTransaksi',
                'kasMasuk', 'kasKeluar',
                'monthlyBreakdown', 'teknisiPerf',
                'dari', 'sampai'
            ),
            [
                'tahunAktif'      => $tahunAktif,        // int tahun aktif atau null (semua)
                'tahunTersedia'   => $tahunTersedia,     // array tahun untuk dropdown
                'tahunSekarang'   => (int) now()->format('Y'),
            ]
        );
    }

    /**
     * Ambil daftar tahun unik dari transaksi servis & penjualan (untuk dropdown tahun).
     * Feature #4: Logika Tahunan.
     */
    private function getAvailableYears(?int $cabangId, bool $showAll): array
    {
        try {
            $servisQuery = Servis::query();
            if (!$showAll && $cabangId !== null) $servisQuery->where('cabang_id', $cabangId);
            $yearsServis = $servisQuery->selectRaw('DISTINCT YEAR(tgl_diambil) as y')
                ->whereNotNull('tgl_diambil')->pluck('y')->toArray();

            $spQuery = PenjualanSparepart::query();
            if (!$showAll && $cabangId !== null) $spQuery->where('cabang_id', $cabangId);
            $yearsSp = $spQuery->selectRaw('DISTINCT YEAR(tanggal) as y')
                ->pluck('y')->toArray();

            $years = array_unique(array_filter(array_merge($yearsServis, $yearsSp), fn($y) => $y !== null));
            rsort($years);

            // Pastikan tahun berjalan selalu ada di daftar
            $current = (int) now()->format('Y');
            if (!in_array($current, $years, true)) $years[] = $current;
            rsort($years);
            return array_map('intval', $years);
        } catch (\Exception $e) {
            return [(int) now()->format('Y')];
        }
    }

    /* ============================================================
       BUILDER EXCEL (.xlsx Office Open XML — via XlsxWriter)
       ============================================================ */

    /**
     * Bangun seluruh worksheet laporan keuangan ke dalam XlsxWriter.
     */
    private function buildExcelSheets(XlsxWriter $w, array $d): void
    {
        $periodeTeks = !empty($d['tahunAktif'])
            ? ('Tahun ' . $d['tahunAktif'])
            : ((!empty($d['dari']) ? $d['dari'] : 'Awal') . ' s/d ' . ($d['sampai'] ?? 'Sekarang'));

        // ===== SHEET 1: RINGKASAN =====
        $s = $w->sheet('Ringkasan');
        $s->widths([260, 180, 180]);
        $s->row([$s->text('LAPORAN KEUANGAN FIXPRO', 'title')]);
        $s->row([$s->text('Periode: ' . $periodeTeks, 'sub')]);
        $s->row([$s->text('Dicetak: ' . now()->format('d/m/Y H:i'), 'sub')]);
        $s->blankRow();

        $ringkasan = [
            ['LABA BERSIH', $d['labaBersih'], 'rp_total', 'Margin ' . $d['margin'] . '% dari Omset Bersih ' . number_format((float)$d['totalOmset'], 0, ',', '.')],
            ['Laba Servis', $d['labaServis'], 'rp', 'Biaya Servis - Harga Jual Sparepart'],
            ['Laba Sparepart Servis', $d['labaSpServis'], 'rp', 'Hrg Jual SP ' . number_format((float)$d['totalHargaJualSpServis'], 0, ',', '.') . ' - Modal SP ' . number_format((float)$d['totalModalServisSP'], 0, ',', '.')],
            ['Laba Sparepart POS (Setelah Diskon)', $d['labaSP'], 'rp', 'Omset Bersih ' . number_format((float)($d['totalOmsetSP'] - $d['totalDiskonSP']), 0, ',', '.') . ' - Modal ' . number_format((float)$d['totalModalSP'], 0, ',', '.')],
            ['Total Omset Bersih', $d['totalOmset'], 'rp', 'Servis ' . number_format((float)$d['totalOmsetServis'], 0, ',', '.') . ' + SP POS Bersih ' . number_format((float)($d['totalOmsetSP'] - $d['totalDiskonSP']), 0, ',', '.')],
            ['', null, '', ''],
            ['--- DI LUAR LABA ---', null, '', ''],
            ['Total Diskon Sparepart POS', $d['totalDiskonSP'] ?? 0, 'rp_red', 'Diskon dipisahkan dari perhitungan laba'],
            ['Total Omset Kotor SP POS', $d['totalOmsetSP'], 'rp', 'Sebelum dikurangi diskon'],
            ['Total Modal Sparepart', $d['totalModal'], 'rp_red', 'Servis ' . number_format((float)$d['totalModalServisSP'], 0, ',', '.') . ' + POS ' . number_format((float)$d['totalModalSP'], 0, ',', '.')],
            ['', null, '', ''],
            ['Kas Masuk', $d['kasMasuk'], 'rp', ''],
            ['Kas Keluar', $d['kasKeluar'], 'rp', ''],
            ['Selisih Kas (Masuk - Keluar)', ($d['kasMasuk'] - $d['kasKeluar']), 'rp', ''],
            ['', null, '', ''],
            ['Total Transaksi', null, '', $d['totalTransaksi'] . ' (' . $d['totalTransaksiServis'] . ' servis + ' . $d['totalTransaksiSP'] . ' sparepart POS)'],
        ];

        $s->headerRow(['Kategori', 'Nominal (Rp)', 'Keterangan']);
        foreach ($ringkasan as $r) {
            $s->row([
                $s->text($r[0], 'bold'),
                $r[1] !== null ? $s->money($r[1], $r[2]) : $s->blank(),
                $s->text($r[3] ?? ''),
            ]);
        }

        // ===== SHEET 2: SERVIS SELESAI =====
        $s2 = $w->sheet('Servis Selesai');
        $s2->widths([130, 90, 160, 180, 120, 200, 100, 100, 100, 90, 110]);
        $s2->headerRow(['Kode', 'Tanggal', 'Pelanggan', 'Perangkat', 'Teknisi', 'Keluhan', 'Biaya Jasa', 'Harga Jual SP', 'Modal SP', 'Laba SP', 'Laba Servis']);
        foreach ($d['servisSelesai'] as $srv) {
            $biaya     = (float) $srv->biaya;
            $hargaJual = (float) $srv->harga_jual_sp;
            $modalSp   = (float) $srv->modal_sp;
            $labaSp    = $hargaJual - $modalSp;
            $laba      = $biaya - $hargaJual;
            $s2->row([
                $s2->text($srv->kode),
                $s2->text($srv->tgl_diambil?->format('d/m/Y')),
                $s2->text($srv->pelanggan?->nama ?? '-'),
                $s2->text($srv->perangkat),
                $s2->text($srv->teknisi?->nama ?? '-'),
                $s2->text($srv->keluhan),
                $s2->money($biaya, 'rp'),
                $s2->money($hargaJual, 'rp'),
                $s2->money($modalSp, 'rp_red'),
                $s2->money($labaSp, 'rp'),
                $s2->money($laba, 'rp_green'),
            ]);
        }
        // Total row
        $s2->row([
            $s2->text('TOTAL', 'total'),
            $s2->blank(), $s2->blank(), $s2->blank(), $s2->blank(), $s2->blank(),
            $s2->money($d['totalOmsetServis'], 'rp_total'),
            $s2->money($d['totalHargaJualSpServis'], 'rp_total'),
            $s2->money($d['totalModalServisSP'], 'rp_total'),
            $s2->money($d['labaSpServis'], 'rp_total'),
            $s2->money($d['labaServis'], 'rp_total'),
        ]);

        // ===== SHEET 3: PENJUALAN SPAREPART =====
        $s3 = $w->sheet('Penjualan Sparepart');
        $s3->widths([130, 90, 160, 60, 120, 120, 120, 110]);
        $s3->headerRow(['No. Transaksi', 'Tanggal', 'Pelanggan', 'Qty', 'Total', 'Diskon', 'Modal', 'Laba', 'Metode']);
        foreach ($d['penjualanSP'] as $sp) {
            $modal  = (float) ($sp->modal_total ?? 0);
            $total  = (float) ($sp->total ?? 0);
            $diskon = (float) ($sp->diskon ?? 0);
            $s3->row([
                $s3->text($sp->no_transaksi ?? (string) $sp->id),
                $s3->text($sp->tanggal?->format('d/m/Y')),
                $s3->text($sp->pelanggan?->nama ?? 'Umum'),
                $s3->num($sp->qty ?? 1),
                $s3->money($total, 'rp'),
                $s3->money($diskon > 0 ? $diskon : 0, 'rp_red'),
                $s3->money($modal, 'rp_red'),
                $s3->money($total - $modal, 'rp_green'),
                $s3->text($sp->metode_bayar ?? '-'),
            ]);
        }
        $s3->row([
            $s3->text('TOTAL', 'total'),
            $s3->blank(), $s3->blank(), $s3->blank(),
            $s3->money($d['totalOmsetSP'], 'rp_total'),
            $s3->money($d['totalDiskonSP'] ?? 0, 'rp_total'),
            $s3->money($d['totalModalSP'], 'rp_total'),
            $s3->money($d['labaSP'], 'rp_total'),
            $s3->blank(),
        ]);

        // ===== SHEET 4: OMSET PER TEKNISI =====
        $s4 = $w->sheet('Omset Teknisi');
        $s4->widths([200, 120, 160]);
        $s4->headerRow(['Teknisi', 'Jml Servis', 'Omset']);
        $totJml = 0; $totOmset = 0;
        foreach ($d['teknisiPerf'] as $t) {
            $s4->row([
                $s4->text($t->nama),
                $s4->num($t->total),
                $s4->money($t->omset, 'rp'),
            ]);
            $totJml += $t->total;
            $totOmset += $t->omset;
        }
        $s4->row([
            $s4->text('TOTAL', 'total'),
            $s4->num($totJml, 'total'),
            $s4->money($totOmset, 'rp_total'),
        ]);

        // ===== SHEET 5: TREN 12 BULAN =====
        $s5 = $w->sheet('Tren 12 Bulan');
        $s5->widths([100, 140, 140, 140, 140, 140, 140]);
        $s5->headerRow(['Bulan', 'Omset Servis', 'Omset SP Kotor', 'Diskon SP', 'Modal SP', 'Laba SP (Bersih)', 'Laba Total']);
        foreach ($d['monthlyBreakdown'] as $m) {
            $s5->row([
                $s5->text($m['month']),
                $s5->money($m['omset_servis'], 'rp'),
                $s5->money($m['omset_sp'], 'rp'),
                $s5->money($m['diskon_sp'] ?? 0, 'rp_red'),
                $s5->money($m['modal_sp'], 'rp_red'),
                $s5->money($m['laba_sp'], 'rp_green'),
                $s5->money($m['laba_total'], 'rp_total'),
            ]);
        }
    }
}
