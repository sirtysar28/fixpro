<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tagihan_sparepart')) {
            // Table already exists, skip creation
            return;
        }

        Schema::create('tagihan_sparepart', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_toko');
            $table->string('kontak_toko')->nullable();
            $table->string('alamat_toko')->nullable();
            $table->date('tanggal');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('diskon_persen', 5, 2)->default(0);
            $table->decimal('diskon_nominal', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('dibayar', 15, 2)->default(0);
            $table->decimal('sisa', 15, 2)->default(0);
            $table->enum('status', ['Belum Dibayar', 'Sebagian', 'Lunas', 'Dibatalkan'])->default('Belum Dibayar');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasTable('tagihan_sparepart_items')) {
        Schema::create('tagihan_sparepart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan_sparepart')->cascadeOnDelete();
            $table->foreignId('stok_id')->nullable()->constrained('stoks')->nullOnDelete();
            $table->string('nama_barang');
            $table->integer('qty');
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_sparepart_items');
        Schema::dropIfExists('tagihan_sparepart');
    }
};
