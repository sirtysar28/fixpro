<x-guest-layout>

@php
    // 1. Deteksi Tab Register
    $showRegister = request()->query('tab') === 'register' || request()->is('register') || $errors->has('name') || $errors->has('nama_toko') || $errors->has('phone') || old('tab') === 'register';
    
    // 2. Deteksi Tab Forgot Password
    $showForgot = old('form_type') === 'forgot' || session('status');
    
    // 3. Default ke Login
    $showLogin = !$showRegister && !$showForgot;
@endphp

<!-- STYLES -->
<style>
/* === FORM OPTIONS === */
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
    transition: all 0.2s;
    background: none;
    border: none;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
}
.forgot-link:hover { color: #15803d; background: #f0fdf4; text-decoration: none; }

/* === WA SUPPORT LINK === */
.wa-support-link {
    color: #16a34a;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    transition: all 0.2s;
    background: rgba(22, 163, 74, 0.05);
}
.wa-support-link:hover {
    color: #15803d;
    background: rgba(22, 163, 74, 0.1);
    text-decoration: none;
    transform: translateY(-1px);
}

/* === PANEL TRANSITIONS === */
.auth-panel {
    display: none;
    opacity: 0;
    transform: translateY(8px);
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.auth-panel.on {
    display: block;
    animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes fadeInUp {
    to { opacity: 1; transform: translateY(0); }
}

/* === PRO INTERACTIVE CONNECTING OVERLAY === */
.login-form-area { position: relative; }

.connecting-overlay {
    position: absolute;
    inset: 0;
    /* Premium Glassmorphism */
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 50;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.5);
}
.connecting-overlay.active {
    opacity: 1;
    pointer-events: all;
}
.connecting-content {
    text-align: center;
    color: #0f172a;
    padding: 32px 24px;
    width: 100%;
    max-width: 300px;
}

/* Pro Spinner: Glowing Dual Ring */
.spinner-container {
    position: relative;
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
}
.spinner-ring-pro {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 3px solid transparent;
    border-top-color: #16a34a;
    border-right-color: #22c55e;
    animation: spin-pro 1s cubic-bezier(0.5, 0, 0.5, 1) infinite;
    filter: drop-shadow(0 0 8px rgba(22, 163, 74, 0.4));
}
.spinner-ring-pro::before {
    content: '';
    position: absolute;
    top: 6px; left: 6px; right: 6px; bottom: 6px;
    border-radius: 50%;
    border: 3px solid transparent;
    border-top-color: #86efac;
    animation: spin-pro 1.5s cubic-bezier(0.5, 0, 0.5, 1) infinite reverse;
}
@keyframes spin-pro { to { transform: rotate(360deg); } }

.spinner-container.hide {
    animation: fadeOutScale 0.3s ease forwards;
}
@keyframes fadeOutScale {
    to { opacity: 0; transform: scale(0.8); pointer-events: none; }
}

/* Dynamic Text */
.connecting-text {
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1e293b;
    min-height: 1.5rem;
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.connecting-text.fade {
    opacity: 0;
    transform: translateY(6px);
}

.connecting-percent {
    font-size: 2.25rem;
    font-weight: 800;
    color: #16a34a;
    margin-bottom: 16px;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.03em;
    text-shadow: 0 2px 10px rgba(22, 163, 74, 0.15);
}

/* Pro Progress Bar with Glowing Tip */
.progress-bar-bg {
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 99px;
    overflow: visible;
    margin: 0 auto;
    position: relative;
}
.progress-bar-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #16a34a, #4ade80);
    border-radius: 99px;
    transition: width 0.1s linear;
    position: relative;
    box-shadow: 0 0 12px rgba(22, 163, 74, 0.5);
}
.progress-bar-fill::after {
    content: '';
    position: absolute;
    right: -4px;
    top: 50%;
    transform: translateY(-50%);
    width: 14px;
    height: 14px;
    background: #fff;
    border: 3px solid #16a34a;
    border-radius: 50%;
    box-shadow: 0 0 8px rgba(22, 163, 74, 0.6);
    opacity: 0;
    transition: opacity 0.2s;
}
.progress-bar-fill[style*="width: 0"]::after { opacity: 0; }
.progress-bar-fill:not([style*="width: 0"])::after { opacity: 1; }

/* Pro Success Checkmark Animation */
.success-checkmark {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    filter: drop-shadow(0 4px 12px rgba(22, 163, 74, 0.3));
}
.success-checkmark.show {
    opacity: 1;
    transform: scale(1);
}
.checkmark-circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 3;
    stroke-miterlimit: 10;
    stroke: #16a34a;
    fill: none;
    animation: stroke-circle 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}
.checkmark-check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    stroke-width: 3;
    stroke: #16a34a;
    fill: none;
    animation: stroke-check 0.4s cubic-bezier(0.65, 0, 0.45, 1) 0.4s forwards;
}
@keyframes stroke-circle { 100% { stroke-dashoffset: 0; } }
@keyframes stroke-check { 100% { stroke-dashoffset: 0; } }

