<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stok extends Model
{
    use HasFactory;

    protected $fillable = ['kode', 'barcode', 'nama', 'kategori', 'merk_hp', 'stok', 'reserved', 'modal', 'jual', 'min_alert', 'satuan', 'foto', 'cabang_id'];
    protected $casts = [
        'stok' => 'integer',
        'reserved' => 'integer',
        'modal' => 'decimal:2',
        'jual' => 'decimal:2',
        'min_alert' => 'integer',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function movements()
    {
        return $this->hasMany(SparepartMovement::class);
    }

    public function hargaGrosir()
    {
        return $this->hasOne(HargaGrosir::class);
    }

    /** Stok tersedia = stok fisik - reservasi pesanan grosir */
    public function getStokTersediaAttribute(): int
    {
        return max(0, (int) $this->stok - (int) ($this->reserved ?? 0));
    }
}
