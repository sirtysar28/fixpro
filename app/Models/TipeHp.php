<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipeHp extends Model
{
    use HasFactory;

    protected $table = 'tipe_hp';
    protected $fillable = ['merk', 'tipe', 'kategori', 'aktif'];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public function scopeByMerk($query, $merk)
    {
        return $query->where('merk', $merk);
    }

    /**
     * Get unique list of merk (brands)
     */
    public static function getMerks(): array
    {
        return self::where('aktif', true)->orderBy('merk')->distinct()->pluck('merk')->toArray();
    }

    /**
     * Get tipe HP by merk
     */
    public static function getByMerk(string $merk): array
    {
        return self::where('aktif', true)->where('merk', $merk)->orderBy('tipe')->pluck('tipe', 'id')->toArray();
    }
}
