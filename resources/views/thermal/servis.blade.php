<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Servis {{ $servis->kode }}</title>
    <style>
        @page {
            size: {{ $settings['thermal_width'] ?? 80 }}mm auto;
            margin: 2mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            width: {{ $settings['thermal_width'] ?? 80 }}mm;
            padding: 2mm;
            color: #000;
        }

        /* HEADER */
        .brand { font-size: 16px; font-weight: bold; letter-spacing: 2px; text-align: center; }
        .tagline { font-size: 8px; letter-spacing: 2px; text-align: center; }
        .addr { font-size: 8px; text-align: center; }
        .telp { font-size: 9px; text-align: center; font-weight: bold; }

        /* DIVIDERS */
        .divider { border-top: 1px dashed #000; margin: 4px 0; }
        .divider-double { border-top: 2px solid #000; margin: 4px 0; }

        /* SECTION TITLE */
        .section-title { text-align: center; font-weight: bold; font-size: 10px; letter-spacing: 2px; padding: 1px 0; }

        /* DATA ROWS */
        .row { display: flex; padding: 1px 0; font-size: 10px; }
        .row .lbl { width: 105px; }
        .row .val { flex: 1; font-weight: bold; }

        /* PAYMENT TOTALS */
        .pay-row { display: flex; padding: 1px 0; font-size: 10px; }
        .pay-row .lbl { flex: 1; }
        .pay-row .val { font-weight: bold; text-align: right; }
        .pay-row.sisa { font-size: 13px; font-weight: bold; }

        /* FOOTER */
        .foot-center { text-align: center; font-size: 9px; padding: 1px 0; }
        .foot-bold { font-weight: bold; }
        .foot-thx { font-size: 10px; font-weight: bold; letter-spacing: 1px; }
        .slogan { font-size: 9px; font-style: italic; margin-top: 1px; }
        .stamp { font-size: 8px; text-align: center; margin-top: 4px; }

        @media print { body { width: {{ $settings['thermal_width'] ?? 80 }}mm; } }
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
<div class="row"><span class="lbl">KODE SERVIS</span><span class="val">: {{ $servis->kode }}</span></div>
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

<div class="row"><span class="lbl">MASA GARANSI</span><span class="val">: {{ $garansi > 0 ? $garansi . ' HARI' : 'TANPA GARANSI' }}</span></div>
<div class="row"><span class="lbl">BERLAKU S/D</span><span class="val">: {{ $servis->tanggal_garansi?->format('d/m/Y') ?? '-' }}</span></div>

{{-- ===== FOOTER ===== --}}
<div class="divider-double"></div>

<div class="foot-center foot-bold">NOTA DIGITAL / PDF</div>
<div class="foot-center">MOHON DISIMPAN DENGAN BAIK</div>

<div class="divider"></div>

<div class="foot-center foot-thx">TERIMA KASIH TELAH MEMILIH</div>
<div class="brand" style="font-size:13px;margin-top:1px">{{ strtoupper($settings['nama_toko']) }}</div>
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
