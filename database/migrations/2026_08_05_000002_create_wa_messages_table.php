<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur #9 — Integrasi WhatsApp & WhatsApp Web (Fonnte)
 * Login via QR Code, pesan masuk realtime di dashboard, kirim invoice/tagihan/status servis otomatis.
 *
 * Struktur meniru WhatsApp Web: 1 room = 1 nomor HP, banyak message.
 * Pesan masuk didorong Fonnte via webhook, pesan keluar dikirim via API Fonnte.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('wa_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();            // nomor WA pelanggan (format 62xxx)
            $table->string('name')->nullable();                // nama kontak / pushname
            $table->string('avatar')->nullable();
            $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
            $table->text('last_message')->nullable();
            $table->string('last_direction', 3)->default('in'); // in / out
            $table->timestamp('last_message_at')->nullable()->index();
            $table->unsignedInteger('unread')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('wa_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('wa_rooms')->cascadeOnDelete();
            $table->string('message_id')->nullable()->unique();  // id pesan dari Fonnte (untuk dedup)
            $table->string('from_number', 30)->nullable();
            $table->string('to_number', 30)->nullable();
            $table->enum('direction', ['in', 'out'])->default('in');
            $table->enum('type', ['text', 'image', 'video', 'audio', 'document', 'location', 'system'])->default('text');
            $table->text('message')->nullable();
            $table->string('media_url')->nullable();
            $table->string('caption')->nullable();
            $table->string('filename')->nullable();
            $table->string('mime')->nullable();
            $table->string('status', 20)->default('sent');      // sent / delivered / read / failed / pending
            $table->string('sender_id', 60)->nullable();        // id device pengirim (Fonnte)
            $table->string('device_id', 60)->nullable();
            $table->boolean('is_auto')->default(false);         // true jika dikirim otomatis (invoice dll)
            $table->json('meta')->nullable();
            $table->timestamp('received_at')->nullable();       // waktu pesan diterima di WA
            $table->timestamps();

            $table->index(['room_id', 'created_at']);
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_messages');
        Schema::dropIfExists('wa_rooms');
    }
};
