@extends('layouts.app')
@section('title', 'Retur Invoice')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-undo" style="color:var(--primary)"></i> Retur Invoice Sparepart</h2>
    <a href="{{ route('invoice.riwayat') }}" class="btn btn-primary btn-sm"><i class="fas fa-history"></i> Cari Invoice untuk Diretur</a>
</div>

<form method="GET" class="card mb-4">
    <div style="display:flex;gap:8px;align-items:flex-end">
        <div style="flex:1"><label class="text-xs font-bold text-muted">Cari</label>
            <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="No. retur / no. invoice..."></div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        <a href="{{ route('invoice.retur') }}" class="btn btn-secondary btn-sm"><i class="fas fa-redo"></i></a>
    </div>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Retur</th><th>Invoice</th><th>Tanggal</th><th>Item Diretur</th><th>Total Retur</th><th>Alasan</th><th>Oleh</th></tr></thead>
            <tbody>
                @forelse($returs as $r)
                <tr>
                    <td><strong style="color:var(--primary)">{{ $r->no_retur }}</strong></td>
                    <td><a href="{{ route('invoice.show', $r->invoice) }}" style="font-weight:700">{{ $r->invoice?->no_invoice }}</a></td>
                    <td>{{ $r->tanggal?->format('d/m/Y H:i') }}</td>
                    <td>
                        @foreach($r->items as $ri)
                        <div style="font-size:.76rem">{{ $ri->nama }} × {{ $ri->qty }} = {{ formatRp($ri->subtotal) }}</div>
                        @endforeach
                    </td>
                    <td style="color:#dc2626;font-weight:700">{{ formatRp($r->total) }}</td>
                    <td>{{ $r->alasan }}</td>
                    <td>{{ $r->user?->name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:24px">Belum ada retur.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $returs->links() }}
</div>
@endsection
