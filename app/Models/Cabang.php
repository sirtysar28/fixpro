<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    use HasFactory;

    protected $table = 'cabang';
    protected $fillable = ['nama', 'alamat', 'telp', 'aktif', 'tipe', 'created_by_user_id', 'parent_cabang_id'];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Cabang::class, 'parent_cabang_id');
    }

    public function children()
    {
        return $this->hasMany(Cabang::class, 'parent_cabang_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function teknisis()
    {
        return $this->hasMany(Teknisi::class);
    }

    public function servis()
    {
        return $this->hasMany(Servis::class);
    }

    public function kas()
    {
        return $this->hasMany(Kas::class);
    }

    /** Cek apakah cabang ini gudang (bukan toko) */
    public function isGudang(): bool
    {
        return ($this->tipe ?? 'toko') === 'gudang';
    }
}
