<?php

namespace App\Console\Commands;

use App\Models\Cabang;
use App\Models\Stok;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Perbaiki data admin enterprise yang bermasalah.
 *
 * Masalah: admin enterprise dengan users.cabang_id = NULL (akun lama /
 * di-upgrade setelah registrasi) membuat semua guard cabang jatuh ke
 * cabang 1 (milik toko lain) → tidak bisa edit daftar sparepart,
 * pembelian, dll, karena data miliknya ada di cabang grupnya sendiri.
 *
 * Solusi: isi users.cabang_id dengan cabang pusat grupnya
 * (yang ia buat sendiri, atau parent dari cabang anaknya).
 *
 * Jalankan di server: php artisan enterprise:fix-cabang
 */
class FixEnterpriseCabang extends Command
{
    protected $signature = 'enterprise:fix-cabang
                            {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Perbaiki users.cabang_id admin enterprise yang masih NULL (penyebab tidak bisa edit daftar sparepart)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $users = User::whereNull('cabang_id')
            ->where('is_super_admin', false)
            ->where('paket', 'enterprise')
            ->get();

        if ($users->isEmpty()) {
            $this->info('✓ Tidak ada admin enterprise dengan cabang_id NULL. Semua sudah benar.');
        }

        $fixed = 0;
        foreach ($users as $user) {
            $defaultId = $user->getDefaultCabangId();

            if ($defaultId === null) {
                $this->warn("⚠ {$user->email}: belum memiliki cabang sama sekali — lewati (buat cabang dulu di menu Multi Cabang).");
                continue;
            }

            $cabang = Cabang::find($defaultId);
            if ($dryRun) {
                $this->line("DRY-RUN • {$user->email}: cabang_id NULL → {$defaultId} ({$cabang?->nama})");
                continue;
            }

            $user->update(['cabang_id' => $defaultId]);
            $this->info("✓ {$user->email}: cabang_id diisi {$defaultId} ({$cabang?->nama})");
            $fixed++;
        }

        // Info tambahan: sparepart tanpa cabang (data pra-multi-cabang)
        $nullStok = Stok::whereNull('cabang_id')->count();
        if ($nullStok > 0) {
            $this->warn("ℹ Terdapat {$nullStok} sparepart dengan cabang_id NULL (data lama sebelum multi-cabang).");
            $this->line('  Sparepart tersebut hanya bisa dikelola Super Admin atau saat aktif di cabang default (1).');
            $this->line('  Jika seluruh sparepart NULL itu milik satu toko, Super Admin bisa memindahkannya manual via DB.');
        }

        if (!$dryRun) {
            $this->newLine();
            $this->info($fixed > 0 ? "Selesai: {$fixed} akun diperbaiki." : 'Selesai.');
        }

        return self::SUCCESS;
    }
}
