@extends('layouts.app')
@section('title', 'Riwayat Sinkronisasi Offline')

@section('content')
@php $user = auth()->user(); @endphp

<div class="flex-between mb-4" style="flex-wrap:wrap;gap:10px">
    <h2 style="margin:0"><i class="fas fa-sync-alt" style="color:var(--primary);margin-right:6px"></i> Riwayat Sinkronisasi Offline</h2>
    <span class="badge" style="background:var(--primary-bg);color:var(--primary-dark);font-size:.72rem">
        <i class="fas fa-clock"></i> Sync Terakhir: {{ $stats['last_sync']?->setTimezone('Asia/Jakarta')->diffForHumans() ?? '-' }}
    </span>
</div>

<div class="alert" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.82rem">
    <i class="fas fa-info-circle"></i> Transaksi yang dibuat saat <strong>Mode Offline</strong> di aplikasi mobile akan otomatis tersinkron ke server. Setiap entri memiliki <code>client_ref</code> unik sehingga sinkronisasi ulang <strong>tidak menimbulkan duplikat</strong>.
</div>

{{-- Statistik --}}
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-database"></i></div>
        <div class="stat-label">Total Entri Sync</div>
        <div class="stat-value" style="color:var(--primary);font-size:1.3rem">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-check"></i></div>
        <div class="stat-label">Berhasil Diproses</div>
        <div class="stat-value" style="color:var(--success);font-size:1.3rem">{{ number_format($stats['processed']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-times"></i></div>
        <div class="stat-label">Gagal</div>
        <div class="stat-value" style="color:var(--danger);font-size:1.3rem">{{ number_format($stats['failed']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--accent)"><i class="fas fa-exclamation"></i></div>
        <div class="stat-label">Konflik</div>
        <div class="stat-value" style="color:var(--accent);font-size:1.3rem">{{ number_format($stats['conflict']) }}</div>
    </div>
</div>

{{-- Breakdown by entity --}}
@if(!empty($byEntity))
<div class="card" style="padding:14px 18px;margin-bottom:16px">
    <h3 style="font-size:.85rem;margin:0 0 8px"><i class="fas fa-layer-group"></i> Breakdown per Jenis Transaksi</h3>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        @foreach($byEntity as $type => $cnt)
        <span class="badge" style="background:#f1f5f9;color:#475569;font-size:.74rem;padding:5px 12px">
            <i class="fas fa-{{ $type==='servis'?'tools':($type==='kas'?'cash-register':($type==='penjualan_sparepart'?'shopping-cart':($type==='jualbeli'?'mobile-alt':'box'))) }}"></i>
            {{ ucfirst(str_replace('_',' ',$type)) }}: <strong>{{ $cnt }}</strong>
        </span>
        @endforeach
    </div>
</div>
@endif

{{-- Filter --}}
<form method="GET" class="card mb-4" style="padding:14px">
    <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:160px">
            <label class="text-xs font-bold text-muted">Cari (client_ref / nama user)</label>
            <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="client_ref / nama user...">
        </div>
        <div style="min-width:130px">
            <label class="text-xs font-bold text-muted">Status</label>
            <select name="status" class="form-input">
                <option value="">Semua</option>
                <option value="processed" {{ request('status')=='processed'?'selected':'' }}>Berhasil</option>
                <option value="failed" {{ request('status')=='failed'?'selected':'' }}>Gagal</option>
                <option value="conflict" {{ request('status')=='conflict'?'selected':'' }}>Konflik</option>
            </select>
        </div>
        <div style="min-width:150px">
            <label class="text-xs font-bold text-muted">Jenis Transaksi</label>
            <select name="entity_type" class="form-input">
                <option value="">Semua</option>
                @foreach($entityTypes as $et)
                <option value="{{ $et }}" {{ request('entity_type')==$et?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$et)) }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:130px">
            <label class="text-xs font-bold text-muted">Dari Tanggal</label>
            <input type="date" name="from" class="form-input" value="{{ request('from') }}">
        </div>
        <div style="min-width:130px">
            <label class="text-xs font-bold text-muted">Sampai</label>
            <input type="date" name="to" class="form-input" value="{{ request('to') }}">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        <a href="{{ route('sync.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-redo"></i></a>
    </div>
</form>

{{-- Tabel --}}
<div class="card">
    <div class="card-header">
        <h3 style="margin:0;font-size:.92rem"><i class="fas fa-list"></i> Riwayat Sinkronisasi</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Waktu Sync</th>
                    <th>User</th>
                    <th>Jenis</th>
                    <th>Aksi</th>
                    <th>Client Ref</th>
                    <th>Server ID</th>
                    <th>Status</th>
                    <th>Error</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $s)
                <tr>
                    <td style="white-space:nowrap;font-size:.76rem">
                        <div style="font-weight:600">{{ $s->synced_at?->format('d/m/Y') }}</div>
                        <div style="color:#94a3b8">{{ $s->synced_at?->format('H:i:s') }}</div>
                        @if($s->client_created_at && $s->synced_at)
                        <div style="font-size:.66rem;color:#94a3b8"><i class="fas fa-stopwatch"></i> delay {{ $s->client_created_at->diffForHumans($s->synced_at, true) }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:.82rem">{{ $s->user?->name ?? '-' }}</div>
                        <div style="font-size:.66rem;color:#94a3b8">{{ $s->cabang?->nama ?? '-' }}</div>
                    </td>
                    <td>
                        <span class="badge" style="background:#eff6ff;color:#1e40af;font-size:.66rem">
                            <i class="fas fa-{{ $s->entity_type==='servis'?'tools':($s->entity_type==='kas'?'cash-register':($s->entity_type==='penjualan_sparepart'?'shopping-cart':($s->entity_type==='jualbeli'?'mobile-alt':'box'))) }}"></i>
                            {{ ucfirst(str_replace('_',' ',$s->entity_type)) }}
                        </span>
                    </td>
                    <td><span class="text-xs">{{ $s->action ?? 'create' }}</span></td>
                    <td><code class="text-xs" style="font-size:.68rem;background:#f1f5f9;padding:2px 6px;border-radius:4px">{{ Str::limit($s->client_ref, 16) }}</code></td>
                    <td>{{ $s->server_id ? '#'.$s->server_id : '-' }}</td>
                    <td>
                        @if($s->status === 'processed')
                        <span class="badge badge-selesai"><i class="fas fa-check"></i> Berhasil</span>
                        @elseif($s->status === 'failed')
                        <span class="badge badge-pending"><i class="fas fa-times"></i> Gagal</span>
                        @elseif($s->status === 'conflict')
                        <span class="badge badge-urgent"><i class="fas fa-exclamation"></i> Konflik</span>
                        @else
                        <span class="badge badge-normal">{{ $s->status }}</span>
                        @endif
                    </td>
                    <td class="text-xs" style="color:#dc2626;max-width:240px">
                        @if($s->error_message)
                        <span title="{{ $s->error_message }}">{{ Str::limit($s->error_message, 40) }}</span>
                        @else <span style="color:#cbd5e1">—</span> @endif
                    </td>
                    <td>
                        <a href="{{ route('sync.show', $s) }}" class="btn btn-secondary btn-xs" title="Detail"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:30px">
                    <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4"></i>
                    Belum ada riwayat sinkronisasi.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $items->links() }}
</div>
@endsection
