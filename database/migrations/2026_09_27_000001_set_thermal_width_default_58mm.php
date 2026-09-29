<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Default printer thermal mini 58mm (sebelumnya 80mm menyebabkan teks terpotong)
        DB::table('settings')
            ->where('key', 'thermal_width')
            ->where('value', '80')
            ->update(['value' => '58', 'updated_at' => now()]);

        // Pastikan key selalu ada
        DB::table('settings')->insertOrIgnore([
            'key' => 'thermal_width',
            'value' => '58',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'thermal_width')
            ->where('value', '58')
            ->update(['value' => '80', 'updated_at' => now()]);
    }
};
