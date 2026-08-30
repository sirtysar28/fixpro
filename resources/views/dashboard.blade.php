@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<style>
    /* ===== DASHBOARD STYLES ===== */
    .dash-grid-6 { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; margin-bottom: 18px; }
    .dash-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 18px; }
    .dash-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 18px; }
    .dash-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-bottom: 18px; }
    /* dash-grid-21 */
    .dash-grid-21 { display: grid; grid-template-columns: 2fr 1fr; gap: 14px; margin-bottom: 18px; }
    .dash-grid-12 { display: grid; grid-template-columns: 1fr 2fr; gap: 14px; margin-bottom: 18px; }
    .dash-banner-scroll { display: flex; gap: 16px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 18px; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; }
    .dash-banner-scroll::-webkit-scrollbar { height: 4px; }
    .dash-banner-scroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
    .dash-banner-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

    .dash-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 18px;
        transition: transform .2s, box-shadow .2s;
        position: relative;
        overflow: hidden;
    }
    .dash-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,.06); }
    .dash-card .dc-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; margin-bottom: 12px;
    }
    .dash-card .dc-label { font-size: .72rem; color: #64748b; font-weight: 600; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .3px; }
    .dash-card .dc-value { font-size: 1.5rem; font-weight: 800; line-height: 1.1; }
    .dash-card .dc-sub { font-size: .66rem; color: #94a3b8; margin-top: 4px; line-height: 1.4; }
    .dash-card .dc-sub strong { color: #475569; }

    /* Big highlight card */
    .dash-card-highlight {
        background: linear-gradient(135deg, #0d9488, #065f46);
        border: none; color: #fff;
    }
    .dash-card-highlight .dc-label { color: rgba(255,255,255,.7); }
    .dash-card-highlight .dc-value { color: #fff; font-size: 2rem; }
    .dash-card-highlight .dc-sub { color: rgba(255,255,255,.65); }
    .dash-card-highlight .dc-sub strong { color: rgba(255,255,255,.85); }
    .dash-card-highlight .dc-icon { background: rgba(255,255,255,.15); color: #fff; }

    /* Urgent card */
    .dash-card-urgent {
        border-left: 4px solid #dc2626;
    }

    /* Section header */
    .dash-section {
        font-size: .82rem; font-weight: 700; color: #334155;
        margin-bottom: 12px; display: flex; align-items: center; gap: 8px;
        padding-bottom: 8px; border-bottom: 1.5px solid #e2e8f0;
    }
    .dash-section i { color: var(--primary); font-size: .88rem; }

    /* Analysis box */
    .analysis-box {
        background: #f8fafc; border-radius: 10px; padding: 16px;
        border: 1px solid #e2e8f0;
    }
    .analysis-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 7px 0; border-bottom: 1px dashed #e2e8f0;
        font-size: .78rem;
    }
    .analysis-row:last-child { border-bottom: none; }
    .analysis-row .ar-label { color: #475569; }
    .analysis-row .ar-value { font-weight: 700; color: #0f172a; }
    .analysis-row .ar-value.positive { color: #16a34a; }
    .analysis-row .ar-value.negative { color: #dc2626; }

    /* Legend items */
    .legend-items { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
    .legend-item { display: flex; align-items: center; gap: 5px; font-size: .7rem; font-weight: 600; color: #475569; }
    .legend-dot { width: 10px; height: 10px; border-radius: 50%; }

    /* Footer bar */
    .dash-footer-bar {
        position: fixed; bottom: 0; left: var(--sidebar-w); right: 0;
        height: 44px; background: #fff; border-top: 1.5px solid #e2e8f0;
        display: flex; align-items: center; justify-content: center; gap: 32px;
        z-index: 90; font-size: .74rem; padding: 0 20px;
        box-shadow: 0 -2px 10px rgba(0,0,0,.04);
    }
    .dash-footer-item { display: flex; align-items: center; gap: 6px; }
    .dash-footer-item .df-label { color: #94a3b8; font-weight: 600; text-transform: uppercase; font-size: .64rem; letter-spacing: .3px; }
    .dash-footer-item .df-value { font-weight: 800; color: #0f172a; }
    .dash-footer-item .df-value.teal { color: var(--primary); }
    .dash-footer-live {
        background: #dcfce7; color: #16a34a; font-size: .64rem; font-weight: 700;
        padding: 3px 10px; border-radius: 20px; display: flex; align-items: center; gap: 5px;
        animation: pulse-live 2s infinite;
    }
    .dash-footer-live .live-dot { width: 6px; height: 6px; border-radius: 50%; background: #16a34a; }
    @keyframes pulse-live { 0%,100% { opacity: 1; } 50% { opacity: .5; } }

    /* Make room for footer bar */
    .page-content { padding-bottom: 64px !important; }

    /* Responsive */
    @media (max-width: 1200px) {
        .dash-grid-6 { grid-template-columns: repeat(3, 1fr); }
        .dash-grid-4 { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 900px) {
        .dash-grid-6 { grid-template-columns: repeat(2, 1fr); }
        .dash-grid-4 { grid-template-columns: 1fr; }
        .dash-grid-3 { grid-template-columns: 1fr; }
        .dash-grid-2 { grid-template-columns: 1fr; }
        .dash-grid-21 { grid-template-columns: 1fr; }
        .dash-grid-12 { grid-template-columns: 1fr; }
        .dash-footer-bar { left: 0; gap: 16px; font-size: .68rem; }
    }
    @media (max-width: 480px) {
        .dash-grid-6 { grid-template-columns: 1fr; }
    }

    /* Dark mode dashboard */
    body.dark .dash-card { background: #1e293b; border-color: #334155; }
    body.dark .dash-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,.3); }
    body.dark .dash-card .dc-label { color: #94a3b8; }
    body.dark .dash-card .dc-value { color: #e2e8f0; }
    body.dark .dash-card .dc-sub { color: #64748b; }
    body.dark .dash-card .dc-sub strong { color: #94a3b8; }
    body.dark .dash-card-highlight { background: linear-gradient(135deg, #115e59, #134e4a); }
    body.dark .dash-card-urgent { border-left-color: #ef4444; }
    body.dark .dash-section { color: #e2e8f0; border-bottom-color: #334155; }
    body.dark .analysis-box { background: #0f172a; border-color: #334155; }
    body.dark .analysis-row { border-bottom-color: #1e293b; }
    body.dark .analysis-row .ar-label { color: #94a3b8; }
    body.dark .analysis-row .ar-value { color: #e2e8f0; }
    body.dark .legend-item { color: #94a3b8; }
    body.dark .dash-footer-bar { background: #1e293b; border-top-color: #334155; }
    body.dark .dash-footer-item .df-label { color: #64748b; }
    body.dark .dash-footer-item .df-value { color: #e2e8f0; }
    body.dark .dash-footer-live .live-dot { background: #4ade80; }
    .dash-banner-card { min-width: 240px; max-width: 260px; scroll-snap-align: start; background: #fff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; transition: transform .2s, box-shadow .2s; flex-shrink: 0; }
    .dash-banner-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,.08); }
    .dash-banner-card img { width: 100%; aspect-ratio: 3/4; object-fit: cover; display: block; }
    .dash-banner-card .bc-body { padding: 16px 18px 18px; }
    .dash-banner-card .bc-title { font-size: .92rem; font-weight: 800; color: #0f172a; line-height: 1.3; margin-bottom: 8px; }
    .dash-banner-card .bc-desc { font-size: .76rem; color: #475569; line-height: 1.65; margin-bottom: 14px; }
    .dash-banner-card .bc-desc ul, .dash-banner-card .bc-desc ol { margin: 4px 0; padding-left: 16px; }
    .dash-banner-card .bc-desc li { margin-bottom: 3px; }
    .dash-banner-card .bc-desc strong { color: #1e293b; }
    .dash-banner-card .bc-btns { display: flex; gap: 8px; }
    .dash-banner-card .bc-btn { display: inline-flex; align-items: center; justify-content: center; gap: 5px; padding: 10px 16px; color: #fff; border-radius: 10px; font-size: .78rem; font-weight: 700; text-decoration: none; transition: all .2s; flex: 1; }
    .dash-banner-card .bc-btn:hover { opacity: .85; }
    .dash-banner-card .bc-btn-primary { background: var(--primary); }
    .dash-banner-card .bc-btn-wa { background: #25D366; }
    body.dark .dash-banner-card { background: #1e293b; border-color: #334155; }
    body.dark .dash-banner-card .bc-title { color: #e2e8f0; }
    body.dark .dash-banner-card .bc-desc { color: #94a3b8; }
    body.dark .dash-banner-card .bc-desc strong { color: #cbd5e1; }
    body.dark .dash-banner-scroll::-webkit-scrollbar-track { background: #1e293b; }
    body.dark .dash-banner-scroll::-webkit-scrollbar-thumb { background: #475569; }
    .banner-desc p { margin: 0 0 4px; }
    .banner-desc ul, .banner-desc ol { margin: 2px 0; padding-left: 16px; }
    .banner-desc li { margin-bottom: 2px; }
    .banner-desc strong { color: #1e293b; }
</style>

{{-- ====== BRANCH INDICATOR ====== --}}
@if(auth()->user()->isSuperAdmin() && session('cabang_id') === 'all')
<div style="margin-bottom:16px;padding:10px 16px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:10px;border:1px solid #fcd34d;font-size:.8rem;color:#92400e;display:flex;align-items:center;gap:8px">
    <i class="fas fa-globe"></i>
    <strong>Semua Cabang</strong> — Menampilkan data akumulasi dari seluruh cabang.
</div>
@elseif(isset($activeCabang))
<div style="margin-bottom:16px;padding:10px 16px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:10px;border:1px solid #bbf7d0;font-size:.8rem;color:#166534;display:flex;align-items:center;gap:8px">
    <i class="fas fa-store"></i>
    <strong>{{ $activeCabang->nama }}</strong> — Data menurut cabang yang dipilih.
</div>
@endif

{{-- ====== TOP STAT CARDS (6 kolom) ====== --}}
<div class="dash-grid-6">
    {{-- 1. Total Servis --}}
    <div class="dash-card">
        <div class="dc-icon" style="background:rgba(13,148,136,.1);color:var(--primary)"><i class="fas fa-tools"></i></div>
        <div class="dc-label">Total Servis</div>
        <div class="dc-value" style="color:var(--primary)">{{ $totalServis }}</div>
        <div class="dc-sub">Akumulasi semua data</div>
    </div>

    {{-- 2. Menunggu Diambil --}}
    <div class="dash-card" style="{{ $menungguDiambil > 0 ? 'border-left:4px solid var(--warning)' : '' }}">
        <div class="dc-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-box-open"></i></div>
        <div class="dc-label">Menunggu Diambil</div>
        <div class="dc-value" style="color:#d97706">{{ $menungguDiambil }}</div>
        <div class="dc-sub">Selesai belum diambil</div>
    </div>

    {{-- 3. Selesai Hari Ini --}}
    <div class="dash-card">
        <div class="dc-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-check-double"></i></div>
        <div class="dc-label">Selesai Hari Ini</div>
        <div class="dc-value" style="color:#16a34a">{{ $selesaiHariIni }}</div>
        <div class="dc-sub">Real akumulasi harian</div>
    </div>

    {{-- 4. Laba Servis Hari Ini --}}
    <div class="dash-card">
        <div class="dc-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="dc-label">Laba Servis Hari Ini</div>
        <div class="dc-value" style="color:#2563eb">{{ formatRp($labaServisHariIni) }}</div>
        <div class="dc-sub">Biaya servis Selesai/Diambil</div>
    </div>

    {{-- 5. Omset Harian --}}
    <div class="dash-card">
        <div class="dc-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-coins"></i></div>
        <div class="dc-label">Omset Harian</div>
        <div class="dc-value" style="color:#7c3aed">{{ formatRp($omsetHariIni) }}</div>
        <div class="dc-sub">Servis {{ formatRp($labaServisHariIni + $servisSpTotalHariIni) }} + SP POS {{ formatRp($posTotalHariIni) }}</div>
    </div>

    {{-- 6. Laba Sparepart Hari Ini --}}
    <div class="dash-card">
        <div class="dc-icon" style="background:#ecfdf5;color:#059669"><i class="fas fa-cash-register"></i></div>
        <div class="dc-label">Laba SP Hari Ini</div>
        <div class="dc-value" style="color:#059669">{{ formatRp($labaSparepartHariIni) }}</div>
        <div class="dc-sub">POS + Servis SP (jualan - modal)</div>
    </div>
</div>

{{-- ====== INVOICE SPAREPART — PUSAT (khusus Super Admin, revisi) ====== --}}
@if($invoiceStats && auth()->user()->isSuperAdmin())
<div class="dash-section">
    <i class="fas fa-file-invoice-dollar"></i>
    Invoice Sparepart <span style="font-size:.68rem;color:#94a3b8;font-weight:400">— Retail + Grosir + Reseller + Member dalam satu invoice · <a href="{{ route('invoice.create') }}" style="color:var(--primary)">Buat Invoice</a> · <a href="{{ route('invoice.riwayat') }}" style="color:var(--primary)">Riwayat</a> · <a href="{{ route('invoice.piutang') }}" style="color:var(--primary)">Piutang</a></span>
</div>
<div class="dash-grid-6">
    <div class="dash-card">
        <div class="dc-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-cash-register"></i></div>
        <div class="dc-label">Penjualan Hari Ini</div>
        <div class="dc-value" style="color:var(--primary)">{{ formatRp($invoiceStats['penjualan_hari_ini']) }}</div>
        <div class="dc-sub">{{ $invoiceStats['invoice_hari_ini'] }} invoice hari ini</div>
    </div>
    <div class="dash-card">
        <div class="dc-icon" style="background:#ecfdf5;color:#059669"><i class="fas fa-store"></i></div>
        <div class="dc-label">Retail</div>
        <div class="dc-value" style="color:#059669">{{ formatRp($invoiceStats['retail']) }}</div>
        <div class="dc-sub">Pelanggan Umum</div>
    </div>
    <div class="dash-card">
        <div class="dc-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-boxes"></i></div>
        <div class="dc-label">Grosir</div>
        <div class="dc-value" style="color:#d97706">{{ formatRp($invoiceStats['grosir']) }}</div>
        <div class="dc-sub">Grosir 1/2/3 + Distributor</div>
    </div>
    <div class="dash-card">
        <div class="dc-icon" style="background:#fff7ed;color:#ea580c"><i class="fas fa-people-arrows"></i></div>
        <div class="dc-label">Reseller</div>
        <div class="dc-value" style="color:#ea580c">{{ formatRp($invoiceStats['reseller']) }}</div>
        <div class="dc-sub">Harga reseller</div>
    </div>
    <div class="dash-card">
        <div class="dc-icon" style="background:#fdf2f8;color:#db2777"><i class="fas fa-user-check"></i></div>
        <div class="dc-label">Member</div>
        <div class="dc-value" style="color:#db2777">{{ formatRp($invoiceStats['member']) }}</div>
        <div class="dc-sub">Harga member</div>
    </div>
    <div class="dash-card dash-card-urgent">
        <div class="dc-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="dc-label">Piutang / Jatuh Tempo</div>
        <div class="dc-value" style="color:#dc2626">{{ formatRp($invoiceStats['piutang']) }}</div>
        <div class="dc-sub"><strong>{{ $invoiceStats['jatuh_tempo'] }}</strong> invoice lewat tempo · masuk {{ formatRp($invoiceStats['pembayaran_masuk']) }}</div>
    </div>
</div>
@endif

{{-- ====== ANALISIS SPAREPART HARI INI ====== --}}
<div class="dash-section">
    <i class="fas fa-chart-pie"></i>
    Analisis Sparepart Hari Ini <span style="font-size:.68rem;color:#94a3b8;font-weight:400">— Hitung Pasti, Terpisah dari Omset Jasa Servis</span>
</div>
<div class="dash-grid-2" style="margin-bottom:18px">
    {{-- POS --}}
    <div class="analysis-box">
        <div style="font-size:.78rem;font-weight:700;color:#0f172a;margin-bottom:10px"><i class="fas fa-shopping-bag" style="color:#7c3aed;margin-right:4px"></i> Penjualan POS (Kasir)</div>
        <div class="analysis-row">
            <span class="ar-label">Total Penjualan</span>
            <span class="ar-value">{{ formatRp($posTotalHariIni) }}</span>
        </div>
        <div class="analysis-row">
            <span class="ar-label">Modal (HPP)</span>
            <span class="ar-value">{{ formatRp($posModalHariIni) }}</span>
        </div>
        <div class="analysis-row">
            <span class="ar-label">Laba Kotor</span>
            <span class="ar-value {{ $posLabaHariIni >= 0 ? 'positive' : 'negative' }}">{{ formatRp($posLabaHariIni) }}</span>
        </div>
        @php $posMargin = $posTotalHariIni > 0 ? round(($posLabaHariIni / $posTotalHariIni) * 100) : 0; @endphp
        <div class="analysis-row">
            <span class="ar-label">Margin</span>
            <span class="ar-value">{{ $posMargin }}%</span>
        </div>
    </div>

    {{-- SP dari Input Servis --}}
    <div class="analysis-box">
        <div style="font-size:.78rem;font-weight:700;color:#0f172a;margin-bottom:10px"><i class="fas fa-wrench" style="color:var(--primary);margin-right:4px"></i> SP dari Input Servis</div>
        <div class="analysis-row">
            <span class="ar-label">Total Penjualan SP</span>
            <span class="ar-value">{{ formatRp($servisSpTotalHariIni) }}</span>
        </div>
        <div class="analysis-row">
            <span class="ar-label">Modal (HPP)</span>
            <span class="ar-value">{{ formatRp($servisSpModalHariIni) }}</span>
        </div>
        <div class="analysis-row" style="border-bottom:2px solid #e2e8f0;padding-bottom:10px;margin-bottom:4px">
            <span class="ar-label" style="font-weight:700">Total Pendapatan SP</span>
            <span class="ar-value" style="font-size:.88rem">{{ formatRp($totalPendapatanSpHariIni) }}</span>
        </div>
        <div class="analysis-row" style="border:none">
            <span class="ar-label" style="font-weight:700">Total Modal SP (HPP)</span>
            <span class="ar-value" style="font-size:.88rem;color:#dc2626">{{ formatRp($totalModalSpHariIni) }}</span>
        </div>
    </div>
</div>

{{-- ====== SECONDARY STAT CARDS (4 kolom) ====== --}}
<div class="dash-grid-4">
    {{-- Omset Bulanan --}}
    <div class="dash-card">
        <div class="dc-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-calendar-alt"></i></div>
        <div class="dc-label">Omset Bulanan</div>
        <div class="dc-value" style="color:#d97706">{{ formatRp($omsetBulanan) }}</div>
        <div class="dc-sub">Bulan ini — Servis {{ formatRp($omsetServisBulanan) }} + SP POS {{ formatRp($omsetSpBulanan) }}</div>
    </div>

    {{-- Laba Bersih Hari Ini --}}
    <div class="dash-card">
        <div class="dc-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-chart-line"></i></div>
        <div class="dc-label">Laba Bersih Hari Ini</div>
        <div class="dc-value" style="color:#2563eb">{{ formatRp($labaBersihHariIni) }}</div>
        <div class="dc-sub">Servis {{ formatRp($labaServisHariIni) }} + SP Servis {{ formatRp($servisSpLabaHariIni) }} + SP POS {{ formatRp($labaSparepartHariIni) }}</div>
    </div>

    {{-- Saldo Kas --}}
    <div class="dash-card dash-card-urgent">
        <div class="dc-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-wallet"></i></div>
        <div class="dc-label">Saldo Kas (Auto)</div>
        <div class="dc-value" style="color:#dc2626">{{ formatRp($saldoKas) }}</div>
        <div class="dc-sub">Dihitung otomatis dari kas masuk/keluar <br><strong>Real akumulasi aktif</strong></div>
    </div>

    {{-- Laba Sparepart Bulan Ini --}}
    <div class="dash-card">
        <div class="dc-icon" style="background:#ecfdf5;color:#059669"><i class="fas fa-percentage"></i></div>
        <div class="dc-label">Laba SP Bulan Ini</div>
        <div class="dc-value" style="color:#059669">{{ formatRp($totalLabaSparepart) }}</div>
        <div class="dc-sub">Margin <strong>{{ $marginSp }}%</strong> dari Pendapatan {{ formatRp($totalPendapatanSp) }}</div>
    </div>
</div>

{{-- ====== LABA BERSIH + CHARTS SECTION ====== --}}
<div class="dash-grid-3">
    {{-- Laba Bersih Hari Ini Detail --}}
    <div class="dash-card">
        <div style="font-size:.82rem;font-weight:700;color:#0f172a;margin-bottom:16px">
            <i class="fas fa-calculator" style="color:var(--primary);margin-right:4px"></i>
            Laba Bersih Hari Ini
            <div style="font-size:.64rem;color:#94a3b8;font-weight:400;margin-top:2px">Auto Hitung: Servis + Sparepart</div>
        </div>

        <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#f0fdf4;border-radius:10px;margin-bottom:12px;border:1px solid #bbf7d0">
            <div style="width:36px;height:36px;border-radius:8px;background:#dcfce7;display:flex;align-items:center;justify-content:center;color:#16a34a;font-size:.9rem"><i class="fas fa-wrench"></i></div>
            <div>
                <div style="font-size:.68rem;color:#64748b;font-weight:500">Laba Servis</div>
                <div style="font-size:1.15rem;font-weight:800;color:#16a34a">{{ formatRp($labaServisHariIni) }}</div>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#f8fafc;border-radius:10px;margin-bottom:12px;border:1px solid #e2e8f0">
            <div style="width:36px;height:36px;border-radius:8px;background:#dbeafe;display:flex;align-items:center;justify-content:center;color:#2563eb;font-size:.9rem"><i class="fas fa-microchip"></i></div>
            <div>
                <div style="font-size:.68rem;color:#64748b;font-weight:500">Laba Sparepart</div>
                <div style="font-size:1.15rem;font-weight:800;color:#2563eb">{{ formatRp($servisSpLabaHariIni + $labaSparepartHariIni) }}</div>
            </div>
        </div>

        <div style="padding:12px;background:linear-gradient(135deg,#0d9488,#065f46);border-radius:10px;color:#fff;text-align:center">
            <div style="font-size:.68rem;opacity:.7;margin-bottom:2px">TOTAL LABA BERSIH HARI INI</div>
            <div style="font-size:1.4rem;font-weight:800">{{ formatRp($labaBersihHariIni) }}</div>
        </div>
    </div>

    {{-- Status Servis Chart --}}
    <div class="dash-card">
        <div style="font-size:.82rem;font-weight:700;color:#0f172a;margin-bottom:12px">
            <i class="fas fa-tasks" style="color:var(--primary);margin-right:4px"></i>
            Status Servis
            <div style="font-size:.64rem;color:#94a3b8;font-weight:400;margin-top:2px">Distribusi status real-time</div>
        </div>
        <div class="chart-container" style="height:220px">
            <canvas id="chartStatus"></canvas>
        </div>
        <div class="legend-items">
            <div class="legend-item"><div class="legend-dot" style="background:#3b82f6"></div> Masuk</div>
            <div class="legend-item"><div class="legend-dot" style="background:#f59e0b"></div> Proses</div>
            <div class="legend-item"><div class="legend-dot" style="background:#ef4444"></div> Pending</div>
            <div class="legend-item"><div class="legend-dot" style="background:#22c55e"></div> Selesai</div>
            <div class="legend-item"><div class="legend-dot" style="background:#8b5cf6"></div> Diambil</div>
            <div class="legend-item"><div class="legend-dot" style="background:#94a3b8"></div> Dibatalkan</div>
        </div>
    </div>

    {{-- LABA BERSIH BULAN INI --}}
    <div class="dash-card dash-card-highlight">
        <div class="dc-icon"><i class="fas fa-trophy"></i></div>
        <div class="dc-label">LABA BERSIH BULAN INI</div>
        <div class="dc-value">{{ formatRp($labaBersihTotal) }}</div>
        <div class="dc-sub" style="margin-top:10px">
            Margin <strong>{{ $marginBersihTotal }}%</strong> dari Total Pendapatan <strong>{{ formatRp($totalPendapatan) }}</strong>
        </div>
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid rgba(255,255,255,.2);display:flex;justify-content:space-between;font-size:.72rem">
            <div>
                <div style="opacity:.6;margin-bottom:2px">Laba Servis</div>
                <div style="font-weight:700;font-size:.88rem">{{ formatRp($labaServisTotal) }}</div>
            </div>
            <div style="text-align:right">
                <div style="opacity:.6;margin-bottom:2px">Laba Sparepart</div>
                <div style="font-weight:700;font-size:.88rem">{{ formatRp($totalLabaSparepart) }}</div>
            </div>
        </div>
        <div style="margin-top:12px;padding-top:10px;border-top:1px solid rgba(255,255,255,.15);text-align:center">
            <a href="{{ route('laporan-keuangan.index') }}" style="color:#fff;text-decoration:underline;font-size:.72rem;font-weight:600;opacity:.8">📊 Lihat Laporan Keuangan Lengkap →</a>
        </div>
    </div>
</div>

{{-- ====== OMET PER TEKNISI + ARUS KAS ====== --}}
<div class="dash-grid-2">
    {{-- Omset Per Teknisi --}}
    <div class="dash-card">
        <div style="font-size:.82rem;font-weight:700;color:#0f172a;margin-bottom:12px">
            <i class="fas fa-user-tie" style="color:var(--primary);margin-right:4px"></i>
            Omset Per Teknisi
            <div style="font-size:.64rem;color:#94a3b8;font-weight:400;margin-top:2px">Revenue dari servis selesai</div>
        </div>
        @if($teknisiPerf->count() > 0)
        <div class="chart-container" style="height:220px">
            <canvas id="chartTeknisi"></canvas>
        </div>
        @else
        <div style="text-align:center;padding:40px;color:#94a3b8;font-size:.82rem">
            <i class="fas fa-user-slash" style="font-size:1.5rem;margin-bottom:8px;display:block;opacity:.3"></i>
            Belum ada teknisi aktif
        </div>
        @endif
    </div>

    {{-- Arus Kas 7 Hari --}}
    <div class="dash-card">
        <div style="font-size:.82rem;font-weight:700;color:#0f172a;margin-bottom:12px">
            <i class="fas fa-chart-bar" style="color:var(--primary);margin-right:4px"></i>
            Arus Kas 7 Hari
            <div style="font-size:.64rem;color:#94a3b8;font-weight:400;margin-top:2px">Masuk vs Keluar</div>
        </div>
        <div class="chart-container" style="height:220px">
            <canvas id="chartKasFlow"></canvas>
        </div>
    </div>
</div>

{{-- ====== BANNER IKLAN (Portrait Cards, Horizontal Scroll) ====== --}}
@if($banners->count() > 0)
<div class="dash-section">
    <i class="fas fa-ad" style="color:var(--primary)"></i>
    Info & Promo
</div>
<div class="dash-banner-scroll">
    @foreach($banners as $banner)
    <div class="dash-banner-card">
        @if($banner->gambar)
        <img src="{{ str_starts_with($banner->gambar, 'http') ? $banner->gambar : Storage::url($banner->gambar) }}" alt="{{ $banner->judul }}">
        @endif
        <div class="bc-body">
            <div class="bc-title">{{ $banner->judul }}</div>
            @if($banner->deskripsi)
            <div class="bc-desc">{!! $banner->deskripsi !!}</div>
            @endif
            <div class="bc-btns">
                <a href="{{ $banner->link ?: '#' }}" target="_blank" class="bc-btn bc-btn-primary">
                    <i class="fas fa-rocket"></i> Daftar Sekarang!
                </a>
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings['telp'] ?? '6281234567890') }}?text=Halo%20FixPro,%20saya%20tertarik%20dengan%20{{ urlencode($banner->judul) }}" target="_blank" class="bc-btn bc-btn-wa">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ====== STOK ALERTS (AJAX + PAGINATION) ====== --}}
<div id="stokAlertsSection" style="margin-bottom:18px">
    <div class="dash-section">
        <i class="fas fa-exclamation-triangle" style="color:var(--warning)"></i>
        Peringatan Stok
        <span id="stokAlertsCount" style="margin-left:auto;font-size:.72rem;font-weight:700;color:var(--warning)"></span>
    </div>
    <div id="stokAlertsBox" style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:14px">
        <div style="text-align:center;color:#92400e;font-size:.82rem;padding:10px">
            <i class="fas fa-spinner fa-spin"></i> Memuat peringatan stok...
        </div>
    </div>
</div>

{{-- ====== RINGKASAN CEPAT + LATEST SERVIS ====== --}}
<div class="dash-grid-21">
    {{-- Ringkasan --}}
    <div class="dash-card">
        <div style="font-size:.82rem;font-weight:700;color:#0f172a;margin-bottom:14px">
            <i class="fas fa-info-circle" style="color:var(--primary);margin-right:4px"></i>
            Ringkasan Bulan Ini
            <div style="font-size:.64rem;color:#94a3b8;font-weight:400;margin-top:2px">Data transaksi bulan {{ now()->format('F Y') }}</div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div style="padding:12px;background:#f8fafc;border-radius:10px;text-align:center;border:1px solid #e2e8f0">
                <div style="font-size:1.2rem;font-weight:800;color:#2563eb">{{ $servisMasuk }}</div>
                <div style="font-size:.68rem;color:#3b82f6;font-weight:600">Servis Masuk</div>
            </div>
            <div style="padding:12px;background:#f8fafc;border-radius:10px;text-align:center;border:1px solid #e2e8f0">
                <div style="font-size:1.2rem;font-weight:800;color:#f59e0b">{{ $servisProses }}</div>
                <div style="font-size:.68rem;color:#d97706;font-weight:600">Sedang Proses</div>
            </div>
            <div style="padding:12px;background:#f8fafc;border-radius:10px;text-align:center;border:1px solid #e2e8f0">
                <div style="font-size:1.2rem;font-weight:800;color:#ef4444">{{ $servisPending }}</div>
                <div style="font-size:.68rem;color:#dc2626;font-weight:600">Pending</div>
            </div>
            <div style="padding:12px;background:#f8fafc;border-radius:10px;text-align:center;border:1px solid #e2e8f0">
                <div style="font-size:1.2rem;font-weight:800;color:#7c3aed">{{ $totalTeknisi }}</div>
                <div style="font-size:.68rem;color:#7c3aed;font-weight:600">Teknisi Aktif</div>
            </div>
            <div style="padding:12px;background:#f8fafc;border-radius:10px;text-align:center;border:1px solid #e2e8f0">
                <div style="font-size:1.2rem;font-weight:800;color:#16a34a">{{ $totalPelanggan }}</div>
                <div style="font-size:.68rem;color:#16a34a;font-weight:600">Pelanggan</div>
            </div>
            <div style="padding:12px;background:#f8fafc;border-radius:10px;text-align:center;border:1px solid #e2e8f0">
                <div style="font-size:1.2rem;font-weight:800;color:#059669">{{ $selesaiHariIni }}</div>
                <div style="font-size:.68rem;color:#059669;font-weight:600">Diambil Hari Ini</div>
            </div>
        </div>
    </div>

    {{-- Transaksi Bulan Ini --}}
    <div class="dash-card" style="padding:0;overflow:hidden">
        <div style="padding:16px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0">
            <div style="font-size:.82rem;font-weight:700;color:#0f172a">
                <i class="fas fa-list" style="color:var(--primary);margin-right:4px"></i> Transaksi Bulan Ini
                <span style="font-size:.66rem;color:#94a3b8;font-weight:400">({{ now()->format('F Y') }})</span>
            </div>
            <a href="{{ route('laporan-keuangan.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-chart-bar"></i> Laporan Lengkap</a>
        </div>
        <div style="display:flex;border-bottom:1px solid #e2e8f0">
            <button onclick="switchDashTab('servis')" id="tabServis" class="btn btn-sm" style="border-radius:0;border:none;border-bottom:2px solid var(--primary);color:var(--primary);font-weight:700;font-size:.76rem;padding:10px 18px">Servis Selesai</button>
            <button onclick="switchDashTab('sparepart')" id="tabSparepart" class="btn btn-sm" style="border-radius:0;border:none;border-bottom:2px solid transparent;color:#94a3b8;font-weight:600;font-size:.76rem;padding:10px 18px">Penjualan SP</button>
        </div>
        <div id="panelServis" style="overflow-x:auto">
            <table>
                <thead><tr><th>Kode</th><th>Tgl</th><th>Pelanggan</th><th>Perangkat</th><th>Teknisi</th><th>Biaya Jasa</th><th>Harga Jual SP</th><th>Modal SP</th><th>Laba SP</th><th>Laba Servis</th></tr></thead>
                <tbody>
                    @foreach($latestServisBulan as $s)
                    <tr>
                        <td><strong style="color:var(--primary);font-size:.76rem">{{ $s->kode }}</strong></td>
                        <td style="font-size:.72rem;color:#94a3b8">{{ $s->tgl_diambil?->format('d/m') }}</td>
                        <td style="font-size:.76rem">{{ $s->pelanggan?->nama ?? '-' }}</td>
                        <td style="font-size:.76rem">{{ Str::limit($s->perangkat, 16) }}</td>
                        <td style="font-size:.76rem">{{ $s->teknisi?->nama ?? '-' }}</td>
                        <td style="font-size:.76rem;font-weight:600">{{ formatRp($s->biaya) }}</td>
                        <td style="font-size:.76rem;font-weight:600;color:#7c3aed">{{ formatRp($s->harga_jual_sp ?? 0) }}</td>
                        <td style="font-size:.76rem;color:#dc2626">{{ formatRp($s->modal_sp ?? 0) }}</td>
                        <td style="font-size:.76rem;font-weight:600;color:#2563eb">{{ formatRp($s->laba_sp_servis ?? 0) }}</td>
                        <td style="font-size:.76rem;font-weight:700;color:#16a34a">{{ formatRp($s->laba_servis ?? 0) }}</td>
                    </tr>
                    @endforeach
                    @if($latestServisBulan->count() === 0)
                    <tr><td colspan="10" style="text-align:center;color:#94a3b8;padding:20px;font-size:.8rem">Belum ada transaksi servis bulan ini</td></tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr style="background:#f0fdf4">
                        <td colspan="5" style="padding:10px 12px;font-size:.72rem;font-weight:700;color:#166534;text-align:right">TOTAL {{ $countServisBulan }} servis selesai bulan ini</td>
                        <td style="padding:10px 12px;font-size:.8rem;font-weight:800;color:#0f172a">{{ formatRp($totalBiayaServisBulan) }}</td>
                        <td style="padding:10px 12px;font-size:.78rem;font-weight:700;color:#7c3aed">{{ formatRp($totalHargaJualSpServisBulan) }}</td>
                        <td style="padding:10px 12px;font-size:.78rem;font-weight:700;color:#dc2626">{{ formatRp($totalModalSpServisBulan) }}</td>
                        <td style="padding:10px 12px;font-size:.78rem;font-weight:700;color:#2563eb">{{ formatRp($totalLabaSpServisBulan) }}</td>
                        <td style="padding:10px 12px;font-size:.82rem;font-weight:800;color:#16a34a">{{ formatRp($totalLabaServisBulan) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div id="panelSparepart" style="overflow-x:auto;display:none">
            <table>
                <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Pelanggan</th><th>Total</th><th>Modal</th><th>Laba</th></tr></thead>
                <tbody>
                    @foreach($latestSpBulan as $sp)
                    <tr>
                        <td><strong style="color:var(--primary);font-size:.78rem">{{ $sp->no_transaksi ?? $sp->id }}</strong></td>
                        <td style="font-size:.72rem;color:#94a3b8">{{ $sp->tanggal?->format('d/m') }}</td>
                        <td style="font-size:.78rem">{{ $sp->pelanggan?->nama ?? $sp->nama_pelanggan ?? '-' }}</td>
                        <td style="font-size:.78rem;font-weight:600">{{ formatRp($sp->total) }}</td>
                        <td style="font-size:.78rem;color:#dc2626">{{ formatRp($sp->modal_total) }}</td>
                        <td style="font-size:.78rem;font-weight:700;color:#16a34a">{{ formatRp($sp->total - $sp->modal_total) }}</td>
                    </tr>
                    @endforeach
                    @if($latestSpBulan->count() === 0)
                    <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:20px;font-size:.8rem">Belum ada penjualan sparepart bulan ini</td></tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr style="background:#f0fdf4">
                        <td colspan="3" style="padding:10px 12px;font-size:.74rem;font-weight:700;color:#166534;text-align:right">TOTAL {{ $countSpBulan }} transaksi POS bulan ini</td>
                        <td style="padding:10px 12px;font-size:.84rem;font-weight:800;color:#0f172a">{{ formatRp($totalPenjualanSpBulan) }}</td>
                        <td style="padding:10px 12px;font-size:.78rem;font-weight:700;color:#dc2626">{{ formatRp($totalModalSpBulan) }}</td>
                        <td style="padding:10px 12px;font-size:.84rem;font-weight:800;color:#16a34a">{{ formatRp($totalLabaSpBulan) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- ====== FOOTER BAR ====== --}}
<div class="dash-footer-bar">
    <div class="dash-footer-item">
        <span class="df-label">FS</span>
        <span class="df-value">{{ $totalServis }}</span>
    </div>
    <div style="width:1px;height:20px;background:#e2e8f0"></div>
    <div class="dash-footer-item">
        <span class="df-label"><i class="fas fa-wallet" style="margin-right:2px"></i> Kas</span>
        <span class="df-value teal">{{ formatRp($saldoKas) }}</span>
    </div>
    <div style="width:1px;height:20px;background:#e2e8f0"></div>
    <div class="dash-footer-item">
        <span class="df-label">SP Laba</span>
        <span class="df-value" style="color:#059669">{{ formatRp($totalLabaSparepart) }}</span>
    </div>
    <div style="width:1px;height:20px;background:#e2e8f0"></div>
    <div class="dash-footer-item">
        <span class="df-label">Laba Bersih Bulan</span>
        <span class="df-value teal">{{ formatRp($labaBersihTotal) }}</span>
    </div>
    <div style="flex:1"></div>
    <div class="dash-footer-live">
        <div class="live-dot"></div>
        Live <span id="footerClock">{{ now()->format('H.i.s') }}</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Colors
    const C = {
        primary: '#0d9488',
        masuk: '#3b82f6', proses: '#f59e0b', pending: '#ef4444',
        selesai: '#22c55e', diambil: '#8b5cf6', dibatalkan: '#94a3b8',
        success: '#16a34a', successL: 'rgba(22,163,74,.15)',
        danger: '#dc2626', dangerL: 'rgba(220,38,38,.15)',
    };

    // Status Chart (Doughnut)
    const statusData = @json($statusChart);
    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: {
            labels: ['Masuk','Proses','Pending','Selesai','Diambil','Dibatalkan'],
            datasets: [{
                data: [statusData.Masuk, statusData.Proses, statusData.Pending, statusData.Selesai, statusData.Diambil, statusData.Dibatalkan],
                backgroundColor: [C.masuk, C.proses, C.pending, C.selesai, C.diambil, C.dibatalkan],
                borderWidth: 0, hoverOffset: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '55%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b', titleFont: { size: 11 },
                    bodyFont: { size: 11 }, padding: 10, cornerRadius: 8,
                }
            }
        }
    });

    // Omset Per Teknisi (Horizontal Bar)
    @if($teknisiPerf->count() > 0)
    const teknisiData = @json($teknisiPerf->map(fn($t) => ['nama' => $t->nama, 'omset' => (float) $t->omset]));
    new Chart(document.getElementById('chartTeknisi'), {
        type: 'bar',
        data: {
            labels: teknisiData.map(d => d.nama),
            datasets: [{
                label: 'Omset',
                data: teknisiData.map(d => d.omset),
                backgroundColor: 'rgba(13,148,136,.2)',
                borderColor: C.primary,
                borderWidth: 1.5, borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            scales: {
                x: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 }, callback: v => 'Rp ' + (v/1000) + 'k' } },
                y: { grid: { display: false }, ticks: { font: { size: 11, weight: 600 } } }
            },
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1e293b', cornerRadius: 8, callbacks: { label: ctx => 'Rp ' + ctx.raw.toLocaleString('id-ID') } } }
        }
    });
    @endif

    // Kas Flow Chart
    const kasFlowData = @json($kasFlow);
    new Chart(document.getElementById('chartKasFlow'), {
        type: 'bar',
        data: {
            labels: kasFlowData.map(d => d.date),
            datasets: [
                { label: 'Masuk', data: kasFlowData.map(d => d.masuk), backgroundColor: C.successL, borderColor: C.success, borderWidth: 1, borderRadius: 4 },
                { label: 'Keluar', data: kasFlowData.map(d => d.keluar), backgroundColor: C.dangerL, borderColor: C.danger, borderWidth: 1, borderRadius: 4 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 }, callback: v => 'Rp ' + (v/1000) + 'k' } },
                x: { grid: { display: false }, ticks: { font: { size: 10 } } }
            },
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }, tooltip: { backgroundColor: '#1e293b', cornerRadius: 8, callbacks: { label: ctx => ctx.dataset.label + ': Rp ' + ctx.raw.toLocaleString('id-ID') } } }
        }
    });

    // Tab switch
    function switchDashTab(tab) {
        document.getElementById('panelServis').style.display = tab === 'servis' ? 'block' : 'none';
        document.getElementById('panelSparepart').style.display = tab === 'sparepart' ? 'block' : 'none';
        document.getElementById('tabServis').style.borderBottomColor = tab === 'servis' ? 'var(--primary)' : 'transparent';
        document.getElementById('tabServis').style.color = tab === 'servis' ? 'var(--primary)' : '#94a3b8';
        document.getElementById('tabSparepart').style.borderBottomColor = tab === 'sparepart' ? 'var(--primary)' : 'transparent';
        document.getElementById('tabSparepart').style.color = tab === 'sparepart' ? 'var(--primary)' : '#94a3b8';
    }

    // Footer clock
    function updateFooterClock() {
        const now = new Date();
        const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
        const wib = new Date(utc + (7 * 3600000));
        const el = document.getElementById('footerClock');
        if (el) el.textContent = String(wib.getHours()).padStart(2,'0') + '.' + String(wib.getMinutes()).padStart(2,'0') + '.' + String(wib.getSeconds()).padStart(2,'0');
    }
    updateFooterClock();
    setInterval(updateFooterClock, 1000);

    // ===== STOK ALERTS (AJAX + PAGINATION) =====
    let stokAlertsPage = 1;
    function loadStokAlerts(page) {
        stokAlertsPage = page;
        const box = document.getElementById('stokAlertsBox');
        const section = document.getElementById('stokAlertsSection');
        if (!box) return;
        box.innerHTML = '<div style="text-align:center;color:#92400e;font-size:.82rem;padding:10px"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>';
        fetch('{{ route("api.stok-alerts") }}?page=' + page, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const total = data.total || 0;
                document.getElementById('stokAlertsCount').textContent = total > 0 ? (total + ' item') : '';
                if (total === 0) {
                    section.style.display = 'none';
                    return;
                }
                section.style.display = '';
                let html = '<div style="display:flex;flex-direction:column;gap:8px">';
                data.items.forEach(function(s) {
                    const habis = s.stok === 0;
                    const bg = habis ? '#fee2e2' : '#fef3c7';
                    const col = habis ? '#991b1b' : '#92400e';
                    const bd = habis ? '#fca5a5' : '#fde68a';
                    const ic = habis ? 'times-circle' : 'exclamation-circle';
                    html += '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 14px;background:' + bg + ';border-radius:8px;border:1px solid ' + bd + '">';
                    html += '<div style="font-size:.8rem;font-weight:600;color:' + col + '"><i class="fas fa-' + ic + '"></i> ' + s.nama + ' <span style=\'color:#a16207;font-weight:400;font-size:.72rem\'>(' + s.kode + ')</span></div>';
                    html += '<div style="text-align:right">';
                    html += '<span style="font-size:.72rem;color:#92400e">Sisa</span> ';
                    html += '<strong style="font-size:.88rem;color:' + col + '">' + s.stok + '</strong> ';
                    html += '<span style="font-size:.68rem;color:#a16207">/ min ' + s.min + ' ' + s.satuan + '</span>';
                    html += '</div></div>';
                });
                html += '</div>';

                // pagination footer
                if (data.last_page > 1) {
                    html += '<div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;padding-top:10px;border-top:1px dashed #fde68a">';
                    html += '<span style="font-size:.68rem;color:#a16207">' + data.from + '-' + data.to + ' dari ' + data.total + '</span>';
                    html += '<div style="display:flex;gap:4px">';
                    html += '<button onclick="loadStokAlerts(' + (data.current_page - 1) + ')" ' + (data.current_page <= 1 ? 'disabled' : '') + ' style="padding:4px 10px;border-radius:6px;border:1px solid #fde68a;background:#fff;color:#92400e;font-size:.72rem;font-weight:600;cursor:pointer;opacity:' + (data.current_page <= 1 ? '.4' : '1') + '"><i class=\'fas fa-chevron-left\'></i></button>';
                    html += '<span style="padding:4px 10px;font-size:.72rem;font-weight:700;color:#92400e">' + data.current_page + ' / ' + data.last_page + '</span>';
                    html += '<button onclick="loadStokAlerts(' + (data.current_page + 1) + ')" ' + (data.current_page >= data.last_page ? 'disabled' : '') + ' style="padding:4px 10px;border-radius:6px;border:1px solid #fde68a;background:#fff;color:#92400e;font-size:.72rem;font-weight:600;cursor:pointer;opacity:' + (data.current_page >= data.last_page ? '.4' : '1') + '"><i class=\'fas fa-chevron-right\'></i></button>';
                    html += '</div></div>';
                }
                box.innerHTML = html;
            })
            .catch(() => {
                box.innerHTML = '<div style="font-size:.78rem;color:#92400e;padding:8px">Gagal memuat peringatan stok.</div>';
            });
    }
    window.loadStokAlerts = loadStokAlerts;
    loadStokAlerts(1);
});
</script>
@endsection
