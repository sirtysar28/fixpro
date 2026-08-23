<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('settings')->insert([
            ['key' => 'nama_toko', 'value' => 'FIXPRO SERVICE', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'alamat', 'value' => 'Jl. Raya Utama No. 88', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'telp', 'value' => '0812-3456-7890', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'wa_template', 'value' => 'Halo {nama}, servis ({kode}) - {perangkat} status: {status}. Biaya: {biaya}', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'wa_api_key', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'thermal_width', 'value' => '80', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'qris_image', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'qris_merchant', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
