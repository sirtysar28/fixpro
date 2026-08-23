<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagihanSparepartItem extends Model
{
    use HasFactory;

    protected $table = 'tagihan_sparepart_items';

    protected $fillable = [
        'tagihan_id', 'stok_id', 'nama_barang', 'qty', 'harga_satuan', 'subtotal',
    ];

    protected $casts = [
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function tagihan() { return $this->belongsTo(TagihanSparepart::class, 'tagihan_id'); }
    public function stok() { return $this->belongsTo(Stok::class); }
}
