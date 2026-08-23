<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Perpanjang kolom teks di tabel stoks agar muat nama barang panjang
 * (mis. nama LCD combo Oppo/Realme yang bisa > 400 karakter).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Cek DB driver — hanya mysql/mariadb yang pakai ALTER CHANGE.
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: tidak bisa modify column langsung, skip (diabaikan pada testing lokal).
            return;
        }

        // mysql / mariadb — ubah ke VARCHAR(1000). Lebih dari cukup untuk nama LCD combo.
        DB::statement('ALTER TABLE stoks MODIFY nama VARCHAR(1000) NOT NULL');
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }
        DB::statement('ALTER TABLE stoks MODIFY nama VARCHAR(255) NOT NULL');
    }
};
