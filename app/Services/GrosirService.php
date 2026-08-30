<?php

namespace App\Services;

use App\Models\Cabang;
use App\Models\HargaGrosir;
use App\Models\HargaKhusus;
use App\Models\Kas;
use App\Models\PelangganGrosir;
use App\Models\Stok;
use App\Models\User;

/**
 * Service inti Modul Penjualan Grosir.
 *
 * Aturan penting:
 * - DATA GROSIR SELALU TERPISAH PER TOKO (cabang). Semua query WAJIB difilter
 *   cabang aktif user supaya stok/pencarian tidak campur dengan toko lain.
 * - Harga diambil dengan prioritas: Harga Khusus Pelanggan > Harga Level (Grosir1-3/
 *   Reseller/Distributor) > Harga Eceran (stok.jual).
 */
class GrosirService
{
    /** Daftar level harga grosir (key → label) */
    public const LEVELS = [
        'eceran' => 'Harga Eceran',
        'retail' => 'Harga Retail',
        'grosir1' => 'Grosir 1',
        'grosir2' => 'Grosir 2',
        'grosir3' => 'Grosir 3',
        'reseller' => 'Reseller',
        'member' => 'Member',
        'distributor' => 'Distributor',
    ];

    /**
     * Gate wajib cabang aktif. Return cabang_id (int) jika boleh lanjut,
     * atau string "pilih_cabang" bila user sedang mode "Semua Cabang".
     */
    public static function wajibCabang(): int|string
    {
        $cabangId = auth()->user()->getActiveCabangId();
        if ($cabangId === null) {
            return 'pilih_cabang';
        }
        return (int) $cabangId;
    }

    /**
     * Guard kepemilikan data per cabang. Abort 403 bila data bukan milik cabang/grup user.
     * Super Admin bebas; Admin Cabang Anak terkunci ke cabangnya; Enterprise boleh grupnya.
     */
    public static function assertAksesCabang(?int $dataCabangId, ?User $user = null): void
    {
        $user = $user ?? auth()->user();
        if ($user->isSuperAdmin()) return;

        if ($user->isAdminCabangAnak()) {
            if ((int) ($dataCabangId ?? 0) !== (int) $user->cabang_id) {
                abort(403, 'Data ini bukan milik cabang Anda.');
            }
            return;
        }

        if ($user->isEnterprise() && $user->isAdmin()) {
            $allowed = $user->getAllowedCabangIds();
            if (in_array((int) $dataCabangId, $allowed, true)) return;
            abort(403, 'Data ini bukan milik grup cabang Anda.');
        }

        if ((int) ($dataCabangId ?? 0) !== (int) $user->getActiveCabangId()) {
            abort(403, 'Data ini bukan milik cabang Anda.');
        }
    }

    /**
     * Daftar cabang gudang yang boleh dipakai user sebagai sumber stok grosir.
     * (Gudang milik grup sendiri — cabang pusat + anak / cabang sendiri saja).
     */
    public static function gudangOptions(User $user): array
    {
        $query = Cabang::where('aktif', true)->where('tipe', 'gudang');
        if (!$user->isSuperAdmin()) {
            $ids = $user->getAllowedCabangIds();
            $query->whereIn('id', $ids);
        }
        return $query->orderBy('nama')->get(['id', 'nama'])->toArray();
    }

