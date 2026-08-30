<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cabang;
use App\Models\Role;
use App\Models\Pelanggan;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    private function checkAccess()
    {
        $user = auth()->user();
        // Super Admin dan Admin Cabang bisa akses
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    private function checkSuperAdmin()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang bisa mengakses halaman ini.');
        }
    }

    public function index()
    {
        $this->checkAccess();

        $currentUser = auth()->user();

        if ($currentUser->isSuperAdmin()) {
            $users = User::with(['role', 'cabang', 'pelanggan'])->orderBy('is_super_admin', 'desc')->orderBy('name')->get();
            $cabangs = Cabang::where('aktif', true)->orderBy('nama')->get();
        } elseif ($currentUser->isEnterprise() && $currentUser->isAdmin()) {
            // Enterprise admin: lihat user di semua cabang group-nya
            $allowedIds = $currentUser->getAllowedCabangIds();
            $users = User::with(['role', 'cabang', 'pelanggan'])
                ->whereIn('cabang_id', $allowedIds)
                ->orderBy('cabang_id')->orderBy('name')->get();
            $cabangs = Cabang::whereIn('id', $allowedIds)->where('aktif', true)->orderBy('nama')->get();
        } else {
            // Admin Cabang standar: hanya cabang sendiri
            $users = User::with(['role', 'cabang', 'pelanggan'])
                ->where('cabang_id', $currentUser->cabang_id)
                ->orderBy('name')->get();
            $cabangs = Cabang::where('id', $currentUser->cabang_id)->get();
        }

        $roles = Role::orderBy('id')->get();

        // Token aktivasi tersedia (pilihan di form edit) + token terpakai per user
        // supaya kolom Masa Berlaku jelas: paket apa & durasi berapa yang mengaktivasi.
        $availableCodes = \App\Models\ActivationCode::where('is_used', false)
            ->where('status', 'aktif')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();
        $codeByUser = \App\Models\ActivationCode::where('is_used', true)
            ->whereNotNull('used_by_user_id')
            ->get()
            ->keyBy('used_by_user_id');

        return view('user-management.index', compact('users', 'cabangs', 'roles', 'availableCodes', 'codeByUser'));
    }

    public function create()
    {
        $this->checkSuperAdmin();

        $cabangs = Cabang::where('aktif', true)->orderBy('nama')->get();
        $roles = Role::orderBy('id')->get();
        $availableCodes = collect();
        $codeByUser = collect();

        return view('user-management.index', compact('cabangs', 'roles', 'availableCodes', 'codeByUser'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:30',
            'role_id' => 'required|exists:roles,id',
            'cabang_id' => 'required|exists:cabang,id',
            'is_active' => 'nullable',
            'paket' => 'nullable|in:standar,enterprise',
        ]);

        // Admin Cabang hanya bisa buat di cabang sendiri, role terbatas Staff/User
        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin()) {
            if ($currentUser->isEnterprise() && $currentUser->isAdmin()) {
                // Enterprise admin: bisa buat akun di semua cabang group-nya
                $allowedIds = $currentUser->getAllowedCabangIds();
                if (!in_array((int) $validated['cabang_id'], $allowedIds)) {
                    return redirect()->route('user-management.index')->with('error', 'Anda hanya bisa menambah akun di cabang dalam group Anda.');
                }
                // Enterprise admin bisa buat Admin untuk cabang anak
                $role = Role::find($validated['role_id']);
                if ($validated['is_super_admin'] ?? false) {
                    return redirect()->route('user-management.index')->with('error', 'Hanya Super Admin yang bisa menambahkan Super Admin.');
                }
            } else {
                // Admin Cabang standar: hanya cabang sendiri
                if ($validated['cabang_id'] != $currentUser->cabang_id) {
                    return redirect()->route('user-management.index')->with('error', 'Anda hanya bisa menambah akun di cabang Anda sendiri.');
                }
                // Admin cabang tidak bisa buat Admin lain, hanya Staff & User
                $role = Role::find($validated['role_id']);
                if ($role && $role->name === 'Admin') {
                    return redirect()->route('user-management.index')->with('error', 'Admin Cabang tidak bisa membuat akun Admin lain.');
                }
                if ($validated['is_super_admin'] ?? false) {
                    return redirect()->route('user-management.index')->with('error', 'Hanya Super Admin yang bisa menambahkan Super Admin.');
                }
            }
        }

        // Cek apakah role-nya User biasa -> set trial 1 bulan
        $role = \App\Models\Role::find($validated['role_id']);
        $isRegularUser = $role && $role->name === 'User';

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'role_id' => $validated['role_id'],
                'cabang_id' => $validated['cabang_id'],
                'is_active' => $request->has('is_active'),
                'is_super_admin' => false,
                'is_permanent' => !$isRegularUser,
                'login_expires_at' => $isRegularUser ? now()->addMonth() : null,
                'paket' => $validated['paket'] ?? 'standar',
            ]);

            // Kalau role User biasa → otomatis buat data pelanggan
            if ($isRegularUser) {
                // Cek apakah sudah ada pelanggan dengan no_hp yang sama di cabang yang sama
                $existingPelanggan = Pelanggan::where('no_hp', $user->phone)
                    ->where('cabang_id', $validated['cabang_id'])->first();
                if ($existingPelanggan) {
                    // Link pelanggan yang sudah ada ke user ini
                    $existingPelanggan->update(['user_id' => $user->id]);
                } else {
                    Pelanggan::create([
                        'user_id' => $user->id,
                        'nama' => $user->name,
                        'no_hp' => $user->phone ?? '-',
                        'alamat' => null,
                        'cabang_id' => $validated['cabang_id'],
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        AuditLogService::log('user_management', 'create', "Menambahkan akun baru: {$validated['name']} ({$validated['email']}) — Role: {$role->name}" . ($isRegularUser ? ' — otomatis ditambahkan ke pelanggan' : ''));

        return redirect()->route('user-management.index')->with('success', 'Akun berhasil ditambahkan!' . ($isRegularUser ? ' Data pelanggan otomatis dibuat.' : ''));
    }

    public function show(User $user_management)
    {
        $this->checkSuperAdmin();
        return redirect()->route('user-management.index');
    }

    public function edit(User $user_management)
    {
        $this->checkSuperAdmin();
        return redirect()->route('user-management.index');
    }

    public function update(Request $request, User $user_management)
    {
        $this->checkAccess();

        $currentUser = auth()->user();
        // Admin Cabang hanya bisa edit user di cabang sendiri
        if (!$currentUser->isSuperAdmin() && $user_management->cabang_id != $currentUser->cabang_id) {
            return redirect()->route('user-management.index')->with('error', 'Anda hanya bisa mengedit akun di cabang Anda sendiri.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user_management->id,
            'password' => 'nullable|string|min:6|confirmed',
            'phone' => 'nullable|string|max:30',
            'role_id' => 'required|exists:roles,id',
            'cabang_id' => 'required|exists:cabang,id',
            'is_active' => 'nullable',
            'paket' => 'nullable|in:standar,enterprise',
            'activation_code_id' => 'nullable|exists:activation_codes,id',
        ]);

        // Admin Cabang: tidak bisa ubah role menjadi Admin, hanya Staff & User
        // Dan hanya bisa set cabang ke cabang sendiri
        if (!$currentUser->isSuperAdmin()) {
            if ($currentUser->isEnterprise() && $currentUser->isAdmin()) {
                // Enterprise admin: bisa edit user di semua cabang group-nya
                $allowedIds = $currentUser->getAllowedCabangIds();
                if (!in_array((int) $validated['cabang_id'], $allowedIds)) {
                    return redirect()->route('user-management.index')->with('error', 'Anda hanya bisa mengedit akun di cabang dalam group Anda.');
                }
            } else {
                // Admin Cabang standar: hanya cabang sendiri
                $role = Role::find($validated['role_id']);
                if ($role && $role->name === 'Admin') {
                    return redirect()->route('user-management.index')->with('error', 'Admin Cabang tidak bisa mengubah role menjadi Admin.');
                }
                if ($validated['cabang_id'] != $currentUser->cabang_id) {
                    return redirect()->route('user-management.index')->with('error', 'Anda hanya bisa mengedit akun di cabang Anda sendiri.');
                }
            }
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role_id' => $validated['role_id'],
            'cabang_id' => $validated['cabang_id'],
            'is_active' => $request->has('is_active'),
            'paket' => $validated['paket'] ?? $user_management->paket,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user_management->update($data);

        // Sinkron ke pelanggan: kalau role User → pastikan ada pelanggan
        $newRole = Role::find($validated['role_id']);
        if ($newRole && $newRole->name === 'User') {
            $existingPel = Pelanggan::where('user_id', $user_management->id)->first();
            if (!$existingPel) {
                $existingPel = Pelanggan::where('no_hp', $user_management->phone)
                    ->where('cabang_id', $validated['cabang_id'])->first();
                if ($existingPel) {
                    $existingPel->update(['user_id' => $user_management->id, 'nama' => $user_management->name]);
                } else {
                    Pelanggan::create([
                        'user_id' => $user_management->id,
                        'nama' => $user_management->name,
                        'no_hp' => $user_management->phone ?? '-',
                        'alamat' => null,
                        'cabang_id' => $validated['cabang_id'],
                    ]);
                }
            } else {
                // Update nama/no_hp di pelanggan juga
                $existingPel->update(['nama' => $user_management->name, 'no_hp' => $user_management->phone ?? '-']);
            }
        } else {
            // Kalau role bukan User → tetap sinkron nama ke pelanggan yang terhubung
            $pel = Pelanggan::where('user_id', $user_management->id)->first();
            if ($pel) {
                $pel->update(['nama' => $user_management->name, 'no_hp' => $user_management->phone ?? $pel->no_hp]);
            }
        }

        AuditLogService::log('user_management', 'update', "Mengupdate akun: {$user_management->name} ({$user_management->email})");

        // ===== Aktivasi via token (pilihan di form edit — Super Admin) =====
        // Jelas: role & paket user yang diaktivasi + masa berlaku baru tercantum di hasil.
        $tokenMsg = '';
        if (auth()->user()->isSuperAdmin() && !empty($validated['activation_code_id'])) {
            $code = \App\Models\ActivationCode::find($validated['activation_code_id']);
            if (!$code || $code->is_used) {
                return redirect()->route('user-management.index')->with('error', 'Token aktivasi tidak tersedia atau sudah dipakai. Pilih token lain.');
            }
            if ($code->activate($user_management)) {
                $user_management->refresh();
                $roleLabel = $user_management->role?->name ?? '-';
                $paketLabel = $code->paket === 'enterprise' ? 'Enterprise' : 'Standard';
                $sampai = $user_management->is_permanent ? 'Permanen' : ($user_management->login_expires_at?->format('d/m/Y') ?? '-');
                $tokenMsg = " Token {$code->code} dipakai — Role: {$roleLabel} · Paket: {$paketLabel} · {$code->durasiLabel()}. Masa berlaku s.d. {$sampai}.";
                AuditLogService::log('user_management', 'update', "Aktivasi token {$code->code} (Paket {$paketLabel} — {$code->durasiLabel()}) untuk {$user_management->email} [Role: {$roleLabel}] — masa berlaku s.d. {$sampai}");
            }
        }

        return redirect()->route('user-management.index')->with('success', 'Akun berhasil diupdate!' . $tokenMsg);
    }

    public function destroy(User $user_management)
    {
        $this->checkAccess();

        $currentUser = auth()->user();
        if (!$currentUser->isSuperAdmin()) {
            if ($currentUser->isEnterprise() && $currentUser->isAdmin()) {
                $allowedIds = $currentUser->getAllowedCabangIds();
                if (!in_array((int) $user_management->cabang_id, $allowedIds)) {
                    return redirect()->route('user-management.index')->with('error', 'Anda hanya bisa menghapus akun di cabang dalam group Anda.');
                }
            } else {
                if ($user_management->cabang_id != $currentUser->cabang_id) {
                    return redirect()->route('user-management.index')->with('error', 'Anda hanya bisa menghapus akun di cabang Anda sendiri.');
                }
            }
        }

        if ($user_management->isSuperAdmin()) {
            return redirect()->route('user-management.index')->with('error', 'Super Admin tidak bisa dihapus!');
        }
        AuditLogService::log('user_management', 'delete', "Menghapus akun: {$user_management->name} ({$user_management->email})");
        $user_management->delete();
        return redirect()->route('user-management.index')->with('success', 'Akun berhasil dihapus!');
    }

    public function toggleSuperAdmin(User $user)
    {
        $this->checkAccess();

        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->route('user-management.index')->with('error', 'Hanya Super Admin yang bisa mengubah status Super Admin!');
        }

        if ($user->id === auth()->id()) {
            return redirect()->route('user-management.index')->with('error', 'Tidak bisa mengubah status Super Admin sendiri!');
        }

        $user->update(['is_super_admin' => !$user->is_super_admin]);

        $status = $user->is_super_admin ? 'Super Admin' : 'Admin Cabang';
        AuditLogService::log('user_management', 'toggle', "Toggle Super Admin: {$user->name} → {$status}");
        return redirect()->route('user-management.index')->with('success', "{$user->name} sekarang menjadi {$status}.");
    }

    public function togglePaket(Request $request, User $user)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang bisa mengubah paket admin.');
        }

        $request->validate([
            'paket' => 'required|in:standar,enterprise',
        ]);

        $oldPaket = $user->paket;
        $user->update(['paket' => $request->paket]);

        AuditLogService::log('user_management', 'toggle', "Ubah paket {$user->name}: {$oldPaket} → {$request->paket}");
        return back()->with('success', "Paket {$user->name} diubah menjadi " . ucfirst($request->paket) . ".");
    }
}
