<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    // Admin = 1, Staff = 2, User = 3
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->name === 'Admin';
    }

    public function isStaff(): bool
    {
        return $this->name === 'Staff';
    }

    public function isUser(): bool
    {
        return $this->name === 'User';
    }
}
