<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturGrosir extends Model
{
    protected $fillable = [
        'no_retur', 'cabang_id', 'user_id', 'penjualan_grosir_id', 'pelanggan_grosir_id',
        'nama_pelanggan', 'tanggal', 'total', 'metode', 'alasan',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'total' => 'decimal:2',
    ];

    public function cabang() { return $this->belongsTo(Cabang::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function penjualan() { return $this->belongsTo(PenjualanGrosir::class, 'penjualan_grosir_id'); }
    public function pelanggan() { return $this->belongsTo(PelangganGrosir::class, 'pelanggan_grosir_id'); }
    public function items() { return $this->hasMany(ReturGrosirItem::class); }

    public static function generateNoRetur(): string
    {
        $date = now()->format('ymd');
        $last = static::where('no_retur', 'like', "RTG-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->no_retur, -4) + 1 : 1;
        return "RTG-$date-" . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
