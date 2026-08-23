<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Servis;
use App\Models\Kas;
use App\Models\Stok;
use App\Models\Pelanggan;
use App\Models\Teknisi;
use App\Models\Cabang;
use App\Models\BannerIklan;
use App\Models\PenjualanSparepart;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isUser() && !$user->isAdmin() && !$user->isStaff()) {
            return $this->userDashboard($user);
        }

        return $this->adminDashboard($user, $request);
    }

    /**
     * Resolve cabang_id: query param > session > user default
     * Uses getApiCabangId() which is mobile-friendly
     */
    private function resolveCabangId($user, Request $request)
    {
        return $user->getApiCabangId($request);
    }

    private function adminDashboard($user, Request $request)
    {
        $cabangId = $this->resolveCabangId($user, $request);
        $today = now()->format('Y-m-d');
        $monthStart = now()->startOfMonth()->format('Y-m-d');

        $isSuperAdmin = $user->isSuperAdmin();
        $showAll = $isSuperAdmin && $cabangId === null;

        // ====== Base queries ======
        $servisQuery = $showAll ? Servis::query() : Servis::where('cabang_id', $cabangId);
        $kasQuery = $showAll ? Kas::query() : Kas::where('cabang_id', $cabangId);
        $spQuery = $showAll ? PenjualanSparepart::query() : PenjualanSparepart::where('cabang_id', $cabangId);

        // ====== STAT CARDS ======
        $totalServis = (clone $servisQuery)->count();
        $menungguDiambil = (clone $servisQuery)->where('status', 'Selesai')->where('diambil', false)->count();
        $selesaiHariIni = (clone $servisQuery)->whereDate('tgl_diambil', $today)->where('diambil', true)->count();
        $labaJasaServisHariIni = (clone $servisQuery)->where('status', 'Selesai')->whereDate('tgl_diambil', $today)->sum('biaya');

        // SP hari ini (POS)
        $spHariIni = (clone $spQuery)->whereDate('tanggal', $today)->where('status', '!=', 'Dibatalkan')->get();
        $posTotalHariIni = $spHariIni->sum('total');
        $posModalHariIni = $spHariIni->sum('modal_total');
        $posLabaHariIni = $posTotalHariIni - $posModalHariIni;

        // SP dari servis hari ini
        $servisSpHariIni = (clone $servisQuery)->where('status', 'Selesai')->whereDate('tgl_diambil', $today)->get();
        $servisSpTotalHariIni = 0;
        $servisSpModalHariIni = 0;
        foreach ($servisSpHariIni as $s) {
            if ($s->spareparts) {
                foreach ($s->spareparts as $sp) {
                    $servisSpTotalHariIni += (float) ($sp['harga'] ?? 0) * (int) ($sp['qty'] ?? 1);
                }
            }
            $servisSpModalHariIni += (float) ($s->modal_sparepart ?? 0);
        }
        $servisSpLabaHariIni = $servisSpTotalHariIni - $servisSpModalHariIni;
        // Laba Servis = Biaya Servis - Harga Jual Sparepart (biaya sudah termasuk harga jual sparepart)
        $labaJasaServisHariIni = $labaJasaServisHariIni - $servisSpTotalHariIni;

        $omsetHariIni = $labaJasaServisHariIni + $servisSpTotalHariIni + $posTotalHariIni;
        $labaSparepartHariIni = $servisSpLabaHariIni + $posLabaHariIni;
        $labaBersihHariIni = $labaJasaServisHariIni + $labaSparepartHariIni;

        // ====== OMSET BULANAN ======
        $servisBulan = (clone $servisQuery)->where('status', 'Selesai')
            ->whereBetween('tgl_diambil', [$monthStart, $today . ' 23:59:59'])->get();
        $omsetJasaServisBulan = 0;
        $omsetHargaJualSpServisBulan = 0;
        $modalSpServisBulan = 0;
        foreach ($servisBulan as $s) {
            $hargaJual = 0;
            if (is_array($s->spareparts)) {
                foreach ($s->spareparts as $sp) {
                    $hargaJual += (float) ($sp['harga'] ?? 0) * (int) ($sp['qty'] ?? 1);
                }
            }
            $omsetJasaServisBulan += (float) $s->biaya;
            $omsetHargaJualSpServisBulan += $hargaJual;
            $modalSpServisBulan += (float) ($s->modal_sparepart ?? 0);
        }
        $labaSpServisBulan = $omsetHargaJualSpServisBulan - $modalSpServisBulan;
        $labaServisBulan = $omsetJasaServisBulan - $omsetHargaJualSpServisBulan; // Laba Servis = biaya - harga jual sparepart

        $spBulan = (clone $spQuery)->where('status', '!=', 'Dibatalkan')
            ->whereBetween('tanggal', [$monthStart, $today])->get();
        $omsetSpBulan = $spBulan->sum('total');
        $modalSpBulan = $spBulan->sum('modal_total');
        $labaSpBulan = $omsetSpBulan - $modalSpBulan;

        $omsetBulanan = $omsetJasaServisBulan + $omsetSpBulan;
        $labaBersihBulan = $labaServisBulan + $labaSpServisBulan + $labaSpBulan;
        $totalPendapatanBulan = $omsetJasaServisBulan + $omsetSpBulan;
        $marginBersihBulan = $totalPendapatanBulan > 0 ? round(($labaBersihBulan / $totalPendapatanBulan) * 100) : 0;

        // ====== SALDO KAS ======
        $lastKas = $showAll
            ? Kas::orderBy('waktu', 'desc')->first()
            : Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
        $saldoKas = $lastKas ? $lastKas->saldo : 0;

        // ====== STATUS CHART ======
        $statusChart = [
            'Masuk' => (clone $servisQuery)->where('status', 'Masuk')->count(),
            'Proses' => (clone $servisQuery)->where('status', 'Proses')->count(),
            'Pending' => (clone $servisQuery)->where('status', 'Pending')->count(),
            'Selesai' => (clone $servisQuery)->where('status', 'Selesai')->where('diambil', false)->count(),
            'Diambil' => (clone $servisQuery)->where('status', 'Selesai')->where('diambil', true)->count(),
            'Dibatalkan' => (clone $servisQuery)->where('status', 'Dibatalkan')->count(),
        ];

        $servisMasuk = $statusChart['Masuk'];
        $servisProses = $statusChart['Proses'];
        $servisPending = $statusChart['Pending'];
        $servisSelesai = $statusChart['Selesai'] + $statusChart['Diambil'];

        // ====== KAS FLOW 7 HARI ======
        $kasFlow = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $masuk = (clone $kasQuery)->where('tipe', 'masuk')->whereDate('waktu', $date)->sum('jml');
            $keluar = (clone $kasQuery)->where('tipe', 'keluar')->whereDate('waktu', $date)->sum('jml');
            $kasFlow[] = ['date' => now()->subDays($i)->format('d/m'), 'masuk' => (float) $masuk, 'keluar' => (float) $keluar];
        }

        // ====== OMET PER TEKNISI ======
        $teknisiPerf = Teknisi::with(['servis' => function ($q) use ($cabangId, $showAll) {
            $q->where('status', 'Selesai');
        }])->where('aktif', true);
        if (!$showAll) $teknisiPerf->where('cabang_id', $cabangId);
        $teknisiPerf = $teknisiPerf->get()->map(function ($t) {
            $selesai = $t->servis;
            $t->total = $selesai->count();
            $t->omset = $selesai->sum('biaya');
            return $t;
        });

        // ====== LATEST SERVIS BULAN INI ======
        $latestServis = (clone $servisQuery)->where('status', 'Selesai')
            ->whereBetween('tgl_diambil', [$monthStart, $today . ' 23:59:59'])
            ->with(['pelanggan', 'teknisi'])->orderBy('tgl_diambil', 'desc')->take(10)->get()
            ->map(function ($s) {
                $hargaJual = 0;
                if (is_array($s->spareparts)) {
                    foreach ($s->spareparts as $sp) {
                        $hargaJual += (float) ($sp['harga'] ?? 0) * (int) ($sp['qty'] ?? 1);
                    }
                }
                return [
                    'id' => $s->id,
                    'kode' => $s->kode,
                    'tgl_diambil' => $s->tgl_diambil?->format('d/m/Y'),
                    'pelanggan' => $s->pelanggan?->nama ?? '-',
                    'perangkat' => $s->perangkat,
                    'teknisi' => $s->teknisi?->nama ?? '-',
                    'biaya' => (float) $s->biaya,
                    'harga_jual_sp' => $hargaJual,
                    'modal_sp' => (float) ($s->modal_sparepart ?? 0),
                    'laba_sp' => $hargaJual - (float) ($s->modal_sparepart ?? 0),
                    'laba_total' => (float) $s->biaya - $hargaJual,
                ];
            });

        // ====== LATEST SP BULAN INI ======
        $latestSp = (clone $spQuery)->where('status', '!=', 'Dibatalkan')
            ->whereBetween('tanggal', [$monthStart, $today])
            ->orderBy('tanggal', 'desc')->take(5)->get()
            ->map(fn($sp) => [
                'id' => $sp->id,
                'no_transaksi' => $sp->no_transaksi ?? (string) $sp->id,
                'tanggal' => $sp->tanggal?->format('d/m/Y'),
                'pelanggan' => $sp->pelanggan?->nama ?? 'Umum',
                'total' => (float) ($sp->total ?? 0),
                'modal' => (float) ($sp->modal_total ?? 0),
                'laba' => (float) ($sp->total ?? 0) - (float) ($sp->modal_total ?? 0),
            ]);

        // ====== STOK ALERTS ======
        $stokAlertsQuery = Stok::whereColumn('stok', '<=', 'min_alert');
        if (!$showAll) $stokAlertsQuery->where('cabang_id', $cabangId);
        $stokAlerts = (clone $stokAlertsQuery)->orderBy('stok', 'asc')->take(10)->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'nama' => $s->nama,
                'kode' => $s->kode,
                'stok' => (int) $s->stok,
                'min' => (int) $s->min_alert,
                'satuan' => $s->satuan ?? 'pcs',
            ]);
        $stokAlertsCount = (clone $stokAlertsQuery)->count();

        // ====== BANNERS ======
        $banners = BannerIklan::getAktif()->map(fn($b) => [
            'id' => $b->id,
            'judul' => $b->judul,
            'deskripsi' => $b->deskripsi,
            'gambar_url' => $b->gambar ? (str_starts_with($b->gambar, 'http') ? $b->gambar : url('storage/' . $b->gambar)) : null,
            'link' => $b->link,
        ]);

        // ====== EXTRA STATS ======
        $totalPelanggan = $showAll
            ? Pelanggan::has('servis')->count()
            : Pelanggan::whereHas('servis', fn($q) => $q->where('cabang_id', $cabangId))->count();
        $totalTeknisi = $showAll
            ? Teknisi::where('aktif', true)->count()
            : Teknisi::where('aktif', true)->where('cabang_id', $cabangId)->count();

        $activeCabang = $showAll ? null : Cabang::find($cabangId);

        // ====== CABANG LIST (for Super Admin selector) ======
        $cabangList = null;
        if ($isSuperAdmin) {
            $cabangList = Cabang::orderBy('id')->get()->map(fn($c) => [
                'id' => $c->id,
                'nama' => $c->nama,
                'aktif' => (bool) $c->aktif,
            ])->toArray();
        }

        return response()->json([
            // Cabang
            'active_cabang' => $activeCabang ? ['id' => $activeCabang->id, 'nama' => $activeCabang->nama] : null,
            'show_all' => $showAll,
            'cabang_list' => $cabangList,
            'selected_cabang_id' => $cabangId,

            // 6 Top stat cards (same as web dashboard.blade.php)
            'total_servis' => $totalServis,
            'menunggu_diambil' => $menungguDiambil,
            'selesai_hari_ini' => $selesaiHariIni,
            'laba_servis_hari_ini' => (float) $labaJasaServisHariIni,
            'omset_hari_ini' => (float) $omsetHariIni,
            'laba_sp_hari_ini' => (float) $labaSparepartHariIni,

            // Analisis SP hari ini (same as web)
            'pos_total' => (float) $posTotalHariIni,
            'pos_modal' => (float) $posModalHariIni,
            'pos_laba' => (float) $posLabaHariIni,
            'servis_sp_total' => (float) $servisSpTotalHariIni,
            'servis_sp_modal' => (float) $servisSpModalHariIni,
            'servis_sp_laba' => (float) $servisSpLabaHariIni,

            // 4 Secondary stat cards
            'omset_bulanan' => (float) $omsetBulanan,
            'laba_bersih_hari_ini' => (float) $labaBersihHariIni,
            'saldo_kas' => (float) $saldoKas,
            'laba_sp_bulan' => (float) $labaSpBulan,
            'margin_sp_bulan' => ($omsetSpBulan + $omsetHargaJualSpServisBulan) > 0
                ? round(($labaSpBulan / ($omsetSpBulan + $omsetHargaJualSpServisBulan)) * 100) : 0,

            // Laba bersih bulan (same as web highlight card)
            'laba_bersih_bulan' => (float) $labaBersihBulan,
            'laba_servis_bulan' => (float) $labaServisBulan,
            'total_pendapatan_bulan' => (float) $totalPendapatanBulan,
            'margin_bersih_bulan' => $marginBersihBulan,

            // Status chart (same as web doughnut)
            'status_chart' => $statusChart,

            // Ringkasan bulan (same as web)
            'servis_masuk' => $servisMasuk,
            'servis_proses' => $servisProses,
            'servis_pending' => $servisPending,
            'servis_selesai' => $servisSelesai,
            'total_teknisi' => $totalTeknisi,
            'total_pelanggan' => $totalPelanggan,

            // Kas flow 7 hari
            'kas_flow' => $kasFlow,

            // Omset teknisi
            'teknisi_perf' => $teknisiPerf->map(fn($t) => [
                'id' => $t->id,
                'nama' => $t->nama,
                'total' => $t->total,
                'omset' => (float) $t->omset,
            ]),

            // Latest transaksi
            'latest_servis' => $latestServis,
            'latest_sp' => $latestSp,

            // Stok alerts
            'stok_alerts' => $stokAlerts,
            'stok_alerts_count' => $stokAlertsCount,

            // Banners
            'banners' => $banners,
        ]);
    }

    private function userDashboard($user)
    {
        $myServis = Servis::with(['pelanggan', 'teknisi'])
            ->whereHas('pelanggan', function ($q) use ($user) {
                $q->where('no_hp', $user->phone)->orWhere('nama', $user->name);
            })
            ->orderBy('created_at', 'desc')->take(10)->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'kode' => $s->kode,
                'tanggal' => $s->tanggal?->format('Y-m-d'),
                'pelanggan' => $s->pelanggan?->nama,
                'perangkat' => $s->perangkat,
                'keluhan' => $s->keluhan,
                'status' => $s->status,
                'biaya' => (float) $s->biaya,
                'teknisi' => $s->teknisi?->nama,
                'created_at' => $s->created_at?->format('Y-m-d H:i'),
            ]);

        $banners = BannerIklan::getAktif()->map(fn($b) => [
            'id' => $b->id,
            'judul' => $b->judul,
            'deskripsi' => $b->deskripsi,
            'gambar_url' => $b->gambar ? (str_starts_with($b->gambar, 'http') ? $b->gambar : url('storage/' . $b->gambar)) : null,
            'link' => $b->link,
        ]);

        return response()->json([
            'total_servis' => $myServis->count(),
            'my_servis' => $myServis,
            'banners' => $banners,
        ]);
    }
}
