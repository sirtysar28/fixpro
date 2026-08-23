<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stoks', function (Blueprint $table) {
            if (!Schema::hasColumn('stoks', 'cabang_id')) {
                $table->unsignedBigInteger('cabang_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('stoks', 'merk_hp')) {
                $table->string('merk_hp')->nullable()->after('kategori');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stoks', function (Blueprint $table) {
            if (Schema::hasColumn('stoks', 'cabang_id')) {
                $table->dropColumn('cabang_id');
            }
            if (Schema::hasColumn('stoks', 'merk_hp')) {
                $table->dropColumn('merk_hp');
            }
        });
    }
};
