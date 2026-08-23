<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['masuk', 'keluar']);
            $table->string('kategori')->nullable();
            $table->decimal('jml', 15, 2)->default(0);
            $table->text('ket')->nullable();
            $table->string('ref')->nullable();
            $table->datetime('waktu');
            $table->decimal('saldo', 15, 2)->default(0);
            $table->enum('metode', ['Cash', 'Transfer', 'QRIS'])->default('Cash');
            $table->longText('bukti')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas');
    }
};
