<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturGrosirItem extends Model
{
    protected $fillable = [
        'retur_grosir_id', 'stok_id', 'nama', 'qty', 'harga_satuan', 'subtotal',
    ];

    protected $casts = [
        'qty' => 'integer',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function retur() { return $this->belongsTo(ReturGrosir::class, 'retur_grosir_id'); }
    public function stok() { return $this->belongsTo(Stok::class); }
}
