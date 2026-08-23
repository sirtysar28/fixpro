<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servis extends Model
{
    use HasFactory;

    protected $table = 'servis';
    protected $fillable = [
        'kode', 'pelanggan_id', 'cabang_id', 'sumber', 'perangkat', 'keluhan', 'tipe', 'status',
        'biaya', 'dp', 'modal_sparepart', 'tanggal', 'teknisi_id', 'prioritas',
        'imei', 'catatan', 'garansi', 'eta', 'foto', 'spareparts',
        'diambil', 'tgl_diambil', 'tanggal_garansi',
        'alasan_pembatalan', 'dibatalkan_oleh', 'dibatalkan_pada',
    ];
    protected $casts = [
        'tanggal' => 'date',
        'eta' => 'datetime',
        'tgl_diambil' => 'datetime',
        'tanggal_garansi' => 'date',
        'biaya' => 'decimal:2',
        'dp' => 'decimal:2',
        'modal_sparepart' => 'decimal:2',
        'garansi' => 'integer',
        'diambil' => 'boolean',
        'foto' => 'array',
        'spareparts' => 'array',
        'dibatalkan_pada' => 'datetime',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function teknisi()
    {
        return $this->belongsTo(Teknisi::class);
    }

    public function dibatalkanOleh()
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }
}
