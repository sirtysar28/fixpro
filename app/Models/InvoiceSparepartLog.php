<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSparepartLog extends Model
{
    protected $table = 'invoice_sparepart_logs';

    protected $fillable = [
        'invoice_sparepart_id', 'user_id', 'aksi', 'deskripsi', 'data_lama', 'data_baru',
    ];

    protected $casts = [
        'data_lama' => 'array',
        'data_baru' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoice()
    {
        return $this->belongsTo(InvoiceSparepart::class, 'invoice_sparepart_id');
    }
}
