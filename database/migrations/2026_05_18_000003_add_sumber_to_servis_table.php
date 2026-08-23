<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servis', function (Blueprint $table) {
            $table->string('sumber')->default('admin')->after('cabang_id'); // 'admin' atau 'user'
        });

        // Update existing: data yang dibuat via my-service store (tanpa cabang_id) = user
        \DB::table('servis')->whereNull('cabang_id')->update(['sumber' => 'user', 'cabang_id' => 1]);
        \DB::table('servis')->where('cabang_id', '>', 0)->where('sumber', 'admin')->update(['sumber' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('servis', function (Blueprint $table) {
            $table->dropColumn('sumber');
        });
    }
};
