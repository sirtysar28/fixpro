<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Fitur Multi Bahasa — master data bahasa + terjemahan key/value.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== Master Data Bahasa =====
        if (!Schema::hasTable('languages')) {
            Schema::create('languages', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique();          // id, en, jv, su, ...
                $table->string('name', 60);                     // Indonesia, English, ...
                $table->string('native_name', 60)->nullable();  // Bahasa Indonesia, ...
                $table->string('flag', 12)->default('🌐');      // emoji flag
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);  // hanya 1 (id)
                $table->timestamps();
            });
        }

        // ===== Terjemahan (key → value per bahasa) =====
        if (!Schema::hasTable('translations')) {
            Schema::create('translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
                $table->string('group', 40)->default('app');    // app, menu, dashboard, ...
                $table->string('key', 191);
                $table->text('value')->nullable();
                $table->timestamps();

                $table->unique(['language_id', 'group', 'key']);
                $table->index(['language_id', 'group']);
            });
        }

        // Seed default: Indonesia (default) + English
        $now = now();

        $idLangId = DB::table('languages')->insertGetId([
            'code' => 'id', 'name' => 'Indonesia', 'native_name' => 'Bahasa Indonesia',
            'flag' => '🇮🇩', 'is_active' => true, 'is_default' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $enLangId = DB::table('languages')->insertGetId([
            'code' => 'en', 'name' => 'English', 'native_name' => 'English',
            'flag' => '🇬🇧', 'is_active' => true, 'is_default' => false, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Seed terjemahan default (key yang dipakai di UI)
        $rows = require __DIR__ . '/../seeders/_translation_seed_data.php';

        $batch = [];
        foreach ($rows as $group => $pairs) {
            foreach ($pairs as $key => $vals) {
                $batch[] = ['language_id' => $idLangId, 'group' => $group, 'key' => $key, 'value' => $vals['id'] ?? null, 'created_at' => $now, 'updated_at' => $now];
                $batch[] = ['language_id' => $enLangId, 'group' => $group, 'key' => $key, 'value' => $vals['en'] ?? null, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        // Chunk insert
        foreach (array_chunk($batch, 200) as $chunk) {
            DB::table('translations')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
        Schema::dropIfExists('languages');
    }
};
