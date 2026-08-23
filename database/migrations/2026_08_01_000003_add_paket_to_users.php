<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add paket column to users table (standar / enterprise)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'paket')) {
                $table->string('paket', 20)->default('standar')->after('is_permanent');
                // standar = 1 cabang, enterprise = maks 6 cabang + transfer stok
            }
        });

        // Set all existing admins to 'standar' by default
        DB::table('users')
            ->where('role_id', 2) // Admin role
            ->where('is_super_admin', 0)
            ->update(['paket' => 'standar']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'paket')) {
                $table->dropColumn('paket');
            }
        });
    }
};
