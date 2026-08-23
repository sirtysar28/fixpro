@extends('layouts.app')
@section('title', 'Detail Penjualan')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-receipt" style="color:var(--primary);margin-right:6px"></i> Detail Penjualan {{ $penjualan_sparepart->kode }}</h2>
    <div style="display:flex;gap:8px">
        <a href="{{ route('print.penjualan-sparepart', $penjualan_sparepart) }}" class="btn btn-primary btn-sm" target="_blank"><i class="fas fa-print"></i> Print</a>
        <a href="{{ route('penjualan-sparepart.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
</div>

@if($penjualan_sparepart->status === 'Dibatalkan')
<div class="card mb-4" style="border:1px solid #fecaca;background:#fef2f2">
    <div style="display:flex;align-items:center;gap:12px">
        <div style="width:48px;height:48px;border-radius:12px;background:#fecaca;display:flex;align-items:center;justify-content:center;font-size:1.4rem">🚫</div>
        <div>
            <div style="font-size:1rem;font-weight:700;color:#dc2626">Transaksi Dibatalkan</div>
            <div style="font-size:.82rem;color:#991b1b">Alasan: {{ $penjualan_sparepart->alasan_pembatalan }}</div>
            <div style="font-size:.72rem;color:#7f1d1d">Dibatalkan pada: {{ $penjualan_sparepart->dibatalkan_pada?->format('d/m/Y H:i') }} oleh {{ $penjualan_sparepart->dibatalkanOleh?->name ?? '-' }}</div>
        </div>
    </div>
</div>
@endif

<div class="grid-2">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-info-circle" style="color:var(--info);margin-right:6px"></i> Informasi Transaksi</h3>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Kode</span><strong style="color:var(--primary)">{{ $penjualan_sparepart->kode }}</strong>
        </div>
        @if($penjualan_sparepart->no_transaksi)
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">No. Transaksi</span><strong>{{ $penjualan_sparepart->no_transaksi }}</strong>
        </div>
        @endif
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Tanggal</span><strong>{{ $penjualan_sparepart->tanggal?->format('d/m/Y H:i') }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Cabang</span><strong>{{ $penjualan_sparepart->cabang?->nama ?? '-' }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Kasir</span><strong>{{ $penjualan_sparepart->user?->name ?? '-' }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Metode Bayar</span><strong><span class="badge badge-masuk">{{ $penjualan_sparepart->metode_bayar }}</span></strong>
        </div>
    </div>
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-box" style="color:var(--accent);margin-right:6px"></i> Detail Barang</h3>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Sparepart</span><strong>{{ $penjualan_sparepart->stok?->nama ?? '-' }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Pelanggan</span><strong>{{ $penjualan_sparepart->pelanggan?->nama ?? 'Umum' }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Qty</span><strong>{{ $penjualan_sparepart->qty }} unit</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Harga Satuan</span><strong>{{ formatRp($penjualan_sparepart->harga_satuan) }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Total</span>
            <strong>{{ formatRp($penjualan_sparepart->total) }}</strong>
        </div>
        @if($penjualan_sparepart->diskon > 0)
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Diskon</span>
            <strong style="color:#dc2626">-{{ formatRp($penjualan_sparepart->diskon) }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:12px;font-size:1rem;font-weight:800;background:#fef2f2;border-radius:8px;margin-top:4px;border:1px solid #fecaca">
            <span style="color:#991b1b">Total Bayar</span>
            <span style="color:#dc2626">{{ formatRp($penjualan_sparepart->total - $penjualan_sparepart->diskon) }}</span>
        </div>
        @else
        <div style="display:flex;justify-content:space-between;padding:12px;font-size:1rem;font-weight:800;background:var(--primary-bg);border-radius:8px;margin-top:8px">
            <span style="color:var(--primary-dark)">Total Bayar</span>
            <span style="color:var(--primary)">{{ formatRp($penjualan_sparepart->total) }}</span>
        </div>
        @endif
        @if($penjualan_sparepart->catatan)
        <div style="margin-top:8px;padding:8px;background:#f8fafc;border-radius:8px;font-size:.8rem">
            <span class="text-muted">Catatan:</span> {{ $penjualan_sparepart->catatan }}
        </div>
        @endif
    </div>
</div>

{{-- Other items in same transaction --}}
@if($siblings->count() > 0)
<div class="card mt-4">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-list" style="color:var(--info);margin-right:6px"></i> Item Lain dalam Transaksi yang Sama ({{ $penjualan_sparepart->no_transaksi }})</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Sparepart</th><th>Qty</th><th>Harga</th><th>Total</th><th>Diskon</th></tr></thead>
            <tbody>
                @foreach($siblings as $s)
                <tr style="{{ $s->status === 'Dibatalkan' ? 'opacity:.5' : '' }}">
                    <td><strong style="color:var(--primary)">{{ $s->kode }}</strong></td>
                    <td>{{ $s->stok?->nama ?? '-' }}</td>
                    <td>{{ $s->qty }}</td>
                    <td>{{ formatRp($s->harga_satuan) }}</td>
                    <td>{{ formatRp($s->total) }}</td>
                    <td style="color:#dc2626;font-weight:700">{{ ($s->diskon > 0) ? '-' . formatRp($s->diskon) : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
