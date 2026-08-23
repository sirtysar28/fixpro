<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan_sparepart', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_id')->constrained('stoks')->cascadeOnDelete();
            $table->foreignId('pelanggan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cabang_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kode')->unique();
            $table->integer('qty');
            $table->decimal('harga_satuan', 12, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('modal_total', 12, 2)->default(0);
            $table->enum('metode_bayar', ['Cash', 'Transfer', 'QRIS'])->default('Cash');
            $table->text('catatan')->nullable();
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_sparepart');
    }
};
