@extends('layouts.app')
@section('title', 'Laporan Grosir')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">📊 Laporan Grosir</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">Laporan penjualan grosir toko aktif — {{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') }}</p>
    </div>
</div>

<div class="grid-3" style="margin-bottom:16px;">
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">OMZET ({{ $jumlahTransaksi }} NOTA)</div>
        <div style="font-size:1.4rem;font-weight:800;color:var(--primary);margin-top:4px;">{{ formatRp($omzet) }}</div>
        <div style="font-size:.72rem;color:#64748b;">Diskon: {{ formatRp($totalDiskon) }}</div>
        @if($totalRetur > 0)
        <div style="font-size:.72rem;color:#c2410c;">Retur: &minus; {{ formatRp($totalRetur) }}</div>
        <div style="font-size:.75rem;color:#0f172a;font-weight:700;margin-top:2px;">Omzet Bersih: {{ formatRp($omzetBersih) }}</div>
        @endif
    </div>
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">LABA KOTOR</div>
        <div style="font-size:1.4rem;font-weight:800;color:var(--success);margin-top:4px;">{{ formatRp($laba) }}</div>
    </div>
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">PIUTANG (PERIODE INI)</div>
        <div style="font-size:1.4rem;font-weight:800;color:#b45309;margin-top:4px;">{{ formatRp($piutangSisa) }}</div>
        <a href="{{ route('grosir.piutang.index') }}" style="font-size:.75rem;color:var(--primary);">Kelola piutang →</a>
    </div>
</div>

<form method="GET" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <div style="min-width:140px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Dari</label>
        <input type="date" name="dari" value="{{ $dari }}" class="form-input">
    </div>
    <div style="min-width:140px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Sampai</label>
        <input type="date" name="sampai" value="{{ $sampai }}" class="form-input">
    </div>
    <input type="hidden" name="tab" value="{{ $tab }}">
    <button class="btn btn-primary"><i class="fas fa-filter"></i> Terapkan</button>
    <a href="{{ route('grosir.laporan.index', ['tab' => $tab, 'dari' => $dari, 'sampai' => $sampai, 'export' => 1]) }}" class="btn btn-success"><i class="fas fa-file-excel"></i> Export Excel</a>
</form>

<div class="card" style="display:flex;gap:8px;flex-wrap:wrap;">
    @php
        $tabs = [
            'penjualan' => '🧾 Penjualan',
            'omzet' => '📈 Omzet',
            'laba' => '💰 Laba',
            'terlaris' => '🔥 Produk Terlaris',
            'pelanggan' => '👥 Per Pelanggan',
            'toko' => '🏠 Per Toko',
            'gudang' => '🏬 Per Gudang',
            'piutang' => '💳 Piutang',
            'retur' => '↩️ Retur',
        ];
    @endphp
    @foreach($tabs as $key => $label)
    <a href="{{ route('grosir.laporan.index', ['tab' => $key, 'dari' => $dari, 'sampai' => $sampai]) }}" class="btn {{ $tab === $key ? 'btn-primary' : 'btn-secondary' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- ============ TAB PENJUALAN ============ --}}
@if($tab === 'penjualan')
<div class="card">
    <div class="card-header"><h3>Daftar Nota Penjualan</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>No Nota</th><th>Tanggal</th><th>Pelanggan</th><th>Level</th><th style="text-align:right;">Total</th><th style="text-align:right;">Retur</th><th style="text-align:right;">Laba</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($notas as $p)
                <tr>
                    <td style="font-family:monospace;font-weight:700;"><a href="{{ route('grosir.penjualan.show', $p) }}">{{ $p->no_nota }}</a></td>
                    <td>{{ $p->tanggal->format('d/m/Y H:i') }}</td>
                    <td>{{ $p->nama_pelanggan }}</td>
                    <td>{{ $p->labelLevelHarga() }}</td>
                    <td style="text-align:right;font-weight:600;">{{ formatRp($p->total) }}</td>
                    <td style="text-align:right;color:{{ (float) $p->total_retur > 0 ? '#c2410c' : '#94a3b8' }};">{{ (float) $p->total_retur > 0 ? '- ' . formatRp($p->total_retur) : '-' }}</td>
                    <td style="text-align:right;">{{ formatRp($p->items->sum(fn($i) => ($i->harga_satuan - $i->modal_satuan) * $i->qty) - $p->diskon) }}</td>
                    <td>{{ $p->status }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $notas->links() }}
</div>
@endif

{{-- ============ TAB OMZET ============ --}}
@if($tab === 'omzet')
<div class="card">
    <div class="card-header"><h3>Omzet per Hari</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tanggal</th><th style="text-align:center;">Transaksi</th><th style="text-align:right;">Omzet</th><th style="text-align:right;">Diskon</th><th style="text-align:right;">Piutang Baru</th></tr></thead>
            <tbody>
                @forelse($perHari as $r)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('l, d M Y') }}</td>
                    <td style="text-align:center;">{{ $r->transaksi }}</td>
                    <td style="text-align:right;font-weight:700;">{{ formatRp($r->omzet) }}</td>
                    <td style="text-align:right;">{{ formatRp($r->diskon) }}</td>
                    <td style="text-align:right;">{{ formatRp($r->piutang) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">Tidak ada data pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ============ TAB LABA ============ --}}
@if($tab === 'laba')
<div class="card">
    <div class="card-header"><h3>Laba per Produk</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Produk</th><th style="text-align:center;">Qty Terjual</th><th style="text-align:right;">Omzet</th><th style="text-align:right;">Modal</th><th style="text-align:right;">Laba</th></tr></thead>
            <tbody>
                @forelse($perProduk as $r)
                <tr>
                    <td><b>{{ $r->nama }}</b> <span style="color:#94a3b8;font-family:monospace;font-size:.72rem;">{{ $r->kode }}</span></td>
                    <td style="text-align:center;">{{ $r->qty }}</td>
                    <td style="text-align:right;">{{ formatRp($r->omzet) }}</td>
                    <td style="text-align:right;">{{ formatRp($r->modal) }}</td>
                    <td style="text-align:right;font-weight:700;color:{{ $r->laba >= 0 ? 'var(--success)' : 'var(--danger)' }};">{{ formatRp($r->laba) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ============ TAB TERLARIS ============ --}}
@if($tab === 'terlaris')
<div class="card">
    <div class="card-header"><h3>Produk Terlaris (Top 50)</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Produk</th><th style="text-align:center;">Qty Terjual</th><th style="text-align:right;">Omzet</th></tr></thead>
            <tbody>
                @forelse($terlaris as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><b>{{ $r->nama }}</b> <span style="color:#94a3b8;font-family:monospace;font-size:.72rem;">{{ $r->kode }}</span></td>
                    <td style="text-align:center;"><b>{{ $r->qty }}</b></td>
                    <td style="text-align:right;">{{ formatRp($r->omzet) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:24px;">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ============ TAB PELANGGAN ============ --}}
@if($tab === 'pelanggan')
<div class="card">
    <div class="card-header"><h3>Rekap per Pelanggan</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Pelanggan</th><th>Level</th><th style="text-align:center;">Transaksi</th><th style="text-align:right;">Omzet</th><th style="text-align:right;">Sisa Piutang</th></tr></thead>
            <tbody>
                @forelse($perPelanggan as $r)
                <tr>
                    <td><b>{{ $r->nama }}</b></td>
                    <td>{{ $r->level }}</td>
                    <td style="text-align:center;">{{ $r->transaksi }}</td>
                    <td style="text-align:right;font-weight:700;">{{ formatRp($r->omzet) }}</td>
                    <td style="text-align:right;color:{{ $r->piutang > 0 ? 'var(--danger)' : 'inherit' }};">{{ formatRp($r->piutang) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ============ TAB TOKO / GUDANG ============ --}}
@if(in_array($tab, ['toko', 'gudang']))
<div class="card">
    <div class="card-header"><h3>Rekap per {{ $tab === 'toko' ? 'Toko' : 'Gudang' }} (Sumber Stok)</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Sumber</th><th style="text-align:center;">Transaksi</th><th style="text-align:center;">Qty Keluar</th><th style="text-align:right;">Omzet</th><th style="text-align:right;">Laba</th></tr></thead>
            <tbody>
                @forelse($perSumber as $r)
                <tr>
                    <td><b>{{ $r->nama }}</b></td>
                    <td style="text-align:center;">{{ $r->transaksi }}</td>
                    <td style="text-align:center;">{{ $r->qty }}</td>
                    <td style="text-align:right;font-weight:700;">{{ formatRp($r->omzet) }}</td>
                    <td style="text-align:right;">{{ formatRp($r->laba) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">Tidak ada transaksi dari {{ $tab === 'toko' ? 'toko' : 'gudang' }} pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ============ TAB PIUTANG ============ --}}
@if($tab === 'piutang')
<div class="card">
    <div class="card-header"><h3>Piutang pada Periode Ini</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>No Nota</th><th>Pelanggan</th><th>Jatuh Tempo</th><th style="text-align:right;">Total</th><th style="text-align:right;">Sisa</th></tr></thead>
            <tbody>
                @forelse($piutangList as $p)
                <tr>
                    <td style="font-family:monospace;font-weight:700;"><a href="{{ route('grosir.penjualan.show', $p) }}">{{ $p->no_nota }}</a></td>
                    <td>{{ $p->nama_pelanggan }}</td>
                    <td>{{ $p->jatuh_tempo?->format('d-m-Y') ?? '-' }}</td>
                    <td style="text-align:right;">{{ formatRp($p->total) }}</td>
                    <td style="text-align:right;font-weight:700;">{{ formatRp($p->sisaPiutang()) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">Tidak ada piutang pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
{{-- ============ TAB RETUR ============ --}}
@if($tab === 'retur')
@if($returPerMetode->isNotEmpty())
<div class="grid-3" style="margin-bottom:16px;">
    @foreach($returPerMetode as $m)
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">RETUR — {{ strtoupper($m->metode) }}</div>
        <div style="font-size:1.3rem;font-weight:800;color:#c2410c;margin-top:4px;">{{ formatRp($m->nilai) }}</div>
        <div style="font-size:.72rem;color:#64748b;">{{ $m->jumlah }} transaksi retur</div>
    </div>
    @endforeach
</div>
@endif

<div class="card">
    <div class="card-header"><h3>Daftar Retur Grosir</h3><span class="badge badge-proses">Total periode: {{ formatRp($totalRetur) }}</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>No Retur</th><th>Tanggal</th><th>No Nota</th><th>Pelanggan</th><th>Metode</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Nilai Retur</th><th>Alasan</th><th></th></tr></thead>
            <tbody>
                @forelse($returs as $r)
                <tr>
                    <td style="font-family:monospace;font-weight:700;">{{ $r->no_retur }}</td>
                    <td>{{ $r->tanggal->format('d/m/Y H:i') }}</td>
                    <td style="font-family:monospace;"><a href="{{ route('grosir.penjualan.show', $r->penjualan_id) }}">{{ $r->penjualan?->no_nota ?? '-' }}</a></td>
                    <td>{{ $r->nama_pelanggan ?? '-' }}</td>
                    <td><span class="badge badge-pending">{{ $r->metode }}</span></td>
                    <td style="text-align:center;">{{ $r->items->sum('qty') }}</td>
                    <td style="text-align:right;font-weight:700;color:#c2410c;">{{ formatRp($r->total) }}</td>
                    <td style="font-size:.75rem;color:#64748b;">{{ $r->alasan }}</td>
                    <td><a href="{{ route('grosir.retur.show', $r) }}" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></a></td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:24px;">Tidak ada retur pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($returs, 'links'))
    {{ $returs->links() }}
    @endif
</div>

@if($returPerProduk->isNotEmpty())
<div class="card" style="margin-top:16px;">
    <div class="card-header"><h3>Produk yang Paling Sering Diretur</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Produk</th><th style="text-align:center;">Qty Diretur</th><th style="text-align:right;">Nilai Retur</th></tr></thead>
            <tbody>
                @foreach($returPerProduk as $r)
                <tr>
                    <td><b>{{ $r->nama }}</b></td>
                    <td style="text-align:center;">{{ $r->qty }}</td>
                    <td style="text-align:right;color:#c2410c;">{{ formatRp($r->nilai) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endif

@endsection
