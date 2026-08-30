<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivationCode extends Model
{
    use HasFactory;

    protected $table = 'activation_codes';

    protected $fillable = [
        'code',
        'cabang_id',
        'status',
        'durasi',
        'paket',
        'jumlah_user',
        'is_used',
        'used_by_user_id',
        'used_at',
        'activated_at',
        'activated_by',
        'mulai_berlaku',
        'berakhir_berlaku',
        'created_by',
        'note',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'activated_at' => 'datetime',
        'mulai_berlaku' => 'datetime',
        'berakhir_berlaku' => 'datetime',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /** Apakah kode masih berlaku (aktif & belum expired) */
    public function berlaku(): bool
    {
        if ($this->status !== 'aktif') return false;
        if ($this->berakhir_berlaku && $this->berakhir_berlaku->isPast()) return false;
        return true;
    }

    public function statusBerlakuLabel(): string
    {
        if ($this->status === 'nonaktif') return 'Nonaktif';
        if ($this->berakhir_berlaku && $this->berakhir_berlaku->isPast()) return 'Expired';
        return 'Aktif';
    }

    /**
     * Generate kode aktivasi unik (tanpa menyimpan).
     * Format: FX-{UPPERCASE_RANDOM}
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = 'FX-' . strtoupper(\Illuminate\Support\Str::random(8));
        } while (self::where('code', $code)->exists());
        return $code;
    }

    /**
     * Generate kode aktivasi unik.
     * Format: FX-{UPPERCASE_RANDOM}
     */
    public static function generate(string $durasi = '1_bulan', ?int $createdBy = null, ?string $note = null): self
    {
        return self::create([
            'code'       => self::generateUniqueCode(),
            'durasi'     => $durasi,
            'created_by' => $createdBy,
            'note'       => $note,
        ]);
    }

    public function durasiLabel(): string
    {
        return match ($this->durasi) {
            'standard_1_tahun'  => 'Standard — 1 Tahun',
            'enterprise_1_tahun'=> 'Enterprise — 1 Tahun',
            '1_bulan'  => '1 Bulan',
            '3_bulan'  => '3 Bulan',
            '6_bulan'  => '6 Bulan',
            '1_tahun'  => '1 Tahun',
            'permanen' => 'Permanen',
            default    => $this->durasi,
        };
    }

    public function durasiDays(): int
    {
        return match ($this->durasi) {
            'standard_1_tahun', 'enterprise_1_tahun' => 365,
            '1_bulan'  => 30,
            '3_bulan'  => 90,
            '6_bulan'  => 180,
            '1_tahun'  => 365,
            'permanen' => 0, // legacy data lama
            default    => 365,
        };
    }

    /**
     * Aktifkan / perpanjang masa aktif user dengan kode ini.
     * @return bool true jika berhasil
     */
    public function activate(User $user): bool
    {
        if ($this->is_used) {
            return false;
        }

        if ($this->durasi === 'permanen') {
            $user->update([
                'is_permanent'      => true,
                'login_expires_at'  => null,
            ]);
        } else {
            $days = $this->durasiDays();
            $current = $user->login_expires_at;
            // Kalau masih aktif, tambah dari sisa; kalau expired, mulai dari sekarang
            $newExpiry = ($current && now()->lt($current))
                ? $current->addDays($days)
                : now()->addDays($days);
            $user->update([
                'is_permanent'      => false,
                'login_expires_at'  => $newExpiry,
            ]);
        }

        $this->update([
            'is_used'          => true,
            'used_at'          => now(),
            'used_by_user_id'  => $user->id,
        ]);

        return true;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }
}
