<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteContent extends Model
{
    use HasFactory;

    protected $table = 'website_contents';

    protected $fillable = ['section', 'key', 'value', 'image', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * Get a single content value by section and key
     */
    public static function getContent(string $section, string $key, $default = null)
    {
        $item = static::where('section', $section)
            ->where('key', $key)
            ->where('is_active', true)
            ->first();
        return $item ? $item->value : $default;
    }

    /**
     * Get all content for a section as key => value
     */
    public static function getSection(string $section): array
    {
        return static::where('section', $section)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Get section with images as collection of objects
     */
    public static function getSectionWithImages(string $section)
    {
        return static::where('section', $section)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get JSON-decoded content
     */
    public static function getJson(string $section, string $key, $default = [])
    {
        $value = static::getContent($section, $key);
        if ($value) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : $default;
        }
        return $default;
    }

    /**
     * Get all active sections
     */
    public static function getAllSections(): array
    {
        $sections = static::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('section');

        $result = [];
        foreach ($sections as $section => $items) {
            $result[$section] = [];
            foreach ($items as $item) {
                $result[$section][$item->key] = $item;
            }
        }
        return $result;
    }
}
