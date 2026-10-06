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
    public function servis(Servis $servis)
    {
        $servis->load(['pelanggan', 'teknisi', 'cabang']);
        $cabang = $servis->cabang;
        $settings = $this->getSettings($cabang);

        return view('thermal.servis', compact('servis', 'cabang', 'settings'));
    }

    public function penjualanSparepart(PenjualanSparepart $penjualan_sparepart)
    {
        $penjualan_sparepart->load(['stok', 'pelanggan', 'user', 'cabang']);

        // 1. Ambil SEMUA item dengan no_transaksi yang sama
        if ($penjualan_sparepart->no_transaksi) {
            $allItems = PenjualanSparepart::with('stok')
                ->where('no_transaksi', $penjualan_sparepart->no_transaksi)
                ->get()
                ->unique('id'); // KUNCI: Hapus duplikat berdasarkan ID
        } else {
            $allItems = collect([$penjualan_sparepart]);
        }

        // 2. Hitung total keseluruhan dari seluruh item
        $totalKeseluruhan = $allItems->sum('total');
        
        // 3. KUNCI ANTI-DOBEL: Ambil diskon HANYA dari item pertama (Null-safe)
        $diskon = $allItems->first()?->diskon ?? 0;
        
        $totalSetelahDiskon = $totalKeseluruhan - $diskon;
        
        $cabang = $penjualan_sparepart->cabang;
        $settings = $this->getSettings($cabang);

        return view('thermal.penjualan-sparepart', compact(
            'penjualan_sparepart', 
            'allItems', 
            'totalKeseluruhan', 
            'diskon', 
            'totalSetelahDiskon', 
            'cabang', 
            'settings'
        ));
    }

    public function jualBeli(JualBeli $jualBeli)
    {
        $cabang = Cabang::find(auth()->user()->getActiveCabangId());
        $settings = $this->getSettings($cabang);

        return view('thermal.jual-beli', compact('jualBeli', 'cabang', 'settings'));
    }

    private function getSettings($cabang)
    {
        $cabangId = $cabang?->id ?? 1;

        $paperWidth = (int) (Setting::get('thermal_width') ?? 58);
        if (!in_array($paperWidth, [58, 80])) {
            $paperWidth = 58;
        }

        $receiptWidth = $paperWidth === 80 ? 72 : 48;

        return [
            'nama_toko' => Setting::get("nama_toko_{$cabangId}") ?? Setting::get('nama_toko') ?? ($cabang?->nama ?? 'FIXPRO'),
            'alamat' => Setting::get("alamat_{$cabangId}") ?? Setting::get('alamat') ?? '',
            'telp' => Setting::get("telp_{$cabangId}") ?? Setting::get('telp') ?? '',
            'tagline' => Setting::get("tagline_{$cabangId}") ?? Setting::get('tagline') ?? 'SMARTPHONE SERVICE CENTER',
            'slogan' => Setting::get("slogan_{$cabangId}") ?? Setting::get('slogan') ?? 'Smart. Fast. Reliable.',
            'thermal_width' => $paperWidth,
            'receipt_width' => $receiptWidth,
        ];
    }
}