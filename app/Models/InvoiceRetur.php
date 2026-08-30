<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceRetur extends Model
{
    protected $table = 'invoice_returs';

    protected $fillable = [
        'no_retur', 'invoice_sparepart_id', 'user_id', 'cabang_id', 'tanggal', 'total', 'alasan',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'total' => 'decimal:2',
    ];

    public static function generateNoRetur(): string
    {
        $prefix = 'RTN-' . now()->format('ymd') . '-';
        $last = self::where('no_retur', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $seq = $last ? ((int) substr($last->no_retur, -4)) + 1 : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function items()
    {
        return $this->hasMany(InvoiceReturItem::class, 'invoice_retur_id');
    }

    public function invoice()
    {
        return $this->belongsTo(InvoiceSparepart::class, 'invoice_sparepart_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
