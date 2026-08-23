<?php

namespace App\Services;

use App\Models\SyncQueue;
use App\Models\Servis;
use App\Models\Pelanggan;
use App\Models\Stok;
use App\Models\Kas;
use App\Models\PenjualanSparepart;
use App\Models\Cabang;
use App\Services\SparepartMovementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fitur #11 — Mode Offline (Offline Sync)
 *
 * Proses batch transaksi yang dibuat saat offline di mobile app.
 *
 * Idempotency: client_ref (UUID di-generate client saat offline) → UNIQUE.
 * Sinkronanisasi ulang dengan client_ref yang sama TIDAK menghasilkan duplikat,
 * melainkan mengembalikan server_id yang sudah ada sebelumnya.
 *
 * Mencegah duplikat stok/kas/servis saat koneksi putus-nyambung berulang.
 */
class OfflineSyncService
{
    /**
     * Proses satu entri sync.
     *
     * @param array $entry {
     *     client_ref:    UUID (wajib, unik),
     *     entity_type:   'servis' | 'penjualan_sparepart' | 'kas' | 'pelanggan' | ...,
     *     action:        'create' | 'update' | 'delete' (default 'create'),
     *     payload:       data lengkap transaksi,
     *     client_id:     id lokal client (untuk mapping),
     *     client_created_at: ISO timestamp saat dibuat offline,
     *     device_id:     id perangkat,
     * }
     * @return SyncQueue record sync yang sudah diproses (status=processed|failed|conflict)
     */
    public function process(array $entry, $user): SyncQueue
    {
        $clientRef = $entry['client_ref'] ?? (string) Str::uuid();
        $entityType = strtolower((string) ($entry['entity_type'] ?? ''));
        $action = strtolower((string) ($entry['action'] ?? 'create'));
        $payload = $entry['payload'] ?? [];

        // ====== IDEMPOTENCY: jika sudah pernah diproses, return existing ======
        $existing = SyncQueue::where('client_ref', $clientRef)->first();
        if ($existing) {
            return $existing; // tidak diproses ulang → anti duplikat
        }

        $cabangId = $user->getApiCabangId(request()) ?? $user->cabang_id ?? null;

        $record = SyncQueue::create([
            'user_id'           => $user->id,
            'cabang_id'         => $cabangId,
            'device_id'         => $entry['device_id'] ?? null,
            'client_ref'        => $clientRef,
            'entity_type'       => $entityType,
            'action'            => $action,
            'payload'           => $payload,
            'client_id'         => $entry['client_id'] ?? null,
            'status'            => SyncQueue::STATUS_PROCESSED,
            'client_created_at' => $entry['client_created_at'] ?? null,
            'synced_at'         => now(),
        ]);

        DB::beginTransaction();
        try {
            $serverId = match ($entityType) {
                'servis'               => $this->syncServis($payload, $user, $cabangId, $action),
                'penjualan_sparepart', 'penjualan_sp' => $this->syncPenjualanSparepart($payload, $user, $cabangId, $action),
                'kas'                  => $this->syncKas($payload, $cabangId, $action),
                'pelanggan'            => $this->syncPelanggan($payload, $cabangId, $action),
                'jualbeli', 'jual_beli' => $this->syncJualBeli($payload, $user, $cabangId, $action),
                default                => null,
            };

            $record->update([
                'server_id' => $serverId,
                'status'    => $serverId ? SyncQueue::STATUS_PROCESSED : SyncQueue::STATUS_FAILED,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $record->update([
                'status'        => SyncQueue::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
            Log::warning('OfflineSync gagal', ['client_ref' => $clientRef, 'err' => $e->getMessage()]);
        }

        return $record->fresh();
    }

    /* ============================================================
       ENTITY HANDLERS — semua pakai upsert berbasis client-side identifier
       ============================================================ */

    private function syncServis(array $p, $user, ?int $cabangId, string $action): ?int
    {
        // Update by id (kalau ada)
        if ($action === 'update' && !empty($p['id'])) {
            $s = Servis::find($p['id']);
            if ($s) {
                $s->update(array_filter([
                    'perangkat' => $p['perangkat'] ?? null,
                    'keluhan'   => $p['keluhan'] ?? null,
                    'status'    => $p['status'] ?? null,
                    'biaya'     => $p['biaya'] ?? null,
                    'catatan'   => $p['catatan'] ?? null,
                    'teknisi_id'=> $p['teknisi_id'] ?? null,
                ], fn($v) => $v !== null));
                return $s->id;
            }
        }

        // Create — cek duplikat by kode dulu
        $kode = $p['kode'] ?? null;
        if ($kode) {
            $exist = Servis::where('kode', $kode)->first();
            if ($exist) return $exist->id;
        }

        $s = Servis::create([
            'kode'          => $kode ?? $this->genServisKode(),
            'pelanggan_id'  => $p['pelanggan_id'] ?? null,
            'cabang_id'     => $cabangId,
            'sumber'        => 'mobile',
            'perangkat'     => $p['perangkat'] ?? 'Unknown',
            'keluhan'       => $p['keluhan'] ?? '',
            'tipe'          => $p['tipe'] ?? 'Android',
            'status'        => $p['status'] ?? 'Masuk',
            'biaya'         => $p['biaya'] ?? 0,
            'dp'            => $p['dp'] ?? 0,
            'tanggal'       => $p['tanggal'] ?? now()->format('Y-m-d'),
            'teknisi_id'    => $p['teknisi_id'] ?? null,
            'prioritas'     => $p['prioritas'] ?? 'Normal',
            'imei'          => $p['imei'] ?? null,
            'catatan'       => $p['catatan'] ?? null,
            'garansi'       => $p['garansi'] ?? 0,
        ]);
        return $s->id;
    }

    private function syncPenjualanSparepart(array $p, $user, ?int $cabangId, string $action): ?int
    {
        $kode = $p['kode'] ?? null;
        if ($kode) {
            $exist = PenjualanSparepart::where('kode', $kode)->first();
            if ($exist) return $exist->id;
        }

        $stokId = $p['stok_id'] ?? null;
        $qty    = (int) ($p['qty'] ?? 0);
        $stok   = $stokId ? Stok::find($stokId) : null;

        if ($stok && $qty > 0 && $stok->stok < $qty) {
            // Konflik stok — kembalikan null supaya sync failure tercatat
            throw new \RuntimeException("Stok {$stok->nama} tidak cukup (sisa {$stok->stok}). Transaksi offline dibatalkan, periksa stok terbaru.");
        }

        $harga = (float) ($p['harga_satuan'] ?? ($stok?->jual ?? 0));
        $total = $harga * $qty;
        $penj = PenjualanSparepart::create([
            'stok_id'       => $stokId,
            'pelanggan_id'  => $p['pelanggan_id'] ?? null,
            'cabang_id'     => $cabangId,
            'user_id'       => $user->id,
            'kode'          => $kode ?? PenjualanSparepart::generateKode(),
            'no_transaksi'  => $p['no_transaksi'] ?? PenjualanSparepart::generateNoTransaksi(),
            'qty'           => $qty,
            'harga_satuan'  => $harga,
            'total'         => $total,
            'modal_total'   => $stok ? ($qty * (float) $stok->modal) : 0,
            'metode_bayar'  => $p['metode_bayar'] ?? 'Cash',
            'catatan'       => $p['catatan'] ?? 'Sync offline',
            'tanggal'       => $p['tanggal'] ?? now()->format('Y-m-d'),
            'status'        => $p['status'] ?? 'Sukses',
        ]);

        if ($stok) $stok->decrement('stok', $qty);

        // Catat pergerakan stok (Kartu Stok) — dari sync offline
        if ($stok) {
            SparepartMovementService::record($stok, 'keluar', 'penjualan', $qty, [
                'referensi'       => $penj->no_transaksi ?: $penj->kode,
                'referensi_id'    => $penj->id,
                'referensi_model' => $penj,
                'harga_satuan'    => $harga,
                'metode'          => $p['metode_bayar'] ?? 'Cash',
                'cabang_id'       => $cabangId,
                'catatan'         => 'Sinkronisasi transaksi offline',
            ]);
        }

        // Catat kas otomatis
        $this->recordKas($cabangId, 'masuk', $total, $p['metode_bayar'] ?? 'Cash', $penj->kode, "Penjualan sparepart {$penj->kode} (offline sync)");

        return $penj->id;
    }

    private function syncKas(array $p, ?int $cabangId, string $action): ?int
    {
        $ref = $p['ref'] ?? null;
        if ($ref) {
            $exist = Kas::where('ref', $ref)->first();
            if ($exist) return $exist->id;
        }

        $tipe = $p['tipe'] ?? 'masuk';
        $jml  = (float) ($p['jml'] ?? 0);
        if ($jml <= 0) return null;

        $lastKas = Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
        $lastSaldo = $lastKas ? (float) $lastKas->saldo : 0;
        $newSaldo = $tipe === 'masuk' ? $lastSaldo + $jml : $lastSaldo - $jml;

        $kas = Kas::create([
            'tipe'      => $tipe,
            'cabang_id' => $cabangId,
            'jml'       => $jml,
            'kategori'  => $p['kategori'] ?? 'Lain-lain',
            'ket'       => $p['ket'] ?? '(offline sync)',
            'ref'       => $ref,
            'metode'    => $p['metode'] ?? 'Cash',
            'waktu'     => $p['waktu'] ?? now(),
            'saldo'     => $newSaldo,
        ]);
        return $kas->id;
    }

    private function syncPelanggan(array $p, ?int $cabangId, string $action): ?int
    {
        if (!empty($p['id'])) {
            $exist = Pelanggan::find($p['id']);
            if ($exist) {
                $exist->update(array_filter([
                    'nama'   => $p['nama'] ?? null,
                    'no_hp'  => $p['no_hp'] ?? null,
                    'alamat' => $p['alamat'] ?? null,
                ], fn($v) => $v !== null));
                return $exist->id;
            }
        }
        // Dedup by no_hp di cabang ini
        if (!empty($p['no_hp'])) {
            $exist = Pelanggan::where('no_hp', $p['no_hp'])->where('cabang_id', $cabangId)->first();
            if ($exist) return $exist->id;
        }
        $pg = Pelanggan::create([
            'nama'      => $p['nama'] ?? 'Pelanggan',
            'no_hp'     => $p['no_hp'] ?? null,
            'alamat'    => $p['alamat'] ?? null,
            'cabang_id' => $cabangId,
        ]);
        return $pg->id;
    }

    private function syncJualBeli(array $p, $user, ?int $cabangId, string $action): ?int
    {
        $model = \App\Models\JualBeli::class;
        $kode = $p['kode'] ?? null;
        if ($kode) {
            $exist = $model::where('kode', $kode)->first();
            if ($exist) return $exist->id;
        }
        // Tentukan harga sesuai tipe (jual/beli) agar konsisten dengan skema
        $tipe = strtolower((string) ($p['tipe'] ?? 'beli'));
        $tipeEnum = in_array($tipe, ['jual', 'beli']) ? $tipe : 'beli';

        $hargaBeli = isset($p['harga_beli']) ? (float) $p['harga_beli'] : null;
        $hargaJual = isset($p['harga_jual']) ? (float) $p['harga_jual'] : null;
        // Fallback harga tunggal (kompatibilitas client lama)
        $hargaUtama = isset($p['harga']) ? (float) $p['harga'] : 0;
        if ($hargaBeli === null && $tipeEnum === 'beli') $hargaBeli = $hargaUtama;
        if ($hargaJual === null && $tipeEnum === 'jual') $hargaJual = $hargaUtama;
        $hargaKolom = $tipeEnum === 'jual' ? ($hargaJual ?: $hargaUtama) : ($hargaBeli ?: $hargaUtama);

        $metode = $p['metode_bayar'] ?? $p['metode'] ?? 'Cash';
        $metodeBayar = in_array($metode, ['Cash', 'Transfer', 'QRIS']) ? $metode : 'Cash';

        $jb = $model::create([
            'cabang_id'     => $cabangId,
            'user_id'       => $user->id,
            'kode'          => $kode ?? 'JB-' . now()->format('ymd') . '-' . str_pad((string) rand(0, 999), 3, '0', STR_PAD_LEFT),
            'tanggal'       => $p['tanggal'] ?? now()->format('Y-m-d'),
            'tipe'          => $tipeEnum,
            'hp'            => $p['hp'] ?? $p['nama_hp'] ?? 'HP',
            'imei'          => $p['imei'] ?? null,
            'imei2'         => $p['imei2'] ?? null,
            'serial_number' => $p['serial_number'] ?? null,
            'merk'          => $p['merk'] ?? null,
            'model'         => $p['model'] ?? null,
            'warna'         => $p['warna'] ?? null,
            'ram'           => $p['ram'] ?? null,
            'kapasitas'     => $p['kapasitas'] ?? null,
            'battery_health'=> $p['battery_health'] ?? null,
            'harga'         => $hargaKolom,
            'harga_beli'    => $hargaBeli,
            'harga_jual'    => $hargaJual,
            'modal_total'   => $p['modal_total'] ?? $hargaBeli,
            'metode_bayar'  => $metodeBayar,
            'pelanggan'     => $p['pelanggan'] ?? null,
            'no_hp_pelanggan' => $p['no_hp_pelanggan'] ?? null,
            'kondisi'       => $p['kondisi'] ?? 'Second',
            'kelengkapan'   => $p['kelengkapan'] ?? null,
            'catatan'       => $p['catatan'] ?? '(offline sync)',
            'status'        => $p['status'] ?? 'Selesai',
            'status_unit'   => $p['status_unit'] ?? ($tipeEnum === 'jual' ? 'Terjual' : 'Ready Dijual'),
            'garansi'       => $p['garansi'] ?? 'Tanpa Garansi',
            'riwayat_harga' => [[
                'tanggal' => now()->toDateTimeString(),
                'harga_beli' => $hargaBeli ?? 0,
                'harga_jual' => $hargaJual ?? 0,
                'keterangan' => 'Sync offline',
                'user_id' => $user->id,
            ]],
        ]);

        // Catat kas otomatis (sinkron dengan JualBeliController)
        $this->recordKas(
            $cabangId,
            $tipeEnum === 'jual' ? 'masuk' : 'keluar',
            $hargaKolom,
            $metodeBayar,
            $jb->kode,
            ($tipeEnum === 'jual' ? 'Jual' : 'Beli') . ' HP Second (offline sync): ' . $jb->hp
        );

        return $jb->id;
    }

    /* ============================================================
       INTERNAL HELPERS
       ============================================================ */

    private function recordKas(?int $cabangId, string $tipe, float $jml, string $metode, string $ref, string $ket): void
    {
        $lastKas = Kas::where('cabang_id', $cabangId)->orderBy('waktu', 'desc')->first();
        $lastSaldo = $lastKas ? (float) $lastKas->saldo : 0;
        $newSaldo = $tipe === 'masuk' ? $lastSaldo + $jml : $lastSaldo - $jml;
        Kas::create([
            'tipe'      => $tipe,
            'cabang_id' => $cabangId,
            'jml'       => $jml,
            'kategori'  => $tipe === 'masuk' ? 'Penjualan Sparepart' : 'Pengeluaran',
            'ket'       => $ket,
            'ref'       => $ref,
            'metode'    => $metode,
            'waktu'     => now(),
            'saldo'     => $newSaldo,
        ]);
    }

    private function genServisKode(): string
    {
        $date = now()->format('ymd');
        $last = Servis::where('kode', 'like', "SVC-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->kode, -3) + 1 : 1;
        return "SVC-$date-" . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
    }
}
