@extends('layouts.app')
@section('title', 'WhatsApp Web — Inbox')

@section('content')
<h2 class="mb-4" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <i class="fab fa-whatsapp" style="color:#25D366;font-size:1.5rem"></i> WhatsApp Inbox
    @if($enabled)
    <span id="deviceBadge" class="wa-badge {{ ($deviceStatus['connected'] ?? false) ? 'online' : 'offline' }}">
        <span class="dot"></span>
        @if(($deviceStatus['connected'] ?? false)) Terhubung @else Terputus @endif
    </span>
    @else
    <span class="wa-badge offline"><span class="dot"></span> Belum dikonfigurasi</span>
    @endif

    {{-- Dropdown WhatsApp Web --}}
    <div style="position:relative;display:inline-block" id="waDropdown">
        <button onclick="toggleWaMenu()" class="btn btn-success btn-sm" style="font-size:.82rem" id="waMenuBtn">
            <i class="fab fa-whatsapp"></i> WhatsApp Web <i class="fas fa-chevron-down" style="font-size:.65rem;margin-left:4px"></i>
        </button>
        <div id="waMenuList" style="display:none;position:absolute;top:100%;left:0;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:260px;z-index:999;padding:6px;margin-top:4px">
            <button onclick="openWaMini()" class="wa-menu-item">
                <i class="fas fa-window-restore" style="color:#25D366"></i>
                <span><strong>Mini Window</strong><br><small style="color:#64748b">Jendela kecil mengambang</small></span>
            </button>
            <button onclick="openWaSide()" class="wa-menu-item">
                <i class="fas fa-columns" style="color:#25D366"></i>
                <span><strong>Split Layar</strong><br><small style="color:#64748b">FixPro kiri, WA kanan</small></span>
            </button>
            <button onclick="openWaFull()" class="wa-menu-item">
                <i class="fas fa-desktop" style="color:#25D366"></i>
                <span><strong>Layar Penuh</strong><br><small style="color:#64748b">WA sepenuh layar</small></span>
            </button>
            <div style="border-top:1px solid #e2e8f0;margin:4px 0"></div>
            <button onclick="showWaGuide()" class="wa-menu-item">
                <i class="fas fa-lightbulb" style="color:#f59e0b"></i>
                <span><strong>Panduan Split Browser</strong><br><small style="color:#64748b">Cara split tanpa kode</small></span>
            </button>
            <button onclick="showShortcutInfo()" class="wa-menu-item">
                <i class="fas fa-keyboard" style="color:#6366f1"></i>
                <span><strong>Keyboard Shortcut</strong><br><small style="color:#64748b">Ctrl + Shift + W</small></span>
            </button>
        </div>
    </div>

    <span style="margin-left:auto;font-size:.8rem;color:#64748b" id="totalUnread">{{ $totalUnread }} pesan belum dibaca</span>
</h2>

{{-- Info Panel Pop-up --}}
<div id="waPopInfo" class="card mb-4" style="display:none;background:#ecfdf5;border:1px solid #86efac;color:#166534;padding:12px 16px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <div style="font-size:.84rem">
            <i class="fas fa-info-circle"></i>
            <strong>WhatsApp Web terbuka di jendela terpisah.</strong>
            Anda tetap berada di FixPro. Gunakan tombol
            <em>"Lihat WA"</em> untuk fokus ke jendela WhatsApp, atau tekan <kbd style="background:#d1fae5;padding:2px 6px;border-radius:4px;font-size:.75rem">Ctrl+Shift+W</kbd>.
        </div>
        <div style="display:flex;gap:6px">
            <button onclick="focusWaWindow()" class="btn btn-sm btn-success" style="font-size:.76rem;padding:4px 10px">
                <i class="fas fa-eye"></i> Lihat WA
            </button>
            <button onclick="closeWaWindow()" class="btn btn-sm btn-outline-danger" style="font-size:.76rem;padding:4px 10px">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
    </div>
</div>

