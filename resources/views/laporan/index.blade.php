@extends('layouts.app')
@section('title', 'Laporan')

@section('content')
<h2 class="mb-4" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    Laporan
    @if(!empty($tahunAktif))
    <span style="background:var(--primary-bg,#ccfbf1);color:var(--primary,#0d9480);padding:3px 10px;border-radius:10px;font-size:.72rem;font-weight:700">Tahun {{ $tahunAktif }}</span>
    @else
    <span style="background:#f1f5f9;color:#64748b;padding:3px 10px;border-radius:10px;font-size:.72rem;font-weight:700">Semua Tahun</span>
    @endif
</h2>

<div class="stats-grid mb-6">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-label">Omset Servis</div>
        <div class="stat-value" style="color:var(--primary)">{{ formatRp($totalOmset) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:var(--info)"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-label">Laba Sparepart (POS)</div>
        <div class="stat-value" style="color:var(--info)">{{ formatRp($labaSparepart) }}</div>
        <div class="text-xs text-muted">Omset Bersih {{ formatRp($omsetBersihSparepart ?? $omsetSparepart) }} − Modal</div>
    </div>
    @if(($diskonSparepart ?? 0) > 0)
    <div class="stat-card" style="border-color:#fde68a">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-tag"></i></div>
        <div class="stat-label">Diskon SP (di luar laba)</div>
        <div class="stat-value" style="color:#dc2626">- {{ formatRp($diskonSparepart) }}</div>
        <div class="text-xs text-muted">Kotor {{ formatRp($omsetSparepart) }} → Bersih {{ formatRp($omsetBersihSparepart) }}</div>
    </div>
    @endif
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-tools"></i></div>
        <div class="stat-label">Total Servis</div>
        <div class="stat-value" style="color:var(--success)">{{ $totalServis }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-star"></i></div>
        <div class="stat-label">Terpopuler</div>
        <div class="stat-value" style="color:var(--warning);font-size:1rem">{{ $popular }}</div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <form method="GET">
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
            <div class="form-group" style="margin:0">
                <label class="text-xs font-bold text-muted">Tahun Aktif</label>
                <select name="tahun" class="form-input" onchange="this.form.submit()" style="padding:8px 12px;font-size:.84rem">
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
                <input type="date" name="dari" class="form-input" value="{{ request('dari') }}" style="padding:8px 12px;font-size:.84rem">
            </div>
            <div class="form-group" style="margin:0">
                <label class="text-xs font-bold text-muted">Sampai (custom)</label>
                <input type="date" name="sampai" class="form-input" value="{{ request('sampai') }}" style="padding:8px 12px;font-size:.84rem">
            </div>
            <div class="form-group" style="margin:0">
                <label class="text-xs font-bold text-muted">Status</label>
                <select name="status" class="form-input" style="padding:8px 12px;font-size:.84rem">
                    <option value="">Semua</option>
                    @foreach(['Masuk','Proses','Pending','Selesai'] as $st)
                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
            <a href="{{ route('laporan.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Reset</a>
        </div>
        <div style="font-size:.74rem;color:#64748b;margin-top:8px">
            <i class="fas fa-info-circle"></i>
            @if(!empty($tahunAktif))
                Data dilingkup ke <strong>Tahun {{ $tahunAktif }}</strong>. Isi custom date untuk periode lain.
            @else
                Menampilkan <strong>semua tahun</strong>.
            @endif
        </div>
    </form>
</div>

<div class="grid-2 mb-6">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:4px"><i class="fas fa-users" style="color:var(--primary);margin-right:6px"></i>Omset Per Teknisi</h3>
        <p class="text-xs text-muted mb-4">Revenue dari servis</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Teknisi</th><th>Total</th><th>Selesai</th><th>Omset</th></tr></thead>
                <tbody>
                    @foreach($teknisiPerf as $t)
                    <tr>
                        <td>{{ $t->nama }}</td>
                        <td>{{ $t->total }}</td>
                        <td><span class="badge badge-selesai">{{ $t->selesai }}</span></td>
                        <td><strong style="color:var(--primary)">{{ formatRp($t->omset) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:4px">Laba Harian (14 Hari)</h3>
        <p class="text-xs text-muted mb-4">Omset vs Modal</p>
        <div class="chart-container">
            <canvas id="chartLaba"></canvas>
        </div>
    </div>
</div>

<!-- Detail Laporan -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-table" style="color:var(--primary);margin-right:6px"></i>Daftar Servis</h3>
        <span class="text-muted text-sm">{{ $servis->count() }} data</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Tgl</th><th>Pelanggan</th><th>Perangkat</th><th>Teknisi</th><th>Status</th><th>Biaya Jasa</th><th>Harga Jual SP</th><th>Modal SP</th><th>Laba SP</th><th>Laba Servis</th></tr></thead>
            <tbody>
                @foreach($servis as $s)
                <tr>
                    <td><strong style="color:var(--primary)">{{ $s->kode }}</strong></td>
                    <td>{{ $s->tanggal?->format('d/m/Y') }}</td>
                    <td>{{ $s->pelanggan?->nama ?? '-' }}</td>
                    <td>{{ $s->perangkat }}</td>
                    <td>{{ $s->teknisi?->nama ?? '-' }}</td>
                    <td><span class="badge badge-{{ strtolower($s->status) }}">{{ $s->status }}</span></td>
                    <td style="font-weight:600">{{ formatRp($s->biaya) }}</td>
                    <td style="font-weight:600;color:#7c3aed">{{ formatRp($s->harga_jual_sp ?? 0) }}</td>
                    <td style="color:#dc2626">{{ formatRp($s->modal_sp ?? 0) }}</td>
                    <td style="font-weight:600;color:#2563eb">{{ formatRp($s->laba_sp_servis ?? 0) }}</td>
                    <td style="font-weight:700;color:#16a34a">{{ formatRp($s->laba_servis ?? 0) }}</td>
                </tr>
                @endforeach
                @if($servis->count() === 0)
                <tr><td colspan="11" style="text-align:center;color:#94a3b8;padding:20px">Belum ada data servis pada filter ini</td></tr>
                @endif
            </tbody>
            <tfoot>
                <tr style="background:#f0fdf4">
                    <td colspan="6" style="padding:10px 12px;font-size:.74rem;font-weight:700;color:#166534;text-align:right">TOTAL {{ $servis->count() }} SERVIS</td>
                    <td style="padding:10px 12px;font-weight:800;color:#0f172a">{{ formatRp($totalBiayaJasa) }}</td>
                    <td style="padding:10px 12px;font-weight:800;color:#7c3aed">{{ formatRp($totalHargaJualSp) }}</td>
                    <td style="padding:10px 12px;font-weight:800;color:#dc2626">{{ formatRp($totalModalSp) }}</td>
                    <td style="padding:10px 12px;font-weight:800;color:#2563eb">{{ formatRp($totalLabaSp) }}</td>
                    <td style="padding:10px 12px;font-weight:800;color:#16a34a;font-size:1rem">{{ formatRp($totalLabaServis) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    {{-- Notif ringkasan otomatis --}}
    <div style="margin-top:14px;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:1px solid #bbf7d0;border-radius:12px;padding:16px">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
            <div style="flex:1;min-width:130px;background:#fff;border-radius:10px;padding:12px 14px;border:1px solid #e2e8f0">
                <span style="display:block;font-size:.64rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px">Laba Servis (Biaya − Hrg Jual SP)</span>
                <span style="font-size:1.05rem;font-weight:800;color:#0f172a">{{ formatRp($totalLabaServis) }}</span>
            </div>
            <div style="font-size:1.2rem;font-weight:800;color:#94a3b8">+</div>
            <div style="flex:1;min-width:130px;background:#fff;border-radius:10px;padding:12px 14px;border:1px solid #e2e8f0">
                <span style="display:block;font-size:.64rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px">Laba Sparepart (Hrg Jual − Modal)</span>
                <span style="font-size:1.05rem;font-weight:800;color:#2563eb">{{ formatRp($totalLabaSp) }}</span>
            </div>
            <div style="font-size:1.2rem;font-weight:800;color:#94a3b8">=</div>
            <div style="flex:1;min-width:130px;background:linear-gradient(135deg,#0d9488,#065f46);border-radius:10px;padding:12px 14px">
                <span style="display:block;font-size:.64rem;font-weight:700;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px">TOTAL LABA BERSIH</span>
                <span style="font-size:1.2rem;font-weight:800;color:#fff">{{ formatRp($totalLabaBersih) }}</span>
            </div>
        </div>
        <div style="margin-top:12px;padding-top:10px;border-top:1px dashed #bbf7d0;font-size:.72rem;color:#166534;display:flex;align-items:center;gap:6px">
            <i class="fas fa-calculator"></i>
            <span>Hitung otomatis: <strong>Laba Servis = Biaya Servis − Harga Jual Sparepart</strong>. <strong>Laba Sparepart = Harga Jual {{ formatRp($totalHargaJualSp) }} − Modal {{ formatRp($totalModalSp) }}</strong>. Total Laba = Laba Servis + Laba Sparepart.</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const labaData = @json($labaChart);
    new Chart(document.getElementById('chartLaba'), {
        type: 'line',
        data: {
            labels: labaData.map(d => d.date),
            datasets: [
                { label: 'Omset', data: labaData.map(d => d.omset), borderColor: '#0d9488', backgroundColor: 'rgba(13,148,136,.1)', fill: true, tension: .4 },
                { label: 'Modal', data: labaData.map(d => d.modal), borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,.1)', fill: true, tension: .4 },
                { label: 'Laba', data: labaData.map(d => d.laba), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.1)', fill: true, tension: .4 },
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });
});
</script>
@endsection
