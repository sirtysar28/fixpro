<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Fitur #9 — WhatsApp Web
 * Pesan individual (in/out). dedup via message_id dari Fonnte.
 */
class WaMessage extends Model
{
    use HasFactory;

    protected $table = 'wa_messages';

    protected $fillable = [
        'room_id', 'message_id', 'from_number', 'to_number',
        'direction', 'type', 'message', 'media_url', 'caption',
        'filename', 'mime', 'status', 'sender_id', 'device_id',
        'is_auto', 'meta', 'received_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'received_at' => 'datetime',
        'is_auto' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(WaRoom::class, 'room_id');
    }
}
