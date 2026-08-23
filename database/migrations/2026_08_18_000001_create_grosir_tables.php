<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MODUL PENJUALAN GROSIR — 18 Agustus 2026
 *
 * Fitur lengkap:
 * - Harga grosir berbeda per produk & per toko (Eceran, Grosir 1-3, Reseller, Distributor)
 * - Harga khusus per pelanggan (prioritas tertinggi)
 * - Pelanggan grosir dengan level harga & tipe (Member/Grosir/Reseller/Distributor)
 * - Transaksi penjualan grosir multi-item + piutang + jatuh tempo
 * - Pesanan grosir dengan reservasi stok
 * - Retur grosir
 * - Pembayaran piutang grosir
 * - Semua tabel punya cabang_id agar data ANTAR TOKO TIDAK CAMPUR (stok & pencarian independen)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== 0. Kolom pendukung =====

        // Cabang bisa bertipe "toko" atau "gudang" (sumber stok grosir)
        if (!Schema::hasColumn('cabang', 'tipe')) {
            Schema::table('cabang', function (Blueprint $table) {
                $table->string('tipe', 20)->default('toko')->after('aktif');
            });
        }

        // Stok reservasi (dipakai pesanan grosir yang sudah diproses)
        // Stok tersedia = stok - reserved
        if (!Schema::hasColumn('stoks', 'reserved')) {
            Schema::table('stoks', function (Blueprint $table) {
                $table->integer('reserved')->default(0)->after('stok');
            });
        }

        // ===== 1. Tabel harga grosir per produk per cabang =====
        if (!Schema::hasTable('harga_grosirs')) {
            Schema::create('harga_grosirs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->foreignId('stok_id')->constrained('stoks')->cascadeOnDelete();
                $table->decimal('harga_grosir1', 15, 2)->nullable();
                $table->decimal('harga_grosir2', 15, 2)->nullable();
                $table->decimal('harga_grosir3', 15, 2)->nullable();
                $table->decimal('harga_reseller', 15, 2)->nullable();
                $table->decimal('harga_distributor', 15, 2)->nullable();
                $table->integer('min_qty_grosir1')->default(3);
                $table->integer('min_qty_grosir2')->default(6);
                $table->integer('min_qty_grosir3')->default(12);
                $table->boolean('aktif')->default(true);
                $table->timestamps();
                $table->unique(['stok_id', 'cabang_id'], 'harga_grosir_stok_cabang_unique');
            });
        }

        // ===== 2. Pelanggan grosir =====
        if (!Schema::hasTable('pelanggan_grosirs')) {
            Schema::create('pelanggan_grosirs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('kode', 30)->nullable();
                $table->string('nama');
                $table->string('no_hp', 30)->nullable();
                $table->text('alamat')->nullable();
                $table->text('alamat_kirim')->nullable();
                // Tipe pelanggan: Member / Grosir / Reseller / Distributor / Umum
                $table->string('tipe', 30)->default('Grosir');
                // Level harga default pelanggan
                $table->string('level_harga', 20)->default('grosir1');
                $table->decimal('limit_piutang', 15, 2)->default(0);
                $table->boolean('aktif')->default(true);
                $table->text('catatan')->nullable();
                $table->timestamps();
                $table->unique(['kode', 'cabang_id'], 'pelanggan_grosir_kode_cabang_unique');
            });
        }

        // ===== 3. Harga khusus per pelanggan per produk (prioritas tertinggi) =====
        if (!Schema::hasTable('harga_khusus')) {
            Schema::create('harga_khusus', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pelanggan_grosir_id')->constrained('pelanggan_grosirs')->cascadeOnDelete();
                $table->foreignId('stok_id')->constrained('stoks')->cascadeOnDelete();
                $table->decimal('harga', 15, 2);
                $table->timestamps();
                $table->unique(['pelanggan_grosir_id', 'stok_id'], 'harga_khusus_pelanggan_stok_unique');
            });
        }

        // ===== 4. Penjualan grosir (header) =====
        if (!Schema::hasTable('penjualan_grosirs')) {
            Schema::create('penjualan_grosirs', function (Blueprint $table) {
                $table->id();
                $table->string('no_nota', 40)->unique();
                $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete(); // toko pencatat nota
                $table->foreignId('sumber_cabang_id')->nullable()->constrained('cabang')->nullOnDelete(); // sumber stok (toko/gudang)
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // kasir
                $table->foreignId('pelanggan_grosir_id')->nullable()->constrained('pelanggan_grosirs')->nullOnDelete();
                $table->string('nama_pelanggan')->nullable();
                $table->string('level_harga', 20)->default('eceran');
                $table->dateTime('tanggal');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('diskon', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->decimal('bayar', 15, 2)->default(0);
                $table->decimal('piutang', 15, 2)->default(0);
                $table->decimal('total_retur', 15, 2)->default(0);
                $table->date('jatuh_tempo')->nullable();
                $table->enum('metode_bayar', ['Cash', 'Transfer', 'QRIS', 'Tempo'])->default('Cash');
                $table->enum('status', ['Lunas', 'Piutang', 'Sebagian', 'Dibatalkan'])->default('Lunas');
                $table->text('alamat_kirim')->nullable();
                $table->text('catatan')->nullable();
                $table->foreignId('pesanan_grosir_id')->nullable();
                $table->string('alasan_pembatalan', 500)->nullable();
                $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('dibatalkan_pada')->nullable();
                $table->timestamps();
            });
        }

        // ===== 5. Item penjualan grosir =====
        if (!Schema::hasTable('penjualan_grosir_items')) {
            Schema::create('penjualan_grosir_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('penjualan_grosir_id')->constrained('penjualan_grosirs')->cascadeOnDelete();
                $table->foreignId('stok_id')->nullable()->constrained('stoks')->nullOnDelete();
                $table->string('kode', 60)->nullable();
                $table->string('nama');
                $table->integer('qty')->default(1);
                $table->decimal('harga_satuan', 15, 2)->default(0);
                $table->decimal('modal_satuan', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        // ===== 6. Pesanan grosir (order) =====
        if (!Schema::hasTable('pesanan_grosirs')) {
            Schema::create('pesanan_grosirs', function (Blueprint $table) {
                $table->id();
                $table->string('no_pesanan', 40)->unique();
                $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->foreignId('sumber_cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('pelanggan_grosir_id')->nullable()->constrained('pelanggan_grosirs')->nullOnDelete();
                $table->string('nama_pelanggan')->nullable();
                $table->string('level_harga', 20)->default('grosir1');
                $table->dateTime('tanggal');
                $table->date('tanggal_selesai')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('diskon', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->enum('status', ['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'])->default('Menunggu');
                $table->text('alamat_kirim')->nullable();
                $table->text('catatan')->nullable();
                $table->foreignId('penjualan_grosir_id')->nullable();
                $table->timestamps();
            });
        }

        // ===== 7. Item pesanan grosir =====
        if (!Schema::hasTable('pesanan_grosir_items')) {
            Schema::create('pesanan_grosir_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pesanan_grosir_id')->constrained('pesanan_grosirs')->cascadeOnDelete();
                $table->foreignId('stok_id')->nullable()->constrained('stoks')->nullOnDelete();
                $table->string('kode', 60)->nullable();
                $table->string('nama');
                $table->integer('qty')->default(1);
                $table->decimal('harga_satuan', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        // ===== 8. Retur grosir =====
        if (!Schema::hasTable('retur_grosirs')) {
            Schema::create('retur_grosirs', function (Blueprint $table) {
                $table->id();
                $table->string('no_retur', 40)->unique();
                $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('penjualan_grosir_id')->nullable()->constrained('penjualan_grosirs')->nullOnDelete();
                $table->foreignId('pelanggan_grosir_id')->nullable()->constrained('pelanggan_grosirs')->nullOnDelete();
                $table->string('nama_pelanggan')->nullable();
                $table->dateTime('tanggal');
                $table->decimal('total', 15, 2)->default(0);
                $table->enum('metode', ['Uang Kembali', 'Tukar Barang', 'Potong Piutang'])->default('Uang Kembali');
                $table->string('alasan', 500)->nullable();
                $table->timestamps();
            });
        }

        // ===== 9. Item retur grosir =====
        if (!Schema::hasTable('retur_grosir_items')) {
            Schema::create('retur_grosir_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('retur_grosir_id')->constrained('retur_grosirs')->cascadeOnDelete();
                $table->foreignId('stok_id')->nullable()->constrained('stoks')->nullOnDelete();
                $table->string('nama');
                $table->integer('qty')->default(1);
                $table->decimal('harga_satuan', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        // ===== 10. Pembayaran piutang grosir =====
        if (!Schema::hasTable('piutang_grosir_payments')) {
            Schema::create('piutang_grosir_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('penjualan_grosir_id')->constrained('penjualan_grosirs')->cascadeOnDelete();
                $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('tanggal');
                $table->decimal('jml', 15, 2)->default(0);
                $table->enum('metode', ['Cash', 'Transfer', 'QRIS'])->default('Cash');
                $table->string('catatan', 500)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('piutang_grosir_payments');
        Schema::dropIfExists('retur_grosir_items');
        Schema::dropIfExists('retur_grosirs');
        Schema::dropIfExists('pesanan_grosir_items');
        Schema::dropIfExists('pesanan_grosirs');
        Schema::dropIfExists('penjualan_grosir_items');
        Schema::dropIfExists('penjualan_grosirs');
        Schema::dropIfExists('harga_khusus');
        Schema::dropIfExists('pelanggan_grosirs');
        Schema::dropIfExists('harga_grosirs');

        if (Schema::hasColumn('stoks', 'reserved')) {
            Schema::table('stoks', function (Blueprint $table) {
                $table->dropColumn('reserved');
            });
        }
        if (Schema::hasColumn('cabang', 'tipe')) {
            Schema::table('cabang', function (Blueprint $table) {
                $table->dropColumn('tipe');
            });
        }
    }
};
