<?php

namespace App\Http\Requests\Auth;

use App\Models\ActivationCode;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'activation_code' => ['nullable', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // ===== Kode Aktivasi untuk user yang masa aktifnya HABIS =====
        // User yang masih aktif tidak perlu kode → langsung login.
        // User yang expired wajib masukin kode aktivasi dari admin.
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user && !$user->isSuperAdmin() && $user->isLoginExpired()) {
            $inputCode = trim((string) $this->string('activation_code'));

            // Kode kosong → tolak, suruh minta kode via WA
            if ($inputCode === '') {
                Auth::guard('web')->logout();
                $this->session()->invalidate();

                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'activation_code' => 'Masa aktif akun Anda sudah habis. Silakan minta Kode Aktivasi ke Admin melalui WhatsApp, lalu masukkan kodenya di sini.',
                ]);
            }

            // Cari kode (case-insensitive, trim)
            $code = ActivationCode::whereRaw('LOWER(code) = ?', [strtolower($inputCode)])->first();

            // Kode tidak ditemukan / sudah dipakai
            if (!$code || $code->is_used) {
                Auth::guard('web')->logout();
                $this->session()->invalidate();

                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'activation_code' => $code && $code->is_used
                        ? 'Kode aktivasi sudah pernah dipakai. Minta kode baru ke Admin.'
                        : 'Kode aktivasi tidak valid. Periksa kembali atau minta kode baru ke Admin.',
                ]);
            }

            // Kode valid → perpanjang masa aktif user
            $code->activate($user);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
