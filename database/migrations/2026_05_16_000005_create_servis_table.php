<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servis', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggans')->nullOnDelete();
            $table->string('perangkat');
            $table->text('keluhan')->nullable();
            $table->string('tipe')->default('Android'); // Apple / Android
            $table->enum('status', ['Masuk', 'Proses', 'Pending', 'Selesai'])->default('Masuk');
            $table->decimal('biaya', 15, 2)->default(0);
            $table->decimal('dp', 15, 2)->default(0);
            $table->decimal('modal_sparepart', 15, 2)->default(0);
            $table->date('tanggal');
            $table->foreignId('teknisi_id')->nullable()->constrained('teknisis')->nullOnDelete();
            $table->enum('prioritas', ['Normal', 'Urgent'])->default('Normal');
            $table->string('imei', 20)->nullable();
            $table->text('catatan')->nullable();
            $table->integer('garansi')->default(30);
            $table->datetime('eta')->nullable();
            $table->json('foto')->nullable();
            $table->json('spareparts')->nullable();
            $table->boolean('diambil')->default(false);
            $table->datetime('tgl_diambil')->nullable();
            $table->date('tanggal_garansi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servis');
    }
};
