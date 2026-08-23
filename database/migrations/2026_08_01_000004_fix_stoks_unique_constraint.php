<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop unique lama (kode saja) dan ganti dengan composite unique (kode + cabang_id)
        // Cek apakah index stoks_kode_unique ada
        $rows = DB::select("SHOW INDEX FROM stoks WHERE Key_name = 'stoks_kode_unique'");
        if (!empty($rows)) {
            DB::statement("ALTER TABLE stoks DROP INDEX stoks_kode_unique");
        }

        // Buat composite unique kode + cabang_id
        Schema::table('stoks', function (Blueprint $table) {
            $table->unique(['kode', 'cabang_id'], 'stoks_kode_cabang_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stoks', function (Blueprint $table) {
            $table->dropUnique(['kode', 'cabang_id'], 'stoks_kode_cabang_unique');
        });

        // Restore unique lama
        Schema::table('stoks', function (Blueprint $table) {
            $table->unique(['kode'], 'stoks_kode_unique');
        });
    }
};
