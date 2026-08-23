<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cabang', function (Blueprint $table) {
            if (!Schema::hasColumn('cabang', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->after('aktif');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cabang', function (Blueprint $table) {
            if (Schema::hasColumn('cabang', 'created_by_user_id')) {
                $table->dropColumn('created_by_user_id');
            }
        });
    }
};
