<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSparepart extends Model
{
    protected $table = 'invoice_spareparts';

    protected $fillable = [
        'no_invoice', 'cabang_id', 'sumber_cabang_id', 'user_id',
        'pelanggan_grosir_id', 'nama_pelanggan', 'no_wa', 'alamat', 'tipe_pelanggan',
        'tanggal', 'subtotal', 'diskon_item', 'diskon_total', 'total', 'total_retur',
        'dibayar', 'sisa', 'metode_bayar', 'status', 'jatuh_tempo',
        'approval_diskon_oleh', 'alasan_void', 'void_oleh', 'void_pada',
        'updated_by', 'catatan',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'subtotal' => 'decimal:2',
        'diskon_item' => 'decimal:2',
        'diskon_total' => 'decimal:2',
        'total' => 'decimal:2',
        'total_retur' => 'decimal:2',
        'dibayar' => 'decimal:2',
        'sisa' => 'decimal:2',
        'jatuh_tempo' => 'date',
        'void_pada' => 'datetime',
    ];

    public const METODE = ['Tunai', 'Transfer', 'QRIS', 'DP', 'Tempo'];
    public const STATUS = ['Lunas', 'Sebagian', 'Piutang', 'Dibatalkan'];

    /** Generate nomor invoice otomatis: INV-YYMMDD-0001 */
    public static function generateNoInvoice(): string
    {
        $prefix = 'INV-' . now()->format('ymd') . '-';
        $last = self::where('no_invoice', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $seq = $last ? ((int) substr($last->no_invoice, -4)) + 1 : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function items()
    {
        return $this->hasMany(InvoiceSparepartItem::class, 'invoice_sparepart_id');
    }

    public function payments()
    {
        return $this->hasMany(InvoiceSparepartPayment::class, 'invoice_sparepart_id');
    }

    public function logs()
    {
        return $this->hasMany(InvoiceSparepartLog::class, 'invoice_sparepart_id')->latest();
    }

    public function returs()
    {
        return $this->hasMany(InvoiceRetur::class, 'invoice_sparepart_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function sumberCabang()
    {
        return $this->belongsTo(Cabang::class, 'sumber_cabang_id');
    }

    public function pelanggan()
    {
        return $this->belongsTo(PelangganGrosir::class, 'pelanggan_grosir_id');
    }

    public function approvalDiskonOleh()
    {
        return $this->belongsTo(User::class, 'approval_diskon_oleh');
    }

    public function isVoid(): bool
    {
        return $this->status === 'Dibatalkan';
    }

    public function isLunas(): bool
    {
        return $this->status === 'Lunas';
    }

    public function hasPiutang(): bool
    {
        return in_array($this->status, ['Piutang', 'Sebagian']) && (float) $this->sisa > 0;
    }

    public function isJatuhTempo(): bool
    {
        return $this->hasPiutang() && $this->jatuh_tempo && $this->jatuh_tempo->isPast();
    }

    public function badgeStatus(): string
    {
        return match ($this->status) {
            'Lunas' => 'badge-selesai',
            'Sebagian' => 'badge-proses',
            'Piutang' => 'badge-pending',
            'Dibatalkan' => 'badge-dibatalkan',
            default => 'badge-masuk',
        };
    }

    /** Terapkan pembayaran: update dibayar, sisa, dan status */
    public function applyPayment(float $jumlah): void
    {
        $dibayar = (float) $this->dibayar + $jumlah;
        $netTotal = max(0, (float) $this->total - (float) $this->total_retur);
        $sisa = max(0, $netTotal - $dibayar);

        $this->update([
            'dibayar' => $dibayar,
            'sisa' => $sisa,
            'status' => $sisa <= 0 ? 'Lunas' : ($dibayar > 0 ? 'Sebagian' : $this->status),
        ]);
    }
}
