@extends('layouts.app')
@section('title', 'Dashboard Grosir')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">📦 Dashboard Grosir</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">Ringkasan penjualan grosir toko aktif Anda (data antar toko tidak campur)</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('grosir.penjualan.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Transaksi Baru</a>
        <a href="{{ route('grosir.pesanan.create') }}" class="btn btn-secondary"><i class="fas fa-clipboard-list"></i> Pesanan Grosir</a>
    </div>
</div>

@if ($errors->any())
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
@endif

<div class="grid-3">
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">OMZET HARI INI</div>
        <div style="font-size:1.4rem;font-weight:800;color:var(--primary);margin-top:6px;">{{ formatRp($omsetHariIni) }}</div>
        <div style="font-size:.75rem;color:#64748b;margin-top:4px;">{{ $transaksiHariIni }} transaksi</div>
    </div>
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">OMZET BULAN INI</div>
        <div style="font-size:1.4rem;font-weight:800;color:#0f172a;margin-top:6px;">{{ formatRp($omsetBulanIni) }}</div>
        <div style="font-size:.75rem;color:#64748b;margin-top:4px;">Laba: {{ formatRp($labaBulanIni) }}</div>
    </div>
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">PIUTANG AKTIF</div>
        <div style="font-size:1.4rem;font-weight:800;color:{{ $jatuhTempo > 0 ? 'var(--danger)' : '#b45309' }};margin-top:6px;">{{ formatRp($totalPiutang) }}</div>
        <div style="font-size:.75rem;color:#64748b;margin-top:4px;">
            @if($jatuhTempo > 0)<i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> {{ $jatuhTempo }} nota jatuh tempo @else Aman @endif
        </div>
    </div>
</div>

<div class="grid-3" style="margin-top:16px;">
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">PESANAN AKTIF</div>
        <div style="font-size:1.4rem;font-weight:800;margin-top:6px;">{{ $pesananAktif }}</div>
        <a href="{{ route('grosir.pesanan.index') }}" style="font-size:.75rem;color:var(--primary);">Lihat pesanan →</a>
    </div>
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">PELANGGAN GROSIR</div>
        <div style="font-size:1.4rem;font-weight:800;margin-top:6px;">{{ $totalPelanggan }}</div>
        <a href="{{ route('grosir.pelanggan.index') }}" style="font-size:.75rem;color:var(--primary);">Kelola pelanggan →</a>
    </div>
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">PRODUK GROSIR</div>
        <div style="font-size:1.4rem;font-weight:800;margin-top:6px;">{{ $produkGrosir }}</div>
        <div style="font-size:.75rem;color:#64748b;margin-top:4px;">@if($stokRendah > 0)<span style="color:var(--danger);">{{ $stokRendah }} stok rendah</span>@else Stok aman @endif</div>
        <a href="{{ route('grosir.harga.index') }}" style="font-size:.75rem;color:var(--primary);">Atur harga →</a>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <div class="card-header"><h3><i class="fas fa-chart-bar"></i> Omzet 7 Hari Terakhir</h3></div>
    <div style="display:flex;align-items:flex-end;gap:10px;height:140px;padding:10px 4px 0;">
        @foreach($chart as $tgl => $nilai)
        @php $max = max(1, max($chart)); $h = max(4, (int) round($nilai / $max * 110)); @endphp
        <div style="flex:1;text-align:center;">
            <div style="font-size:.65rem;color:#64748b;margin-bottom:4px;">{{ $nilai > 0 ? number_format($nilai/1000, 0) . 'k' : '' }}</div>
            <div title="{{ formatRp($nilai) }}" style="background:linear-gradient(180deg,var(--primary),var(--primary-dark));border-radius:6px 6px 0 0;height:{{ $h }}px;opacity:{{ $nilai > 0 ? 1 : .25 }};"></div>
            <div style="font-size:.65rem;color:#94a3b8;margin-top:6px;">{{ \Carbon\Carbon::parse($tgl)->translatedFormat('D, d/m') }}</div>
        </div>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-receipt"></i> Transaksi Terakhir</h3>
        <a href="{{ route('grosir.penjualan.index') }}" class="btn btn-sm btn-secondary">Lihat Semua</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No Nota</th><th>Pelanggan</th><th>Kasir</th><th>Tanggal</th>
                    <th style="text-align:right;">Total</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($terakhir as $p)
                <tr>
                    <td style="font-family:monospace;font-weight:700;">{{ $p->no_nota }}</td>
                    <td>{{ $p->nama_pelanggan }}</td>
                    <td>{{ $p->user?->name ?? '-' }}</td>
                    <td>{{ $p->tanggal->format('d/m/Y H:i') }}</td>
                    <td style="text-align:right;font-weight:700;">{{ formatRp($p->total) }}</td>
                    <td>
                        @if($p->status === 'Lunas')<span class="badge badge-selesai">Lunas</span>
                        @elseif($p->status === 'Dibatalkan')<span class="badge badge-pending">Dibatalkan</span>
                        @else<span class="badge badge-proses">{{ $p->status }}</span>@endif
                    </td>
                    <td><a href="{{ route('grosir.penjualan.show', $p) }}" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></a></td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:28px;">Belum ada transaksi grosir. <a href="{{ route('grosir.penjualan.create') }}">Buat transaksi pertama →</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-th-large"></i> Menu Cepat Grosir</h3></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;">
        <a href="{{ route('grosir.penjualan.create') }}" class="btn btn-secondary" style="justify-content:flex-start;"><i class="fas fa-cash-register"></i> Transaksi Baru</a>
        <a href="{{ route('grosir.pesanan.index') }}" class="btn btn-secondary" style="justify-content:flex-start;"><i class="fas fa-clipboard-list"></i> Pesanan Grosir</a>
        <a href="{{ route('grosir.pelanggan.index') }}" class="btn btn-secondary" style="justify-content:flex-start;"><i class="fas fa-users"></i> Pelanggan Grosir</a>
        <a href="{{ route('grosir.harga.index') }}" class="btn btn-secondary" style="justify-content:flex-start;"><i class="fas fa-tags"></i> Harga Grosir</a>
        <a href="{{ route('grosir.stok.index') }}" class="btn btn-secondary" style="justify-content:flex-start;"><i class="fas fa-boxes"></i> Stok Grosir</a>
        <a href="{{ route('grosir.piutang.index') }}" class="btn btn-secondary" style="justify-content:flex-start;"><i class="fas fa-hand-holding-usd"></i> Piutang</a>
        <a href="{{ route('grosir.retur.index') }}" class="btn btn-secondary" style="justify-content:flex-start;"><i class="fas fa-undo"></i> Retur Grosir</a>
        <a href="{{ route('grosir.laporan.index') }}" class="btn btn-secondary" style="justify-content:flex-start;"><i class="fas fa-chart-line"></i> Laporan Grosir</a>
    </div>
</div>
@endsection
