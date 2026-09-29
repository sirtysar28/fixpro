{{-- ============================================================
     CARD BANNER PROMO — tampil di paling atas Dashboard (SEMUA ROLE)
     • Tampil 2 card per halaman; jika lebih → pagination (dots/panah
       bisa diklik) + bisa digeser (swipe/drag)
     • Card menampilkan: gambar, judul & tombol (teks + link bisa diisi)
     • Klik card → popup detail lengkap
     • Tambah / edit / hapus hanya SUPER ADMIN langsung dari dashboard
============================================================ --}}
@php
    // Guard: table belum ada (sebelum migrate di server) → partial skip tanpa error
    $__pcAll = \Schema::hasTable('promo_cards')
        ? \App\Models\PromoCard::orderBy('urutan')->orderBy('created_at')->get()
        : collect();
    $__pcActive = $__pcAll->where('aktif', true)->values();
    $__pcSuper  = auth()->user()->isSuperAdmin();

    $__pcData = $__pcActive->map(fn($c) => [
        'id'          => $c->id,
        'judul'       => $c->judul,
        'deskripsi'   => (string) $c->deskripsi,
        'gambar_url'  => $c->image_url,
        'tombol_text' => $c->tombol_text ?: 'Lihat Detail',
        'tombol_link' => ($c->tombol_link && $c->tombol_link !== '#') ? $c->tombol_link : null,
    ])->values();

    $__pcAdminData = $__pcAll->map(fn($c) => [
        'id'          => $c->id,
        'judul'       => $c->judul,
        'deskripsi'   => (string) $c->deskripsi,
        'gambar_url'  => $c->image_url,
        'tombol_text' => $c->tombol_text ?: 'Lihat Detail',
        'tombol_link' => $c->tombol_link,
        'aktif'       => (bool) $c->aktif,
        'urutan'      => (int) $c->urutan,
    ])->values();
@endphp

@if($__pcActive->count() > 0 || $__pcSuper)

{{-- Toast notifikasi --}}
<div class="pc-toast" id="pcToast"><span id="pcToastText"></span></div>

<div class="pc-wrap" id="pcWrap">
    <div class="pc-head">
        <div class="pc-head-title">
            <span class="pc-head-ico"><i class="fas fa-bullhorn"></i></span>
            Promo Untuk Anda
        </div>
        @if($__pcSuper)
        <button type="button" class="pc-manage" onclick="pcOpenAdmin()">
            <i class="fas fa-pen-to-square"></i> Kelola Promo
        </button>
        @endif
    </div>

    @if($__pcActive->count() > 0)
    <div class="pc-carousel">
        <button type="button" class="pc-nav pc-prev" id="pcNavPrev" onclick="pcGoPageRel(-1)" aria-label="Sebelumnya"><i class="fas fa-chevron-left"></i></button>

        <div class="pc-viewport" id="pcViewport">
            @foreach($__pcActive as $i => $c)
            <div class="pc-card" data-idx="{{ $i }}" role="button" tabindex="0" title="Klik untuk detail">
                <div class="pc-img">
                    @if($c->gambar)
                    <img src="{{ $c->image_url }}" alt="{{ $c->judul }}" loading="lazy" draggable="false">
                    @else
                    <div class="pc-img-empty"><i class="fas fa-image"></i></div>
                    @endif
                    <span class="pc-badge"><i class="fas fa-tags"></i> PROMO</span>
                </div>
                <div class="pc-body">
                    <div class="pc-title">{{ $c->judul }}</div>
                    @if($c->deskripsi)
                    <div class="pc-desc">{{ \Illuminate\Support\Str::limit(strip_tags($c->deskripsi), 100) }}</div>
                    @endif
                    @if($c->tombol_link && $c->tombol_link !== '#')
                    <a href="{{ $c->tombol_link }}" target="_blank" rel="noopener" class="pc-btn">
                        <i class="fas fa-rocket"></i> {{ $c->tombol_text ?: 'Lihat Detail' }}
                    </a>
                    @else
                    <button type="button" class="pc-btn">
                        <i class="fas fa-eye"></i> {{ $c->tombol_text ?: 'Lihat Detail' }}
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <button type="button" class="pc-nav pc-next" id="pcNavNext" onclick="pcGoPageRel(1)" aria-label="Berikutnya"><i class="fas fa-chevron-right"></i></button>
    </div>
    <div class="pc-dots" id="pcDots"></div>
    @else
    <div class="pc-empty">
        <i class="fas fa-bullhorn"></i>
        <span>Belum ada promo aktif.</span>
        @if($__pcSuper)
        <button type="button" class="pc-manage" onclick="pcOpenAdmin()"><i class="fas fa-plus"></i> Tambah Promo</button>
        @endif
    </div>
    @endif
