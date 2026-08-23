@extends('layouts.app')
@section('title', 'Pengaturan')

@section('content')
<h2 class="mb-4">Pengaturan Sistem</h2>

<div class="grid-2">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-store" style="color:var(--primary);margin-right:6px"></i>Info Toko — <span style="color:var(--primary)">{{ $activeCabang->nama ?? 'Cabang' }}</span></h3>
        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
            @csrf @method('POST')
            <div class="form-group"><label>Nama Toko</label><input type="text" name="nama_toko" class="form-input" value="{{ $settings['nama_toko'] ?? '' }}"></div>
            <div class="form-group"><label>Alamat</label><input type="text" name="alamat" class="form-input" value="{{ $settings['alamat'] ?? '' }}"></div>
            <div class="form-group"><label>Telepon</label><input type="text" name="telp" class="form-input" value="{{ $settings['telp'] ?? '' }}"></div>
            <div class="form-group"><label>WA Template</label><textarea name="wa_template" class="form-input" rows="3">{{ $settings['wa_template'] ?? '' }}</textarea>
                <div class="text-xs text-muted" style="margin-top:4px">{nama}, {kode}, {status}, {perangkat}, {keluhan}, {biaya}</div>
            </div>
            <div class="form-group"><label>API Key (Fonnte)</label><input type="text" name="wa_api_key" id="waApiKeyInput" class="form-input" value="{{ $settings['wa_api_key'] ?? '' }}" placeholder="Masukkan API key">
                <div id="fonnteTestResult" style="margin-top:6px;font-size:.74rem"></div>
                <button type="button" onclick="testFonnteKey()" class="btn btn-secondary btn-sm" style="margin-top:6px"><i class="fas fa-plug"></i> Test Koneksi API Key</button>
            </div>
            <div class="form-group"><label>Lebar Thermal</label>
                <div class="text-xs text-muted" style="padding:8px;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;color:#166534">
                    <i class="fas fa-check-circle"></i> Ukuran thermal printer default: <strong>80mm</strong> (otomatis)
                </div>
            </div>

            <h3 style="font-size:.95rem;margin:20px 0 16px;padding-top:16px;border-top:1px solid #e2e8f0"><i class="fab fa-google" style="color:#4285F4;margin-right:6px"></i>Login Google OAuth</h3>
            {{-- GOOGLE OAUTH: Super Admin Only --}}
            @if(auth()->user()->isSuperAdmin())
            <div class="form-group">
                <label>Google Client ID</label>
                <input type="text" name="google_client_id" class="form-input" value="{{ $settings['google_client_id'] ?? '' }}" placeholder="xxxx.apps.googleusercontent.com">
                <div class="text-xs text-muted" style="margin-top:4px">Dari <a href="https://console.cloud.google.com/apis/credentials" target="_blank" style="color:var(--primary)">Google Console</a></div>
            </div>
            <div class="form-group">
                <label>Google Client Secret</label>
                <input type="password" name="google_client_secret" class="form-input" value="{{ $settings['google_client_secret'] ?? '' }}" placeholder="GOCSPX-xxxx">
            </div>
            <div class="form-group">
                <label>Google Redirect URI</label>
                <input type="text" name="google_redirect_uri" class="form-input" value="{{ $settings['google_redirect_uri'] ?? url('/auth/google/callback') }}">
                <div class="text-xs text-muted" style="margin-top:4px">Masukkan URI ini di Google Console → Authorized redirect URIs</div>
            </div>
            @else
            <div style="padding:12px 16px;background:#f8fafc;border-radius:8px;font-size:.82rem;color:#64748b">
                <i class="fas fa-lock" style="color:#94a3b8"></i> Pengaturan Google OAuth hanya bisa diubah oleh <strong>Super Admin</strong>.
            </div>
            @endif

            <h3 style="font-size:.95rem;margin:20px 0 16px;padding-top:16px;border-top:1px solid #e2e8f0"><i class="fas fa-robot" style="color:var(--accent);margin-right:6px"></i>AI Chat Bot</h3>
            {{-- AI BOT: Super Admin Only --}}
            @if(auth()->user()->isSuperAdmin())
            <div class="form-group">
                <label>Provider AI Bot *</label>
                <select name="bot_provider" class="form-input" id="botProvider" onchange="toggleBotProvider()">
                    <option value="default" {{ ($settings['bot_provider'] ?? 'default') === 'default' ? 'selected' : '' }}>🤖 Default (Gratis — Keyword-based, tanpa API)</option>
                    <option value="gemini" {{ ($settings['bot_provider'] ?? '') === 'gemini' ? 'selected' : '' }}>🟢 Google Gemini (Gratis — AI cerdas)</option>
                    <option value="groq" {{ ($settings['bot_provider'] ?? '') === 'groq' ? 'selected' : '' }}>⚡ Groq (Gratis — Super cepat, open source)</option>
                    <option value="openai" {{ ($settings['bot_provider'] ?? '') === 'openai' ? 'selected' : '' }}>💰 OpenAI (Berbayar — GPT-4o-mini)</option>
                </select>
            </div>

            {{-- DEFAULT INFO --}}
            <div id="providerDefault" class="card mb-4" style="background:#f0fdf4;border:1px solid #bbf7d0;padding:14px">
                <p style="margin:0;font-size:.84rem;color:#166534"><i class="fas fa-check-circle"></i> <strong>Mode Default</strong> — Bot otomatis membalas berdasarkan kata kunci (harga, LCD, baterai, dll). 100% gratis, tanpa API key. Cocok untuk toko kecil.</p>
            </div>

            {{-- GEMINI --}}
            <div id="providerGemini" style="display:none">
                <div class="form-group">
                    <label>Google Gemini API Key</label>
                    <div style="display:flex;gap:8px;align-items:flex-end">
                        <div style="flex:1">
                            <input type="text" name="gemini_api_key" class="form-input" value="{{ $settings['gemini_api_key'] ?? '' }}" placeholder="AIzaSy..." autocomplete="off">
                            <div class="text-xs text-muted" style="margin-top:4px">
                                <strong>GRATIS!</strong> Dapatkan key di <a href="https://aistudio.google.com/apikey" target="_blank" style="color:var(--primary)">Google AI Studio</a> — cukup klik "Create API Key", langsung jadi!
                            </div>
                        </div>
                        <button type="button" onclick="testBot()" class="btn btn-secondary btn-sm" id="btnTestBot"><i class="fas fa-flask"></i> Test</button>
                    </div>
                    <div id="botTestResult" style="margin-top:8px;display:none;padding:10px;border-radius:8px;font-size:.82rem"></div>
                </div>
                <div class="card mb-4" style="background:#f0fdf4;border:1px solid #bbf7d0;padding:12px">
                    <p style="margin:0;font-size:.78rem;color:#166534"><strong>Info Gratis:</strong> Gemini Flash gratis 15 request/menit, 1.500 request/hari. Lebih dari cukup untuk chatbot toko! 🟢</p>
                </div>
            </div>

            {{-- GROQ --}}
            <div id="providerGroq" style="display:none">
                <div class="form-group">
                    <label>Groq API Key</label>
                    <div style="display:flex;gap:8px;align-items:flex-end">
                        <div style="flex:1">
                            <input type="text" name="groq_api_key" class="form-input" value="{{ $settings['groq_api_key'] ?? '' }}" placeholder="gsk_..." autocomplete="off">
                            <div class="text-xs text-muted" style="margin-top:4px">
                                <strong>GRATIS!</strong> Daftar di <a href="https://console.groq.com" target="_blank" style="color:var(--primary)">console.groq.com</a> → buat API Key → copy paste ke sini. Super cepat!
                            </div>
                        </div>
                        <button type="button" onclick="testBot()" class="btn btn-secondary btn-sm" id="btnTestBotGroq"><i class="fas fa-flask"></i> Test</button>
                    </div>
                    <div id="botTestResultGroq" style="margin-top:8px;display:none;padding:10px;border-radius:8px;font-size:.82rem"></div>
                </div>
                <div class="card mb-4" style="background:#eff6ff;border:1px solid #bfdbfe;padding:12px">
                    <p style="margin:0;font-size:.78rem;color:#1e40af"><strong>Info Gratis:</strong> Groq pakai model Llama 3 (open source) dengan inferensi super cepat. Free tier sangat generous! ⚡</p>
                </div>
            </div>

            {{-- OPENAI --}}
            <div id="providerOpenai" style="display:none">
                <div class="form-group">
                    <label>OpenAI API Key</label>
                    <div style="display:flex;gap:8px;align-items:flex-end">
                        <div style="flex:1">
                            <input type="text" name="openai_api_key" class="form-input" value="{{ $settings['openai_api_key'] ?? '' }}" placeholder="sk-..." autocomplete="off">
                            <div class="text-xs text-muted" style="margin-top:4px">
                                <strong>BERBAYAR.</strong> Dari <a href="https://platform.openai.com/api-keys" target="_blank" style="color:var(--primary)">OpenAI</a>. Model GPT-4o-mini ~$0.15/1M token.
                            </div>
                        </div>
                        <button type="button" onclick="testBot()" class="btn btn-secondary btn-sm" id="btnTestBotOpenai"><i class="fas fa-flask"></i> Test</button>
                    </div>
                    <div id="botTestResultOpenai" style="margin-top:8px;display:none;padding:10px;border-radius:8px;font-size:.82rem"></div>
                </div>
            </div>
            <div class="form-group">
                <label>Bot System Prompt</label>
                <textarea name="bot_system_prompt" class="form-input" rows="3">{{ $settings['bot_system_prompt'] ?? 'Kamu adalah asisten AI dari FIXPRO, layanan service HP profesional. Jawab dengan ramah dan singkat dalam bahasa Indonesia. Tanyakan keluhan HP pelanggan, berikan estimasi biaya kasar, dan sarankan untuk datang ke toko.' }}</textarea>
            </div>
            @else
            <div style="padding:12px 16px;background:#f8fafc;border-radius:8px;font-size:.82rem;color:#64748b">
                <i class="fas fa-lock" style="color:#94a3b8"></i> Pengaturan AI Chat Bot hanya bisa diubah oleh <strong>Super Admin</strong>.
            </div>
            @endif

            <button type="submit" class="btn btn-primary" style="margin-top:8px"><i class="fas fa-save"></i> Simpan Pengaturan</button>
        </form>
    </div>
    <div>
        <div class="card mb-4">
            <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-qrcode" style="color:var(--primary);margin-right:6px"></i>Upload QRIS — <span style="color:var(--primary)">{{ $activeCabang->nama ?? 'Cabang' }}</span></h3>
            @if($cabangs->count() > 1)
            <div class="form-group">
                <label>Pilih Cabang</label>
                <select id="qrisCabang" class="form-input" onchange="loadQris()">
                    <option value="">-- Pilih Cabang --</option>
                    @foreach($cabangs as $cab)
                    <option value="{{ $cab->id }}">{{ $cab->nama }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" id="qrisCabang" value="{{ $cabangs->first()?->id ?? $activeCabang?->id ?? 1 }}">
            @endif
            <form id="qrisForm" method="POST" action="{{ route('settings.upload-qris') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="cabang_id" id="qrisCabangId" value="{{ $cabangs->first()?->id ?? $activeCabang?->id ?? 1 }}">
                @if($cabangs->count() === 1)
                @php
                    $existingQris = \App\Models\Setting::get("qris_image_" . ($cabangs->first()?->id ?? $activeCabang?->id ?? 1));
                @endphp
                @if($existingQris)
                <div style="margin-bottom:12px;text-align:center">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($existingQris) }}" style="max-width:200px;border-radius:10px;border:1px solid #e2e8f0">
                </div>
                @endif
                @endif
                <div class="form-group">
                    <label>Upload Gambar QRIS</label>
                    <input type="file" name="qris_image" class="form-input" accept="image/*" onchange="previewQris(this)">
                </div>
                <div id="qrisPreview" style="margin-bottom:12px"></div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-upload"></i> Upload QRIS</button>
            </form>
        </div>

        {{-- BACKUP: Super Admin Only --}}
        @if(auth()->user()->isSuperAdmin())
        <div class="card mb-4">
            <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-database" style="color:var(--info);margin-right:6px"></i>Backup Database</h3>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
                <form method="POST" action="{{ route('settings.backup-db') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Backup SQL</button>
                </form>
                <form method="POST" action="{{ route('settings.backup-json') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-file-code"></i> Backup JSON</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-upload" style="color:var(--success);margin-right:6px"></i>Restore Data (JSON)</h3>
            <p class="text-xs text-muted" style="margin-bottom:12px">Restore data dari file JSON yang sudah di-backup sebelumnya.</p>
            <form method="POST" action="{{ route('settings.restore-json') }}" enctype="multipart/form-data" onsubmit="return confirm('PERHATIAN! Data yang ada sekarang akan ditimpa. Lanjutkan?')">
                @csrf
                <div class="form-group">
                    <input type="file" name="json_file" class="form-input" accept=".json,.txt" required>
                </div>
                <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-upload"></i> Restore dari JSON</button>
            </form>
        </div>
        @endif

        {{-- ====== SUPER ADMIN ONLY ====== --}}
        @if(auth()->user()->isSuperAdmin())

        {{-- Fitur #8: Payment Gateway --}}
        <div class="card mb-4" style="border:1px solid #bfdbfe">
            <h3 style="font-size:.95rem;margin-bottom:14px"><i class="fas fa-credit-card" style="color:#2563eb;margin-right:6px"></i>Payment Gateway (Tripay)</h3>
            <p class="text-xs text-muted" style="margin-bottom:12px">Integrasi pembayaran online (VA / QRIS / E-Wallet / Bank Transfer). Status terverifikasi otomatis via webhook. Daftar di <a href="https://tripay.co.id" target="_blank" style="color:var(--primary)">tripay.co.id</a>.</p>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf @method('POST')
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
                    <div class="form-group">
                        <label>Provider</label>
                        <select name="pg_provider" class="form-input">
                            <option value="manual" {{ ($settings['pg_provider'] ?? '') === 'manual' ? 'selected' : '' }}>Manual (admin verifikasi)</option>
                            <option value="tripay" {{ in_array(($settings['pg_provider'] ?? ''), ['', 'tripay']) ? 'selected' : '' }}>Tripay</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mode</label>
                        <select name="pg_mode" class="form-input">
                            <option value="sandbox" {{ ($settings['pg_mode'] ?? '') !== 'production' ? 'selected' : '' }}>Sandbox (uji coba)</option>
                            <option value="production" {{ ($settings['pg_mode'] ?? '') === 'production' ? 'selected' : '' }}>Production</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>API Key</label>
                        <input type="text" name="pg_api_key" class="form-input" value="{{ $settings['pg_api_key'] ?? '' }}" placeholder="Txxxxxx" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Private Key</label>
                        <input type="password" name="pg_private_key" class="form-input" value="{{ $settings['pg_private_key'] ?? '' }}" placeholder="xxxx-xxxx" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Merchant Code</label>
                        <input type="text" name="pg_merchant_code" class="form-input" value="{{ $settings['pg_merchant_code'] ?? '' }}" placeholder="Mxxxx" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Webhook Token (verifikasi)</label>
                        <input type="text" name="pg_webhook_token" class="form-input" value="{{ $settings['pg_webhook_token'] ?? '' }}" placeholder="token acak" autocomplete="off">
                    </div>
                </div>
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 12px;font-size:.74rem;color:#1e40af;margin:10px 0">
                    <strong>Webhook URL:</strong> <code>{{ url('/payment/webhook') }}</code><br>
                    Masukkan URL ini di dashboard Tripay → <em>Merchant → Callback / Webhook</em>.
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Simpan Payment Gateway</button>
            </form>
        </div>

        {{-- Fitur #9: WhatsApp Web webhook token --}}
        <div class="card mb-4" style="border:1px solid #d1fae5">
            <h3 style="font-size:.95rem;margin-bottom:14px"><i class="fab fa-whatsapp" style="color:#25D366;margin-right:6px"></i>WhatsApp Web (Fonnte)</h3>
            <p class="text-xs text-muted" style="margin-bottom:12px">API Key Fonnte diisi per-cabang di atas (kolom <em>API Key (Fonnte)</em>). Konfigurasi webhook untuk menerima pesan masuk otomatis.</p>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf @method('POST')
                <div class="form-group">
                    <label>Webhook Token (keamanan)</label>
                    <input type="text" name="wa_webhook_token" class="form-input" value="{{ $settings['wa_webhook_token'] ?? '' }}" placeholder="token acak untuk verifikasi webhook">
                </div>
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 12px;font-size:.74rem;color:#166534;margin:10px 0">
                    <strong>Webhook URL:</strong> <code>{{ url('/whatsapp/webhook') }}</code>@if(!empty($settings['wa_webhook_token']))?token={{ $settings['wa_webhook_token'] }}@endif<br>
                    Masukkan URL ini di dashboard Fonnte → <em>Webhook</em>.
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Simpan Konfigurasi WA</button>
            </form>
        </div>

        {{-- Fitur #12: Nomor WhatsApp Admin (untuk minta kode aktivasi saat expired) --}}
        <div class="card mb-4" style="border:1px solid #fde68a;background:#fffbeb">
            <h3 style="font-size:.95rem;margin-bottom:14px"><i class="fab fa-whatsapp" style="color:#25D366;margin-right:6px"></i>Nomor WhatsApp Admin</h3>
            <p class="text-xs text-muted" style="margin-bottom:12px">Nomor ini dipakai di halaman login. User yang <strong>masa aktifnya habis</strong> bisa minta kode aktivasi via tombol WhatsApp. Generate kodenya di menu <a href="{{ route('activation-code.index') }}" style="color:var(--primary)">Kode Aktivasi</a>.</p>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf @method('POST')
                <div class="form-group">
                    <label>Nomor WhatsApp Admin</label>
                    <input type="text" name="admin_wa_number" class="form-input" value="{{ $settings['admin_wa_number'] ?? '' }}" placeholder="62xxxxxxxxxxx" autocomplete="off">
                    <div class="text-xs text-muted" style="margin-top:4px">Format internasional tanpa <code>+</code>, contoh: <code>6281234567890</code>.</div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="background:#25D366"><i class="fas fa-save"></i> Simpan Nomor WA</button>
            </form>
        </div>

        <div class="card mb-4" style="border:2px solid #fca5a5;background:#fff5f5">
            <h3 style="font-size:.95rem;margin-bottom:12px;color:#dc2626"><i class="fas fa-exclamation-triangle" style="margin-right:6px"></i>Hard Reset</h3>
            <p class="text-xs text-muted" style="margin-bottom:12px;color:#991b1b">Menghapus SEMUA data transaksi (servis, kas, penjualan, jual beli). Data user, stok, dan pengaturan tetap ada.</p>
            <form method="POST" action="{{ route('settings.data-reset') }}" onsubmit="return confirm('PERHATIAN! SEMUA DATA TRANSAKSI AKAN DIHAPUS PERMANEN! Ketik HARD RESET untuk konfirmasi.')">
                @csrf
                <div class="form-group">
                    <label style="color:#991b1b">Ketik "HARD RESET" untuk konfirmasi</label>
                    <input type="text" name="confirm_text" class="form-input" placeholder="HARD RESET" required pattern="^HARD RESET$" title="Ketik persis: HARD RESET">
                </div>
                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hard Reset Data</button>
            </form>
        </div>

        {{-- Hapus Akun Demo/Default --}}
        <div class="card mb-4" style="border:2px solid #fcd34d;background:#fffbeb">
            <h3 style="font-size:.95rem;margin-bottom:12px;color:#b45309"><i class="fas fa-user-times" style="margin-right:6px"></i>Hapus Akun Demo</h3>
            <p class="text-xs text-muted" style="margin-bottom:12px;color:#92400e">Menghapus akun bawaan/demo bawaan seeder (<code>admin@fixpro.id</code>, <code>staff@fixpro.id</code>). Akun Anda sendiri & akun non-demo tidak ikut dihapus. Lakukan setelah aplikasi go-live.</p>
            <form method="POST" action="{{ route('settings.delete-default-accounts') }}" onsubmit="return confirm('Yakin hapus akun demo (admin@fixpro.id, staff@fixpro.id)? Akun Anda sendiri tidak akan dihapus.')">
                @csrf
                <div class="form-group">
                    <label style="color:#92400e">Ketik "HAPUS AKUN DEMO" untuk konfirmasi</label>
                    <input type="text" name="confirm_text" class="form-input" placeholder="HAPUS AKUN DEMO" required pattern="^HAPUS AKUN DEMO$" title="Ketik persis: HAPUS AKUN DEMO">
                </div>
                <button type="submit" class="btn btn-warning btn-sm" style="background:#b45309;color:#fff"><i class="fas fa-user-times"></i> Hapus Akun Demo</button>
            </form>
        </div>
        @endif

        <div class="card mb-4">
            <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-info-circle" style="color:var(--info);margin-right:6px"></i>Info Aplikasi</h3>
            <table style="width:100%;font-size:.84rem">
                <tr><td class="text-muted" style="padding:8px 0">Versi</td><td>FIXPRO Laravel v2.0</td></tr>
                <tr><td class="text-muted" style="padding:8px 0">Framework</td><td>Laravel {{ app()->version() }}</td></tr>
                <tr><td class="text-muted" style="padding:8px 0">PHP</td><td>{{ PHP_VERSION }}</td></tr>
                <tr><td class="text-muted" style="padding:8px 0">Cabang</td><td><strong>{{ $activeCabang->nama ?? auth()->user()->cabang?->nama ?? '-' }}</strong></td></tr>
            </table>
        </div>

    </div>
</div>

<script>
function loadQris() {
    const selectEl = document.getElementById('qrisCabang');
    const cabId = selectEl.tagName === 'SELECT' ? selectEl.value : selectEl.value;
    const form = document.getElementById('qrisForm');
    const preview = document.getElementById('qrisPreview');
    if (cabId) {
        form.style.display = 'block';
        document.getElementById('qrisCabangId').value = cabId;
        preview.innerHTML = '<div style="padding:12px;text-align:center;color:#94a3b8;font-size:.8rem">Memuat...</div>';
        fetch('/settings/qris/' + cabId)
            .then(r => r.json())
            .then(data => {
                if (data.image) {
                    preview.innerHTML = '<div style="text-align:center"><img src="' + data.image + '" style="max-width:200px;border-radius:8px;border:1px solid #e2e8f0"></div>';
                } else {
                    preview.innerHTML = '<div style="padding:12px;text-align:center;color:#94a3b8;font-size:.8rem">Belum ada QRIS untuk cabang ini</div>';
                }
            })
            .catch(() => {
                preview.innerHTML = '';
            });
    } else {
        form.style.display = 'none';
    }
}
// Auto-load jika hanya 1 cabang
@if($cabangs->count() === 1)
loadQris();
@endif

function previewQris(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('qrisPreview').innerHTML = '<div style="text-align:center"><img src="' + e.target.result + '" style="max-width:200px;border-radius:8px;border:1px solid #e2e8f0"></div>';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleBotProvider() {
    const provider = document.getElementById('botProvider').value;
    document.getElementById('providerDefault').style.display = provider === 'default' ? 'block' : 'none';
    document.getElementById('providerGemini').style.display = provider === 'gemini' ? 'block' : 'none';
    document.getElementById('providerGroq').style.display = provider === 'groq' ? 'block' : 'none';
    document.getElementById('providerOpenai').style.display = provider === 'openai' ? 'block' : 'none';
}
// Init on page load
toggleBotProvider();

function testBot() {
    const provider = document.getElementById('botProvider').value;
    const btn = document.querySelector('button[id^="btnTestBot"]');
    const resultId = provider === 'gemini' ? 'botTestResult' : provider === 'groq' ? 'botTestResultGroq' : 'botTestResultOpenai';
    const result = document.getElementById(resultId) || document.getElementById('botTestResult');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    result.style.display = 'block';
    result.style.background = '#fef3c7';
    result.style.color = '#92400e';
    result.style.border = '1px solid #fde68a';
    result.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengetes koneksi ke ' + provider.toUpperCase() + '...';

    fetch('/api/test-bot', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-flask"></i> Test';
        if (data.success) {
            result.style.background = '#f0fdf4';
            result.style.color = '#166534';
            result.style.border = '1px solid #bbf7d0';
            result.innerHTML = data.message + '<br><small style="opacity:.7">Balasan: ' + (data.reply || '-') + '</small>';
        } else {
            result.style.background = '#fef2f2';
            result.style.color = '#991b1b';
            result.style.border = '1px solid #fecaca';
            result.innerHTML = data.message + (data.details ? '<br><small style="opacity:.7">' + data.details.join('<br>') + '</small>' : '');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-flask"></i> Test';
        result.style.background = '#fef2f2';
        result.style.color = '#991b1b';
        result.style.border = '1px solid #fecaca';
        result.innerHTML = 'Gagal menghubungi server: ' + err.message;
    });
}

function testFonnteKey() {
    const key = document.getElementById('waApiKeyInput')?.value || '';
    const box = document.getElementById('fonnteTestResult');
    const btn = event?.target;
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menguji...'; }
    box.innerHTML = '<span style="color:#64748b"><i class="fas fa-spinner fa-spin"></i> Memvalidasi API Key ke Fonnte...</span>';

    fetch('{{ route("settings.test-fonnte") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ api_key: key })
    })
    .then(r => r.json())
    .then(data => {
        if (data.valid) {
            box.innerHTML = '<span style="color:#16a34a;font-weight:600"><i class="fas fa-check-circle"></i> ' + data.message + (data.devices !== undefined ? ' (' + data.connected + '/' + data.devices + ' device terhubung)' : '') + '</span>';
            box.style.background = '#f0fdf4';
        } else {
            box.innerHTML = '<span style="color:#dc2626;font-weight:600"><i class="fas fa-times-circle"></i> ' + (data.message || 'API Key tidak valid') + '</span>';
            box.style.background = '#fef2f2';
        }
    })
    .catch(err => {
        box.innerHTML = '<span style="color:#dc2626"><i class="fas fa-times-circle"></i> Gagal: ' + err.message + '</span>';
    })
    .finally(() => {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-plug"></i> Test Koneksi API Key'; }
    });
}
</script>
@endsection
