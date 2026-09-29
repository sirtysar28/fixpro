<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Servis {{ $servis->kode }}</title>
    <style>
        @php
            // Lebar kertas thermal (default 58mm) & area cetak aman biar teks tidak terpotong
            $paperWidth  = (int) ($settings['thermal_width'] ?? 58);
            $receiptWidth = (int) ($settings['receipt_width'] ?? ($paperWidth === 80 ? 72 : 48));
        @endphp
        @page {
            size: {{ $paperWidth }}mm auto;
            margin: 0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; max-width: 100%; }
        html, body { width: {{ $paperWidth }}mm; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            line-height: 1.25;
            color: #000;
            width: {{ $receiptWidth }}mm;
            margin: 0 auto;
            padding: 2mm 0;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* HEADER */
        .brand { font-size: 12px; font-weight: bold; letter-spacing: 1px; text-align: center; }
        .tagline { font-size: 7px; letter-spacing: 1px; text-align: center; }
        .addr { font-size: 7px; text-align: center; }
        .telp { font-size: 8px; text-align: center; font-weight: bold; }

        /* DIVIDERS */
        .divider { border-top: 1px dashed #000; margin: 3px 0; }
        .divider-double { border-top: 2px solid #000; margin: 3px 0; }

        /* SECTION TITLE */
        .section-title { text-align: center; font-weight: bold; font-size: 9px; letter-spacing: 1px; padding: 1px 0; }

        /* DATA ROWS */
        .row { display: flex; flex-wrap: wrap; padding: 1px 0; font-size: 9px; }
        .row .lbl { flex: 0 0 54px; }
        .row .val { flex: 1 1 60px; font-weight: bold; word-break: break-word; }

        /* PAYMENT TOTALS */
        .pay-row { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 2px; padding: 1px 0; font-size: 9px; }
        .pay-row .lbl { flex: 0 1 auto; }
        .pay-row .val { flex: 0 1 auto; font-weight: bold; text-align: right; word-break: break-word; }
        .pay-row.sisa { font-size: 11px; font-weight: bold; }

        /* FOOTER */
        .foot-center { text-align: center; font-size: 8px; padding: 1px 0; }
        .foot-bold { font-weight: bold; }
        .foot-thx { font-size: 9px; font-weight: bold; letter-spacing: 0; }
        .slogan { font-size: 8px; font-style: italic; margin-top: 1px; }
        .stamp { font-size: 7px; text-align: center; margin-top: 4px; }

        @media print { body { width: {{ $receiptWidth }}mm; } }
    </style>
</head>
<body>

@php
    $statusTitle = $servis->status === 'Selesai' ? 'SERVIS SELESAI' : 'NOTA SERVIS';
    $biaya  = (float) $servis->biaya;
    $dp     = (float) $servis->dp;
    $sisa   = max(0, $biaya - $dp);
    $garansi= (int) ($servis->garansi ?? 0);
@endphp

{{-- ===== HEADER ===== --}}
<div class="brand">{{ strtoupper($settings['nama_toko']) }}</div>
@if(!empty($settings['tagline']))
<div class="tagline">{{ strtoupper($settings['tagline']) }}</div>
@endif
@if(!empty($settings['alamat']))
<div class="addr">{{ $settings['alamat'] }}</div>
@endif
@if(!empty($settings['telp']))
<div class="telp">{{ $settings['telp'] }}</div>
@endif

<div class="divider-double"></div>
<div class="section-title">{{ $statusTitle }}</div>
<div class="divider-double"></div>

{{-- ===== INFO SERVIS ===== --}}
<div class="row"><span class="lbl">TANGGAL</span><span class="val">: {{ $servis->tanggal?->format('d/m/Y') }}</span></div>
<div class="row"><span class="lbl">KODE</span><span class="val">: {{ $servis->kode }}</span></div>
<div class="row"><span class="lbl">PELANGGAN</span><span class="val">: {{ $servis->pelanggan?->nama ?? '-' }}</span></div>
<div class="row"><span class="lbl">PERANGKAT</span><span class="val">: {{ $servis->perangkat }}</span></div>
@if($servis->keluhan)
<div class="row"><span class="lbl">KELUHAN</span><span class="val">: {{ $servis->keluhan }}</span></div>
@endif
<div class="row"><span class="lbl">TEKNISI</span><span class="val">: {{ $servis->teknisi?->nama ?? '-' }}</span></div>

{{-- ===== PEMBAYARAN ===== --}}
<div class="divider"></div>
<div class="section-title">PEMBAYARAN</div>
<div class="divider"></div>

<div class="pay-row"><span class="lbl">BIAYA SERVIS :</span><span class="val">Rp {{ number_format($biaya, 0, ',', '.') }}</span></div>
@if($dp > 0)
<div class="pay-row"><span class="lbl">DP DIBAYAR :</span><span class="val">- Rp {{ number_format($dp, 0, ',', '.') }}</span></div>
@endif
<div class="pay-row sisa"><span class="lbl">SISA BAYAR :</span><span class="val">Rp {{ number_format($sisa, 0, ',', '.') }}</span></div>

{{-- ===== GARANSI ===== --}}
<div class="divider"></div>
<div class="section-title">GARANSI</div>
<div class="divider"></div>

<div class="row"><span class="lbl">GARANSI</span><span class="val">: {{ $garansi > 0 ? $garansi . ' HARI' : 'TANPA GARANSI' }}</span></div>
<div class="row"><span class="lbl">BERLAKU</span><span class="val">: {{ $servis->tanggal_garansi?->format('d/m/Y') ?? '-' }}</span></div>

{{-- ===== FOOTER ===== --}}
<div class="divider-double"></div>

<div class="foot-center foot-bold">NOTA DIGITAL / PDF</div>
<div class="foot-center">MOHON DISIMPAN BAIK</div>

<div class="divider"></div>

<div class="foot-center foot-thx">TERIMA KASIH TELAH MEMILIH</div>
<div class="brand" style="font-size:11px;margin-top:1px">{{ strtoupper($settings['nama_toko']) }}</div>
@if(!empty($settings['slogan']))
<div class="slogan">{{ $settings['slogan'] }}</div>
@endif

<div class="divider-double"></div>
@if(!empty($settings['telp']))
<div class="telp">{{ $settings['telp'] }}</div>
@endif
@if(!empty($settings['alamat']))
<div class="addr">{{ $settings['alamat'] }}</div>
@endif
<div class="divider-double"></div>

<div class="stamp">Dicetak: {{ now()->format('d/m/Y H:i') }}</div>

<script>
    window.onload = function() { window.print(); };
</script>
</body>
</html>
