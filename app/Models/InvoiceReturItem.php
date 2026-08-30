<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceReturItem extends Model
{
    protected $table = 'invoice_retur_items';

    protected $fillable = [
        'invoice_retur_id', 'invoice_sparepart_item_id', 'stok_id', 'nama',
        'qty', 'harga_satuan', 'subtotal',
    ];

    protected $casts = [
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function retur()
    {
        return $this->belongsTo(InvoiceRetur::class, 'invoice_retur_id');
    }

    public function stok()
    {
        return $this->belongsTo(Stok::class);
    }
}
