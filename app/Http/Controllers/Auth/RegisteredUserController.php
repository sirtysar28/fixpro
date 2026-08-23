<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SerialNumber;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'nama_toko' => ['nullable', 'string', 'max:255'],
            // Kode aktivasi opsional — kalau diisi harus cocok dengan email
            'activation_code' => ['nullable', 'string', 'max:50'],
        ]);

        // ===== Validasi kode aktivasi (jika diisi) =====
        // Kode aktivasi = Serial Number yang dibuat Super Admin untuk email tertentu.
        // Cocok → akun langsung permanen. Tidak cocok → error spesifik.
        $isPermanent = false;
        $serial = null;

        $activationCode = trim((string) $request->activation_code);
        if ($activationCode !== '') {
            // Normalisasi ke UPPERCASE (format serial: FP-XXXXXXXX-XXXXXX).
            // Collation MySQL default sudah case-insensitive, jadi match fleksibel.
            $normalizedCode = strtoupper($activationCode);
            $serial = SerialNumber::where('serial_code', $normalizedCode)->first();

            if (!$serial) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['activation_code' => 'Kode aktivasi salah. Periksa kembali kode yang diberikan oleh Admin Pusat.']);
            }

            if ($serial->is_used) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['activation_code' => 'Kode aktivasi sudah pernah digunakan. Minta kode baru kepada Admin Pusat.']);
            }

            // Cek kecocokan dengan email yang didaftarkan
            if (strtolower($serial->email) !== strtolower($request->email)) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['activation_code' => 'Kode aktivasi tidak cocok dengan email yang Anda daftarkan. Kode ini diperuntukkan untuk email lain.']);
            }

            // Valid lolos → tandai akan jadi permanen
            $isPermanent = true;
        }

        // Default role is Admin Cabang (1) for public registration
        DB::beginTransaction();
        try {
            // Auto-create cabang for this admin
            $namaToko = $request->nama_toko ?: ($request->name . ' Service');
            $cabang = \App\Models\Cabang::create([
                'nama' => $namaToko,
                'alamat' => null,
                'telp' => $request->phone,
                'aktif' => true,
                'created_by_user_id' => null, // will update after user created
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role_id' => 1, // Admin role (Admin Cabang)
                'cabang_id' => $cabang->id,
                'is_active' => true,
                'is_super_admin' => false,
                // Tanpa kode aktivasi → trial 1 bulan. Dengan kode valid → permanen.
                'is_permanent' => $isPermanent,
                'login_expires_at' => $isPermanent ? null : now()->addMonth(),
            ]);

            // Update cabang owner
            $cabang->update(['created_by_user_id' => $user->id]);

            // ===== Tandai serial sebagai sudah digunakan =====
            if ($serial) {
                $serial->update([
                    'is_used' => true,
                    'used_at' => now(),
                    'used_by_user_id' => $user->id,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        event(new Registered($user));

        Auth::login($user);

        // Audit log
        if ($isPermanent && $serial) {
            AuditLogService::log('auth', 'create', "Registrasi Admin Cabang baru (AKTIVASI KODE): {$user->name} ({$user->email}) — Cabang: {$cabang->nama} — Permanen via kode {$serial->serial_code}", userId: $user->id);
            AuditLogService::custom('serial_number', 'redeem', "Redeem otomatis saat registrasi: kode {$serial->serial_code} — akun {$user->email} jadi permanen");
        } else {
            AuditLogService::log('auth', 'create', "Registrasi Admin Cabang baru (TRIAL): {$user->name} ({$user->email}) — Cabang: {$cabang->nama}", userId: $user->id);
        }

        return redirect(route('dashboard', absolute: false))
            ->with('success', $isPermanent
                ? '🎉 Aktivasi berhasil! Akun Anda permanen dan siap digunakan.'
                : null);
    }
}
