<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Fitur #11 — Offline Sync
 * Idempotent: client_ref (UUID client) bersifat unik → tidak duplikat saat sinkron ulang.
 */
class SyncQueue extends Model
{
    use HasFactory;

    protected $table = 'sync_queue';

    protected $fillable = [
        'user_id', 'cabang_id', 'device_id',
        'client_ref', 'entity_type', 'action',
        'payload', 'client_id', 'server_id',
        'status', 'error_message',
        'client_created_at', 'synced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'client_created_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CONFLICT  = 'conflict';
}
