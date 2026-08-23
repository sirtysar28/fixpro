<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Fitur #8 — Payment Gateway
 * Catatan transaksi pembayaran online. Bisa dipakai polimorfik untuk berbagai payable.
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode', 'reference', 'provider', 'method_code', 'provider_ref',
        'payable_type', 'payable_id', 'cabang_id', 'user_id',
        'customer_name', 'customer_email', 'customer_phone',
        'amount', 'fee_customer', 'fee_merchant', 'total_bayar', 'diterima',
        'status', 'va_number', 'qr_string', 'pay_url', 'instructions',
        'expired_at', 'paid_at', 'raw_response', 'webhook_payload', 'catatan',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'fee_customer'   => 'decimal:2',
        'fee_merchant'   => 'decimal:2',
        'total_bayar'    => 'decimal:2',
        'diterima'       => 'decimal:2',
        'expired_at'     => 'datetime',
        'paid_at'        => 'datetime',
        'raw_response'   => 'array',
        'webhook_payload'=> 'array',
        'instructions'   => 'array',
    ];

    public const STATUS_PENDING  = 'pending';
    public const STATUS_PAID     = 'paid';
    public const STATUS_EXPIRED  = 'expired';
    public const STATUS_FAILED   = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    public function cabang() { return $this->belongsTo(Cabang::class); }
    public function user()   { return $this->belongsTo(User::class); }

    public function payable()
    {
        return $this->morphTo();
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public static function generateKode(): string
    {
        $date = now()->format('ymd');
        $last = self::where('kode', 'like', "PAY-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->kode, -4) + 1 : 1;
        return "PAY-$date-" . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    /** Mapping method_code → label ramah pengguna */
    public static function methodLabel(string $code): string
    {
        return [
            'VA_BCA'        => 'Virtual Account BCA',
            'VA_BNI'        => 'Virtual Account BNI',
            'VA_BRI'        => 'Virtual Account BRI',
            'VA_MANDIRI'    => 'Virtual Account Mandiri',
            'VA_PERMATA'    => 'Virtual Account Permata',
            'VA_CIMB'       => 'Virtual Account CIMB Niaga',
            'QRIS'          => 'QRIS (Semua e-wallet & bank)',
            'EWALLET_OVO'   => 'E-Wallet OVO',
            'EWALLET_DANA'  => 'E-Wallet DANA',
            'EWALLET_LINKAJA' => 'E-Wallet LinkAja',
            'EWALLET_SHOPEEPAY' => 'E-Wallet ShopeePay',
            'EWALLET_GOPAY' => 'E-Wallet GoPay',
            'BANK_BCA'      => 'Transfer Bank BCA',
            'BANK_MANDIRI'  => 'Transfer Bank Mandiri',
            'BANK_BNI'      => 'Transfer Bank BNI',
            'BANK_BRI'      => 'Transfer Bank BRI',
            'RETAIL_ALFAMART' => 'Alfamart',
            'RETAIL_INDOMARET' => 'Indomaret',
        ][$code] ?? $code;
    }

    /** Kelompok metode untuk ikon/UI */
    public static function methodGroup(string $code): string
    {
        if (str_starts_with($code, 'VA_'))   return 'va';
        if (str_starts_with($code, 'QRIS'))  return 'qris';
        if (str_starts_with($code, 'EWALLET')) return 'ewallet';
        if (str_starts_with($code, 'BANK'))  return 'bank';
        if (str_starts_with($code, 'RETAIL')) return 'retail';
        return 'other';
    }
}
