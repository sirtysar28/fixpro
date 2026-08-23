<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teknisis', function (Blueprint $table) {
            // cabang_id sudah ada dari migrasi sebelumnya, hanya tambah bagi_hasil
            if (!Schema::hasColumn('teknisis', 'bagi_hasil')) {
                $table->decimal('bagi_hasil', 5, 2)->default(35.00)->after('cabang_id')->comment('Persentase bagi hasil teknisi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teknisis', function (Blueprint $table) {
            if (Schema::hasColumn('teknisis', 'bagi_hasil')) {
                $table->dropColumn('bagi_hasil');
            }
        });
    }
};
