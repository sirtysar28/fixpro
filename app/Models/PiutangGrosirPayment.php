<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PiutangGrosirPayment extends Model
{
    protected $fillable = [
        'penjualan_grosir_id', 'cabang_id', 'user_id', 'tanggal', 'jml', 'metode', 'catatan',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'jml' => 'decimal:2',
    ];

    public function penjualan() { return $this->belongsTo(PenjualanGrosir::class, 'penjualan_grosir_id'); }
    public function cabang() { return $this->belongsTo(Cabang::class); }
    public function user() { return $this->belongsTo(User::class); }
}
