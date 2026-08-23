<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelians', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('supplier_nama');
            $table->string('supplier_kontak')->nullable();
            $table->string('supplier_alamat')->nullable();
            $table->date('tanggal');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('diskon_persen', 5, 2)->default(0);
            $table->decimal('diskon_nominal', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('dibayar', 15, 2)->default(0);
            $table->decimal('sisa', 15, 2)->default(0);          // hutang supplier
            $table->enum('status', ['Belum Dibayar', 'Sebagian', 'Lunas', 'Dibatalkan'])->default('Belum Dibayar');
            $table->enum('metode_bayar', ['Cash', 'Transfer', 'QRIS'])->default('Cash');
            $table->json('items')->nullable();                   // [{stok_id, nama, kode, qty, harga_beli, harga_jual, subtotal}]
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelians');
    }
};
