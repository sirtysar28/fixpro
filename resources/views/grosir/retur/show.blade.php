@extends('layouts.app')
@section('title', 'Retur ' . $retur_grosir->no_retur)

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <h1 style="font-size:1.5rem;margin:0;">↩️ Retur {{ $retur_grosir->no_retur }}</h1>
    <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">
        <a href="{{ route('grosir.retur.index') }}">← Daftar retur</a>
        @if($retur_grosir->penjualan) · <a href="{{ route('grosir.penjualan.show', $retur_grosir->penjualan) }}">Lihat nota {{ $retur_grosir->penjualan->no_nota }}</a>@endif
    </p>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Info Retur</h3></div>
        <table style="font-size:.85rem;">
            <tr><td style="color:#94a3b8;width:130px;">No Retur</td><td style="font-weight:700;font-family:monospace;">{{ $retur_grosir->no_retur }}</td></tr>
            <tr><td style="color:#94a3b8;">Tanggal</td><td>{{ $retur_grosir->tanggal->format('d M Y, H:i') }}</td></tr>
            <tr><td style="color:#94a3b8;">Nota Asal</td><td style="font-family:monospace;">{{ $retur_grosir->penjualan?->no_nota ?? '-' }}</td></tr>
            <tr><td style="color:#94a3b8;">Pelanggan</td><td style="font-weight:600;">{{ $retur_grosir->nama_pelanggan ?? '-' }}</td></tr>
            <tr><td style="color:#94a3b8;">Metode</td><td><span class="badge badge-normal">{{ $retur_grosir->metode }}</span></td></tr>
            <tr><td style="color:#94a3b8;">Alasan</td><td>{{ $retur_grosir->alasan }}</td></tr>
            <tr><td style="color:#94a3b8;">Petugas</td><td>{{ $retur_grosir->user?->name ?? '-' }}</td></tr>
        </table>
    </div>
    <div class="card">
        <div class="card-header"><h3>Nilai Retur</h3></div>
        <div style="font-size:2rem;font-weight:800;color:var(--danger);">{{ formatRp($retur_grosir->total) }}</div>
        <p style="font-size:.8rem;color:#64748b;margin-top:6px;">
            @if($retur_grosir->metode === 'Uang Kembali') Uang dikembalikan ke pelanggan (kas keluar).
            @elseif($retur_grosir->metode === 'Tukar Barang') Barang masuk kembali ke stok.
            @else Retur memotong sisa piutang nota.
            @endif
        </p>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Barang yang Diretur</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Produk</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Harga Satuan</th><th style="text-align:right;">Subtotal</th></tr></thead>
            <tbody>
                @foreach($retur_grosir->items as $item)
                <tr>
                    <td style="font-weight:600;">{{ $item->nama }}</td>
                    <td style="text-align:center;"><b>{{ $item->qty }}</b></td>
                    <td style="text-align:right;">{{ formatRp($item->harga_satuan) }}</td>
                    <td style="text-align:right;font-weight:700;">{{ formatRp($item->subtotal) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
