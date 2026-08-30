@extends('layouts.app')
@section('title', 'Riwayat Invoice Sparepart')

@section('content')
<div class="inv-tabs" style="display:flex;gap:0;margin-bottom:16px;background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden">
    <a href="{{ route('invoice.create') }}" class="inv-tab" style="flex:1;padding:11px 14px;font-size:.84rem;font-weight:600;color:#64748b;text-align:center;text-decoration:none;border-bottom:3px solid transparent"><i class="fas fa-file-invoice"></i> Invoice Sparepart</a>
    <div class="inv-tab active" style="flex:1;padding:11px 14px;font-size:.84rem;font-weight:600;color:var(--primary);text-align:center;border-bottom:3px solid var(--primary);background:var(--primary-bg)"><i class="fas fa-history"></i> Riwayat Invoice</div>
    <a href="{{ route('invoice.piutang') }}" class="inv-tab" style="flex:1;padding:11px 14px;font-size:.84rem;font-weight:600;color:#64748b;text-align:center;text-decoration:none;border-bottom:3px solid transparent"><i class="fas fa-hand-holding-usd"></i> Piutang</a>
    <a href="{{ route('invoice.retur') }}" class="inv-tab" style="flex:1;padding:11px 14px;font-size:.84rem;font-weight:600;color:#64748b;text-align:center;text-decoration:none;border-bottom:3px solid transparent"><i class="fas fa-undo"></i> Retur</a>
</div>

{{-- ===== DASHBOARD PENJUALAN ===== --}}
<div class="stats-grid mb-4" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-cash-register"></i></div>
        <div class="stat-label">Penjualan Hari Ini</div>
        <div class="stat-value" style="color:var(--primary);font-size:1.05rem">{{ formatRp($stats['penjualan_hari_ini']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:var(--info)"><i class="fas fa-file-invoice"></i></div>
        <div class="stat-label">Total Invoice</div>
        <div class="stat-value" style="color:var(--info);font-size:1.05rem">{{ number_format($stats['total_invoice']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f5f3ff;color:#7c3aed"><i class="fas fa-store"></i></div>
        <div class="stat-label">Penjualan Retail</div>
        <div class="stat-value" style="color:#7c3aed;font-size:1.05rem">{{ formatRp($stats['retail']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#ecfdf5;color:#059669"><i class="fas fa-boxes"></i></div>
        <div class="stat-label">Penjualan Grosir</div>
        <div class="stat-value" style="color:#059669;font-size:1.05rem">{{ formatRp($stats['grosir']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff7ed;color:#ea580c"><i class="fas fa-people-arrows"></i></div>
        <div class="stat-label">Penjualan Reseller</div>
        <div class="stat-value" style="color:#ea580c;font-size:1.05rem">{{ formatRp($stats['reseller']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fdf2f8;color:#db2777"><i class="fas fa-user-check"></i></div>
        <div class="stat-label">Penjualan Member</div>
        <div class="stat-value" style="color:#db2777;font-size:1.05rem">{{ formatRp($stats['member']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="stat-label">Total Piutang</div>
        <div class="stat-value" style="color:var(--warning);font-size:1.05rem">{{ formatRp($stats['piutang']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-alarm-clock"></i></div>
        <div class="stat-label">Jatuh Tempo</div>
        <div class="stat-value" style="color:var(--danger);font-size:1.05rem">{{ number_format($stats['jatuh_tempo']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-label">Pembayaran Masuk Hari Ini</div>
        <div class="stat-value" style="color:var(--success);font-size:1.05rem">{{ formatRp($stats['pembayaran_masuk']) }}</div>
    </div>
</div>

{{-- ===== FILTER ===== --}}
<form method="GET" class="card mb-4">
    <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:150px"><label class="text-xs font-bold text-muted">Cari</label>
            <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="No. invoice / pelanggan..."></div>
        <div style="min-width:130px"><label class="text-xs font-bold text-muted">Status</label>
            <select name="status" class="form-input">
                <option value="">Semua</option>
                @foreach(\App\Models\InvoiceSparepart::STATUS as $st)
                <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select></div>
        <div style="min-width:120px"><label class="text-xs font-bold text-muted">Metode</label>
            <select name="metode" class="form-input">
                <option value="">Semua</option>
                @foreach(\App\Models\InvoiceSparepart::METODE as $m)
                <option value="{{ $m }}" {{ request('metode') == $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select></div>
        <div style="min-width:120px"><label class="text-xs font-bold text-muted">Tipe</label>
            <select name="tipe" class="form-input">
                <option value="">Semua</option>
                @foreach(['Umum','Grosir','Reseller','Member','Distributor'] as $t)
                <option value="{{ $t }}" {{ request('tipe') == $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select></div>
        <div style="min-width:130px"><label class="text-xs font-bold text-muted">Dari</label>
            <input type="date" name="dari" class="form-input" value="{{ request('dari') }}"></div>
        <div style="min-width:130px"><label class="text-xs font-bold text-muted">Sampai</label>
            <input type="date" name="sampai" class="form-input" value="{{ request('sampai') }}"></div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        <a href="{{ route('invoice.riwayat') }}" class="btn btn-secondary btn-sm"><i class="fas fa-redo"></i></a>
    </div>
</form>

{{-- ===== TABEL ===== --}}
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3>Riwayat Invoice</h3>
        <a href="{{ route('invoice.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Invoice Baru</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No. Invoice</th><th>Tanggal</th><th>Pelanggan</th><th>Tipe</th>
                    <th>Item</th><th>Total</th><th>Dibayar</th><th>Sisa</th>
                    <th>Jatuh Tempo</th><th>Metode</th><th>Status</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                <tr style="{{ $inv->status === 'Dibatalkan' ? 'opacity:.55;background:#fef2f2' : ($inv->isJatuhTempo() ? 'background:#fffbeb' : '') }}">
                    <td><strong style="color:var(--primary)">{{ $inv->no_invoice }}</strong></td>
                    <td>{{ $inv->tanggal?->format('d/m/Y H:i') }}</td>
                    <td>{{ $inv->nama_pelanggan ?? 'Umum' }}</td>
                    <td><span class="badge badge-masuk">{{ $inv->tipe_pelanggan }}</span></td>
                    <td>{{ $inv->items->count() }}</td>
                    <td><strong>{{ formatRp($inv->total) }}</strong></td>
                    <td>{{ formatRp($inv->dibayar) }}</td>
                    <td style="{{ (float) $inv->sisa > 0 ? 'color:#dc2626;font-weight:700' : '' }}">{{ formatRp($inv->sisa) }}</td>
                    <td>{{ $inv->jatuh_tempo?->format('d/m/Y') ?? '-' }}
                        @if($inv->isJatuhTempo())<br><span style="font-size:.6rem;color:#dc2626;font-weight:700">LEWAT TEMPO!</span>@endif
                    </td>
                    <td><span class="badge badge-proses">{{ $inv->metode_bayar }}</span></td>
                    <td><span class="badge {{ $inv->badgeStatus() }}">{{ $inv->status }}</span></td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('invoice.show', $inv) }}" class="btn btn-primary btn-xs" title="Detail"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('invoice.pdf', $inv) }}" target="_blank" class="btn btn-secondary btn-xs" title="PDF A4"><i class="fas fa-file-pdf"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="12" style="text-align:center;color:#94a3b8;padding:24px">Belum ada invoice.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $invoices->links() }}
</div>
@endsection
