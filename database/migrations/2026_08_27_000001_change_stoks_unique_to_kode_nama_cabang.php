<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kode barang kini boleh sama untuk barang yang berbeda.
 *
 * Case nyata: file import "lcd Vivo part 1.xlsx" berisi 36 barang yang memakai
 * kolom Kode sebagai TIPE LCD (OG, LF, HX, SS, ...) — kode berulang untuk tiap
 * model HP. Constraint lama (kode + cabang unik) membuat import hanya menghasilkan
 * 9 barang (jumlah kode unik) karena baris dengan kode sama saling menimpa.
 *
 * Unique key baru: (kode, nama, cabang_id) — kombinasi kode+nama tetap unik
 * per cabang, tapi kode sama boleh dipakai banyak barang berbeda.
 */
return new class extends Migration
{
    private const OLD_INDEXES = ['stoks_kode_unique', 'stoks_kode_cabang_unique'];
    private const NEW_INDEX = 'stoks_kode_nama_cabang_unique';

    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';
        $this->dropIndexIfExists($isSqlite, self::OLD_INDEXES);

        if ($this->indexExists($isSqlite, self::NEW_INDEX)) {
            return;
        }

        if ($isSqlite) {
            DB::statement('CREATE UNIQUE INDEX ' . self::NEW_INDEX . ' ON stoks (kode, nama, cabang_id)');
        } else {
            // MySQL/MariaDB: kode VARCHAR(255) & nama VARCHAR(1000) → pakai prefix 191
            // karakter per kolom string agar total index < 3072 byte (utf8mb4 = 4 byte/char).
            DB::statement('ALTER TABLE stoks ADD UNIQUE INDEX ' . self::NEW_INDEX . ' (kode(191), nama(191), cabang_id)');
        }
    }

    public function down(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';
        $this->dropIndexIfExists($isSqlite, [self::NEW_INDEX]);

        if ($this->indexExists($isSqlite, 'stoks_kode_cabang_unique')) {
            return;
        }

        if ($isSqlite) {
            DB::statement('CREATE UNIQUE INDEX stoks_kode_cabang_unique ON stoks (kode, cabang_id)');
        } else {
            DB::statement('ALTER TABLE stoks ADD UNIQUE INDEX stoks_kode_cabang_unique (kode(191), cabang_id)');
        }
    }

    private function indexExists(bool $isSqlite, string $name): bool
    {
        if ($isSqlite) {
            return collect(DB::select("PRAGMA index_list('stoks')"))->contains(fn ($i) => $i->name === $name);
        }
        return !empty(DB::select('SHOW INDEX FROM stoks WHERE Key_name = ?', [$name]));
    }

    private function dropIndexIfExists(bool $isSqlite, array $names): void
    {
        foreach ($names as $name) {
            if (!$this->indexExists($isSqlite, $name)) {
                continue;
            }
            if ($isSqlite) {
                DB::statement('DROP INDEX ' . $name);
            } else {
                DB::statement('ALTER TABLE stoks DROP INDEX ' . $name);
            }
        }
    }
};
