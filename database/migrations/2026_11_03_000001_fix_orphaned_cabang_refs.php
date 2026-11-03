<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FIX SQLSTATE[23000] 1452 di sparepart_movements
 * (kejadian: Admin Cabang Anak transaksi → FK sparepart_movements_cabang_id_foreign gagal).
 *
 * Akar masalah:
 * - Kolom stoks.cabang_id tidak punya foreign key (migrasi lama hanya menambah kolom),
 *   sedangkan sparepart_movements.cabang_id PUNYA FK ke cabang.
 * - Model Cabang tidak memakai SoftDeletes → penghapusan cabang = HARD DELETE.
 *   Baris stok/user tetap bisa memegang id cabang yang sudah mati (orphan).
 * - Setiap pencatatan pergerakan stok yang memakai cabang_id orphan gagal insert
 *   ke sparepart_movements dan seluruh transaksi di-rollback.
 *
 * Solusi:
 * 1. Bersihkan semua referensi orphan (stoks & users) → NULL. (Idempotent)
 * 2. Pasang FK stoks.cabang_id & users.cabang_id → cabang.id ON DELETE SET NULL.
 *
 * Catatan produksi (fix errno 121 "Duplicate key on write or update"):
 * - Nama constraint FK itu UNIK seluruh database di MySQL/MariaDB. Nama default
 *   `stoks_cabang_id_foreign` ternyata sudah terpakai di server produksi (sisa
 *   setup lama) meski tidak terdeteksi via Schema::getForeignKeys().
 * - Karena itu deteksi dilakukan langsung ke information_schema, dan bila nama
 *   default bentrok → pakai nama alternatif (..._fx1, _fx2, dst).
 * - Bila information_schema tidak terbaca (shared hosting membatasi), FK DILEWATI
 *   (jangan sampai `php artisan migrate` gagal total — fix utamanya ada di
 *   SparepartMovementService + User model + pembersihan orphan di langkah 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        $isMysql = in_array(DB::getDriverName(), ['mysql', 'mariadb']);

        // 1) Bersihkan orphan: cabang_id yang tidak ada lagi di tabel cabang.
        //    (Aman diulang — pernah terlanjur jalan sebagian di server.)
        DB::table('stoks')
            ->whereNotNull('cabang_id')
            ->whereNotIn('cabang_id', DB::table('cabang')->select('id'))
            ->update(['cabang_id' => null]);

        DB::table('users')
            ->whereNotNull('cabang_id')
            ->whereNotIn('cabang_id', DB::table('cabang')->select('id'))
            ->update(['cabang_id' => null]);

        // 2) Pastikan FK terpasang
        $this->ensureCabangForeignKey('stoks', $isMysql);
        $this->ensureCabangForeignKey('users', $isMysql);
    }

    public function down(): void
    {
        // Best-effort: lepas FK pada stoks.cabang_id (users dikembalikan tidak disentuh)
        $isMysql = in_array(DB::getDriverName(), ['mysql', 'mariadb']);

        if ($isMysql) {
            $name = $this->findForeignKeyName('stoks');
            if ($name) {
                DB::statement("ALTER TABLE `stoks` DROP FOREIGN KEY `{$name}`");
            }
            return;
        }

        if ($this->hasForeignKeyOnColumn('stoks')) {
            Schema::table('stoks', function (Blueprint $table) {
                $table->dropForeign(['cabang_id']);
            });
        }
    }

    /* ==========================================================
       Helper — aman untuk MySQL/MariaDB produksi & SQLite testing
       ========================================================== */

    private function ensureCabangForeignKey(string $table, bool $isMysql): void
    {
        // Sudah ada FK (apa pun namanya) yang meng-cover kolom cabang_id? → skip
        if ($this->hasForeignKeyOnColumn($table, $isMysql)) {
            return;
        }

        if (!$isMysql) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreign('cabang_id')->references('id')->on('cabang')->nullOnDelete();
            });
            return;
        }

        $name = $this->pickConstraintName($table, $isMysql);
        DB::statement(
            "ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` "
            . "FOREIGN KEY (`cabang_id`) REFERENCES `cabang` (`id`) ON DELETE SET NULL"
        );
    }

    private function hasForeignKeyOnColumn(string $table, ?bool $isMysql = null): bool
    {
        $isMysql = $isMysql ?? in_array(DB::getDriverName(), ['mysql', 'mariadb']);

        if (!$isMysql) {
            // SQLite (testing): introspeksi via Schema, deteksi per kolom
            foreach (Schema::getForeignKeys($table) as $fk) {
                $cols = $fk->columns ?? [];
                sort($cols);
                if ($cols === ['cabang_id']) {
                    return true;
                }
            }
            return false;
        }

        // MySQL/MariaDB: cek LANGSUNG ke information_schema (bukan via
        // Schema::getForeignKeys() yang terbukti bisa melewatkan constraint
        // di server tertentu → errno 121 saat ALTER duplikat).
        try {
            $row = DB::selectOne("
                SELECT COUNT(*) AS c
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = 'cabang_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$table]);
            return (int) ($row->c ?? 0) > 0;
        } catch (\Throwable $e) {
            // information_schema tak terbaca → anggap sudah ada agar migrate
            // tidak gagal total di shared hosting
            return true;
        }
    }

    /**
     * Pilih nama constraint yang BELUM dipakai di database ini.
     * Nama constraint unik seluruh database → bentrok = errno 121.
     */
    private function pickConstraintName(string $table, bool $isMysql): string
    {
        $default = $table . '_cabang_id_foreign';
        if (!$isMysql) {
            return $default;
        }

        try {
            $name = $default;
            $i = 1;
            while ($this->constraintNameExists($name)) {
                $name = $default . '_fx' . $i++;
            }
            return $name;
        } catch (\Throwable $e) {
            return $default . '_fx' . substr(uniqid(), -6);
        }
    }

    private function constraintNameExists(string $name): bool
    {
        // Constraint (UNIQUE/PK/FK/CHECK)
        $row = DB::selectOne("
            SELECT COUNT(*) AS c
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND CONSTRAINT_NAME = ?
        ", [$name]);
        if ((int) ($row->c ?? 0) > 0) {
            return true;
        }

        // Index biasa (non-unique) tidak tercatat di TABLE_CONSTRAINTS, tapi tetap
        // bikin bentrok nama saat MySQL membuat index implisit untuk FK (errno 121)
        $idx = DB::selectOne("
            SELECT COUNT(*) AS c
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND INDEX_NAME = ?
        ", [$name]);
        return (int) ($idx->c ?? 0) > 0;
    }

    /**
     * Cari nama FK aktual yang meng-cover stoks.cabang_id (untuk down()).
     */
    private function findForeignKeyName(string $table): ?string
    {
        try {
            $row = DB::selectOne("
                SELECT CONSTRAINT_NAME AS name
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = 'cabang_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ", [$table]);
            return $row->name ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
};
