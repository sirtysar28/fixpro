@extends('layouts.app')
@section('title', 'Pembayaran Invoice')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-money-bill-wave" style="color:var(--primary)"></i> Pembayaran (Invoice Sparepart)</h2>
    <a href="{{ route('invoice.piutang') }}" class="btn btn-primary btn-sm"><i class="fas fa-hand-holding-usd"></i> Lihat Piutang</a>
</div>

<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-label">Pembayaran Masuk Hari Ini</div>
        <div class="stat-value" style="color:var(--success)">{{ formatRp($masukHariIni) }}</div>
    </div>
</div>

<form method="GET" class="card mb-4">
    <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
        <div style="min-width:140px"><label class="text-xs font-bold text-muted">Dari</label>
            <input type="date" name="dari" class="form-input" value="{{ request('dari') }}"></div>
        <div style="min-width:140px"><label class="text-xs font-bold text-muted">Sampai</label>
            <input type="date" name="sampai" class="form-input" value="{{ request('sampai') }}"></div>
        <div style="min-width:120px"><label class="text-xs font-bold text-muted">Metode</label>
            <select name="metode" class="form-input">
                <option value="">Semua</option>
                <option value="Tunai" {{ request('metode') == 'Tunai' ? 'selected' : '' }}>Tunai</option>
                <option value="Transfer" {{ request('metode') == 'Transfer' ? 'selected' : '' }}>Transfer</option>
                <option value="QRIS" {{ request('metode') == 'QRIS' ? 'selected' : '' }}>QRIS</option>
            </select></div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        <a href="{{ route('invoice.pembayaran') }}" class="btn btn-secondary btn-sm"><i class="fas fa-redo"></i></a>
    </div>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tanggal</th><th>Invoice</th><th>Pelanggan</th><th>Jumlah</th><th>Metode</th><th>Diterima Oleh</th><th>Catatan</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($payments as $pay)
                <tr>
                    <td>{{ $pay->tanggal?->format('d/m/Y H:i') }}</td>
                    <td><a href="{{ route('invoice.show', $pay->invoice) }}" style="color:var(--primary);font-weight:700">{{ $pay->invoice?->no_invoice }}</a></td>
                    <td>{{ $pay->invoice?->nama_pelanggan ?? 'Umum' }}</td>
                    <td><strong style="color:var(--success)">{{ formatRp($pay->jumlah) }}</strong></td>
                    <td><span class="badge badge-proses">{{ $pay->metode }}</span></td>
                    <td>{{ $pay->user?->name ?? '-' }}</td>
                    <td>{{ $pay->catatan ?? '-' }}</td>
                    <td><a href="{{ route('invoice.show', $pay->invoice) }}" class="btn btn-secondary btn-xs"><i class="fas fa-eye"></i></a></td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:24px">Belum ada pembayaran.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $payments->links() }}
</div>
@endsection
