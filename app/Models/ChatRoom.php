<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'admin_id', 'cabang_id', 'last_message_at'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function messages()
    {
        return $this->hasMany(Chat::class, 'room_id');
    }

    public function unreadCount($userId)
    {
        return Chat::where('room_id', $this->id)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }
}
