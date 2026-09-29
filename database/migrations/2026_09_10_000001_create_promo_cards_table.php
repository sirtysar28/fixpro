<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Card Banner Promo — tampil di paling atas Dashboard (semua role).
        // Update isi card hanya oleh Super Admin.
        Schema::create('promo_cards', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();          // detail lengkap (tampil di popup)
            $table->string('gambar')->nullable();           // path storage atau URL
            $table->string('tombol_text', 100)->default('Lihat Detail');
            $table->string('tombol_link', 500)->default('#');
            $table->boolean('aktif')->default(true);
            $table->integer('urutan')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_cards');
    }
};
