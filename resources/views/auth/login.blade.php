<x-guest-layout>

@php
    // 1. Deteksi Tab Register
    $showRegister = request()->query('tab') === 'register' || request()->is('register') || $errors->has('name') || $errors->has('nama_toko') || $errors->has('phone') || old('tab') === 'register';
    
    // 2. Deteksi Tab Forgot Password (Berfungsi saat ada error validasi email dari form forgot, atau ada status sukses)
    $showForgot = old('form_type') === 'forgot' || session('status');
    
    // 3. Default ke Login jika bukan register atau forgot
    $showLogin = !$showRegister && !$showForgot;
@endphp

<!-- STYLES -->
<style>
/* === LOADING SPINNER STYLES === */
@keyframes spin { to { transform: rotate(360deg); } }
.btn-spinner { display: none; align-items: center; justify-content: center; gap: 8px; }
.btn-spinner svg { animation: spin 1s linear infinite; width: 20px; height: 20px; }
.b.bp:disabled { opacity: 0.9; cursor: wait; }

/* === FORM OPTIONS (Remember & Forgot) === */
.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    font-size: 0.85rem;
}
.form-options label {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    color: #475569;
    font-weight: 500;
}
.form-options input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #16a34a;
    cursor: pointer;
}
.forgot-link {
    color: #16a34a;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
    background: none;
    border: none;
    padding: 0;
    font-size: 0.85rem;
    cursor: pointer;
}
.forgot-link:hover { color: #15803d; text-decoration: underline; }

/* === PANEL TRANSITIONS === */
.auth-panel {
    display: none;
    opacity: 0;
    transform: translateY(8px);
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.auth-panel.on {
    display: block;
    animation: fadeInUp 0.3s forwards;
}
@keyframes fadeInUp {
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- SOUND EFFECT -->
<audio id="loginSound" preload="auto">
    <source src="https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3" type="audio/mpeg">
</audio>

<!-- BRAND LEFT -->
<div class="login-brand">
    <div class="login-logo-wrap"><img src="{{ asset('logo-fixpro.jpg') }}" alt="FixPro Logo"></div>
    <h2>FixPro <span>Enterprise</span></h2>
    <p>Sistem manajemen servis profesional.<br>Daftar langsung, trial aktif 1 Bulan.</p>
</div>

<!-- FORM RIGHT -->
<div class="login-form-area">

    <div class="welcome-text" style="text-align: center; margin-bottom: 24px;">
        <h2 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Selamat Datang di FixPro</h2>
        <div class="sub" style="color: #64748b; font-size: 0.95rem; line-height: 1.6;">
            🚀 Kelola bisnis servis Anda lebih mudah dalam satu sistem terintegrasi.<br>
            <strong style="color: #16a34a;">FIXPRO</strong> — Solusi manajemen bisnis servis HP profesional.
        </div>
    </div>

    @if(session('error'))
    <div class="alert-box alert-err show"><i class="fas fa-exclamation-circle" style="margin-top:2px"></i><span>{{ session('error') }}</span></div>
    @endif
    @if(session('status') && !$showForgot)
    <div class="alert-box alert-ok show"><i class="fas fa-check-circle" style="margin-top:2px"></i><span>{{ session('status') }}</span></div>
    @endif

    <!-- TABS (Hanya tampil jika bukan di panel forgot password) -->
    @if(!$showForgot)
    <div class="auth-tabs">
        <div class="auth-tab {{ $showLogin ? 'on' : '' }}" onclick="switchTab('login')"><i class="fas fa-sign-in-alt"></i> Login</div>
        <div class="auth-tab {{ $showRegister ? 'on' : '' }}" onclick="switchTab('register')"><i class="fas fa-user-plus"></i> Daftar</div>
    </div>
    @endif

    <!-- LOGIN PANEL -->
    <div class="auth-panel {{ $showLogin ? 'on' : '' }}" id="panel-login">
        <form method="POST" action="{{ route('login') }}" id="loginForm">
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
                    <button type="button" class="pass-toggle" onclick="togglePass('loginPass', this)"><i class="fas fa-eye"></i></button>
                </div>
                @error('password')<div class="field-err">{{ $message }}</div>@enderror
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="form-options">
                <label>
                    <input type="checkbox" name="remember" id="remember">
                    <span>Ingat Saya</span>
                </label>
                <button type="button" class="forgot-link" onclick="showForgotPanel()">Lupa Password?</button>
            </div>

            {{-- ===== KODE AKTIVASI ===== --}}
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

            <!-- TOMBOL LOGIN DENGAN SPINNER -->
            <button type="submit" class="b bp b-full" id="loginSubmitBtn">
                <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Masuk</span>
                <span class="btn-spinner">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="stroke: rgba(255,255,255,0.4);"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" style="fill: #ffffff;"></path>
                    </svg>
                    Memproses...
                </span>
            </button>
        </form>

        @php
            $googleClientId = \App\Models\Setting::get('google_client_id') ?: config('services.google.client_id');
        @endphp
        @if($googleClientId)
        <div class="google-login-wrap" style="margin-top: 20px;">
            <div class="google-divider" style="display: flex; align-items: center; text-align: center; color: #64748b; font-size: 0.85rem; margin-bottom: 12px;">
                <span style="flex: 1; border-bottom: 1px solid #e2e8f0; margin-right: 10px;"></span>atau<span style="flex: 1; border-bottom: 1px solid #e2e8f0; margin-left: 10px;"></span>
            </div>
            <a href="{{ route('auth.google') }}" class="b" style="width: 100%; border: 1.5px solid #e2e8f0; background: white; color: #0f172a; justify-content: center;">
                <svg viewBox="0 0 24 24" style="width: 20px; height: 20px;"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Masuk dengan Google
            </a>
        </div>
        @endif
    </div>

    <!-- FORGOT PASSWORD PANEL (DIPERBAIKI AGAR BERFUNGSI SAAT ERROR) -->
    <div class="auth-panel {{ $showForgot ? 'on' : '' }}" id="panel-forgot">
        <div class="welcome-text" style="text-align: center; margin-bottom: 20px;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Lupa Password?</h2>
            <div class="sub" style="color: #64748b; font-size: 0.9rem; line-height: 1.5;">
                Tenang, hal ini bisa terjadi. Masukkan email Anda, dan kami akan mengirimkan tautan untuk mengatur ulang password.
            </div>
        </div>

        @if(session('status') && $showForgot)
        <div class="alert-box alert-ok show">
            <i class="fas fa-check-circle" style="margin-top:2px"></i>
            <span>{{ session('status') }}</span>
        </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" id="forgotForm">
            @csrf
            <!-- INI KUNCINYA: Agar saat error validasi, panel ini tetap terbuka -->
            <input type="hidden" name="form_type" value="forgot">
            
            <div class="fg">
                <label>Alamat Email</label>
                <input class="fci" type="email" name="email" value="{{ old('email') }}" placeholder="email@contoh.com" required autofocus>
                @error('email')<div class="field-err">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="b bp b-full" id="forgotSubmitBtn">
                <span class="btn-text"><i class="fas fa-paper-plane"></i> Kirim Tautan Reset</span>
                <span class="btn-spinner">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="stroke: rgba(255,255,255,0.4);"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" style="fill: #ffffff;"></path>
                    </svg>
                    Mengirim...
                </span>
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <button type="button" class="forgot-link" onclick="showLoginPanel()" style="font-size: 0.9rem;">
                <i class="fas fa-arrow-left" style="margin-right: 4px;"></i> Kembali ke Login
            </button>
        </div>
    </div>

    <!-- REGISTER PANEL -->
    <div class="auth-panel {{ $showRegister ? 'on' : '' }}" id="panel-register">
        <form method="POST" action="{{ route('register') }}" id="registerForm">
            @csrf
            <input type="hidden" name="tab" value="register">
            <div class="fg">
                <label>Nama Lengkap *</label>
                <input class="fci" type="text" name="name" value="{{ old('name') }}" placeholder="Nama Anda" required>
                @error('name')<div class="field-err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Nama Toko / Konter *</label>
                <input class="fci" type="text" name="nama_toko" value="{{ old('nama_toko') }}" placeholder="Contoh: iPhone Service Surabaya">
                <div style="font-size:.68rem;color:#64748b;margin-top:3px">Kosongkan untuk otomatis menggunakan Nama Anda</div>
                @error('nama_toko')<div class="field-err">{{ $message }}</div>@enderror
            </div>
            <div class="fg">
                <label>Email *</label>
                <input type="email" class="fci" name="email" value="{{ old('email') }}" placeholder="email@contoh.com" required autocomplete="email">
                @error('email')<div class="field-err">{{ $message }}</div>@enderror
            </div>
            <div class="fr" style="display: flex; gap: 12px;">
                <div class="fg" style="flex: 1;">
                    <label>Password *</label>
                    <div class="fg-pass">
                        <input type="password" class="fci" name="password" id="regPass" placeholder="Min. 6 karakter" required minlength="6">
                        <button type="button" class="pass-toggle" onclick="togglePass('regPass', this)"><i class="fas fa-eye"></i></button>
                    </div>
                    @error('password')<div class="field-err">{{ $message }}</div>@enderror
                </div>
                <div class="fg" style="flex: 1;">
                    <label>Konfirmasi *</label>
                    <div class="fg-pass">
                        <input type="password" class="fci" name="password_confirmation" id="regPass2" placeholder="Ulangi password" required minlength="6">
                        <button type="button" class="pass-toggle" onclick="togglePass('regPass2', this)"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
            </div>
            <div class="fg">
                <label>Nomor HP *</label>
                <input type="tel" class="fci" name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" required>
                @error('phone')<div class="field-err">{{ $message }}</div>@enderror
            </div>
            <div class="trial-badge" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; padding: 10px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-align: center; margin-bottom: 14px; border: 1px solid #93c5fd;">
                <i class="fas fa-clock"></i> Trial Aktif 1 Bulan — Hubungi admin untuk perpanjangan!
            </div>
            
            <button type="submit" class="b bp b-full" id="registerSubmitBtn">
                <span class="btn-text"><i class="fas fa-user-plus"></i> Daftar & Langsung Masuk</span>
                <span class="btn-spinner">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="stroke: rgba(255,255,255,0.4);"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" style="fill: #ffffff;"></path>
                    </svg>
                    Mendaftar...
                </span>
            </button>
        </form>
    </div>
</div>

<script>
// === 1. SPINNER & SOUND LOGIN ===
document.getElementById('loginForm').addEventListener('submit', function(e) {
    if (this.checkValidity()) {
        const audio = document.getElementById('loginSound');
        audio.volume = 0.4;
        audio.play().catch(() => {}); // Ignore error jika browser blokir autoplay

        const btn = document.getElementById('loginSubmitBtn');
        btn.querySelector('.btn-text').style.display = 'none';
        btn.querySelector('.btn-spinner').style.display = 'inline-flex';
        btn.disabled = true;
    }
});

// === 2. SPINNER REGISTER ===
document.getElementById('registerForm').addEventListener('submit', function(e) {
    if (this.checkValidity()) {
        const btn = document.getElementById('registerSubmitBtn');
        btn.querySelector('.btn-text').style.display = 'none';
        btn.querySelector('.btn-spinner').style.display = 'inline-flex';
        btn.disabled = true;
    }
});

// === 3. SPINNER FORGOT PASSWORD ===
document.getElementById('forgotForm').addEventListener('submit', function(e) {
    if (this.checkValidity()) {
        const btn = document.getElementById('forgotSubmitBtn');
        btn.querySelector('.btn-text').style.display = 'none';
        btn.querySelector('.btn-spinner').style.display = 'inline-flex';
        btn.disabled = true;
    }
});

// === 4. FUNGSI TAB & PANEL TRANSITIONS ===
function switchTab(tab) {
    document.querySelectorAll('.auth-panel').forEach(p => p.classList.remove('on'));
    document.querySelectorAll('.auth-tab').forEach((t, i) => {
        t.classList.toggle('on', (tab === 'login' && i === 0) || (tab === 'register' && i === 1));
    });
    document.getElementById(`panel-${tab}`).classList.add('on');
}

function showForgotPanel() {
    document.getElementById('panel-login').classList.remove('on');
    // Hapus class 'on' dari tabs agar tidak membingungkan
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('on'));
    setTimeout(() => {
        document.getElementById('panel-forgot').classList.add('on');
    }, 200);
}

function showLoginPanel() {
    document.getElementById('panel-forgot').classList.remove('on');
    setTimeout(() => {
        document.getElementById('panel-login').classList.add('on');
        // Kembalikan state tab login
        document.querySelectorAll('.auth-tab')[0].classList.add('on');
    }, 200);
}

function toggleActCode() {
    document.getElementById('actCodeWrap').classList.toggle('open');
}

function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</x-guest-layout>