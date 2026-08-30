<x-guest-layout>

@php
    $showRegister = request()->query('tab') === 'register' || request()->is('register') || $errors->has('name') || $errors->has('email') || $errors->has('phone') || $errors->has('password') || old('tab') === 'register';
    $showLogin = !$showRegister;
@endphp

<!-- BRAND LEFT -->
<div class="login-brand">
    <div class="login-logo-wrap"><img src="{{ asset('logo-fixpro.jpg') }}" alt="FixPro Logo"></div>
    <h2>FixPro <span>AL2000</span></h2>
    <p>Sistem manajemen servis profesional.<br>Daftar langsung, trial aktif.</p>
    <!-- <div class="db-status db-ok"><i class="fas fa-database"></i> DB OK</div> -->
</div>

<!-- FORM RIGHT -->
<div class="login-form-area">
    <div class="welcome-text">
        <h2>Selamat Datang</h2>
        <div class="sub">Kelola bisnis servis Anda dengan mudah</div>
    </div>

    @if(session('error'))
    <div class="alert-box alert-err show"><i class="fas fa-exclamation-circle" style="margin-top:2px"></i><span>{{ session('error') }}</span></div>
    @endif
    @if(session('status'))
    <div class="alert-box alert-ok show"><i class="fas fa-check-circle" style="margin-top:2px"></i><span>{{ session('status') }}</span></div>
    @endif

    <!-- TABS -->
    <div class="auth-tabs">
        <div class="auth-tab {{ $showLogin ? 'on' : '' }}" onclick="switchTab('login')"><i class="fas fa-sign-in-alt"></i> Login</div>
        <div class="auth-tab {{ $showRegister ? 'on' : '' }}" onclick="switchTab('register')"><i class="fas fa-user-plus"></i> Daftar</div>
    </div>

    <!-- LOGIN PANEL -->
    <div class="auth-panel {{ $showLogin ? 'on' : '' }}" id="panel-login">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="fg">
                <label>Username / Email</label>
                <input class="fci" type="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email" autocomplete="username" required autofocus>
                @error('email')<div class="field-err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Password</label>
                <div class="fg-pass">
                    <input type="password" class="fci" name="password" id="loginPass" placeholder="Masukkan password" autocomplete="current-password" required>
                    <button type="button" class="pass-toggle" onclick="togglePass('loginPass',this)"><i class="fas fa-eye"></i></button>
                </div>
                @error('password')<div class="field-err">{{ $message }}</div>@enderror
            </div>

            {{-- ===== KODE AKTIVASI (hanya untuk user yang masa aktifnya HABIS) ===== --}}
            @php
                $showCodeField = $errors->has('activation_code') || old('activation_code') !== null || request()->query('expired') === '1';
                $adminWaClean = isset($adminWa) ? preg_replace('/[^0-9]/', '', (string) $adminWa) : '';
                $waMessage = rawurlencode("Halo Admin FixPro,\n\nMasa aktif akun saya sudah habis.\nSaya ingin meminta *Kode Aktivasi* untuk bisa login kembali.\n\nTerima kasih.");
                $waUrl = $adminWaClean ? "https://wa.me/{$adminWaClean}?text={$waMessage}" : '';
            @endphp
            <div class="act-code-wrap {{ $showCodeField ? 'open' : '' }}" id="actCodeWrap">
                <button type="button" class="act-code-toggle" onclick="toggleActCode()">
                    <i class="fas fa-clock"></i>
                    <span>Masa aktif habis? Minta Kode Aktivasi</span>
                    <i class="fas fa-chevron-down act-code-arrow"></i>
                </button>
                <div class="act-code-body">
                    <label style="display:block;font-size:.78rem;font-weight:600;color:#475569;margin-bottom:6px">Kode Aktivasi</label>
                    <input type="text" class="fci" name="activation_code" value="{{ old('activation_code') }}" placeholder="Masukkan kode dari Admin" autocomplete="off">
                    @error('activation_code')
                        <div class="field-err">{{ $message }}</div>
                    @enderror
                    @if(!empty($waUrl))
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="b b-wa" style="margin-top:8px">
                        <i class="fab fa-whatsapp"></i> Minta Kode Aktivasi via WhatsApp
                    </a>
                    @else
                    <button type="button" class="b b-wa" style="margin-top:8px;opacity:.6;cursor:not-allowed" disabled title="Nomor WhatsApp admin belum diatur">
                        <i class="fab fa-whatsapp"></i> Minta Kode Aktivasi via WhatsApp
                    </button>
                    @endif
                </div>
            </div>

            <button type="submit" class="b bp b-full"><i class="fas fa-sign-in-alt"></i> Masuk</button>
        </form>

        @php
            $googleClientId = \App\Models\Setting::get('google_client_id') ?: config('services.google.client_id');
        @endphp
        @if($googleClientId)
        <div class="google-login-wrap">
            <div class="google-divider">atau</div>
            <a href="{{ route('auth.google') }}" class="btn-google" style="text-decoration:none">
                <svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Masuk dengan Google
            </a>
        </div>
        @endif

    </div>

    <!-- REGISTER PANEL -->
    <div class="auth-panel {{ $showRegister ? 'on' : '' }}" id="panel-register">
        <form method="POST" action="{{ route('register') }}">
            @csrf
            <input type="hidden" name="tab" value="register">
            <div class="fg">
                <label>Nama Lengkap *</label>
                <input class="fci" type="text" name="name" value="{{ old('name') }}" placeholder="Nama Anda" required>
                @error('name')<div class="field-err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Nama Toko / Bengkel *</label>
                <input class="fci" type="text" name="nama_toko" value="{{ old('nama_toko') }}" placeholder="Contoh: iPhone Service Surabaya">
                <div style="font-size:.68rem;color:#64748b;margin-top:3px">Kosongkan untuk otomatis menggunakan Nama Anda</div>
                @error('nama_toko')<div class="field-err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Email *</label>
                <input type="email" class="fci" name="email" value="{{ old('email') }}" placeholder="email@contoh.com" required autocomplete="email">
                @error('email')<div class="field-err">{{ $message }}</div>@enderror
            </div>
            <div class="fr">
                <div class="fg">
                    <label>Password *</label>
                    <div class="fg-pass">
                        <input type="password" class="fci" name="password" id="regPass" placeholder="Min. 6 karakter" required minlength="6">
                        <button type="button" class="pass-toggle" onclick="togglePass('regPass',this)"><i class="fas fa-eye"></i></button>
                    </div>
                    @error('password')<div class="field-err">{{ $message }}</div>@enderror
                </div>
                <div class="fg">
                    <label>Konfirmasi *</label>
                    <div class="fg-pass">
                        <input type="password" class="fci" name="password_confirmation" id="regPass2" placeholder="Ulangi password" required minlength="6">
                        <button type="button" class="pass-toggle" onclick="togglePass('regPass2',this)"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
            </div>
            <div class="fg">
                <label>Nomor HP *</label>
                <input type="tel" class="fci" name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" required>
                @error('phone')<div class="field-err">{{ $message }}</div>@enderror
            </div>
            <div class="trial-badge"><i class="fas fa-clock"></i> Trial Aktif 1 Bulan — Hubungi admin untuk Serial Number perpanjangan!</div>
            <button type="submit" class="b bp b-full" style="margin-top:14px"><i class="fas fa-user-plus"></i> Daftar & Langsung Masuk</button>
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.auth-tab').forEach(function(t, i) {
        t.classList.toggle('on', (tab === 'login' && i === 0) || (tab === 'register' && i === 1));
    });
    document.getElementById('panel-login').classList.toggle('on', tab === 'login');
    document.getElementById('panel-register').classList.toggle('on', tab === 'register');
}
function toggleActCode() {
    document.getElementById('actCodeWrap').classList.toggle('open');
}
</script>
</x-guest-layout>
