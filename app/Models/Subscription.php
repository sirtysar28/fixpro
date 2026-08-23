<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Fitur Paket Berlangganan.
 * Aktivasi paket akan mengisi/memperpanjang users.login_expires_at.
 */
class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'cabang_id', 'package', 'kode',
        'duration_months', 'amount', 'started_at', 'ends_at',
        'status', 'note', 'activated_by',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'started_at'      => 'datetime',
        'ends_at'         => 'datetime',
    ];

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    /** Paket berlangganan yang tersedia (semua 3 bulan) */
    public static function packages(): array
    {
        return [
            'standar'    => ['label' => 'Standar — 3 Bulan',    'duration_months' => 3, 'max_cabang' => 1, 'desc' => '1 cabang, cocok untuk toko kecil'],
            'enterprise' => ['label' => 'Enterprise — 3 Bulan', 'duration_months' => 3, 'max_cabang' => 4, 'desc' => '1 pusat + maks 3 cabang anak + transfer stok'],
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function activator()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->ends_at
            && now()->lt($this->ends_at);
    }

    /** Sisa hari aktif (0 bila sudah lewat) */
    public function daysLeft(): int
    {
        if (!$this->ends_at) return 0;
        return max(0, (int) now()->diffInDays($this->ends_at, false));
    }

    public static function generateKode(): string
    {
        $date = now()->format('ymd');
        $last = self::where('kode', 'like', "SUB-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->kode, -4) + 1 : 1;
        return "SUB-$date-" . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
