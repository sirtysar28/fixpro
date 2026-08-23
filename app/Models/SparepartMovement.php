<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SparepartMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sparepart_movements';

    protected $fillable = [
        'stok_id', 'cabang_id', 'user_id',
        'tipe', 'jenis',
        'qty', 'saldo', 'harga_satuan',
        'referensi', 'referensi_id', 'referensi_tipe',
        'pelaku_nama', 'metode',
        'catatan', 'waktu',
    ];

    protected $casts = [
        'waktu'         => 'datetime',
        'qty'           => 'integer',
        'saldo'         => 'integer',
        'harga_satuan'  => 'decimal:2',
    ];

    public function stok()
    {
        return $this->belongsTo(Stok::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Label ramah-manusia untuk jenis pergerakan.
     */
    public function labelJenis(): string
    {
        return match ($this->jenis) {
            'pembelian'         => 'Pembelian Supplier',
            'penjualan'         => 'Penjualan',
            'pemakaian_servis'  => 'Pemakaian Servis',
            'batal_pemakaian_servis' => 'Pengembalian Sparepart Servis',
            'retur_pembelian'   => 'Retur ke Supplier',
            'batal_penjualan'   => 'Pembatalan Penjualan',
            'batal_pembelian'   => 'Pembatalan Pembelian',
            'adjustment_naik'   => 'Penyesuaian (+)',
            'adjustment_turun'  => 'Penyesuaian (-)',
            'transfer_masuk'    => 'Transfer Masuk',
            'transfer_keluar'   => 'Transfer Keluar',
            'stok_awal'         => 'Stok Awal',
            'import'            => 'Import Excel',
            'edit_stok'         => 'Edit Stok',
            default             => ucfirst(str_replace('_', ' ', $this->jenis)),
        };
    }
}
