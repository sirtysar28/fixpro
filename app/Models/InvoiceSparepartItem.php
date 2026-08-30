<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSparepartItem extends Model
{
    protected $table = 'invoice_sparepart_items';

    protected $fillable = [
        'invoice_sparepart_id', 'stok_id', 'kode', 'nama', 'merk_hp', 'tipe_lcd',
        'qty', 'harga_satuan', 'jenis_harga', 'diskon', 'harga_modal', 'subtotal',
    ];

    protected $casts = [
        'harga_satuan' => 'decimal:2',
        'diskon' => 'decimal:2',
        'harga_modal' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public const JENIS_HARGA = ['retail', 'grosir1', 'grosir2', 'grosir3', 'reseller', 'member', 'khusus', 'manual'];

    public function invoice()
    {
        return $this->belongsTo(InvoiceSparepart::class, 'invoice_sparepart_id');
    }

    public function stok()
    {
        return $this->belongsTo(Stok::class);
    }

    public function labelJenisHarga(): string
    {
        return match ($this->jenis_harga) {
            'retail' => 'Retail',
            'grosir1' => 'Grosir 1',
            'grosir2' => 'Grosir 2',
            'grosir3' => 'Grosir 3',
            'reseller' => 'Reseller',
            'member' => 'Member',
            'khusus' => 'Khusus',
            'manual' => 'Manual',
            default => $this->jenis_harga,
        };
    }
}
