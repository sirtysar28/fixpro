<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert role Admin Cabang Anak
        $exists = DB::table('roles')->where('name', 'Admin Cabang Anak')->exists();
        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'Admin Cabang Anak',
                'description' => 'Admin cabang anak enterprise — transaksi servis, sparepart, stok, laporan cabang sendiri',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'Admin Cabang Anak')->delete();
    }
};
