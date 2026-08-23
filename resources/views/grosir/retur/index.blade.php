@extends('layouts.app')
@section('title', 'Retur Grosir')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">↩️ Retur Grosir</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">Pengembalian barang dari nota grosir — stok masuk kembali otomatis</p>
    </div>
    <a href="{{ route('grosir.retur.create') }}" class="btn btn-primary"><i class="fas fa-undo"></i> Retur Baru</a>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

<form method="GET" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <div style="flex:1;min-width:200px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Cari</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="No retur / no nota / pelanggan..." class="form-input">
    </div>
    <button class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
</form>

<div class="card">
    <div class="card-header"><h3>{{ $returs->total() }} Retur</h3></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No Retur</th><th>Tanggal</th><th>No Nota</th><th>Pelanggan</th>
                    <th style="text-align:right;">Total</th><th>Metode</th><th>Alasan</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($returs as $r)
                <tr>
                    <td style="font-family:monospace;font-weight:700;">{{ $r->no_retur }}</td>
                    <td>{{ $r->tanggal->format('d/m/Y H:i') }}</td>
                    <td style="font-family:monospace;">{{ $r->penjualan?->no_nota ?? '-' }}</td>
                    <td>{{ $r->nama_pelanggan ?? '-' }}</td>
                    <td style="text-align:right;font-weight:700;">{{ formatRp($r->total) }}</td>
                    <td><span class="badge badge-normal">{{ $r->metode }}</span></td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $r->alasan }}">{{ $r->alasan }}</td>
                    <td><a href="{{ route('grosir.retur.show', $r) }}" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></a></td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:28px;">Belum ada retur grosir.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $returs->links() }}
</div>
@endsection
