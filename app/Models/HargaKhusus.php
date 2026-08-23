<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HargaKhusus extends Model
{
    protected $table = 'harga_khusus';
    protected $fillable = ['pelanggan_grosir_id', 'stok_id', 'harga'];

    protected $casts = ['harga' => 'decimal:2'];

    public function pelanggan()
    {
        return $this->belongsTo(PelangganGrosir::class, 'pelanggan_grosir_id');
    }

    public function stok()
    {
        return $this->belongsTo(Stok::class);
    }
}
