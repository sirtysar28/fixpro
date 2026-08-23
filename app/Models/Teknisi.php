<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teknisi extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'alamat', 'no_wa', 'spesialisasi', 'aktif', 'cabang_id', 'bagi_hasil'];
    protected $casts = ['aktif' => 'boolean', 'bagi_hasil' => 'decimal:2'];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function servis()
    {
        return $this->hasMany(Servis::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'teknisi_id');
    }
}
