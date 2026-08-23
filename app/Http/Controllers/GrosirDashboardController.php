<?php

namespace App\Http\Controllers;

use App\Models\PenjualanGrosir;
use App\Models\PelangganGrosir;
use App\Models\PesananGrosir;
use App\Models\Stok;
use App\Services\GrosirService;
use Illuminate\Http\Request;

class GrosirDashboardController extends Controller
{
    public function index(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.dashboard')]);
        }
        $cabangId = $gate;

        $today = now()->format('Y-m-d');
        $bulanIni = now()->startOfMonth();

        // ===== Statistik penjualan grosir (HANYA cabang aktif — tidak campur toko lain) =====
        $base = PenjualanGrosir::where('cabang_id', $cabangId)->where('status', '!=', 'Dibatalkan');
        $hariIni = (clone $base)->whereDate('tanggal', $today);
        $omsetHariIni = (clone $hariIni)->sum('total');
        $transaksiHariIni = (clone $hariIni)->count();

        $bulan = (clone $base)->where('tanggal', '>=', $bulanIni);
        $omsetBulanIni = (clone $bulan)->sum('total');
        $labaBulanIni = (clone $bulan)->get()->sum(function ($p) {
            return $p->items->sum(fn($i) => ($i->harga_satuan - $i->modal_satuan) * $i->qty) - $p->diskon;
        });
        // Eager load items untuk laba (hindari N+1)
        $labaBulanIni = (clone $bulan)->with('items')->get()
            ->sum(fn($p) => $p->items->sum(fn($i) => ($i->harga_satuan - $i->modal_satuan) * $i->qty) - $p->diskon);

        // ===== Piutang =====
        $piutangList = PenjualanGrosir::with(['pelanggan', 'items'])
            ->where('cabang_id', $cabangId)
            ->whereIn('status', ['Piutang', 'Sebagian'])
            ->get();
        $totalPiutang = $piutangList->sum(fn($p) => $p->sisaPiutang());
        $jatuhTempo = $piutangList->filter(fn($p) => $p->jatuh_tempo && $p->jatuh_tempo->isPast() && $p->sisaPiutang() > 0)->count();

        // ===== Pesanan aktif =====
        $pesananAktif = PesananGrosir::where('cabang_id', $cabangId)
            ->whereIn('status', ['Menunggu', 'Diproses'])->count();

        // ===== Pelanggan & produk =====
        $totalPelanggan = PelangganGrosir::where('cabang_id', $cabangId)->where('aktif', true)->count();
        $produkGrosir = Stok::where('cabang_id', $cabangId)
            ->whereHas('hargaGrosir', fn($q) => $q->where('aktif', true))->count();
        $stokRendah = Stok::where('cabang_id', $cabangId)
            ->whereColumn('stok', '<=', 'min_alert')->count();

        // ===== Transaksi terakhir =====
        $terakhir = PenjualanGrosir::with(['pelanggan', 'user'])
            ->where('cabang_id', $cabangId)
            ->orderByDesc('created_at')->limit(8)->get();

        // ===== Chart omzet 7 hari =====
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $chart[$d] = (float) (clone $base)->whereDate('tanggal', $d)->sum('total');
        }

        return view('grosir.dashboard', compact(
            'omsetHariIni', 'transaksiHariIni', 'omsetBulanIni', 'labaBulanIni',
            'totalPiutang', 'jatuhTempo', 'pesananAktif', 'totalPelanggan',
            'produkGrosir', 'stokRendah', 'terakhir', 'chart'
        ));
    }
}
