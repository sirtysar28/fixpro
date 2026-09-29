<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->no_invoice }}</title>
    <style>
        @php
            $paperWidth   = (int) ($thermal_width ?? 58);
            $receiptWidth = (int) ($receipt_width ?? ($paperWidth === 80 ? 72 : 48));
        @endphp
        @page { size: {{ $paperWidth }}mm auto; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; max-width: 100%; }
        html, body { width: {{ $paperWidth }}mm; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            line-height: 1.25;
            width: {{ $receiptWidth }}mm;
            margin: 0 auto;
            padding: 2mm 0;
            color: #000;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .text-sm { font-size: 8px; }
        .text-xs { font-size: 7px; }
        .text-xl { font-size: 12px; }
        .divider { border-top: 1px dashed #000; margin: 3px 0; }
        .row { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 2px; padding: 1px 0; }
        .row > span { max-width: 100%; word-break: break-word; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table td { word-break: break-word; }
        .item-name { font-weight: bold; word-break: break-word; }
        .grand-total { font-weight: bold; font-size: 11px; border-top: 2px solid #000; padding-top: 4px; margin-top: 4px; }
        @media print { body { width: {{ $receiptWidth }}mm; } }
    </style>
</head>
<body>
    <div class="text-center">
        <div class="text-xl bold">{{ $settings['nama_toko'] }}</div>
        @if($settings['alamat'])<div class="text-xs">{{ $settings['alamat'] }}</div>@endif
        @if($settings['telp'])<div class="text-xs">{{ $settings['telp'] }}</div>@endif
    </div>

    <div class="divider"></div>
    <div class="text-center text-sm bold">INVOICE SPAREPART</div>
    <div class="divider"></div>

    <div class="row"><span>No</span><span>{{ $invoice->no_invoice }}</span></div>
    <div class="row"><span>Tanggal</span><span>{{ $invoice->tanggal?->format('d/m/y H:i') }}</span></div>
    <div class="row"><span>Kasir</span><span>{{ $invoice->kasir?->name ?? '-' }}</span></div>
    <div class="row"><span>Cabang</span><span>{{ $invoice->cabang?->nama ?? '-' }}</span></div>
    <div class="row"><span>Pelanggan</span><span>{{ $invoice->nama_pelanggan ?? 'Umum' }}</span></div>
    <div class="row"><span>Tipe</span><span>{{ $invoice->tipe_pelanggan }}</span></div>

    <div class="divider"></div>
    @foreach($invoice->items as $it)
    <div class="item-name">{{ $it->nama }}</div>
    <div class="text-xs">
        {{ $it->kode }}@if($it->merk_hp) · {{ $it->merk_hp }}@endif @if($it->tipe_lcd) · {{ $it->tipe_lcd }}@endif
    </div>
    <div class="row text-sm">
        <span>{{ $it->qty }} x {{ number_format($it->harga_satuan, 0, ',', '.') }} @if($it->jenis_harga !== 'retail')[{{ strtoupper($it->labelJenisHarga()) }}]@endif</span>
        <span>{{ number_format($it->subtotal, 0, ',', '.') }}</span>
    </div>
    @endforeach

    <div class="divider"></div>
    <div class="row"><span>Subtotal</span><span>{{ number_format($invoice->subtotal + $invoice->diskon_total, 0, ',', '.') }}</span></div>
    @if((float) $invoice->diskon_total > 0)
    <div class="row"><span>Diskon</span><span>-{{ number_format($invoice->diskon_total, 0, ',', '.') }}</span></div>
    @endif
    @if((float) $invoice->total_retur > 0)
    <div class="row"><span>Retur</span><span>-{{ number_format($invoice->total_retur, 0, ',', '.') }}</span></div>
    @endif
    <div class="row grand-total"><span>TOTAL</span><span>{{ number_format($invoice->total, 0, ',', '.') }}</span></div>
    <div class="row"><span>Bayar ({{ $invoice->metode_bayar }})</span><span>{{ number_format($invoice->dibayar, 0, ',', '.') }}</span></div>
    @if((float) $invoice->sisa > 0)
    <div class="row bold"><span>SISA PIUTANG</span><span>{{ number_format($invoice->sisa, 0, ',', '.') }}</span></div>
    @if($invoice->jatuh_tempo)<div class="row text-xs"><span>Jatuh tempo</span><span>{{ $invoice->jatuh_tempo->format('d/m/Y') }}</span></div>@endif
    @else
    <div class="text-center bold">*** LUNAS ***</div>
    @endif

    @if($invoice->isVoid())
    <div class="text-center bold" style="font-size:11px;margin-top:6px">** DIBATALKAN **</div>
    @endif

    <div class="divider"></div>
    <div class="text-center text-xs">
        Terima kasih sudah berbelanja!<br>
        Barang yang dibeli silakan dicek dulu ^_^
    </div>

    <script>
        window.onload = function () { setTimeout(function () { window.print(); }, 300); };
    </script>
</body>
</html>
