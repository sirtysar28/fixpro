@extends('layouts.app')
@section('title', 'Piutang Invoice Sparepart')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-hand-holding-usd" style="color:var(--warning)"></i> Piutang Invoice Sparepart</h2>
    <div style="display:flex;gap:8px">
        <a href="{{ route('invoice.pembayaran') }}" class="btn btn-secondary btn-sm"><i class="fas fa-money-bill-wave"></i> Riwayat Pembayaran</a>
        <a href="{{ route('invoice.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Invoice Baru</a>
    </div>
</div>

<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="stat-label">Total Piutang</div>
        <div class="stat-value" style="color:var(--warning)">{{ formatRp($totalPiutang) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-alarm-clock"></i></div>
        <div class="stat-label">Invoice Jatuh Tempo</div>
        <div class="stat-value" style="color:var(--danger)">{{ $jumlahJatuhTempo }}</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px" class="mb-4">
    {{-- ===== DAFTAR PIUTANG ===== --}}
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
            <h3>Daftar Piutang</h3>
            <form method="GET" style="display:flex;gap:6px;align-items:center">
                <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="No. invoice / pelanggan..." style="padding:6px 10px;font-size:.78rem;width:180px">
                <select name="jatuh_tempo" class="form-input" style="padding:6px 10px;font-size:.78rem">
                    <option value="">Semua</option>
                    <option value="lewat" {{ request('jatuh_tempo') == 'lewat' ? 'selected' : '' }}>Sudah Lewat Tempo</option>
                </select>
                <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>No. Invoice</th><th>Tanggal</th><th>Pelanggan</th><th>Total</th><th>Dibayar</th><th>Sisa</th><th>Jatuh Tempo</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($piutangs as $inv)
                    <tr style="{{ $inv->isJatuhTempo() ? 'background:#fef2f2' : '' }}">
                        <td><a href="{{ route('invoice.show', $inv) }}" style="color:var(--primary);font-weight:700">{{ $inv->no_invoice }}</a></td>
                        <td>{{ $inv->tanggal?->format('d/m/Y') }}</td>
                        <td>{{ $inv->nama_pelanggan ?? 'Umum' }}</td>
                        <td>{{ formatRp($inv->total) }}</td>
                        <td>{{ formatRp($inv->dibayar) }}</td>
                        <td style="color:#dc2626;font-weight:700">{{ formatRp($inv->sisa) }}</td>
                        <td>
                            {{ $inv->jatuh_tempo?->format('d/m/Y') ?? '-' }}
                            @if($inv->isJatuhTempo())
                            <br><span style="font-size:.6rem;color:#dc2626;font-weight:800">
                                {{ $inv->jatuh_tempo->diffInDays(now()) }} hari lewat!
                            </span>
                            @else
                                <br><span style="font-size:.6rem;color:#64748b">{{ now()->diffInDays($inv->jatuh_tempo) }} hari lagi</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $inv->badgeStatus() }}">{{ $inv->status }}</span></td>
                        <td><a href="{{ route('invoice.show', $inv) }}" class="btn btn-primary btn-xs" title="Bayar / Detail"><i class="fas fa-money-bill-wave"></i></a></td>
                    </tr>
                    @empty
                    <tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:24px">Tidak ada piutang. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $piutangs->links() }}
    </div>

    {{-- ===== LIMIT PIUTANG PELANGGAN ===== --}}
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-pie"></i> Limit Piutang Pelanggan</h3></div>
        <div style="padding:12px 16px">
            @forelse($pelangganLimits as $p)
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:4px">
                    <b>{{ $p->nama }}</b>
                    <span style="color:{{ $p->persen_pakai >= 100 ? '#dc2626' : ($p->persen_pakai >= 80 ? '#d97706' : 'var(--success)') }};font-weight:700">
                        {{ number_format($p->persen_pakai, 0) }}%
                    </span>
                </div>
                <div style="background:#f1f5f9;border-radius:8px;height:8px;overflow:hidden">
                    <div style="height:100%;border-radius:8px;background:{{ $p->persen_pakai >= 100 ? '#dc2626' : ($p->persen_pakai >= 80 ? '#d97706' : '#16a34a') }};width:{{ min(100, $p->persen_pakai) }}%"></div>
                </div>
                <div style="font-size:.66rem;color:#94a3b8;margin-top:3px">
                    {{ formatRp($p->piutang_berjalan) }} / {{ formatRp($p->limit_piutang) }}
                </div>
            </div>
            @empty
            <div style="color:#94a3b8;font-size:.8rem;text-align:center;padding:16px">Belum ada pelanggan dengan limit piutang.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
