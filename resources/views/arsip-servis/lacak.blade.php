@extends('layouts.app')
@section('title', 'Lacak Servis - ' . $servis->kode)

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-search-location" style="color:var(--primary);margin-right:6px"></i>Lacak Servis: {{ $servis->kode }}</h2>
    <div style="display:flex;gap:8px">
        @if($viewRole !== 'user')
        <a href="{{ route('arsip-servis.print', $servis->id) }}" class="btn btn-primary btn-sm" target="_blank"><i class="fas fa-print"></i> Print Thermal</a>
        @endif
        @if($viewRole === 'admin' || $viewRole === 'superadmin')
        <a href="{{ route('servis.edit', $servis) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</a>
        @endif
        <a href="{{ route('arsip-servis.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
</div>

{{-- Info badge role --}}
@if($viewRole === 'user')
<div class="alert alert-warning mb-4" style="display:flex;align-items:center;gap:8px;padding:10px 14px">
    <i class="fas fa-user" style="color:var(--primary)"></i>
    <span style="font-size:.82rem">Ini adalah servis HP milik Anda. Untuk info lebih lanjut, hubungi cabang terkait.</span>
</div>
@endif

{{-- Timeline Status --}}
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:20px"><i class="fas fa-tasks" style="color:var(--primary);margin-right:6px"></i>Progress Servis</h3>
    <div style="display:flex;justify-content:space-between;position:relative;padding:0 20px">
        <div style="position:absolute;top:20px;left:60px;right:60px;height:4px;background:#e2e8f0;border-radius:2px"></div>
        @php $statuses = ['Masuk','Proses','Pending','Selesai']; $currentIdx = array_search($servis->status, $statuses); @endphp
        @foreach($statuses as $idx => $st)
            @php $isActive = $idx <= $currentIdx; $isCurrent = $idx === $currentIdx; @endphp
            <div style="text-align:center;position:relative;z-index:1">
                <div style="width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:.85rem;font-weight:700;
                    {{ $isActive ? 'background:var(--primary);color:#fff;box-shadow:0 4px 12px rgba(13,148,136,.3)' : 'background:#f1f5f9;color:#94a3b8' }}">
                    @if($st === 'Masuk')<i class="fas fa-inbox"></i>
                    @elseif($st === 'Proses')<i class="fas fa-cog"></i>
                    @elseif($st === 'Pending')<i class="fas fa-pause"></i>
                    @else<i class="fas fa-check"></i>@endif
                </div>
                <div style="font-size:.76rem;font-weight:{{ $isCurrent ? '700' : '500' }};color:{{ $isActive ? 'var(--primary-dark)' : '#94a3b8' }}">{{ $st }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-info-circle" style="color:var(--primary);margin-right:6px"></i>Info Servis</h3>
        <table style="width:100%">
            <tr><td class="text-muted" style="padding:8px 0;width:140px">Kode Servis</td><td style="font-weight:700;color:var(--primary)">{{ $servis->kode }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Tanggal</td><td>{{ $servis->tanggal?->format('d/m/Y') }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Cabang</td><td><span class="badge badge-masuk">{{ $servis->cabang?->nama ?? '-' }}</span></td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Status</td><td><span class="badge badge-{{ strtolower($servis->status) }}">{{ $servis->status }}</span></td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Perangkat</td><td>{{ $servis->perangkat }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Tipe</td><td>{{ $servis->tipe }}</td></tr>
            @if($viewRole !== 'user')
            <tr><td class="text-muted" style="padding:8px 0">IMEI</td><td>{{ $servis->imei ?? '-' }}</td></tr>
            @endif
            <tr><td class="text-muted" style="padding:8px 0">Keluhan</td><td>{{ $servis->keluhan }}</td></tr>
        </table>
    </div>
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-user" style="color:var(--info);margin-right:6px"></i>Pelanggan & Biaya</h3>
        <table style="width:100%">
            <tr><td class="text-muted" style="padding:8px 0;width:140px">Nama</td><td>{{ $servis->pelanggan?->nama ?? '-' }}</td></tr>
            @if($viewRole !== 'user')
            <tr><td class="text-muted" style="padding:8px 0">No. HP</td><td>{{ $servis->pelanggan?->no_hp ?? '-' }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Teknisi</td><td>{{ $servis->teknisi?->nama ?? '-' }}</td></tr>
            @endif
            <tr><td class="text-muted" style="padding:8px 0;border-top:1px solid #e2e8f0;padding-top:12px">Biaya</td><td style="font-weight:700;border-top:1px solid #e2e8f0;padding-top:12px">{{ formatRp($servis->biaya) }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">DP</td><td>{{ formatRp($servis->dp) }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Sisa</td><td style="font-weight:700;color:var(--danger)">{{ formatRp($servis->biaya - $servis->dp) }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Garansi</td><td>{{ $servis->garansi }} hari (s/d {{ $servis->tanggal_garansi?->format('d/m/Y') ?? '-' }})</td></tr>
            @if($viewRole !== 'user')
            <tr><td class="text-muted" style="padding:8px 0">Diambil</td><td>{{ $servis->diambil ? '✓ Ya (' . ($servis->tgl_diambil?->format('d/m/Y') ?? '-') . ')' : 'Belum' }}</td></tr>
            @endif
        </table>
    </div>
</div>

{{-- Spareparts — hanya Admin yang bisa lihat detail harga --}}
@if($servis->spareparts && count($servis->spareparts) > 0 && $viewRole !== 'user')
<div class="card mt-4">
    <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-puzzle-piece" style="color:var(--accent);margin-right:6px"></i>Sparepart Digunakan</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nama</th><th>Harga</th></tr></thead>
            <tbody>
                @foreach($servis->spareparts as $sp)
                <tr><td>{{ $sp['nama'] ?? '-' }}</td><td>{{ formatRp($sp['harga'] ?? 0) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Foto Kondisi HP --}}
@if($servis->foto && count($servis->foto) > 0)
<div class="card mt-4">
    <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-camera" style="color:var(--info);margin-right:6px"></i>Foto Kondisi HP</h3>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
        @foreach($servis->foto as $f)
        <div style="width:150px;height:150px;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0">
            <img src="{{ Storage::url($f) }}" style="width:100%;height:100%;object-fit:cover">
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
