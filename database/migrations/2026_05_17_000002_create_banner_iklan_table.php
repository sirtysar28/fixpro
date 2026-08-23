<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner_iklan', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('gambar'); // path file
            $table->string('link')->default('#');
            $table->boolean('aktif')->default(true);
            $table->integer('urutan')->default(1);
            $table->timestamps();
        });

        // Insert default banner
        \DB::table('banner_iklan')->insert([
            'judul' => 'Pendaftaran Kelas Baru Dibuka!',
            'deskripsi' => 'Training Profesional Servis HP & Elektronik',
            'gambar' => 'banner/banner-training.jpg',
            'link' => 'https://alpha2000.id/',
            'aktif' => true,
            'urutan' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_iklan');
    }
};
