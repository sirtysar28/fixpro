@extends('layouts.app')
@section('title', 'Detail Servis')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0">Detail Servis - {{ $servis->kode }}</h2>
    <div style="display:flex;gap:8px">
        @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
        <form method="POST" action="{{ route('my-service.destroy', $servis) }}" onsubmit="return confirm('PERINGATAN! Anda akan menghapus servis {{ $servis->kode }} — {{ $servis->perangkat }}.\n\nStok sparepart akan dikembalikan & DP dikoreksi ke Kas.\n\nTindakan ini tidak dapat dibatalkan. Lanjutkan?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus Servis</button>
        </form>
        @endif
        <a href="{{ route('my-service.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-info-circle" style="color:var(--primary);margin-right:6px"></i>Informasi Servis</h3>
        <table style="width:100%">
            <tr><td class="text-muted" style="padding:8px 0;width:140px">Kode Servis</td><td style="font-weight:700;color:var(--primary)">{{ $servis->kode }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Tanggal</td><td>{{ $servis->tanggal?->format('d/m/Y') }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Cabang</td><td><span class="badge badge-masuk">{{ $servis->cabang?->nama ?? '-' }}</span></td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Status</td><td><span class="badge badge-{{ strtolower($servis->status) }}">{{ $servis->status }}</span></td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Estimasi</td><td>{{ $servis->eta?->format('d/m/Y H:i') ?? 'Menunggu' }}</td></tr>
        </table>
    </div>
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-mobile-alt" style="color:var(--accent);margin-right:6px"></i>Perangkat</h3>
        <table style="width:100%">
            <tr><td class="text-muted" style="padding:8px 0;width:140px">Perangkat</td><td>{{ $servis->perangkat }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Tipe</td><td>{{ $servis->tipe }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">IMEI</td><td>{{ $servis->imei ?? '-' }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Keluhan</td><td>{{ $servis->keluhan }}</td></tr>
            <tr><td class="text-muted" style="padding:8px 0">Teknisi</td><td>{{ $servis->teknisi?->nama ?? 'Belum ditugaskan' }}</td></tr>
        </table>
    </div>
</div>

@if($servis->catatan)
<div class="card mt-4">
    <h3 style="font-size:.95rem;margin-bottom:8px">Catatan</h3>
    <p>{{ $servis->catatan }}</p>
</div>
@endif

{{-- Admin/Staff: Update Status Panel --}}
@if(auth()->user()->isAdmin() || auth()->user()->isStaff())
<div class="card mt-4" style="border:2px solid var(--primary-bg)">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-exchange-alt" style="color:var(--primary);margin-right:6px"></i>Ubah Status Servis</h3>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <form method="POST" action="{{ route('my-service.update-status', $servis) }}" style="display:inline" onsubmit="return confirm('Ubah status menjadi Masuk?')">
            @csrf
            <input type="hidden" name="status" value="Masuk">
            <button type="submit" class="btn btn-sm {{ $servis->status === 'Masuk' ? 'btn-primary' : 'btn-secondary' }}" {{ $servis->status === 'Masuk' ? 'disabled' : '' }}><i class="fas fa-inbox"></i> Masuk</button>
        </form>
        <form method="POST" action="{{ route('my-service.update-status', $servis) }}" style="display:inline" onsubmit="return confirm('Ubah status menjadi Proses?')">
            @csrf
            <input type="hidden" name="status" value="Proses">
            <button type="submit" class="btn btn-sm {{ $servis->status === 'Proses' ? 'btn-primary' : 'btn-secondary' }}" {{ $servis->status === 'Proses' ? 'disabled' : '' }}><i class="fas fa-cog"></i> Proses</button>
        </form>
        <form method="POST" action="{{ route('my-service.update-status', $servis) }}" style="display:inline" onsubmit="return confirm('Ubah status menjadi Pending?')">
            @csrf
            <input type="hidden" name="status" value="Pending">
            <button type="submit" class="btn btn-sm {{ $servis->status === 'Pending' ? 'btn-primary' : 'btn-secondary' }}" {{ $servis->status === 'Pending' ? 'disabled' : '' }}><i class="fas fa-clock"></i> Pending</button>
        </form>
        <form method="POST" action="{{ route('my-service.update-status', $servis) }}" style="display:inline" onsubmit="return confirm('Ubah status menjadi Selesai?')">
            @csrf
            <input type="hidden" name="status" value="Selesai">
            <button type="submit" class="btn btn-sm {{ $servis->status === 'Selesai' ? 'btn-primary' : 'btn-success' }}" {{ $servis->status === 'Selesai' ? 'disabled' : '' }}><i class="fas fa-check-circle"></i> Selesai</button>
        </form>
        @if($servis->status !== 'Dibatalkan')
        <form method="POST" action="{{ route('my-service.update-status', $servis) }}" style="display:inline" onsubmit="return confirm('PERINGATAN: Ubah status menjadi Dibatalkan? Tindakan ini tidak dapat dibatalkan.')">
            @csrf
            <input type="hidden" name="status" value="Dibatalkan">
            <button type="submit" class="btn btn-sm btn-danger" {{ $servis->status === 'Dibatalkan' ? 'disabled' : '' }}><i class="fas fa-times-circle"></i> Batal</button>
        </form>
        @endif
    </div>
    <div style="font-size:.72rem;color:#94a3b8;margin-top:8px">Status saat ini: <strong>{{ $servis->status }}</strong></div>
</div>
@endif

<!-- Status Timeline -->
<div class="card mt-4">
    <h3 style="font-size:.95rem;margin-bottom:16px">Timeline Status</h3>
    <div style="display:flex;gap:0;align-items:center">
        @php
            $statuses = ['Masuk', 'Proses', 'Pending', 'Selesai'];
            $currentIdx = array_search($servis->status, $statuses);
        @endphp
        @foreach($statuses as $idx => $st)
            <div style="flex:1;text-align:center;position:relative">
                @if($idx <= $currentIdx)
                <div style="width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.9rem;background:var(--primary);color:#fff"><i class="fas fa-check"></i></div>
                @else
                <div style="width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;background:#e2e8f0;color:#94a3b8">{{ $idx + 1 }}</div>
                @endif
                <div style="font-size:.72rem;margin-top:6px;font-weight:600;color:{{ $idx <= $currentIdx ? 'var(--primary)' : '#94a3b8' }}">{{ $st }}</div>
                @if($idx < count($statuses) - 1)
                <div style="position:absolute;top:18px;left:calc(50% + 22px);width:calc(100% - 44px);height:3px;border-radius:2px;background:{{ $idx < $currentIdx ? 'var(--primary)' : '#e2e8f0' }}"></div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
