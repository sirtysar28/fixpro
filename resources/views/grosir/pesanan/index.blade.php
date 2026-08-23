@extends('layouts.app')
@section('title', 'Pesanan Grosir')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">📋 Pesanan Grosir</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">Kelola pesanan pelanggan grosir — reservasi stok otomatis saat diproses</p>
    </div>
    <a href="{{ route('grosir.pesanan.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Pesanan Baru</a>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

<form method="GET" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <div style="flex:1;min-width:160px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Cari</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="No pesanan / pelanggan..." class="form-input">
    </div>
    <div style="min-width:150px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Status</label>
        <select name="status" class="form-input">
            <option value="">Semua</option>
            @foreach(['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'] as $st)
            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
</form>

<div class="card">
    <div class="card-header"><h3>{{ $pesanans->total() }} Pesanan</h3></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No Pesanan</th><th>Tanggal</th><th>Pelanggan</th><th>Level</th>
                    <th style="text-align:right;">Total</th><th>Status</th><th>Aksi Lanjutan</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($pesanans as $ps)
                <tr>
                    <td style="font-family:monospace;font-weight:700;">{{ $ps->no_pesanan }}</td>
                    <td>{{ $ps->tanggal->format('d/m/Y H:i') }}</td>
                    <td>{{ $ps->nama_pelanggan }}</td>
                    <td><span class="badge badge-normal">{{ $ps->labelLevelHarga() }}</span></td>
                    <td style="text-align:right;font-weight:700;">{{ formatRp($ps->total) }}</td>
                    <td>
                        @if($ps->status === 'Menunggu')<span class="badge badge-pending">Menunggu</span>
                        @elseif($ps->status === 'Diproses')<span class="badge badge-proses">Diproses</span>
                        @elseif($ps->status === 'Selesai')<span class="badge badge-selesai">Selesai</span>
                        @else<span class="badge badge-normal">Dibatalkan</span>@endif
                    </td>
                    <td style="white-space:nowrap;">
                        @if($ps->status === 'Menunggu')
                        <form method="POST" action="{{ route('grosir.pesanan.proses', $ps) }}" style="display:inline;">
                            @csrf <button class="btn btn-sm btn-success" title="Proses & reservasi stok"><i class="fas fa-play"></i> Proses</button>
                        </form>
                        @endif
                        @if(in_array($ps->status, ['Menunggu', 'Diproses']))
                        <a href="{{ route('grosir.pesanan.checkout', $ps) }}" class="btn btn-sm btn-primary" title="Jadikan nota penjualan"><i class="fas fa-cash-register"></i> Checkout</a>
                        @endif
                        @if($ps->status === 'Selesai' && $ps->penjualan)
                        <a href="{{ route('grosir.penjualan.show', $ps->penjualan) }}" class="btn btn-sm btn-secondary"><i class="fas fa-receipt"></i> {{ $ps->penjualan->no_nota }}</a>
                        @endif
                    </td>
                    <td><a href="{{ route('grosir.pesanan.show', $ps) }}" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></a></td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:28px;">Belum ada pesanan. <a href="{{ route('grosir.pesanan.create') }}">Buat pesanan →</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $pesanans->links() }}
</div>
@endsection
