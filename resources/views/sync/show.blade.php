@extends('layouts.app')
@section('title', 'Detail Sinkronisasi')

@section('content')
<a href="{{ route('sync.index') }}" class="btn btn-secondary btn-sm mb-4"><i class="fas fa-arrow-left"></i> Kembali ke Riwayat</a>

<div class="card" style="padding:20px">
    <div class="flex-between mb-4" style="flex-wrap:wrap;gap:10px">
        <h3 style="margin:0;font-size:1rem"><i class="fas fa-sync-alt" style="color:var(--primary)"></i> Detail Entri Sinkronisasi</h3>
        @if($sync->status === 'processed')
        <span class="badge badge-selesai"><i class="fas fa-check"></i> Berhasil</span>
        @elseif($sync->status === 'failed')
        <span class="badge badge-pending"><i class="fas fa-times"></i> Gagal</span>
        @elseif($sync->status === 'conflict')
        <span class="badge badge-urgent"><i class="fas fa-exclamation"></i> Konflik</span>
        @endif
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:20px">
        <div>
            <div class="text-xs text-muted font-bold">Client Ref</div>
            <code style="font-size:.74rem;background:#f1f5f9;padding:3px 8px;border-radius:4px;display:inline-block;margin-top:2px">{{ $sync->client_ref }}</code>
        </div>
        <div>
            <div class="text-xs text-muted font-bold">Jenis Transaksi</div>
            <div style="font-weight:600;font-size:.85rem">{{ ucfirst(str_replace('_',' ',$sync->entity_type)) }}</div>
        </div>
        <div>
            <div class="text-xs text-muted font-bold">Aksi</div>
            <div style="font-weight:600;font-size:.85rem">{{ $sync->action ?? 'create' }}</div>
        </div>
        <div>
            <div class="text-xs text-muted font-bold">User</div>
            <div style="font-weight:600;font-size:.85rem">{{ $sync->user?->name ?? '-' }}</div>
            <div class="text-xs" style="color:#94a3b8">{{ $sync->cabang?->nama ?? '-' }}</div>
        </div>
        <div>
            <div class="text-xs text-muted font-bold">Dibuat Offline (Client)</div>
            <div style="font-size:.82rem">{{ $sync->client_created_at?->format('d/m/Y H:i:s') ?? '-' }}</div>
        </div>
        <div>
            <div class="text-xs text-muted font-bold">Disinkronkan (Server)</div>
            <div style="font-size:.82rem">{{ $sync->synced_at?->format('d/m/Y H:i:s') ?? '-' }}</div>
        </div>
        <div>
            <div class="text-xs text-muted font-bold">Server ID</div>
            <div style="font-weight:600;font-size:.85rem">{{ $sync->server_id ? '#'.$sync->server_id : '(belum tersimpan)' }}</div>
        </div>
        <div>
            <div class="text-xs text-muted font-bold">Device ID</div>
            <div style="font-size:.82rem">{{ $sync->device_id ?? '-' }}</div>
        </div>
    </div>

    @if($sync->error_message)
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.82rem">
        <strong><i class="fas fa-exclamation-triangle"></i> Error:</strong> {{ $sync->error_message }}
    </div>
    @endif

    <h4 style="font-size:.88rem;margin-bottom:10px"><i class="fas fa-code"></i> Payload (data transaksi offline)</h4>
    <pre style="background:#0f172a;color:#e2e8f0;padding:16px;border-radius:10px;overflow-x:auto;font-size:.74rem;line-height:1.5">{{ json_encode($sync->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
</div>
@endsection
