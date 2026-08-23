<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom pembatalan ke tabel servis
        Schema::table('servis', function (Blueprint $table) {
            $table->string('alasan_pembatalan')->nullable()->after('tgl_diambil');
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete()->after('alasan_pembatalan');
            $table->datetime('dibatalkan_pada')->nullable()->after('dibatalkan_oleh');
        });

        // Ubah enum status servis: tambah 'Dibatalkan'
        DB::statement("ALTER TABLE servis MODIFY COLUMN status ENUM('Masuk','Proses','Pending','Selesai','Dibatalkan') DEFAULT 'Masuk'");

        // Tambah kolom pembatalan ke tabel penjualan_sparepart
        Schema::table('penjualan_sparepart', function (Blueprint $table) {
            $table->enum('status', ['Selesai', 'Dibatalkan'])->default('Selesai')->after('catatan');
            $table->string('alasan_pembatalan')->nullable()->after('status');
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete()->after('alasan_pembatalan');
            $table->datetime('dibatalkan_pada')->nullable()->after('dibatalkan_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('servis', function (Blueprint $table) {
            $table->dropForeign(['dibatalkan_oleh']);
            $table->dropColumn(['alasan_pembatalan', 'dibatalkan_oleh', 'dibatalkan_pada']);
        });

        DB::statement("ALTER TABLE servis MODIFY COLUMN status ENUM('Masuk','Proses','Pending','Selesai') DEFAULT 'Masuk'");

        Schema::table('penjualan_sparepart', function (Blueprint $table) {
            $table->dropForeign(['dibatalkan_oleh']);
            $table->dropColumn(['status', 'alasan_pembatalan', 'dibatalkan_oleh', 'dibatalkan_pada']);
        });
    }
};
