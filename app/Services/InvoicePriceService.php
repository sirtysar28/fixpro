<?php

namespace App\Services;

use App\Models\HargaGrosir;
use App\Models\HargaKhusus;
use App\Models\PelangganGrosir;
use App\Models\Stok;

/**
 * InvoicePriceService — Penentuan harga otomatis Invoice Sparepart FIXPRO.
 *
 * Prioritas harga:
 * 1. Harga khusus pelanggan (jika ada)          → 'khusus'
 * 2. Tipe pelanggan Reseller / Member           → 'reseller' / 'member'
 * 3. Harga berdasarkan Qty (Grosir 1/2/3)       → tier sesuai min qty
 * 4. Harga retail (stok.jual)                   → 'retail'
 *
 * Min qty per produk dikonfigurasi admin di master Harga Grosir.
 */
class InvoicePriceService
{
    /**
     * Hitung harga untuk satu produk.
     *
     * @param Stok $stok           produk (sudah termasuk relasi hargaGrosir jika ada)
     * @param int  $qty            jumlah pembelian
     * @param PelangganGrosir|null $pelanggan
     * @param int|null $cabangId   cabang untuk lookup harga grosir (default cabang stok)
     * @return array{harga: float, jenis: string, tiers: array}
     */
    public static function resolve(Stok $stok, int $qty, ?PelangganGrosir $pelanggan = null, ?int $cabangId = null): array
    {
        $cabangId = $cabangId ?? (int) ($stok->cabang_id ?? 0) ?: null;
        $retail = (float) $stok->jual;

        $hg = HargaGrosir::where('stok_id', $stok->id)
            ->where(function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId)->orWhereNull('cabang_id');
            })
            ->where('aktif', true)
            ->orderByDesc('cabang_id')
            ->first();

        $tiers = [
            'retail'   => $retail,
            'grosir1'  => $hg ? (float) ($hg->harga_grosir1 ?: 0) : 0,
            'grosir2'  => $hg ? (float) ($hg->harga_grosir2 ?: 0) : 0,
            'grosir3'  => $hg ? (float) ($hg->harga_grosir3 ?: 0) : 0,
            'reseller' => $hg ? (float) ($hg->harga_reseller ?: 0) : 0,
            'member'   => $hg ? (float) ($hg->harga_member ?: 0) : 0,
        ];

        // 1. Harga khusus pelanggan — prioritas tertinggi
        if ($pelanggan) {
            $khusus = HargaKhusus::where('pelanggan_grosir_id', $pelanggan->id)
                ->where('stok_id', $stok->id)
                ->first();
            if ($khusus && (float) $khusus->harga > 0) {
                $tiers['khusus'] = (float) $khusus->harga;
                return ['harga' => (float) $khusus->harga, 'jenis' => 'khusus', 'tiers' => $tiers];
            }
        }

        // 2. Tipe pelanggan Reseller / Member
        if ($pelanggan) {
            $tipe = $pelanggan->tipe;
            if ($tipe === 'Reseller' && $tiers['reseller'] > 0) {
                return ['harga' => $tiers['reseller'], 'jenis' => 'reseller', 'tiers' => $tiers];
            }
            if ($tipe === 'Member' && $tiers['member'] > 0) {
                return ['harga' => $tiers['member'], 'jenis' => 'member', 'tiers' => $tiers];
            }
            if ($tipe === 'Distributor' && $tiers['reseller'] > 0) {
                // Distributor pakai harga reseller bila tidak ada harga khusus
                return ['harga' => $tiers['reseller'], 'jenis' => 'reseller', 'tiers' => $tiers];
            }
        }

        // 3. Harga otomatis berdasarkan qty: 1-4 Retail, 5-9 Grosir 1, 10-19 Grosir 2, >=20 Grosir 3
        //    (min qty per produk dikonfigurasi admin)
        if ($hg) {
            if ($qty >= (int) $hg->min_qty_grosir3 && $tiers['grosir3'] > 0) {
                return ['harga' => $tiers['grosir3'], 'jenis' => 'grosir3', 'tiers' => $tiers];
            }
            if ($qty >= (int) $hg->min_qty_grosir2 && $tiers['grosir2'] > 0) {
                return ['harga' => $tiers['grosir2'], 'jenis' => 'grosir2', 'tiers' => $tiers];
            }
            if ($qty >= (int) $hg->min_qty_grosir1 && $tiers['grosir1'] > 0) {
                return ['harga' => $tiers['grosir1'], 'jenis' => 'grosir1', 'tiers' => $tiers];
            }
        }

        // 4. Retail
        return ['harga' => $retail, 'jenis' => 'retail', 'tiers' => $tiers];
    }
}
