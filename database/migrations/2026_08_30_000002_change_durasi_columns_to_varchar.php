<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revisi aktivasi: masa berlaku sekarang mengikuti paket —
 * 'standard_1_tahun' / 'enterprise_1_tahun' (opsi permanen dihapus).
 *
 * Kolom `durasi` di activation_requests & activation_codes masih ENUM lama
 * ('1_bulan','3_bulan','6_bulan','1_tahun','permanen') sehingga nilai baru
 * ditolak MySQL: "Data truncated for column 'durasi'".
 * Ubah ke VARCHAR(30) agar fleksibel (data lama tetap terjaga).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['activation_requests', 'activation_codes'] as $tbl) {
            if (!Schema::hasTable($tbl) || !Schema::hasColumn($tbl, 'durasi')) {
                continue;
            }
            DB::statement("ALTER TABLE {$tbl} MODIFY durasi VARCHAR(30) NOT NULL DEFAULT 'standard_1_tahun'");
        }
    }

    public function down(): void
    {
        foreach (['activation_requests', 'activation_codes'] as $tbl) {
            if (!Schema::hasTable($tbl) || !Schema::hasColumn($tbl, 'durasi')) {
                continue;
            }
            DB::statement(
                "ALTER TABLE {$tbl} MODIFY durasi ENUM('1_bulan','3_bulan','6_bulan','1_tahun','permanen','standard_1_tahun','enterprise_1_tahun') NOT NULL DEFAULT '1_bulan'"
            );
        }
    }
};
