<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Invoice {{ $penjualan_grosir->no_nota }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #e2e8f0; color: #1e293b; font-size: 13px; }
    .page { width: 210mm; min-height: 297mm; margin: 20px auto; background: #fff; padding: 18mm 16mm; box-shadow: 0 4px 30px rgba(0,0,0,.12); }

    .inv-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0d9488; padding-bottom: 14px; }
    .inv-head .toko { display: flex; gap: 12px; align-items: center; }
    .inv-head .logo { width: 64px; height: 64px; object-fit: cover; border-radius: 10px; }
    .inv-head h1 { font-size: 20px; color: #0d9488; }
    .inv-head p { font-size: 11.5px; color: #64748b; line-height: 1.6; }
    .inv-title { text-align: right; }
    .inv-title h2 { font-size: 26px; letter-spacing: 3px; color: #0f172a; }
    .inv-title .no { font-family: monospace; font-weight: 700; font-size: 13px; margin-top: 4px; }
    .inv-title .tgl { font-size: 11.5px; color: #64748b; }

    .dua-kolom { display: flex; gap: 14px; margin-top: 18px; }
    .kotak { flex: 1; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; background: #f8fafc; }
    .kotak h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #0d9488; margin-bottom: 8px; }
    .kotak table { font-size: 12.5px; }
    .kotak td { padding: 2px 0; vertical-align: top; }
    .kotak td.lbl { color: #94a3b8; width: 95px; }

    table.items { width: 100%; border-collapse: collapse; margin-top: 18px; font-size: 12.5px; }
    table.items thead th { background: #0f172a; color: #fff; padding: 9px 10px; text-align: left; font-size: 11.5px; text-transform: uppercase; letter-spacing: .5px; }
    table.items thead th.r { text-align: right; }
    table.items thead th.c { text-align: center; }
    table.items tbody td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
    table.items tbody tr:nth-child(even) { background: #f8fafc; }
    table.items td.r { text-align: right; }
    table.items td.c { text-align: center; }
    .badge-lvl { background: #ccfbf1; color: #0f766e; font-size: 10.5px; padding: 2px 8px; border-radius: 10px; font-weight: 700; }

    .inv-total { display: flex; justify-content: space-between; margin-top: 16px; gap: 20px; }
    .inv-total .info { flex: 1; font-size: 11.5px; color: #64748b; }
    .inv-total .info .box { border: 1px dashed #cbd5e1; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
    .inv-total .info b { color: #1e293b; }
    .tbl-total { width: 320px; font-size: 13px; }
    .tbl-total td { padding: 5px 0; }
    .tbl-total td.r { text-align: right; font-weight: 600; }
    .tbl-total .garis td { border-top: 2px solid #0f172a; padding-top: 9px; font-weight: 800; font-size: 16px; }
    .tbl-total .garis td.r { color: #0d9488; }
    .tbl-total .piutang td { color: #b45309; font-weight: 700; }
    .tbl-total .tempo td { color: #b45309; }

    .ttd { display: flex; justify-content: space-between; margin-top: 46px; text-align: center; }
    .ttd .kolom { width: 220px; font-size: 12px; }
    .ttd .nm { margin-top: 52px; border-top: 1.5px solid #94a3b8; padding-top: 6px; font-weight: 700; }
    .ttd .ket { font-size: 10.5px; color: #94a3b8; }

    .foot { margin-top: 26px; text-align: center; font-size: 10.5px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }

    .btn-print { display: block; width: 210mm; margin: 0 auto; padding: 11px; background: #0d9488; color: #fff; border: none; font-family: inherit; font-weight: 700; font-size: 14px; cursor: pointer; border-radius: 10px 10px 0 0; }
    @media print {
        body { background: #fff; }
        .btn-print { display: none; }
        .page { margin: 0; box-shadow: none; width: 100%; }
        @page { size: A4; margin: 10mm; }
    }
</style>
</head>
<body>
<button class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF (A4)</button>

<div class="page">
    {{-- HEADER --}}
    <div class="inv-head">
        <div class="toko">
            @if(!empty($settings['logo']))<img src="{{ $settings['logo'] }}" class="logo" alt="logo">@endif
            <div>
                <h1>{{ $settings['nama_toko'] }}</h1>
                <p>{{ $settings['alamat'] }}<br>
                WA: {{ $settings['wa'] }} · Telp: {{ $settings['telp'] }}</p>
            </div>
        </div>
        <div class="inv-title">
            <h2>INVOICE</h2>
            <div class="no">{{ $penjualan_grosir->no_nota }}</div>
            <div class="tgl">{{ $penjualan_grosir->tanggal->format('d F Y, H:i') }} WIB</div>
            <div style="margin-top:6px;">@if($penjualan_grosir->status === 'Lunas')<span style="background:#dcfce7;color:#166534;padding:3px 12px;border-radius:12px;font-weight:700;font-size:11px;">LUNAS</span>@elseif($penjualan_grosir->status === 'Dibatalkan')<span style="background:#fee2e2;color:#991b1b;padding:3px 12px;border-radius:12px;font-weight:700;font-size:11px;">DIBATALKAN</span>@else<span style="background:#fef3c7;color:#92400e;padding:3px 12px;border-radius:12px;font-weight:700;font-size:11px;">PIUTANG</span>@endif</div>
        </div>
    </div>

    {{-- INFO PELANGGAN & NOTA --}}
    <div class="dua-kolom">
        <div class="kotak">
            <h3>Ditagihkan Kepada</h3>
            <table>
                <tr><td class="lbl">Nama</td><td>: <b>{{ $penjualan_grosir->nama_pelanggan }}</b></td></tr>
                <tr><td class="lbl">Kode</td><td>: {{ $penjualan_grosir->pelanggan?->kode ?? '-' }}</td></tr>
                <tr><td class="lbl">No HP</td><td>: {{ $penjualan_grosir->pelanggan?->no_hp ?? '-' }}</td></tr>
                <tr><td class="lbl">Alamat</td><td>: {{ $penjualan_grosir->alamat_kirim ?? $penjualan_grosir->pelanggan?->alamat ?? '-' }}</td></tr>
            </table>
        </div>
        <div class="kotak">
            <h3>Detail Pengiriman</h3>
            <table>
                <tr><td class="lbl">Sumber Stok</td><td>: {{ $penjualan_grosir->sumberCabang?->nama ?? '-' }} ({{ $penjualan_grosir->sumberCabang?->isGudang() ? 'Gudang' : 'Toko' }})</td></tr>
                <tr><td class="lbl">Level Harga</td><td>: <span class="badge-lvl">{{ $penjualan_grosir->labelLevelHarga() }}</span></td></tr>
                <tr><td class="lbl">Kasir</td><td>: {{ $penjualan_grosir->user?->name ?? '-' }}</td></tr>
                <tr><td class="lbl">Metode</td><td>: {{ $penjualan_grosir->metode_bayar }}</td></tr>
            </table>
        </div>
    </div>

    {{-- ITEM --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:34px;">No</th>
                <th>Deskripsi Barang</th>
                <th class="c" style="width:60px;">Qty</th>
                <th class="r" style="width:110px;">Harga Satuan</th>
                <th class="r" style="width:120px;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penjualan_grosir->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><b>{{ $item->nama }}</b><br><span style="color:#94a3b8;font-size:11px;font-family:monospace;">{{ $item->kode }}</span></td>
                <td class="c">{{ $item->qty }}</td>
                <td class="r">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                <td class="r"><b>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</b></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTAL --}}
    <div class="inv-total">
        <div class="info">
            @if($penjualan_grosir->catatan)
            <div class="box"><b>Catatan:</b><br>{{ $penjualan_grosir->catatan }}</div>
            @endif
            <div class="box">
                <b>Pembayaran:</b> {{ $penjualan_grosir->metode_bayar }} — Rp {{ number_format($penjualan_grosir->bayar, 0, ',', '.') }}<br>
                @foreach($penjualan_grosir->payments as $pay)
                Pelunasan {{ $pay->tanggal->format('d/m/Y') }} ({{ $pay->metode }}) — Rp {{ number_format($pay->jml, 0, ',', '.') }}<br>
                @endforeach
                @if($sisaPiutang > 0 && $penjualan_grosir->jatuh_tempo)
                <b style="color:#b45309;">Harap lakukan pembayaran sebelum {{ $penjualan_grosir->jatuh_tempo->format('d F Y') }}</b>
                @endif
            </div>
        </div>
        <table class="tbl-total">
            <tr><td>Subtotal</td><td class="r">Rp {{ number_format($penjualan_grosir->subtotal, 0, ',', '.') }}</td></tr>
            <tr><td>Diskon</td><td class="r">− Rp {{ number_format($penjualan_grosir->diskon, 0, ',', '.') }}</td></tr>
            <tr class="garis"><td>TOTAL</td><td class="r">Rp {{ number_format($penjualan_grosir->total, 0, ',', '.') }}</td></tr>
            <tr><td>Dibayar</td><td class="r">Rp {{ number_format($penjualan_grosir->bayar, 0, ',', '.') }}</td></tr>
            @if($sisaPiutang > 0)
            <tr class="piutang"><td>Sisa Piutang</td><td class="r">Rp {{ number_format($sisaPiutang, 0, ',', '.') }}</td></tr>
            @endif
            @if($penjualan_grosir->jatuh_tempo)
            <tr class="tempo"><td>Jatuh Tempo</td><td class="r">{{ $penjualan_grosir->jatuh_tempo->format('d-m-Y') }}</td></tr>
            @endif
        </table>
    </div>

    {{-- TANDA TANGAN --}}
    <div class="ttd">
        <div class="kolom">
            <div class="ket">Hormat Kami,</div>
            <div class="nm">{{ $penjualan_grosir->user?->name ?? $settings['nama_toko'] }}</div>
            <div class="ket">{{ $settings['nama_toko'] }}</div>
        </div>
        <div class="kolom">
            <div class="ket">Diterima oleh,</div>
            <div class="nm">( {{ $penjualan_grosir->nama_pelanggan}} )</div>
            <div class="ket">{{ $penjualan_grosir->pelanggan?->kode ?? 'Pelanggan' }}</div>
        </div>
    </div>

    <div class="foot">
        {{ $settings['nama_toko'] }} · {{ $settings['alamat'] }} · WA {{ $settings['wa'] }}<br>
        Invoice ini dicetak secara otomatis oleh sistem FixPro
    </div>
</div>
</body>
</html>
