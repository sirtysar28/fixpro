@extends('layouts.app')
@section('title', 'Laporan Keuangan')

@section('content')
<style>
    .lk-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px; }
    .lk-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
    .lk-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 20px; }
    .lk-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-bottom: 20px; }
    .lk-stat {
        background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 18px;
        transition: transform .2s, box-shadow .2s;
    }
    .lk-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,.06); }
    .lk-stat .ls-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: .95rem; margin-bottom: 10px;
    }
    .lk-stat .ls-label { font-size: .72rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
    .lk-stat .ls-value { font-size: 1.5rem; font-weight: 800; margin-top: 4px; }
    .lk-stat .ls-sub { font-size: .66rem; color: #94a3b8; margin-top: 4px; }
    .lk-highlight {
        background: linear-gradient(135deg, #0d9488, #065f46); border: none; color: #fff;
    }
    .lk-highlight .ls-label { color: rgba(255,255,255,.7); }
    .lk-highlight .ls-value { color: #fff; font-size: 1.8rem; }
    .lk-highlight .ls-sub { color: rgba(255,255,255,.6); }
    .lk-highlight .ls-icon { background: rgba(255,255,255,.15); color: #fff; }

    .lk-section { font-size: .82rem; font-weight: 700; color: #334155; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; padding-bottom: 8px; border-bottom: 1.5px solid #e2e8f0; }
    .lk-section i { color: var(--primary); }

    .lk-tabs { display: flex; gap: 0; border-bottom: 2px solid #e2e8f0; margin-bottom: 16px; }
    .lk-tab { padding: 10px 20px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; background: none; color: #94a3b8; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all .2s; }
    .lk-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
    .lk-tab:hover { color: var(--primary-dark); }
    .lk-panel { display: none; }
    .lk-panel.active { display: block; }

    /* Notif ringkasan bawah tabel servis */
    .lk-notif { background: linear-gradient(135deg,#f0fdf4,#ecfdf5); border:1px solid #bbf7d0; border-radius:12px; padding:16px; margin-top:14px; }
    .lk-notif-row { display:flex; flex-wrap:wrap; align-items:center; gap:12px; }
    .lk-notif-item { flex:1; min-width:130px; background:#fff; border-radius:10px; padding:12px 14px; border:1px solid #e2e8f0; }
    .lk-notif-item .ln-label { display:block; font-size:.64rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.3px; margin-bottom:4px; }
    .lk-notif-item .ln-value { font-size:1.05rem; font-weight:800; color:#0f172a; }
    .lk-notif-op { font-size:1.2rem; font-weight:800; color:#94a3b8; }
    .lk-notif-item.lk-notif-highlight { background:linear-gradient(135deg,#0d9488,#065f46); border:none; }
    .lk-notif-item.lk-notif-highlight .ln-label { color:rgba(255,255,255,.7); }
    .lk-notif-item.lk-notif-highlight .ln-value { color:#fff; }
    .lk-notif-note { margin-top:12px; padding-top:10px; border-top:1px dashed #bbf7d0; font-size:.72rem; color:#166534; display:flex; align-items:center; gap:6px; }
    .lk-notif-note strong { color:#065f46; }
    body.dark .lk-notif { background:#0f172a; border-color:#134e4a; }
    body.dark .lk-notif-item { background:#1e293b; border-color:#334155; }
    body.dark .lk-notif-item .ln-label { color:#94a3b8; }
    body.dark .lk-notif-item .ln-value { color:#e2e8f0; }
    body.dark .lk-notif-note { color:#6ee7b7; border-top-color:#134e4a; }

    .print-btn { position: fixed; bottom: 20px; right: 20px; z-index: 90; }

    @media (max-width: 1200px) { .lk-grid-4 { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) { .lk-grid-4, .lk-grid-3, .lk-grid-2 { grid-template-columns: 1fr; } }

    body.dark .lk-stat { background: #1e293b; border-color: #334155; }
    body.dark .lk-stat .ls-label { color: #94a3b8; }
    body.dark .lk-stat .ls-value { color: #e2e8f0; }
    body.dark .lk-stat .ls-sub { color: #64748b; }
    body.dark .lk-highlight { background: linear-gradient(135deg, #115e59, #134e4a); }
    body.dark .lk-section { color: #e2e8f0; border-bottom-color: #334155; }
    body.dark .lk-tab { color: #94a3b8; }
    body.dark .lk-tab.active { color: #2dd4bf; border-bottom-color: #2dd4bf; }
</style>

<h2 style="margin:0 0 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <i class="fas fa-chart-line" style="color:var(--primary)"></i> Laporan Keuangan
    @if(!empty($tahunAktif))
    <span style="background:var(--primary-bg,#ccfbf1);color:var(--primary,#0d9488);padding:3px 10px;border-radius:10px;font-size:.72rem;font-weight:700">Tahun {{ $tahunAktif }}</span>
    @else
    <span style="background:#f1f5f9;color:#64748b;padding:3px 10px;border-radius:10px;font-size:.72rem;font-weight:700">Semua Tahun</span>
    @endif
    <span style="font-size:.7rem;color:#94a3b8;font-weight:400">— Seluruh Transaksi Berhasil</span>
    <a href="#" id="btnExportExcel" onclick="exportExcel();return false;" class="btn btn-success" style="margin-left:auto;background:#16a34a;color:#fff;text-decoration:none;font-size:.82rem;padding:8px 14px">
        <i class="fas fa-file-excel"></i> Export ke Excel
    </a>
</h2>

{{-- ====== STAT CARDS ====== --}}
<div class="lk-grid-4">
    <div class="lk-stat lk-highlight">
        <div class="ls-icon"><i class="fas fa-trophy"></i></div>
        <div class="ls-label">LABA BERSIH</div>
        <div class="ls-value">{{ formatRp($labaBersih) }}</div>
        <div class="ls-sub">Margin <strong style="color:#fff">{{ $margin }}%</strong> dari Total Omset {{ formatRp($totalOmset) }}</div>
    </div>
    <div class="lk-stat">
        <div class="ls-icon" style="background:rgba(13,148,136,.1);color:var(--primary)"><i class="fas fa-wrench"></i></div>
        <div class="ls-label">Laba Servis</div>
        <div class="ls-value" style="color:var(--primary)">{{ formatRp($labaServis) }}</div>
        <div class="ls-sub">Biaya Servis − Harga Jual SP • {{ $totalTransaksiServis }} servis selesai</div>
    </div>
    <div class="lk-stat">
        <div class="ls-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-microchip"></i></div>
        <div class="ls-label">Laba Sparepart (POS)</div>
        <div class="ls-value" style="color:#2563eb">{{ formatRp($labaSP) }}</div>
        <div class="ls-sub">Omset Bersih {{ formatRp($totalOmsetBersihSP) }} - Modal {{ formatRp($totalModalSP) }}</div>
    </div>
    <div class="lk-stat">
        <div class="ls-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-receipt"></i></div>
        <div class="ls-label">Total Transaksi</div>
        <div class="ls-value" style="color:#d97706">{{ $totalTransaksi }}</div>
        <div class="ls-sub">{{ $totalTransaksiServis }} servis + {{ $totalTransaksiSP }} sparepart</div>
    </div>
</div>

{{-- ====== KAS MASUK/KELUAR ====== --}}
<div class="lk-grid-2">
    <div class="lk-stat">
        <div class="ls-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-arrow-down"></i></div>
        <div class="ls-label">Kas Masuk</div>
        <div class="ls-value" style="color:#16a34a">{{ formatRp($kasMasuk) }}</div>
    </div>
    <div class="lk-stat">
        <div class="ls-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-arrow-up"></i></div>
        <div class="ls-label">Kas Keluar</div>
        <div class="ls-value" style="color:#dc2626">{{ formatRp($kasKeluar) }}</div>
    </div>
</div>

{{-- ====== DISKON INFO ====== --}}
@if($totalDiskonSP > 0)
<div style="background:linear-gradient(135deg,#fefce8,#fef3c7);border:1.5px solid #fde68a;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;flex-wrap:wrap;align-items:center;gap:16px">
    <div style="flex:1;min-width:200px">
        <div style="font-size:.72rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px"><i class="fas fa-tag" style="margin-right:4px"></i>DI LUAR LABA — Potongan Diskon Sparepart (POS)</div>
        <div style="font-size:1.3rem;font-weight:800;color:#d97706">- {{ formatRp($totalDiskonSP) }}</div>
        <div style="font-size:.66rem;color:#78350f;margin-top:4px">Diskon <strong style="color:#dc2626">TIDAK</strong> masuk ke perhitungan laba. Dikurangkan dari omset kotor terlebih dahulu, baru dihitung labanya.</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <div style="background:#fff;border-radius:8px;padding:10px 14px;text-align:center;border:1px solid #fde68a">
            <div style="font-size:.6rem;color:#92400e;font-weight:600">Omset Kotor SP</div>
            <div style="font-size:.9rem;font-weight:700;color:#0f172a">{{ formatRp($totalOmsetSP) }}</div>
        </div>
        <div style="font-size:1.2rem;font-weight:800;color:#dc2626">−</div>
        <div style="background:#fee2e2;border-radius:8px;padding:10px 14px;text-align:center;border:1px solid #fecaca">
            <div style="font-size:.6rem;color:#991b1b;font-weight:600">Diskon</div>
            <div style="font-size:.9rem;font-weight:700;color:#dc2626">{{ formatRp($totalDiskonSP) }}</div>
        </div>
        <div style="font-size:1.2rem;font-weight:800;color:#16a34a">=</div>
        <div style="background:#f0fdf4;border-radius:8px;padding:10px 14px;text-align:center;border:1px solid #bbf7d0">
            <div style="font-size:.6rem;color:#166534;font-weight:600">Omset Bersih SP</div>
            <div style="font-size:.9rem;font-weight:700;color:#16a34a">{{ formatRp($totalOmsetBersihSP) }}</div>
        </div>
        <div style="font-size:1.2rem;font-weight:800;color:#dc2626">−</div>
        <div style="background:#fee2e2;border-radius:8px;padding:10px 14px;text-align:center;border:1px solid #fecaca">
            <div style="font-size:.6rem;color:#991b1b;font-weight:600">Modal</div>
            <div style="font-size:.9rem;font-weight:700;color:#dc2626">{{ formatRp($totalModalSP) }}</div>
        </div>
        <div style="font-size:1.2rem;font-weight:800;color:#16a34a">=</div>
        <div style="background:linear-gradient(135deg,#2563eb,#1d4ed8);border-radius:8px;padding:10px 14px;text-align:center">
            <div style="font-size:.6rem;color:rgba(255,255,255,.7);font-weight:600">Laba Bersih POS</div>
            <div style="font-size:.9rem;font-weight:700;color:#fff">{{ formatRp($labaSP) }}</div>
        </div>
    </div>
</div>
@endif

{{-- ====== FILTER ====== --}}
<div class="lk-card">
    <div class="lk-section"><i class="fas fa-filter"></i> Filter Periode</div>
    <form method="GET" id="filterForm">
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
            <div class="form-group" style="margin:0">
                <label class="text-xs font-bold text-muted">Tahun Aktif</label>
                <select name="tahun" class="form-input" onchange="document.getElementById('dariInput').value='';document.getElementById('sampaiInput').value='';this.form.submit()" style="padding:8px 12px;font-size:.84rem">
                    @foreach($tahunTersedia as $th)
                    @php $selTh = ($tahunAktif ?? null) === (int)$th ? 'selected' : ''; @endphp
                    <option value="{{ $th }}" {{ $selTh }}>Tahun {{ $th }}</option>
                    @endforeach
                    @php $selAll = ($tahunAktif ?? null) === null ? 'selected' : ''; @endphp
                    <option value="all" {{ $selAll }}>Semua Tahun</option>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label class="text-xs font-bold text-muted">Dari (custom)</label>
                <input type="date" id="dariInput" name="dari" class="form-input" value="{{ $dari ?? '' }}" style="padding:8px 12px;font-size:.84rem">
            </div>
            <div class="form-group" style="margin:0">
                <label class="text-xs font-bold text-muted">Sampai (custom)</label>
                <input type="date" id="sampaiInput" name="sampai" class="form-input" value="{{ $sampai ?? '' }}" style="padding:8px 12px;font-size:.84rem">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
            <a href="{{ route('laporan-keuangan.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Reset</a>
            <button type="button" onclick="exportExcel()" class="btn btn-success btn-sm" style="background:#16a34a;color:#fff">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
        </div>
        <div style="font-size:.74rem;color:#64748b;margin-top:8px">
            <i class="fas fa-info-circle"></i>
            @if(!empty($tahunAktif))
                Menampilkan data periode <strong>Tahun {{ $tahunAktif }}</strong> (1 Jan - 31 Des). Ganti tahun di atas atau isi custom date untuk periode lain.
            @else
                Menampilkan <strong>semua tahun</strong>. Pilih tahun tertentu untuk fokus ke satu periode.
            @endif
        </div>
    </form>
</div>

{{-- ====== GRAFIK 12 BULAN ====== --}}
<div class="lk-card">
    <div class="lk-section"><i class="fas fa-chart-area"></i> Tren Laba 12 Bulan Terakhir</div>
    <div class="chart-container" style="height:280px">
        <canvas id="chartMonthly"></canvas>
    </div>
</div>

{{-- ====== TEKNISI PERFORMANCE ====== --}}
<div class="lk-card">
    <div class="lk-section"><i class="fas fa-user-tie"></i> Omset Per Teknisi</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Teknisi</th><th>Jml Servis</th><th>Omset</th></tr></thead>
            <tbody>
                @foreach($teknisiPerf as $t)
                <tr>
                    <td><strong>{{ $t->nama }}</strong></td>
                    <td>{{ $t->total }}</td>
                    <td><strong style="color:var(--primary)">{{ formatRp($t->omset) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ====== DETAIL TRANSAKSI ====== --}}
<div class="lk-card">
    <div class="lk-section"><i class="fas fa-table"></i> Detail Transaksi</div>
    <div class="lk-tabs">
        <button class="lk-tab active" onclick="switchLKTab('servis')"><i class="fas fa-wrench"></i> Servis Selesai ({{ $totalTransaksiServis }})</button>
        <button class="lk-tab" onclick="switchLKTab('sparepart')"><i class="fas fa-microchip"></i> Penjualan Sparepart ({{ $totalTransaksiSP }})</button>
    </div>

    {{-- Panel Servis --}}
    <div id="lkPanelServis" class="lk-panel active">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Kode</th><th>Tanggal</th><th>Pelanggan</th><th>Perangkat</th><th>Teknisi</th><th>Biaya (Omset)</th><th>Harga Jual SP</th><th>Modal SP</th><th>Laba SP</th><th>Laba Bersih</th></tr></thead>
                <tbody>
                    @foreach($servisSelesai as $s)
                    <tr>
                        <td><strong style="color:var(--primary)">{{ $s->kode }}</strong></td>
                        <td>{{ $s->tgl_diambil?->format('d/m/Y') }}</td>
                        <td>{{ $s->pelanggan?->nama ?? '-' }}</td>
                        <td>{{ Str::limit($s->perangkat, 20) }}</td>
                        <td>{{ $s->teknisi?->nama ?? '-' }}</td>
                        <td style="font-weight:600">{{ formatRp($s->biaya) }}</td>
                        <td style="font-weight:600;color:#7c3aed">{{ formatRp($s->harga_jual_sp ?? 0) }}</td>
                        <td style="color:#dc2626">{{ formatRp($s->modal_sp ?? 0) }}</td>
                        <td style="font-weight:600;color:#2563eb">{{ formatRp($s->laba_sp_servis ?? 0) }}</td>
                        <td style="font-weight:700;color:#16a34a;background:#f0fdf4;padding:4px 8px;border-radius:6px">{{ formatRp($s->laba_total ?? 0) }}</td>
                    </tr>
                    @endforeach
                    @if($servisSelesai->count() === 0)
                    <tr><td colspan="10" style="text-align:center;color:#94a3b8;padding:20px;font-size:.8rem">Belum ada transaksi servis selesai pada periode ini</td></tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr style="background:#f0fdf4">
                        <td colspan="5" style="padding:10px 12px;font-size:.74rem;font-weight:700;color:#166534;text-align:right">TOTAL {{ $totalTransaksiServis }} SERVIS SELESAI</td>
                        <td style="padding:10px 12px;font-weight:800;color:#0f172a">{{ formatRp($totalOmsetServis) }}</td>
                        <td style="padding:10px 12px;font-weight:800;color:#7c3aed">{{ formatRp($totalHargaJualSpServis) }}</td>
                        <td style="padding:10px 12px;font-weight:800;color:#dc2626">{{ formatRp($totalModalServisSP) }}</td>
                        <td style="padding:10px 12px;font-weight:800;color:#2563eb">{{ formatRp($labaSpServis) }}</td>
                        <td style="padding:10px 12px;font-weight:800;color:#16a34a;font-size:1rem;background:#dcfce7;border-radius:6px">{{ formatRp($labaServis + $labaSpServis) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        {{-- Notif ringkasan otomatis bawah tabel servis --}}
        <div class="lk-notif">
            <div class="lk-notif-row">
                <div class="lk-notif-item">
                    <span class="ln-label">Laba Servis (Biaya − Hrg Jual SP)</span>
                    <span class="ln-value">{{ formatRp($labaServis) }}</span>
                </div>
                <div class="lk-notif-op">+</div>
                <div class="lk-notif-item">
                    <span class="ln-label">Laba Sparepart (Hrg Jual − Modal)</span>
                    <span class="ln-value" style="color:#2563eb">{{ formatRp($labaSpServis) }}</span>
                </div>
                <div class="lk-notif-op">=</div>
                <div class="lk-notif-item lk-notif-highlight">
                    <span class="ln-label">LABA BERSIH SERVIS</span>
                    <span class="ln-value">{{ formatRp($labaServis + $labaSpServis) }}</span>
                </div>
            </div>
            <div class="lk-notif-note">
                <i class="fas fa-calculator"></i>
                <span>Hitung otomatis: <strong>Laba Servis = Biaya Servis {{ formatRp($totalOmsetServis) }} − Harga Jual SP {{ formatRp($totalHargaJualSpServis) }}</strong>. Laba Sparepart Servis = Harga Jual − Modal = <strong>{{ formatRp($labaSpServis) }}</strong>. Total laba bersih keseluruhan: <strong>{{ formatRp($labaBersih) }}</strong> (Laba Servis + Laba SP Servis + Laba SP POS).</span>
            </div>
        </div>
    </div>

    {{-- Panel Sparepart --}}
    <div id="lkPanelSparepart" class="lk-panel">
        <div class="table-wrap">
            <table>
                <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Pelanggan</th><th>Qty</th><th>Total</th><th>Diskon</th><th>Modal</th><th>Laba</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach($penjualanSP as $sp)
                    <tr>
                        <td><strong style="color:var(--primary)">{{ $sp->no_transaksi ?? $sp->id }}</strong></td>
                        <td>{{ $sp->tanggal?->format('d/m/Y') }}</td>
                        <td>{{ $sp->pelanggan?->nama ?? '-' }}</td>
                        <td style="font-size:.76rem;color:#64748b">{{ $sp->qty ?? 1 }}</td>
                        <td style="font-weight:600">{{ formatRp($sp->total) }}</td>
                        <td style="color:#dc2626">{{ $sp->diskon > 0 ? formatRp($sp->diskon) : '-' }}</td>
                        <td style="color:#dc2626">{{ formatRp($sp->modal_total ?? 0) }}</td>
                        <td style="font-weight:700;color:#16a34a">{{ formatRp(($sp->total ?? 0) - ($sp->modal_total ?? 0)) }}</td>
                        <td>
                            <a href="{{ route('penjualan-sparepart.show', $sp) }}" class="btn btn-primary btn-xs"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    @endforeach
                    @if($penjualanSP->count() === 0)
                    <tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:20px;font-size:.8rem">Belum ada penjualan sparepart pada periode ini</td></tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr style="background:#eff6ff">
                        <td colspan="4" style="padding:10px 12px;font-size:.74rem;font-weight:700;color:#1e40af;text-align:right">TOTAL {{ $totalTransaksiSP }} TRANSAKSI POS</td>
                        <td style="padding:10px 12px;font-weight:800;color:#0f172a">{{ formatRp($totalOmsetSP) }}</td>
                        <td style="padding:10px 12px;font-weight:800;color:#dc2626">{{ $totalDiskonSP > 0 ? formatRp($totalDiskonSP) : '-' }}</td>
                        <td style="padding:10px 12px;font-weight:800;color:#dc2626">{{ formatRp($totalModalSP) }}</td>
                        <td style="padding:10px 12px;font-weight:800;color:#16a34a;font-size:1rem">{{ formatRp($labaSP) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<button onclick="window.print()" class="btn btn-primary print-btn" style="border-radius:50%;width:50px;height:50px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(0,0,0,.15)" title="Cetak Laporan">
    <i class="fas fa-print"></i>
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly chart
    const monthlyData = @json($monthlyBreakdown);
    new Chart(document.getElementById('chartMonthly'), {
        type: 'bar',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [
                { label: 'Laba Servis', data: monthlyData.map(d => d.laba_servis), backgroundColor: 'rgba(13,148,136,.6)', borderRadius: 4 },
                { label: 'Laba SP (POS)', data: monthlyData.map(d => d.laba_sp), backgroundColor: 'rgba(37,99,235,.6)', borderRadius: 4 },
                { label: 'Laba Total', data: monthlyData.map(d => d.laba_total), backgroundColor: 'rgba(22,163,74,.6)', borderRadius: 4 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                x: { stacked: false, grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 }, callback: v => 'Rp ' + (v/1000000).toFixed(1) + 'jt' } }
            },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
                tooltip: { backgroundColor: '#1e293b', cornerRadius: 8, callbacks: { label: ctx => ctx.dataset.label + ': Rp ' + ctx.raw.toLocaleString('id-ID') } }
            }
        }
    });
});

function switchLKTab(tab) {
    document.querySelectorAll('.lk-tab').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.lk-panel').forEach(el => el.classList.remove('active'));
    event.target.closest('.lk-tab').classList.add('active');
    document.getElementById(tab === 'servis' ? 'lkPanelServis' : 'lkPanelSparepart').classList.add('active');
}

// ===== EXPORT KE EXCEL =====
function exportExcel() {
    const dari = document.querySelector('input[name="dari"]')?.value || '';
    const sampai = document.querySelector('input[name="sampai"]')?.value || '';
    const tahun = document.querySelector('select[name="tahun"]')?.value || '';
    const params = new URLSearchParams();
    if (dari) params.set('dari', dari);
    if (sampai) params.set('sampai', sampai);
    if (tahun) params.set('tahun', tahun);
    const url = '{{ route("laporan-keuangan.export") }}' + (params.toString() ? '?' + params.toString() : '');
    // tampilkan loading sebentar
    const btn = document.getElementById('btnExportExcel');
    if (btn) {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan...';
        btn.style.pointerEvents = 'none';
        // langsung trigger download
        window.location.href = url;
        setTimeout(() => { btn.innerHTML = original; btn.style.pointerEvents = ''; }, 2500);
    } else {
        window.location.href = url;
    }
}
</script>
@endsection
