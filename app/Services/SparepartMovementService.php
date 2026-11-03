<?php

namespace App\Services;

use App\Models\Cabang;
use App\Models\SparepartMovement;
use App\Models\Stok;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mencatat setiap pergerakan stok sparepart ke tabel
 * sparepart_movements (Kartu Stok).
 *
 * Semua controller yang mengubah stok (pembelian, penjualan, retur,
 * pembatalan, penyesuaian manual, transfer, import) WAJIB memanggil
 * SparepartMovementService::record(...) supaya histori tetap lengkap.
 */
class SparepartMovementService
{
    /**
     * Catat satu pergerakan stok.
     *
     * @param  Stok|int      $stok        Sparepart yang berubah
     * @param  string        $tipe        'masuk' atau 'keluar'
     * @param  string        $jenis       pembelian|penjualan|retur_pembelian|...
     * @param  int           $qty         jumlah (selalu positif)
     * @param  array         $opts        opsi tambahan (referensi, harga, dll)
     */
    public static function record($stok, string $tipe, string $jenis, int $qty, array $opts = []): ?SparepartMovement
    {
        if ($qty <= 0) {
            return null;
        }

        $stokId = $stok instanceof Stok ? $stok->id : (int) $stok;
        $stokModel = $stok instanceof Stok ? $stok : Stok::find($stokId);
        if (!$stokModel) {
            return null;
        }

        $cabangId = $opts['cabang_id']
            ?? $stokModel->cabang_id
            ?? Auth::user()?->getActiveCabangId();

        // FIX SQLSTATE[23000] 1452 (FK sparepart_movements_cabang_id_foreign):
        // kolom stoks.cabang_id TIDAK punya FK — bisa berisi id cabang yang sudah
        // dihapus (orphan), dan fallback `?? 1` di beberapa alur bisa menghasilkan
        // id cabang yang tidak ada. Tanpa validasi ini, INSERT movement gagal dan
        // seluruh transaksi (penjualan/pembelian/servis) ikut di-rollback.
        if ($cabangId !== null && !Cabang::whereKey((int) $cabangId)->exists()) {
            $fallback = Auth::user()?->getActiveCabangId();
            $cabangId = ($fallback !== null && Cabang::whereKey((int) $fallback)->exists())
                ? (int) $fallback
                : null; // kolom nullable — histori tetap tercatat meski cabang tak valid
            Log::warning('SparepartMovement: cabang_id tidak valid, fallback ke cabang user/null', [
                'stok_id' => $stokId, 'jenis' => $jenis,
            ]);
        }

        // Hitung saldo berjalan (stok terkini) — selalu baca fresh dari DB
        // agar akurat setelah increment/decrement di transaksi.
        $saldo = (int) Stok::where('id', $stokId)->value('stok');

        return SparepartMovement::create([
            'stok_id'        => $stokId,
            'cabang_id'      => $cabangId,
            'user_id'        => $opts['user_id'] ?? Auth::id(),
            'tipe'           => in_array($tipe, ['masuk', 'keluar']) ? $tipe : 'masuk',
            'jenis'          => $jenis,
            'qty'            => $qty,
            'saldo'          => $saldo,
            'harga_satuan'   => (float) ($opts['harga_satuan'] ?? 0),
            'referensi'      => $opts['referensi'] ?? null,
            'referensi_id'   => $opts['referensi_id'] ?? null,
            'referensi_tipe' => $opts['referensi_tipe'] ?? (($opts['referensi_model'] ?? null) ? get_class($opts['referensi_model']) : null),
            'pelaku_nama'    => $opts['pelaku_nama'] ?? null,
            'metode'         => $opts['metode'] ?? null,
            'catatan'        => $opts['catatan'] ?? null,
            'waktu'          => $opts['waktu'] ?? now(),
        ]);
    }

    /**
     * Hitung ulang seluruh saldo berjalan untuk satu sparepart.
     * Dipakai setelah edit/hapus pergerakan agar saldo konsisten.
     */
    public static function recomputeSaldo(int $stokId): void
    {
        $movs = SparepartMovement::where('stok_id', $stokId)
            ->orderBy('waktu')
            ->orderBy('id')
            ->get(['id', 'cabang_id', 'tipe', 'qty']);

        $saldoPerCabang = [];
        foreach ($movs as $m) {
            $key = $m->cabang_id ?? 0;
            if (!isset($saldoPerCabang[$key])) {
                $saldoPerCabang[$key] = 0;
            }
            if ($m->tipe === 'masuk') {
                $saldoPerCabang[$key] += $m->qty;
            } else {
                $saldoPerCabang[$key] -= $m->qty;
            }
            $m->saldo = max(0, $saldoPerCabang[$key]);
            // update langsung tanpa trigger event/observer
            DB::table('sparepart_movements')->where('id', $m->id)->update(['saldo' => $m->saldo]);
        }
    }
}
