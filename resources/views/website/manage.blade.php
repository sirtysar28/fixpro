@extends('layouts.app')
@section('title', 'Kelola Website Publik')

@section('content')
<style>
.web-alert{position:fixed;top:20px;right:20px;z-index:9999;padding:14px 22px;border-radius:10px;color:#fff;font-size:.85rem;font-weight:600;max-width:400px;box-shadow:0 8px 25px rgba(0,0,0,.15);animation:slideIn .3s ease;display:none}
.web-alert.show{display:flex;align-items:center;gap:10px}
.web-alert.success{background:#16a34a}
.web-alert.error{background:#dc2626}
.web-alert.info{background:#2563eb}
@keyframes slideIn{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
.web-loading{display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:9998;align-items:center;justify-content:center}
.web-loading.show{display:flex}
.web-loading .spinner{width:40px;height:40px;border:4px solid #e2e8f0;border-top-color:var(--primary);border-radius:50%;animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.section-tabs{display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:24px;overflow-x:auto}
.section-tab{padding:12px 20px;font-size:.84rem;font-weight:600;color:#64748b;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;white-space:nowrap;transition:all .2s;display:flex;align-items:center;gap:6px}
.section-tab:hover{color:var(--primary)}
.section-tab.active{color:var(--primary);border-bottom-color:var(--primary)}
.section-panel{display:none}
.section-panel.active{display:block}
.web-preview-frame{width:100%;border:1.5px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:24px;background:#fff}
.web-preview-bar{padding:10px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:10px}
.web-preview-bar .dots{display:flex;gap:5px}
.web-preview-bar .dot{width:10px;height:10px;border-radius:50%}
.web-preview-bar .url{flex:1;padding:5px 12px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;font-size:.72rem;color:#64748b}
.web-preview-frame iframe{width:100%;height:400px;border:none}
.json-list-item{padding:16px;border:1.5px solid #e2e8f0;border-radius:10px;margin-bottom:12px;background:#fff;transition:all .2s}
.json-list-item:hover{border-color:var(--primary);box-shadow:0 2px 8px rgba(13,148,136,.08)}
.json-list-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.json-list-header h4{font-size:.9rem;font-weight:700;margin:0}
.add-item-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px dashed var(--primary);border-radius:8px;background:rgba(13,148,136,.05);color:var(--primary);font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s}
.add-item-btn:hover{background:rgba(13,148,136,.1)}
.color-dot{display:inline-block;width:14px;height:14px;border-radius:50%;border:1.5px solid #e2e8f0;cursor:pointer}
</style>

<!-- Alert -->
<div id="webAlert" class="web-alert"><span id="webAlertText"></span></div>
<!-- Loading -->
<div id="webLoading" class="web-loading"><div class="spinner"></div></div>

<div class="flex-between mb-4" style="flex-wrap:wrap;gap:12px">
    <div>
        <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-globe" style="color:var(--primary);margin-right:6px"></i> Kelola Website Publik</h2>
        <p style="font-size:.78rem;color:#64748b;margin-top:4px">Atur konten halaman publik FixPro yang dilihat pengunjung</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="/" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-external-link-alt"></i> Lihat Website</a>
    </div>
</div>

<!-- Preview -->
<div class="web-preview-frame" id="previewFrame">
    <div class="web-preview-bar">
        <div class="dots">
            <div class="dot" style="background:#ef4444"></div>
            <div class="dot" style="background:#f59e0b"></div>
            <div class="dot" style="background:#22c55e"></div>
        </div>
        <div class="url"><i class="fas fa-lock" style="margin-right:4px"></i> fixpro.id</div>
        <button onclick="refreshPreview()" class="btn btn-secondary btn-xs"><i class="fas fa-sync-alt"></i></button>
        <button onclick="document.getElementById('previewIframe').style.height=document.getElementById('previewIframe').style.height==='600px'?'400px':'600px'" class="btn btn-secondary btn-xs"><i class="fas fa-expand"></i></button>
    </div>
    <iframe id="previewIframe" src="/" style="width:100%;height:400px;border:none"></iframe>
</div>

<!-- Section Tabs -->
<div class="section-tabs" id="sectionTabs">
    <div class="section-tab active" onclick="switchSection('hero')"><i class="fas fa-home"></i> Hero</div>
    <div class="section-tab" onclick="switchSection('features')"><i class="fas fa-star"></i> Fitur</div>
    <div class="section-tab" onclick="switchSection('about')"><i class="fas fa-info-circle"></i> Tentang</div>
    <div class="section-tab" onclick="switchSection('services')"><i class="fas fa-cogs"></i> Layanan</div>
    <div class="section-tab" onclick="switchSection('pricing')"><i class="fas fa-tag"></i> Harga</div>
    <div class="section-tab" onclick="switchSection('testimonials')"><i class="fas fa-quote-right"></i> Testimoni</div>
    <div class="section-tab" onclick="switchSection('faq')"><i class="fas fa-question-circle"></i> FAQ</div>
    <div class="section-tab" onclick="switchSection('cta')"><i class="fas fa-bullhorn"></i> CTA</div>
    <div class="section-tab" onclick="switchSection('contact')"><i class="fas fa-envelope"></i> Kontak</div>
    <div class="section-tab" onclick="switchSection('wa_group')"><i class="fab fa-whatsapp" style="color:#25D366"></i> Grup WhatsApp</div>
    <div class="section-tab" onclick="switchSection('mobile_app')"><i class="fas fa-mobile-alt" style="color:#6366f1"></i> Aplikasi Mobile</div>
    <div class="section-tab" onclick="switchSection('footer')"><i class="fas fa-shoe-prints"></i> Footer</div>
</div>

@php
    $allSections = $sections;
@endphp

<!-- ==================== HERO SECTION ==================== -->
<div class="section-panel active" id="panel-hero">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-home" style="color:var(--primary);margin-right:6px"></i> Hero Section (Bagian Atas)</h3>
        @php $heroSection = $allSections->get('hero', collect()); @endphp

        <div class="form-group">
            <label>Judul Utama</label>
            <input type="text" id="hero_title" class="form-input" value="{{ $heroSection->where('key','title')->first()?->value ?? 'Solusi Servis HP Profesional' }}">
        </div>
        <div class="form-group">
            <label>Sub Judul</label>
            <textarea id="hero_subtitle" class="form-input" rows="3">{{ $heroSection->where('key','subtitle')->first()?->value ?? '' }}</textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Tombol Utama (Teks)</label>
                <input type="text" id="hero_cta_text" class="form-input" value="{{ $heroSection->where('key','cta_text')->first()?->value ?? 'Mulai Sekarang' }}">
            </div>
            <div class="form-group">
                <label>Tombol Utama (Link)</label>
                <input type="text" id="hero_cta_link" class="form-input" value="{{ $heroSection->where('key','cta_link')->first()?->value ?? '/register' }}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Tombol Kedua (Teks)</label>
                <input type="text" id="hero_cta_secondary_text" class="form-input" value="{{ $heroSection->where('key','cta_secondary_text')->first()?->value ?? 'Lihat Fitur' }}">
            </div>
            <div class="form-group">
                <label>Tombol Kedua (Link)</label>
                <input type="text" id="hero_cta_secondary_link" class="form-input" value="{{ $heroSection->where('key','cta_secondary_link')->first()?->value ?? '#features' }}">
            </div>
        </div>

        <h4 style="font-size:.85rem;font-weight:700;margin:20px 0 12px"><i class="fas fa-chart-bar" style="color:var(--primary);margin-right:4px"></i> Statistik Hero</h4>
        <div id="heroStatsContainer"></div>
        <button type="button" class="add-item-btn" onclick="addHeroStat()"><i class="fas fa-plus"></i> Tambah Statistik</button>
    </div>
    <button type="button" onclick="saveSection('hero')" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Hero</button>
</div>

<!-- ==================== FEATURES SECTION ==================== -->
<div class="section-panel" id="panel-features">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-star" style="color:var(--primary);margin-right:6px"></i> Fitur Unggulan</h3>
        @php $featSection = $allSections->get('features', collect()); @endphp
        <div class="form-group">
            <label>Judul Section</label>
            <input type="text" id="features_title" class="form-input" value="{{ $featSection->where('key','title')->first()?->value ?? 'Fitur Unggulan' }}">
        </div>
        <div class="form-group">
            <label>Sub Judul</label>
            <input type="text" id="features_subtitle" class="form-input" value="{{ $featSection->where('key','subtitle')->first()?->value ?? '' }}">
        </div>

        <h4 style="font-size:.85rem;font-weight:700;margin:20px 0 12px"><i class="fas fa-th-large" style="color:var(--primary);margin-right:4px"></i> Daftar Fitur</h4>
        <div id="featuresItemsContainer"></div>
        <button type="button" class="add-item-btn" onclick="addFeatureItem()"><i class="fas fa-plus"></i> Tambah Fitur</button>
    </div>
    <button type="button" onclick="saveFeatures()" class="btn btn-primary" style="margin-top:16px"><i class="fas fa-save"></i> Simpan Fitur</button>
</div>

<!-- ==================== ABOUT SECTION ==================== -->
<div class="section-panel" id="panel-about">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-info-circle" style="color:var(--primary);margin-right:6px"></i> Tentang FixPro</h3>
        @php $aboutSection = $allSections->get('about', collect()); @endphp
        <div class="form-group">
            <label>Judul</label>
            <input type="text" id="about_title" class="form-input" value="{{ $aboutSection->where('key','title')->first()?->value ?? 'Tentang FixPro' }}">
        </div>
        <div class="form-group">
            <label>Deskripsi (HTML)</label>
            <textarea id="about_description" class="form-input" rows="6">{{ $aboutSection->where('key','description')->first()?->value ?? '' }}</textarea>
            <div style="font-size:.7rem;color:#94a3b8;margin-top:4px">Mendukung HTML: &lt;p&gt;, &lt;b&gt;, &lt;br&gt;, dll.</div>
        </div>
        <div class="form-group">
            <label>Gambar</label>
            @php $aboutImg = $aboutSection->where('key','image')->first(); @endphp
            @if($aboutImg && $aboutImg->image)
            <div style="margin-bottom:8px"><img src="{{ Storage::url($aboutImg->image) }}" style="max-height:120px;border-radius:8px;border:1px solid #e2e8f0"></div>
            @endif
            <input type="file" id="about_image" accept="image/*" class="form-input">
        </div>
    </div>
    <button type="button" onclick="saveAbout()" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Tentang</button>
</div>

<!-- ==================== SERVICES SECTION ==================== -->
<div class="section-panel" id="panel-services">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-cogs" style="color:var(--primary);margin-right:6px"></i> Layanan</h3>
        @php $svcSection = $allSections->get('services', collect()); @endphp
        <div class="form-group">
            <label>Judul Section</label>
            <input type="text" id="services_title" class="form-input" value="{{ $svcSection->where('key','title')->first()?->value ?? 'Layanan Kami' }}">
        </div>
        <div class="form-group">
            <label>Sub Judul</label>
            <input type="text" id="services_subtitle" class="form-input" value="{{ $svcSection->where('key','subtitle')->first()?->value ?? '' }}">
        </div>

        <h4 style="font-size:.85rem;font-weight:700;margin:20px 0 12px"><i class="fas fa-concierge-bell" style="color:var(--primary);margin-right:4px"></i> Daftar Layanan</h4>
        <div id="servicesItemsContainer"></div>
        <button type="button" class="add-item-btn" onclick="addServiceItem()"><i class="fas fa-plus"></i> Tambah Layanan</button>
    </div>
    <button type="button" onclick="saveServices()" class="btn btn-primary" style="margin-top:16px"><i class="fas fa-save"></i> Simpan Layanan</button>
</div>

<!-- ==================== PRICING SECTION ==================== -->
<div class="section-panel" id="panel-pricing">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-tag" style="color:var(--primary);margin-right:6px"></i> Paket Harga</h3>
        @php $pricSection = $allSections->get('pricing', collect()); @endphp
        <div class="form-group">
            <label>Judul Section</label>
            <input type="text" id="pricing_title" class="form-input" value="{{ $pricSection->where('key','title')->first()?->value ?? 'Paket Harga' }}">
        </div>
        <div class="form-group">
            <label>Sub Judul</label>
            <input type="text" id="pricing_subtitle" class="form-input" value="{{ $pricSection->where('key','subtitle')->first()?->value ?? '' }}">
        </div>

        <h4 style="font-size:.85rem;font-weight:700;margin:20px 0 12px"><i class="fas fa-box" style="color:var(--primary);margin-right:4px"></i> Daftar Paket</h4>
        <div id="pricingItemsContainer"></div>
        <button type="button" class="add-item-btn" onclick="addPricingItem()"><i class="fas fa-plus"></i> Tambah Paket</button>
    </div>
    <button type="button" onclick="savePricing()" class="btn btn-primary" style="margin-top:16px"><i class="fas fa-save"></i> Simpan Harga</button>
</div>

<!-- ==================== TESTIMONIALS SECTION ==================== -->
<div class="section-panel" id="panel-testimonials">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-quote-right" style="color:var(--primary);margin-right:6px"></i> Testimoni</h3>
        @php $testSection = $allSections->get('testimonials', collect()); @endphp
        <div class="form-group">
            <label>Judul Section</label>
            <input type="text" id="testimonials_title" class="form-input" value="{{ $testSection->where('key','title')->first()?->value ?? 'Apa Kata Mereka' }}">
        </div>
        <div class="form-group">
            <label>Sub Judul</label>
            <input type="text" id="testimonials_subtitle" class="form-input" value="{{ $testSection->where('key','subtitle')->first()?->value ?? '' }}">
        </div>

        <h4 style="font-size:.85rem;font-weight:700;margin:20px 0 12px"><i class="fas fa-comments" style="color:var(--primary);margin-right:4px"></i> Daftar Testimoni</h4>
        <div id="testimonialItemsContainer"></div>
        <button type="button" class="add-item-btn" onclick="addTestimonialItem()"><i class="fas fa-plus"></i> Tambah Testimoni</button>
    </div>
    <button type="button" onclick="saveTestimonials()" class="btn btn-primary" style="margin-top:16px"><i class="fas fa-save"></i> Simpan Testimoni</button>
</div>

<!-- ==================== FAQ SECTION ==================== -->
<div class="section-panel" id="panel-faq">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-question-circle" style="color:var(--primary);margin-right:6px"></i> FAQ (Pertanyaan Umum)</h3>
        @php $faqSection = $allSections->get('faq', collect()); @endphp
        <div class="form-group">
            <label>Judul Section</label>
            <input type="text" id="faq_title" class="form-input" value="{{ $faqSection->where('key','title')->first()?->value ?? 'Pertanyaan Umum' }}">
        </div>

        <h4 style="font-size:.85rem;font-weight:700;margin:20px 0 12px"><i class="fas fa-list-ul" style="color:var(--primary);margin-right:4px"></i> Daftar FAQ</h4>
        <div id="faqItemsContainer"></div>
        <button type="button" class="add-item-btn" onclick="addFaqItem()"><i class="fas fa-plus"></i> Tambah FAQ</button>
    </div>
    <button type="button" onclick="saveFaq()" class="btn btn-primary" style="margin-top:16px"><i class="fas fa-save"></i> Simpan FAQ</button>
</div>

<!-- ==================== CTA SECTION ==================== -->
<div class="section-panel" id="panel-cta">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-bullhorn" style="color:var(--primary);margin-right:6px"></i> Call to Action (CTA)</h3>
        @php $ctaSection = $allSections->get('cta', collect()); @endphp
        <div class="form-group">
            <label>Judul</label>
            <input type="text" id="cta_title" class="form-input" value="{{ $ctaSection->where('key','title')->first()?->value ?? '' }}">
        </div>
        <div class="form-group">
            <label>Sub Judul</label>
            <textarea id="cta_subtitle" class="form-input" rows="3">{{ $ctaSection->where('key','subtitle')->first()?->value ?? '' }}</textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Tombol (Teks)</label>
                <input type="text" id="cta_button_text" class="form-input" value="{{ $ctaSection->where('key','button_text')->first()?->value ?? 'Daftar Gratis' }}">
            </div>
            <div class="form-group">
                <label>Tombol (Link)</label>
                <input type="text" id="cta_button_link" class="form-input" value="{{ $ctaSection->where('key','button_link')->first()?->value ?? '/register' }}">
            </div>
        </div>
    </div>
    <button type="button" onclick="saveSection('cta')" class="btn btn-primary"><i class="fas fa-save"></i> Simpan CTA</button>
</div>

<!-- ==================== CONTACT SECTION ==================== -->
<div class="section-panel" id="panel-contact">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-envelope" style="color:var(--primary);margin-right:6px"></i> Kontak</h3>
        @php $contactSection = $allSections->get('contact', collect()); @endphp
        <div class="form-group">
            <label>Judul Section</label>
            <input type="text" id="contact_title" class="form-input" value="{{ $contactSection->where('key','title')->first()?->value ?? '' }}">
        </div>
        <div class="form-group">
            <label>Sub Judul</label>
            <input type="text" id="contact_subtitle" class="form-input" value="{{ $contactSection->where('key','subtitle')->first()?->value ?? '' }}">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Nomor WhatsApp (tanpa +)</label>
                <input type="text" id="contact_whatsapp" class="form-input" value="{{ $contactSection->where('key','whatsapp')->first()?->value ?? '' }}" placeholder="6281234567890">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="contact_email" class="form-input" value="{{ $contactSection->where('key','email')->first()?->value ?? '' }}">
            </div>
        </div>
        <div class="form-group">
            <label>Alamat</label>
            <textarea id="contact_address" class="form-input" rows="2">{{ $contactSection->where('key','address')->first()?->value ?? '' }}</textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Instagram</label>
                <input type="text" id="contact_instagram" class="form-input" value="{{ $contactSection->where('key','instagram')->first()?->value ?? '' }}" placeholder="username">
            </div>
            <div class="form-group">
                <label>Facebook</label>
                <input type="text" id="contact_facebook" class="form-input" value="{{ $contactSection->where('key','facebook')->first()?->value ?? '' }}" placeholder="username">
            </div>
        </div>
        <div class="form-group">
            <label>YouTube</label>
            <input type="text" id="contact_youtube" class="form-input" value="{{ $contactSection->where('key','youtube')->first()?->value ?? '' }}" placeholder="channel">
        </div>
    </div>
    <button type="button" onclick="saveContact()" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Kontak</button>
</div>

<!-- ==================== WHATSAPP GROUP SECTION ==================== -->
<div class="section-panel" id="panel-wa_group">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fab fa-whatsapp" style="color:#25D366;margin-right:6px"></i> Grup WhatsApp</h3>
        @php $waSection = $allSections->get('wa_group', collect()); @endphp
        <div class="form-group">
            <label>Label Navigasi</label>
            <input type="text" id="wa_group_nav_label" class="form-input" value="{{ $waSection->where('key','nav_label')->first()?->value ?? 'Komunitas' }}">
            <div style="font-size:.7rem;color:#94a3b8;margin-top:4px">Teks yang tampil di menu navigasi (default: Komunitas)</div>
        </div>
        <div class="form-group">
            <label>Judul Section</label>
            <input type="text" id="wa_group_title" class="form-input" value="{{ $waSection->where('key','title')->first()?->value ?? 'Bergabung Grup WhatsApp FixPro' }}">
        </div>
        <div class="form-group">
            <label>Sub Judul</label>
            <textarea id="wa_group_subtitle" class="form-input" rows="3">{{ $waSection->where('key','subtitle')->first()?->value ?? '' }}</textarea>
        </div>
        <div class="form-group">
            <label>Teks Tombol</label>
            <input type="text" id="wa_group_button_text" class="form-input" value="{{ $waSection->where('key','button_text')->first()?->value ?? 'Bergabung Sekarang' }}">
        </div>
        <div class="form-group">
            <label>Link Grup WhatsApp</label>
            <input type="url" id="wa_group_link" class="form-input" value="{{ $waSection->where('key','link')->first()?->value ?? 'https://chat.whatsapp.com/G41Mmc3CzWD2CsQGSlEljj' }}" placeholder="https://chat.whatsapp.com/...">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Statistik Anggota (contoh: 1.2k+ Anggota)</label>
                <input type="text" id="wa_group_stat_members" class="form-input" value="{{ $waSection->where('key','stat_members')->first()?->value ?? '1.2k+' }}">
            </div>
            <div class="form-group">
                <label>Statistik Aktivitas (contoh: Aktif Setiap Hari)</label>
                <input type="text" id="wa_group_stat_active" class="form-input" value="{{ $waSection->where('key','stat_active')->first()?->value ?? 'Aktif Setiap Hari' }}">
            </div>
        </div>
        <div class="form-group">
            <label>QR Code Gambar (untuk popup desktop)</label>
            @php $waQrImg = $waSection->where('key','qr_image')->first(); @endphp
            @if($waQrImg && $waQrImg->image)
            <div style="margin-bottom:10px">
                <img src="{{ Storage::url($waQrImg->image) }}" style="max-height:160px;border-radius:8px;border:1px solid #e2e8f0">
                <div style="font-size:.7rem;color:#94a3b8;margin-top:4px">Gambar saat ini tersimpan. Upload gambar baru untuk mengganti.</div>
            </div>
            @else
            <div style="margin-bottom:10px">
                <img src="{{ asset('Fixpro_Official_Support.png') }}" style="max-height:160px;border-radius:8px;border:1px solid #e2e8f0;opacity:.6">
                <div style="font-size:.7rem;color:#94a3b8;margin-top:4px">Gambar default. Upload gambar baru untuk mengganti.</div>
            </div>
            @endif
            <input type="file" id="wa_group_qr_image" accept="image/*" class="form-input">
        </div>
    </div>
    <button type="button" onclick="saveWaGroup()" class="btn btn-primary" style="margin-top:16px;background:#25D366;border-color:#25D366"><i class="fab fa-whatsapp"></i> Simpan Grup WhatsApp</button>
</div>

<!-- ==================== MOBILE APP SECTION ==================== -->
<div class="section-panel" id="panel-mobile_app">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:6px"><i class="fas fa-mobile-alt" style="color:#6366f1;margin-right:6px"></i> Aplikasi Mobile (APK Android)</h3>
        <p style="font-size:.78rem;color:#64748b;margin-bottom:20px">Upload file APK yang akan ditampilkan di website publik untuk diunduh pengunjung.</p>

        @php
            $apkItem = $allSections->get('mobile_app', collect());
            $currentApk = $apkItem->where('key','apk_file')->first();
            $currentVersion = $apkItem->where('key','apk_file')->first()?->image ?? '1.0';
            $currentFilename = $apkItem->where('key','apk_filename')->first()?->value ?? '-';
            $currentSize = $apkItem->where('key','apk_size')->first()?->value ?? '-';
        @endphp

        @if($currentApk && $currentApk->value)
        <div style="padding:20px;border:1.5px solid #22c55e;border-radius:12px;background:#f0fdf4;margin-bottom:20px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem"><i class="fab fa-android"></i></div>
                <div>
                    <div style="font-weight:700;font-size:.92rem;color:#0f172a">{{ $currentFilename }}</div>
                    <div style="font-size:.75rem;color:#64748b">Versi {{ $currentVersion }} &middot; {{ $currentSize }}</div>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <a href="/download-apk" target="_blank" class="btn btn-sm" style="background:#22c55e;border-color:#22c55e;color:#fff"><i class="fab fa-android"></i> Test Download</a>
                <button type="button" class="btn btn-danger btn-sm" onclick="deleteApk()"><i class="fas fa-trash"></i> Hapus APK</button>
            </div>
        </div>
        @else
        <div style="padding:20px;border:1.5px dashed #e2e8f0;border-radius:12px;text-align:center;margin-bottom:20px;color:#94a3b8">
            <i class="fas fa-cloud-upload-alt" style="font-size:2rem;margin-bottom:8px;display:block;opacity:.5"></i>
            <div style="font-size:.85rem;font-weight:600">Belum ada APK yang diupload</div>
            <div style="font-size:.75rem">Upload file APK di bawah untuk menampilkannya di website.</div>
        </div>
        @endif

        <div class="form-group">
            <label>Upload APK Baru</label>
            <input type="file" id="apkFile" accept=".apk" class="form-input" style="padding:10px">
            <div style="font-size:.7rem;color:#94a3b8;margin-top:4px">Format: .apk &middot; Maks: 100MB</div>
        </div>
        <div class="form-group">
            <label>Versi Aplikasi</label>
            <input type="text" id="apkVersion" class="form-input" value="{{ $currentVersion }}" placeholder="contoh: 2.1.0" style="max-width:200px">
        </div>
    </div>
    <button type="button" onclick="uploadApk()" class="btn btn-primary" style="margin-top:16px;background:#6366f1;border-color:#6366f1"><i class="fas fa-cloud-upload-alt"></i> Upload APK</button>
</div>

<!-- ==================== FOOTER SECTION ==================== -->
<div class="section-panel" id="panel-footer">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-shoe-prints" style="color:var(--primary);margin-right:6px"></i> Footer</h3>
        @php $footerSection = $allSections->get('footer', collect()); @endphp
        <div class="form-group">
            <label>Copyright</label>
            <input type="text" id="footer_copyright" class="form-input" value="{{ $footerSection->where('key','copyright')->first()?->value ?? '© 2026 FixPro AL2000. All rights reserved.' }}">
        </div>
        <div class="form-group">
            <label>Tagline</label>
            <input type="text" id="footer_tagline" class="form-input" value="{{ $footerSection->where('key','tagline')->first()?->value ?? 'Sistem Manajemen Servis Profesional' }}">
        </div>
    </div>
    <button type="button" onclick="saveSection('footer')" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Footer</button>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

// ── Helpers ──
function showAlert(msg, type) {
    const el = document.getElementById('webAlert');
    const txt = document.getElementById('webAlertText');
    el.className = 'web-alert show ' + (type || 'info');
    txt.textContent = msg;
    setTimeout(() => el.className = 'web-alert', 4000);
}
function showLoading() { document.getElementById('webLoading').classList.add('show'); }
function hideLoading() { document.getElementById('webLoading').classList.remove('show'); }

function refreshPreview() {
    const iframe = document.getElementById('previewIframe');
    iframe.src = iframe.src;
}

// ── Section Tabs ──
function switchSection(name) {
    document.querySelectorAll('.section-tab').forEach((t, i) => {
        const target = t.getAttribute('onclick') || '';
        t.classList.toggle('active', target.includes("'" + name + "'"));
    });
    document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
    const panel = document.getElementById('panel-' + name);
    if (panel) panel.classList.add('active');
}

// ── Generic save ──
async function saveFields(section, fields) {
    showLoading();
    try {
        const fd = new FormData();
        fd.append('section', section);
        for (const [key, value] of Object.entries(fields)) {
            fd.append('fields[' + key + '][value]', value);
        }
        const res = await fetch('/admin/website/update-section', {
            method: 'POST', body: fd,
            headers: {'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest'}
        });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) {
            hideLoading();
            showAlert('Gagal menyimpan. Coba refresh halaman.', 'error');
            return;
        }
        hideLoading();
        if (data.success) {
            showAlert('✅ ' + (data.message || 'Berhasil disimpan!'), 'success');
            refreshPreview();
        } else {
            showAlert('❌ ' + (data.error || data.message || 'Gagal menyimpan'), 'error');
        }
    } catch(e) {
        hideLoading();
        showAlert('❌ Error: ' + e.message, 'error');
    }
}

async function saveJsonItems(section, key, items) {
    showLoading();
    try {
        const fd = new FormData();
        fd.append('section', section);
        fd.append('key', key);
        fd.append('items', JSON.stringify(items));
        const res = await fetch('/admin/website/update-json-items', {
            method: 'POST', body: fd,
            headers: {'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest'}
        });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) {
            hideLoading();
            showAlert('Gagal menyimpan. Coba refresh halaman.', 'error');
            return;
        }
        hideLoading();
        if (data.success) {
            showAlert('✅ ' + (data.message || 'Berhasil disimpan!'), 'success');
            refreshPreview();
        } else {
            showAlert('❌ ' + (data.error || data.message || 'Gagal menyimpan'), 'error');
        }
    } catch(e) {
        hideLoading();
        showAlert('❌ Error: ' + e.message, 'error');
    }
}

async function saveWithImage(section, fields, imageInputId) {
    showLoading();
    try {
        const fd = new FormData();
        fd.append('section', section);
        for (const [key, value] of Object.entries(fields)) {
            fd.append('fields[' + key + '][value]', value);
        }
        const fileInput = document.getElementById(imageInputId);
        if (fileInput && fileInput.files.length) {
            fd.append('fields[image][image]', fileInput.files[0]);
        }
        const res = await fetch('/admin/website/update-section', {
            method: 'POST', body: fd,
            headers: {'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest'}
        });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) {
            hideLoading(); showAlert('Gagal menyimpan.', 'error'); return;
        }
        hideLoading();
        if (data.success) {
            showAlert('✅ ' + (data.message || 'Berhasil disimpan!'), 'success');
            refreshPreview();
        } else {
            showAlert('❌ ' + (data.error || 'Gagal'), 'error');
        }
    } catch(e) {
        hideLoading(); showAlert('❌ ' + e.message, 'error');
    }
}

// ── Save functions per section ──
function saveSection(section) {
    const fields = {};
    document.querySelectorAll('#panel-' + section + ' [id^="' + section + '_"]').forEach(el => {
        const key = el.id.replace(section + '_', '');
        fields[key] = el.value;
    });
    saveFields(section, fields);
}

function saveContact() {
    const fields = {};
    ['title','subtitle','whatsapp','email','address','instagram','facebook','youtube'].forEach(k => {
        const el = document.getElementById('contact_' + k);
        if (el) fields[k] = el.value;
    });
    saveFields('contact', fields);
}

function saveAbout() {
    const fields = {};
    ['title','description'].forEach(k => {
        const el = document.getElementById('about_' + k);
        if (el) fields[k] = el.value;
    });
    saveWithImage('about', fields, 'about_image');
}

// ── HERO STATS ──
let heroStats = @json(\App\Models\WebsiteContent::getJson('hero', 'stats', []));
function renderHeroStats() {
    const c = document.getElementById('heroStatsContainer');
    c.innerHTML = heroStats.map((s, i) =>
        '<div class="json-list-item"><div class="json-list-header"><h4>Statistik #' + (i+1) + '</h4><button class="btn btn-danger btn-xs" onclick="heroStats.splice('+i+',1);renderHeroStats()"><i class="fas fa-trash"></i></button></div>' +
        '<div class="form-row"><div class="form-group"><label>Angka</label><input type="text" class="form-input" value="' + (s.number||'') + '" onchange="heroStats['+i+'].number=this.value"></div>' +
        '<div class="form-group"><label>Label</label><input type="text" class="form-input" value="' + (s.label||'') + '" onchange="heroStats['+i+'].label=this.value"></div></div></div>'
    ).join('');
}
function addHeroStat() { heroStats.push({number:'',label:''}); renderHeroStats(); }

// Override hero save to include stats
const _saveHero = saveSection;
saveSection = function(section) {
    if (section === 'hero') {
        const fields = {};
        ['title','subtitle','cta_text','cta_link','cta_secondary_text','cta_secondary_link'].forEach(k => {
            const el = document.getElementById('hero_' + k);
            if (el) fields[k] = el.value;
        });
        fields['stats'] = JSON.stringify(heroStats);
        saveFields('hero', fields);
    } else {
        _saveHero(section);
    }
};

// ── FEATURES ──
let featureItems = @json(\App\Models\WebsiteContent::getJson('features', 'items', []));
function renderFeatureItems() {
    const c = document.getElementById('featuresItemsContainer');
    c.innerHTML = featureItems.map((f, i) =>
        '<div class="json-list-item"><div class="json-list-header"><h4><i class="' + (f.icon||'fas fa-star') + '" style="color:var(--primary);margin-right:6px"></i>' + (f.title||'Fitur #'+(i+1)) + '</h4><button class="btn btn-danger btn-xs" onclick="featureItems.splice('+i+',1);renderFeatureItems()"><i class="fas fa-trash"></i></button></div>' +
        '<div class="form-row"><div class="form-group"><label>Icon (Font Awesome)</label><input type="text" class="form-input" value="' + (f.icon||'') + '" onchange="featureItems['+i+'].icon=this.value" placeholder="fas fa-tools"></div>' +
        '<div class="form-group"><label>Judul</label><input type="text" class="form-input" value="' + (f.title||'') + '" onchange="featureItems['+i+'].title=this.value"></div></div>' +
        '<div class="form-group"><label>Deskripsi</label><textarea class="form-input" rows="2" onchange="featureItems['+i+'].description=this.value">' + (f.description||'') + '</textarea></div></div>'
    ).join('');
}
function addFeatureItem() { featureItems.push({icon:'fas fa-star',title:'',description:''}); renderFeatureItems(); }
function saveFeatures() {
    const fields = {};
    ['title','subtitle'].forEach(k => {
        const el = document.getElementById('features_' + k);
        if (el) fields[k] = el.value;
    });
    fields['items'] = JSON.stringify(featureItems);
    saveFields('features', fields);
}

// ── SERVICES ──
let serviceItems = @json(\App\Models\WebsiteContent::getJson('services', 'items', []));
function renderServiceItems() {
    const c = document.getElementById('servicesItemsContainer');
    c.innerHTML = serviceItems.map((s, i) =>
        '<div class="json-list-item"><div class="json-list-header"><h4>' + (s.title||'Layanan #'+(i+1)) + '</h4><button class="btn btn-danger btn-xs" onclick="serviceItems.splice('+i+',1);renderServiceItems()"><i class="fas fa-trash"></i></button></div>' +
        '<div class="form-row"><div class="form-group"><label>Icon</label><input type="text" class="form-input" value="' + (s.icon||'') + '" onchange="serviceItems['+i+'].icon=this.value"></div>' +
        '<div class="form-group"><label>Judul</label><input type="text" class="form-input" value="' + (s.title||'') + '" onchange="serviceItems['+i+'].title=this.value"></div></div>' +
        '<div class="form-group"><label>Deskripsi</label><textarea class="form-input" rows="2" onchange="serviceItems['+i+'].description=this.value">' + (s.description||'') + '</textarea></div></div>'
    ).join('');
}
function addServiceItem() { serviceItems.push({icon:'fas fa-cog',title:'',description:''}); renderServiceItems(); }
function saveServices() {
    const fields = {};
    ['title','subtitle'].forEach(k => {
        const el = document.getElementById('services_' + k);
        if (el) fields[k] = el.value;
    });
    fields['items'] = JSON.stringify(serviceItems);
    saveFields('services', fields);
}

// ── PRICING ──
let pricingItems = @json(\App\Models\WebsiteContent::getJson('pricing', 'items', []));
function renderPricingItems() {
    const c = document.getElementById('pricingItemsContainer');
    c.innerHTML = pricingItems.map((p, i) =>
        '<div class="json-list-item" style="border-color:' + (p.popular?'var(--primary)':'#e2e8f0') + '"><div class="json-list-header"><h4>' + (p.name||'Paket #'+(i+1)) + (p.popular?' <span class="badge badge-selesai" style="font-size:.6rem">Populer</span>':'') + '</h4><button class="btn btn-danger btn-xs" onclick="pricingItems.splice('+i+',1);renderPricingItems()"><i class="fas fa-trash"></i></button></div>' +
        '<div class="form-row"><div class="form-group"><label>Nama Paket</label><input type="text" class="form-input" value="' + (p.name||'') + '" onchange="pricingItems['+i+'].name=this.value"></div>' +
        '<div class="form-group"><label>Harga</label><input type="text" class="form-input" value="' + (p.price||'') + '" onchange="pricingItems['+i+'].price=this.value"></div></div>' +
        '<div class="form-row"><div class="form-group"><label>Periode</label><input type="text" class="form-input" value="' + (p.period||'') + '" onchange="pricingItems['+i+'].period=this.value" placeholder="/bulan"></div>' +
        '<div class="form-group"><label>Teks Tombol</label><input type="text" class="form-input" value="' + (p.button_text||'') + '" onchange="pricingItems['+i+'].button_text=this.value"></div></div>' +
        '<div class="form-row"><div class="form-group"><label>Link Tombol</label><input type="text" class="form-input" value="' + (p.button_link||'#') + '" onchange="pricingItems['+i+'].button_link=this.value"></div>' +
        '<div class="form-group"><label style="display:flex;align-items:center;gap:8px;margin-top:24px"><input type="checkbox" ' + (p.popular?'checked':'') + ' onchange="pricingItems['+i+'].popular=this.checked" style="width:16px;height:16px;accent-color:var(--primary)"><span>Populer</span></label></div></div>' +
        '<div class="form-group"><label>Fitur (satu per baris)</label><textarea class="form-input" rows="4" onchange="pricingItems['+i+'].features=this.value.split(\'\\n\').filter(f=>f.trim())">' + ((p.features||[]).join('\n')) + '</textarea></div></div>'
    ).join('');
}
function addPricingItem() { pricingItems.push({name:'',price:'',period:'',features:[],popular:false,button_text:'Pilih',button_link:'#'}); renderPricingItems(); }
function savePricing() {
    const fields = {};
    ['title','subtitle'].forEach(k => {
        const el = document.getElementById('pricing_' + k);
        if (el) fields[k] = el.value;
    });
    fields['items'] = JSON.stringify(pricingItems);
    saveFields('pricing', fields);
}

// ── TESTIMONIALS ──
let testimonialItems = @json(\App\Models\WebsiteContent::getJson('testimonials', 'items', []));
function renderTestimonialItems() {
    const c = document.getElementById('testimonialItemsContainer');
    c.innerHTML = testimonialItems.map((t, i) =>
        '<div class="json-list-item"><div class="json-list-header"><h4>' + (t.name||'Testimoni #'+(i+1)) + '</h4><button class="btn btn-danger btn-xs" onclick="testimonialItems.splice('+i+',1);renderTestimonialItems()"><i class="fas fa-trash"></i></button></div>' +
        '<div class="form-row"><div class="form-group"><label>Nama</label><input type="text" class="form-input" value="' + (t.name||'') + '" onchange="testimonialItems['+i+'].name=this.value"></div>' +
        '<div class="form-group"><label>Role/Jabatan</label><input type="text" class="form-input" value="' + (t.role||'') + '" onchange="testimonialItems['+i+'].role=this.value"></div></div>' +
        '<div class="form-group"><label>Testimoni</label><textarea class="form-input" rows="3" onchange="testimonialItems['+i+'].content=this.value">' + (t.content||'') + '</textarea></div>' +
        '<div class="form-group"><label>Rating (1-5)</label><input type="number" class="form-input" value="' + (t.rating||5) + '" min="1" max="5" style="max-width:100px" onchange="testimonialItems['+i+'].rating=parseInt(this.value)"></div></div>'
    ).join('');
}
function addTestimonialItem() { testimonialItems.push({name:'',role:'',content:'',rating:5}); renderTestimonialItems(); }
function saveTestimonials() {
    const fields = {};
    ['title','subtitle'].forEach(k => {
        const el = document.getElementById('testimonials_' + k);
        if (el) fields[k] = el.value;
    });
    fields['items'] = JSON.stringify(testimonialItems);
    saveFields('testimonials', fields);
}

// ── FAQ ──
let faqItems = @json(\App\Models\WebsiteContent::getJson('faq', 'items', []));
function renderFaqItems() {
    const c = document.getElementById('faqItemsContainer');
    c.innerHTML = faqItems.map((f, i) =>
        '<div class="json-list-item"><div class="json-list-header"><h4>' + (f.question||'FAQ #'+(i+1)) + '</h4><button class="btn btn-danger btn-xs" onclick="faqItems.splice('+i+',1);renderFaqItems()"><i class="fas fa-trash"></i></button></div>' +
        '<div class="form-group"><label>Pertanyaan</label><input type="text" class="form-input" value="' + (f.question||'').replace(/"/g, '&quot;') + '" onchange="faqItems['+i+'].question=this.value"></div>' +
        '<div class="form-group"><label>Jawaban</label><textarea class="form-input" rows="3" onchange="faqItems['+i+'].answer=this.value">' + (f.answer||'') + '</textarea></div></div>'
    ).join('');
}
function addFaqItem() { faqItems.push({question:'',answer:''}); renderFaqItems(); }
function saveFaq() {
    const fields = {};
    const el = document.getElementById('faq_title');
    if (el) fields['title'] = el.value;
    fields['items'] = JSON.stringify(faqItems);
    saveFields('faq', fields);
}

// ── Init render ──
document.addEventListener('DOMContentLoaded', function() {
    renderHeroStats();
    renderFeatureItems();
    renderServiceItems();
    renderPricingItems();
    renderTestimonialItems();
    renderFaqItems();
});

// ── WHATSAPP GROUP ──
function saveWaGroup() {
    const fields = {};
    ['nav_label','title','subtitle','button_text','link','stat_members','stat_active'].forEach(k => {
        const el = document.getElementById('wa_group_' + k);
        if (el) fields[k] = el.value;
    });
    saveWithImage('wa_group', fields, 'wa_group_qr_image');
}

// ── MOBILE APP APK ──
async function uploadApk() {
    const fileInput = document.getElementById('apkFile');
    if (!fileInput || !fileInput.files.length) {
        showAlert('Pilih file APK terlebih dahulu.', 'error');
        return;
    }
    const file = fileInput.files[0];
    if (file.size > 104857600) {
        showAlert('Ukuran file melebihi 100MB. Pilih file yang lebih kecil.', 'error');
        return;
    }
    showLoading();
    const fd = new FormData();
    fd.append('apk', file);
    fd.append('version', document.getElementById('apkVersion').value || '1.0');
    try {
        const res = await fetch('/admin/website/upload-apk', {
            method: 'POST', body: fd,
            headers: {'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest'}
        });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch(e) {
            hideLoading();
            if (text.includes('413') || text.includes('Request Entity Too Large') || text.includes('upload_max_filesize') || text.includes('post_max_size')) {
                showAlert('File terlalu besar untuk diupload. Hubungi admin server untuk menaikkan batas upload_max_filesize & post_max_size PHP.', 'error');
            } else if (res.status === 422) {
                showAlert('Format file tidak valid. Pastikan file berformat .apk', 'error');
            } else {
                showAlert('Gagal upload. Server error ' + res.status, 'error');
            }
            return;
        }
        hideLoading();
        if (data.success) {
            showAlert('✅ ' + data.message + ' (' + data.filename + ', v' + data.version + ', ' + data.size + ')', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            const errs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal upload');
            showAlert('❌ ' + errs, 'error');
        }
    } catch(e) {
        hideLoading(); showAlert('❌ ' + e.message, 'error');
    }
}

async function deleteApk() {
    if (!confirm('Yakin ingin menghapus APK? File akan dihapus dan tombol download di website akan disembunyikan.')) return;
    showLoading();
    try {
        const res = await fetch('/admin/website/delete-apk', {
            method: 'DELETE',
            headers: {'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
        });
        const data = await res.json();
        hideLoading();
        if (data.success) {
            showAlert('✅ ' + data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showAlert('❌ ' + (data.message || 'Gagal menghapus'), 'error');
        }
    } catch(e) {
        hideLoading(); showAlert('❌ ' + e.message, 'error');
    }
}
</script>
@endsection
