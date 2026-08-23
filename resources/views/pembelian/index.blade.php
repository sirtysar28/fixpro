@extends('layouts.app')
@section('title', 'Pembelian Supplier')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:16px">
    <h2 style="margin:0"><i class="fas fa-truck-loading" style="color:var(--primary);margin-right:6px"></i>Pembelian Supplier</h2>
    <div style="display:flex;gap:8px">
        <a href="{{ route('pembelian.hutang') }}" class="btn btn-sm" style="background:#fee2e2;color:#dc2626"><i class="fas fa-hand-holding-usd"></i> Hutang Supplier</a>
        <a href="{{ route('pembelian.create') }}" class="btn btn-success btn-sm" style="background:#16a34a;color:#fff"><i class="fas fa-plus"></i> Pembelian Baru</a>
    </div>
</div>

{{-- ===== DASHBOARD PEMBELIAN ===== --}}
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-label">Total Pembelian</div>
        <div class="stat-value" style="color:#d97706">{{ formatRp($totalPembelian) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="stat-label">Total Hutang Supplier</div>
        <div class="stat-value" style="color:#dc2626">{{ formatRp($totalHutang) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-receipt"></i></div>
        <div class="stat-label">Total Transaksi</div>
        <div class="stat-value" style="color:var(--primary)">{{ $totalTransaksi }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-label">Pembelian Bulan Ini</div>
        <div class="stat-value" style="color:#2563eb">{{ formatRp($pembelianBulanIni) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-money-check-alt"></i></div>
        <div class="stat-label">Pembayaran Bulan Ini</div>
        <div class="stat-value" style="color:#16a34a">{{ formatRp($pembayaranBulanIni) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fce7f3;color:#db2777"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-label">Hutang Jatuh Tempo</div>
        <div class="stat-value" style="color:#db2777">{{ $jatuhTempo->count() }}</div>
    </div>
</div>

@if($jatuhTempo->count() > 0)
<div style="background:linear-gradient(135deg,#fef3c7,#fde68a);border:1px solid #fcd34d;border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:10px">
    <i class="fas fa-exclamation-triangle" style="color:#b45309;font-size:1.2rem"></i>
    <div style="font-size:.82rem;color:#78350f">
        <strong>{{ $jatuhTempo->count() }} hutang jatuh tempo (≤7 hari / lewat):</strong>
        @foreach($jatuhTempo->take(3) as $jt)
        <span style="margin-left:6px">{{ $jt->kode }} ({{ $jt->supplier_nama }}) — Rp {{ number_format($jt->sisa,0,',','.') }} @if($jt->tanggal_jatuh_tempo)<em style="opacity:.7">({{ $jt->tanggal_jatuh_tempo->format('d/m/Y') }})</em>@endif</span>{{ !$loop->last ? '•' : '' }}
        @endforeach
        <a href="{{ route('pembelian.hutang') }}" style="margin-left:8px;font-weight:700;color:#92400e">Lihat semua →</a>
    </div>
</div>
@endif

{{-- ===== TOP SUPPLIER / TOP PRODUK + GRAFIK ===== --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px" class="grid-responsive">
    <div class="card" style="padding:16px">
        <h3 style="font-size:.9rem;margin-bottom:12px"><i class="fas fa-chart-bar" style="color:var(--primary)"></i> Grafik Pembelian vs Pembayaran Hutang (12 bulan)</h3>
        <div class="chart-container"><canvas id="chartPembelian"></canvas></div>
    </div>
    <div style="display:grid;gap:16px">
        <div class="card" style="padding:16px">
            <h3 style="font-size:.9rem;margin-bottom:10px"><i class="fas fa-store" style="color:#d97706"></i> Supplier Paling Sering Dibeli</h3>
            @forelse($topSuppliers as $i => $ts)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px dashed #e2e8f0;font-size:.8rem">
                <span><span style="background:#fef3c7;color:#b45309;padding:1px 7px;border-radius:8px;font-size:.68rem;font-weight:700;margin-right:6px">{{ $i+1 }}</span>{{ Str::limit($ts->supplier_nama, 30) }}</span>
                <span style="color:#64748b">{{ $ts->jumlah_transaksi }}x • {{ formatRp($ts->total_nilai) }}</span>
            </div>
            @empty
            <div style="color:#94a3b8;font-size:.8rem;padding:8px 0">Belum ada data.</div>
            @endforelse
        </div>
        <div class="card" style="padding:16px">
            <h3 style="font-size:.9rem;margin-bottom:10px"><i class="fas fa-box-open" style="color:#2563eb"></i> Produk Paling Banyak Dibeli</h3>
            @forelse($topProducts as $i => $tp)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px dashed #e2e8f0;font-size:.8rem">
                <span><span style="background:#dbeafe;color:#2563eb;padding:1px 7px;border-radius:8px;font-size:.68rem;font-weight:700;margin-right:6px">{{ $i+1 }}</span>{{ Str::limit($tp['nama'], 30) }}</span>
                <span style="color:#64748b">{{ $tp['qty'] }} pcs • {{ formatRp($tp['nilai']) }}</span>
            </div>
            @empty
            <div style="color:#94a3b8;font-size:.8rem;padding:8px 0">Belum ada data.</div>
            @endforelse
        </div>
    </div>
</div>

{{-- ===== FILTER & PENCARIAN ===== --}}
<div class="card mb-4">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0;flex:1;min-width:180px">
            <label class="text-xs font-bold text-muted">Cari (nomor transaksi / supplier / produk)</label>
            <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="PMB-... / nama supplier / nama produk..." style="padding:8px 12px;font-size:.84rem">
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Status Bayar</label>
            <select name="status" class="form-input" style="padding:8px 12px;font-size:.84rem">
                <option value="">Semua</option>
                @foreach(['Hutang','Sebagian','Lunas','Dibatalkan'] as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Status Transaksi</label>
            <select name="status_transaksi" class="form-input" style="padding:8px 12px;font-size:.84rem">
                <option value="">Semua</option>
                @foreach(['Draft','Diproses','Selesai','Dibatalkan'] as $st)
                <option value="{{ $st }}" {{ request('status_transaksi') === $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Dari</label>
            <input type="date" name="dari" class="form-input" value="{{ request('dari') }}" style="padding:8px 12px;font-size:.84rem">
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Sampai</label>
            <input type="date" name="sampai" class="form-input" value="{{ request('sampai') }}" style="padding:8px 12px;font-size:.84rem">
        </div>
        <div class="form-group" style="margin:0">
            <label class="text-xs font-bold text-muted">Jatuh Tempo</label>
            <select name="jatuh_tempo" class="form-input" style="padding:8px 12px;font-size:.84rem">
                <option value="">Semua</option>
                <option value="lewat" {{ request('jatuh_tempo') === 'lewat' ? 'selected' : '' }}>Sudah Lewat</option>
                <option value="7hari" {{ request('jatuh_tempo') === '7hari' ? 'selected' : '' }}>≤ 7 Hari</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('pembelian.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i></a>
    </form>
</div>

{{-- ===== RIWAYAT PEMBELIAN ===== --}}
<div class="card">
    <div class="card-header">
        <h3 style="font-size:.95rem"><i class="fas fa-list"></i> Riwayat Pembelian</h3>
        <span class="text-muted text-sm">{{ $items->total() }} data</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Tgl</th><th>Supplier</th><th>Items</th><th>Total</th><th>Retur</th><th>Dibayar</th><th>Sisa (Hutang)</th><th>Status Bayar</th><th>Transaksi</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($items as $p)
                @php
                    $sb = $p->statusBadge();
                    $st = $p->statusTransaksiBadge();
                    $sisaNow = $p->isDibatalkan() ? 0 : max(0, (float)$p->total - (float)$p->total_retur - (float)$p->dibayar);
                @endphp
                <tr>
                    <td><strong style="color:var(--primary)">{{ $p->kode }}</strong></td>
                    <td>{{ $p->tanggal?->format('d/m/Y') }}</td>
                    <td>{{ $p->supplier_nama }}<br><span style="font-size:.7rem;color:#94a3b8">{{ $p->supplier_kontak }}</span></td>
                    <td style="text-align:center">{{ is_array($p->items) ? count($p->items) : 0 }}</td>
                    <td style="font-weight:600">{{ formatRp($p->total) }}</td>
                    <td style="color:{{ (float)$p->total_retur > 0 ? '#dc2626' : '#94a3b8' }}">{{ (float)$p->total_retur > 0 ? '- ' . formatRp($p->total_retur) : '-' }}</td>
                    <td>{{ formatRp($p->dibayar) }}</td>
                    <td style="color:{{ $sisaNow > 0 ? '#dc2626' : '#16a34a' }};font-weight:600">{{ formatRp($sisaNow) }}</td>
                    <td><span style="background:{{ $sb['bg'] }};color:{{ $sb['color'] }};padding:2px 8px;border-radius:10px;font-size:.7rem;font-weight:700">{{ $sb['label'] }}</span></td>
                    <td><span style="background:{{ $st['bg'] }};color:{{ $st['color'] }};padding:2px 8px;border-radius:10px;font-size:.7rem;font-weight:700">{{ $p->status_transaksi }}</span></td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap">
                            {{-- 👁 Detail --}}
                            <a href="{{ route('pembelian.show', $p) }}" class="btn btn-primary btn-xs" title="Detail"><i class="fas fa-eye"></i></a>
                            {{-- 🖨 Cetak Nota --}}
                            <a href="{{ route('pembelian.nota', $p) }}" target="_blank" class="btn btn-xs" style="background:#e0e7ff;color:#4338ca" title="Cetak Nota"><i class="fas fa-print"></i></a>
                            {{-- ✏️ Edit --}}
                            @if(!$p->isDibatalkan())
                            <a href="{{ route('pembelian.edit', $p) }}" class="btn btn-xs" style="background:#fef3c7;color:#b45309" title="Edit"><i class="fas fa-edit"></i></a>
                            {{-- 💰 Bayar Hutang --}}
                            @if($sisaNow > 0 && !$p->isDraft())
                            <a href="{{ route('pembelian.show', $p) }}#bayar" class="btn btn-xs" style="background:#dcfce7;color:#16a34a" title="Bayar Hutang"><i class="fas fa-hand-holding-usd"></i></a>
                            @endif
                            {{-- ↩️ Retur --}}
                            @if(!$p->isDraft() && (float)$p->total_retur < (float)$p->total)
                            <a href="{{ route('pembelian.show', $p) }}#retur" class="btn btn-xs" style="background:#ffedd5;color:#c2410c" title="Retur"><i class="fas fa-undo"></i></a>
                            @endif
                            @endif
                            {{-- 🗑 Batalkan/Hapus --}}
                            @if(!$p->isDibatalkan())
                            <form method="POST" action="{{ route('pembelian.batal', $p) }}" style="display:inline" onsubmit="return confirm('Batalkan {{ $p->kode }}? Stok & pembayaran akan dikembalikan.')">
                                @csrf
                                <input type="hidden" name="alasan" value="Dibatalkan dari daftar">
                                <button type="submit" class="btn btn-xs" style="background:#fee2e2;color:#dc2626" title="Batalkan"><i class="fas fa-ban"></i></button>
                            </form>
                            @elseif($p->payments()->doesntExist())
                            <form method="POST" action="{{ route('pembelian.destroy', $p) }}" style="display:inline" onsubmit="return confirm('Hapus permanen {{ $p->kode }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-xs" style="background:#f1f5f9;color:#64748b" title="Hapus Permanen"><i class="fas fa-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
                @if($items->count() === 0)
                <tr><td colspan="11" style="text-align:center;color:#94a3b8;padding:24px">Belum ada pembelian. Klik "Pembelian Baru" untuk mulai.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    {{ $items->links() }}
</div>

<style>
@media (max-width: 900px) { .grid-responsive { grid-template-columns: 1fr !important; } }
</style>

<script>
// Grafik pembelian vs pembayaran hutang
new Chart(document.getElementById('chartPembelian'), {
    type: 'bar',
    data: {
        labels: @json($labels12),
        datasets: [
            {
                label: 'Pembelian',
                data: @json($dataPembelian),
                backgroundColor: 'rgba(217, 119, 6, .7)',
                borderRadius: 4,
            },
            {
                label: 'Pembayaran Hutang',
                data: @json($dataPembayaran),
                backgroundColor: 'rgba(22, 163, 74, .7)',
                borderRadius: 4,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label + ': Rp ' + (ctx.parsed.y ?? 0).toLocaleString('id-ID'),
                },
            },
        },
        scales: {
            y: { ticks: { callback: v => v >= 1000000 ? (v/1000000)+'jt' : v, font: { size: 10 } } },
            x: { ticks: { font: { size: 10 } } },
        },
    },
});
</script>
@endsection
