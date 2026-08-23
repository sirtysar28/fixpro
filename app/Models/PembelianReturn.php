<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Riwayat retur pembelian (barang dikembalikan ke supplier).
 * Stok berkurang, nilai pembelian berkurang, hutang supplier disesuaikan.
 */
class PembelianReturn extends Model
{
    use HasFactory;

    protected $table = 'pembelian_returns';

    protected $fillable = [
        'pembelian_id', 'cabang_id', 'user_id', 'kode',
        'stok_id', 'nama_barang', 'qty', 'harga_retur', 'nilai',
        'alasan', 'tanggal',
    ];

    protected $casts = [
        'tanggal'     => 'date',
        'harga_retur' => 'decimal:2',
        'nilai'       => 'decimal:2',
    ];

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stok()
    {
        return $this->belongsTo(Stok::class);
    }
}
