<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HargaGrosir extends Model
{
    protected $fillable = [
        'cabang_id', 'stok_id',
        'harga_grosir1', 'harga_grosir2', 'harga_grosir3', 'harga_reseller', 'harga_member', 'harga_distributor',
        'min_qty_grosir1', 'min_qty_grosir2', 'min_qty_grosir3', 'aktif',
    ];

    protected $casts = [
        'harga_grosir1' => 'decimal:2',
        'harga_grosir2' => 'decimal:2',
        'harga_grosir3' => 'decimal:2',
        'harga_reseller' => 'decimal:2',
        'harga_member' => 'decimal:2',
        'harga_distributor' => 'decimal:2',
        'min_qty_grosir1' => 'integer',
        'min_qty_grosir2' => 'integer',
        'min_qty_grosir3' => 'integer',
        'aktif' => 'boolean',
    ];

    public function stok()
    {
        return $this->belongsTo(Stok::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    /** Ambil harga berdasarkan level (kolom dinamis) */
    public function hargaUntukLevel(string $level): ?float
    {
        $map = [
            'grosir1' => 'harga_grosir1',
            'grosir2' => 'harga_grosir2',
            'grosir3' => 'harga_grosir3',
            'reseller' => 'harga_reseller',
            'member' => 'harga_member',
            'distributor' => 'harga_distributor',
        ];
        $col = $map[$level] ?? null;
        if (!$col) return null;
        $val = $this->{$col};
        return $val !== null ? (float) $val : null;
    }
}
