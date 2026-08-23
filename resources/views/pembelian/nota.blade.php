<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Pembelian - {{ $pembelian->kode }}</title>
    <style>
        @page { size: A5; margin: 10mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1e293b; background: #f1f5f9; }
        .nota { max-width: 148mm; margin: 0 auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #1e293b; padding-bottom: 12px; margin-bottom: 14px; }
        .toko { display: flex; gap: 10px; align-items: center; }
        .toko-logo { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; background: #f1f5f9; }
        .toko-logo-fallback { width: 48px; height: 48px; border-radius: 8px; background: #1e293b; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px; }
        .toko-nama { font-size: 17px; font-weight: 800; letter-spacing: .5px; }
        .toko-alamat { font-size: 10px; color: #64748b; max-width: 220px; }
        .nota-title { text-align: right; }
        .nota-title h1 { font-size: 16px; letter-spacing: 2px; }
        .nota-kode { font-size: 13px; font-weight: 700; color: #2563eb; }
        table { width: 100%; border-collapse: collapse; }
        .info { margin-bottom: 14px; display: flex; gap: 24px; }
        .info-box { flex: 1; background: #f8fafc; border-radius: 6px; padding: 10px 12px; }
        .info-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #94a3b8; margin-bottom: 3px; letter-spacing: .5px; }
        .info-val { font-size: 11.5px; font-weight: 600; }
        .items { margin-bottom: 14px; }
        .items th { background: #1e293b; color: #fff; font-size: 10px; text-transform: uppercase; padding: 7px 8px; text-align: left; }
        .items td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        .items .num { text-align: right; }
        .items .ctr { text-align: center; }
        .totals { display: flex; justify-content: flex-end; margin-bottom: 14px; }
        .totals table { width: 62%; }
        .totals td { padding: 4px 8px; font-size: 11.5px; }
        .totals .grand td { border-top: 2px solid #1e293b; font-weight: 800; font-size: 13px; padding-top: 7px; }
        .totals .neg { color: #dc2626; }
        .totals .pos { color: #16a34a; }
        .status-bar { text-align: center; margin-bottom: 14px; }
        .status-pill { display: inline-block; padding: 5px 22px; border-radius: 20px; font-weight: 800; font-size: 12px; letter-spacing: 1px; }
        .ttd { display: flex; justify-content: space-between; margin-top: 26px; }
        .ttd-box { text-align: center; width: 40%; }
        .ttd-box .line { margin-top: 52px; border-top: 1px dashed #94a3b8; padding-top: 5px; font-size: 10.5px; font-weight: 600; }
        .footer { margin-top: 18px; text-align: center; font-size: 9.5px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        .catatan { background: #fffbeb; border-left: 3px solid #f59e0b; padding: 8px 12px; border-radius: 4px; font-size: 10.5px; color: #78350f; margin-bottom: 14px; white-space: pre-wrap; }
        @media print { body { background: #fff; } .nota { box-shadow: none; border-radius: 0; max-width: none; } .no-print { display: none !important; } }
        .toolbar { max-width: 148mm; margin: 12px auto 0; display: flex; gap: 8px; }
        .toolbar button, .toolbar a { padding: 8px 18px; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 12px; }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-back { background: #e2e8f0; color: #334155; }
    </style>
</head>
<body>
<div class="toolbar no-print">
    <button onclick="window.print()" class="btn-print"><i>🖨</i> Cetak Nota</button>
    <a href="{{ url()->previous() ?: route('pembelian.show', $pembelian) }}" class="btn-back">← Kembali</a>
</div>

<div class="nota" style="margin-top:12px">
    {{-- ===== Header: identitas toko ===== --}}
    <div class="header">
        <div class="toko">
            @if(!empty($settings['logo']) && str_starts_with($settings['logo'], 'storage/'))
                <img src="{{ asset($settings['logo']) }}" alt="logo" class="toko-logo" onerror="this.style.display='none'">
            @else
                <div class="toko-logo-fallback">{{ strtoupper(substr($settings['nama_toko'], 0, 1)) }}</div>
            @endif
            <div>
                <div class="toko-nama">{{ strtoupper($settings['nama_toko']) }}</div>
                @if($settings['alamat'])<div class="toko-alamat">{{ $settings['alamat'] }}</div>@endif
                @if($settings['telp'])<div class="toko-alamat">Telp: {{ $settings['telp'] }}</div>@endif
                @if($cabang)<div class="toko-alamat">Cabang: {{ $cabang->nama }}</div>@endif
            </div>
        </div>
        <div class="nota-title">
            <h1>NOTA PEMBELIAN</h1>
            <div class="nota-kode">{{ $pembelian->kode }}</div>
        </div>
    </div>

    {{-- ===== Info supplier & transaksi ===== --}}
    <div class="info">
        <div class="info-box">
            <div class="info-label">Supplier</div>
            <div class="info-val">{{ $pembelian->supplier_nama }}</div>
            @if($pembelian->supplier_kontak)<div style="font-size:10.5px">{{ $pembelian->supplier_kontak }}</div>@endif
            @if($pembelian->supplier_alamat)<div style="font-size:10px;color:#64748b">{{ $pembelian->supplier_alamat }}</div>@endif
        </div>
        <div class="info-box">
            <div class="info-label">Tanggal Pembelian</div>
            <div class="info-val">{{ $pembelian->tanggal?->format('d/m/Y') }}</div>
            <div class="info-label" style="margin-top:6px">Jatuh Tempo</div>
            <div class="info-val">{{ $pembelian->tanggal_jatuh_tempo?->format('d/m/Y') ?? '-' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">Metode</div>
            <div class="info-val">{{ $pembelian->metode_bayar }}</div>
            <div class="info-label" style="margin-top:6px">Dibuat Oleh</div>
            <div class="info-val">{{ $pembelian->user?->name ?? '-' }}</div>
        </div>
    </div>

    {{-- ===== Daftar barang ===== --}}
    <table class="items">
        <thead>
            <tr><th>#</th><th style="width:34%">Nama Barang</th><th>Kode</th><th class="ctr">Qty</th><th class="num">Harga</th><th class="num">Diskon</th><th class="num">Subtotal</th></tr>
        </thead>
        <tbody>
            @foreach(($pembelian->items ?? []) as $i => $it)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $it['nama'] }}</td>
                <td style="font-size:10px;color:#64748b">{{ $it['kode'] ?? '-' }}</td>
                <td class="ctr">{{ $it['qty'] }}</td>
                <td class="num">{{ number_format($it['harga_beli'], 0, ',', '.') }}</td>
                <td class="num neg">{{ (float)($it['diskon_item'] ?? 0) > 0 ? number_format($it['diskon_item'], 0, ',', '.') : '-' }}</td>
                <td class="num" style="font-weight:600">{{ number_format($it['subtotal'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($pembelian->catatan)
    <div class="catatan"><strong>Catatan:</strong> {{ $pembelian->catatan }}</div>
    @endif

    {{-- ===== Total ===== --}}
    <div class="totals">
        <table>
            <tr><td>Subtotal</td><td class="num">{{ number_format($pembelian->subtotal, 0, ',', '.') }}</td></tr>
            @if((float)$pembelian->diskon_persen > 0 || (float)$pembelian->diskon_nominal > 0)
            <tr><td>Diskon ({{ $pembelian->diskon_persen }}% + {{ number_format($pembelian->diskon_nominal, 0, ',', '.') }})</td><td class="num neg">- {{ number_format((float)$pembelian->subtotal * (float)$pembelian->diskon_persen / 100 + (float)$pembelian->diskon_nominal, 0, ',', '.') }}</td></tr>
            @endif
            @if((float)$pembelian->biaya_tambahan > 0)
            <tr><td>Biaya Tambahan</td><td class="num">+ {{ number_format($pembelian->biaya_tambahan, 0, ',', '.') }}</td></tr>
            @endif
            @if((float)$pembelian->ongkir > 0)
            <tr><td>Ongkir</td><td class="num">+ {{ number_format($pembelian->ongkir, 0, ',', '.') }}</td></tr>
            @endif
            <tr class="grand"><td>TOTAL PEMBELIAN</td><td class="num">Rp {{ number_format($pembelian->total, 0, ',', '.') }}</td></tr>
            @if((float)$pembelian->total_retur > 0)
            <tr><td>Retur</td><td class="num neg">- {{ number_format($pembelian->total_retur, 0, ',', '.') }}</td></tr>
            <tr class="grand"><td>TOTAL AKHIR</td><td class="num">Rp {{ number_format($pembelian->totalAkhir(), 0, ',', '.') }}</td></tr>
            @endif
            <tr><td>Dibayar</td><td class="num pos">{{ number_format($pembelian->dibayar, 0, ',', '.') }}</td></tr>
            <tr class="grand"><td>SISA HUTANG</td><td class="num {{ $pembelian->sisaHutang() > 0 ? 'neg' : 'pos' }}">Rp {{ number_format($pembelian->sisaHutang(), 0, ',', '.') }}</td></tr>
        </table>
    </div>

    {{-- ===== Status pembayaran ===== --}}
    @php $sb = $pembelian->statusBadge(); @endphp
    <div class="status-bar">
        <span class="status-pill" style="background:{{ $sb['bg'] }};color:{{ $sb['color'] }}">
            {{ strtoupper($sb['label']) }} — {{ strtoupper($pembelian->status_transaksi) }}
        </span>
    </div>

    {{-- ===== Tanda tangan ===== --}}
    <div class="ttd">
        <div class="ttd-box">
            <div style="font-size:10.5px">Penerima,</div>
            <div class="line">{{ $pembelian->user?->name ?? '....................' }}</div>
        </div>
        <div class="ttd-box">
            <div style="font-size:10.5px">Hormat kami,<br>(Supplier)</div>
            <div class="line">....................</div>
        </div>
    </div>

    <div class="footer">
        Dicetak {{ now()->format('d/m/Y H:i') }} • {{ $pembelian->kode }} • Dokumen ini dicetak otomatis oleh sistem
    </div>
</div>
</body>
</html>
