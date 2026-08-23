<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SerialNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_code',
        'email',
        'is_used',
        'used_at',
        'used_by_user_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Generate serial number dari email user.
     * Format: FIXPRO-{hash dari email}-{random}
     */
    public static function generateFromEmail(string $email, int $createdBy): self
    {
        $emailHash = strtoupper(substr(md5($email), 0, 8));
        $random = strtoupper(Str::random(6));
        $serialCode = "FP-{$emailHash}-{$random}";

        return self::create([
            'serial_code' => $serialCode,
            'email' => $email,
            'is_used' => false,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Redeem serial number - aktifkan akun permanen
     */
    public function redeem(User $user): bool
    {
        if ($this->is_used) {
            return false;
        }

        if ($this->email !== $user->email) {
            return false;
        }

        $this->update([
            'is_used' => true,
            'used_at' => now(),
            'used_by_user_id' => $user->id,
        ]);

        $user->update([
            'is_permanent' => true,
            'login_expires_at' => null,
        ]);

        return true;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }
}
