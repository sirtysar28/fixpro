<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSparepartPayment extends Model
{
    protected $table = 'invoice_sparepart_payments';

    protected $fillable = [
        'invoice_sparepart_id', 'user_id', 'jumlah', 'metode', 'tanggal', 'catatan',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tanggal' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(InvoiceSparepart::class, 'invoice_sparepart_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
