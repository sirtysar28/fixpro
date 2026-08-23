<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade Modul Pembelian Supplier — Final (14 Agustus 2026)
 *
 * 1. Kolom baru di pembelians:
 *    - biaya_tambahan, ongkir      → perhitungan total pembelian
 *    - total_retur                 → nilai retur (nilai pembelian berkurang)
 *    - status_transaksi            → Draft / Diproses / Selesai / Dibatalkan
 *    - status 'Hutang'             → pelunasan hutang supplier
 *    - diedit_oleh, diedit_pada    → audit siapa yang mengedit
 * 2. Tabel pembelian_payments      → riwayat pembayaran hutang (bayar sebagian/lunas)
 * 3. Tabel pembelian_returns       → riwayat retur pembelian
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== 1. Kolom tambahan di pembelians =====
        Schema::table('pembelians', function (Blueprint $table) {
            if (!Schema::hasColumn('pembelians', 'biaya_tambahan')) {
                $table->decimal('biaya_tambahan', 15, 2)->default(0)->after('diskon_nominal');
            }
            if (!Schema::hasColumn('pembelians', 'ongkir')) {
                $table->decimal('ongkir', 15, 2)->default(0)->after('biaya_tambahan');
            }
            if (!Schema::hasColumn('pembelians', 'total_retur')) {
                $table->decimal('total_retur', 15, 2)->default(0)->after('total');
            }
            if (!Schema::hasColumn('pembelians', 'diedit_oleh')) {
                $table->foreignId('diedit_oleh')->nullable()->constrained('users')->nullOnDelete()->after('user_id');
            }
            if (!Schema::hasColumn('pembelians', 'diedit_pada')) {
                $table->datetime('diedit_pada')->nullable()->after('diedit_oleh');
            }
        });

        // Status pembayaran: tambah 'Hutang' (alias resmi dari 'Belum Dibayar')
        // 'Belum Dibayar' tetap dipertahankan agar data lama tidak rusak.
        DB::statement("ALTER TABLE pembelians MODIFY status ENUM('Belum Dibayar','Hutang','Sebagian','Lunas','Dibatalkan') NOT NULL DEFAULT 'Belum Dibayar'");

        if (!Schema::hasColumn('pembelians', 'status_transaksi')) {
            Schema::table('pembelians', function (Blueprint $table) {
                $table->enum('status_transaksi', ['Draft', 'Diproses', 'Selesai', 'Dibatalkan'])->default('Selesai')->after('status');
            });
            // Data lama dianggap transaksi selesai (stok sudah masuk saat itu juga)
        }

        // ===== 2. Riwayat pembayaran hutang supplier =====
        if (!Schema::hasTable('pembelian_payments')) {
            Schema::create('pembelian_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pembelian_id')->constrained('pembelians')->cascadeOnDelete();
                $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // siapa menerima/mencatat pembayaran
                $table->date('tanggal');                       // tanggal pembayaran
                $table->decimal('jumlah', 15, 2);              // nominal
                $table->enum('metode', ['Cash', 'Transfer', 'QRIS'])->default('Cash');
                $table->string('ref_kode')->nullable();        // nomor referensi (BAYAR-PMB-...)
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->index(['cabang_id', 'tanggal']);
                $table->index('pembelian_id');
            });
        }

        // ===== 3. Riwayat retur pembelian =====
        if (!Schema::hasTable('pembelian_returns')) {
            Schema::create('pembelian_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pembelian_id')->constrained('pembelians')->cascadeOnDelete();
                $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('kode');                        // RETUR-PMB-...
                $table->foreignId('stok_id')->nullable()->constrained('stoks')->nullOnDelete();
                $table->string('nama_barang');
                $table->unsignedInteger('qty');
                $table->decimal('harga_retur', 15, 2)->default(0); // harga per pcs saat retur
                $table->decimal('nilai', 15, 2)->default(0);       // qty x harga_retur
                $table->string('alasan')->nullable();
                $table->date('tanggal');
                $table->timestamps();

                $table->index(['cabang_id', 'tanggal']);
                $table->index('pembelian_id');
            });
        }

        // ===== Backfill: pembayaran awal dari data lama =====
        // Pembelian lama yang sudah ada "dibayar" dicatat sebagai 1 baris
        // pembayaran awal supaya riwayat hutang lengkap.
        $now = now();
        $rows = [];
        foreach (DB::table('pembelians')->where('dibayar', '>', 0)->cursor() as $p) {
            $rows[] = [
                'pembelian_id' => $p->id,
                'cabang_id'    => $p->cabang_id,
                'user_id'      => $p->user_id,
                'tanggal'      => $p->tanggal,
                'jumlah'       => $p->dibayar,
                'metode'       => $p->metode_bayar ?? 'Cash',
                'ref_kode'     => 'AWAL-' . $p->kode,
                'catatan'      => 'Pembayaran awal (migrasi data)',
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('pembelian_payments')->insert($chunk);
        }

        // Seragamkan status lama 'Belum Dibayar' → 'Hutang' agar tampilan konsisten
        DB::table('pembelians')->where('status', 'Belum Dibayar')->update(['status' => 'Hutang']);
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelian_returns');
        Schema::dropIfExists('pembelian_payments');

        Schema::table('pembelians', function (Blueprint $table) {
            // lepas FK dulu sebelum drop kolom
            if (Schema::hasColumn('pembelians', 'diedit_oleh')) {
                try { $table->dropForeign(['diedit_oleh']); } catch (\Throwable $e) {}
            }
            foreach (['biaya_tambahan', 'ongkir', 'total_retur', 'status_transaksi', 'diedit_oleh', 'diedit_pada'] as $col) {
                if (Schema::hasColumn('pembelians', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        DB::statement("ALTER TABLE pembelians MODIFY status ENUM('Belum Dibayar','Sebagian','Lunas','Dibatalkan') NOT NULL DEFAULT 'Belum Dibayar'");
        DB::table('pembelians')->where('status', 'Hutang')->update(['status' => 'Belum Dibayar']);
    }
};
