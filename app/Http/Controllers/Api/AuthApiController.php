<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SerialNumber;
use App\Models\User;
use App\Models\Cabang;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        // Cari user by email
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Cek password — sama seperti Auth::attempt() di web
        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif.'],
            ]);
        }

        // Cek masa berlaku login
        if ($user->isLoginExpired()) {
            throw ValidationException::withMessages([
                'email' => ['Masa berlaku akun Anda sudah habis. Hubungi admin untuk mendapatkan Serial Number perpanjangan.'],
            ]);
        }

        // Buat personal access token untuk mobile
        $token = $user->createToken('fixpro-mobile-' . now()->timestamp)->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:30',
            'nama_toko' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Auto-create cabang for this admin (same as web register)
            $namaToko = $request->nama_toko ?: ($request->name . ' Service');
            $cabang = Cabang::create([
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
                'role_id' => 1, // Admin Cabang (same as web)
                'cabang_id' => $cabang->id,
                'is_active' => true,
                'is_super_admin' => false,
                'is_permanent' => false,
                'login_expires_at' => now()->addMonth(), // Trial 1 bulan
            ]);

            // Update cabang owner
            $cabang->update(['created_by_user_id' => $user->id]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        $token = $user->createToken('fixpro-mobile')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user),
            'token' => $token,
            'message' => 'Registrasi berhasil! Akun Admin Cabang & toko otomatis dibuat. Trial 1 bulan.',
        ]);
    }

    /**
     * Redeem Serial Number - User memasukkan kode serial
     */
    public function redeemSerial(Request $request)
    {
        $request->validate([
            'serial_code' => 'required|string',
        ]);

        $serial = SerialNumber::where('serial_code', $request->serial_code)->first();

        if (!$serial) {
            throw ValidationException::withMessages([
                'serial_code' => ['Serial Number tidak valid.'],
            ]);
        }

        if ($serial->is_used) {
            throw ValidationException::withMessages([
                'serial_code' => ['Serial Number sudah pernah digunakan.'],
            ]);
        }

        $user = $request->user();

        if ($serial->email !== $user->email) {
            throw ValidationException::withMessages([
                'serial_code' => ['Serial Number ini bukan untuk akun Anda. Serial ini untuk email: ' . $serial->email],
            ]);
        }

        $success = $serial->redeem($user);

        if (!$success) {
            throw ValidationException::withMessages([
                'serial_code' => ['Gagal redeem Serial Number. Silakan hubungi admin.'],
            ]);
        }

        return response()->json([
            'message' => 'Serial Number berhasil diredeem! Akun Anda sekarang aktif selamanya.',
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'user' => $this->formatUser($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }

    private function formatUser($user)
    {
        $user->load(['role', 'cabang']);
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role?->name,
            'is_super_admin' => $user->is_super_admin,
            'is_admin' => $user->isAdmin(),
            'is_staff' => $user->isStaff(),
            'is_user' => $user->isUser(),
            'cabang_id' => $user->cabang_id,
            'cabang' => $user->cabang?->nama,
            'active_cabang_id' => $user->getActiveCabangId(),
            'is_permanent' => $user->is_permanent,
            'login_expires_at' => $user->login_expires_at?->toIso8601String(),
            'days_until_expiry' => $user->daysUntilExpiry(),
            'is_login_expired' => $user->isLoginExpired(),
        ];
    }
}