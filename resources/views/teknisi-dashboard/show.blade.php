@extends('layouts.app')
@section('title', 'Detail Servis - Teknisi')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-tools" style="color:var(--primary);margin-right:6px"></i> Detail Servis {{ $servis->kode }}</h2>
    <a href="{{ route('teknisi-dashboard.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

{{-- Read Only Banner --}}
<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:10px 16px;margin-bottom:16px;display:flex;align-items:center;gap:8px">
    <i class="fas fa-eye" style="color:#92400e"></i>
    <span style="font-size:.82rem;color:#92400e;font-weight:600">Mode lihat saja — Anda tidak dapat mengubah status servis ini</span>
</div>

<div class="grid-2">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-info-circle" style="color:var(--info);margin-right:6px"></i> Informasi Servis</h3>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Kode</span><strong style="color:var(--primary)">{{ $servis->kode }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Tanggal</span><strong>{{ $servis->tanggal?->format('d/m/Y') }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Status</span>
            @if($servis->status === 'Selesai')
            <span class="badge badge-selesai">Selesai</span>
            @elseif($servis->status === 'Proses')
            <span class="badge badge-proses">Proses</span>
            @elseif($servis->status === 'Masuk')
            <span class="badge badge-masuk">Masuk</span>
            @elseif($servis->status === 'Pending')
            <span class="badge badge-pending">Pending</span>
            @else
            <span class="badge" style="background:#f1f5f9;color:#64748b">{{ $servis->status }}</span>
            @endif
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Perangkat</span><strong>{{ $servis->perangkat }}</strong>
        </div>
        @if($servis->imei)
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">IMEI</span><strong>{{ $servis->imei }}</strong>
        </div>
        @endif
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Tipe</span><strong>{{ $servis->tipe }}</strong>
        </div>
        @if($servis->prioritas)
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Prioritas</span>
            <span class="badge badge-{{ $servis->prioritas === 'Urgent' ? 'urgent' : 'normal' }}">{{ $servis->prioritas }}</span>
        </div>
        @endif
    </div>
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-user" style="color:var(--accent);margin-right:6px"></i> Pelanggan & Biaya</h3>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Pelanggan</span><strong>{{ $servis->pelanggan?->nama ?? '-' }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">No HP</span><strong>{{ $servis->pelanggan?->no_hp ?? '-' }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem">
            <span class="text-muted">Cabang</span><strong>{{ $servis->cabang?->nama ?? '-' }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:12px;font-size:1rem;font-weight:800;background:var(--primary-bg);border-radius:8px;margin-top:8px">
            <span style="color:var(--primary-dark)">Biaya Servis</span>
            <span style="color:var(--primary)">{{ formatRp($servis->biaya) }}</span>
        </div>
        @if($servis->dp > 0)
        <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:.84rem">
            <span class="text-muted">DP</span><strong>{{ formatRp($servis->dp) }}</strong>
        </div>
        @endif
    </div>
</div>

{{-- Keluhan --}}
<div class="card mt-4">
    <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-clipboard-list" style="color:var(--primary);margin-right:6px"></i> Keluhan</h3>
    <div style="background:#f8fafc;border-radius:8px;padding:14px;font-size:.84rem;line-height:1.6">{{ $servis->keluhan }}</div>
</div>

{{-- Catatan --}}
@if($servis->catatan)
<div class="card" style="margin-top:12px">
    <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-sticky-note" style="color:var(--accent);margin-right:6px"></i> Catatan</h3>
    <div style="background:#f8fafc;border-radius:8px;padding:14px;font-size:.84rem;line-height:1.6">{{ $servis->catatan }}</div>
</div>
@endif

{{-- Spareparts --}}
@if($servis->spareparts && count($servis->spareparts) > 0)
<div class="card" style="margin-top:12px">
    <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-puzzle-piece" style="color:var(--info);margin-right:6px"></i> Sparepart Digunakan</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nama</th><th>Qty</th><th>Harga</th></tr></thead>
            <tbody>
                @foreach($servis->spareparts as $sp)
                <tr>
                    <td>{{ $sp['nama'] ?? '-' }}</td>
                    <td>{{ $sp['qty'] ?? 1 }}</td>
                    <td>{{ formatRp($sp['harga'] ?? 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
