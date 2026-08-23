<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_bank', 'atas_nama', 'no_rekening', 'logo', 'aktif', 'catatan',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public function scopeOrderByDefault($query)
    {
        return $query->orderBy('nama_bank')->orderBy('atas_nama');
    }

    public static function getActiveBanks()
    {
        return self::aktif()->orderByDefault()->get();
    }
}
