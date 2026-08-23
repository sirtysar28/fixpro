<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\PenjualanGrosir;
use App\Models\PenjualanGrosirItem;
use App\Models\PelangganGrosir;
use App\Services\GrosirService;
use App\Services\XlsxWriter;
use Illuminate\Http\Request;

class LaporanGrosirController extends Controller
{
    /**
     * Laporan Grosir multi-tab (semua per cabang aktif):
     * penjualan | omzet | laba | terlaris | pelanggan | toko | gudang | piutang
     */
    public function index(Request $request)
    {
        $gate = GrosirService::wajibCabang();
        if ($gate === 'pilih_cabang') {
            return view('stok.pilih-cabang', ['redirectTo' => route('grosir.laporan.index')]);
        }
        $cabangId = $gate;

        $tab = $request->get('tab', 'penjualan');
        $dari = $request->get('dari', now()->startOfMonth()->format('Y-m-d'));
        $sampai = $request->get('sampai', now()->format('Y-m-d'));

        $base = PenjualanGrosir::with(['items', 'pelanggan', 'sumberCabang', 'payments', 'returs'])
            ->where('cabang_id', $cabangId)
            ->where('status', '!=', 'Dibatalkan')
            ->whereDate('tanggal', '>=', $dari)
            ->whereDate('tanggal', '<=', $sampai);

        $data = [];

        // ===== Rekap utama (dipakai semua tab) =====
        $notas = (clone $base)->get();
        $omzet = $notas->sum('total');
        $totalDiskon = $notas->sum('diskon');
        $laba = $notas->sum(function ($p) {
            return $p->items->sum(fn($i) => ($i->harga_satuan - $i->modal_satuan) * $i->qty) - $p->diskon;
        });
        $piutangSisa = $notas->sum(fn($p) => $p->sisaPiutang());

        // ===== Tab: penjualan (daftar nota) =====
        if ($tab === 'penjualan') {
            $data['notas'] = (clone $base)->orderByDesc('tanggal')->paginate(25)->withQueryString();
        }

        // ===== Tab: omzet (per hari) =====
        if ($tab === 'omzet') {
            $perHari = $notas->groupBy(fn($p) => $p->tanggal->format('Y-m-d'))
                ->map(fn($group, $tgl) => (object) [
                    'tanggal' => $tgl,
                    'transaksi' => $group->count(),
                    'omzet' => $group->sum('total'),
                    'diskon' => $group->sum('diskon'),
                    'piutang' => $group->sum('piutang'),
                ])
                ->sortByDesc('tanggal')
                ->values();
            $data['perHari'] = $perHari;
        }

        // ===== Tab: laba =====
        if ($tab === 'laba') {
            $perProduk = PenjualanGrosirItem::whereIn('penjualan_grosir_id', $notas->pluck('id'))
                ->get()
                ->groupBy('stok_id')
                ->map(fn($items) => (object) [
                    'nama' => $items->first()->nama,
                    'kode' => $items->first()->kode,
                    'qty' => $items->sum('qty'),
                    'omzet' => $items->sum(fn($i) => $i->harga_satuan * $i->qty),
                    'modal' => $items->sum(fn($i) => $i->modal_satuan * $i->qty),
                ])
                ->map(fn($r) => tap($r, fn($x) => $x->laba = $x->omzet - $x->modal))
                ->sortByDesc('laba')
                ->values();
            $data['perProduk'] = $perProduk;
        }

        // ===== Tab: terlaris =====
        if ($tab === 'terlaris') {
            $terlaris = PenjualanGrosirItem::whereIn('penjualan_grosir_id', $notas->pluck('id'))
                ->get()
                ->groupBy('stok_id')
                ->map(fn($items) => (object) [
                    'nama' => $items->first()->nama,
                    'kode' => $items->first()->kode,
                    'qty' => $items->sum('qty'),
                    'omzet' => $items->sum(fn($i) => $i->harga_satuan * $i->qty),
                ])
                ->sortByDesc('qty')
                ->values()
                ->take(50);
            $data['terlaris'] = $terlaris;
        }

        // ===== Tab: pelanggan =====
        if ($tab === 'pelanggan') {
            $perPelanggan = $notas->groupBy('pelanggan_grosir_id')
                ->map(function ($group, $key) {
                    $first = $group->first();
                    return (object) [
                        'nama' => $first->nama_pelanggan ?? 'Umum',
                        'level' => $first->labelLevelHarga(),
                        'transaksi' => $group->count(),
                        'omzet' => $group->sum('total'),
                        'piutang' => $group->sum(fn($p) => $p->sisaPiutang()),
                    ];
                })
                ->sortByDesc('omzet')
                ->values();
            $data['perPelanggan'] = $perPelanggan;
        }

        // ===== Tab: toko & gudang (per sumber stok) =====
        if (in_array($tab, ['toko', 'gudang'])) {
            $perSumber = $notas->filter(fn($p) => $p->sumberCabang)
                ->groupBy(fn($p) => $p->sumberCabang->id)
                ->map(fn($group) => (object) [
                    'nama' => $group->first()->sumberCabang->nama . ($group->first()->sumberCabang->isGudang() ? ' (Gudang)' : ' (Toko)'),
                    'transaksi' => $group->count(),
                    'qty' => $group->sum(fn($p) => $p->items->sum('qty')),
                    'omzet' => $group->sum('total'),
                    'laba' => $group->sum(fn($p) => $p->items->sum(fn($i) => ($i->harga_satuan - $i->modal_satuan) * $i->qty) - $p->diskon),
                ])
                ->sortByDesc('omzet')
                ->values();
            $data['perSumber'] = $perSumber;
        }

        // ===== Tab: piutang =====
        if ($tab === 'piutang') {
            $piutangList = $notas->filter(fn($p) => $p->piutang > 0 || $p->sisaPiutang() > 0);
            $data['piutangList'] = $piutangList->sortBy(fn($p) => $p->jatuh_tempo ?? now())->values();
        }

        // ===== Export Excel =====
        if ($request->get('export') === '1') {
            return $this->export($tab, $data, $dari, $sampai);
        }

        return view('grosir.laporan.index', array_merge([
            'tab' => $tab, 'dari' => $dari, 'sampai' => $sampai,
            'omzet' => $omzet, 'totalDiskon' => $totalDiskon, 'laba' => $laba, 'piutangSisa' => $piutangSisa,
            'jumlahTransaksi' => $notas->count(),
        ], $data));
    }