/* Button Click Micro-interaction */
.btn-press {
    transform: scale(0.96) !important;
    transition: transform 0.1s ease !important;
    filter: brightness(0.95);
}
</style>

<!-- SOUND EFFECT -->
<audio id="loginSound" preload="auto">
    <source src="https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3" type="audio/mpeg">
</audio>
<audio id="successSound" preload="auto">
    <source src="https://assets.mixkit.co/active_storage/sfx/2000/2000-preview.mp3" type="audio/mpeg">
</audio>

<!-- BRAND LEFT -->
<div class="login-brand">
    <div class="login-logo-wrap"><img src="{{ asset('logo-fixpro.jpg') }}" alt="FixPro Logo"></div>
    <h2>FixPro <span>Enterprise</span></h2>
    <p>Sistem manajemen servis profesional.<br>Daftar langsung, trial aktif 1 Bulan.</p>
</div>

<!-- FORM RIGHT -->
<div class="login-form-area">

    <!-- PRO INTERACTIVE CONNECTING OVERLAY -->
    <div id="connectingOverlay" class="connecting-overlay">
        <div class="connecting-content">
            <!-- Spinner State -->
            <div class="spinner-container" id="spinnerContainer">
                <div class="spinner-ring-pro"></div>
            </div>
            
            <!-- Success State (Hidden by default) -->
            <svg class="success-checkmark" id="successCheckmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark-circle" cx="26" cy="26" r="25"/>
                <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>

            <div class="connecting-text" id="connectingText">Memverifikasi data...</div>
            <div class="connecting-percent" id="connectingPercent">0%</div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" id="progressBarFill"></div>
            </div>
        </div>
    </div>

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

    <!-- TABS -->
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

            <div class="form-options">
                <label>
                    <input type="checkbox" name="remember" id="remember">
                    <span>Ingat Saya</span>
                </label>
                <button type="button" class="forgot-link" onclick="showForgotPanel()">Lupa Password?</button>
            </div>

            <!-- Link WhatsApp Group FixPro Official Support (Diekstrak dari Landing Page) -->
            <div style="text-align: center; margin-top: -8px; margin-bottom: 20px;">
                <a href="https://chat.whatsapp.com/G41Mmc3CzWD2CsQGSlEljj" target="_blank" rel="noopener" class="wa-support-link">
                    <i class="fab fa-whatsapp" style="font-size: 1.1rem;"></i> Gabung Grup WhatsApp FixPro Official Support
                </a>
            </div>

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
                    @error('activation_code')<div class="field-err">{{ $message }}</div>@enderror
                    @if(!empty($waUrl))
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="b b-wa" style="margin-top:8px">
                        <i class="fab fa-whatsapp"></i> Minta Kode Aktivasi via WhatsApp
                    </a>
                    @else
                    <button type="button" class="b b-wa" style="margin-top:8px;opacity:.6;cursor:not-allowed" disabled>
                        <i class="fab fa-whatsapp"></i> Minta Kode Aktivasi via WhatsApp
                    </button>
                    @endif
                </div>
            </div>

            <button type="submit" class="b bp b-full" id="loginSubmitBtn">
                <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Masuk</span>
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

    <!-- FORGOT PASSWORD PANEL -->
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
            <input type="hidden" name="form_type" value="forgot">
            
            <div class="fg">
                <label>Alamat Email</label>
                <input class="fci" type="email" name="email" value="{{ old('email') }}" placeholder="email@contoh.com" required autofocus>
                @error('email')<div class="field-err">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="b bp b-full" id="forgotSubmitBtn">
                <span class="btn-text"><i class="fas fa-paper-plane"></i> Kirim Tautan Reset</span>
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
            </button>
        </form>
    </div>
</div>

