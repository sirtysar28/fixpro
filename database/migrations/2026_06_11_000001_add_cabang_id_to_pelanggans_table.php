<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cek dulu sebelum tambah kolom
        if (!Schema::hasColumn('pelanggans', 'cabang_id')) {
            Schema::table('pelanggans', function (Blueprint $table) {
                $table->unsignedBigInteger('cabang_id')->nullable()->after('user_id');
                $table->foreign('cabang_id')->references('id')->on('cabang')->nullOnDelete();
            });
        }

        // Backfill: update pelanggan yang sudah punya user_id tapi belum punya cabang_id
        $pelanggans = DB::table('pelanggans')->whereNull('cabang_id')->whereNotNull('user_id')->get();
        foreach ($pelanggans as $pel) {
            $user = DB::table('users')->where('id', $pel->user_id)->first();
            if ($user && $user->cabang_id) {
                DB::table('pelanggans')->where('id', $pel->id)->update(['cabang_id' => $user->cabang_id]);
            }
        }

        // Backfill: update pelanggan yang punya servis tapi belum punya cabang_id (ambil dari servis terbaru)
        $pelanggans = DB::table('pelanggans')->whereNull('cabang_id')->get();
        foreach ($pelanggans as $pel) {
            $servis = DB::table('servis')->where('pelanggan_id', $pel->id)->orderBy('created_at', 'desc')->first();
            if ($servis && $servis->cabang_id) {
                DB::table('pelanggans')->where('id', $pel->id)->update(['cabang_id' => $servis->cabang_id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('pelanggans', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
        });
    }
};
