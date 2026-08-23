<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
            $table->string('nama_toko')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('durasi', ['1_bulan', '3_bulan', '6_bulan', '1_tahun', 'permanen'])->default('1_tahun');
            $table->decimal('nominal_bayar', 15, 2)->nullable();
            $table->string('bukti_transfer')->nullable(); // path file bukti transfer
            $table->text('catatan')->nullable();
            $table->text('admin_note')->nullable(); // catatan dari super admin
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activation_requests');
    }
};
