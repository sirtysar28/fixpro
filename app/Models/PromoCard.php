<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PromoCard extends Model
{
    use HasFactory;

    protected $table = 'promo_cards';

    protected $fillable = [
        'judul',
        'deskripsi',
        'gambar',
        'tombol_text',
        'tombol_link',
        'aktif',
        'urutan',
    ];

    protected $casts = ['aktif' => 'boolean'];

    /**
     * Card promo aktif (urut berdasarkan urutan lalu terbaru).
     */
    public static function getAktif()
    {
        return static::where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * URL gambar siap pakai (storage publik atau URL eksternal).
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->gambar) {
            return null;
        }

        return str_starts_with($this->gambar, 'http')
            ? $this->gambar
            : Storage::url($this->gambar);
    }
}
