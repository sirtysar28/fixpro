<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipe_hp', function (Blueprint $table) {
            $table->id();
            $table->string('merk')->index(); // Apple, Samsung, Xiaomi, Oppo, Vivo, dll
            $table->string('tipe')->unique(); // iPhone 13 Pro Max, Samsung A54, dll
            $table->string('kategori')->nullable(); // Smartphone, Tablet, Feature Phone
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipe_hp');
    }
};
