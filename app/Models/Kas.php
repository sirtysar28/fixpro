<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kas extends Model
{
    use HasFactory;

    protected $fillable = ['tipe', 'cabang_id', 'kategori', 'jml', 'ket', 'ref', 'waktu', 'saldo', 'metode', 'bukti'];
    protected $casts = [
        'waktu' => 'datetime',
        'jml' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];
    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }
}
