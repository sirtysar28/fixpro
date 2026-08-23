<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur #8 — Payment Gateway
 * Menyimpan transaksi pembayaran online (VA / QRIS / E-Wallet / Transfer Bank).
 * Status terverifikasi otomatis via webhook dari payment gateway.
 *
 * Catatan: 'payable_type' & 'payable_id' pakai polimorfik agar fleksibel dipakai
 * untuk pelunasan Servis, PenjualanSparepart, TagihanSparepart, atau ActivationRequest.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();                          // PAY-ymd-xxx (internal)
            $table->string('reference')->nullable()->index();          // ref internal (mis. SVC-xxx / TRX-xxx)
            $table->string('provider')->default('tripay');             // tripay / midtrans / xendit / manual
            $table->string('method_code', 40);                         // VA_BCA / QRIS / EWALLET_OVO / BANK_MANDIRI
            $table->string('provider_ref')->nullable()->index();       // merchant_ref / transaction_id dari gateway
            $table->string('payable_type')->nullable()->index();       // App\Models\Servis, dst
            $table->unsignedBigInteger('payable_id')->nullable();      // id model payable
            $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();

            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('fee_customer', 15, 2)->default(0);        // fee yang ditanggung customer
            $table->decimal('fee_merchant', 15, 2)->default(0);        // fee yang ditanggung toko
            $table->decimal('total_bayar', 15, 2)->default(0);         // amount + fee_customer
            $table->decimal('diterima', 15, 2)->default(0);            // nominal yang masuk ke rekening

            // Status: pending / paid / expired / failed / refunded
            $table->string('status', 20)->default('pending')->index();

            // Output dari gateway untuk instruksi pembayaran
            $table->string('va_number')->nullable();
            $table->text('qr_string')->nullable();
            $table->text('pay_url')->nullable();
            $table->text('instructions')->nullable();                  // JSON array langkah pembayaran

            $table->timestamp('expired_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_response')->nullable();                  // snapshot response dari gateway
            $table->json('webhook_payload')->nullable();               // snapshot payload webhook terakhir

            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
