<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Nota {{ $penjualan_grosir->no_nota }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Courier New', monospace; background: #f1f5f9; color: #000; font-size: 12px; }
    .nota { width: 80mm; margin: 20px auto; background: #fff; padding: 12px; }

    /* Header */
    .n-head { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 8px; }
    .n-head .logo { width: 54px; height: 54px; object-fit: cover; border-radius: 8px; margin: 0 auto 6px; display: block; }
    .n-head h1 { font-size: 15px; letter-spacing: 1px; }
    .n-head p { font-size: 10.5px; line-height: 1.5; }
    .n-judul { background: #000; color: #fff; text-align: center; font-weight: bold; font-size: 12.5px; padding: 5px 0; letter-spacing: 2px; margin: 8px 0; }

    .n-row { display: flex; justify-content: space-between; font-size: 11px; padding: 1.5px 0; }
    .n-row b { font-weight: bold; }

    .n-pelanggan { border: 1px dashed #000; padding: 7px 8px; margin: 8px 0; }
    .n-pelanggan .t { font-weight: bold; font-size: 11px; margin-bottom: 3px; }
    .n-pelanggan .r { display: flex; justify-content: space-between; font-size: 10.5px; }
    .n-pelanggan .r span:first-child { color: #333; }

    table.items { width: 100%; border-collapse: collapse; font-size: 10.5px; }
    table.items th { border-bottom: 1.5px solid #000; padding: 4px 2px; text-align: left; font-size: 10.5px; }
    table.items td { padding: 4px 2px; vertical-align: top; border-bottom: 1px dotted #999; }
    table.items .r { text-align: right; }
    table.items .c { text-align: center; }

    .n-total { margin-top: 8px; font-size: 11.5px; }
    .n-total .n-row { padding: 2px 0; }
    .n-total .garis { border-top: 2px dashed #000; margin: 5px 0; }
    .n-total .grand { font-size: 14px; font-weight: bold; }
    .n-total .piutang-box { border: 1.5px dashed #000; padding: 6px 8px; margin-top: 6px; }
    .n-total .piutang-box .r { display: flex; justify-content: space-between; }

    .n-foot { margin-top: 12px; text-align: center; font-size: 9.5px; line-height: 1.6; border-top: 1px dashed #000; padding-top: 8px; }
    .n-ttd { margin-top: 16px; display: flex; justify-content: space-between; text-align: center; font-size: 10.5px; }
    .n-ttd .kolom { width: 45%; }
    .n-ttd .garis-ttd { margin-top: 34px; border-top: 1px solid #000; padding-top: 3px; }

    .btn-print { display: block; width: 80mm; margin: 0 auto 0; padding: 10px; background: #0d9488; color: #fff; border: none; font-family: inherit; font-weight: bold; font-size: 13px; cursor: pointer; border-radius: 8px 8px 0 0; }
    @media print {
        body { background: #fff; }
        .btn-print { display: none; }
        .nota { margin: 0; width: 100%; }
        @page { margin: 4mm; }
    }
</style>
</head>
<body>
<button class="btn-print" onclick="window.print()">🖨️ Cetak Nota Grosir</button>

<div class="nota">
    {{-- HEADER --}}
    <div class="n-head">
        @if(!empty($settings['logo']))<img src="{{ $settings['logo'] }}" class="logo" alt="logo">@endif
        <h1>{{ strtoupper($settings['nama_toko']) }}</h1>
        <p>{{ $settings['alamat'] }}</p>
        <p>WA: {{ $settings['wa'] }} · Telp: {{ $settings['telp'] }}</p>
    </div>

    <div class="n-judul">NOTA PENJUALAN GROSIR</div>

    <div class="n-row"><span>No Nota</span><b>{{ $penjualan_grosir->no_nota }}</b></div>
    <div class="n-row"><span>Tanggal & Jam</span><b>{{ $penjualan_grosir->tanggal->format('d-m-Y H:i') }}</b></div>
    <div class="n-row"><span>Sumber Stok</span><b>{{ $penjualan_grosir->sumberCabang?->nama ?? '-' }} ({{ $penjualan_grosir->sumberCabang?->isGudang() ? 'Gudang' : 'Toko' }})</b></div>
    <div class="n-row"><span>Kasir</span><b>{{ $penjualan_grosir->user?->name ?? '-' }}</b></div>

    {{-- DATA PELANGGAN --}}
    <div class="n-pelanggan">
        <div class="t">PELANGGAN</div>
        <div class="r"><span>Nama</span><b>{{ $penjualan_grosir->nama_pelanggan }}</b></div>
        <div class="r"><span>Kode</span><span>{{ $penjualan_grosir->pelanggan?->kode ?? '-' }}</span></div>
        <div class="r"><span>Level Harga</span><span>{{ $penjualan_grosir->labelLevelHarga() }}</span></div>
        <div class="r"><span>No. HP</span><span>{{ $penjualan_grosir->pelanggan?->no_hp ?? '-' }}</span></div>
        @if($penjualan_grosir->alamat_kirim)
        <div class="r"><span>Kirim ke</span><span style="text-align:right;max-width:55%;">{{ \Illuminate\Support\Str::limit($penjualan_grosir->alamat_kirim, 60) }}</span></div>
        @endif
    </div>

    {{-- ITEM --}}
    <table class="items">
        <thead>
            <tr><th>Barang</th><th class="c">Qty</th><th class="r">Harga</th><th class="r">Total</th></tr>
        </thead>
        <tbody>
            @foreach($penjualan_grosir->items as $item)
            <tr>
                <td>{{ \Illuminate\Support\Str::limit($item->nama, 24) }}</td>
                <td class="c">{{ $item->qty }}</td>
                <td class="r">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                <td class="r">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTAL --}}
    <div class="n-total">
        <div class="n-row"><span>Subtotal Barang</span><b>Rp {{ number_format($penjualan_grosir->subtotal, 0, ',', '.') }}</b></div>
        <div class="n-row"><span>Diskon Transaksi</span><span>Rp {{ number_format($penjualan_grosir->diskon, 0, ',', '.') }}</span></div>
        <div class="garis"></div>
        <div class="n-row grand"><span>TOTAL</span><span>Rp {{ number_format($penjualan_grosir->total, 0, ',', '.') }}</span></div>
        <div class="n-row"><span>Pembayaran</span><span>Rp {{ number_format($penjualan_grosir->bayar, 0, ',', '.') }}</span></div>

        @if($sisaPiutang > 0)
        <div class="piutang-box">
            <div class="r"><span><b>PIUTANG</b></span><b>Rp {{ number_format($sisaPiutang, 0, ',', '.') }}</b></div>
            @if($penjualan_grosir->jatuh_tempo)
            <div class="r"><span>Jatuh Tempo</span><b>{{ $penjualan_grosir->jatuh_tempo->format('d-m-Y') }}</b></div>
            @endif
        </div>
        @endif
    </div>

    @if($penjualan_grosir->catatan)
    <div style="font-size:10px;margin-top:6px;">Catatan: {{ $penjualan_grosir->catatan }}</div>
    @endif

    <div class="n-ttd">
        <div class="kolom">Hormat Kami<div class="garis-ttd">{{ $settings['nama_toko'] }}</div></div>
        <div class="kolom">Penerima<div class="garis-ttd">(..................)</div></div>
    </div>

    <div class="n-foot">
        ~ Terima kasih atas kerjasama Anda ~<br>
        Barang yang sudah dibeli dapat ditukar dengan syarat & ketentuan berlaku<br>
        -- Dicetak {{ now()->format('d-m-Y H:i') }} --
    </div>
</div>

<script>
    // Auto print jika dari POS (parameter ?print=1)
    if (new URLSearchParams(window.location.search).get('print') === '1') {
        window.print();
    }
</script>
</body>
</html>
