<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanGrosirItem extends Model
{
    protected $fillable = [
        'penjualan_grosir_id', 'stok_id', 'kode', 'nama',
        'qty', 'harga_satuan', 'modal_satuan', 'subtotal',
    ];

    protected $casts = [
        'qty' => 'integer',
        'harga_satuan' => 'decimal:2',
        'modal_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function penjualan() { return $this->belongsTo(PenjualanGrosir::class, 'penjualan_grosir_id'); }
    public function stok() { return $this->belongsTo(Stok::class); }
}
