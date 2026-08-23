<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop global unique index on no_hp in pelanggans table
        // Pelanggan dengan no_hp sama bisa ada di cabang berbeda
        $indexRows = DB::select("SHOW INDEXES FROM pelanggans WHERE Column_name = 'no_hp' AND Non_unique = 0");

        foreach ($indexRows as $row) {
            Schema::table('pelanggans', function (Blueprint $table) use ($row) {
                $table->dropUnique($row->Key_name);
            });
        }

        // Ensure cabang_id column exists and is nullable
        if (Schema::hasColumn('pelanggans', 'cabang_id')) {
            // Make sure null pelanggans get a default cabang_id
            $defaultCabangId = DB::table('cabang')->orderBy('id')->value('id') ?? 1;
            DB::table('pelanggans')->whereNull('cabang_id')->update(['cabang_id' => $defaultCabangId]);
        }
    }

    public function down(): void
    {
        // Re-add unique constraint (not recommended if you have cross-branch duplicates now)
        try {
            Schema::table('pelanggans', function (Blueprint $table) {
                $table->string('no_hp')->unique()->change();
            });
        } catch (\Exception $e) {
            // If there are duplicates, just skip
        }
    }
};
