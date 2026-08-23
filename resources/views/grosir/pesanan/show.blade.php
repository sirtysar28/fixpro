@extends('layouts.app')
@section('title', 'Pesanan ' . $pesanan_grosir->no_pesanan)

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">📋 Pesanan {{ $pesanan_grosir->no_pesanan }}</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;"><a href="{{ route('grosir.pesanan.index') }}">← Daftar pesanan</a></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @if($pesanan_grosir->status === 'Menunggu')
        <form method="POST" action="{{ route('grosir.pesanan.proses', $pesanan_grosir) }}">
            @csrf <button class="btn btn-success"><i class="fas fa-play"></i> Proses (Reservasi Stok)</button>
        </form>
        @endif
        @if(in_array($pesanan_grosir->status, ['Menunggu', 'Diproses']))
        <a href="{{ route('grosir.pesanan.checkout', $pesanan_grosir) }}" class="btn btn-primary"><i class="fas fa-cash-register"></i> Checkout Jadi Nota</a>
        <form method="POST" action="{{ route('grosir.pesanan.batal', $pesanan_grosir) }}" onsubmit="return confirm('Batalkan pesanan ini?')">
            @csrf <button class="btn btn-danger"><i class="fas fa-ban"></i> Batalkan</button>
        </form>
        @endif
        @if($pesanan_grosir->status === 'Selesai' && $pesanan_grosir->penjualan)
        <a href="{{ route('grosir.penjualan.show', $pesanan_grosir->penjualan) }}" class="btn btn-secondary"><i class="fas fa-receipt"></i> Lihat Nota {{ $pesanan_grosir->penjualan->no_nota }}</a>
        @endif
    </div>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Info Pesanan</h3></div>
        <table style="font-size:.85rem;">
            <tr><td style="color:#94a3b8;width:140px;">No Pesanan</td><td style="font-weight:700;font-family:monospace;">{{ $pesanan_grosir->no_pesanan }}</td></tr>
            <tr><td style="color:#94a3b8;">Tanggal</td><td>{{ $pesanan_grosir->tanggal->format('d M Y, H:i') }}</td></tr>
            <tr><td style="color:#94a3b8;">Pelanggan</td><td style="font-weight:600;">{{ $pesanan_grosir->nama_pelanggan }}</td></tr>
            <tr><td style="color:#94a3b8;">Level Harga</td><td>{{ $pesanan_grosir->labelLevelHarga() }}</td></tr>
            <tr><td style="color:#94a3b8;">Sumber Stok</td><td>{{ $pesanan_grosir->sumberCabang?->nama ?? '-' }}</td></tr>
            <tr><td style="color:#94a3b8;">Dibuat Oleh</td><td>{{ $pesanan_grosir->user?->name ?? '-' }}</td></tr>
            <tr><td style="color:#94a3b8;">Status</td><td>
                @if($pesanan_grosir->status === 'Menunggu')<span class="badge badge-pending">Menunggu Konfirmasi</span>
                @elseif($pesanan_grosir->status === 'Diproses')<span class="badge badge-proses">Diproses (Stok Direservasi)</span>
                @elseif($pesanan_grosir->status === 'Selesai')<span class="badge badge-selesai">Selesai</span>
                @else<span class="badge badge-normal">Dibatalkan</span>@endif
            </td></tr>
            @if($pesanan_grosir->alamat_kirim)<tr><td style="color:#94a3b8;">Alamat Kirim</td><td>{{ $pesanan_grosir->alamat_kirim }}</td></tr>@endif
            @if($pesanan_grosir->catatan)<tr><td style="color:#94a3b8;">Catatan</td><td>{{ $pesanan_grosir->catatan }}</td></tr>@endif
        </table>
    </div>
    <div class="card">
        <div class="card-header"><h3>Ringkasan Nilai</h3></div>
        <table style="font-size:.9rem;min-width:250px;">
            <tr><td style="color:#64748b;">Subtotal</td><td style="text-align:right;font-weight:600;">{{ formatRp($pesanan_grosir->subtotal) }}</td></tr>
            <tr><td style="color:#64748b;">Diskon</td><td style="text-align:right;">−{{ formatRp($pesanan_grosir->diskon) }}</td></tr>
            <tr><td style="font-weight:800;border-top:2px solid #e2e8f0;">TOTAL</td><td style="text-align:right;font-weight:800;color:var(--primary);border-top:2px solid #e2e8f0;">{{ formatRp($pesanan_grosir->total) }}</td></tr>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Item Pesanan</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Produk</th><th>Kode</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Harga</th><th style="text-align:right;">Subtotal</th><th style="text-align:center;">Stok Saat Ini</th></tr></thead>
            <tbody>
                @foreach($pesanan_grosir->items as $item)
                <tr>
                    <td style="font-weight:600;">{{ $item->nama }}</td>
                    <td style="font-family:monospace;">{{ $item->kode }}</td>
                    <td style="text-align:center;"><b>{{ $item->qty }}</b></td>
                    <td style="text-align:right;">{{ formatRp($item->harga_satuan) }}</td>
                    <td style="text-align:right;font-weight:700;">{{ formatRp($item->subtotal) }}</td>
                    <td style="text-align:center;">
                        @if($item->stok)
                            <span class="badge {{ $item->stok->stok >= $item->qty ? 'badge-selesai' : 'badge-pending' }}">
                                {{ $item->stok->stok }} {{ $item->qty > $item->stok->stok ? '(kurang)' : '(cukup)' }}
                            </span>
                        @else
                            <span class="badge badge-normal">produk terhapus</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
