<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = ['room_id', 'sender_id', 'receiver_id', 'cabang_id', 'message', 'is_read', 'is_bot'];

    protected $casts = [
        'is_read' => 'boolean',
        'is_bot' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(ChatRoom::class, 'room_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }
}
