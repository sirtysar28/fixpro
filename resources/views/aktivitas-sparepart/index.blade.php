@extends('layouts.app')
@section('title', 'Aktivitas Sparepart')

@section('content')
<style>
    .stock-bar { height:6px;border-radius:6px;background:#f1f5f9;overflow:hidden;display:flex; }
    .stock-bar .b-masuk { background:var(--success); }
    .stock-bar .b-keluar { background:var(--danger); }
    .mov-badge { display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:10px; }
    .mov-in { background:#dcfce7;color:#166534; }
    .mov-out { background:#fee2e2;color:#991b1b; }
    .srow:hover { background:var(--primary-bg); }
</style>

<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-exchange-alt" style="color:var(--primary);margin-right:6px"></i>Aktivitas Sparepart</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('aktivitas-sparepart.riwayat') }}" class="btn btn-secondary btn-sm"><i class="fas fa-stream"></i> Riwayat Global</a>
    </div>
</div>

<p style="margin:-8px 0 16px;font-size:.82rem;color:#64748b">
    <i class="fas fa-info-circle"></i>
    Pantau kapan sparepart <strong>dibeli</strong> dan <strong>dijual</strong> dalam satu kartu stok. Klik sparepart untuk melihat detail pergerakan lengkap.
</p>

<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-label">Total Barang Masuk (Bulan Ini)</div>
        <div class="stat-value" style="color:var(--success)">{{ number_format($stats['masuk_bulan_ini']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-label">Total Barang Keluar (Bulan Ini)</div>
        <div class="stat-value" style="color:var(--danger)">{{ number_format($stats['keluar_bulan_ini']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-label">Terjual Hari Ini</div>
        <div class="stat-value" style="color:var(--primary)">{{ number_format($stats['jual_hari_ini']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-truck-loading"></i></div>
        <div class="stat-label">Dibeli Hari Ini</div>
        <div class="stat-value" style="color:#d97706">{{ number_format($stats['beli_hari_ini']) }}</div>
    </div>
</div>

<div class="card mb-4">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0;flex:1;min-width:180px">
            <label class="text-xs font-bold text-muted">Cari Sparepart</label>
            <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="Nama / kode / barcode / merk..." style="padding:8px 12px;font-size:.84rem">
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Kategori</label>
            <select name="kategori" class="form-input" style="padding:8px 12px;font-size:.84rem">
                <option value="">Semua Kategori</option>
                @foreach($kategoris as $k)
                <option value="{{ $k }}" {{ request('kategori') === $k ? 'selected' : '' }}>{{ $k }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('aktivitas-sparepart.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-redo"></i> Reset</a>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 style="font-size:.95rem"><i class="fas fa-boxes"></i> Kartu Stok per Sparepart</h3>
        <span class="text-muted text-sm">{{ $stoks->total() }} sparepart</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Nama Sparepart</th><th>Stok Sekarang</th><th>Total Masuk</th><th>Total Keluar</th><th>Profil Stok</th><th>Aktivitas Terakhir</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($stoks as $s)
                @php $totalAktivitas = ($s->total_masuk ?? 0) + ($s->total_keluar ?? 0); @endphp
                <tr class="srow">
                    <td><strong style="color:var(--primary)">{{ $s->kode }}</strong></td>
                    <td>
                        <div style="font-weight:600">{{ $s->nama }}</div>
                        <div style="font-size:.7rem;color:#94a3b8">{{ $s->kategori }}{{ $s->merk_hp ? ' • '.$s->merk_hp : '' }}</div>
                    </td>
                    <td>
                        @if($s->stok == 0)<span class="badge badge-pending">Habis</span>
                        @elseif($s->stok <= $s->min_alert)<span class="badge badge-proses">{{ $s->stok }}</span>
                        @else<span class="badge badge-selesai">{{ $s->stok }}</span>@endif
                    </td>
                    <td><span class="mov-badge mov-in"><i class="fas fa-arrow-down"></i> {{ number_format($s->total_masuk ?? 0) }}</span></td>
                    <td><span class="mov-badge mov-out"><i class="fas fa-arrow-up"></i> {{ number_format($s->total_keluar ?? 0) }}</span></td>
                    <td style="min-width:120px">
                        @if($totalAktivitas > 0)
                        @php $pctMasuk = $totalAktivitas > 0 ? round($s->total_masuk / $totalAktivitas * 100) : 0; @endphp
                        <div class="stock-bar" title="Masuk: {{ $s->total_masuk }} • Keluar: {{ $s->total_keluar }}">
                            <div class="b-masuk" style="width:{{ $pctMasuk }}%"></div>
                        </div>
                        <div style="font-size:.62rem;color:#94a3b8;margin-top:2px"> turnover {{ $s->total_keluar }} / {{ $s->total_masuk }} </div>
                        @else
                        <span style="font-size:.7rem;color:#cbd5e1">belum ada aktivitas</span>
                        @endif
                    </td>
                    <td style="font-size:.76rem;color:#64748b">
                        @if($s->terakhir)
                            {{ $s->terakhir->diffForHumans() }}<br>
                            <span style="font-size:.66rem;color:#94a3b8">{{ $s->terakhir->format('d/m/Y H:i') }}</span>
                        @else
                            <span style="color:#cbd5e1">-</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('aktivitas-sparepart.show', $s) }}" class="btn btn-primary btn-xs" title="Lihat Kartu Stok"><i class="fas fa-clipboard-list"></i> Kartu Stok</a>
                    </td>
                </tr>
                @endforeach
                @if($stoks->count() === 0)
                <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:30px">
                    <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4"></i>
                    Belum ada sparepart ditemukan.
                </td></tr>
                @endif
            </tbody>
        </table>
    </div>
    {{ $stoks->links() }}
</div>
@endsection
