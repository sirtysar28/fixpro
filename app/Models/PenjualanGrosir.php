<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanGrosir extends Model
{
    protected $fillable = [
        'no_nota', 'cabang_id', 'sumber_cabang_id', 'user_id', 'pelanggan_grosir_id',
        'nama_pelanggan', 'level_harga', 'tanggal', 'subtotal', 'diskon', 'total',
        'bayar', 'piutang', 'total_retur', 'jatuh_tempo', 'metode_bayar', 'status',
        'alamat_kirim', 'catatan', 'pesanan_grosir_id',
        'alasan_pembatalan', 'dibatalkan_oleh', 'dibatalkan_pada',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'jatuh_tempo' => 'date',
        'dibatalkan_pada' => 'datetime',
        'subtotal' => 'decimal:2',
        'diskon' => 'decimal:2',
        'total' => 'decimal:2',
        'bayar' => 'decimal:2',
        'piutang' => 'decimal:2',
        'total_retur' => 'decimal:2',
    ];

    public function cabang() { return $this->belongsTo(Cabang::class); }
    public function sumberCabang() { return $this->belongsTo(Cabang::class, 'sumber_cabang_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function pelanggan() { return $this->belongsTo(PelangganGrosir::class, 'pelanggan_grosir_id'); }
    public function items() { return $this->hasMany(PenjualanGrosirItem::class); }
    public function returs() { return $this->hasMany(ReturGrosir::class); }
    public function payments() { return $this->hasMany(PiutangGrosirPayment::class); }
    public function dibatalkanOleh() { return $this->belongsTo(User::class, 'dibatalkan_oleh'); }

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

    /** Sisa piutang terkini = total - bayar awal - retur potong piutang - pembayaran */
    public function sisaPiutang(): float
    {
        $potongRetur = (float) $this->returs()->where('metode', 'Potong Piutang')->sum('total');
        return max(0, (float) $this->total - (float) $this->bayar - $potongRetur - (float) $this->payments()->sum('jml'));
    }

    public static function generateNoNota(): string
    {
        $date = now()->format('ymd');
        $last = static::where('no_nota', 'like', "GSR-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->no_nota, -4) + 1 : 1;
        return "GSR-$date-" . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
