<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'pembelians';

    protected $fillable = [
        'kode', 'cabang_id', 'user_id', 'diedit_oleh', 'diedit_pada',
        'supplier_nama', 'supplier_kontak', 'supplier_alamat',
        'tanggal', 'tanggal_jatuh_tempo',
        'subtotal', 'diskon_persen', 'diskon_nominal', 'biaya_tambahan', 'ongkir',
        'total', 'total_retur',
        'dibayar', 'sisa', 'status', 'status_transaksi', 'metode_bayar',
        'items', 'catatan',
    ];

    protected $casts = [
        'tanggal'            => 'date',
        'tanggal_jatuh_tempo'=> 'date',
        'diedit_pada'        => 'datetime',
        'subtotal'           => 'decimal:2',
        'diskon_persen'      => 'decimal:2',
        'diskon_nominal'     => 'decimal:2',
        'biaya_tambahan'     => 'decimal:2',
        'ongkir'             => 'decimal:2',
        'total'              => 'decimal:2',
        'total_retur'        => 'decimal:2',
        'dibayar'            => 'decimal:2',
        'sisa'               => 'decimal:2',
        'items'              => 'array',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Siapa yang terakhir mengedit pembelian ini */
    public function editor()
    {
        return $this->belongsTo(User::class, 'diedit_oleh');
    }

    /** Riwayat pembayaran hutang (bayar sebagian / lunas) */
    public function payments()
    {
        return $this->hasMany(PembelianPayment::class)->orderBy('tanggal')->orderBy('id');
    }

    /** Riwayat retur pembelian */
    public function returns()
    {
        return $this->hasMany(PembelianReturn::class)->orderBy('tanggal')->orderBy('id');
    }

    public function isLunas(): bool
    {
        return $this->status === 'Lunas';
    }

    public function isHutang(): bool
    {
        return in_array($this->status, ['Hutang', 'Belum Dibayar', 'Sebagian']) && (float) $this->sisa > 0;
    }

    public function isDraft(): bool
    {
        return $this->status_transaksi === 'Draft';
    }

    public function isDibatalkan(): bool
    {
        return $this->status_transaksi === 'Dibatalkan' || $this->status === 'Dibatalkan';
    }

    /** Total nilai pembelian DIKURANGI retur */
    public function totalAkhir(): float
    {
        return max(0, (float) $this->total - (float) $this->total_retur);
    }

    /** Sisa hutang setelah retur & pembayaran */
    public function sisaHutang(): float
    {
        if ($this->isDibatalkan()) return 0;
        return max(0, $this->totalAkhir() - (float) $this->dibayar);
    }

    /** Apakah hutang sudah jatuh tempo */
    public function isJatuhTempo(): bool
    {
        return $this->sisaHutang() > 0
            && $this->tanggal_jatuh_tempo !== null
            && $this->tanggal_jatuh_tempo->isPast();
    }

    public static function generateKode(): string
    {
        $date = now()->format('ymd');
        $last = self::where('kode', 'like', "PMB-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->kode, -3) + 1 : 1;
        return "PMB-$date-" . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    /** Total nilai item yang sudah diterima (sebelum retur) */
    public function totalItemsValue(): float
    {
        $sum = 0;
        if (is_array($this->items)) {
            foreach ($this->items as $it) {
                $sum += (float) ($it['subtotal'] ?? 0);
            }
        }
        return (float) $sum;
    }

    /** Total qty yang sudah pernah diretur untuk satu stok pada pembelian ini */
    public function qtyRetur(int $stokId): int
    {
        return (int) $this->returns()->where('stok_id', $stokId)->sum('qty');
    }

    /** Warna badge status pembayaran (dipakai di semua view pembelian) */
    public function statusBadge(): array
    {
        return match ($this->status) {
            'Lunas'         => ['bg' => '#dcfce7', 'color' => '#16a34a', 'label' => 'Lunas'],
            'Sebagian'      => ['bg' => '#fef3c7', 'color' => '#b45309', 'label' => 'Sebagian'],
            'Dibatalkan'    => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => 'Dibatalkan'],
            default         => ['bg' => '#fee2e2', 'color' => '#dc2626', 'label' => 'Hutang'],
        };
    }

    /** Warna badge status transaksi */
    public function statusTransaksiBadge(): array
    {
        return match ($this->status_transaksi) {
            'Draft'      => ['bg' => '#f1f5f9', 'color' => '#64748b'],
            'Diproses'   => ['bg' => '#dbeafe', 'color' => '#2563eb'],
            'Dibatalkan' => ['bg' => '#fee2e2', 'color' => '#dc2626'],
            default      => ['bg' => '#dcfce7', 'color' => '#16a34a'],
        };
    }
}
