<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerIklan extends Model
{
    use HasFactory;

    protected $table = 'banner_iklan';
    protected $fillable = ['judul', 'deskripsi', 'gambar', 'link', 'aktif', 'urutan'];

    protected $casts = ['aktif' => 'boolean'];

    public static function getAktif()
    {
        return static::where('aktif', true)->orderBy('urutan')->get();
    }
}
