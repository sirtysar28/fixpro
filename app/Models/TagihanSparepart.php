<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagihanSparepart extends Model
{
    use HasFactory;

    protected $table = 'tagihan_sparepart';

    protected $fillable = [
        'kode', 'cabang_id', 'user_id',
        'nama_toko', 'kontak_toko', 'alamat_toko',
        'tanggal', 'tanggal_jatuh_tempo',
        'subtotal', 'diskon_persen', 'diskon_nominal',
        'total', 'dibayar', 'sisa',
        'status', 'catatan',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'diskon_persen' => 'decimal:2',
        'diskon_nominal' => 'decimal:2',
        'total' => 'decimal:2',
        'dibayar' => 'decimal:2',
        'sisa' => 'decimal:2',
        'tanggal' => 'date',
        'tanggal_jatuh_tempo' => 'date',
    ];

    public function cabang() { return $this->belongsTo(Cabang::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(TagihanSparepartItem::class, 'tagihan_id'); }

    public static function generateKode(): string
    {
        $date = now()->format('ymd');
        $last = static::where('kode', 'like', "TGH-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->kode, -3) + 1 : 1;
        return "TGH-$date-" . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
}
