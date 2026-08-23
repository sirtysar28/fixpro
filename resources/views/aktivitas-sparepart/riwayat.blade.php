@extends('layouts.app')
@section('title', 'Riwayat Aktivitas Sparepart')

@section('content')
<style>
    .mov-badge { display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:10px; }
    .mov-in { background:#dcfce7;color:#166534; }
    .mov-out { background:#fee2e2;color:#991b1b; }
    .jenis-pill { display:inline-block;font-size:.66rem;font-weight:600;padding:1px 7px;border-radius:8px;background:#f1f5f9;color:#475569; }
</style>

<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-stream" style="color:var(--primary);margin-right:6px"></i>Riwayat Aktivitas Sparepart</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('aktivitas-sparepart.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-th-large"></i> Kartu per Sparepart</a>
    </div>
</div>

<p style="margin:-8px 0 16px;font-size:.82rem;color:#64748b">
    <i class="fas fa-info-circle"></i>
    Semua pergerakan stok sparepart (pembelian, penjualan, retur, penyesuaian, transfer) dalam satu timeline gabungan.
</p>

<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-label">Masuk (Bulan Ini)</div>
        <div class="stat-value" style="color:var(--success)">{{ number_format($stats['masuk_bulan_ini']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-label">Keluar (Bulan Ini)</div>
        <div class="stat-value" style="color:var(--danger)">{{ number_format($stats['keluar_bulan_ini']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-label">Total Unit Terjual</div>
        <div class="stat-value" style="color:var(--primary)">{{ number_format($stats['total_penjualan']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-truck-loading"></i></div>
        <div class="stat-label">Total Unit Dibeli</div>
        <div class="stat-value" style="color:#d97706">{{ number_format($stats['total_pembelian']) }}</div>
    </div>
</div>

<div class="card mb-4">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0;flex:1;min-width:180px">
            <label class="text-xs font-bold text-muted">Cari</label>
            <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="Nama/kode sparepart, referensi, supplier..." style="padding:8px 12px;font-size:.84rem">
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Tipe</label>
            <select name="tipe" class="form-input" style="padding:8px 12px;font-size:.84rem">
                <option value="">Semua</option>
                <option value="masuk" {{ request('tipe') === 'masuk' ? 'selected' : '' }}>Masuk</option>
                <option value="keluar" {{ request('tipe') === 'keluar' ? 'selected' : '' }}>Keluar</option>
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Jenis</label>
            <select name="jenis" class="form-input" style="padding:8px 12px;font-size:.84rem">
                <option value="">Semua Jenis</option>
                @foreach(['pembelian'=>'Pembelian','penjualan'=>'Penjualan','pemakaian_servis'=>'Pemakaian Servis','batal_pemakaian_servis'=>'Pengembalian Sparepart Servis','retur_pembelian'=>'Retur','batal_penjualan'=>'Pembatalan Penjualan','batal_pembelian'=>'Pembatalan Pembelian','adjustment_naik'=>'Penyesuaian +','adjustment_turun'=>'Penyesuaian -','transfer_masuk'=>'Transfer Masuk','transfer_keluar'=>'Transfer Keluar','stok_awal'=>'Stok Awal','import'=>'Import','edit_stok'=>'Edit Stok'] as $j=>$lbl)
                <option value="{{ $j }}" {{ request('jenis') === $j ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Dari</label>
            <input type="date" name="dari" class="form-input" value="{{ request('dari') }}" style="padding:8px 12px;font-size:.84rem">
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Sampai</label>
            <input type="date" name="sampai" class="form-input" value="{{ request('sampai') }}" style="padding:8px 12px;font-size:.84rem">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 style="font-size:.95rem"><i class="fas fa-list"></i> Timeline Pergerakan</h3>
        <span class="text-muted text-sm">{{ $movements->total() }} catatan</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Waktu</th><th>Sparepart</th><th>Jenis</th><th>Tipe</th><th>Qty</th><th>Saldo</th><th>Harga</th><th>Referensi</th><th>Pelaku</th></tr></thead>
            <tbody>
                @foreach($movements as $m)
                @php $isMasuk = $m->tipe === 'masuk'; @endphp
                <tr>
                    <td style="font-size:.76rem;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($m->waktu)->format('d/m/Y') }}<br>
                        <span style="color:#94a3b8">{{ \Carbon\Carbon::parse($m->waktu)->format('H:i') }}</span>
                    </td>
                    <td>
                        @if($m->stok)
                        <a href="{{ route('aktivitas-sparepart.show', $m->stok) }}" style="font-weight:600;color:#0f172a">{{ $m->stok->nama }}</a>
                        <div style="font-size:.68rem;color:#94a3b8">{{ $m->stok->kode }}</div>
                        @else <span style="color:#cbd5e1">-</span> @endif
                    </td>
                    <td><span class="jenis-pill">{{ $m->labelJenis() }}</span></td>
                    <td>
                        <span class="mov-badge {{ $isMasuk ? 'mov-in' : 'mov-out' }}">
                            <i class="fas {{ $isMasuk ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                            {{ $isMasuk ? 'Masuk' : 'Keluar' }}
                        </span>
                    </td>
                    <td style="font-weight:700;color:{{ $isMasuk ? '#16a34a' : '#dc2626' }}">{{ $isMasuk ? '+' : '-' }}{{ number_format($m->qty) }}</td>
                    <td style="font-weight:600">{{ number_format($m->saldo) }}</td>
                    <td style="font-size:.78rem">{{ formatRp($m->harga_satuan) }}</td>
                    <td style="font-size:.76rem;font-family:monospace;color:var(--primary)">{{ $m->referensi ?? '-' }}</td>
                    <td style="font-size:.76rem;color:#64748b">{{ $m->pelaku_nama ?? '-' }}</td>
                </tr>
                @endforeach
                @if($movements->count() === 0)
                <tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:30px">
                    <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4"></i>
                    Belum ada pergerakan pada filter ini.
                </td></tr>
                @endif
            </tbody>
        </table>
    </div>
    {{ $movements->links() }}
</div>
@endsection