{{-- Modal Panduan Split Browser --}}
<div id="waGuideModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:14px;max-width:560px;width:100%;max-height:85vh;overflow-y:auto;padding:24px;position:relative">
        <button onclick="closeModal('waGuideModal')" style="position:absolute;top:12px;right:14px;background:none;border:none;font-size:1.3rem;cursor:pointer;color:#94a3b8">&times;</button>
        <h3 style="margin:0 0 16px;font-size:1.05rem"><i class="fas fa-lightbulb" style="color:#f59e0b"></i> Panduan Split Browser (Tanpa Kode)</h3>
        <div style="font-size:.86rem;line-height:1.7;color:#334155">
            <p><strong>Windows (Snap Layout):</strong></p>
            <ol style="padding-left:20px;margin:6px 0 14px">
                <li>Buka FixPro di browser</li>
                <li>Tekan <kbd>Win</kbd> + <kbd>←</kbd> untuk snap FixPro ke kiri</li>
                <li>Tekan <kbd>Win</kbd> + <kbd>→</kbd> lalu pilih browser baru</li>
                <li>Buka <code>web.whatsapp.com</code> di browser tersebut</li>
            </ol>
            <p><strong>macOS (Split View):</strong></p>
            <ol style="padding-left:20px;margin:6px 0 14px">
                <li>Hover di tombol hijau (maximize) browser FixPro</li>
                <li>Pilih "Tile Window to Left of Screen"</li>
                <li>Pilih browser kedua di sisi kanan</li>
                <li>Buka <code>web.whatsapp.com</code></li>
            </ol>
            <p><strong>Chrome Side Panel (Chrome 114+):</strong></p>
            <ol style="padding-left:20px;margin:6px 0 14px">
                <li>Klik ikon Side Panel di toolbar Chrome</li>
                <li>Klik "Open" lalu buka <code>web.whatsapp.com</code></li>
                <li>Panel WA akan tampil di samping FixPro</li>
            </ol>
            <p style="background:#fef3c7;padding:10px 14px;border-radius:8px;font-size:.8rem;color:#92400e">
                <i class="fas fa-exclamation-triangle"></i> WhatsApp Web memblokir embedding di dalam halaman web lain (iframe/object/embed). Semua solusi di atas adalah cara resmi yang didukung browser.
            </p>
        </div>
    </div>
</div>

{{-- Modal Shortcut Info --}}
<div id="shortcutModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:14px;max-width:420px;width:100%;padding:24px;position:relative">
        <button onclick="closeModal('shortcutModal')" style="position:absolute;top:12px;right:14px;background:none;border:none;font-size:1.3rem;cursor:pointer;color:#94a3b8">&times;</button>
        <h3 style="margin:0 0 16px;font-size:1.05rem"><i class="fas fa-keyboard" style="color:#6366f1"></i> Keyboard Shortcut</h3>
        <table style="width:100%;font-size:.84rem;border-collapse:collapse">
            <tr style="border-bottom:1px solid #e2e8f0"><td style="padding:8px 0"><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>W</kbd></td><td>Buka / Fokus WhatsApp Web</td></tr>
            <tr style="border-bottom:1px solid #e2e8f0"><td style="padding:8px 0"><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>X</kbd></td><td>Tutup jendela WhatsApp</td></tr>
        </table>
    </div>
</div>

{{-- Floating Action Button --}}
<button id="waFab" onclick="focusOrOpenWa()" title="WhatsApp Web (Ctrl+Shift+W)" style="display:none;position:fixed;bottom:24px;right:24px;width:56px;height:56px;border-radius:50%;background:#25D366;color:#fff;border:none;font-size:1.5rem;cursor:pointer;box-shadow:0 4px 16px rgba(37,211,102,.4);z-index:998;transition:transform .2s" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
    <i class="fab fa-whatsapp"></i>
    <span id="waFabBadge" style="display:none;position:absolute;top:-2px;right:-2px;width:18px;height:18px;border-radius:50%;background:#ef4444;color:#fff;font-size:.65rem;line-height:18px;text-align:center">!</span>
</button>