<script>
// === FUNGSI SIMULASI KONEKSI INTERAKTIF (PRO VERSION) ===
function simulateServerConnection(form, playSound = false) {
    const overlay = document.getElementById('connectingOverlay');
    const percentEl = document.getElementById('connectingPercent');
    const fillEl = document.getElementById('progressBarFill');
    const textEl = document.getElementById('connectingText');
    const spinnerContainer = document.getElementById('spinnerContainer');
    const checkmarkEl = document.getElementById('successCheckmark');
    
    if (playSound) {
        const audio = document.getElementById('loginSound');
        audio.volume = 0.2;
        audio.currentTime = 0;
        audio.play().catch(() => {}); 
    }

    overlay.classList.add('active');
    
    let progress = 0;
    const targetProgress = 92; // Stop before 100% to simulate "waiting for server"
    
    const messages = [
        { threshold: 0, text: "Memverifikasi kredensial..." },
        { threshold: 25, text: "Menghubungkan ke server aman..." },
        { threshold: 55, text: "Mengenkripsi sesi..." },
        { threshold: 80, text: "Menyiapkan dashboard..." }
    ];

    // Recursive setTimeout for organic, non-linear progress (feels more real)
    function advanceProgress() {
        if (progress < targetProgress) {
            // Slower as it gets closer to target (exponential decay feel)
            const remaining = targetProgress - progress;
            const step = Math.max(0.3, (remaining * 0.08) + (Math.random() * 1.5));
            progress = Math.min(targetProgress, progress + step);
            
            const displayProgress = Math.floor(progress);
            percentEl.textContent = displayProgress + '%';
            fillEl.style.width = displayProgress + '%';
            
            // Smooth text transition
            const currentMsg = messages.slice().reverse().find(m => progress >= m.threshold);
            if (currentMsg && textEl.dataset.currentMsg !== currentMsg.text) {
                textEl.classList.add('fade');
                setTimeout(() => {
                    textEl.textContent = currentMsg.text;
                    textEl.dataset.currentMsg = currentMsg.text;
                    textEl.classList.remove('fade');
                }, 150);
            }
            
            // Variable delay for realism
            const delay = Math.random() * 80 + 40;
            setTimeout(advanceProgress, delay);
        }
    }
    
    advanceProgress();

    // Selesaikan proses
    setTimeout(() => {
        progress = 100;
        percentEl.textContent = '100%';
        fillEl.style.width = '100%';
        textEl.textContent = "Berhasil! Mengalihkan...";
        
        // Efek transisi ke success state
        spinnerContainer.classList.add('hide');
        
        setTimeout(() => {
            checkmarkEl.classList.add('show');
            
            if (playSound) {
                const successAudio = document.getElementById('successSound');
                successAudio.volume = 0.3;
                successAudio.currentTime = 0;
                successAudio.play().catch(() => {});
            }
            
            // Submit form asli setelah animasi sukses selesai
            setTimeout(() => {
                HTMLFormElement.prototype.submit.call(form);
            }, 500);
        }, 200);
    }, 1800); // Total waktu simulasi ~1.8 - 2.2 detik
}

// === 1. HANDLE LOGIN SUBMIT ===
document.getElementById('loginForm').addEventListener('submit', function(e) {
    if (this.checkValidity()) {
        e.preventDefault();
        const btn = document.getElementById('loginSubmitBtn');
        btn.classList.add('btn-press');
        setTimeout(() => simulateServerConnection(this, true), 100);
    }
});

// === 2. HANDLE REGISTER SUBMIT ===
document.getElementById('registerForm').addEventListener('submit', function(e) {
    if (this.checkValidity()) {
        e.preventDefault();
        const btn = document.getElementById('registerSubmitBtn');
        btn.classList.add('btn-press');
        setTimeout(() => simulateServerConnection(this, false), 100);
    }
});

// === 3. HANDLE FORGOT PASSWORD SUBMIT ===
document.getElementById('forgotForm').addEventListener('submit', function(e) {
    if (this.checkValidity()) {
        e.preventDefault();
        const btn = document.getElementById('forgotSubmitBtn');
        btn.classList.add('btn-press');
        setTimeout(() => simulateServerConnection(this, false), 100);
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
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('on'));
    setTimeout(() => {
        document.getElementById('panel-forgot').classList.add('on');
    }, 150);
}

function showLoginPanel() {
    document.getElementById('panel-forgot').classList.remove('on');
    setTimeout(() => {
        document.getElementById('panel-login').classList.add('on');
        document.querySelectorAll('.auth-tab')[0].classList.add('on');
    }, 150);
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