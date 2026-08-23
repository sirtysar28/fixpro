@extends('layouts.app')
@section('title', 'Riwayat Pembayaran')

@section('content')
<h2 class="mb-4"><i class="fas fa-history" style="color:var(--primary)"></i> Riwayat Pembayaran Online</h2>

<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-check"></i></div>
        <div class="stat-label">Total Berhasil</div>
        <div class="stat-value" style="color:#16a34a">{{ formatRp($totalPaid) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#b45309"><i class="fas fa-clock"></i></div>
        <div class="stat-label">Total Pending</div>
        <div class="stat-value" style="color:#b45309">{{ formatRp($totalPending) }}</div>
    </div>
</div>

<div class="card mb-4">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0;flex:1;min-width:160px">
            <label class="text-xs font-bold text-muted">Cari</label>
            <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="Kode / referensi / nama..." style="padding:8px 12px;font-size:.84rem">
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Status</label>
            <select name="status" class="form-input" style="padding:8px 12px;font-size:.84rem">
                <option value="">Semua</option>
                @foreach(['pending','paid','expired','failed','refunded'] as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('payment.select') }}" class="btn btn-success btn-sm" style="background:#16a34a;color:#fff"><i class="fas fa-plus"></i> Pembayaran Baru</a>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Tgl</th><th>Pelanggan</th><th>Metode</th><th>Nominal</th><th>+ Admin</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($payments as $p)
                <tr>
                    <td><strong style="color:var(--primary)">{{ $p->kode }}</strong>@if($p->reference)<br><span style="font-size:.7rem;color:#94a3b8">{{ $p->reference }}</span>@endif</td>
                    <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $p->customer_name ?: '-' }}<br><span style="font-size:.7rem;color:#94a3b8">{{ $p->customer_phone }}</span></td>
                    <td><span style="font-size:.74rem">{{ \App\Models\Payment::methodLabel($p->method_code) }}</span></td>
                    <td>{{ formatRp($p->amount) }}</td>
                    <td style="color:#dc2626">{{ formatRp($p->fee_customer) }}</td>
                    <td style="font-weight:700">{{ formatRp($p->total_bayar) }}</td>
                    <td>
                        @php $st = ['pending'=>['#fef3c7','#b45309'],'paid'=>['#dcfce7','#16a34a'],'expired'=>['#f1f5f9','#64748b'],'failed'=>['#fee2e2','#dc2626'],'refunded'=>['#f1f5f9','#64748b']][$p->status] ?? ['#f1f5f9','#64748b']; @endphp
                        <span style="background:{{ $st[0] }};color:{{ $st[1] }};padding:2px 8px;border-radius:10px;font-size:.7rem;font-weight:700">{{ ucfirst($p->status) }}</span>
                    </td>
                    <td><a href="{{ route('payment.show', $p->kode) }}" class="btn btn-primary btn-xs"><i class="fas fa-eye"></i></a></td>
                </tr>
                @endforeach
                @if($payments->count() === 0)
                <tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:24px">Belum ada transaksi pembayaran.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    {{ $payments->links() }}
</div>
@endsection
