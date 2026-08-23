@extends('layouts.app')
@section('title', 'Kelola Banner Iklan')

@section('content')
<style>
.banner-alert{position:fixed;top:20px;right:20px;z-index:9999;padding:14px 22px;border-radius:10px;color:#fff;font-size:.85rem;font-weight:600;max-width:400px;box-shadow:0 8px 25px rgba(0,0,0,.15);animation:slideIn .3s ease;display:none}
.banner-alert.show{display:flex;align-items:center;gap:10px}
.banner-alert.success{background:#16a34a}
.banner-alert.error{background:#dc2626}
.banner-alert.info{background:#2563eb}
@keyframes slideIn{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
.banner-loading{display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:9998;align-items:center;justify-content:center}
.banner-loading.show{display:flex}
.banner-loading .spinner{width:40px;height:40px;border:4px solid #e2e8f0;border-top-color:var(--primary);border-radius:50%;animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.banner-img-preview{max-width:120px;max-height:80px;border-radius:8px;margin-top:6px;border:1px solid #e2e8f0;object-fit:cover}
</style>

<!-- Alert -->
<div id="bannerAlert" class="banner-alert"><span id="bannerAlertText"></span></div>
<!-- Loading -->
<div id="bannerLoading" class="banner-loading"><div class="spinner"></div></div>

<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-ad" style="color:var(--primary);margin-right:6px"></i> Kelola Banner Iklan</h2>
</div>

<!-- Form tambah -->
<div class="card" id="addCard">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:6px"></i> Tambah Banner Baru</h3>
    <div class="form-row">
        <div class="form-group">
            <label>Judul Banner *</label>
            <input type="text" id="addJudul" class="form-input" placeholder="Contoh: Promo Training">
        </div>
        <div class="form-group">
            <label>Link Tujuan (URL)</label>
            <input type="text" id="addLink" class="form-input" placeholder="https://example.com">
        </div>
    </div>
    <div class="form-group">
        <label>Deskripsi / Konten (Rich Text)</label>
        <div id="addEditor" style="min-height:220px;background:#fff;border-radius:8px;font-size:.85rem"></div>
        <div style="font-size:.7rem;color:#94a3b8;margin-top:4px">Mendukung format teks: bold, italic, warna, list, link, heading, gambar, dll.</div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>Gambar Banner (Upload)</label>
            <input type="file" id="addGambar" accept="image/*" class="form-input">
            <div style="font-size:.7rem;color:#94a3b8;margin-top:4px">JPG/PNG, Maks 2MB. Rekomendasi: 400x600px portrait</div>
        </div>
        <div class="form-group">
            <label>Atau URL Gambar</label>
            <input type="text" id="addGambarUrl" class="form-input" placeholder="https://example.com/gambar.jpg">
            <div style="font-size:.7rem;color:#94a3b8;margin-top:4px">Upload file diabaikan jika URL diisi</div>
        </div>
    </div>
    <div class="form-group">
        <label>Urutan Tampil</label>
        <input type="number" id="addUrutan" class="form-input" value="1" min="1" style="max-width:120px">
    </div>
    <button type="button" onclick="submitBanner()" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Banner</button>
</div>

<!-- Daftar banner -->
<div class="card" style="margin-top:20px">
    <h3 style="font-size:.95rem;margin-bottom:16px">Daftar Banner (<span id="bannerCount">0</span>)</h3>
    <div id="bannerList"></div>
</div>

<!-- Modal Edit -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:28px;max-width:600px;width:92%;max-height:90vh;overflow-y:auto">
        <h3 style="font-size:1rem;margin-bottom:16px"><i class="fas fa-edit" style="color:var(--primary);margin-right:6px"></i> Edit Banner</h3>
        <input type="hidden" id="editId">
        <div class="form-group">
            <label>Judul *</label>
            <input type="text" id="editJudul" class="form-input">
        </div>
        <div class="form-group">
            <label>Deskripsi / Konten (Rich Text)</label>
            <div id="editEditor" style="min-height:220px;background:#fff;border-radius:8px;font-size:.85rem"></div>
        </div>
        <div class="form-group">
            <label>Link URL</label>
            <input type="text" id="editLink" class="form-input">
        </div>
        <div class="form-group">
            <label>Gambar Baru (kosongkan jika tidak ganti)</label>
            <input type="file" id="editGambar" accept="image/*" class="form-input">
        </div>
        <div class="form-group">
            <label>Atau URL Gambar Baru</label>
            <input type="text" id="editGambarUrl" class="form-input" placeholder="https://example.com/gambar.jpg">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Urutan</label>
                <input type="number" id="editUrutan" class="form-input" min="1">
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;margin-top:24px;cursor:pointer">
                    <input type="checkbox" id="editAktif" value="1" style="width:18px;height:18px;accent-color:var(--primary)">
                    <span>Aktif</span>
                </label>
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:16px">
            <button type="button" onclick="submitEditBanner()" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <button type="button" class="btn btn-secondary" onclick="closeModal()"><i class="fas fa-times"></i> Batal</button>
        </div>
    </div>
</div>

<!-- Quill Editor CDN -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<style>
    .ql-toolbar.ql-snow{border:1.5px solid #e2e8f0!important;border-radius:8px 8px 0 0!important;background:#f8fafc}
    .ql-container.ql-snow{border:1.5px solid #e2e8f0!important;border-top:none!important;border-radius:0 0 8px 8px!important;font-family:'Inter',sans-serif;font-size:14px;color:#475569;min-height:180px;max-height:400px;overflow-y:auto}
    .ql-editor{min-height:180px;max-height:370px;overflow-y:auto;padding:12px 15px;line-height:1.7}
    .ql-editor img{max-width:100%;height:auto;border-radius:6px}
    .ql-editor.ql-blank::before{font-style:normal;color:#94a3b8}
    .ql-snow .ql-tooltip{z-index:10000}
    #editModal .ql-container.ql-snow{max-height:300px}
    #editModal .ql-editor{max-height:270px}
</style>
<script>
function getCSRF() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

const quillConfig = {
    theme: 'snow',
    placeholder: 'Tulis deskripsi banner dengan format lengkap...',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            [{ 'align': [] }],
            ['link', 'image'],
            ['clean']
        ]
    }
};
let addQuill = null;
let editQuill = null;

document.addEventListener('DOMContentLoaded', function() {
    addQuill = new Quill('#addEditor', quillConfig);
    // Render banner list directly from Blade data (no extra fetch needed)
    const banners = @json($banners);
    renderBannerList(banners);
});

// ── Show Alert ──
function showAlert(msg, type) {
    const el = document.getElementById('bannerAlert');
    const txt = document.getElementById('bannerAlertText');
    el.className = 'banner-alert show ' + (type || 'info');
    txt.textContent = msg;
    setTimeout(() => { el.className = 'banner-alert'; }, 6000);
}

// ── Show/Hide Loading ──
function showLoading() { document.getElementById('bannerLoading').classList.add('show'); }
function hideLoading() { document.getElementById('bannerLoading').classList.remove('show'); }

// ── AJAX Helper (FIXED: HTML response = ERROR, not success) ──
async function ajaxPost(url, formData) {
    // Always refresh CSRF token from meta tag
    const csrf = getCSRF();
    if (csrf) formData.set('_token', csrf);

    showLoading();
    try {
        const resp = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const text = await resp.text();

        // If response is HTML → something went wrong (CSRF, auth, server error, redirect to login)
        if (text.includes('<!DOCTYPE') || text.includes('<html')) {
            hideLoading();
            let errMsg = 'Terjadi kesalahan. Coba refresh halaman.';
            if (text.includes('419') || text.includes('CSRF') || text.includes('TokenMismatch')) {
                errMsg = 'Sesi telah berakhir (CSRF expired). Silakan REFRESH halaman terlebih dahulu, lalu coba lagi.';
            } else if (text.includes('login') || text.includes('Login') || text.includes('Sign in')) {
                errMsg = 'Sesi telah berakhir. Silakan login kembali.';
            } else if (text.includes('403') || text.includes('Forbidden')) {
                errMsg = 'Akses ditolak. Anda tidak punya izin.';
            } else if (text.includes('500') || text.includes('Server Error')) {
                errMsg = 'Server error (500). Hubungi admin.';
            } else if (text.includes('429') || text.includes('Too Many')) {
                errMsg = 'Terlalu banyak request. Tunggu sebentar.';
            }
            console.error('[Banner AJAX] Server returned HTML error page:', resp.status, url);
            return { ok: false, error: errMsg, status: resp.status };
        }

        // Response is JSON (expected from AJAX endpoints)
        try {
            const json = JSON.parse(text);
            if (resp.ok) {
                // 2xx response with JSON = success
                hideLoading();
                return { ok: true, data: json };
            } else {
                // 4xx/5xx response with JSON = error with details
                hideLoading();
                let errMsg = json.error || json.message || ('Error ' + resp.status);
                if (json.errors) {
                    const firstErr = Object.values(json.errors)[0];
                    if (Array.isArray(firstErr) && firstErr.length) errMsg = firstErr[0];
                }
                console.error('[Banner AJAX] Error:', resp.status, errMsg);
                return { ok: false, error: errMsg, status: resp.status };
            }
        } catch(e) {
            // Not valid JSON either
            hideLoading();
            console.error('[Banner AJAX] Unexpected response:', resp.status, text.substring(0, 200));
            return { ok: false, error: 'Response tidak dikenali. Coba refresh halaman.', status: resp.status };
        }
    } catch(e) {
        hideLoading();
        console.error('[Banner AJAX] Network error:', e);
        return { ok: false, error: 'Gagal menghubungi server: ' + e.message };
    }
}

function renderBannerList(banners) {
    const list = document.getElementById('bannerList');
    const count = document.getElementById('bannerCount');
    count.textContent = banners.length;
    if (!banners.length) {
        list.innerHTML = '<p style="color:#94a3b8;text-align:center;padding:20px">Belum ada banner.</p>';
        return;
    }
    let html = '<div class="table-wrap"><table><thead><tr><th>#</th><th>Preview</th><th>Judul</th><th>Status</th><th>Urutan</th><th>Aksi</th></tr></thead><tbody>';
    banners.forEach(function(b, i) {
        const imgUrl = b.gambar ? (b.gambar.startsWith('http') ? b.gambar : '/storage/' + b.gambar) : '';
        html += '<tr>';
        html += '<td>' + (i+1) + '</td>';
        html += '<td>' + (imgUrl ? '<img src="'+imgUrl+'" style="width:50px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0">' : '-') + '</td>';
        html += '<td><strong>' + (b.judul || '-') + '</strong></td>';
        html += '<td>' + (b.aktif ? '<span class="badge badge-selesai">Aktif</span>' : '<span class="badge badge-pending">Nonaktif</span>') + '</td>';
        html += '<td>' + (b.urutan || '-') + '</td>';
        html += '<td style="white-space:nowrap">';
        html += '<button type="button" class="btn btn-primary btn-xs" onclick="openEdit('+b.id+')"><i class="fas fa-edit"></i></button> ';
        html += '<button type="button" class="btn btn-danger btn-xs" onclick="deleteBanner('+b.id+')"><i class="fas fa-trash"></i></button>';
        html += '</td></tr>';
    });
    html += '</tbody></table></div>';
    list.innerHTML = html;
}

// ── Submit Tambah Banner ──
async function submitBanner() {
    const judul = document.getElementById('addJudul').value.trim();
    const link = document.getElementById('addLink').value.trim();
    const urutan = document.getElementById('addUrutan').value;
    const deskripsi = addQuill ? addQuill.root.innerHTML : '';
    const gambarFile = document.getElementById('addGambar').files[0];
    const gambarUrl = document.getElementById('addGambarUrl').value.trim();

    if (!judul) { showAlert('Judul wajib diisi!', 'error'); return; }
    if (!gambarFile && !gambarUrl) { showAlert('Upload gambar atau masukkan URL gambar!', 'error'); return; }

    const fd = new FormData();
    fd.append('judul', judul);
    fd.append('deskripsi', deskripsi);
    fd.append('link', link || '#');
    fd.append('urutan', urutan || 1);
    if (gambarFile) fd.append('gambar', gambarFile);
    if (gambarUrl) fd.append('gambar_url', gambarUrl);

    const result = await ajaxPost('/banner-iklan', fd);
    if (result.ok) {
        showAlert('✅ Banner berhasil ditambahkan!', 'success');
        // Reset form
        document.getElementById('addJudul').value = '';
        document.getElementById('addLink').value = '';
        document.getElementById('addGambar').value = '';
        document.getElementById('addGambarUrl').value = '';
        document.getElementById('addUrutan').value = '1';
        if (addQuill) addQuill.setText('');
        setTimeout(() => location.reload(), 800);
    } else {
        showAlert('❌ ' + result.error, 'error');
    }
}

// ── Open Edit Modal ──
const ALL_BANNERS = @json($banners);

function openEdit(id) {
    const banner = ALL_BANNERS.find(b => b.id == id);
    if (!banner) { showAlert('Banner tidak ditemukan!', 'error'); return; }

    document.getElementById('editId').value = banner.id;
    document.getElementById('editJudul').value = banner.judul || '';
    document.getElementById('editLink').value = banner.link || '';
    document.getElementById('editAktif').checked = banner.aktif == 1;
    document.getElementById('editUrutan').value = banner.urutan || 1;
    document.getElementById('editModal').style.display = 'flex';

    setTimeout(function() {
        if (editQuill) {
            const container = document.getElementById('editEditor');
            container.innerHTML = '';
            editQuill = null;
        }
        editQuill = new Quill('#editEditor', quillConfig);
        if (banner.deskripsi && banner.deskripsi.trim() !== '' && banner.deskripsi !== '<p><br></p>') {
            const delta = editQuill.clipboard.convert(banner.deskripsi);
            editQuill.setContents(delta, 'silent');
        } else {
            editQuill.setText('', 'silent');
        }
    }, 150);
}

// ── Submit Edit Banner ──
async function submitEditBanner() {
    const id = document.getElementById('editId').value;
    const judul = document.getElementById('editJudul').value.trim();
    const link = document.getElementById('editLink').value.trim();
    const urutan = document.getElementById('editUrutan').value;
    const aktif = document.getElementById('editAktif').checked;
    const deskripsi = editQuill ? editQuill.root.innerHTML : '';
    const gambarFile = document.getElementById('editGambar').files[0];
    const gambarUrl = document.getElementById('editGambarUrl').value.trim();

    if (!judul) { showAlert('Judul wajib diisi!', 'error'); return; }

    const fd = new FormData();
    fd.append('_method', 'PUT');
    fd.append('judul', judul);
    fd.append('deskripsi', deskripsi);
    fd.append('link', link || '#');
    fd.append('urutan', urutan || 1);
    fd.append('aktif', aktif ? '1' : '');
    if (gambarFile) fd.append('gambar', gambarFile);
    if (gambarUrl) fd.append('gambar_url', gambarUrl);

    const result = await ajaxPost('/banner-iklan/' + id, fd);
    if (result.ok) {
        showAlert('✅ Banner berhasil diupdate!', 'success');
        closeModal();
        setTimeout(() => location.reload(), 800);
    } else {
        showAlert('❌ ' + result.error, 'error');
    }
}

// ── Delete Banner ──
async function deleteBanner(id) {
    if (!confirm('Hapus banner ini?')) return;

    const fd = new FormData();
    fd.append('_method', 'DELETE');

    const result = await ajaxPost('/banner-iklan/' + id, fd);
    if (result.ok) {
        showAlert('✅ Banner berhasil dihapus!', 'success');
        setTimeout(() => location.reload(), 800);
    } else {
        showAlert('❌ ' + result.error, 'error');
    }
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
    document.getElementById('editGambar').value = '';
    document.getElementById('editGambarUrl').value = '';
}
</script>
@endsection
