<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'module',
        'action',
        'description',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'cabang_id',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get icon for action type
     */
    public function getActionIcon(): string
    {
        return match ($this->action) {
            'login' => 'fas fa-sign-in-alt',
            'logout' => 'fas fa-sign-out-alt',
            'create' => 'fas fa-plus-circle',
            'update' => 'fas fa-edit',
            'delete' => 'fas fa-trash-alt',
            'redeem' => 'fas fa-key',
            'generate' => 'fas fa-cogs',
            'toggle' => 'fas fa-exchange-alt',
            'import' => 'fas fa-file-import',
            'export' => 'fas fa-file-export',
            'print' => 'fas fa-print',
            default => 'fas fa-circle',
        };
    }

    /**
     * Get color for action type
     */
    public function getActionColor(): string
    {
        return match ($this->action) {
            'login' => '#2563eb',
            'logout' => '#64748b',
            'create' => '#16a34a',
            'update' => '#f59e0b',
            'delete' => '#dc2626',
            'redeem' => '#0d9488',
            'generate' => '#8b5cf6',
            'toggle' => '#f97316',
            default => '#64748b',
        };
    }

    /**
     * Get badge CSS for module
     */
    public function getModuleBadge(): string
    {
        return match ($this->module) {
            'auth' => 'background:#dbeafe;color:#1e40af',
            'servis' => 'background:#fef3c7;color:#92400e',
            'pelanggan' => 'background:#e0e7ff;color:#3730a3',
            'teknisi' => 'background:#fce7f3;color:#9d174d',
            'stok' => 'background:#d1fae5;color:#065f46',
            'kas' => 'background:#dcfce7;color:#166534',
            'jual_beli' => 'background:#f3e8ff;color:#6b21a8',
            'penjualan_sparepart' => 'background:#fef9c3;color:#854d0e',
            'user_management' => 'background:#fee2e2;color:#991b1b',
            'serial_number' => 'background:#ccfbf1;color:#115e59',
            'cabang' => 'background:#e0f2fe;color:#075985',
            'settings' => 'background:#f1f5f9;color:#475569',
            'banner' => 'background:#fdf4ff;color:#86198f',
            'profile' => 'background:#f0fdf4;color:#166534',
            default => 'background:#f1f5f9;color:#475569',
        };
    }

    /**
     * Get module label
     */
    public function getModuleLabel(): string
    {
        return match ($this->module) {
            'auth' => 'Autentikasi',
            'servis' => 'Servis',
            'pelanggan' => 'Pelanggan',
            'teknisi' => 'Teknisi',
            'stok' => 'Stok',
            'kas' => 'Kas',
            'jual_beli' => 'Jual Beli',
            'penjualan_sparepart' => 'Penjualan Sparepart',
            'user_management' => 'Kelola Akun',
            'serial_number' => 'Serial Number',
            'cabang' => 'Cabang',
            'settings' => 'Pengaturan',
            'banner' => 'Banner',
            'profile' => 'Profil',
            default => ucfirst(str_replace('_', ' ', $this->module)),
        };
    }

    /**
     * Get action label in Indonesian
     */
    public function getActionLabel(): string
    {
        return match ($this->action) {
            'login' => 'Login',
            'logout' => 'Logout',
            'create' => 'Tambah',
            'update' => 'Ubah',
            'delete' => 'Hapus',
            'redeem' => 'Redeem',
            'generate' => 'Generate',
            'toggle' => 'Toggle',
            'import' => 'Import',
            'export' => 'Export',
            'print' => 'Cetak',
            default => ucfirst($this->action),
        };
    }
}
