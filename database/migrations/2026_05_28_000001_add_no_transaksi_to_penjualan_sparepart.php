<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan_sparepart', function (Blueprint $table) {
            $table->string('no_transaksi')->nullable()->after('kode')->index();
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_sparepart', function (Blueprint $table) {
            $table->dropColumn('no_transaksi');
        });
    }
};
