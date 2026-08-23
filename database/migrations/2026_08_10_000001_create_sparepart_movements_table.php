<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel aktivitas sparepart (Kartu Stok).
 *
 * Menyimpan seluruh pergerakan stok sparepart: pembelian (masuk),
 * penjualan (keluar), retur, pembatalan, penyesuaian manual, transfer
 * antar cabang, stok awal, dan import. Dengan tabel ini, riwayat beli
 * & jual setiap sparepart bisa dilihat dalam satu timeline tanpa cek
 * manual satu per satu (mirip fitur "Kartu Stok" Erzap).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Migration sebelumnya gagal di tahap backfill dan bisa meninggalkan
        // tabel parsial tanpa kolom deleted_at. Karena migration ini belum
        // pernah sukses (tidak tercatat di tabel migrations), aman untuk
        // dibangun ulang dari nol.
        Schema::dropIfExists('sparepart_movements');

        Schema::create('sparepart_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stok_id')->nullable()->constrained('stoks')->cascadeOnDelete();
                $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

                // 'masuk' = stok bertambah, 'keluar' = stok berkurang
                $table->enum('tipe', ['masuk', 'keluar'])->default('masuk');

                // Sumber pergerakan: pembelian, penjualan, retur_pembelian,
                // batal_penjualan, batal_pembelian, adjustment_naik,
                // adjustment_turun, transfer_masuk, transfer_keluar,
                // stok_awal, import, edit_stok
                $table->string('jenis', 40)->default('stok_awal');

                $table->unsignedInteger('qty')->default(0);        // selalu positif
                $table->decimal('saldo', 12, 0)->default(0);       // sisa stok setelah pergerakan
                $table->decimal('harga_satuan', 15, 2)->default(0);// harga beli/jual saat pergerakan

                // Referensi ke transaksi asal (kode + id + model)
                $table->string('referensi')->nullable();           // kode transaksi (PMB-/TRX-/dst)
                $table->unsignedBigInteger('referensi_id')->nullable();
                $table->string('referensi_tipe')->nullable();      // nama model sumber

                // Pihak lawan (untuk info cepat di kartu stok)
                $table->string('pelaku_nama')->nullable();         // nama supplier / pelanggan
                $table->string('metode')->nullable();              // Cash/Transfer/QRIS

                $table->text('catatan')->nullable();
                $table->datetime('waktu');                         // waktu kejadian pergerakan
                $table->timestamps();
                $table->softDeletes();                             // untuk SoftDeletes di model

                $table->index(['stok_id', 'waktu']);
                $table->index(['cabang_id', 'waktu']);
                $table->index('jenis');
                $table->index('tipe');
            });

        // ===== BACKFILL: isi data historis dari tabel yang sudah ada =====
        // Rekonstruksi pergerakan dari pembelians, penjualan_sparepart,
        // dan stock_transfers agar kartu stok lengkap sejak awal.
        $this->backfill();
    }

    private function backfill(): void
    {
        // Hindari backfill ganda
        if (DB::table('sparepart_movements')->exists()) {
            return;
        }

        $rows = [];
        $now = now();

        // 1) Pembelian supplier → stok masuk
        foreach (DB::table('pembelians')->where('status', '!=', 'Dibatalkan')->orderBy('tanggal')->cursor() as $pmb) {
            $items = is_string($pmb->items) ? json_decode($pmb->items, true) : ($pmb->items ?? []);
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $it) {
                $stokId = $it['stok_id'] ?? null;
                if (!$stokId) {
                    continue;
                }
                $qty = (int) ($it['qty'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $rows[] = [
                    'stok_id'        => $stokId,
                    'cabang_id'      => $pmb->cabang_id,
                    'user_id'        => $pmb->user_id,
                    'tipe'           => 'masuk',
                    'jenis'          => 'pembelian',
                    'qty'            => $qty,
                    'saldo'          => 0, // dihitung ulang nanti
                    'harga_satuan'   => (float) ($it['harga_beli'] ?? 0),
                    'referensi'      => $pmb->kode,
                    'referensi_id'   => $pmb->id,
                    'referensi_tipe' => 'App\\Models\\Pembelian',
                    'pelaku_nama'    => $pmb->supplier_nama,
                    'metode'         => $pmb->metode_bayar,
                    'catatan'        => 'Backfill dari data pembelian',
                    'waktu'          => $pmb->created_at ?? $pmb->tanggal,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }

        // 2) Penjualan sparepart → stok keluar (kecuali yang dibatalkan)
        foreach (DB::table('penjualan_sparepart')
            ->where(function ($q) { $q->where('status', '!=', 'Dibatalkan')->orWhereNull('status'); })
            ->orderBy('tanggal')->cursor() as $pnj) {
            $qty = (int) $pnj->qty;
            if ($qty <= 0) {
                continue;
            }
            $rows[] = [
                'stok_id'        => $pnj->stok_id,
                'cabang_id'      => $pnj->cabang_id,
                'user_id'        => $pnj->user_id,
                'tipe'           => 'keluar',
                'jenis'          => 'penjualan',
                'qty'            => $qty,
                'saldo'          => 0,
                'harga_satuan'   => (float) $pnj->harga_satuan,
                'referensi'      => $pnj->no_transaksi ?: $pnj->kode,
                'referensi_id'   => $pnj->id,
                'referensi_tipe' => 'App\\Models\\PenjualanSparepart',
                'pelaku_nama'    => null,
                'metode'         => $pnj->metode_bayar,
                'catatan'        => 'Backfill dari data penjualan',
                'waktu'          => $pnj->created_at ?? $pnj->tanggal,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        // 3) Transfer stok antar cabang
        //    stock_transfers.stok_id = stok di cabang ASAL. Untuk transfer_masuk,
        //    cari stok di cabang tujuan berdasarkan kode; kalau tidak ada, lewati.
        if (Schema::hasTable('stock_transfers')) {
            foreach (DB::table('stock_transfers')->orderBy('created_at')->cursor() as $tr) {
                $stokAsal = DB::table('stoks')->where('id', $tr->stok_id)->first();
                if (!$stokAsal) {
                    continue;
                }
                // Keluar dari cabang asal
                $rows[] = [
                    'stok_id'        => $tr->stok_id,
                    'cabang_id'      => $tr->from_cabang_id,
                    'user_id'        => $tr->user_id,
                    'tipe'           => 'keluar',
                    'jenis'          => 'transfer_keluar',
                    'qty'            => (int) $tr->qty,
                    'saldo'          => 0,
                    'harga_satuan'   => (float) $tr->harga_satuan,
                    'referensi'      => $tr->kode,
                    'referensi_id'   => $tr->id,
                    'referensi_tipe' => 'App\\Models\\StockTransfer',
                    'pelaku_nama'    => 'Transfer keluar',
                    'metode'         => null,
                    'catatan'        => 'Backfill transfer',
                    'waktu'          => $tr->created_at,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
                // Masuk di cabang tujuan (cari stok berdasarkan kode)
                $stokTujuan = DB::table('stoks')
                    ->where('kode', $stokAsal->kode)
                    ->where('cabang_id', $tr->to_cabang_id)
                    ->first();
                if ($stokTujuan) {
                    $rows[] = [
                        'stok_id'        => $stokTujuan->id,
                        'cabang_id'      => $tr->to_cabang_id,
                        'user_id'        => $tr->user_id,
                        'tipe'           => 'masuk',
                        'jenis'          => 'transfer_masuk',
                        'qty'            => (int) $tr->qty,
                        'saldo'          => 0,
                        'harga_satuan'   => (float) $tr->harga_satuan,
                        'referensi'      => $tr->kode,
                        'referensi_id'   => $tr->id,
                        'referensi_tipe' => 'App\\Models\\StockTransfer',
                        'pelaku_nama'    => 'Transfer masuk',
                        'metode'         => null,
                        'catatan'        => 'Backfill transfer',
                        'waktu'          => $tr->created_at,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }
            }
        }

        // Insert bertahap agar tidak kehabisan memori pada data besar
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('sparepart_movements')->insert($chunk);
        }

        // 4) Reconcile: tambah "saldo awal" agar running balance cocok dengan
        //    stok aktual saat ini. Selisih = stok_sekarang - (masuk - keluar) dari
        //    data backfill. Selisih dicatat sebagai movement 'stok_awal'.
        $this->reconcileOpeningBalance();

        // Hitung saldo berjalan (running balance) per stok.
        $this->recomputeSaldo();
    }

    /**
     * Tambah movement rekonsiliasi agar total pergerakan menyamai stok aktual.
     * - opening > 0 : dicatat sebagai 'stok_awal' (masuk) di awal timeline.
     * - opening < 0 : selisih akibat outflow tak tercatat (manual edit/delete
     *   sebelum fitur ini) → dicatat sebagai 'penyesuaian' (keluar) di akhir
     *   timeline agar tidak menampilkan saldo negatif di tengah.
     */
    private function reconcileOpeningBalance(): void
    {
        $now = now();
        foreach (DB::table('stoks')->orderBy('id')->cursor() as $stok) {
            $actual = (int) $stok->stok;
            $agg = DB::table('sparepart_movements')
                ->where('stok_id', $stok->id)
                ->selectRaw("COALESCE(SUM(CASE WHEN tipe='masuk' THEN qty ELSE 0 END),0) AS masuk")
                ->selectRaw("COALESCE(SUM(CASE WHEN tipe='keluar' THEN qty ELSE 0 END),0) AS keluar")
                ->first();
            // opening + masuk - keluar = actual  =>  opening = actual - masuk + keluar
            $opening = $actual - (int) $agg->masuk + (int) $agg->keluar;
            if ($opening == 0) {
                continue;
            }

            if ($opening > 0) {
                // Saldo awal di awal timeline
                $firstWaktu = DB::table('sparepart_movements')
                    ->where('stok_id', $stok->id)->min('waktu');
                $waktu = $firstWaktu
                    ? \Carbon\Carbon::parse($firstWaktu)->subSecond()
                    : ($stok->created_at ?? $now);
                DB::table('sparepart_movements')->insert([
                    'stok_id'        => $stok->id,
                    'cabang_id'      => $stok->cabang_id,
                    'user_id'        => null,
                    'tipe'           => 'masuk',
                    'jenis'          => 'stok_awal',
                    'qty'            => $opening,
                    'saldo'          => 0,
                    'harga_satuan'   => (float) $stok->modal,
                    'referensi'      => 'STOK-AWAL-' . $stok->kode,
                    'pelaku_nama'    => 'Saldo Awal',
                    'metode'         => null,
                    'catatan'        => 'Saldo awal hasil rekonsiliasi data historis',
                    'waktu'          => $waktu,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            } else {
                // Penyesuaian di akhir timeline (outflow tak tercatat)
                $lastWaktu = DB::table('sparepart_movements')
                    ->where('stok_id', $stok->id)->max('waktu');
                $waktu = $lastWaktu
                    ? \Carbon\Carbon::parse($lastWaktu)->addSecond()
                    : ($stok->created_at ?? $now);
                DB::table('sparepart_movements')->insert([
                    'stok_id'        => $stok->id,
                    'cabang_id'      => $stok->cabang_id,
                    'user_id'        => null,
                    'tipe'           => 'keluar',
                    'jenis'          => 'adjustment_turun',
                    'qty'            => abs($opening),
                    'saldo'          => 0,
                    'harga_satuan'   => (float) $stok->modal,
                    'referensi'      => 'REKON-' . $stok->kode,
                    'pelaku_nama'    => 'Rekonsiliasi',
                    'metode'         => null,
                    'catatan'        => 'Penyesuaian rekonsiliasi (selisih data historis: stok pernah diubah/dihapus sebelum fitur kartu stok)',
                    'waktu'          => $waktu,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }
    }

    /**
     * Hitung ulang kolom saldo untuk semua pergerakan, dipesan per stok & waktu.
     */
    public static function recomputeSaldo(): void
    {
        $stokIds = DB::table('sparepart_movements')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('stok_id');

        foreach ($stokIds as $stokId) {
            $movs = DB::table('sparepart_movements')
                ->where('stok_id', $stokId)
                ->orderBy('waktu')
                ->orderBy('id')
                ->get(['id', 'cabang_id', 'tipe', 'qty']);

            // Kelompokkan per cabang agar saldo akurat per cabang
            $saldoPerCabang = [];

            foreach ($movs as $m) {
                $key = $m->cabang_id ?? 0;
                if (!isset($saldoPerCabang[$key])) {
                    $saldoPerCabang[$key] = 0;
                }
                if ($m->tipe === 'masuk') {
                    $saldoPerCabang[$key] += (int) $m->qty;
                } else {
                    $saldoPerCabang[$key] -= (int) $m->qty;
                }
                // Tidak di-clamp agar rekonsiliasi tetap exact (saldo bisa
                // minus secara historis bila ada outflow tak tercatat di awal).
                DB::table('sparepart_movements')
                    ->where('id', $m->id)
                    ->update(['saldo' => $saldoPerCabang[$key]]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sparepart_movements');
    }
};
