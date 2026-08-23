<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add cabang_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cabang_id')->nullable()->after('role_id')->constrained('cabang')->nullOnDelete();
        });

        // Add cabang_id to teknisis
        Schema::table('teknisis', function (Blueprint $table) {
            $table->foreignId('cabang_id')->nullable()->after('aktif')->constrained('cabang')->nullOnDelete();
        });

        // Add cabang_id to servis
        Schema::table('servis', function (Blueprint $table) {
            $table->foreignId('cabang_id')->nullable()->after('pelanggan_id')->constrained('cabang')->nullOnDelete();
        });

        // Add cabang_id to kas
        Schema::table('kas', function (Blueprint $table) {
            $table->foreignId('cabang_id')->nullable()->after('tipe')->constrained('cabang')->nullOnDelete();
        });

        // Set all existing data to cabang_id = 1 (Cabang Utama)
        \DB::table('users')->whereNull('cabang_id')->update(['cabang_id' => 1]);
        \DB::table('teknisis')->whereNull('cabang_id')->update(['cabang_id' => 1]);
        \DB::table('servis')->whereNull('cabang_id')->update(['cabang_id' => 1]);
        \DB::table('kas')->whereNull('cabang_id')->update(['cabang_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
        });
        Schema::table('teknisis', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
        });
        Schema::table('servis', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
        });
        Schema::table('kas', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
        });
    }
};
