<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'cabang_id', 'nama_cabang', 'nama_toko', 'alamat', 'nama_pemilik', 'no_wa', 'email',
        'paket', 'jumlah_user', 'jumlah_perangkat',
        'status', 'durasi', 'nominal_bayar', 'bukti_transfer', 'catatan', 'admin_note',
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

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'processing' => 'Diproses',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'aktif' => 'Aktif',
            'nonaktif' => 'Nonaktif',
            'expired' => 'Expired',
            default => ucfirst($this->status),
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'pending' => 'badge-pending',
            'processing' => 'badge-proses',
            'approved', 'aktif' => 'badge-selesai',
            'rejected' => 'badge-dibatalkan',
            'nonaktif' => 'badge-proses',
            'expired' => 'badge-dibatalkan',
            default => 'badge-masuk',
        };
    }

    public function durasiLabel(): string
    {
        return match ($this->durasi) {
            'standard_1_tahun'  => 'Standard — 1 Tahun',
            'enterprise_1_tahun'=> 'Enterprise — 1 Tahun',
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
            'standard_1_tahun', 'enterprise_1_tahun' => 365,
            '1_bulan' => 30,
            '3_bulan' => 90,
            '6_bulan' => 180,
            '1_tahun' => 365,
            'permanen' => 0, // legacy data lama
            default => 365,
        };
    }

    /**
     * Paket label yang mudah dibaca (Standard / Enterprise).
     */
    public function paketLabel(): string
    {
        return match ($this->paket) {
            'enterprise' => 'Enterprise',
            'standar' => 'Standard',
            default => $this->paket ?? 'Standard',
        };
    }
}
