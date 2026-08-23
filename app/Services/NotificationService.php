<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notifikasi email ke Super Admin.
 *
 * - Saat user baru mendaftar → Super Admin dapat notifikasi.
 * - Saat kode aktivasi (serial number) di-redeem → Super Admin dapat notifikasi.
 *
 * Email tujuan diambil dari Super Admin aktif di DB, fallback ke ADMIN_EMAIL env.
 * Pengiriman di-queue (Mail::to()->queue() jika ada job runner) atau langsung,
 * dan error TIDAK memblokir flow utama (ditangkap + di-log).
 */
class NotificationService
{
    /** Ambil daftar email tujuan notifikasi (Super Admin) */
    public static function adminRecipients(): array
    {
        $emails = User::where('is_super_admin', true)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn($e) => strtolower(trim($e)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Fallback ke env ADMIN_EMAIL kalau tidak ada Super Admin
        if (empty($emails)) {
            $fallback = trim((string) (env('ADMIN_EMAIL') ?? ''));
            if ($fallback !== '') {
                $emails[] = strtolower($fallback);
            }
        }

        return $emails;
    }

    /**
     * Kirim email notifikasi dengan aman (tidak lempar exception ke flow utama).
     * @param callable $callback menerima (string $recipient) → Mailable/closure Mail
     */
    protected static function safeSend(callable $buildMail, string $contextLabel): void
    {
        $recipients = self::adminRecipients();
        if (empty($recipients)) {
            Log::warning("NotificationService [{$contextLabel}]: tidak ada email Super Admin tujuan.");
            return;
        }

        foreach ($recipients as $email) {
            try {
                $mailable = $buildMail($email);
                if ($mailable === null) continue;
                Mail::to($email)->send($mailable);
            } catch (\Throwable $e) {
                // Jangan sampai error email menggagalkan registrasi/aktivasi
                Log::warning("NotificationService [{$contextLabel}] gagal kirim ke {$email}: " . $e->getMessage());
            }
        }
    }

    /**
     * Notifikasi: User baru mendaftar.
     */
    public static function notifyUserRegistered(User $user, bool $isPermanent = false, ?string $activationCode = null): void
    {
        self::safeSend(function ($recipient) use ($user, $isPermanent, $activationCode) {
            $cabang = $user->cabang?->nama ?? '-';
            $trialEnds = $user->login_expires_at?->translatedFormat('d F Y H:i');
            $status = $isPermanent ? 'AKTIF PERMANEN (pakai kode aktivasi)' : 'TRIAL 1 BULAN';

            $lines = [
                "Halo Admin FixPro,",
                "",
                "Ada user baru yang mendaftar di sistem FixPro:",
                "",
                "Nama        : {$user->name}",
                "Email       : {$user->email}",
                "No. HP      : " . ($user->phone ?? '-'),
                "Cabang/Toko: {$cabang}",
                "Status      : {$status}",
            ];

            if ($activationCode) {
                $lines[] = "Kode Aktivasi: {$activationCode}";
            }
            if (!$isPermanent && $trialEnds) {
                $lines[] = "Trial berakhir: {$trialEnds}";
            }

            $lines[] = "";
            $lines[] = "Waktu daftar: " . now()->setTimezone('Asia/Jakarta')->format('d F Y H:i:s') . ' WIB';
            $lines[] = "";
            $lines[] = "Anda bisa melihat detail & mengelola akun di dashboard Super Admin:";
            $lines[] = config('app.url') . '/user-management';
            $lines[] = "";
            $lines[] = "---";
            $lines[] = "Email ini dikirim otomatis oleh sistem FixPro.";

            $subject = $isPermanent
                ? "[FixPro] Registrasi Baru (Aktivasi Kode) — {$user->name}"
                : "[FixPro] Registrasi Baru (Trial) — {$user->name}";

            return self::textMailable($subject, $lines);
        }, 'register');
    }

    /**
     * Notifikasi: Kode aktivasi (serial number) berhasil di-redeem.
     *
     * @param string $source 'register' | 'profile'
     */
    public static function notifyActivationCodeRedeemed(User $user, string $serialCode, string $source = 'profile'): void
    {
        self::safeSend(function ($recipient) use ($user, $serialCode, $source) {
            $cabang = $user->cabang?->nama ?? '-';
            $sourceLabel = $source === 'register' ? 'saat registrasi' : 'melalui halaman Profil';

            $lines = [
                "Halo Admin FixPro,",
                "",
                "Sebuah KODE AKTIVASI telah berhasil di-redeem {$sourceLabel}:",
                "",
                "Nama          : {$user->name}",
                "Email         : {$user->email}",
                "No. HP        : " . ($user->phone ?? '-'),
                "Cabang/Toko  : {$cabang}",
                "Kode Aktivasi : {$serialCode}",
                "Status Akun   : AKTIF PERMANEN",
                "",
                "Waktu redeem : " . now()->setTimezone('Asia/Jakarta')->format('d F Y H:i:s') . ' WIB',
                "",
                "Detail kode aktivasi & lisensi:",
                config('app.url') . '/serial-number',
                "",
                "---",
                "Email ini dikirim otomatis oleh sistem FixPro.",
            ];

            $subject = "[FixPro] Kode Aktivasi Redeem — {$user->name} ({$serialCode})";

            return self::textMailable($subject, $lines);
        }, 'activation_redeem');
    }

    /**
     * Helper: buat mailable teks sederhana (tanpa view kompleks, anti-dependency).
     */
    protected static function textMailable(string $subject, array $lines)
    {
        return new class($subject, $lines) extends \Illuminate\Mail\Mailable {
            public function __construct(
                public string $subjectText,
                public array $lines,
            ) {}

            public function build()
            {
                return $this->subject($this->subjectText)
                    ->text('emails.plain', ['lines' => $this->lines, 'subject' => $this->subjectText]);
            }
        };
    }
}
