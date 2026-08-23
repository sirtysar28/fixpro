<?php

namespace App\Http\Controllers;

use App\Models\Servis;
use App\Models\PenjualanSparepart;
use App\Models\JualBeli;
use App\Models\Cabang;
use App\Models\Setting;
use Illuminate\Http\Request;

class ThermalPrintController extends Controller
{
    /**
     * Print struk servis ke thermal printer
     */
    public function servis(Servis $servis)
    {
        $servis->load(['pelanggan', 'teknisi', 'cabang']);
        $cabang = $servis->cabang;
        $settings = $this->getSettings($cabang);

        return view('thermal.servis', compact('servis', 'cabang', 'settings'));
    }

    /**
     * Print struk penjualan sparepart
     */
    public function penjualanSparepart(PenjualanSparepart $penjualan_sparepart)
    {
        $penjualan_sparepart->load(['stok', 'pelanggan', 'user', 'cabang']);

        // Load semua item dalam transaksi yang sama
        $siblings = collect([]);
        $allItems = collect([$penjualan_sparepart]);
        if ($penjualan_sparepart->no_transaksi) {
            $siblings = PenjualanSparepart::with('stok')
                ->where('no_transaksi', $penjualan_sparepart->no_transaksi)
                ->where('id', '!=', $penjualan_sparepart->id)
                ->get();
            $allItems = $allItems->merge($siblings);
        }

        $totalKeseluruhan = $allItems->sum('total');
        $diskon = $penjualan_sparepart->diskon ?? 0;
        $totalSetelahDiskon = $totalKeseluruhan - $diskon;
        $cabang = $penjualan_sparepart->cabang;
        $settings = $this->getSettings($cabang);

        return view('thermal.penjualan-sparepart', compact('penjualan_sparepart', 'siblings', 'allItems', 'totalKeseluruhan', 'diskon', 'totalSetelahDiskon', 'cabang', 'settings'));
    }

    /**
     * Print struk jual beli HP
     */
    public function jualBeli(JualBeli $jualBeli)
    {
        $cabang = Cabang::find(auth()->user()->getActiveCabangId());
        $settings = $this->getSettings($cabang);

        return view('thermal.jual-beli', compact('jualBeli', 'cabang', 'settings'));
    }

    private function getSettings($cabang)
    {
        $cabangId = $cabang?->id ?? 1;
        return [
            'nama_toko' => Setting::get("nama_toko_{$cabangId}") ?? Setting::get('nama_toko') ?? ($cabang?->nama ?? 'FIXPRO'),
            'alamat' => Setting::get("alamat_{$cabangId}") ?? Setting::get('alamat') ?? '',
            'telp' => Setting::get("telp_{$cabangId}") ?? Setting::get('telp') ?? '',
            'tagline' => Setting::get("tagline_{$cabangId}") ?? Setting::get('tagline') ?? 'SMARTPHONE SERVICE CENTER',
            'slogan' => Setting::get("slogan_{$cabangId}") ?? Setting::get('slogan') ?? 'Smart. Fast. Reliable.',
            'thermal_width' => 80,
        ];
    }
}
