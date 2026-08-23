<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banner_iklan', function (Blueprint $table) {
            $table->longText('deskripsi')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('banner_iklan', function (Blueprint $table) {
            $table->text('deskripsi')->nullable()->change();
        });
    }
};
