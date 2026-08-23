@extends('layouts.app')
@section('title', 'Riwayat Penjualan Grosir')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">📋 Riwayat Penjualan Grosir</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">Semua nota grosir toko aktif Anda</p>
    </div>
    <a href="{{ route('grosir.penjualan.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Transaksi Baru</a>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

<div class="grid-3" style="margin-bottom:16px;">
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">OMZET BULAN INI</div>
        <div style="font-size:1.3rem;font-weight:800;color:var(--primary);margin-top:4px;">{{ formatRp($omsetBulan) }}</div>
    </div>
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">PIUTANG AKTIF</div>
        <div style="font-size:1.3rem;font-weight:800;color:#b45309;margin-top:4px;">{{ formatRp($piutangAktif) }}</div>
        <a href="{{ route('grosir.piutang.index') }}" style="font-size:.75rem;color:var(--primary);">Kelola piutang →</a>
    </div>
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">RETUR GROSIR</div>
        <div style="font-size:1.3rem;font-weight:800;margin-top:4px;">
            <a href="{{ route('grosir.retur.create') }}" class="btn btn-secondary" style="margin-top:2px;"><i class="fas fa-undo"></i> Buat Retur</a>
        </div>
    </div>
</div>

<form method="GET" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <div style="flex:2;min-width:170px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Cari</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="No nota / pelanggan..." class="form-input">
    </div>
    <div style="min-width:130px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Status</label>
        <select name="status" class="form-input">
            <option value="">Semua</option>
            @foreach(['Lunas', 'Piutang', 'Sebagian', 'Dibatalkan'] as $st)
            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>
    </div>
    <div style="min-width:130px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Dari</label>
        <input type="date" name="dari" value="{{ request('dari') }}" class="form-input">
    </div>
    <div style="min-width:130px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Sampai</label>
        <input type="date" name="sampai" value="{{ request('sampai') }}" class="form-input">
    </div>
    <button class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
</form>

<div class="card">
    <div class="card-header"><h3>{{ $penjualans->total() }} Nota</h3></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No Nota</th><th>Tanggal</th><th>Pelanggan</th><th>Level</th><th>Sumber</th>
                    <th style="text-align:right;">Total</th><th style="text-align:right;">Piutang</th>
                    <th>Status</th><th>Kasir</th><th style="width:150px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($penjualans as $p)
                <tr>
                    <td style="font-family:monospace;font-weight:700;">{{ $p->no_nota }}</td>
                    <td>{{ $p->tanggal->format('d/m/Y H:i') }}</td>
                    <td>{{ $p->nama_pelanggan }}</td>
                    <td><span class="badge badge-normal">{{ $p->labelLevelHarga() }}</span></td>
                    <td>{{ $p->sumberCabang?->nama ?? '-' }}</td>
                    <td style="text-align:right;font-weight:700;">{{ formatRp($p->total) }}</td>
                    <td style="text-align:right;color:{{ $p->status !== 'Lunas' && $p->status !== 'Dibatalkan' ? 'var(--danger)' : 'inherit' }};">
                        {{ $p->piutang > 0 || $p->status === 'Sebagian' ? formatRp($p->sisaPiutang()) : '—' }}
                    </td>
                    <td>
                        @if($p->status === 'Lunas')<span class="badge badge-selesai">Lunas</span>
                        @elseif($p->status === 'Dibatalkan')<span class="badge badge-pending">Dibatalkan</span>
                        @else<span class="badge badge-proses">{{ $p->status }}</span>@endif
                    </td>
                    <td>{{ $p->user?->name ?? '-' }}</td>
                    <td style="white-space:nowrap;">
                        <a href="{{ route('grosir.penjualan.show', $p) }}" class="btn btn-sm btn-secondary" title="Detail"><i class="fas fa-eye"></i></a>
                        @if($p->status !== 'Dibatalkan')
                        <a href="{{ route('grosir.penjualan.nota', $p) }}" target="_blank" class="btn btn-sm btn-secondary" title="Nota"><i class="fas fa-receipt"></i></a>
                        <a href="{{ route('grosir.penjualan.invoice', $p) }}" target="_blank" class="btn btn-sm btn-secondary" title="Invoice A4"><i class="fas fa-file-invoice"></i></a>
                        <a href="{{ route('grosir.penjualan.surat-jalan', $p) }}" target="_blank" class="btn btn-sm btn-secondary" title="Surat Jalan"><i class="fas fa-truck"></i></a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center;color:#94a3b8;padding:28px;">Belum ada transaksi grosir. <a href="{{ route('grosir.penjualan.create') }}">Buat transaksi →</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $penjualans->links() }}
</div>
@endsection
