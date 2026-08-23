<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Http\Request;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
        'phone',
        'role_id',
        'cabang_id',
        'teknisi_id',
        'is_active',
        'is_super_admin',
        'login_expires_at',
        'is_permanent',
        'paket',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
            'login_expires_at' => 'datetime',
            'is_permanent' => 'boolean',
        ];
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isAdmin(): bool
    {
        return $this->role && $this->role->name === 'Admin';
    }

    public function isStaff(): bool
    {
        return $this->role && $this->role->name === 'Staff';
    }

    public function isUser(): bool
    {
        return $this->role && $this->role->name === 'User';
    }

    /**
     * Check if user is Admin Cabang (Admin tapi bukan Super Admin)
     */
    public function isCabangAdmin(): bool
    {
        return $this->isAdmin() && !$this->is_super_admin;
    }

    public function isEnterprise(): bool
    {
        return $this->paket === 'enterprise';
    }

    public function isStandar(): bool
    {
        return !$this->is_super_admin && $this->paket !== 'enterprise';
    }

    public function maxCabang(): int
    {
        if ($this->is_super_admin) return 999;
        return $this->isEnterprise() ? 4 : 1; // 1 pusat + 3 anak
    }

    /**
     * Maksimal cabang ANAK yang boleh dibuat (1 pusat + 3 anak = 4 total).
     */
    public function maxChildCabang(): int
    {
        if ($this->is_super_admin) return 999;
        return $this->isEnterprise() ? 3 : 0;
    }

    /**
     * Jumlah cabang ANAK dalam group user ini.
     */
    public function countChildCabang(): int
    {
        if ($this->is_super_admin) {
            return \App\Models\Cabang::whereNotNull('parent_cabang_id')->count();
        }
        $ids = $this->getAllowedCabangIds();
        return \App\Models\Cabang::whereIn('id', $ids)
            ->whereNotNull('parent_cabang_id')
            ->count();
    }

    /**
     * Langganan aktif user (paket berlangganan 3 bulan).
     */
    public function activeSubscription()
    {
        try {
            return \App\Models\Subscription::where('user_id', $this->id)
                ->where('status', \App\Models\Subscription::STATUS_ACTIVE)
                ->latest('ends_at')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Info ringkas sisa hari langganan untuk topbar.
     * Menggabungkan subscription aktif + login_expires_at.
     */
    public function subscriptionSummary(): ?array
    {
        try {
            return $this->_subscriptionSummary();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function _subscriptionSummary(): ?array
    {
        if ($this->is_super_admin) {
            return ['type' => 'super_admin', 'label' => 'Super Admin', 'days_left' => null, 'ends_at' => null];
        }

        $sub = $this->activeSubscription();
        if ($sub && $sub->isActive()) {
            return [
                'type'       => 'subscription',
                'package'    => $sub->package,
                'label'      => ucfirst($sub->package),
                'days_left'  => $sub->daysLeft(),
                'ends_at'    => $sub->ends_at,
                'kode'       => $sub->kode,
            ];
        }

        // Fallback ke trial / permanent
        if ($this->is_permanent) {
            return ['type' => 'permanent', 'label' => 'Permanen', 'days_left' => null, 'ends_at' => null];
        }

        $days = $this->daysUntilExpiry();
        if ($days !== null) {
            return [
                'type'      => 'trial',
                'label'     => 'Trial',
                'days_left' => $days,
                'ends_at'   => $this->login_expires_at,
                'kode'      => null,
            ];
        }

        return null;
    }

    /**
     * Cabang utama (pusat) milik user.
     * - Kalau users.cabang_id sudah terisi → pakai itu.
     * - Kalau NULL (akun lama / upgrade enterprise): cari cabang pusat grupnya
     *   (yang ia buat sendiri atau parent dari cabang anaknya).
     * Mengembalikan null bila user memang belum punya cabang.
     *
     * FIX "admin enterprise tidak bisa edit daftar sparepart":
     * sebelumnya fallback langsung ke cabang 1 (milik toko lain) sehingga semua
     * guard cabang menolak akses edit stok milik sendiri.
     */
    public function getDefaultCabangId(): ?int
    {
        if (!empty($this->cabang_id)) {
            return (int) $this->cabang_id;
        }

        if ($this->isEnterprise() && $this->isAdmin()) {
            // 1. Cabang pusat (tanpa parent) yang dibuat user ini
            $pusatId = \App\Models\Cabang::where('created_by_user_id', $this->id)
                ->whereNull('parent_cabang_id')->orderBy('id')->value('id');
            if ($pusatId) {
                return (int) $pusatId;
            }

            // 2. Parent dari cabang anak yang dibuat user ini
            $parentId = \App\Models\Cabang::where('created_by_user_id', $this->id)
                ->whereNotNull('parent_cabang_id')->orderBy('id')->value('parent_cabang_id');
            if ($parentId) {
                return (int) $parentId;
            }

            // 3. Cabang apa pun yang dibuat user ini
            $anyId = \App\Models\Cabang::where('created_by_user_id', $this->id)
                ->orderBy('id')->value('id');
            if ($anyId) {
                return (int) $anyId;
            }
        }

        return null;
    }

    /**
     * Get effective cabang_id: super admin & enterprise admin use session, others locked to their own
     */
    public function getActiveCabangId(): ?int
    {
        if ($this->isSuperAdmin()) {
            $session = session('cabang_id');
            if ($session === 'all') {
                return null; // null = all branches (dashboard only)
            }
            return (int) ($session ?? $this->cabang_id ?? 1);
        }
        // Enterprise admin: allow switching to child branches via session
        if ($this->isEnterprise() && $this->isAdmin()) {
            $session = session('cabang_id');
            if ($session !== null) {
                $allowedIds = $this->getAllowedCabangIds();
                if (in_array((int) $session, $allowedIds)) {
                    return (int) $session;
                }
            }
            // FIX: fallback ke cabang pusat grup SENDIRI, bukan cabang 1 (milik toko lain)
            return $this->getDefaultCabangId() ?? 1;
        }
        return (int) ($this->cabang_id ?? 1);
    }

    /**
     * Resolve cabang_id for API requests (mobile-friendly).
     * Checks query param first (for mobile), then session (for web), then default.
     * Returns null for super admin viewing all branches.
     */
    public function getApiCabangId(Request $request): ?int
    {
        if ($this->isSuperAdmin()) {
            // 1. Check query param (mobile API)
            $param = $request->query('cabang_id');
            if ($param !== null) {
                if ($param === 'all') {
                    return null; // null = all branches
                }
                return (int) $param;
            }
            // 2. Fallback to session (web)
            $session = session('cabang_id');
            if ($session !== null) {
                if ($session === 'all') {
                    return null;
                }
                return (int) $session;
            }
            // 3. Default to user's own branch
            return (int) ($this->cabang_id ?? 1);
        }

        // Enterprise admin: allow switching via query param or session
        if ($this->isEnterprise() && $this->isAdmin()) {
            $allowedIds = $this->getAllowedCabangIds();
            // 1. Check query param (mobile API)
            $param = $request->query('cabang_id');
            if ($param !== null && in_array((int) $param, $allowedIds)) {
                return (int) $param;
            }
            // 2. Fallback to session (web)
            $session = session('cabang_id');
            if ($session !== null && in_array((int) $session, $allowedIds)) {
                return (int) $session;
            }
            // 3. FIX: fallback ke cabang pusat grup sendiri (bukan cabang 1 milik toko lain)
            return $this->getDefaultCabangId() ?? 1;
        }

        // Non-enterprise: always locked to their own branch
        return (int) ($this->cabang_id ?? 1);
    }

    /**
     * Get effective cabang_id for mutations (always returns a valid int)
     * Use this for creating/editing records
     */
    public function getEffectiveCabangId(): int
    {
        $id = $this->getActiveCabangId();
        return $id ?? (int) ($this->cabang_id ?? 1);
    }

    /**
     * Get all cabang IDs this user is allowed to switch to.
     * For enterprise admin: includes their parent cabang + all child branches.
     * For super admin: all cabangs.
     * For others: only their own cabang.
     */
    public function getAllowedCabangIds(): array
    {
        if ($this->isSuperAdmin()) {
            return \App\Models\Cabang::pluck('id')->map(fn($id) => (int) $id)->toArray();
        }

        if ($this->isEnterprise() && $this->isAdmin()) {
            $ids = [];
            if (!empty($this->cabang_id)) {
                $ids[] = (int) $this->cabang_id;
            }
            // All cabang created by this user
            $createdIds = \Illuminate\Support\Facades\DB::table('cabang')
                ->where('created_by_user_id', $this->id)
                ->pluck('id')->map(fn($id) => (int) $id)->toArray();
            $ids = array_merge($ids, $createdIds);
            // Parent of child branches created by this user
            $parentIds = \Illuminate\Support\Facades\DB::table('cabang')
                ->where('created_by_user_id', $this->id)
                ->whereNotNull('parent_cabang_id')
                ->distinct()->pluck('parent_cabang_id')->map(fn($id) => (int) $id)->toArray();
            $ids = array_merge($ids, $parentIds);

            // FIX: sertakan SEMUA cabang dalam grup (pusat + seluruh anak-anaknya),
            // termasuk cabang anak yang dibuatkan oleh Super Admin / pihak lain.
            // Tanpa ini, admin pusat tidak bisa switch & mengelola cabang anak
            // tersebut → "tidak bisa edit daftar sparepart" di cabang itu.
            $pivotIds = array_values(array_unique(array_merge(
                array_filter([(int) ($this->cabang_id ?? 0)]),
                $parentIds
            )));
            if (!empty($pivotIds)) {
                $siblingIds = \Illuminate\Support\Facades\DB::table('cabang')
                    ->whereIn('parent_cabang_id', $pivotIds)
                    ->pluck('id')->map(fn($id) => (int) $id)->toArray();
                $ids = array_merge($ids, $siblingIds);
            }
            return array_values(array_unique($ids));
        }

        return [(int) ($this->cabang_id ?? 1)];
    }

    /**
     * Fitur #11 — Kategorisasi status aktivasi untuk hitung mundur.
     * Mengembalikan: 'aktif' | 'akan_berakhir' | 'segera_berakhir' | 'kedaluwarsa' | 'permanen' | 'super_admin'
     */
    public function subscriptionStatus(): string
    {
        if ($this->is_super_admin) return 'super_admin';
        if ($this->is_permanent) return 'permanen';

        $days = $this->daysUntilExpiry();
        if ($days === null) return 'permanen';
        if ($days <= 0) return 'kedaluwarsa';
        if ($days <= 7) return 'segera_berakhir';
        if ($days <= 30) return 'akan_berakhir';
        return 'aktif';
    }

    /** Label status aktivasi (untuk badge/UI) */
    public function subscriptionStatusLabel(): array
    {
        return match ($this->subscriptionStatus()) {
            'aktif'            => ['label' => 'Aktif',          'color' => '#16a34a', 'bg' => '#dcfce7', 'icon' => 'fa-check-circle'],
            'akan_berakhir'    => ['label' => 'Akan Berakhir',  'color' => '#92400e', 'bg' => '#fef3c7', 'icon' => 'fa-clock'],
            'segera_berakhir'  => ['label' => 'Segera Berakhir','color' => '#dc2626', 'bg' => '#fee2e2', 'icon' => 'fa-exclamation-triangle'],
            'kedaluwarsa'      => ['label' => 'Kedaluwarsa',    'color' => '#991b1b', 'bg' => '#fee2e2', 'icon' => 'fa-times-circle'],
            'permanen'         => ['label' => 'Permanen',       'color' => '#166534', 'bg' => '#dcfce7', 'icon' => 'fa-infinity'],
            'super_admin'      => ['label' => 'Super Admin',    'color' => '#92400e', 'bg' => '#fef3c7', 'icon' => 'fa-crown'],
            default            => ['label' => '-',              'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'fa-circle'],
        };
    }

    /** Apakah perlu menampilkan pengingat hitung mundur (30/15/7/3/1 hari)? */
    public function needsActivationReminder(): bool
    {
        if ($this->is_super_admin || $this->is_permanent) return false;
        $days = $this->daysUntilExpiry();
        if ($days === null || $days < 0) return false;
        return in_array($days, [30, 15, 7, 3, 1, 0]);
    }

    /**
     * Cek apakah login user masih berlaku
     */
    public function isLoginExpired(): bool
    {
        // Super Admin tidak pernah expired
        if ($this->is_super_admin) {
            return false;
        }

        // Jika akun permanen (sudah redeem serial/approved), tidak expired
        if ($this->is_permanent) {
            return false;
        }

        // Jika belum ada tanggal expired, berarti belum diset
        if (!$this->login_expires_at) {
            return false;
        }

        // Admin Cabang juga bisa expired (trial)
        return now()->isAfter($this->login_expires_at);
    }

    /**
     * Set expiry 1 bulan dari sekarang (untuk user biasa)
     */
    public function setTrialExpiry(): void
    {
        $this->update([
            'login_expires_at' => now()->addMonth(),
            'is_permanent' => false,
        ]);
    }

    /**
     * Sisa hari sebelum expired
     */
    public function daysUntilExpiry(): ?int
    {
        // Super Admin selalu null (tidak pernah expired)
        if ($this->is_super_admin) {
            return null;
        }

        // Permanen = tidak pernah expired
        if ($this->is_permanent) {
            return null;
        }

        if (!$this->login_expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->login_expires_at, false));
    }

    /**
     * Cek apakah user ini Admin Cabang (bukan Super Admin) yang sedang trial
     */
    public function isTrial(): bool
    {
        if ($this->is_super_admin || $this->is_permanent) {
            return false;
        }

        if (!$this->login_expires_at) {
            return false;
        }

        return true;
    }

    /**
     * Cek apakah user adalah Admin Cabang (Admin biasa, bukan Super Admin)
     */
    public function isAdminCabang(): bool
    {
        return $this->isAdmin() && !$this->is_super_admin;
    }

    public function teknisiProfile()
    {
        return $this->belongsTo(Teknisi::class, 'teknisi_id');
    }

    public function isTeknisi(): bool
    {
        return $this->role && $this->role->name === 'Teknisi';
    }

    public function isAdminCabangAnak(): bool
    {
        return $this->role && $this->role->name === 'Admin Cabang Anak';
    }

    /**
     * Cek apakah user punya akses admin (Admin biasa + Admin Cabang Anak, bukan Super Admin)
     */
    public function isAnyAdmin(): bool
    {
        return $this->isAdmin() || $this->isAdminCabangAnak();
    }

    public function pelanggan()
    {
        return $this->hasOne(Pelanggan::class);
    }

    public function hasAccess(string $feature): bool
    {
        if ($this->isSuperAdmin()) return true;
        if ($this->isAdmin()) return true;

        // Staff can access: dashboard, input servis, daftar servis, arsip
        $staffFeatures = ['dashboard', 'input-servis', 'daftar-servis', 'arsip', 'profil'];
        if ($this->isStaff()) return in_array($feature, $staffFeatures);

        // Teknisi can access: dashboard teknisi, profil, lihat servis sendiri
        $teknisiFeatures = ['dashboard', 'profil', 'my-servis', 'teknisi-dashboard'];
        if ($this->isTeknisi()) return in_array($feature, $teknisiFeatures);

        // User can only: dashboard, daftar servis (own), profil
        $userFeatures = ['dashboard', 'my-service', 'profil'];
        return in_array($feature, $userFeatures);
    }
}
