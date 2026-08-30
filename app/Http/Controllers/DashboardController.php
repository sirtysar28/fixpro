<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Servis;
use App\Models\Kas;
use App\Models\Stok;
use App\Models\Pelanggan;
use App\Models\Teknisi;
use App\Models\Cabang;
use App\Models\BannerIklan;
use App\Models\PenjualanSparepart;
use App\Models\Setting;
use App\Models\JualBeli;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->isUser() && !auth()->user()->isAdmin() && !auth()->user()->isStaff()) {
            return $this->userDashboard();
        }
        return $this->adminDashboard();
    }

    private function getCabangId()
    {
        return auth()->user()->getActiveCabangId();
    }

    /**
     * API: daftar peringatan stok (paginate) untuk dashboard.
     */
    public function stokAlerts(Request $request)
    {
        $cabangId = $this->getCabangId();
        $showAll = auth()->user()->isSuperAdmin() && session('cabang_id') === 'all';

        $query = Stok::whereColumn('stok', '<=', 'min_alert');
        if (!$showAll) {
            $query->where('cabang_id', $cabangId);
        }
        $perPage = 8;
        $page = (int) $request->get('page', 1);

        $total = (clone $query)->count();
        $items = (clone $query)->orderBy('stok', 'asc')
            ->offset(($page - 1) * $perPage)->limit($perPage)->get();

        $lastPage = (int) ceil($total / $perPage);

        return response()->json([
            'items' => $items->map(fn($s) => [
                'id'    => $s->id,
                'nama'  => $s->nama,
                'kode'  => $s->kode,
                'stok'  => (int) $s->stok,
                'min'   => (int) $s->min_alert,
                'satuan'=> $s->satuan ?? 'pcs',
            ]),
            'current_page' => $page,
            'last_page'    => max(1, $lastPage),
            'total'        => $total,
            'from'         => $total === 0 ? 0 : (($page - 1) * $perPage) + 1,
            'to'           => min($total, $page * $perPage),
        ]);
    }

    private function adminDashboard()
    {
        $cabangId = $this->getCabangId();
        $today = now()->format('Y-m-d');
        $monthStart = now()->startOfMonth()->format('Y-m-d');

        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $showAll = $isSuperAdmin && session('cabang_id') === 'all';

        // ====== Base queries ======
        $servisQuery = $showAll ? Servis::query() : Servis::where('cabang_id', $cabangId);
        $kasQuery = $showAll ? Kas::query() : Kas::where('cabang_id', $cabangId);
        $spQuery = $showAll ? PenjualanSparepart::query() : PenjualanSparepart::where('cabang_id', $cabangId);

        // ====== STAT CARDS ======

        // 1. Total Servis (all time)
        $totalServis = (clone $servisQuery)->count();

        // 2. Menunggu Diambil (Selesai tapi belum diambil)
        $menungguDiambil = (clone $servisQuery)->where('status', 'Selesai')->where('diambil', false)->count();

        // 3. Selesai Hari Ini (Selesai + diambil hari ini)
        $selesaiHariIni = (clone $servisQuery)->whereDate('tgl_diambil', $today)->where('diambil', true)->count();

        // 4. Laba Jasa Servis Hari Ini (biaya jasa servis Selesai/Diambil hari ini)
        $labaJasaServisHariIni = (clone $servisQuery)
            ->where('status', 'Selesai')
            ->whereDate('tgl_diambil', $today)
            ->sum('biaya');

        // 5. Omset Harian = Laba Jasa Servis Hari Ini + Omset Sparepart Hari Ini
        $spHariIni = (clone $spQuery)
            ->whereDate('tanggal', $today)
            ->where('status', '!=', 'Dibatalkan')
            ->get();
        $omsetSparepartHariIni = $spHariIni->sum('total');
        $modalSparepartHariIni = $spHariIni->sum('modal_total');
        $labaSparepartHariIni = $omsetSparepartHariIni - $modalSparepartHariIni;

        // Omset dihitung nanti setelah SP dari servis diketahui

        // ====== ANALISIS SPAREPART HARI INI ======
        // Penjualan POS (dari PenjualanSparepart)
        $posHariIni = (clone $spQuery)
            ->whereDate('tanggal', $today)
            ->where('status', '!=', 'Dibatalkan')
            ->get();
        $posTotalHariIni = $posHariIni->sum('total');
        $posModalHariIni = $posHariIni->sum('modal_total');
        $posLabaHariIni = $posTotalHariIni - $posModalHariIni;

        // SP dari Input Servis (spareparts field di Servis)
        $servisSpHariIni = (clone $servisQuery)
            ->where('status', 'Selesai')
            ->whereDate('tgl_diambil', $today)
            ->get();
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
        $totalPendapatanSpHariIni = $posTotalHariIni + $servisSpTotalHariIni;
        $totalModalSpHariIni = $posModalHariIni + $servisSpModalHariIni;

        // Omset Harian = Semua pendapatan hari ini (Jasa Servis + Harga Jual SP Servis + Total SP POS)
        $omsetHariIni = $labaJasaServisHariIni + $servisSpTotalHariIni + $posTotalHariIni;

        // ====== OMSET BULANAN ======
        $omsetServisBulanan = (clone $servisQuery)
            ->where('status', 'Selesai')
            ->whereBetween('tgl_diambil', [$monthStart, $today . ' 23:59:59'])
            ->sum('biaya');
        $omsetSpBulanan = (clone $spQuery)
            ->whereBetween('tanggal', [$monthStart, $today])
            ->where('status', '!=', 'Dibatalkan')
            ->sum('total');
        // Omset bulanan dihitung ulang setelah harga jual SP servis diketahui (lihat bawah)
        $omsetBulanan = 0; // placeholder

        // ====== LABA BERSIH HARI INI ======
        // Rumus: Laba Jasa Servis + Laba SP dari Servis + Laba SP dari POS
        $labaBersihHariIni = $labaJasaServisHariIni + $servisSpLabaHariIni + $labaSparepartHariIni;
        // Alias untuk kompatibilitas tampilan
        $labaServisHariIni = $labaJasaServisHariIni;

        // ====== SALDO KAS ======
        $lastKas = $showAll
            ? Kas::orderBy('waktu', 'desc')->first()
            : Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
        $saldoKas = $lastKas ? $lastKas->saldo : 0;

        // ====== LABA SPAREPART BULAN INI ======
        $allSpBulan = (clone $spQuery)->where('status', '!=', 'Dibatalkan')
            ->whereBetween('tanggal', [$monthStart, $today])->get();
        $totalLabaSparepart = $allSpBulan->sum(fn($p) => $p->total - $p->modal_total);
        $totalPendapatanSp = $allSpBulan->sum('total');
        $marginSp = $totalPendapatanSp > 0 ? round(($totalLabaSparepart / $totalPendapatanSp) * 100) : 0;

        // ====== LABA BERSIH BULAN INI ======
        $servisBulanForLaba = (clone $servisQuery)->where('status', 'Selesai')
            ->whereBetween('tgl_diambil', [$monthStart, $today . ' 23:59:59'])->get();
        $omsetJasaServisBulan = 0;
        $omsetHargaJualSpServisBulan = 0;
        $modalSpServisBulan = 0;
        foreach ($servisBulanForLaba as $s) {
            $hargaJual = 0;
            if (is_array($s->spareparts)) {
                foreach ($s->spareparts as $sp) {
                    $hargaJual += (float) ($sp['harga'] ?? 0) * (int) ($sp['qty'] ?? 1);
                }
            }
            $modal = (float) ($s->modal_sparepart ?? 0);
            $omsetJasaServisBulan += (float) $s->biaya;
            $omsetHargaJualSpServisBulan += $hargaJual;
            $modalSpServisBulan += $modal;
        }
        // Laba Servis = Biaya Servis - Harga Jual Sparepart
        $labaServisTotal = $omsetJasaServisBulan - $omsetHargaJualSpServisBulan;
        // Laba bersih = Laba Servis + Laba SP Servis (harga jual - modal) + Laba SP POS
        $labaBersihTotal = $labaServisTotal + ($omsetHargaJualSpServisBulan - $modalSpServisBulan) + $totalLabaSparepart;
        // Total pendapatan (omset) = Biaya Servis (sudah termasuk sparepart) + SP POS
        $totalPendapatan = $omsetJasaServisBulan + $totalPendapatanSp;
        $marginBersihTotal = $totalPendapatan > 0 ? round(($labaBersihTotal / $totalPendapatan) * 100) : 0;

        // Omset Bulanan = Biaya Servis + Total SP POS
        $omsetBulanan = $omsetJasaServisBulan + $totalPendapatanSp;

        // ====== STATUS SERVIS BREAKDOWN ======
        $statusChart = [
            'Masuk' => (clone $servisQuery)->where('status', 'Masuk')->count(),
            'Proses' => (clone $servisQuery)->where('status', 'Proses')->count(),
            'Pending' => (clone $servisQuery)->where('status', 'Pending')->count(),
            'Selesai' => (clone $servisQuery)->where('status', 'Selesai')->where('diambil', false)->count(),
            'Diambil' => (clone $servisQuery)->where('status', 'Selesai')->where('diambil', true)->count(),
            'Dibatalkan' => (clone $servisQuery)->where('status', 'Dibatalkan')->count(),
        ];

        // ====== OMET PER TEKNISI ======
        $teknisiPerf = Teknisi::withCount(['servis', 'servis as servis_selesai_count' => function ($q) {
            $q->where('status', 'Selesai');
        }])
        ->with(['servis' => function ($q) {
            $q->where('status', 'Selesai');
        }])
        ->where('aktif', true);
        if (!$showAll) {
            $teknisiPerf->where('cabang_id', $cabangId);
        }
        $teknisiPerf = $teknisiPerf->get()->map(function ($t) {
            $t->omset = $t->servis->sum('biaya');
            return $t;
        });

        // ====== KAS FLOW 7 HARI ======
        $kasFlow = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $masuk = (clone $kasQuery)->where('tipe', 'masuk')->whereDate('waktu', $date)->sum('jml');
            $keluar = (clone $kasQuery)->where('tipe', 'keluar')->whereDate('waktu', $date)->sum('jml');
            $kasFlow[] = ['date' => now()->subDays($i)->format('d/m'), 'masuk' => (float) $masuk, 'keluar' => (float) $keluar];
        }

        // ====== LATEST TRANSAKSI BULAN INI ======
        $latestServisBulan = (clone $servisQuery)->where('status', 'Selesai')
            ->whereBetween('tgl_diambil', [$monthStart, $today . ' 23:59:59'])
            ->with(['pelanggan', 'teknisi'])->orderBy('tgl_diambil', 'desc')->take(10)->get();

        // Anotasi per-baris: harga jual, modal, laba sparepart, laba total
        $latestServisBulan->each(function ($s) {
            $hargaJual = 0;
            if (is_array($s->spareparts)) {
                foreach ($s->spareparts as $sp) {
                    $hargaJual += (float) ($sp['harga'] ?? 0) * (int) ($sp['qty'] ?? 1);
                }
            }
            $s->harga_jual_sp  = $hargaJual;
            $s->modal_sp       = (float) ($s->modal_sparepart ?? 0);
            $s->laba_sp_servis = $hargaJual - $s->modal_sp;
            $s->laba_servis    = (float) $s->biaya - $hargaJual;       // Laba Servis = biaya - harga jual sparepart
            $s->laba_total     = $s->laba_servis + $s->laba_sp_servis; // Total laba = biaya - modal
        });

        $latestSpBulan = (clone $spQuery)->where('status', '!=', 'Dibatalkan')
            ->whereBetween('tanggal', [$monthStart, $today])
            ->orderBy('tanggal', 'desc')->take(5)->get();

        // ====== TOTAL TABEL TRANSAKSI BULAN INI (dihitung dari SEMUA data, bukan hanya yg ditampilkan) ======
        // Servis Selesai + diambil bulan ini (sumber kebenaran untuk kartu omset servis)
        $servisBulanAll = (clone $servisQuery)->where('status', 'Selesai')
            ->whereBetween('tgl_diambil', [$monthStart, $today . ' 23:59:59'])
            ->get();
        $countServisBulan   = $servisBulanAll->count();
        $totalBiayaServisBulan = (float) $servisBulanAll->sum('biaya');
        $totalHargaJualSpServisBulan = 0;
        $totalModalSpServisBulan = 0;
        foreach ($servisBulanAll as $s) {
            $hargaJual = 0;
            if (is_array($s->spareparts)) {
                foreach ($s->spareparts as $sp) {
                    $hargaJual += (float) ($sp['harga'] ?? 0) * (int) ($sp['qty'] ?? 1);
                }
            }
            $totalHargaJualSpServisBulan += $hargaJual;
            $totalModalSpServisBulan += (float) ($s->modal_sparepart ?? 0);
        }
        $totalSparepartServisBulan = $totalHargaJualSpServisBulan; // backward compat (harga jual)
        $totalLabaSpServisBulan = $totalHargaJualSpServisBulan - $totalModalSpServisBulan;
        $totalLabaServisBulan = $totalBiayaServisBulan - $totalHargaJualSpServisBulan; // Laba Servis = biaya - harga jual sparepart

        // Penjualan sparepart bulan ini (sumber kebenaran untuk kartu omset SP)
        $countSpBulan       = $allSpBulan->count();
        $totalPenjualanSpBulan = (float) $allSpBulan->sum('total');
        $totalModalSpBulan  = (float) $allSpBulan->sum('modal_total');
        $totalLabaSpBulan   = $totalPenjualanSpBulan - $totalModalSpBulan;

        // ====== STOK ALERTS ======
        $stokAlerts = $showAll
            ? Stok::whereColumn('stok', '<=', 'min_alert')->get()
            : Stok::whereColumn('stok', '<=', 'min_alert')->where('cabang_id', $cabangId)->get();

        // ====== BANNERS ======
        $banners = BannerIklan::getAktif();
        $activeCabang = $showAll ? null : Cabang::find($cabangId);
        $settings = Setting::pluck('value', 'key')->toArray();

        // ====== EXTRA STATS ======
        $totalPelanggan = $showAll
            ? Pelanggan::has('servis')->count()
            : Pelanggan::whereHas('servis', fn($q) => $q->where('cabang_id', $cabangId))->count();
        $totalTeknisi = $showAll
            ? Teknisi::where('aktif', true)->count()
            : Teknisi::where('aktif', true)->where('cabang_id', $cabangId)->count();

        $servisMasuk = $statusChart['Masuk'];
        $servisProses = $statusChart['Proses'];
        $servisPending = $statusChart['Pending'];
        $servisSelesai = $statusChart['Selesai'] + $statusChart['Diambil'];

        // ====== INVOICE SPAREPART STATS (revisi: pusat transaksi penjualan) ======
        $invoiceStats = null;
        if (\Schema::hasTable('invoice_spareparts')) {
            $invBase = \App\Models\InvoiceSparepart::where('status', '!=', 'Dibatalkan')
                ->when(!$showAll, fn($q) => $q->where('cabang_id', $cabangId));
            $invToday = (clone $invBase)->whereDate('tanggal', $today);
            $invoiceStats = [
                'penjualan_hari_ini' => (clone $invToday)->sum('total'),
                'invoice_hari_ini' => (clone $invToday)->count(),
                'retail' => (clone $invBase)->where('tipe_pelanggan', 'Umum')->sum('total'),
                'grosir' => (clone $invBase)->whereIn('tipe_pelanggan', ['Grosir', 'Distributor'])->sum('total'),
                'reseller' => (clone $invBase)->where('tipe_pelanggan', 'Reseller')->sum('total'),
                'member' => (clone $invBase)->where('tipe_pelanggan', 'Member')->sum('total'),
                'piutang' => \App\Models\InvoiceSparepart::whereIn('status', ['Piutang', 'Sebagian'])
                    ->when(!$showAll, fn($q) => $q->where('cabang_id', $cabangId))->sum('sisa'),
                'jatuh_tempo' => \App\Models\InvoiceSparepart::whereIn('status', ['Piutang', 'Sebagian'])
                    ->whereDate('jatuh_tempo', '<', $today)
                    ->when(!$showAll, fn($q) => $q->where('cabang_id', $cabangId))->count(),
                'pembayaran_masuk' => \Schema::hasTable('invoice_sparepart_payments')
                    ? \App\Models\InvoiceSparepartPayment::whereDate('tanggal', $today)
                        ->whereHas('invoice', fn($q) => $showAll ? $q : $q->where('cabang_id', $cabangId))->sum('jumlah')
                    : 0,
            ];
        }

        return view('dashboard', compact(
            'totalServis', 'menungguDiambil', 'selesaiHariIni',
            'labaServisHariIni', 'omsetHariIni', 'labaSparepartHariIni',
            'posTotalHariIni', 'posModalHariIni', 'posLabaHariIni',
            'servisSpTotalHariIni', 'servisSpModalHariIni', 'servisSpLabaHariIni',
            'totalPendapatanSpHariIni', 'totalModalSpHariIni',
            'omsetBulanan', 'omsetServisBulanan', 'omsetSpBulanan',
            'labaBersihHariIni', 'saldoKas',
            'totalLabaSparepart', 'totalPendapatanSp', 'marginSp',
            'labaBersihTotal', 'labaServisTotal', 'totalPendapatan', 'marginBersihTotal',
            'statusChart', 'teknisiPerf', 'latestServisBulan', 'latestSpBulan', 'stokAlerts',
            'kasFlow', 'banners', 'activeCabang', 'settings',
            'totalPelanggan', 'totalTeknisi', 'invoiceStats',
            'servisMasuk', 'servisProses', 'servisPending', 'servisSelesai',
            'omsetSparepartHariIni', 'showAll',
            'monthStart',
            'countServisBulan', 'totalBiayaServisBulan', 'totalSparepartServisBulan',
            'totalHargaJualSpServisBulan', 'totalModalSpServisBulan', 'totalLabaSpServisBulan', 'totalLabaServisBulan',
            'countSpBulan', 'totalPenjualanSpBulan', 'totalModalSpBulan', 'totalLabaSpBulan',
        ));
    }

    private function userDashboard()
    {
        $user = auth()->user();
        $myServis = Servis::with(['pelanggan', 'teknisi'])
            ->whereHas('pelanggan', function ($q) use ($user) {
                $q->where('no_hp', $user->phone)
                  ->orWhere('nama', $user->name);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $totalServis = $myServis->count();
        $servisMasuk = $myServis->where('status', 'Masuk')->count();
        $servisProses = $myServis->where('status', 'Proses')->count();
        $servisSelesai = $myServis->where('status', 'Selesai')->count();
        $servisPending = $myServis->where('status', 'Pending')->count();
        $totalBiaya = $myServis->sum('biaya');

        $banners = BannerIklan::getAktif();
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('dashboard-user', compact(
            'myServis', 'totalServis', 'servisMasuk', 'servisProses',
            'servisSelesai', 'servisPending', 'totalBiaya', 'user', 'banners', 'settings'
        ));
    }
}
