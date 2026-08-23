<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanSparepart extends Model
{
    use HasFactory;

    protected $table = 'penjualan_sparepart';
    protected $fillable = [
        'stok_id', 'pelanggan_id', 'cabang_id', 'user_id',
        'kode', 'no_transaksi', 'qty', 'harga_satuan', 'total', 'modal_total', 'diskon',
        'metode_bayar', 'catatan', 'tanggal',
        'status', 'alasan_pembatalan', 'dibatalkan_oleh', 'dibatalkan_pada',
    ];

    protected $casts = [
        'qty' => 'integer',
        'harga_satuan' => 'decimal:2',
        'total' => 'decimal:2',
        'modal_total' => 'decimal:2',
        'diskon' => 'decimal:2',
        'tanggal' => 'date',
        'dibatalkan_pada' => 'datetime',
    ];

    public function stok() { return $this->belongsTo(Stok::class); }
    public function pelanggan() { return $this->belongsTo(Pelanggan::class); }
    public function cabang() { return $this->belongsTo(Cabang::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function dibatalkanOleh() { return $this->belongsTo(User::class, 'dibatalkan_oleh'); }

    public static function generateKode(): string
    {
        $date = now()->format('ymd');
        $last = static::where('kode', 'like', "SPR-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->kode, -3) + 1 : 1;
        return "SPR-$date-" . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    public static function generateNoTransaksi(): string
    {
        $date = now()->format('ymd');
        $last = static::where('no_transaksi', 'like', "TRX-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->no_transaksi, -4) + 1 : 1;
        return "TRX-$date-" . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
