<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur #8 — Penyempurnaan Fitur Jual Beli HP
 *
 * Menambahkan kolom lengkap untuk:
 *  - Harga Beli / Harga Jual / Modal Total / Estimasi Laba
 *  - IMEI 1 & IMEI 2, Serial Number, Battery Health
 *  - Merk, Model, Warna, RAM, Kapasitas Penyimpanan
 *  - Upload Foto Unit (Depan, Belakang, Samping, IMEI)
 *  - Checklist Kondisi (15 item) + Status Pemeriksaan
 *  - Status Unit + Garansi Penjualan
 *  - Riwayat Harga (JSON)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('jual_belis', function (Blueprint $table) {
            // ===== Identifikasi Unit =====
            if (!Schema::hasColumn('jual_belis', 'imei2')) {
                $table->string('imei2', 20)->nullable()->after('imei');
            }
            if (!Schema::hasColumn('jual_belis', 'serial_number')) {
                $table->string('serial_number', 60)->nullable()->after('imei2');
            }
            if (!Schema::hasColumn('jual_belis', 'merk')) {
                $table->string('merk', 60)->nullable()->after('serial_number');
            }
            if (!Schema::hasColumn('jual_belis', 'model')) {
                $table->string('model', 80)->nullable()->after('merk');
            }
            if (!Schema::hasColumn('jual_belis', 'warna')) {
                $table->string('warna', 40)->nullable()->after('model');
            }
            if (!Schema::hasColumn('jual_belis', 'ram')) {
                $table->string('ram', 20)->nullable()->after('warna');
            }
            if (!Schema::hasColumn('jual_belis', 'kapasitas')) {
                $table->string('kapasitas', 20)->nullable()->after('ram');
            }
            if (!Schema::hasColumn('jual_belis', 'battery_health')) {
                $table->tinyInteger('battery_health')->nullable()->after('kapasitas');
            }

            // ===== Harga & Laba =====
            if (!Schema::hasColumn('jual_belis', 'harga_jual')) {
                $table->decimal('harga_jual', 15, 2)->nullable()->after('harga');
            }
            if (!Schema::hasColumn('jual_belis', 'modal_total')) {
                $table->decimal('modal_total', 15, 2)->nullable()->after('harga_jual');
            }
            if (!Schema::hasColumn('jual_belis', 'estimasi_laba')) {
                $table->decimal('estimasi_laba', 15, 2)->nullable()->after('modal_total');
            }

            // ===== Foto Unit =====
            if (!Schema::hasColumn('jual_belis', 'foto_depan')) {
                $table->string('foto_depan')->nullable()->after('estimasi_laba');
            }
            if (!Schema::hasColumn('jual_belis', 'foto_belakang')) {
                $table->string('foto_belakang')->nullable()->after('foto_depan');
            }
            if (!Schema::hasColumn('jual_belis', 'foto_samping')) {
                $table->string('foto_samping')->nullable()->after('foto_belakang');
            }
            if (!Schema::hasColumn('jual_belis', 'foto_imei')) {
                $table->string('foto_imei')->nullable()->after('foto_samping');
            }

            // ===== Checklist Kondisi (JSON: normal/rusak/belumdicek per item) =====
            if (!Schema::hasColumn('jual_belis', 'checklist_kondisi')) {
                $table->json('checklist_kondisi')->nullable()->after('foto_imei');
            }
            if (!Schema::hasColumn('jual_belis', 'status_pemeriksaan')) {
                $table->enum('status_pemeriksaan', ['Normal', 'Rusak', 'Belum Dicek'])
                      ->default('Belum Dicek')->after('checklist_kondisi');
            }

            // ===== Status Unit & Garansi =====
            if (!Schema::hasColumn('jual_belis', 'status_unit')) {
                $table->enum('status_unit', [
                    'Ready Dijual', 'Booking', 'Sedang Diservis', 'Terjual', 'Retur'
                ])->default('Ready Dijual')->after('status_pemeriksaan');
            }
            if (!Schema::hasColumn('jual_belis', 'garansi')) {
                $table->enum('garansi', ['Tanpa Garansi', 'Garansi 7 Hari', 'Garansi 30 Hari', 'Garansi 90 Hari'])
                      ->default('Tanpa Garansi')->after('status_unit');
            }
            if (!Schema::hasColumn('jual_belis', 'garansi_hingga')) {
                $table->date('garansi_hingga')->nullable()->after('garansi');
            }

            // ===== Riwayat Harga =====
            if (!Schema::hasColumn('jual_belis', 'riwayat_harga')) {
                $table->json('riwayat_harga')->nullable()->after('garansi_hingga');
            }
        });

        // Migrasi data lama: isi harga_jual & modal_total dari kolom lama
        \Illuminate\Support\Facades\DB::table('jual_belis')
            ->whereNull('harga_jual')
            ->update([
                'harga_jual' => \Illuminate\Support\Facades\DB::raw('harga'),
            ]);
        \Illuminate\Support\Facades\DB::table('jual_belis')
            ->whereNull('modal_total')
            ->whereNotNull('harga_beli')
            ->update([
                'modal_total' => \Illuminate\Support\Facades\DB::raw('harga_beli'),
            ]);
    }

    public function down(): void
    {
        Schema::table('jual_belis', function (Blueprint $table) {
            $cols = [
                'imei2', 'serial_number', 'merk', 'model', 'warna', 'ram', 'kapasitas',
                'battery_health', 'harga_jual', 'modal_total', 'estimasi_laba',
                'foto_depan', 'foto_belakang', 'foto_samping', 'foto_imei',
                'checklist_kondisi', 'status_pemeriksaan', 'status_unit', 'garansi',
                'garansi_hingga', 'riwayat_harga',
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('jual_belis', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
