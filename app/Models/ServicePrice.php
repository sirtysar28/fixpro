<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePrice extends Model
{
    use HasFactory;

    protected $table = 'service_prices';
    protected $fillable = [
        'cabang_id', 'merk_hp', 'tipe_hp', 'kerusakan', 'deskripsi',
        'harga_jasa', 'kategori', 'aktif', 'created_by',
    ];

    protected $casts = [
        'harga_jasa' => 'decimal:2',
        'aktif' => 'boolean',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
