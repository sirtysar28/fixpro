<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabang', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('telp')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Insert default cabang
        \DB::table('cabang')->insert([
            ['nama' => 'Cabang Utama', 'alamat' => 'Jl. Raya Utama No. 88', 'telp' => '0812-3456-7890', 'aktif' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Cabang 2', 'alamat' => 'Jl. Raya Cabang No. 2', 'telp' => '0812-3456-7891', 'aktif' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cabang');
    }
};
