<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Admin, Staff, User
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default roles
        DB::table('roles')->insert([
            ['name' => 'Admin', 'description' => 'Akses penuh semua fitur', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Staff', 'description' => 'Akses transaksi dan servis', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'User', 'description' => 'Daftar servis HP saja', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
