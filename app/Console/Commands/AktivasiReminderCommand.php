<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Fitur #11 — Notifikasi Masa Aktif Aktivasi (Hitung Mundur)
 *
 * Mengirim pengingat WhatsApp otomatis ke Admin Cabang yang masa aktifnya
 * akan berakhir dalam 30 / 15 / 7 / 3 / 1 hari, atau sudah berakhir.
 *
 * Jalankan: php artisan aktivasi:reminder
 * Disarankan dijadwalkan tiap hari jam 09:00 (lihat routes/console.php / schedule).
 */
class AktivasiReminderCommand extends Command
{
    protected $signature = 'aktivasi:reminder';
    protected $description = 'Kirim pengingat WhatsApp untuk masa aktif lisensi yang akan berakhir (Fitur #11)';

    /** Ambang batas hari untuk pengingat */
    private const REMINDER_DAYS = [30, 15, 7, 3, 1, 0];

    public function handle(WhatsAppService $wa): int
    {
        $users = User::with('cabang')
            ->where('is_super_admin', false)
            ->where('is_permanent', false)
            ->whereNotNull('login_expires_at')
            ->where('is_active', true)
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $days = $user->daysUntilExpiry();
            if ($days === null || !in_array($days, self::REMINDER_DAYS, true)) {
                continue;
            }

            $phone = $this->resolvePhone($user);
            if (!$phone) {
                $skipped++;
                continue;
            }

            $message = $this->buildMessage($user, $days);
            $cabangId = $user->cabang_id;

            $result = $wa->sendAuto($phone, $message, $cabangId);

            if ($result['success']) {
                $sent++;
                AuditLogService::log('subscription', 'reminder', "Pengingat WA masa aktif ({$days} hari) terkirim ke {$user->name} ({$phone})");
                $this->info("✓ Terkirim ke {$user->name} ({$days} hari lagi)");
            } else {
                Log::warning("AktivasiReminder gagal kirim ke {$phone}: " . ($result['error'] ?? '-'));
                $this->warn("✗ Gagal kirim ke {$user->name}: " . ($result['error'] ?? '-'));
            }
        }

        $this->info("Selesai. Terkirim: {$sent}, Dilewati: {$skipped}");
        return self::SUCCESS;
    }

    private function resolvePhone(User $user): ?string
    {
        // Prioritas: phone user → phone dari pelanggan terkait
        $phone = $user->phone;
        if ($phone && strlen($phone) >= 9) return $phone;

        $pelanggan = $user->pelanggan;
        if ($pelanggan && $pelanggan->no_hp) return $pelanggan->no_hp;

        return null;
    }

    private function buildMessage(User $user, int $days): string
    {
        $nama = $user->name;
        $cabang = $user->cabang?->nama ?? 'FIXPRO';
        $expiresAt = $user->login_expires_at;
        $tanggal = $expiresAt ? $expiresAt->translatedFormat('d F Y') : '-';

        if ($days > 0) {
            $emoji = $days <= 3 ? '🚨' : ($days <= 7 ? '⚠️' : '⏰');
            return <<<MSG
{$emoji} *PENGINGAT MASA AKTIF FIXPRO*

Halo {$nama},

Masa aktif lisensi *FixPro* Anda di cabang *{$cabang}* akan berakhir dalam *{$days} hari*.

📅 Tanggal berakhir: {$tanggal}

Segera lakukan perpanjangan paket agar layanan tidak terputus. Hubungi Super Admin atau lakukan aktivasi ulang melalui menu *Paket Berlangganan* di aplikasi.

Terima kasih 🙏
— Tim FixPro
MSG;
        }

        // Expired (days = 0)
        return <<<MSG
🚨 *LISENSI FIXPRO BERAKHIR*

Halo {$nama},

Masa aktif lisensi *FixPro* Anda di cabang *{$cabang}* telah *berakhir* (sejak {$tanggal}).

Sebagian fitur tidak dapat digunakan hingga Anda melakukan perpanjangan paket. Silakan hubungi Super Admin untuk aktivasi ulang.

— Tim FixPro
MSG;
    }
}
