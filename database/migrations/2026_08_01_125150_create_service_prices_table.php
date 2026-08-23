<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
            $table->string('merk_hp')->nullable()->index();
            $table->string('tipe_hp')->nullable();
            $table->string('kerusakan'); // e.g. "Ganti LCD", "Ganti Baterai", "Software Reset"
            $table->text('deskripsi')->nullable();
            $table->decimal('harga_jasa', 15, 2)->default(0);
            $table->string('kategori')->default('umum'); // umum, hardware, software, dll
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};
