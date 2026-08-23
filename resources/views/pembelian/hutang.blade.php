@extends('layouts.app')
@section('title', 'Hutang Supplier')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:16px">
    <h2 style="margin:0"><i class="fas fa-hand-holding-usd" style="color:#dc2626;margin-right:6px"></i>Hutang Supplier</h2>
    <a href="{{ route('pembelian.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Ke Pembelian</a>
</div>

<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-label">Total Hutang Berjalan</div>
        <div class="stat-value" style="color:#dc2626">{{ formatRp($totalHutang) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fce7f3;color:#db2777"><i class="fas fa-clock"></i></div>
        <div class="stat-label">Jumlah Transaksi Hutang</div>
        <div class="stat-value" style="color:#db2777">{{ $hutangs->total() }}</div>
    </div>
</div>

<div class="card mb-4">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0;flex:1;min-width:180px">
            <label class="text-xs font-bold text-muted">Cari (kode / supplier)</label>
            <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="PMB-... / nama supplier..." style="padding:8px 12px;font-size:.84rem">
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Jatuh Tempo</label>
            <select name="kategori_jatuh_tempo" class="form-input" style="padding:8px 12px;font-size:.84rem">
                <option value="">Semua</option>
                <option value="lewat" {{ request('kategori_jatuh_tempo') === 'lewat' ? 'selected' : '' }}>Sudah Lewat</option>
                <option value="7hari" {{ request('kategori_jatuh_tempo') === '7hari' ? 'selected' : '' }}>≤ 7 Hari</option>
                <option value="belum" {{ request('kategori_jatuh_tempo') === 'belum' ? 'selected' : '' }}>Tanpa Jatuh Tempo</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
    </form>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 style="font-size:.95rem"><i class="fas fa-list"></i> Daftar Hutang</h3>
        <span class="text-muted text-sm">{{ $hutangs->total() }} data</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Supplier</th><th>Tgl Beli</th><th>Jatuh Tempo</th><th>Total Akhir</th><th>Dibayar</th><th>Sisa Hutang</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($hutangs as $p)
                @php $sb = $p->statusBadge(); @endphp
                <tr>
                    <td><strong style="color:var(--primary)">{{ $p->kode }}</strong></td>
                    <td>{{ $p->supplier_nama }}</td>
                    <td>{{ $p->tanggal?->format('d/m/Y') }}</td>
                    <td>
                        @if($p->tanggal_jatuh_tempo)
                            <span style="color:{{ $p->isJatuhTempo() ? '#dc2626' : '#0f172a' }};font-weight:{{ $p->isJatuhTempo() ? '700' : '400' }}">{{ $p->tanggal_jatuh_tempo->format('d/m/Y') }}</span>
                            @if($p->isJatuhTempo())<span style="font-size:.66rem;background:#fee2e2;color:#dc2626;padding:1px 6px;border-radius:8px;margin-left:4px">LEWAT</span>@endif
                        @else
                            <span style="color:#94a3b8">—</span>
                        @endif
                    </td>
                    <td>{{ formatRp($p->totalAkhir()) }}</td>
                    <td>{{ formatRp($p->dibayar) }}</td>
                    <td style="color:#dc2626;font-weight:700">{{ formatRp($p->sisa) }}</td>
                    <td><span style="background:{{ $sb['bg'] }};color:{{ $sb['color'] }};padding:2px 8px;border-radius:10px;font-size:.7rem;font-weight:700">{{ $sb['label'] }}</span></td>
                    <td>
                        <a href="{{ route('pembelian.show', $p) }}#bayar" class="btn btn-xs" style="background:#dcfce7;color:#16a34a" title="Bayar"><i class="fas fa-hand-holding-usd"></i> Bayar</a>
                        <a href="{{ route('pembelian.show', $p) }}" class="btn btn-primary btn-xs" title="Detail"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                @endforeach
                @if($hutangs->count() === 0)
                <tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:24px">Tidak ada hutang supplier. 🎉</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    {{ $hutangs->links() }}
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px" class="grid-responsive">
    <div class="card">
        <div class="card-header"><h3 style="font-size:.9rem"><i class="fas fa-history" style="color:#16a34a"></i> Riwayat Pembayaran Terbaru</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tgl</th><th>Pembelian</th><th>Jumlah</th><th>Metode</th><th>Diterima Oleh</th></tr></thead>
                <tbody>
                    @forelse($riwayatPembayaran as $pay)
                    <tr>
                        <td style="font-size:.76rem">{{ $pay->tanggal?->format('d/m/y') }}</td>
                        <td style="font-size:.76rem"><a href="{{ route('pembelian.show', $pay->pembelian_id) }}" style="color:var(--primary);font-weight:600">{{ $pay->pembelian?->kode }}</a><br><span style="color:#94a3b8;font-size:.68rem">{{ $pay->pembelian?->supplier_nama }}</span></td>
                        <td style="font-weight:600;color:#16a34a">{{ formatRp($pay->jumlah) }}</td>
                        <td style="font-size:.76rem">{{ $pay->metode }}</td>
                        <td style="font-size:.76rem">{{ $pay->user?->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:16px">Belum ada pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 style="font-size:.9rem"><i class="fas fa-undo-alt" style="color:#c2410c"></i> Riwayat Retur Terbaru</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tgl</th><th>Barang</th><th>Qty</th><th>Nilai</th><th>Oleh</th></tr></thead>
                <tbody>
                    @forelse($riwayatRetur as $ret)
                    <tr>
                        <td style="font-size:.76rem">{{ $ret->tanggal?->format('d/m/y') }}</td>
                        <td style="font-size:.76rem">{{ $ret->nama_barang }}<br><span style="color:#94a3b8;font-size:.68rem">{{ $ret->kode }}</span></td>
                        <td style="text-align:center">{{ $ret->qty }}</td>
                        <td style="font-weight:600;color:#c2410c">{{ formatRp($ret->nilai) }}</td>
                        <td style="font-size:.76rem">{{ $ret->user?->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:16px">Belum ada retur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media (max-width: 900px) { .grid-responsive { grid-template-columns: 1fr !important; } }
</style>
@endsection