    /**
     * Resolve harga jual untuk sebuah produk berdasar level & pelanggan.
     * Prioritas: 1) Harga khusus pelanggan  2) Harga level di tabel harga_grosirs
     *            3) Fallback harga eceran (stok.jual)
     *
     * Return: ['harga' => float, 'sumber' => 'khusus'|'level'|'eceran', 'level' => string]
     */
    public static function resolveHarga(Stok $stok, ?PelangganGrosir $pelanggan, string $level): array
    {
        // 1. Harga khusus pelanggan (prioritas tertinggi)
        if ($pelanggan) {
            $khusus = HargaKhusus::where('pelanggan_grosir_id', $pelanggan->id)
                ->where('stok_id', $stok->id)
                ->value('harga');
            if ($khusus !== null && (float) $khusus > 0) {
                return ['harga' => (float) $khusus, 'sumber' => 'khusus', 'level' => $level];
            }
        }

        // 2. Harga level dari tabel harga grosir (per stok + cabang stok tsb)
        if ($level !== 'eceran') {
            $hg = HargaGrosir::where('stok_id', $stok->id)
                ->where('cabang_id', $stok->cabang_id)
                ->where('aktif', true)
                ->first();
            if ($hg) {
                $harga = $hg->hargaUntukLevel($level);
                if ($harga !== null && $harga > 0) {
                    return ['harga' => $harga, 'sumber' => 'level', 'level' => $level];
                }
            }
        }

        // 3. Fallback: harga eceran
        return ['harga' => (float) $stok->jual, 'sumber' => 'eceran', 'level' => $level];
    }

    /**
     * Rekap harga semua level untuk sebuah produk (untuk POS & tabel harga).
     */
    public static function rekapHarga(Stok $stok, ?PelangganGrosir $pelanggan = null): array
    {
        $hg = HargaGrosir::where('stok_id', $stok->id)->where('cabang_id', $stok->cabang_id)->first();
        $khusus = null;
        if ($pelanggan) {
            $khusus = HargaKhusus::where('pelanggan_grosir_id', $pelanggan->id)
                ->where('stok_id', $stok->id)->value('harga');
        }
        return [
            'eceran' => (float) $stok->jual,
            'grosir1' => $hg?->hargaUntukLevel('grosir1'),
            'grosir2' => $hg?->hargaUntukLevel('grosir2'),
            'grosir3' => $hg?->hargaUntukLevel('grosir3'),
            'reseller' => $hg?->hargaUntukLevel('reseller'),
            'distributor' => $hg?->hargaUntukLevel('distributor'),
            'khusus' => $khusus !== null ? (float) $khusus : null,
        ];
    }

    /** Catat kas masuk (pembayaran grosir / pelunasan piutang) */
    public static function kasMasuk(int $cabangId, float $jml, string $ket, string $metode, string $ref): void
    {
        if ($jml <= 0) return;
        $lastKas = Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
        $newSaldo = ($lastKas ? $lastKas->saldo : 0) + $jml;
        Kas::create([
            'tipe' => 'masuk',
            'cabang_id' => $cabangId,
            'jml' => $jml,
            'kategori' => 'Penjualan Grosir',
            'ket' => $ket,
            'metode' => $metode,
            'ref' => $ref,
            'waktu' => now(),
            'saldo' => $newSaldo,
        ]);
    }

    /** Catat kas keluar (retur uang kembali, dll) */
    public static function kasKeluar(int $cabangId, float $jml, string $ket, string $metode, string $ref): void
    {
        if ($jml <= 0) return;
        $lastKas = Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
        $newSaldo = ($lastKas ? $lastKas->saldo : 0) - $jml;
        Kas::create([
            'tipe' => 'keluar',
            'cabang_id' => $cabangId,
            'jml' => $jml,
            'kategori' => 'Retur Grosir',
            'ket' => $ket,
            'metode' => $metode,
            'ref' => $ref,
            'waktu' => now(),
            'saldo' => $newSaldo,
        ]);
    }

    /** Cek limit piutang pelanggan (return true = masih boleh berpiutang) */
    public static function bolehPiutang(?PelangganGrosir $pelanggan, float $piutangBaru): bool
    {
        if (!$pelanggan) return true;
        $limit = (float) $pelanggan->limit_piutang;
        if ($limit <= 0) return true; // tanpa limit
        $piutangAktif = (float) \App\Models\PenjualanGrosir::where('pelanggan_grosir_id', $pelanggan->id)
            ->whereIn('status', ['Piutang', 'Sebagian'])
            ->get()
            ->sum(fn($p) => $p->sisaPiutang());
        return ($piutangAktif + $piutangBaru) <= $limit;
    }
}
