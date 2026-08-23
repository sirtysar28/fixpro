<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Language extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'native_name', 'flag', 'is_active', 'is_default'];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    public function translations()
    {
        return $this->hasMany(Translation::class);
    }

    /** Bahasa default (id) */
    public static function default(): self
    {
        return static::where('is_default', true)->first()
            ?? static::where('code', 'id')->first()
            ?? static::firstOrFail();
    }

    /** Bahasa aktif saat ini (dari session user) */
    public static function active(): self
    {
        $code = (string) (session('app_locale') ?? 'id');
        return static::where('code', $code)->where('is_active', true)->first()
            ?? static::default();
    }

    /** Daftar bahasa yang bisa dipilih user (aktif saja) */
    public static function available(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('languages.active', now()->addMinutes(10), function () {
            return static::where('is_active', true)->orderBy('is_default', 'desc')->orderBy('name')->get();
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('languages.active'));
        static::deleted(fn () => Cache::forget('languages.active'));
    }
}