    private function export(string $tab, array $data, string $dari, string $sampai)
    {
        $w = new XlsxWriter();
        $s = $w->sheet('Laporan Grosir');

        switch ($tab) {
            case 'omzet':
                $s->widths([110, 80, 110, 90, 90]);
                $s->headerRow(['Tanggal', 'Transaksi', 'Omzet', 'Diskon', 'Piutang']);
                foreach (($data['perHari'] ?? []) as $r) {
                    $s->row([$r->tanggal, $r->transaksi, $r->omzet, $r->diskon, $r->piutang]);
                }
                break;
            case 'laba':
            case 'terlaris':
                $rows = $data['perProduk'] ?? $data['terlaris'] ?? collect();
                $s->widths([150, 90, 70, 110, 100]);
                $s->headerRow(['Produk', 'Kode', 'Qty', 'Omzet', isset($data['perProduk']) ? 'Laba' : '-']);
                foreach ($rows as $r) {
                    $s->row([$r->nama, $r->kode, $r->qty, $r->omzet, $r->laba ?? 0]);
                }
                break;
            case 'pelanggan':
                $s->widths([150, 90, 80, 110, 100]);
                $s->headerRow(['Pelanggan', 'Level', 'Transaksi', 'Omzet', 'Piutang']);
                foreach (($data['perPelanggan'] ?? []) as $r) {
                    $s->row([$r->nama, $r->level, $r->transaksi, $r->omzet, $r->piutang]);
                }
                break;
            default:
                $s->widths([100, 130, 100, 110, 110, 90]);
                $s->headerRow(['No Nota', 'Pelanggan', 'Tanggal', 'Total', 'Bayar', 'Status']);
                foreach (($data['notas'] ?? collect()) as $p) {
                    $s->row([$p->no_nota, $p->nama_pelanggan, $p->tanggal->format('Y-m-d H:i'), $p->total, $p->bayar, $p->status]);
                }
                break;
        }

        return $w->download("Laporan_Grosir_{$tab}_{$dari}_sd_{$sampai}.xlsx");
    }
}
