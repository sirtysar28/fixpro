<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add diskon column to penjualan_sparepart
        Schema::table('penjualan_sparepart', function (Blueprint $table) {
            if (!Schema::hasColumn('penjualan_sparepart', 'diskon')) {
                $table->decimal('diskon', 12, 2)->default(0)->after('modal_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penjualan_sparepart', function (Blueprint $table) {
            if (Schema::hasColumn('penjualan_sparepart', 'diskon')) {
                $table->dropColumn('diskon');
            }
        });
    }
};
