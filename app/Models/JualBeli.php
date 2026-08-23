<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class JualBeli extends Model
{
    use HasFactory;

    protected $table = 'jual_belis';
    protected $fillable = [
        'kode', 'cabang_id', 'user_id',
        'tanggal', 'hp', 'imei', 'imei2', 'serial_number',
        'merk', 'model', 'warna', 'ram', 'kapasitas', 'battery_health',
        'tipe',
        // Harga lama (kompatibilitas)
        'harga', 'harga_beli',
        // Harga baru (Fitur #8)
        'harga_jual', 'modal_total', 'estimasi_laba',
        // Foto unit
        'foto_depan', 'foto_belakang', 'foto_samping', 'foto_imei',
        // Checklist & status pemeriksaan
        'checklist_kondisi', 'status_pemeriksaan',
        // Status unit & garansi
        'status_unit', 'garansi', 'garansi_hingga',
        // Riwayat harga
        'riwayat_harga',
        // Pembayaran & pelanggan
        'metode_bayar',
        'pelanggan', 'no_hp_pelanggan', 'kondisi', 'kelengkapan',
        'catatan', 'status', 'alasan_pembatalan',
    ];

    protected $casts = [
        'tanggal'            => 'date',
        'harga'              => 'decimal:2',
        'harga_beli'         => 'decimal:2',
        'harga_jual'         => 'decimal:2',
        'modal_total'        => 'decimal:2',
        'estimasi_laba'      => 'decimal:2',
        'battery_health'     => 'integer',
        'checklist_kondisi'  => 'array',
        'riwayat_harga'      => 'array',
        'garansi_hingga'     => 'date',
    ];

    /** Item-item checklist kondisi standar */
    public const CHECKLIST_ITEMS = [
        'face_id', 'lcd', 'touchscreen', 'kamera_depan', 'kamera_belakang',
        'speaker', 'mikrofon', 'wifi', 'bluetooth', 'sinyal',
        'charging', 'getar', 'flash', 'battery_health',
    ];

    public const STATUS_NORMAL     = 'Normal';
    public const STATUS_RUSAK      = 'Rusak';
    public const STATUS_BELUMDicek = 'Belum Dicek';

    public function cabang() { return $this->belongsTo(Cabang::class); }
    public function user()   { return $this->belongsTo(User::class); }

    public static function generateKode(): string
    {
        $date = now()->format('ymd');
        $last = static::where('kode', 'like', "JB-$date-%")->orderBy('id', 'desc')->first();
        $num = $last ? (int) substr($last->kode, -3) + 1 : 1;
        return "JB-$date-" . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    /** Hitung estimasi laba/rugi otomatis dari modal & harga jual */
    public function hitungEstimasiLaba(): ?float
    {
        $modal = (float) ($this->modal_total ?? $this->harga_beli ?? 0);
        $jual  = (float) ($this->harga_jual ?? $this->harga ?? 0);
        if ($modal <= 0 && $jual <= 0) return null;
        return $jual - $modal;
    }

    /** Apakah garansi masih berlaku? */
    public function garansiAktif(): bool
    {
        return $this->garansi && $this->garansi !== 'Tanpa Garansi'
            && $this->garansi_hingga && $this->garansi_hingga->isFuture();
    }

    /** Hitung tanggal berakhir garansi berdasarkan jenis */
    public static function hitungGaransiHingga(string $garansi, ?Carbon $dari = null): ?Carbon
    {
        $dari = $dari ?? now();
        return match ($garansi) {
            'Garansi 7 Hari'   => $dari->copy()->addDays(7),
            'Garansi 30 Hari'  => $dari->copy()->addDays(30),
            'Garansi 90 Hari'  => $dari->copy()->addDays(90),
            default            => null,
        };
    }

    /** Default checklist kosong (semua Belum Dicek) */
    public static function defaultChecklist(): array
    {
        return array_fill_keys(self::CHECKLIST_ITEMS, 'Belum Dicek');
    }

    /** Tambah entri riwayat harga (dipanggil saat harga berubah) */
    public function pushRiwayatHarga(float $hargaBeli, float $hargaJual, ?string $keterangan = null): void
    {
        $riwayat = $this->riwayat_harga ?? [];
        $riwayat[] = [
            'tanggal'   => now()->toDateTimeString(),
            'harga_beli'  => $hargaBeli,
            'harga_jual'  => $hargaJual,
            'keterangan'  => $keterangan,
            'user_id'     => auth()->id(),
        ];
        $this->riwayat_harga = $riwayat;
    }

    /** Label badge warna untuk status unit */
    public function statusUnitBadge(): array
    {
        return match ($this->status_unit) {
            'Ready Dijual'    => ['bg' => '#dcfce7', 'color' => '#166534'],
            'Booking'         => ['bg' => '#fef3c7', 'color' => '#92400e'],
            'Sedang Diservis' => ['bg' => '#dbeafe', 'color' => '#1e40af'],
            'Terjual'         => ['bg' => '#e0e7ff', 'color' => '#3730a3'],
            'Retur'           => ['bg' => '#fee2e2', 'color' => '#991b1b'],
            default           => ['bg' => '#f1f5f9', 'color' => '#475569'],
        };
    }
}
