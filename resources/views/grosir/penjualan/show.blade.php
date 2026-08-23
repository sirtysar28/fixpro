@extends('layouts.app')
@section('title', 'Detail Nota Grosir ' . $penjualan_grosir->no_nota)

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">🧾 Nota {{ $penjualan_grosir->no_nota }}</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">
            <a href="{{ route('grosir.penjualan.index') }}">← Riwayat penjualan grosir</a>
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @if($penjualan_grosir->status !== 'Dibatalkan')
        <a href="{{ route('grosir.penjualan.nota', $penjualan_grosir) }}" target="_blank" class="btn btn-primary"><i class="fas fa-receipt"></i> Nota</a>
        <a href="{{ route('grosir.penjualan.invoice', $penjualan_grosir) }}" target="_blank" class="btn btn-secondary"><i class="fas fa-file-invoice"></i> Invoice A4</a>
        <a href="{{ route('grosir.penjualan.surat-jalan', $penjualan_grosir) }}" target="_blank" class="btn btn-secondary"><i class="fas fa-truck"></i> Surat Jalan</a>
        <a href="{{ route('grosir.retur.create', ['nota' => $penjualan_grosir->id]) }}" class="btn btn-secondary"><i class="fas fa-undo"></i> Retur</a>
        @endif
    </div>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Info Nota</h3></div>
        <table style="font-size:.85rem;">
            <tr><td style="color:#94a3b8;width:150px;">No Nota</td><td style="font-weight:700;font-family:monospace;">{{ $penjualan_grosir->no_nota }}</td></tr>
            <tr><td style="color:#94a3b8;">Tanggal & Jam</td><td>{{ $penjualan_grosir->tanggal->format('d M Y, H:i') }}</td></tr>
            <tr><td style="color:#94a3b8;">Toko Pencatat</td><td>{{ $penjualan_grosir->cabang?->nama ?? '-' }}</td></tr>
            <tr><td style="color:#94a3b8;">Sumber Stok</td><td>{{ $penjualan_grosir->sumberCabang?->nama ?? '-' }} {{ $penjualan_grosir->sumberCabang?->isGudang() ? '(Gudang)' : '(Toko)' }}</td></tr>
            <tr><td style="color:#94a3b8;">Kasir</td><td>{{ $penjualan_grosir->user?->name ?? '-' }}</td></tr>
            <tr><td style="color:#94a3b8;">Metode Bayar</td><td>{{ $penjualan_grosir->metode_bayar }}</td></tr>
            <tr><td style="color:#94a3b8;">Status</td><td>
                @if($penjualan_grosir->status === 'Lunas')<span class="badge badge-selesai">Lunas</span>
                @elseif($penjualan_grosir->status === 'Dibatalkan')<span class="badge badge-pending">Dibatalkan</span>
                @else<span class="badge badge-proses">{{ $penjualan_grosir->status }}</span>@endif
            </td></tr>
            @if($penjualan_grosir->dibatalkan_pada)
            <tr><td style="color:#94a3b8;">Dibatalkan</td><td style="color:var(--danger);">{{ $penjualan_grosir->dibatalkan_pada->format('d/m/Y H:i') }} oleh {{ $penjualan_grosir->dibatalkanOleh?->name }} — {{ $penjualan_grosir->alasan_pembatalan }}</td></tr>
            @endif
        </table>
    </div>
    <div class="card">
        <div class="card-header"><h3>Data Pelanggan</h3></div>
        <table style="font-size:.85rem;">
            <tr><td style="color:#94a3b8;width:150px;">Nama</td><td style="font-weight:600;">{{ $penjualan_grosir->nama_pelanggan }}</td></tr>
            <tr><td style="color:#94a3b8;">Kode Pelanggan</td><td style="font-family:monospace;">{{ $penjualan_grosir->pelanggan?->kode ?? '-' }}</td></tr>
            <tr><td style="color:#94a3b8;">Level Harga</td><td>{{ $penjualan_grosir->labelLevelHarga() }}</td></tr>
            <tr><td style="color:#94a3b8;">No HP</td><td>{{ $penjualan_grosir->pelanggan?->no_hp ?? '-' }}</td></tr>
            <tr><td style="color:#94a3b8;">Alamat Kirim</td><td>{{ $penjualan_grosir->alamat_kirim ?? '-' }}</td></tr>
            @if($penjualan_grosir->catatan)<tr><td style="color:#94a3b8;">Catatan</td><td>{{ $penjualan_grosir->catatan }}</td></tr>@endif
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Item Barang</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Produk</th><th>Kode</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Harga</th><th style="text-align:right;">Subtotal</th></tr></thead>
            <tbody>
                @foreach($penjualan_grosir->items as $item)
                <tr>
                    <td style="font-weight:600;">{{ $item->nama }}</td>
                    <td style="font-family:monospace;">{{ $item->kode }}</td>
                    <td style="text-align:center;">{{ $item->qty }}</td>
                    <td style="text-align:right;">{{ formatRp($item->harga_satuan) }}</td>
                    <td style="text-align:right;font-weight:700;">{{ formatRp($item->subtotal) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="display:flex;justify-content:flex-end;margin-top:14px;">
        <table style="min-width:300px;font-size:.9rem;">
            <tr><td style="color:#64748b;padding:4px 0;">Subtotal Barang</td><td style="text-align:right;font-weight:600;">{{ formatRp($penjualan_grosir->subtotal) }}</td></tr>
            <tr><td style="color:#64748b;padding:4px 0;">Diskon Transaksi</td><td style="text-align:right;">−{{ formatRp($penjualan_grosir->diskon) }}</td></tr>
            <tr><td style="font-weight:800;padding:8px 0;border-top:2px solid #e2e8f0;">TOTAL</td><td style="text-align:right;font-weight:800;font-size:1.05rem;color:var(--primary);border-top:2px solid #e2e8f0;">{{ formatRp($penjualan_grosir->total) }}</td></tr>
            <tr><td style="color:#64748b;padding:4px 0;">Pembayaran</td><td style="text-align:right;">{{ formatRp($penjualan_grosir->bayar) }}</td></tr>
            @foreach($penjualan_grosir->payments as $pay)
            <tr><td style="color:#64748b;padding:2px 0 2px 14px;font-size:.78rem;">↳ {{ $pay->tanggal->format('d/m/Y') }} ({{ $pay->metode }})</td><td style="text-align:right;font-size:.78rem;">{{ formatRp($pay->jml) }}</td></tr>
            @endforeach
            @foreach($penjualan_grosir->returs->where('metode', 'Potong Piutang') as $retur)
            <tr><td style="color:#64748b;padding:2px 0 2px 14px;font-size:.78rem;">↳ Retur potong piutang {{ $retur->no_retur }}</td><td style="text-align:right;font-size:.78rem;">−{{ formatRp($retur->total) }}</td></tr>
            @endforeach
            <tr><td style="color:#b45309;font-weight:600;padding:4px 0;">Sisa Piutang</td><td style="text-align:right;font-weight:700;color:#b45309;">{{ formatRp($sisaPiutang) }}</td></tr>
            @if($penjualan_grosir->jatuh_tempo)
            <tr><td style="color:#64748b;padding:4px 0;">Jatuh Tempo</td><td style="text-align:right;{{ $penjualan_grosir->jatuh_tempo->isPast() && $sisaPiutang > 0 ? 'color:var(--danger);font-weight:700;' : '' }}">{{ $penjualan_grosir->jatuh_tempo->format('d-m-Y') }}</td></tr>
            @endif
        </table>
    </div>
</div>

@if($penjualan_grosir->status !== 'Dibatalkan' && $sisaPiutang > 0)
<div class="card">
    <div class="card-header"><h3>💳 Bayar Piutang</h3></div>
    <form method="POST" action="{{ route('grosir.piutang.bayar', $penjualan_grosir) }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        @csrf
        <div style="flex:1;min-width:130px;">
            <label style="font-size:.75rem;font-weight:600;color:#374151;">Jumlah (Rp)</label>
            <input type="number" name="jml" min="1" step="any" max="{{ $sisaPiutang }}" value="{{ $sisaPiutang }}" class="form-input" required>
        </div>
        <div style="min-width:120px;">
            <label style="font-size:.75rem;font-weight:600;color:#374151;">Metode</label>
            <select name="metode" class="form-input">
                <option value="Cash">Cash</option><option value="Transfer">Transfer</option><option value="QRIS">QRIS</option>
            </select>
        </div>
        <div style="flex:2;min-width:150px;">
            <label style="font-size:.75rem;font-weight:600;color:#374151;">Catatan</label>
            <input type="text" name="catatan" class="form-input" placeholder="Opsional">
        </div>
        <button class="btn btn-primary"><i class="fas fa-money-bill-wave"></i> Catat Pembayaran</button>
    </form>
</div>
@endif

@if($penjualan_grosir->status !== 'Dibatalkan')
<div class="card">
    <div class="card-header"><h3 style="color:var(--danger);">⚠️ Batalkan Nota</h3></div>
    <form id="formBatal" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <label style="font-size:.75rem;font-weight:600;color:#374151;">Alasan Pembatalan</label>
            <input type="text" id="alasanBatal" class="form-input" placeholder="Minimal 3 karakter..." minlength="3" required>
        </div>
        <button type="button" class="btn btn-danger" onclick="batalkan()"><i class="fas fa-ban"></i> Batalkan Nota</button>
    </form>
    <p style="font-size:.75rem;color:#94a3b8;margin:8px 0 0;">Stok akan dikembalikan ke sumber & kas disesuaikan otomatis.</p>
</div>
@endif

<script>
async function batalkan() {
    const alasan = document.getElementById('alasanBatal').value.trim();
    if (alasan.length < 3) { alert('Isi alasan minimal 3 karakter.'); return; }
    if (!confirm('Yakin batalkan nota ini? Stok akan dikembalikan.')) return;
    const res = await fetch('{{ route('grosir.penjualan.batal', $penjualan_grosir) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ alasan })
    });
    const data = await res.json();
    if (data.success) location.reload();
    else alert(data.message || 'Gagal membatalkan.');
}
</script>
@endsection
