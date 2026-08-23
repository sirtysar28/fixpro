@extends('layouts.app')
@section('title', 'Stok Grosir')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">📦 Stok Grosir</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">Stok toko & gudang yang terpisah per lokasi — tidak campur antar toko lain</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('cabang.index') }}" class="btn btn-secondary"><i class="fas fa-exchange-alt"></i> Transfer Stok</a>
        <a href="{{ route('aktivitas-sparepart.index') }}" class="btn btn-secondary"><i class="fas fa-history"></i> Riwayat Stok</a>
    </div>
</div>

<div class="card" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <a href="{{ route('grosir.stok.index', ['tab' => 'toko']) }}" class="btn {{ $tab === 'toko' ? 'btn-primary' : 'btn-secondary' }}"><i class="fas fa-store"></i> Stok Toko</a>
    <a href="{{ route('grosir.stok.index', ['tab' => 'gudang']) }}" class="btn {{ $tab === 'gudang' ? 'btn-primary' : 'btn-secondary' }}"><i class="fas fa-warehouse"></i> Stok Gudang</a>
    <a href="{{ route('grosir.stok.index', ['tab' => 'minimum']) }}" class="btn {{ $tab === 'minimum' ? 'btn-danger' : 'btn-secondary' }}"><i class="fas fa-exclamation-triangle"></i> Stok Minimum</a>
    <a href="{{ route('grosir.stok.index', ['tab' => 'reservasi']) }}" class="btn {{ $tab === 'reservasi' ? 'btn-primary' : 'btn-secondary' }}"><i class="fas fa-lock"></i> Stok Reservasi</a>

    @if($tab === 'gudang' && count($gudangs))
    <form method="GET" style="display:flex;gap:6px;margin-left:auto;">
        <input type="hidden" name="tab" value="gudang">
        <select name="gudang" class="form-input" onchange="this.form.submit()" style="width:200px;">
            @foreach($gudangs as $g)
            <option value="{{ $g['id'] }}" {{ $gudangTerpilih === $g['id'] ? 'selected' : '' }}>{{ $g['nama'] }}</option>
            @endforeach
        </select>
    </form>
    @endif
</div>

<div class="grid-3" style="margin-bottom:16px;">
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">TOTAL JENIS PRODUK</div>
        <div style="font-size:1.4rem;font-weight:800;margin-top:4px;">{{ number_format($totalJenis) }}</div>
    </div>
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">TOTAL UNIT STOK</div>
        <div style="font-size:1.4rem;font-weight:800;margin-top:4px;">{{ number_format($totalUnit) }}</div>
        <div style="font-size:.72rem;color:#b45309;">Reservasi: {{ number_format($totalReserved) }} unit</div>
    </div>
    <div class="stat-card">
        <div style="font-size:.75rem;color:#94a3b8;font-weight:600;">NILAI MODAL STOK</div>
        <div style="font-size:1.4rem;font-weight:800;color:var(--primary);margin-top:4px;">{{ formatRp($nilaiModal) }}</div>
        <div style="font-size:.72rem;color:{{ $stokRendah > 0 ? 'var(--danger)' : 'inherit' }};">{{ $stokRendah }} produk di bawah minimum</div>
    </div>
</div>

<form method="GET" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <input type="hidden" name="tab" value="{{ $tab }}">
    @if($tab === 'gudang')<input type="hidden" name="gudang" value="{{ $gudangTerpilih }}">@endif
    <div style="flex:1;min-width:180px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Cari produk</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / kode / barcode..." class="form-input">
    </div>
    <button class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
</form>

<div class="card">
    <div class="card-header">
        <h3>
            @if($tab === 'toko') Stok Toko Aktif
            @elseif($tab === 'gudang') Stok Gudang
            @elseif($tab === 'minimum') ⚠️ Produk di Bawah Stok Minimum
            @else 🔒 Produk dengan Stok Direservasi (Pesanan Grosir)
            @endif
        </h3>
    </div>

    @if($tab === 'gudang' && !count($gudangs))
    <div class="alert alert-warning"><i class="fas fa-info-circle"></i>
        Belum ada cabang bertipe <b>Gudang</b>. Ubah tipe cabang di menu Multi Cabang menjadi "gudang" agar bisa dipakai sebagai sumber stok grosir.
    </div>
    @endif

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Produk</th><th>Kategori</th>
                    <th style="text-align:right;">Stok Fisik</th>
                    <th style="text-align:right;">Reservasi</th>
                    <th style="text-align:right;">Tersedia</th>
                    <th style="text-align:right;">Min</th>
                    <th style="text-align:right;">Modal</th>
                    <th style="text-align:right;">Harga Eceran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stoks as $s)
                <tr>
                    <td>
                        <b>{{ $s->nama }}</b>
                        <div style="font-size:.7rem;color:#94a3b8;font-family:monospace;">{{ $s->kode }}</div>
                    </td>
                    <td>{{ $s->kategori }}</td>
                    <td style="text-align:right;font-weight:600;">{{ $s->stok }}</td>
                    <td style="text-align:right;color:{{ $s->reserved > 0 ? '#b45309' : '#94a3b8' }};">{{ $s->reserved }}</td>
                    <td style="text-align:right;font-weight:700;color:{{ $s->stok_tersedia > 0 ? 'var(--primary)' : 'var(--danger)' }};">{{ $s->stok_tersedia }}</td>
                    <td style="text-align:right;">{{ $s->min_alert }}</td>
                    <td style="text-align:right;">{{ formatRp($s->modal) }}</td>
                    <td style="text-align:right;">{{ formatRp($s->jual) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:28px;">
                    @if($tab === 'minimum') Semua stok di atas minimum. 👍
                    @elseif($tab === 'reservasi') Tidak ada stok yang direservasi.
                    @else Belum ada produk di lokasi ini.
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $stoks->links() }}
</div>
@endsection
