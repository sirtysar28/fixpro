<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Riwayat pembayaran hutang supplier (bayar sebagian / bayar lunas).
 */
class PembelianPayment extends Model
{
    use HasFactory;

    protected $table = 'pembelian_payments';

    protected $fillable = [
        'pembelian_id', 'cabang_id', 'user_id',
        'tanggal', 'jumlah', 'metode', 'ref_kode', 'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah'  => 'decimal:2',
    ];

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    /** Siapa yang menerima / mencatat pembayaran */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
