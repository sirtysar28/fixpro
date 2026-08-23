<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Support\Facades\App;

/**
 * Fitur Multi Bahasa — service untuk resolve teks terjemahan.
 *
 * Cara pakai di Blade:  {{ t('menu.dashboard', 'Dashboard') }}
 * Default bahasa = id (Indonesia). User ganti via switcher di pojok kanan atas.
 */
class LocalizationService
{
    private static ?array $dict = null;
    private static ?string $activeCode = null;

    /** Kode bahasa aktif saat ini */
    public function activeCode(): string
    {
        if (static::$activeCode) {
            return static::$activeCode;
        }

        $code = (string) (session('app_locale') ?? null);

        if ($code === '' || !$this->isAvailable($code)) {
            $code = Language::default()->code;
        }

        return static::$activeCode = $code;
    }

    public function isAvailable(string $code): bool
    {
        try {
            return Language::available()->contains('code', $code);
        } catch (\Throwable $e) {
            return $code === 'id';
        }
    }

    /** Kamus [group.key => value] untuk bahasa aktif */
    public function dictionary(): array
    {
        if (static::$dict !== null) {
            return static::$dict;
        }

        try {
            $lang = $this->activeModel();
            return static::$dict = Translation::dictionary($lang->id, $lang->code);
        } catch (\Throwable $e) {
            // Tabel belum ada (belum migrasi) → fallback pakai string ID saja
            return static::$dict = [];
        }
    }

    public function activeModel(): Language
    {
        try {
            $code = $this->activeCode();
            return Language::available()->firstWhere('code', $code) ?? Language::default();
        } catch (\Throwable $e) {
            // Fallback virtual model bila tabel bahasa belum tersedia
            return new Language(['code' => 'id', 'name' => 'Indonesia', 'native_name' => 'Bahasa Indonesia', 'flag' => '🇮🇩', 'is_active' => true, 'is_default' => true]);
        }
    }

    /**
     * Translate sebuah key.
     *
     * @param  string       $key      Format "group.key"
     * @param  string|null  $fallback Teks cadangan bila key tidak ditemukan / value kosong
     * @param  array        $params   Placeholder {name}
     */
    public function trans(string $key, ?string $fallback = null, array $params = []): string
    {
        $dict = $this->dictionary();
        $value = $dict[$key] ?? null;

        if ($value === null || trim($value) === '') {
            $value = $fallback ?? $key;
        }

        if ($params) {
            foreach ($params as $k => $v) {
                $value = str_replace('{' . $k . '}', (string) $v, $value);
            }
        }

        return $value;
    }

    /** Set bahasa aktif (disimpan ke session) */
    public function setLocale(string $code): void
    {
        if ($this->isAvailable($code)) {
            session(['app_locale' => $code]);
            App::setLocale($code);
            static::$dict = null;
            static::$activeCode = $code;
        }
    }
}
