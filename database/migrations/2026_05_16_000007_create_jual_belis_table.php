<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jual_belis', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('hp');
            $table->string('imei', 20)->nullable();
            $table->enum('tipe', ['beli', 'jual'])->default('beli');
            $table->decimal('harga', 15, 2)->default(0);
            $table->string('pelanggan')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jual_belis');
    }
};
