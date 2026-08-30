<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->no_invoice }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #111; padding: 30px; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #0d9488; padding-bottom: 14px; margin-bottom: 18px; }
        .toko-name { font-size: 20px; font-weight: bold; color: #0d9488; }
        .toko-sub { font-size: 10px; color: #666; margin-top: 2px; }
        .inv-title { text-align: right; }
        .inv-title h1 { font-size: 18px; color: #0d9488; }
        .inv-title div { font-size: 11px; color: #333; margin-top: 2px; }
        .info { display: flex; gap: 30px; margin-bottom: 16px; }
        .info-box { flex: 1; }
        .info-box h3 { font-size: 11px; text-transform: uppercase; color: #0d9488; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 6px; }
        .info-box p { font-size: 11px; line-height: 1.7; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th { background: #0d9488; color: #fff; font-size: 10px; padding: 7px 8px; text-align: left; }
        td { border-bottom: 1px solid #e5e7eb; font-size: 11px; padding: 6px 8px; }
        .text-right { text-align: right; }
        .summary { margin-left: auto; width: 290px; }
        .summary .row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 11px; }
        .summary .total { border-top: 2px solid #0d9488; margin-top: 6px; padding-top: 8px; font-size: 14px; font-weight: bold; color: #0d9488; }
        .summary .sisa { font-weight: bold; color: #dc2626; }
        .status-box { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; font-size: 11px; margin-bottom: 10px; }
        .status-lunas { background: #dcfce7; color: #166534; }
        .status-piutang { background: #fef2f2; color: #991b1b; }
        .status-batal { background: #f1f5f9; color: #64748b; text-decoration: line-through; }
        .footer { margin-top: 26px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 9px; color: #888; text-align: center; line-height: 1.6; }
        .ttd { display: flex; justify-content: space-between; margin-top: 40px; }
        .ttd div { text-align: center; font-size: 10px; color: #333; }
        .ttd .line { margin-top: 55px; border-top: 1px solid #333; padding-top: 4px; width: 180px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="toko-name">{{ $settings['nama_toko'] }}</div>
            <div class="toko-sub">{{ $settings['tagline'] }}</div>
            <div class="toko-sub">{{ $settings['alamat'] }}</div>
            @if($settings['telp'])<div class="toko-sub">Telp/WA: {{ $settings['telp'] }}</div>@endif
        </div>
        <div class="inv-title">
            <h1>INVOICE SPAREPART</h1>
            <div><b>No:</b> {{ $invoice->no_invoice }}</div>
            <div><b>Tanggal:</b> {{ $invoice->tanggal?->format('d/m/Y H:i') }}</div>
            <div><b>Cabang:</b> {{ $invoice->cabang?->nama ?? '-' }}</div>
        </div>
    </div>

    <div class="status-box {{ $invoice->status === 'Lunas' ? 'status-lunas' : ($invoice->status === 'Dibatalkan' ? 'status-batal' : 'status-piutang') }}">
        {{ strtoupper($invoice->status) }}@if($invoice->isVoid()) — {{ strtoupper($invoice->alasan_void) }}@endif
    </div>

    <div class="info">
        <div class="info-box">
            <h3>Pelanggan</h3>
            <p>
                <b>{{ $invoice->nama_pelanggan ?? 'Umum' }}</b> ({{ $invoice->tipe_pelanggan }})<br>
                @if($invoice->no_wa)WA: {{ $invoice->no_wa }}<br>@endif
                @if($invoice->alamat){{ $invoice->alamat }}<br>@endif
            </p>
        </div>
        <div class="info-box">
            <h3>Info Transaksi</h3>
            <p>
                Kasir: {{ $invoice->kasir?->name ?? '-' }}<br>
                Metode: {{ $invoice->metode_bayar }}<br>
                Gudang/Stok: {{ $invoice->sumberCabang?->nama ?? '-' }}<br>
                @if($invoice->jatuh_tempo)Jatuh Tempo: {{ $invoice->jatuh_tempo->format('d/m/Y') }}<br>@endif
            </p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th><th>Kode</th><th>Nama Sparepart</th><th>Tipe HP</th><th>Tipe LCD</th>
                <th class="text-right">Qty</th><th class="text-right">Harga</th><th class="text-right">Diskon</th><th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $it)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $it->kode }}</td>
                <td>{{ $it->nama }}</td>
                <td>{{ $it->merk_hp ?? '-' }}</td>
                <td>{{ $it->tipe_lcd ?? '-' }}</td>
                <td class="text-right">{{ $it->qty }}</td>
                <td class="text-right">{{ number_format($it->harga_satuan, 0, ',', '.') }}</td>
                <td class="text-right">{{ (float) $it->diskon > 0 ? number_format($it->diskon, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ number_format($it->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="row"><span>Subtotal</span><span>Rp {{ number_format($invoice->subtotal + $invoice->diskon_total, 0, ',', '.') }}</span></div>
        @if((float) $invoice->diskon_total > 0)
        <div class="row"><span>Diskon Nota</span><span>- Rp {{ number_format($invoice->diskon_total, 0, ',', '.') }}</span></div>
        @endif
        @if((float) $invoice->total_retur > 0)
        <div class="row"><span>Retur</span><span>- Rp {{ number_format($invoice->total_retur, 0, ',', '.') }}</span></div>
        @endif
        <div class="row total"><span>TOTAL</span><span>Rp {{ number_format($invoice->total, 0, ',', '.') }}</span></div>
        <div class="row"><span>Dibayar</span><span>Rp {{ number_format($invoice->dibayar, 0, ',', '.') }}</span></div>
        <div class="row sisa"><span>SISA</span><span>Rp {{ number_format($invoice->sisa, 0, ',', '.') }}</span></div>
    </div>

    <div class="ttd">
        <div><div class="line">Pelanggan</div></div>
        <div><div class="line">Hormat Kami,<br>{{ $invoice->kasir?->name ?? '-' }}</div></div>
    </div>

    <div class="footer">
        Invoice ini sah dan diproses otomatis oleh sistem FIXPRO · Stok telah disesuaikan otomatis sesuai cabang/gudang.<br>
        Barang yang sudah dibeli silakan cek sebelum meninggalkan toko. Terima kasih atas kepercayaan Anda!
    </div>
</body>
</html>