@if(!$enabled)
<div class="card mb-4" style="background:#fef3c7;border:1px solid #fcd34d;color:#78350f;padding:14px 18px">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>WhatsApp belum aktif.</strong> Hubungkan akun Fonnte di menu <a href="{{ route('settings.index') }}" style="color:#b45309;text-decoration:underline">Pengaturan</a> (isi API Key Fonnte) lalu scan QR di bawah untuk masuk.
</div>
@endif

<div class="card mb-4" id="qrCard" style="@if(($deviceStatus['connected'] ?? false)) display:none @endif">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <h3 style="font-size:.92rem;margin:0"><i class="fas fa-qrcode" style="color:var(--primary)"></i> Login WhatsApp Web (Scan QR)</h3>
        <div style="display:flex;gap:6px">
            <button onclick="checkDeviceStatus()" class="btn btn-secondary btn-sm"><i class="fas fa-plug"></i> Cek Status</button>
            <button onclick="loadQr()" class="btn btn-secondary btn-sm"><i class="fas fa-sync"></i> Refresh QR</button>
        </div>
    </div>
    <div id="qrContainer" style="text-align:center;padding:20px">
        <p style="color:#64748b;font-size:.82rem">Klik "Refresh QR" untuk menampilkan kode QR. Scan dengan WhatsApp di HP → <strong>Setelan → WhatsApp Web → Tautkan perangkat</strong>.</p>
    </div>
</div>

<div class="card mb-4">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
        <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="Cari nama / nomor / isi pesan..." style="flex:1;min-width:200px;padding:8px 12px;font-size:.84rem">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
    </form>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3 style="font-size:.92rem;margin:0"><i class="fas fa-inbox"></i> Percakapan</h3>
        <span class="text-muted text-sm" id="serverTime"></span>
    </div>
    <div id="roomsList">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama / Nomor</th><th>Pesan Terakhir</th><th>Waktu</th><th>Status</th></tr></thead>
                <tbody id="roomsTbody">
                    @foreach($rooms as $room)
                    <tr data-room-id="{{ $room->id }}" data-last="{{ $room->last_message_at?->timestamp }}" style="cursor:pointer" onclick="location.href='{{ route('whatsapp.show', $room) }}'">
                        <td>
                            <strong>{{ $room->name ?: $room->number }}</strong>
                            @if($room->name)<br><span style="font-size:.7rem;color:#94a3b8">{{ $room->number }}</span>@endif
                        </td>
                        <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            @if($room->last_direction === 'out')<i class="fas fa-reply" style="color:#94a3b8;font-size:.7rem"></i> @endif
                            {{ $room->last_message ?: '(kosong)' }}
                        </td>
                        <td style="font-size:.74rem;color:#64748b">{{ $room->last_message_at?->diffForHumans() ?? '-' }}</td>
                        <td>
                            @if($room->unread > 0)
                            <span class="wa-badge unread-badge">{{ $room->unread }}</span>
                            @else
                            <span style="font-size:.7rem;color:#94a3b8">Dibaca</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    @if($rooms->count() === 0)
                    <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:24px">Belum ada percakapan masuk.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    {{ $rooms->links() }}
</div>

