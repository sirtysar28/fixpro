<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur #11 — Mode Offline (Offline Sync)
 * Antrean transaksi yang dibuat saat offline di mobile app, lalu disinkronkan saat online.
 *
 * Idempotency: client_ref (UUID yang dibuat di client saat offline) bersifat UNIQUE.
 * Sinkronanisasi ulang aman: transaksi dengan client_ref sama TIDAK akan diduplikasi.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
            $table->string('device_id', 80)->nullable();
            $table->string('client_ref')->unique();                  // UUID dari client — anti duplikat
            $table->string('entity_type', 50)->index();              // servis / penjualan_sparepart / kas / pelanggan / ...
            $table->string('action', 20)->default('create');         // create / update / delete
            $table->json('payload');                                 // data lengkap transaksi offline
            $table->string('client_id')->nullable();                 // id lokal di client (untuk mapping response)
            $table->unsignedBigInteger('server_id')->nullable();     // id record di server setelah diproses
            $table->string('status', 20)->default('processed')->index(); // processed / failed / conflict
            $table->text('error_message')->nullable();
            $table->timestamp('client_created_at')->nullable();      // waktu transaksi dibuat offline
            $table->timestamp('synced_at')->nullable();              // waktu berhasil disinkronkan ke server
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
    }
};
