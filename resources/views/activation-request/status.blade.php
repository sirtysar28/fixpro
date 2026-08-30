@extends('layouts.app')
@section('title', 'Status Aktivasi Cabang')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-shield-alt" style="color:var(--primary)"></i> Status Aktivasi Cabang</h2>
    <div style="display:flex;gap:8px">
        <a href="{{ route('admin.activation-requests.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-user-clock"></i> Request Aktivasi</a>
        <a href="{{ route('activation-code.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-ticket-alt"></i> Kode Aktivasi</a>
    </div>
</div>

<div class="stats-grid mb-4">
    @php
        $aktif = $data->where('status', 'aktif')->count();
        $nonaktif = $data->where('status', 'nonaktif')->count();
        $expired = $data->where('status', 'expired')->count();
    @endphp
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-check-circle"></i></div>
        <div class="stat-label">Cabang Aktif</div>
        <div class="stat-value" style="color:var(--success)">{{ $aktif }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f1f5f9;color:#64748b"><i class="fas fa-pause-circle"></i></div>
        <div class="stat-label">Cabang Nonaktif</div>
        <div class="stat-value" style="color:#64748b">{{ $nonaktif }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-times-circle"></i></div>
        <div class="stat-label">Cabang Expired</div>
        <div class="stat-value" style="color:var(--danger)">{{ $expired }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Daftar Status Aktivasi Seluruh Cabang</h3></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Cabang</th><th>Admin Cabang</th><th>Kode Aktivasi</th><th>Status</th><th>Paket</th><th>Jml User</th><th>Mulai</th><th>Berakhir</th><th>Sisa Hari</th><th>Admin Aktivasi</th></tr>
            </thead>
            <tbody>
                @forelse($data as $row)
                <tr>
                    <td><strong>{{ $row->cabang->nama }}</strong>
                        @if($row->cabang->tipe)<br><span style="font-size:.64rem;color:#94a3b8">({{ $row->cabang->tipe }})</span>@endif
                    </td>
                    <td>{{ $row->admin_cabang?->name ?? '-' }}</td>
                    <td>
                        @if($row->kode)
                        <code style="background:var(--primary-bg);color:var(--primary);padding:2px 8px;border-radius:6px;font-weight:700">{{ $row->kode->code }}</code>
                        @else <span style="color:#94a3b8">-</span> @endif
                    </td>
                    <td>
                        @if($row->status === 'aktif')
                        <span class="badge badge-selesai"><i class="fas fa-check-circle"></i> Aktif</span>
                        @elseif($row->status === 'expired')
                        <span class="badge badge-dibatalkan"><i class="fas fa-times-circle"></i> Expired</span>
                        @else
                        <span class="badge badge-proses"><i class="fas fa-pause-circle"></i> Nonaktif</span>
                        @endif
                    </td>
                    <td>{{ ($row->kode?->paket ?? $row->admin_cabang?->paket) ?: 'standar' }}</td>
                    <td>{{ $row->kode?->jumlah_user ?? ($row->admin_cabang ? 1 : '-') }}</td>
                    <td>{{ $row->kode?->mulai_berlaku?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $row->kode?->berakhir_berlaku?->format('d/m/Y') ?? ($row->berakhir?->format('d/m/Y') ?? 'Permanen') }}</td>
                    <td>
                        @if($row->days_left === null)
                            <span style="color:var(--success);font-weight:700">∞</span>
                        @elseif($row->days_left <= 0)
                            <span style="color:var(--danger);font-weight:700">Expired</span>
                        @elseif($row->days_left <= 30)
                            <span style="color:var(--warning);font-weight:700">{{ $row->days_left }} hari</span>
                        @else
                            {{ $row->days_left }} hari
                        @endif
                    </td>
                    <td>{{ $row->kode?->activatedBy?->name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center;color:#94a3b8;padding:24px">Belum ada cabang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
