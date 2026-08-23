<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananGrosir extends Model
{
    protected $fillable = [
        'no_pesanan', 'cabang_id', 'sumber_cabang_id', 'user_id', 'pelanggan_grosir_id',
        'nama_pelanggan', 'level_harga', 'tanggal', 'tanggal_selesai',
        'subtotal', 'diskon', 'total', 'status', 'alamat_kirim', 'catatan', 'penjualan_grosir_id',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'tanggal_selesai' => 'date',
        'subtotal' => 'decimal:2',
        'diskon' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function cabang() { return $this->belongsTo(Cabang::class); }
    public function sumberCabang() { return $this->belongsTo(Cabang::class, 'sumber_cabang_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function pelanggan() { return $this->belongsTo(PelangganGrosir::class, 'pelanggan_grosir_id'); }
    public function items() { return $this->hasMany(PesananGrosirItem::class); }
    public function penjualan() { return $this->belongsTo(PenjualanGrosir::class); }

    public function labelLevelHarga(): string
    {
        return match ($this->level_harga) {
            'grosir1' => 'Grosir 1',
            'grosir2' => 'Grosir 2',
            'grosir3' => 'Grosir 3',
            'reseller' => 'Reseller',
            'distributor' => 'Distributor',
            default => 'Eceran',
        };
    }

    public static function generateNoPesanan(): string
    {
        $date = now()->format('ymd');
        $last = static::where('no_pesanan', 'like', "PSN-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->no_pesanan, -4) + 1 : 1;
        return "PSN-$date-" . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
