@extends('layouts.app')
@section('title', 'Piutang Grosir')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">💳 Piutang Grosir</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">Kelola piutang penjualan grosir — aktif, jatuh tempo, pembayaran, riwayat</p>
    </div>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

<div class="card" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <a href="{{ route('grosir.piutang.index', ['tab' => 'aktif']) }}" class="btn {{ $tab === 'aktif' ? 'btn-primary' : 'btn-secondary' }}"><i class="fas fa-clock"></i> Piutang Aktif</a>
    <a href="{{ route('grosir.piutang.index', ['tab' => 'jatuh-tempo']) }}" class="btn {{ $tab === 'jatuh-tempo' ? 'btn-danger' : 'btn-secondary' }}"><i class="fas fa-exclamation-triangle"></i> Jatuh Tempo</a>
    <a href="{{ route('grosir.piutang.index', ['tab' => 'riwayat']) }}" class="btn {{ $tab === 'riwayat' ? 'btn-primary' : 'btn-secondary' }}"><i class="fas fa-history"></i> Riwayat Piutang</a>
    <form method="GET" style="display:flex;gap:8px;margin-left:auto;flex-wrap:wrap;">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="No nota / pelanggan..." class="form-input" style="width:220px;">
        <button class="btn btn-secondary"><i class="fas fa-search"></i></button>
    </form>
</div>

@if($tab !== 'riwayat' && isset($piutangs->totalSisa))
<div class="stat-card" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">TOTAL SISA PIUTANG {{ $tab === 'jatuh-tempo' ? 'JATUH TEMPO' : 'AKTIF' }}</div>
        <div style="font-size:1.6rem;font-weight:800;color:{{ $tab === 'jatuh-tempo' ? 'var(--danger)' : '#b45309' }};">{{ formatRp($piutangs->totalSisa) }}</div>
    </div>
    <div style="font-size:.8rem;color:#64748b;text-align:right;">{{ $piutangs->total() }} nota</div>
</div>
@endif

<div class="card">
    <div class="card-header"><h3>{{ $tab === 'jatuh-tempo' ? '⚠️ Piutang Jatuh Tempo' : ($tab === 'riwayat' ? '📚 Riwayat Semua Piutang' : '⏳ Piutang Aktif') }}</h3></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No Nota</th><th>Tanggal</th><th>Pelanggan</th><th>Jatuh Tempo</th>
                    <th style="text-align:right;">Total Nota</th><th style="text-align:right;">Sudah Bayar</th>
                    <th style="text-align:right;">Sisa Piutang</th><th>Status</th><th style="width:190px;">Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($piutangs as $p)
                @php
                    $sudahBayar = (float) $p->bayar + (float) $p->payments->sum('jml') + (float) $p->returs->where('metode','Potong Piutang')->sum('total');
                    $sisa = $p->sisaPiutang();
                    $lewat = $p->jatuh_tempo && $p->jatuh_tempo->isPast() && $sisa > 0;
                @endphp
                <tr>
                    <td style="font-family:monospace;font-weight:700;">
                        <a href="{{ route('grosir.penjualan.show', $p) }}" style="color:inherit;">{{ $p->no_nota }}</a>
                    </td>
                    <td>{{ $p->tanggal->format('d/m/Y') }}</td>
                    <td>{{ $p->nama_pelanggan }}</td>
                    <td style="{{ $lewat ? 'color:var(--danger);font-weight:700;' : '' }}">
                        {{ $p->jatuh_tempo ? $p->jatuh_tempo->format('d-m-Y') : '-' }}
                        @if($lewat)<br><small>terlambat {{ $p->jatuh_tempo->diffInDays(now()) }} hari</small>@endif
                    </td>
                    <td style="text-align:right;">{{ formatRp($p->total) }}</td>
                    <td style="text-align:right;">{{ formatRp($sudahBayar) }}</td>
                    <td style="text-align:right;font-weight:700;color:{{ $sisa > 0 ? 'var(--danger)' : 'var(--success)' }};">{{ formatRp($sisa) }}</td>
                    <td>
                        @if($p->status === 'Lunas')<span class="badge badge-selesai">Lunas</span>
                        @else<span class="badge {{ $lewat ? 'badge-pending' : 'badge-proses' }}">{{ $lewat ? 'Terlambat' : $p->status }}</span>@endif
                    </td>
                    <td>
                        @if($sisa > 0)
                        <form method="POST" action="{{ route('grosir.piutang.bayar', $p) }}" style="display:flex;gap:6px;flex-wrap:wrap;">
                            @csrf
                            <input type="number" name="jml" min="1" step="any" max="{{ $sisa }}" value="{{ $sisa }}" class="form-input" style="width:100px;padding:5px 8px;" required>
                            <select name="metode" class="form-input" style="width:85px;padding:5px 8px;">
                                <option>Cash</option><option>Transfer</option><option>QRIS</option>
                            </select>
                            <button class="btn btn-sm btn-primary" title="Catat pembayaran"><i class="fas fa-money-bill-wave"></i></button>
                        </form>
                        @else
                        <span style="color:var(--success);font-size:.8rem;"><i class="fas fa-check-circle"></i> Lunas</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:28px;">
                    {{ $tab === 'jatuh-tempo' ? 'Tidak ada piutang jatuh tempo. 👍' : 'Tidak ada data piutang.' }}
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $piutangs->links() }}
</div>
@endsection