<style>
.wa-badge { display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:14px;font-size:.72rem;font-weight:700 }
.wa-badge .dot { width:8px;height:8px;border-radius:50%;display:inline-block }
.wa-badge.online { background:#dcfce7;color:#15803d }
.wa-badge.online .dot { background:#16a34a;animation:pulse 2s infinite }
.wa-badge.offline { background:#fee2e2;color:#b91c1c }
.wa-badge.offline .dot { background:#dc2626 }
.wa-badge.unread-badge { background:#25D366;color:#fff;min-width:22px;justify-content:center }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
#roomsTbody tr:hover { background:#f0fdf4 }
.wa-menu-item { display:flex;align-items:center;gap:10px;width:100%;padding:10px 12px;background:none;border:none;text-align:left;cursor:pointer;border-radius:8px;font-size:.84rem;transition:background .15s }
.wa-menu-item:hover { background:#f0fdf4 }
.wa-menu-item i { font-size:1.1rem;width:20px;text-align:center }
kbd { background:#e2e8f0;padding:2px 6px;border-radius:4px;font-size:.75rem;font-family:monospace }
</style>

<script>
let lastPollId = 0;
let qrAutoRetries = 0;
const QR_MAX_AUTO_RETRY = 3;

/* ===== WhatsApp Web Window Manager ===== */
let waWindow = null;
let waWindowTimer = null;
let waMode = localStorage.getItem('fixpro_wa_mode') || 'mini';

function openWaMini() {
    waMode = 'mini';
    localStorage.setItem('fixpro_wa_mode', 'mini');
    const w = 480, h = 720;
    const left = Math.max(0, Math.round((screen.width - w) / 2));
    const top = Math.max(0, Math.round((screen.height - h) / 3));
    waWindow = window.open(
        'https://web.whatsapp.com/',
        'fixpro_wa_window',
        `width=${w},height=${h},left=${left},top=${top},resizable=yes,scrollbars=yes,status=no,toolbar=no,menubar=no,location=no`
    );
    afterWaOpen();
}

function openWaSide() {
    waMode = 'side';
    localStorage.setItem('fixpro_wa_mode', 'side');
    const w = Math.round(screen.availWidth / 2);
    const h = screen.availHeight - 40;
    waWindow = window.open(
        'https://web.whatsapp.com/',
        'fixpro_wa_window',
        `width=${w},height=${h},left=${w},top=0,resizable=yes,scrollbars=yes,status=no,toolbar=no,menubar=no,location=no`
    );
    try { window.moveTo(0, 0); window.resizeTo(w, screen.availHeight); } catch (e) {}
    afterWaOpen();
}

function openWaFull() {
    waMode = 'full';
    localStorage.setItem('fixpro_wa_mode', 'full');
    waWindow = window.open(
        'https://web.whatsapp.com/',
        'fixpro_wa_window',
        `width=${screen.availWidth},height=${screen.availHeight},left=0,top=0,resizable=yes,scrollbars=yes`
    );
    afterWaOpen();
}

function afterWaOpen() {
    closeWaMenu();
    document.getElementById('waPopInfo').style.display = 'block';
    document.getElementById('waFab').style.display = 'block';
    startWatcher();
    showWaToast('WhatsApp Web dibuka');
}

function focusOrOpenWa() {
    if (waWindow && !waWindow.closed) {
        waWindow.focus();
    } else {
        // buka sesuai mode tersimpan
        if (waMode === 'side') openWaSide();
        else if (waMode === 'full') openWaFull();
        else openWaMini();
    }
}

function focusWaWindow() {
    if (waWindow && !waWindow.closed) waWindow.focus();
    else hideWaInfo();
}

function closeWaWindow() {
    if (waWindow && !waWindow.closed) waWindow.close();
    hideWaInfo();
    showWaToast('WhatsApp Web ditutup');
}

function hideWaInfo() {
    document.getElementById('waPopInfo').style.display = 'none';
    document.getElementById('waFab').style.display = 'none';
    if (waWindowTimer) { clearInterval(waWindowTimer); waWindowTimer = null; }
    waWindow = null;
}

function startWatcher() {
    if (waWindowTimer) clearInterval(waWindowTimer);
    waWindowTimer = setInterval(() => {
        if (!waWindow || waWindow.closed) hideWaInfo();
    }, 1000);
}

/* ===== Dropdown Menu ===== */
function toggleWaMenu() {
    const menu = document.getElementById('waMenuList');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}
function closeWaMenu() {
    document.getElementById('waMenuList').style.display = 'none';
}
document.addEventListener('click', (e) => {
    if (!document.getElementById('waDropdown').contains(e.target)) closeWaMenu();
});

/* ===== Modal ===== */
function showWaGuide() {
    closeWaMenu();
    document.getElementById('waGuideModal').style.display = 'flex';
}
function showShortcutInfo() {
    closeWaMenu();
    document.getElementById('shortcutModal').style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

/* ===== Toast ===== */
function showWaToast(msg) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:90px;right:24px;background:#1e293b;color:#fff;padding:10px 18px;border-radius:10px;font-size:.82rem;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.2);animation:fadeInUp .3s';
    t.innerHTML = '<i class="fab fa-whatsapp" style="color:#25D366"></i> ' + msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 2500);
}

/* ===== Keyboard Shortcuts ===== */
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey && e.shiftKey && e.key === 'W') {
        e.preventDefault();
        focusOrOpenWa();
    }
    if (e.ctrlKey && e.shiftKey && e.key === 'X') {
        e.preventDefault();
        closeWaWindow();
    }
});

/* ===== QR & Polling ===== */
async function loadQr() {
    const box = document.getElementById('qrContainer');
    const btn = event?.currentTarget;
    const isAutoRetry = !btn && qrAutoRetries > 0;
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...'; }
    if (!isAutoRetry) qrAutoRetries = 0;
    box.innerHTML = '<p style="color:#64748b;font-size:.82rem"><i class="fas fa-spinner fa-spin"></i> Memuat QR... Fonnte sedang menyiapkan kode, mohon tunggu beberapa detik.</p>';
    try {
        const r = await fetch('{{ route("whatsapp.qr") }}', { headers: { 'Accept': 'application/json' } });
        if (!r.ok && r.status !== 200) {
            let txt = '';
            try { txt = await r.text(); } catch(e) {}
            throw new Error('Server merespons HTTP ' + r.status + (txt ? ' (' + txt.substring(0,80) + ')' : ''));
        }
        const d = await r.json();

        if (d.connected) {
            qrAutoRetries = 0;
            const card = document.getElementById('qrCard');
            if (card) card.style.display = 'none';
            updateDeviceBadge(true);
            Swal && Swal.fire ? Swal.fire({icon:'success',title:'WhatsApp Terhubung',text:d.message||'Device sudah terhubung, QR tidak diperlukan.',timer:2500,showConfirmButton:false}) : alert(d.message || 'Device sudah terhubung');
            return;
        }

        if (d.success && d.qr) {
            qrAutoRetries = 0;
            box.innerHTML = '<div id="qrImg" style="display:inline-block;padding:14px;background:#fff;border:1px solid #e2e8f0;border-radius:12px"></div>' +
                '<p style="font-size:.78rem;color:#64748b;margin-top:8px">' + (d.message || '') + '</p>' +
                '<p style="font-size:.72rem;color:#94a3b8;margin-top:4px"><i class="fas fa-info-circle"></i> QR bisa di-scan ulang tiap 1 menit bila belum tertautkan.</p>';
            if (window.QRCode) {
                new QRCode(document.getElementById('qrImg'), { text: d.qr, width: 220, height: 220 });
            } else {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js';
                s.onload = () => new QRCode(document.getElementById('qrImg'), { text: d.qr, width: 220, height: 220 });
                document.body.appendChild(s);
            }
            return;
        }

        if (d.processing && qrAutoRetries < QR_MAX_AUTO_RETRY) {
            qrAutoRetries++;
            box.innerHTML = '<p style="color:#64748b;font-size:.82rem"><i class="fas fa-spinner fa-spin"></i> QR sedang dibuat di server Fonnte... percobaan ' + qrAutoRetries + '/' + QR_MAX_AUTO_RETRY + '</p>';
            setTimeout(loadQr, 4000);
            return;
        }

        qrAutoRetries = 0;
        box.innerHTML =
            '<div style="padding:14px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#991b1b;font-size:.82rem;text-align:left">' +
            '<i class="fas fa-times-circle"></i> <strong>Gagal memuat QR</strong><br>' +
            '<span style="font-size:.8rem">' + (d.message || 'Fonnte tidak dapat menghasilkan QR saat ini.') + '</span></div>' +
            '<div style="margin-top:10px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap">' +
            '<button onclick="loadQr(this)" class="btn btn-primary btn-sm"><i class="fas fa-redo"></i> Coba Lagi</button>' +
            '<button onclick="checkDeviceStatus(this)" class="btn btn-secondary btn-sm"><i class="fas fa-plug"></i> Cek Status Device</button>' +
            '</div>';
    } catch (e) {
        qrAutoRetries = 0;
        box.innerHTML =
            '<div style="padding:14px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#991b1b;font-size:.82rem;text-align:left">' +
            '<i class="fas fa-exclamation-triangle"></i> <strong>Gagal menghubungi server</strong><br>' +
            '<span style="font-size:.8rem">' + e.message + '</span></div>' +
            '<button onclick="loadQr(this)" class="btn btn-primary btn-sm" style="margin-top:10px"><i class="fas fa-redo"></i> Coba Lagi</button>';
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-sync"></i> Refresh QR'; }
    }
}

async function checkDeviceStatus(btn) {
    const box = document.getElementById('qrContainer');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengecek...'; }
    box.innerHTML = '<p style="color:#64748b;font-size:.82rem"><i class="fas fa-spinner fa-spin"></i> Mengecek status device...</p>';
    try {
        const r = await fetch('{{ route("whatsapp.device-status") }}', { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        if (d.connected) {
            updateDeviceBadge(true);
            const card = document.getElementById('qrCard');
            if (card) card.style.display = 'none';
            box.innerHTML = '<p style="color:#16a34a;font-size:.86rem"><i class="fas fa-check-circle"></i> <strong>Device terhubung!</strong> WhatsApp siap digunakan.</p>';
        } else {
            updateDeviceBadge(false);
            box.innerHTML = '<p style="color:#dc2626;font-size:.82rem"><i class="fas fa-times-circle"></i> Device belum terhubung. ' + (d.message || '') + '</p>' +
                '<button onclick="loadQr(this)" class="btn btn-primary btn-sm" style="margin-top:8px"><i class="fas fa-qrcode"></i> Tampilkan QR</button>';
        }
    } catch (e) {
        box.innerHTML = '<p style="color:#dc2626;font-size:.82rem">Gagal cek status: ' + e.message + '</p>';
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-plug"></i> Cek Status'; }
    }
}

function updateDeviceBadge(connected) {
    const badge = document.getElementById('deviceBadge');
    if (!badge) return;
    if (connected) {
        badge.className = 'wa-badge online';
        badge.innerHTML = '<span class="dot"></span> Terhubung';
    } else {
        badge.className = 'wa-badge offline';
        badge.innerHTML = '<span class="dot"></span> Terputus';
    }
}

async function pollRealtime() {
    try {
        const r = await fetch('{{ route("whatsapp.poll") }}?since_id=' + lastPollId);
        const d = await r.json();
        if (d.server_time) document.getElementById('serverTime').textContent = 'Update: ' + new Date(d.server_time).toLocaleTimeString();
        if (d.total_unread !== undefined) document.getElementById('totalUnread').textContent = d.total_unread + ' pesan belum dibaca';

        if (d.messages && d.messages.length > 0) {
            lastPollId = Math.max(lastPollId, ...d.messages.map(m => m.id));
            if (d.total_unread > 0) {
                if (document.title.indexOf('🔴') !== 0) document.title = '🔴 ' + document.title;
                // Tampilkan badge di FAB jika WA window terbuka
                if (waWindow && !waWindow.closed) {
                    const badge = document.getElementById('waFabBadge');
                    badge.style.display = 'block';
                    badge.textContent = d.total_unread > 9 ? '9+' : d.total_unread;
                }
            } else {
                document.title = document.title.replace('🔴 ', '');
                document.getElementById('waFabBadge').style.display = 'none';
            }
        }
    } catch (e) {}
}

@if($enabled)
setInterval(pollRealtime, 5000);
setTimeout(pollRealtime, 1000);
@endif

window.addEventListener('beforeunload', () => {
    if (waWindowTimer) clearInterval(waWindowTimer);
});
</script>
@endsection