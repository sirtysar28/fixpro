<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add parent_cabang_id to group branches under one admin
        Schema::table('cabang', function (Blueprint $table) {
            if (!Schema::hasColumn('cabang', 'parent_cabang_id')) {
                $table->unsignedBigInteger('parent_cabang_id')->nullable()->after('created_by_user_id');
                $table->foreign('parent_cabang_id')->references('id')->on('cabang')->nullOnDelete();
            }
        });

        // Create stock_transfers table
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_id')->constrained('stoks')->cascadeOnDelete();
            $table->foreignId('from_cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->foreignId('to_cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->integer('qty');
            $table->decimal('harga_satuan', 12, 2)->default(0);
            $table->string('kode')->unique();
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');

        Schema::table('cabang', function (Blueprint $table) {
            if (Schema::hasColumn('cabang', 'parent_cabang_id')) {
                $table->dropForeign(['parent_cabang_id']);
                $table->dropColumn('parent_cabang_id');
            }
        });
    }
};
