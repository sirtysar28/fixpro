<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $table = 'stock_transfers';
    protected $fillable = [
        'stok_id', 'from_cabang_id', 'to_cabang_id',
        'qty', 'harga_satuan', 'kode', 'catatan', 'user_id',
    ];

    protected $casts = [
        'qty' => 'integer',
        'harga_satuan' => 'decimal:2',
    ];

    public function stok()
    {
        return $this->belongsTo(Stok::class);
    }

    public function fromCabang()
    {
        return $this->belongsTo(Cabang::class, 'from_cabang_id');
    }

    public function toCabang()
    {
        return $this->belongsTo(Cabang::class, 'to_cabang_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function generateKode(): string
    {
        $date = now()->format('ymd');
        $last = static::where('kode', 'like', "TRF-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->kode, -3) + 1 : 1;
        return "TRF-$date-" . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
}
