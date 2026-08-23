<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananGrosirItem extends Model
{
    protected $fillable = [
        'pesanan_grosir_id', 'stok_id', 'kode', 'nama', 'qty', 'harga_satuan', 'subtotal',
    ];

    protected $casts = [
        'qty' => 'integer',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function pesanan() { return $this->belongsTo(PesananGrosir::class, 'pesanan_grosir_id'); }
    public function stok() { return $this->belongsTo(Stok::class); }
}
