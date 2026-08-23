@extends('layouts.app')
@section('title', 'Dashboard Teknisi')

@section('content')
<style>
.tk-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px; }
.tk-stat { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 18px; transition: transform .2s; }
.tk-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.06); }
.tk-stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; margin-bottom: 10px; }
.tk-stat-label { font-size: .76rem; color: #64748b; font-weight: 500; }
.tk-stat-value { font-size: 1.4rem; font-weight: 800; }
.tk-chart { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px; }
.tk-profile { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 14px; padding: 20px; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 16px; }
.tk-avatar { width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 700; }
.tk-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
@media (max-width: 900px) { .tk-grid { grid-template-columns: 1fr; } }
body.dark .tk-stat { background: #1e293b; border-color: #334155; }
body.dark .tk-chart { background: #1e293b; border-color: #334155; }
</style>

{{-- Profile Banner --}}
<div class="tk-profile">
    <div class="tk-avatar">{{ strtoupper(substr($teknisi->nama, 0, 2)) }}</div>
    <div style="flex:1">
        <div style="font-size:1.15rem;font-weight:700">{{ $teknisi->nama }}</div>
        <div style="font-size:.78rem;opacity:.85"><i class="fas fa-wrench"></i> {{ $teknisi->spesialisasi }} &nbsp;|&nbsp; <i class="fas fa-store"></i> {{ $teknisi->cabang?->nama ?? '-' }}</div>
        @if($teknisi->no_wa)
        <div style="font-size:.72rem;opacity:.7"><i class="fab fa-whatsapp"></i> {{ $teknisi->no_wa }}</div>
        @endif
    </div>
    <div style="text-align:right">
        <div style="font-size:.72rem;opacity:.8">Bagi Hasil</div>
        <div style="font-size:1.6rem;font-weight:800">{{ $bagiHasil }}%</div>
    </div>
</div>

{{-- Stats --}}
<div class="tk-stats">
    <div class="tk-stat">
        <div class="tk-stat-icon" style="background:#fef3c7;color:#92400e"><i class="fas fa-tools"></i></div>
        <div class="tk-stat-label">Servis Dikerjakan</div>
        <div class="tk-stat-value" style="color:#92400e">{{ $servisAktif }}</div>
    </div>
    <div class="tk-stat">
        <div class="tk-stat-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-check-circle"></i></div>
        <div class="tk-stat-label">Servis Selesai</div>
        <div class="tk-stat-value" style="color:#16a34a">{{ $servisSelesai }}</div>
    </div>
    <div class="tk-stat">
        <div class="tk-stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-chart-line"></i></div>
        <div class="tk-stat-label">Omset Bulan Ini</div>
        <div class="tk-stat-value" style="color:var(--primary)">{{ formatRp($omsetBulanIni) }}</div>
    </div>
    <div class="tk-stat">
        <div class="tk-stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-wallet"></i></div>
        <div class="tk-stat-label">Laba Bulan Ini ({{ $bagiHasil }}%)</div>
        <div class="tk-stat-value" style="color:var(--success)">{{ formatRp($labaBulanIni) }}</div>
    </div>
    <div class="tk-stat">
        <div class="tk-stat-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-coins"></i></div>
        <div class="tk-stat-label">Total Omset</div>
        <div class="tk-stat-value" style="color:#2563eb">{{ formatRp($omsetTotal) }}</div>
    </div>
    <div class="tk-stat">
        <div class="tk-stat-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-piggy-bank"></i></div>
        <div class="tk-stat-label">Total Laba Bersih</div>
        <div class="tk-stat-value" style="color:#16a34a">{{ formatRp($labaTotal) }}</div>
    </div>
</div>

<div class="tk-grid">
    {{-- Left: Services Table --}}
    <div>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list-alt" style="color:var(--primary);margin-right:6px"></i> Servis Terbaru Saya</h3>
                <div style="font-size:.72rem;color:#94a3b8">Hanya lihat — Tidak dapat mengubah status</div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Kode</th><th>Tanggal</th><th>Perangkat</th><th>Keluhan</th><th>Status</th><th>Biaya</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($servisTerbaru as $s)
                        <tr>
                            <td><strong style="color:var(--primary)">{{ $s->kode }}</strong></td>
                            <td>{{ $s->tanggal?->format('d/m/Y') }}</td>
                            <td>{{ $s->perangkat }}</td>
                            <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $s->keluhan }}</td>
                            <td>
                                @if($s->status === 'Selesai')
                                <span class="badge badge-selesai"><i class="fas fa-check"></i> Selesai</span>
                                @elseif($s->status === 'Proses')
                                <span class="badge badge-proses"><i class="fas fa-cog fa-spin"></i> Proses</span>
                                @elseif($s->status === 'Masuk')
                                <span class="badge badge-masuk"><i class="fas fa-inbox"></i> Masuk</span>
                                @elseif($s->status === 'Pending')
                                <span class="badge badge-pending"><i class="fas fa-clock"></i> Pending</span>
                                @elseif($s->status === 'Dibatalkan')
                                <span class="badge" style="background:#f1f5f9;color:#64748b"><i class="fas fa-ban"></i> Dibatalkan</span>
                                @endif
                            </td>
                            <td style="font-weight:700">{{ formatRp($s->biaya) }}</td>
                            <td>
                                <a href="{{ route('teknisi-dashboard.show', $s->id) }}" class="btn btn-secondary btn-xs" title="Lihat Detail"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:20px">Belum ada data servis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Right: Chart & Stats --}}
    <div>
        {{-- Status Distribution --}}
        <div class="card mb-4">
            <h3 style="font-size:.92rem;margin-bottom:14px"><i class="fas fa-chart-pie" style="color:var(--primary);margin-right:6px"></i> Distribusi Status</h3>
            <div style="display:flex;flex-direction:column;gap:8px">
                @foreach([
                    ['label' => 'Masuk', 'count' => $statusCounts['Masuk'] ?? 0, 'color' => '#3b82f6', 'bg' => '#dbeafe'],
                    ['label' => 'Proses', 'count' => $statusCounts['Proses'] ?? 0, 'color' => '#f59e0b', 'bg' => '#fef3c7'],
                    ['label' => 'Pending', 'count' => $statusCounts['Pending'] ?? 0, 'color' => '#dc2626', 'bg' => '#fee2e2'],
                    ['label' => 'Selesai', 'count' => $statusCounts['Selesai'] ?? 0, 'color' => '#16a34a', 'bg' => '#dcfce7'],
                    ['label' => 'Dibatalkan', 'count' => $statusCounts['Dibatalkan'] ?? 0, 'color' => '#64748b', 'bg' => '#f1f5f9'],
                ] as $st)
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:10px;height:10px;border-radius:50%;background:{{ $st['color'] }};flex-shrink:0"></div>
                    <span style="flex:1;font-size:.82rem;font-weight:500">{{ $st['label'] }}</span>
                    <span style="font-size:.88rem;font-weight:700;color:{{ $st['color'] }}">{{ $st['count'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Monthly Income Chart --}}
        <div class="tk-chart">
            <h3 style="font-size:.92rem;margin-bottom:14px"><i class="fas fa-chart-bar" style="color:var(--success);margin-right:6px"></i> Pendapatan 6 Bulan Terakhir</h3>
            <div style="display:flex;align-items:flex-end;gap:6px;height:150px">
                @foreach($monthlyIncome as $m)
                @php $maxIncome = max(collect($monthlyIncome)->pluck('income')->max(), 1); @endphp
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px">
                    <div style="font-size:.6rem;font-weight:700;color:var(--primary)">{{ formatRp($m['profit']) }}</div>
                    <div style="width:100%;background:linear-gradient(180deg,var(--primary),var(--primary-dark));border-radius:6px 6px 0 0;height:{{ max(4, ($m['income'] / $maxIncome) * 120) }}px;min-height:4px;transition:height .3s"></div>
                    <div style="font-size:.58rem;color:#64748b;font-weight:600;text-align:center">{{ $m['month'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Summary Card --}}
        <div class="card" style="background:linear-gradient(135deg,#052e16,#064e3b);color:#fff;border:none">
            <div style="text-align:center;padding:8px">
                <div style="font-size:.72rem;opacity:.8;margin-bottom:4px">Total Servis Ditangani</div>
                <div style="font-size:2rem;font-weight:800">{{ $servisSelesai + $servisAktif }}</div>
                <div style="font-size:.68rem;opacity:.7;margin-top:4px">{{ $servisSelesai }} selesai &nbsp;|&nbsp; {{ $servisAktif }} aktif</div>
            </div>
        </div>
    </div>
</div>
@endsection
