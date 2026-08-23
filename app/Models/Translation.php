<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = ['language_id', 'group', 'key', 'value'];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Cache per-bahasa: [group.key => value]
     */
    public static function dictionary(int $languageId, string $code): array
    {
        return Cache::remember("translations.{$code}", now()->addMinutes(5), function () use ($languageId) {
            return static::where('language_id', $languageId)
                ->get(['group', 'key', 'value'])
                ->mapWithKeys(fn ($t) => ["{$t->group}.{$t->key}" => $t->value])
                ->toArray();
        });
    }

    public static function flushCache(?string $code = null): void
    {
        if ($code) {
            Cache::forget("translations.{$code}");
            return;
        }
        foreach (Language::pluck('code') as $c) {
            Cache::forget("translations.{$c}");
        }
    }

    protected static function booted(): void
    {
        static::saved(function (self $t) {
            $code = $t->language?->code;
            if ($code) Cache::forget("translations.{$code}");
        });
        static::deleted(function (self $t) {
            $code = $t->language?->code;
            if ($code) Cache::forget("translations.{$code}");
        });
    }
}
