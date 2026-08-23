<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add Teknisi role
        DB::table('roles')->insertOrIgnore([
            'name' => 'Teknisi',
            'description' => 'Dashboard teknisi - lihat servis & pendapatan sendiri',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add teknisi_id to users table — cek dulu, bisa sudah ada di DB
        if (!Schema::hasColumn('users', 'teknisi_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('teknisi_id')->nullable()->after('cabang_id')->constrained('teknisis')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['teknisi_id']);
            $table->dropColumn('teknisi_id');
        });

        DB::table('roles')->where('name', 'Teknisi')->delete();
    }
};
