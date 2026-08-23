<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom ke tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('login_expires_at')->nullable()->after('is_super_admin');
            $table->boolean('is_permanent')->default(false)->after('login_expires_at');
        });

        // Set semua user yang sudah ada jadi permanen (backward compatibility)
        \DB::table('users')->update(['is_permanent' => true, 'login_expires_at' => null]);

        // Buat tabel serial_numbers
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_code')->unique();
            $table->string('email')->index();
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_numbers');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login_expires_at', 'is_permanent']);
        });
    }
};
