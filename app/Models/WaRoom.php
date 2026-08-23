<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Fitur #9 — WhatsApp Web
 * Satu room = satu percakapan dengan satu nomor WA.
 */
class WaRoom extends Model
{
    use HasFactory;

    protected $table = 'wa_rooms';

    protected $fillable = [
        'number', 'name', 'avatar', 'cabang_id',
        'last_message', 'last_direction', 'last_message_at',
        'unread', 'is_archived',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'unread' => 'integer',
        'is_archived' => 'boolean',
    ];

    public function messages()
    {
        return $this->hasMany(WaMessage::class, 'room_id')->orderBy('created_at', 'asc');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    /** Cari / buat room dari nomor WA */
    public static function resolveRoom(string $number, ?string $name = null, ?int $cabangId = null): self
    {
        $number = self::normalizeNumber($number);
        $room = self::firstOrCreate(
            ['number' => $number],
            ['name' => $name, 'cabang_id' => $cabangId]
        );

        if ($name && $room->name !== $name) {
            $room->update(['name' => $name]);
        }

        return $room;
    }

    /** Normalisasi nomor HP Indonesia → 62xxx */
    public static function normalizeNumber(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        } elseif (str_starts_with($number, '8')) {
            $number = '62' . $number;
        } elseif (!str_starts_with($number, '62')) {
            // sudah internasional atau aneh, biarkan
        }
        return $number;
    }
}
