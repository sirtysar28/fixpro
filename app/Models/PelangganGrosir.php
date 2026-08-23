<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PelangganGrosir extends Model
{
    protected $fillable = [
        'cabang_id', 'user_id', 'kode', 'nama', 'no_hp', 'alamat', 'alamat_kirim',
        'tipe', 'level_harga', 'limit_piutang', 'aktif', 'catatan',
    ];

    protected $casts = [
        'limit_piutang' => 'decimal:2',
        'aktif' => 'boolean',
    ];

    public const TIPE = ['Umum', 'Member', 'Grosir', 'Reseller', 'Distributor'];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function penjualans()
    {
        return $this->hasMany(PenjualanGrosir::class);
    }

    public function hargaKhusus()
    {
        return $this->hasMany(HargaKhusus::class);
    }

    public function labelLevelHarga(): string
    {
        return match ($this->level_harga) {
            'grosir1' => 'Grosir 1',
            'grosir2' => 'Grosir 2',
            'grosir3' => 'Grosir 3',
            'reseller' => 'Reseller',
            'distributor' => 'Distributor',
            default => 'Eceran',
        };
    }

    public static function generateKode(?int $cabangId): string
    {
        $prefix = 'GRS';
        $last = static::where('kode', 'like', "$prefix-%")
            ->when($cabangId, fn($q) => $q->where('cabang_id', $cabangId))
            ->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->kode, -4) + 1 : 1;
        return $prefix . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
