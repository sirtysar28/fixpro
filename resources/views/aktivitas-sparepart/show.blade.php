@extends('layouts.app')
@section('title', 'Kartu Stok - ' . $stok->nama)

@section('content')
<style>
    .ks-item { padding:12px 14px;border-bottom:1px solid #f1f5f9;display:flex;gap:12px;align-items:flex-start; }
    .ks-item:last-child { border-bottom:none; }
    .ks-icon { width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0; }
    .ks-in { background:#dcfce7;color:#16a34a; }
    .ks-out { background:#fee2e2;color:#dc2626; }
    .ks-info { flex:1;min-width:0; }
    .ks-top { display:flex;justify-content:space-between;align-items:flex-start;gap:8px;flex-wrap:wrap; }
    .ks-referensi { font-weight:700;font-size:.86rem;color:#0f172a; }
    .ks-referensi .ref-code { color:var(--primary);font-family:monospace; }
    .ks-sub { font-size:.74rem;color:#94a3b8;margin-top:2px; }
    .ks-qty { font-weight:800;font-size:.92rem;text-align:right;white-space:nowrap; }
    .ks-saldo { font-size:.7rem;color:#64748b;text-align:right;margin-top:2px; }
    .ks-card-head {
        background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;
        border-radius:14px 14px 0 0;padding:20px 24px;
    }
</style>

<div style="margin-bottom:16px">
    <a href="{{ route('aktivitas-sparepart.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

{{-- Header Kartu Stok --}}
<div class="card" style="overflow:hidden;margin-bottom:16px;padding:0">
    <div class="ks-card-head">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
            <div>
                <div style="font-size:.78rem;opacity:.85;text-transform:uppercase;letter-spacing:.05em"><i class="fas fa-clipboard-list"></i> Kartu Stok</div>
                <h2 style="margin:4px 0 0;font-size:1.3rem;font-weight:800">{{ $stok->nama }}</h2>
                <div style="font-size:.8rem;opacity:.9;margin-top:4px">
                    <span style="font-family:monospace;background:rgba(255,255,255,.18);padding:2px 8px;border-radius:6px">{{ $stok->kode }}</span>
                    {{ $stok->kategori }}{{ $stok->merk_hp ? ' • '.$stok->merk_hp : '' }}
                    @if($stok->barcode){{ ' • '.$stok->barcode }}@endif
                </div>
            </div>
            <div style="text-align:right">
                <div style="font-size:.72rem;opacity:.85">Stok Saat Ini</div>
                <div style="font-size:2rem;font-weight:800;line-height:1">{{ $stok->stok }}</div>
                <div style="font-size:.74rem;opacity:.9">{{ $stok->satuan ?? 'pcs' }}</div>
            </div>
        </div>
    </div>
    <div style="padding:16px 24px;display:flex;gap:12px;flex-wrap:wrap">
        <a href="{{ route('aktivitas-sparepart.export', $stok) }}" class="btn btn-success btn-sm" style="background:#16a34a;color:#fff"><i class="fas fa-file-excel"></i> Export Excel</a>
        @if($stok->stok <= $stok->min_alert)
        <a href="{{ route('pembelian.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-truck-loading"></i> Beli Lagi</a>
        @endif
    </div>
</div>

{{-- Ringkasan --}}
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-label">Total Masuk</div>
        <div class="stat-value" style="color:var(--success)">{{ number_format($totalMasuk) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-label">Total Keluar</div>
        <div class="stat-value" style="color:var(--danger)">{{ number_format($totalKeluar) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-label">Nilai Pembelian</div>
        <div class="stat-value" style="color:#d97706;font-size:1.1rem">{{ formatRp($nilaiBeli) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-cash-register"></i></div>
        <div class="stat-label">Nilai Penjualan</div>
        <div class="stat-value" style="color:var(--primary);font-size:1.1rem">{{ formatRp($nilaiJual) }}</div>
    </div>
</div>

{{-- Filter --}}
<div class="card mb-4">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Dari Tanggal</label>
            <input type="date" name="dari" class="form-input" value="{{ request('dari') }}" style="padding:8px 12px;font-size:.84rem">
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Sampai Tanggal</label>
            <input type="date" name="sampai" class="form-input" value="{{ request('sampai') }}" style="padding:8px 12px;font-size:.84rem">
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
        @if(auth()->user()->isSuperAdmin())
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Cabang</label>
            <select name="cabang" class="form-input" style="padding:8px 12px;font-size:.84rem">
                <option value="">Semua Cabang</option>
                @foreach($cabangs as $c)
                <option value="{{ $c->id }}" {{ request('cabang') == $c->id ? 'selected' : '' }}>{{ $c->nama }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('aktivitas-sparepart.show', $stok) }}" class="btn btn-secondary btn-sm"><i class="fas fa-redo"></i> Reset</a>
    </form>
</div>

{{-- Timeline pergerakan --}}
<div class="card">
    <div class="card-header">
        <h3 style="font-size:.95rem"><i class="fas fa-history"></i> Riwayat Pergerakan</h3>
        <span class="text-muted text-sm">{{ $movements->total() }} catatan</span>
    </div>

    @if($movements->count() > 0)
    <div style="padding:6px 0">
        @foreach($movements as $m)
        @php $isMasuk = $m->tipe === 'masuk'; @endphp
        <div class="ks-item">
            <div class="ks-icon {{ $isMasuk ? 'ks-in' : 'ks-out' }}">
                <i class="fas {{ $isMasuk ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
            </div>
            <div class="ks-info">
                <div class="ks-top">
                    <div>
                        <div class="ks-referensi">{{ $m->labelJenis() }} @if($m->referensi)<span class="ref-code">{{ $m->referensi }}</span>@endif</div>
                        <div class="ks-sub">
                            {{ \Carbon\Carbon::parse($m->waktu)->format('d M Y, H:i') }}
                            @if($m->pelaku_nama) • <i class="fas fa-user"></i> {{ $m->pelaku_nama }} @endif
                            @if($m->metode) • <span class="badge badge-normal">{{ $m->metode }}</span> @endif
                            @if($m->cabang) • <i class="fas fa-store"></i> {{ $m->cabang->nama }} @endif
                        </div>
                        @if($m->catatan)<div class="ks-sub" style="color:#64748b;font-style:italic">{{ $m->catatan }}</div>@endif
                    </div>
                    <div>
                        <div class="ks-qty" style="color:{{ $isMasuk ? '#16a34a' : '#dc2626' }}">
                            {{ $isMasuk ? '+' : '-' }}{{ number_format($m->qty) }}
                        </div>
                        <div class="ks-saldo">Saldo: <strong>{{ number_format($m->saldo) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    {{ $movements->links() }}
    @else
    <div style="text-align:center;padding:40px 20px;color:#94a3b8">
        <i class="fas fa-inbox" style="font-size:2.5rem;opacity:.4;margin-bottom:10px"></i>
        <div style="font-weight:600">Belum ada pergerakan</div>
        <div style="font-size:.82rem;margin-top:4px">Belum ada catatan pembelian atau penjualan untuk sparepart ini pada filter yang dipilih.</div>
    </div>
    @endif
</div>
@endsection
