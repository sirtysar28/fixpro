{{-- ============================================================
     BANNER PROMO — tampil di layout seluruh fitur (semua halaman)
     Update banner hanya bisa dilakukan oleh Super Admin
     (menu "Banner Iklan" & routes sudah dibatasi Super Admin).
============================================================ --}}
@php
    $__promos = \App\Models\BannerIklan::getAktif();
    
    // 1. Ambil nomor dari database (Setting)
    $telpSetting = \App\Models\Setting::get('telp') ?? '';
    
    // 2. NOMOR TELEPON MANUAL (FALLBACK)
    // GANTI '6281234567890' DI BAWAH INI DENGAN NOMOR WHATSAPP ADMIN ANDA
    $telpManual = '6281234567890'; 
    
    // 3. Gunakan nomor setting jika ada, jika kosong gunakan nomor manual.
    // Lalu bersihkan string dari semua karakter kecuali angka (0-9).
    $__promoTelp = preg_replace('/[^0-9]/', '', $telpSetting ?: $telpManual);
@endphp

@if($__promos->count() > 0)
<div class="fpromo" id="fpromoStrip" style="display:none">
    <div class="fpromo-head">
        <div class="fpromo-title"><span class="fpromo-ico"><i class="fas fa-bullhorn"></i></span> Info &amp; Promo</div>
        <div class="fpromo-tools">
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('banner-iklan.index') }}" class="fpromo-manage"><i class="fas fa-pen-to-square"></i> Kelola Banner</a>
            @endif
            <button type="button" class="fpromo-close" onclick="dismissFpromo()" title="Tutup promo"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div class="fpromo-scroll">
        @foreach($__promos as $__b)
        <div class="fpromo-card">
            @if($__b->gambar)
            <div class="fpromo-img">
                <img src="{{ str_starts_with($__b->gambar, 'http') ? $__b->gambar : Storage::url($__b->gambar) }}" alt="{{ $__b->judul }}" loading="lazy">
            </div>
            @endif
            <div class="fpromo-body">
                <div class="fpromo-name">{{ $__b->judul }}</div>
                @if($__b->deskripsi)
                <div class="fpromo-desc">{!! $__b->deskripsi !!}</div>
                @endif
                <div class="fpromo-actions">
                    <a href="{{ $__b->link ?: '#' }}" target="_blank" rel="noopener" class="fpromo-btn">
                        <i class="fas fa-rocket"></i> Daftar Sekarang!
                    </a>
                    <a href="https://wa.me/{{ $__promoTelp }}?text={{ rawurlencode('Halo FixPro, saya tertarik dengan ' . $__b->judul) }}" target="_blank" rel="noopener" class="fpromo-btn fpromo-btn-wa">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
    .fpromo { background: linear-gradient(135deg, rgba(13,148,136,.06), rgba(245,158,11,.06)); border: 1px solid #e2e8f0; border-radius: 14px; padding: 12px 14px 6px; margin-bottom: 18px; }
    .fpromo-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 0 2px 10px; }
    .fpromo-title { display: flex; align-items: center; gap: 8px; font-size: .8rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: .5px; }
    .fpromo-ico { width: 26px; height: 26px; border-radius: 8px; background: var(--primary); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: .72rem; }
    .fpromo-tools { display: flex; align-items: center; gap: 6px; }
    .fpromo-manage { display: inline-flex; align-items: center; gap: 5px; font-size: .68rem; font-weight: 700; color: var(--primary-dark); background: var(--primary-bg); border: 1px solid rgba(13,148,136,.25); padding: 4px 10px; border-radius: 8px; text-decoration: none; transition: all .15s; }
    .fpromo-manage:hover { background: var(--primary); color: #fff; }
    .fpromo-close { width: 26px; height: 26px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; font-size: .72rem; display: inline-flex; align-items: center; justify-content: center; transition: all .15s; }
    .fpromo-close:hover { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
    .fpromo-scroll { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 10px; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; }
    .fpromo-scroll::-webkit-scrollbar { height: 4px; }
    .fpromo-scroll::-webkit-scrollbar-track { background: transparent; }
    .fpromo-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .fpromo-card { min-width: 300px; max-width: 340px; flex: 1 0 300px; scroll-snap-align: start; display: flex; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; transition: transform .2s, box-shadow .2s; }
    .fpromo-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,.08); }
    .fpromo-img { flex: 0 0 108px; position: relative; }
    .fpromo-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .fpromo-body { flex: 1; padding: 12px 14px; display: flex; flex-direction: column; min-width: 0; }
    .fpromo-name { font-size: .82rem; font-weight: 800; color: #0f172a; line-height: 1.3; margin-bottom: 4px; }
    .fpromo-desc { font-size: .7rem; color: #475569; line-height: 1.55; margin-bottom: 10px; max-height: 66px; overflow: hidden; position: relative; }
    .fpromo-desc ul, .fpromo-desc ol { margin: 2px 0; padding-left: 14px; }
    .fpromo-desc li { margin-bottom: 2px; }
    .fpromo-desc p { margin: 0 0 3px; }
    .fpromo-desc strong { color: #1e293b; }
    .fpromo-actions { margin-top: auto; display: flex; gap: 6px; }
    .fpromo-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 5px; padding: 7px 10px; background: var(--primary); color: #fff; border-radius: 8px; font-size: .68rem; font-weight: 700; text-decoration: none; transition: opacity .2s; white-space: nowrap; }
    .fpromo-btn:hover { opacity: .85; color: #fff; }
    .fpromo-btn-wa { background: #25D366; }
    /* Dark mode */
    body.dark .fpromo { background: linear-gradient(135deg, rgba(45,212,191,.08), rgba(245,158,11,.06)); border-color: #334155; }
    body.dark .fpromo-title { color: #e2e8f0; }
    body.dark .fpromo-manage { background: rgba(13,148,136,.15); border-color: rgba(45,212,191,.3); color: #5eead4; }
    body.dark .fpromo-manage:hover { background: var(--primary); color: #fff; }
    body.dark .fpromo-close { background: #0f172a; border-color: #334155; color: #94a3b8; }
    body.dark .fpromo-close:hover { background: #450a0a; border-color: #991b1b; color: #fca5a5; }
    body.dark .fpromo-scroll::-webkit-scrollbar-thumb { background: #475569; }
    body.dark .fpromo-card { background: #1e293b; border-color: #334155; }
    body.dark .fpromo-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,.35); }
    body.dark .fpromo-name { color: #e2e8f0; }
    body.dark .fpromo-desc { color: #94a3b8; }
    body.dark .fpromo-desc strong { color: #cbd5e1; }
    @media (max-width: 640px) {
        .fpromo-card { min-width: 260px; flex: 1 0 260px; }
        .fpromo-img { flex-basis: 88px; }
        .fpromo-btn { padding: 7px 8px; font-size: .64rem; }
    }
</style>
<script>
    (function() {
        var strip = document.getElementById('fpromoStrip');
        if (!strip) return;
        var key = 'fixpro_promo_hidden_{{ auth()->id() ?? "guest" }}';
        var hidden = false;
        try { hidden = sessionStorage.getItem(key) === '1'; } catch (e) {}
        if (!hidden) strip.style.display = 'block';
        window.dismissFpromo = function() {
            strip.style.display = 'none';
            try { sessionStorage.setItem(key, '1'); } catch (e) {}
        };
    })();
</script>
@endif