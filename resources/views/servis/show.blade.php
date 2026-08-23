@extends('layouts.app')
@section('title', 'Detail Servis')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem">Detail Servis</h2>
    <div style="display:flex;gap:8px">
        <a href="{{ route('print.servis', $servis) }}" class="btn btn-primary btn-sm" target="_blank"><i class="fas fa-print"></i> Print Thermal</a>
        <a href="{{ route('servis.edit', $servis) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
        <a href="{{ route('servis.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-info-circle" style="color:var(--primary);margin-right:6px"></i>Informasi Servis</h3>
        <table style="width:100%">
            <tr><td class="text-muted" style="padding:8px 0;width:140px">Kode Servis</td><td style="font-weight:700;color:var(--primary)">{{ $servis->kode }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Tanggal</td><td>{{ $servis->tanggal?->format('d/m/Y') }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Cabang</td><td><span class="badge badge-masuk">{{ $servis->cabang?->nama ?? '-' }}</span></td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Sumber</td><td>@if($servis->sumber === 'user')<span style="color:#2563eb;font-size:.84rem"><i class="fas fa-mobile-alt"></i> Input User</span>@else<span style="color:#64748b;font-size:.84rem"><i class="fas fa-desktop"></i> Input Admin</span>@endif</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Status</td><td><span class="badge badge-{{ strtolower($servis->status) }}">{{ $servis->status }}</span></td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Prioritas</td><td><span class="badge badge-{{ strtolower($servis->prioritas) }}">{{ $servis->prioritas }}</span></td></tr>
        </table>
    </div>
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-user" style="color:var(--info);margin-right:6px"></i>Pelanggan</h3>
        <table style="width:100%">
            <tr><td class="text-muted" style="padding:8px 0;width:140px">Nama</td><td>{{ $servis->pelanggan?->nama ?? '-' }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">No. HP</td><td>{{ $servis->pelanggan?->no_hp ?? '-' }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Alamat</td><td>{{ $servis->pelanggan?->alamat ?? '-' }}</td></tr>
        </table>
    </div>
</div>

<div class="grid-2 mt-4">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-mobile-alt" style="color:var(--accent);margin-right:6px"></i>Perangkat</h3>
        <table style="width:100%">
            <tr><td class="text-muted" style="padding:8px 0;width:140px">Perangkat</td><td>{{ $servis->perangkat }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Tipe</td><td>{{ $servis->tipe }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">IMEI</td><td>{{ $servis->imei ?? '-' }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Keluhan</td><td>{{ $servis->keluhan }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Teknisi</td><td>{{ $servis->teknisi?->nama ?? '-' }}</td></tr>
        </table>
    </div>
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-money-bill" style="color:var(--success);margin-right:6px"></i>Biaya</h3>
        <table style="width:100%">
            <tr><td class="text-muted" style="padding:8px 0;width:140px">Total Biaya Servis</td><td style="font-weight:700">{{ formatRp($servis->biaya) }}</td></tr>
            @php
                $totalSpShow = 0;
                if ($servis->spareparts && count($servis->spareparts) > 0) {
                    foreach ($servis->spareparts as $sp) {
                        $totalSpShow += (float) ($sp['harga'] ?? 0) * (int) ($sp['qty'] ?? 1);
                    }
                }
                // biaya = harga KESELURUHAN (sudah termasuk sparepart). Sparepart TIDAK ditambah lagi.
                $sisaShow = max(0, (float) $servis->biaya - (float) $servis->dp);
            @endphp
            @if($totalSpShow > 0)
            <tr><td class="text-muted" style="padding:8px 0;font-size:.78rem">&nbsp;&nbsp;↳ termasuk sparepart</td><td style="font-size:.78rem;color:#7c3aed">{{ formatRp($totalSpShow) }}</td></tr>
            @endif
            <tr><td class="text-muted" style="padding:8px 0">DP</td><td>{{ formatRp($servis->dp) }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Sisa Bayar</td><td style="font-weight:700;color:var(--danger)">{{ formatRp($sisaShow) }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Garansi</td><td>{{ $servis->garansi }} hari</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">s/d Garansi</td><td>{{ $servis->tanggal_garansi?->format('d/m/Y') ?? '-' }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Diambil</td><td>{{ $servis->diambil ? '✓ Ya' : 'Belum' }}</td></tr>
        </table>
    </div>
</div>

{{-- Sparepart Digunakan --}}
@if($servis->spareparts && count($servis->spareparts) > 0)
<div class="card mt-4">
    <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-puzzle-piece" style="color:var(--accent);margin-right:6px"></i>Sparepart Digunakan</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nama</th><th>Kode</th><th>Harga</th></tr></thead>
            <tbody>
                @foreach($servis->spareparts as $sp)
                <tr>
                    <td>{{ $sp['nama'] ?? '-' }}</td>
                    <td>{{ $sp['kode'] ?? '-' }}</td>
                    <td>{{ formatRp($sp['harga'] ?? 0) }}</td>
                </tr>
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
        <div style="width:150px;height:150px;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;cursor:pointer" onclick="window.open('{{ Storage::url($f) }}', '_blank')">
            <img src="{{ Storage::url($f) }}" style="width:100%;height:100%;object-fit:cover">
        </div>
        @endforeach
    </div>
</div>
@endif

@if($servis->catatan)
<div class="card mt-4">
    <h3 style="font-size:.95rem;margin-bottom:8px">Catatan</h3>
    <p>{{ $servis->catatan }}</p>
</div>
@endif

{{-- Riwayat Servis HP yang sudah selesai (berdasarkan IMEI atau pelanggan) --}}
@php
    $riwayat = null;
    if ($servis->pelanggan_id) {
        $riwayat = \App\Models\Servis::where('pelanggan_id', $servis->pelanggan_id)
            ->where('id', '!=', $servis->id)
            ->with(['teknisi', 'cabang'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }
@endphp
@if($riwayat && $riwayat->count() > 0)
<div class="card mt-4">
    <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-history" style="color:var(--primary);margin-right:6px"></i>Riwayat Servis HP Pelanggan Ini</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Tanggal</th><th>Perangkat</th><th>Keluhan</th><th>Status</th><th>Biaya</th></tr></thead>
            <tbody>
                @foreach($riwayat as $r)
                <tr>
                    <td><a href="{{ route('servis.show', $r) }}" style="color:var(--primary);font-weight:700">{{ $r->kode }}</a></td>
                    <td>{{ $r->tanggal?->format('d/m/Y') }}</td>
                    <td>{{ $r->perangkat }}</td>
                    <td>{{ $r->keluhan }}</td>
                    <td><span class="badge badge-{{ strtolower($r->status) }}">{{ $r->status }}</span></td>
                    <td>{{ formatRp($r->biaya) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
