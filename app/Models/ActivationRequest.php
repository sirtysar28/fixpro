<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'cabang_id', 'nama_toko', 'status', 'durasi',
        'nominal_bayar', 'bukti_transfer', 'catatan', 'admin_note',
        'approved_by', 'approved_at',
    ];

    protected $casts = [
        'nominal_bayar' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function durasiLabel(): string
    {
        return match ($this->durasi) {
            '1_bulan' => '1 Bulan',
            '3_bulan' => '3 Bulan',
            '6_bulan' => '6 Bulan',
            '1_tahun' => '1 Tahun',
            'permanen' => 'Permanen',
            default => $this->durasi,
        };
    }

    public function durasiDays(): int
    {
        return match ($this->durasi) {
            '1_bulan' => 30,
            '3_bulan' => 90,
            '6_bulan' => 180,
            '1_tahun' => 365,
            'permanen' => 0, // permanent
            default => 365,
        };
    }
}
