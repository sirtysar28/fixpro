<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur Paket Berlangganan — catat aktivasi paket berdurasi 3 bulan.
 * Mengikat ke user + paket (standar/enterprise) + mengisi login_expires_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->string('package', 20)->default('standar');      // standar / enterprise / custom
                $table->string('kode')->unique();
                $table->unsignedSmallInteger('duration_months')->default(3); // default 3 bulan
                $table->decimal('amount', 12, 2)->default(0);            // harga (opsional)
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->string('status', 20)->default('active');         // active / expired / cancelled
                $table->string('note')->nullable();
                $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index('ends_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
