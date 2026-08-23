<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cabang;
use App\Models\Setting;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Ambil konfigurasi Google (prioritas: Settings DB -> config/env)
     */
    private function getConfig(): array
    {
        $clientId     = Setting::get('google_client_id')     ?: config('services.google.client_id');
        $clientSecret = Setting::get('google_client_secret') ?: config('services.google.client_secret');
        $redirectUri  = Setting::get('google_redirect_uri')  ?: config('services.google.redirect');

        // Fallback dinamis: kalau redirect uri kosong / relatif, generate dari URL saat ini
        if (empty($redirectUri) || str_starts_with($redirectUri, '/')) {
            $redirectUri = rtrim(config('app.url'), '/') . '/auth/google/callback';
        }

        return [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
        ];
    }

    /**
     * Redirect ke Google OAuth
     */
    public function redirect()
    {
        $cfg = $this->getConfig();

        if (empty($cfg['client_id'])) {
            return redirect()->route('login')
                ->with('error', 'Login Google belum dikonfigurasi (Client ID kosong). Hubungi admin untuk seting di menu Pengaturan.');
        }

        $params = http_build_query([
            'client_id'     => $cfg['client_id'],
            'redirect_uri'  => $cfg['redirect_uri'],
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);

        Log::info('Google OAuth redirect', ['redirect_uri' => $cfg['redirect_uri']]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    /**
     * Handle callback dari Google
     */
    public function callback(Request $request)
    {
        // 1. Cek error langsung dari Google
        if ($request->filled('error')) {
            $err = $request->get('error');
            Log::warning('Google OAuth mengembalikan error', ['error' => $err]);
            return redirect()->route('login')
                ->with('error', 'Login Google gagal: ' . $err);
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('login')->with('error', 'Login Google dibatalkan (kode otorisasi tidak ditemukan).');
        }

        $cfg = $this->getConfig();

        if (empty($cfg['client_id']) || empty($cfg['client_secret'])) {
            return redirect()->route('login')
                ->with('error', 'Login Google belum dikonfigurasi dengan benar (Client ID / Secret kosong).');
        }

        // 2. Exchange code for token
        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'redirect_uri'  => $cfg['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ]);

        if (!$tokenResponse->successful()) {
            $body = $tokenResponse->json();
            $errKey = $body['error'] ?? 'unknown';
            $errMsg = $body['error_description'] ?? 'Gagal menukar kode otorisasi dengan token.';

            // Pesan yang lebih ramah user berdasarkan jenis error
            $userMsg = match ($errKey) {
                'invalid_grant'    => 'Sesi login Google sudah kedaluwarsa. Silakan coba login Google lagi.',
                'invalid_client'   => 'Client ID / Client Secret Google tidak valid. Periksa kembali kredensial di Google Cloud Console.',
                'redirect_uri_mismatch', 'invalid_request' => 'Redirect URI tidak terdaftar di Google Cloud Console. URI aktif: ' . $cfg['redirect_uri'],
                'unauthorized_client' => 'Client ini tidak diizinkan melakukan login Google. Periksa konfigurasi OAuth Client di Google Cloud Console.',
                default => $errMsg,
            };

            Log::error('Google OAuth token exchange gagal', [
                'status' => $tokenResponse->status(),
                'body'   => $body,
                'redirect_uri' => $cfg['redirect_uri'],
            ]);

            return redirect()->route('login')->with('error', 'Login Google gagal: ' . $userMsg);
        }

        $tokenData = $tokenResponse->json();
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            return redirect()->route('login')->with('error', 'Token akses Google tidak ditemukan.');
        }

        // 3. Get user info from Google
        $userResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if (!$userResponse->successful()) {
            Log::error('Google OAuth gagal ambil userinfo', ['status' => $userResponse->status()]);
            return redirect()->route('login')->with('error', 'Gagal mengambil data profil Google.');
        }

        $googleUser = $userResponse->json();
        $email    = $googleUser['email'] ?? null;
        $name     = $googleUser['name'] ?? 'Google User';
        $picture  = $googleUser['picture'] ?? null;
        $googleId = $googleUser['sub'] ?? null;

        if (!$email) {
            return redirect()->route('login')->with('error', 'Email Google tidak ditemukan.');
        }

        // 4. Find or create user
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Create new user as Admin Cabang
            $namaToko = $name . ' Service';
            $cabang = Cabang::create([
                'nama' => $namaToko,
                'telp' => null,
                'aktif' => true,
                'created_by' => null,
            ]);

            $user = User::create([
                'name'      => $name,
                'email'     => $email,
                'password'  => Hash::make(Str::random(32)),
                'role_id'   => 1, // Admin Cabang
                'cabang_id' => $cabang->id,
                'is_active' => true,
                'is_super_admin' => false,
                'is_permanent' => false,
                'login_expires_at' => now()->addMonth(),
                'avatar' => $picture,
            ]);

            AuditLogService::log('auth', 'create', "Registrasi via Google: {$name} ({$email}) — Admin Cabang", userId: $user->id);
        } else {
            // Update avatar if available
            if ($picture && !$user->avatar) {
                $user->update(['avatar' => $picture]);
            }
        }

        // 5. Check if user is active
        if (!$user->is_active) {
            return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan. Hubungi admin.');
        }

        // 6. Check login expiry
        if ($user->isLoginExpired()) {
            return redirect()->route('login')->with('error', 'Akun Anda sudah expired. Hubungi admin untuk perpanjangan.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        AuditLogService::login();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
