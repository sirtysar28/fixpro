<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jual_belis', function (Blueprint $table) {
            // Cek dulu, kolom sudah ada di DB tapi belum tercatat di migrations
            if (!Schema::hasColumn('jual_belis', 'kode')) {
                $table->string('kode')->nullable()->after('id');
            }
            if (!Schema::hasColumn('jual_belis', 'cabang_id')) {
                $table->foreignId('cabang_id')->nullable()->after('kode');
            }
            if (!Schema::hasColumn('jual_belis', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('cabang_id');
            }
            if (!Schema::hasColumn('jual_belis', 'metode_bayar')) {
                $table->enum('metode_bayar', ['Cash', 'Transfer', 'QRIS'])->default('Cash')->after('harga');
            }
            if (!Schema::hasColumn('jual_belis', 'harga_beli')) {
                $table->decimal('harga_beli', 15, 2)->nullable()->after('metode_bayar');
            }
            if (!Schema::hasColumn('jual_belis', 'no_hp_pelanggan')) {
                $table->string('no_hp_pelanggan')->nullable()->after('pelanggan');
            }
            if (!Schema::hasColumn('jual_belis', 'kondisi')) {
                $table->string('kondisi')->default('Second')->after('no_hp_pelanggan');
            }
            if (!Schema::hasColumn('jual_belis', 'kelengkapan')) {
                $table->string('kelengkapan')->nullable()->after('kondisi');
            }
            if (!Schema::hasColumn('jual_belis', 'status')) {
                $table->enum('status', ['Selesai', 'Dibatalkan'])->default('Selesai')->after('catatan');
            }
            if (!Schema::hasColumn('jual_belis', 'alasan_pembatalan')) {
                $table->string('alasan_pembatalan')->nullable()->after('status');
            }
        });

        // Populate kode yang masih null
        try {
            DB::table('jual_belis')->whereNull('kode')->eachById(function ($item) {
                $date = \Carbon\Carbon::parse($item->tanggal ?? $item->created_at)->format('ymd');
                $kode = 'JB-' . $date . '-' . str_pad($item->id, 3, '0', STR_PAD_LEFT);
                DB::table('jual_belis')->where('id', $item->id)->update(['kode' => $kode]);
            });
        } catch (\Exception $e) {
            // ignore
        }
    }

    public function down(): void
    {
        Schema::table('jual_belis', function (Blueprint $table) {
            $table->dropColumn([
                'kode', 'cabang_id', 'user_id', 'metode_bayar',
                'harga_beli', 'no_hp_pelanggan', 'kondisi', 'kelengkapan',
                'status', 'alasan_pembatalan',
            ]);
        });
    }
};