</div>

{{-- ==================== POPUP DETAIL CARD ==================== --}}
<div class="pc-modal" id="pcDetailModal" onclick="if(event.target===this) pcCloseDetail()">
    <div class="pc-modal-box">
        <button type="button" class="pc-modal-close" onclick="pcCloseDetail()" aria-label="Tutup"><i class="fas fa-times"></i></button>
        <div class="pc-detail-imgwrap" id="pcDetailImgWrap">
            <img id="pcDetailImg" src="" alt="">
        </div>
        <div class="pc-detail-body">
            <div class="pc-detail-badge"><i class="fas fa-tags"></i> PROMO</div>
            <h3 class="pc-detail-title" id="pcDetailTitle"></h3>
            <div class="pc-detail-desc" id="pcDetailDesc"></div>
            <a href="#" id="pcDetailBtn" class="pc-btn pc-btn-lg" target="_blank" rel="noopener">
                <i class="fas fa-rocket"></i> <span id="pcDetailBtnText"></span>
            </a>
        </div>
    </div>
</div>

@if($__pcSuper)
{{-- ==================== MODAL KELOLA (SUPER ADMIN) ==================== --}}
<div class="pc-modal" id="pcAdminModal" onclick="if(event.target===this) pcCloseAdmin()">
    <div class="pc-modal-box pc-admin-box">
        <button type="button" class="pc-modal-close" onclick="pcCloseAdmin()" aria-label="Tutup"><i class="fas fa-times"></i></button>
        <div class="pc-admin-body">
            <h3 class="pc-admin-title"><i class="fas fa-bullhorn" style="color:var(--primary);margin-right:6px"></i> Kelola Card Promo Dashboard</h3>
            <p class="pc-admin-sub">Card promo tampil di paling atas dashboard semua role — 2 card per halaman. Klik card oleh user akan membuka popup detail.</p>

            <div class="pc-admin-toolbar">
                <span class="pc-admin-count" id="pcAdminCount"></span>
                <button type="button" class="pc-manage" onclick="pcShowForm(null)"><i class="fas fa-plus"></i> Tambah Promo</button>
            </div>
            <div id="pcAdminList"></div>

            {{-- Form Tambah / Edit --}}
            <div id="pcFormWrap" style="display:none">
                <h4 class="pc-form-title" id="pcFormTitle"><i class="fas fa-plus-circle"></i> Tambah Promo Baru</h4>
                <form id="pcForm" onsubmit="return pcSubmitForm(event)" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="pcFormId" value="">
                    <div class="pc-form-grid">
                        <div class="pc-field pc-field-full">
                            <label>Judul Promo *</label>
                            <input type="text" name="judul" id="pcFormJudul" class="form-input" maxlength="255" placeholder="Contoh: Diskon 50% Ganti Battery" required>
                        </div>
                        <div class="pc-field pc-field-full">
                            <label>Deskripsi / Detail <span class="pc-muted">(tampil di popup saat card diklik)</span></label>
                            <textarea name="deskripsi" id="pcFormDeskripsi" class="form-input" rows="4" placeholder="Tulis detail promo, syarat &amp; ketentuan, masa berlaku, dll."></textarea>
                        </div>
                        <div class="pc-field">
                            <label>Gambar (Upload)</label>
                            <input type="file" name="gambar" id="pcFormGambar" accept="image/*" class="form-input">
                            <div class="pc-muted">JPG/PNG/WebP, maks 2MB. Rekomendasi rasio 16:9 (mis. 800x450px)</div>
                            <img id="pcFormPreview" class="pc-preview" style="display:none" alt="Preview">
                        </div>
                        <div class="pc-field">
                            <label>Atau URL Gambar</label>
                            <input type="text" name="gambar_url" id="pcFormGambarUrl" class="form-input" placeholder="https://example.com/gambar.jpg">
                            <div class="pc-muted">Upload file diabaikan jika URL diisi. Kosongkan keduanya saat edit = gambar tetap.</div>
                        </div>
                        <div class="pc-field">
                            <label>Teks Tombol</label>
                            <input type="text" name="tombol_text" id="pcFormTombolText" class="form-input" maxlength="100" placeholder="Contoh: Daftar Sekarang">
                        </div>
                        <div class="pc-field">
                            <label>Link Tombol (URL)</label>
                            <input type="text" name="tombol_link" id="pcFormTombolLink" class="form-input" maxlength="500" placeholder="https://...">
                        </div>
                        <div class="pc-field">
                            <label>Urutan Tampil</label>
                            <input type="number" name="urutan" id="pcFormUrutan" class="form-input" min="1" value="1" style="max-width:120px">
                        </div>
                        <div class="pc-field">
                            <label class="pc-check">
                                <input type="hidden" name="aktif" value="0">
                                <input type="checkbox" name="aktif" id="pcFormAktif" value="1" checked style="width:18px;height:18px;accent-color:var(--primary)">
                                <span>Tampilkan (aktif) di dashboard</span>
                            </label>
                        </div>
                    </div>
                    <div class="pc-form-actions">
                        <button type="submit" class="btn btn-primary" id="pcFormSubmit"><i class="fas fa-save"></i> Simpan</button>
                        <button type="button" class="btn btn-secondary" onclick="pcHideForm()"><i class="fas fa-times"></i> Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<style>
    /* ===== WRAP ===== */
    .pc-wrap { background: linear-gradient(135deg, rgba(13,148,136,.06), rgba(245,158,11,.07)); border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px 16px; margin-bottom: 18px; }
    .pc-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
    .pc-head-title { display: flex; align-items: center; gap: 8px; font-size: .8rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: .5px; }
    .pc-head-ico { width: 26px; height: 26px; border-radius: 8px; background: linear-gradient(135deg, #0d9488, #f59e0b); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: .72rem; }
    .pc-manage { display: inline-flex; align-items: center; gap: 5px; font-size: .68rem; font-weight: 700; color: var(--primary-dark); background: rgba(13,148,136,.08); border: 1px solid rgba(13,148,136,.25); padding: 5px 11px; border-radius: 8px; cursor: pointer; transition: all .15s; }
    .pc-manage:hover { background: var(--primary); color: #fff; }

    /* ===== CAROUSEL ===== */
    .pc-carousel { position: relative; }
    .pc-viewport { display: flex; gap: 14px; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; cursor: grab; scrollbar-width: none; }
    .pc-viewport::-webkit-scrollbar { display: none; }
    .pc-viewport.dragging { scroll-snap-type: none; scroll-behavior: auto; cursor: grabbing; }
    .pc-card { flex: 0 0 calc(50% - 7px); scroll-snap-align: start; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; transition: transform .2s, box-shadow .2s, border-color .2s; user-select: none; -webkit-user-select: none; }
    .pc-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,.09); border-color: rgba(13,148,136,.35); }
    .pc-img { position: relative; padding-top: 50%; background: linear-gradient(135deg, #0d9488, #065f46); overflow: hidden; }
    .pc-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .35s; }
    .pc-card:hover .pc-img img { transform: scale(1.04); }
    .pc-img-empty { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,.75); font-size: 1.8rem; }
    .pc-badge { position: absolute; top: 10px; left: 10px; background: rgba(255,255,255,.94); color: #0d9488; font-size: .58rem; font-weight: 800; letter-spacing: .6px; padding: 3px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 2px 8px rgba(0,0,0,.12); }
    .pc-body { padding: 13px 16px 15px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
    .pc-title { font-size: .9rem; font-weight: 800; color: #0f172a; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .pc-desc { font-size: .72rem; color: #64748b; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .pc-btn { margin-top: auto; align-self: flex-start; display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #0d9488, #0f766e); color: #fff; padding: 8px 16px; border-radius: 9px; font-size: .72rem; font-weight: 700; text-decoration: none; white-space: nowrap; transition: all .15s; }
    .pc-btn:hover { color: #fff; opacity: .88; transform: translateY(-1px); }
    .pc-btn-lg { align-self: stretch; justify-content: center; padding: 11px 18px; font-size: .8rem; border-radius: 10px; }

    /* Arrows */
    .pc-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 34px; height: 34px; border-radius: 50%; background: #fff; border: 1px solid #e2e8f0; color: #334155; cursor: pointer; font-size: .78rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 12px rgba(0,0,0,.1); transition: all .15s; z-index: 5; }
    .pc-nav:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
    .pc-prev { left: -11px; }
    .pc-next { right: -11px; }

    /* Dots pagination */
    .pc-dots { display: flex; justify-content: center; gap: 7px; margin-top: 12px; }
    .pc-dot { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; border: none; padding: 0; cursor: pointer; transition: all .25s; }
    .pc-dot:hover { background: #94a3b8; }
    .pc-dot.active { background: var(--primary); width: 22px; border-radius: 6px; }

    /* Empty state */
    .pc-empty { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 18px; font-size: .8rem; color: #64748b; }

    /* ===== MODAL (detail & admin) ===== */
    .pc-modal { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.62); backdrop-filter: blur(3px); z-index: 10000; align-items: center; justify-content: center; padding: 18px; }
    .pc-modal.show { display: flex; }
    .pc-modal-box { background: #fff; border-radius: 18px; max-width: 480px; width: 100%; max-height: 88vh; overflow-y: auto; position: relative; animation: pcPop .25s ease; }
    .pc-admin-box { max-width: 700px; }
    @keyframes pcPop { from { opacity: 0; transform: scale(.93) translateY(14px); } to { opacity: 1; transform: none; } }
    .pc-modal-close { position: absolute; top: 12px; right: 12px; width: 32px; height: 32px; border-radius: 50%; background: rgba(15,23,42,.55); color: #fff; border: none; cursor: pointer; font-size: .82rem; display: flex; align-items: center; justify-content: center; z-index: 2; transition: background .15s; }
    .pc-modal-close:hover { background: #dc2626; }

    /* Detail popup */
    .pc-detail-imgwrap { background: linear-gradient(135deg, #0d9488, #065f46); border-radius: 18px 18px 0 0; min-height: 60px; }
    .pc-detail-imgwrap img { width: 100%; max-height: 250px; object-fit: cover; border-radius: 18px 18px 0 0; display: block; }
    .pc-detail-body { padding: 18px 22px 22px; display: flex; flex-direction: column; gap: 10px; }
    .pc-detail-badge { align-self: flex-start; display: inline-flex; align-items: center; gap: 4px; background: rgba(13,148,136,.1); color: #0d9488; font-size: .6rem; font-weight: 800; letter-spacing: .6px; padding: 3px 10px; border-radius: 20px; }
    .pc-detail-title { margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a; line-height: 1.35; }
    .pc-detail-desc { font-size: .82rem; color: #475569; line-height: 1.65; white-space: pre-line; }

    /* Admin modal */
    .pc-admin-body { padding: 22px 24px 24px; }
    .pc-admin-title { margin: 0 0 6px; font-size: 1.05rem; }
    .pc-admin-sub { margin: 0 0 14px; font-size: .72rem; color: #94a3b8; line-height: 1.5; }
    .pc-admin-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
    .pc-admin-count { font-size: .72rem; font-weight: 700; color: #475569; }
    .pc-admin-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 8px; background: #fff; }
    .pc-admin-thumb { width: 58px; height: 40px; border-radius: 8px; object-fit: cover; flex-shrink: 0; border: 1px solid #e2e8f0; }
    .pc-admin-thumb-empty { width: 58px; height: 40px; border-radius: 8px; flex-shrink: 0; background: linear-gradient(135deg, #0d9488, #065f46); color: rgba(255,255,255,.75); display: flex; align-items: center; justify-content: center; font-size: .9rem; }
    .pc-admin-info { flex: 1; min-width: 0; }
    .pc-admin-name { font-size: .8rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pc-admin-meta { font-size: .66rem; color: #94a3b8; margin-top: 2px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .pc-status { display: inline-flex; align-items: center; gap: 4px; font-size: .6rem; font-weight: 800; padding: 2px 8px; border-radius: 20px; }
    .pc-status.on { background: #dcfce7; color: #16a34a; }
    .pc-status.off { background: #f1f5f9; color: #94a3b8; }
    .pc-admin-actions { display: flex; gap: 5px; flex-shrink: 0; }
    .pc-mini-btn { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; font-size: .7rem; display: inline-flex; align-items: center; justify-content: center; transition: all .15s; }
    .pc-mini-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
    .pc-mini-btn.danger:hover { background: #dc2626; border-color: #dc2626; }
    .pc-admin-empty { text-align: center; color: #94a3b8; font-size: .78rem; padding: 22px 10px; }

    /* Form */
    #pcFormWrap { margin-top: 16px; border-top: 1.5px dashed #e2e8f0; padding-top: 16px; }
    .pc-form-title { margin: 0 0 12px; font-size: .88rem; color: #0f172a; }
    .pc-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 14px; }
    .pc-field { display: flex; flex-direction: column; gap: 5px; }
    .pc-field-full { grid-column: 1 / -1; }
    .pc-field label { font-size: .72rem; font-weight: 700; color: #334155; }
    .pc-muted { font-size: .66rem; color: #94a3b8; line-height: 1.4; }
    .pc-check { display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 22px !important; }
    .pc-preview { max-width: 140px; max-height: 80px; border-radius: 8px; margin-top: 6px; border: 1px solid #e2e8f0; object-fit: cover; }
    .pc-form-actions { display: flex; gap: 8px; margin-top: 16px; }

    /* Toast */
    .pc-toast { position: fixed; top: 20px; right: 20px; z-index: 10001; padding: 13px 20px; border-radius: 10px; color: #fff; font-size: .82rem; font-weight: 600; display: none; box-shadow: 0 8px 25px rgba(0,0,0,.2); max-width: 380px; }
    .pc-toast.show { display: block; animation: pcPop .25s ease; }
    .pc-toast.success { background: #16a34a; }
    .pc-toast.error { background: #dc2626; }

    /* ===== DARK MODE ===== */
    body.dark .pc-wrap { background: linear-gradient(135deg, rgba(45,212,191,.07), rgba(245,158,11,.06)); border-color: #334155; }
    body.dark .pc-head-title { color: #e2e8f0; }
    body.dark .pc-manage { background: rgba(13,148,136,.15); border-color: rgba(45,212,191,.3); color: #5eead4; }
    body.dark .pc-manage:hover { background: var(--primary); color: #fff; }
    body.dark .pc-card { background: #1e293b; border-color: #334155; }
    body.dark .pc-card:hover { box-shadow: 0 10px 30px rgba(0,0,0,.4); border-color: rgba(45,212,191,.4); }
    body.dark .pc-title { color: #e2e8f0; }
    body.dark .pc-desc { color: #94a3b8; }
    body.dark .pc-nav { background: #1e293b; border-color: #334155; color: #cbd5e1; }
    body.dark .pc-nav:hover { background: var(--primary); color: #fff; }
    body.dark .pc-dot { background: #475569; }
    body.dark .pc-dot.active { background: #5eead4; }
    body.dark .pc-modal-box { background: #1e293b; }
    body.dark .pc-detail-title { color: #e2e8f0; }
    body.dark .pc-detail-desc { color: #94a3b8; }
    body.dark .pc-admin-item { background: #0f172a; border-color: #334155; }
    body.dark .pc-admin-name { color: #e2e8f0; }
    body.dark .pc-admin-title { color: #e2e8f0; }
    body.dark .pc-field label { color: #cbd5e1; }
    body.dark .pc-form-title { color: #e2e8f0; }
    body.dark #pcFormWrap { border-color: #334155; }
    body.dark .pc-mini-btn { background: #1e293b; border-color: #334155; color: #94a3b8; }
    body.dark .pc-admin-thumb { border-color: #334155; }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .pc-card { flex: 0 0 100%; }
        .pc-prev { left: -6px; }
        .pc-next { right: -6px; }
        .pc-form-grid { grid-template-columns: 1fr; }
        .pc-nav { width: 30px; height: 30px; }
    }
</style>

<script>
(function() {
    // ================= DATA =================
    const PC_DATA      = @json($__pcData);
    const PC_ADMIN     = @json($__pcAdminData);
    const PC_IS_ADMIN  = @json($__pcSuper);
    const PC_BASE      = '{{ url('promo-cards') }}';
    const PC_CSRF      = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const esc = s => String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

    // ================= TOAST =================
    let pcToastTimer = null;
    function pcToast(msg, type) {
        const t = document.getElementById('pcToast');
        document.getElementById('pcToastText').textContent = msg;
        t.className = 'pc-toast show ' + (type || 'success');
        clearTimeout(pcToastTimer);
        pcToastTimer = setTimeout(() => t.classList.remove('show'), 3200);
    }
    window.pcToast = pcToast;

    // ================= CAROUSEL (2 card per halaman) =================
    const viewport = document.getElementById('pcViewport');
    const dotsEl   = document.getElementById('pcDots');
    const navPrev  = document.getElementById('pcNavPrev');
    const navNext  = document.getElementById('pcNavNext');

    let pcPage = 0, pcPages = 1, pcPerView = 2;
    let pcAutoTimer = null, pcHovering = false;
    let pcDragging = false, pcDragMoved = false, pcStartX = 0, pcStartScroll = 0;

    const pcPerViewCount = () => window.innerWidth <= 768 ? 1 : 2;

    function pcBuildDots() {
        if (!viewport) return;
        pcPerView = pcPerViewCount();
        pcPages = Math.max(1, Math.ceil(PC_DATA.length / pcPerView));
        pcPage = Math.min(pcPage, pcPages - 1);

        const showNav = pcPages > 1;
        if (dotsEl) {
            dotsEl.innerHTML = Array.from({ length: pcPages }, (_, i) =>
                `<button type="button" class="pc-dot${i === pcPage ? ' active' : ''}" onclick="pcGoPage(${i})" aria-label="Halaman ${i + 1}"></button>`
            ).join('');
            dotsEl.style.display = showNav ? 'flex' : 'none';
        }
        if (navPrev) navPrev.style.display = showNav ? 'flex' : 'none';
        if (navNext) navNext.style.display = showNav ? 'flex' : 'none';
    }

    function pcSyncDots() {
        if (!dotsEl) return;
        dotsEl.querySelectorAll('.pc-dot').forEach((d, i) => d.classList.toggle('active', i === pcPage));
    }

    window.pcGoPage = function(i) {
        if (!viewport || pcPages < 1) return;
        pcPage = ((i % pcPages) + pcPages) % pcPages;
        viewport.scrollTo({ left: pcPage * viewport.clientWidth, behavior: 'smooth' });
        pcSyncDots();
    };
    window.pcGoPageRel = dir => { pcUserNav(); pcGoPage(pcPage + dir); };

    // Auto-geser tiap 6.5 detik (pause saat hover / setelah interaksi user)
    function pcStartAuto() {
        if (pcPages <= 1) return;
        pcStopAuto();
        pcAutoTimer = setInterval(() => { if (!pcHovering && !pcDragging) pcGoPage(pcPage + 1); }, 6500);
    }
    function pcStopAuto() { if (pcAutoTimer) { clearInterval(pcAutoTimer); pcAutoTimer = null; } }
    function pcUserNav() { pcStartAuto(); /* reset timer */ }

    if (viewport) {
        // Update indikator saat scroll (swipe user)
        let pcRaf = null;
        viewport.addEventListener('scroll', () => {
            if (pcRaf) return;
            pcRaf = requestAnimationFrame(() => {
                pcRaf = null;
                if (!viewport.clientWidth) return;
                const p = Math.round(viewport.scrollLeft / viewport.clientWidth);
                if (p !== pcPage && p >= 0 && p < pcPages) { pcPage = p; pcSyncDots(); }
            });
        }, { passive: true });

        // Drag dengan mouse (mobile sudah otomatis via native swipe)
        viewport.addEventListener('pointerdown', e => {
            if (e.pointerType !== 'mouse') return;
            pcDragging = true; pcDragMoved = false;
            pcStartX = e.clientX; pcStartScroll = viewport.scrollLeft;
            viewport.classList.add('dragging');
        });
        window.addEventListener('pointermove', e => {
            if (!pcDragging) return;
            const dx = e.clientX - pcStartX;
            if (Math.abs(dx) > 8) pcDragMoved = true;
            viewport.scrollLeft = pcStartScroll - dx;
        });
        window.addEventListener('pointerup', () => {
            if (!pcDragging) return;
            pcDragging = false;
            viewport.classList.remove('dragging');
            if (pcDragMoved && viewport.clientWidth) {
                // Snap ke halaman terdekat
                const p = Math.max(0, Math.min(pcPages - 1, Math.round(viewport.scrollLeft / viewport.clientWidth)));
                pcGoPage(p);
            }
        });

        // Hover = pause auto
        viewport.addEventListener('mouseenter', () => pcHovering = true);
        viewport.addEventListener('mouseleave', () => pcHovering = false);

        // Klik card → popup detail (link tombol langsung ke URL-nya;
        // tombol tanpa link juga membuka popup)
        viewport.addEventListener('click', e => {
            if (pcDragMoved) { pcDragMoved = false; return; }
            if (e.target.closest('a.pc-btn')) return; // anchor langsung ke link-nya
            const card = e.target.closest('.pc-card');
            if (!card) return;
            pcOpenDetail(PC_DATA[+card.dataset.idx]);
        });
        viewport.addEventListener('keydown', e => {
            if (e.key !== 'Enter') return;
            const card = e.target.closest('.pc-card');
            if (card) pcOpenDetail(PC_DATA[+card.dataset.idx]);
        });

        let pcResizeTimer = null;
        window.addEventListener('resize', () => {
            clearTimeout(pcResizeTimer);
            pcResizeTimer = setTimeout(() => { pcBuildDots(); pcGoPage(pcPage); }, 200);
        });

        pcBuildDots();
        pcStartAuto();
    }

    // ================= POPUP DETAIL =================
    const detailModal = document.getElementById('pcDetailModal');

    window.pcOpenDetail = function(c) {
        if (!c || !detailModal) return;
        document.getElementById('pcDetailTitle').textContent = c.judul;

        const img = document.getElementById('pcDetailImg');
        const wrap = document.getElementById('pcDetailImgWrap');
        if (c.gambar_url) { img.src = c.gambar_url; img.style.display = 'block'; wrap.style.minHeight = '0'; }
        else { img.style.display = 'none'; wrap.style.minHeight = '90px'; }

        document.getElementById('pcDetailDesc').textContent = c.deskripsi || '';

        const btn = document.getElementById('pcDetailBtn');
        if (c.tombol_link) {
            btn.href = c.tombol_link;
            btn.style.display = 'inline-flex';
            document.getElementById('pcDetailBtnText').textContent = c.tombol_text || 'Lihat Detail';
        } else {
            btn.style.display = 'none';
        }
        detailModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };
    window.pcCloseDetail = function() {
        if (!detailModal) return;
        detailModal.classList.remove('show');
        document.body.style.overflow = '';
    };

    // ================= KELOLA PROMO (SUPER ADMIN) =================
    if (PC_IS_ADMIN) {
        const adminModal = document.getElementById('pcAdminModal');

        window.pcOpenAdmin = function() {
            if (!adminModal) return;
            pcHideForm();
            pcRenderAdmin();
            adminModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        };
        window.pcCloseAdmin = function() {
            if (!adminModal) return;
            adminModal.classList.remove('show');
            document.body.style.overflow = '';
        };

        function pcRenderAdmin() {
            const list = document.getElementById('pcAdminList');
            document.getElementById('pcAdminCount').textContent = PC_ADMIN.length + ' promo total';
            if (!PC_ADMIN.length) {
                list.innerHTML = '<div class="pc-admin-empty"><i class="fas fa-inbox" style="font-size:1.4rem;display:block;margin-bottom:8px"></i>Belum ada promo. Klik "Tambah Promo" untuk membuat card pertama.</div>';
                return;
            }
            list.innerHTML = PC_ADMIN.map(c => `
                <div class="pc-admin-item">
                    ${c.gambar_url
                        ? `<img src="${esc(c.gambar_url)}" class="pc-admin-thumb" alt="">`
                        : `<div class="pc-admin-thumb-empty"><i class="fas fa-image"></i></div>`}
                    <div class="pc-admin-info">
                        <div class="pc-admin-name">${esc(c.judul)}</div>
                        <div class="pc-admin-meta">
                            <span class="pc-status ${c.aktif ? 'on' : 'off'}"><i class="fas fa-${c.aktif ? 'check-circle' : 'circle'}"></i> ${c.aktif ? 'Aktif' : 'Nonaktif'}</span>
                            <span><i class="fas fa-sort"></i> Urutan ${c.urutan}</span>
                        </div>
                    </div>
                    <div class="pc-admin-actions">
                        <button type="button" class="pc-mini-btn" title="${c.aktif ? 'Nonaktifkan' : 'Aktifkan'}" onclick="pcToggle(${c.id}, ${c.aktif})"><i class="fas fa-${c.aktif ? 'toggle-on' : 'toggle-off'}"></i></button>
                        <button type="button" class="pc-mini-btn" title="Edit" onclick='pcEditCard(${JSON.stringify(c.id)})'><i class="fas fa-pen"></i></button>
                        <button type="button" class="pc-mini-btn danger" title="Hapus" onclick="pcDeleteCard(${c.id})"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`).join('');
        }

        window.pcShowForm = function(id) {
            const wrap = document.getElementById('pcFormWrap');
            const form = document.getElementById('pcForm');
            const c = id != null ? PC_ADMIN.find(x => x.id === id) : null;

            document.getElementById('pcFormTitle').innerHTML = c
                ? '<i class="fas fa-edit"></i> Edit Promo'
                : '<i class="fas fa-plus-circle"></i> Tambah Promo Baru';
            document.getElementById('pcFormId').value        = c ? c.id : '';
            document.getElementById('pcFormJudul').value     = c ? c.judul : '';
            document.getElementById('pcFormDeskripsi').value = c ? c.deskripsi : '';
            document.getElementById('pcFormGambar').value    = '';
            document.getElementById('pcFormGambarUrl').value = '';
            document.getElementById('pcFormTombolText').value = c ? (c.tombol_text || '') : '';
            document.getElementById('pcFormTombolLink').value = c ? (c.tombol_link && c.tombol_link !== '#' ? c.tombol_link : '') : '';
            document.getElementById('pcFormUrutan').value    = c ? c.urutan : (PC_ADMIN.length + 1);
            document.getElementById('pcFormAktif').checked   = c ? c.aktif : true;

            const prev = document.getElementById('pcFormPreview');
            if (c && c.gambar_url) { prev.src = c.gambar_url; prev.style.display = 'block'; }
            else prev.style.display = 'none';

            document.getElementById('pcFormSubmit').innerHTML = c ? '<i class="fas fa-save"></i> Simpan Perubahan' : '<i class="fas fa-save"></i> Simpan';
            wrap.style.display = 'block';
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        };
        window.pcHideForm = function() {
            document.getElementById('pcFormWrap').style.display = 'none';
        };
        window.pcEditCard = function(id) { pcShowForm(id); };

        async function pcFetch(url, method, body) {
            const res = await fetch(url, {
                method,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': PC_CSRF },
                body
            });
            let json = {};
            try { json = await res.json(); } catch (e) {}
            if (!res.ok || json.error) throw new Error(json.error || 'Terjadi kesalahan. Coba lagi.');
            return json;
        }

        window.pcSubmitForm = async function(e) {
            e.preventDefault();
            const form = e.target;
            const fd = new FormData(form);
            const id = fd.get('id');
            const url = id ? `${PC_BASE}/${id}` : PC_BASE;
            if (id) fd.append('_method', 'PUT');

            const btn = document.getElementById('pcFormSubmit');
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            try {
                const json = await pcFetch(url, 'POST', fd);
                pcToast(json.message || 'Berhasil disimpan!', 'success');
                setTimeout(() => location.reload(), 900);
            } catch (err) {
                pcToast(err.message, 'error');
                btn.disabled = false;
                btn.innerHTML = original;
            }
            return false;
        };

        window.pcToggle = async function(id, aktif) {
            const c = PC_ADMIN.find(x => x.id === id);
            if (!c) return;
            const fd = new FormData();
            fd.append('_method', 'PUT');
            fd.append('judul', c.judul);
            fd.append('deskripsi', c.deskripsi || '');
            fd.append('tombol_text', c.tombol_text || '');
            fd.append('tombol_link', (c.tombol_link && c.tombol_link !== '#') ? c.tombol_link : '');
            fd.append('urutan', c.urutan);
            fd.append('aktif', aktif ? '0' : '1'); // toggle kebalikan
            try {
                const json = await pcFetch(`${PC_BASE}/${id}`, 'POST', fd);
                pcToast(json.message || 'Status promo diubah!', 'success');
                setTimeout(() => location.reload(), 900);
            } catch (err) { pcToast(err.message, 'error'); }
        };

        window.pcDeleteCard = async function(id) {
            if (!confirm('Yakin hapus card promo ini?')) return;
            try {
                const json = await pcFetch(`${PC_BASE}/${id}`, 'DELETE');
                pcToast(json.message || 'Promo dihapus!', 'success');
                setTimeout(() => location.reload(), 900);
            } catch (err) { pcToast(err.message, 'error'); }
        };

        // Preview gambar saat pilih file
        document.getElementById('pcFormGambar')?.addEventListener('change', function() {
            const prev = document.getElementById('pcFormPreview');
            if (this.files && this.files[0]) {
                prev.src = URL.createObjectURL(this.files[0]);
                prev.style.display = 'block';
            }
        });

        // Tutup modal dengan tombol Esc
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') { pcCloseAdmin(); pcCloseDetail(); }
        });
    } else {
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') pcCloseDetail();
        });
    }
})();
</script>
@endif
