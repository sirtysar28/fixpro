<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * Log an activity
     */
    public static function log(
        string $module,
        string $action,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
        ?int $cabangId = null,
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'cabang_id' => $cabangId ?? Auth::user()?->getActiveCabangId(),
        ]);
    }

    /**
     * Log login
     */
    public static function login(?int $userId = null): AuditLog
    {
        return self::log('auth', 'login', 'User berhasil login', userId: $userId);
    }

    /**
     * Log logout
     */
    public static function logout(): AuditLog
    {
        $user = Auth::user();
        return self::log('auth', 'logout', "User {$user?->name} logout");
    }

    /**
     * Log create
     */
    public static function created(string $module, string $description, Model $model, ?array $newValues = null): AuditLog
    {
        return self::log($module, 'create', $description, $model, newValues: $newValues);
    }

    /**
     * Log update
     */
    public static function updated(string $module, string $description, Model $model, ?array $oldValues = null, ?array $newValues = null): AuditLog
    {
        return self::log($module, 'update', $description, $model, oldValues: $oldValues, newValues: $newValues);
    }

    /**
     * Log delete
     */
    public static function deleted(string $module, string $description, Model $model): AuditLog
    {
        return self::log($module, 'delete', $description, $model);
    }

    /**
     * Log custom action (redeem, generate, toggle, etc)
     */
    public static function custom(string $module, string $action, string $description): AuditLog
    {
        return self::log($module, $action, $description);
    }
}
