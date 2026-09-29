<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Servis - {{ $servis->kode }}</title>
    <style>
        @php
            // Default printer mini 58mm; area cetak aman 48mm agar teks tidak terpotong
            $paperWidth   = (int) (\App\Models\Setting::get('thermal_width') ?? 58);
            if (!in_array($paperWidth, [58, 80])) { $paperWidth = 58; }
            $receiptWidth = $paperWidth === 80 ? 72 : 48;
        @endphp
        @page { size: {{ $paperWidth }}mm auto; margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; max-width: 100%; }
        html, body { width: {{ $paperWidth }}mm; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            line-height: 1.3;
            width: {{ $receiptWidth }}mm;
            margin: 0 auto;
            padding: 2mm 0;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 3px 0; }
        .row { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 2px; }
        .row > span { max-width: 100%; word-break: break-word; }
        .mb-2 { margin-bottom: 2px; }
        .mb-4 { margin-bottom: 4px; }
        .mb-8 { margin-bottom: 8px; }
        .big { font-size: 12px; font-weight: bold; }
        h1 { font-size: 12px; text-align: center; }
        @media print { body { width: {{ $receiptWidth }}mm; } }
    </style>
</head>
<body onload="window.print()">
    <div class="center mb-8">
        <div class="bold big">{{ \App\Models\Setting::get('nama_toko', 'FIXPRO') }}</div>
        <div>{{ \App\Models\Setting::get('alamat', 'Service HP Profesional') }}</div>
        <div>{{ \App\Models\Setting::get('telp', '') }}</div>
    </div>

    <div class="line"></div>

    <div class="center bold mb-4">STRUK SERVIS</div>

    <div class="line"></div>

    <div class="row mb-2"><span>Kode</span><span class="bold">{{ $servis->kode }}</span></div>
    <div class="row mb-2"><span>Tanggal</span><span>{{ $servis->tanggal?->format('d/m/Y') }}</span></div>
    <div class="row mb-2"><span>Cabang</span><span>{{ $servis->cabang?->nama ?? '-' }}</span></div>

    <div class="line"></div>

    <div class="bold mb-2">PELANGGAN:</div>
    <div>{{ $servis->pelanggan?->nama ?? '-' }}</div>
    <div>{{ $servis->pelanggan?->no_hp ?? '-' }}</div>

    <div class="line"></div>

    <div class="bold mb-2">PERANGKAT:</div>
    <div class="row mb-2"><span>Perangkat</span><span>{{ $servis->perangkat }}</span></div>
    <div class="row mb-2"><span>Tipe</span><span>{{ $servis->tipe }}</span></div>
    @if($servis->imei)<div class="row mb-2"><span>IMEI</span><span>{{ $servis->imei }}</span></div>@endif
    <div class="row mb-2"><span>Keluhan</span><span>{{ $servis->keluhan }}</span></div>
    <div class="row mb-2"><span>Status</span><span>{{ $servis->status }}</span></div>
    <div class="row mb-2"><span>Teknisi</span><span>{{ $servis->teknisi?->nama ?? '-' }}</span></div>

    <div class="line"></div>

    <div class="bold mb-2">BIAYA:</div>
    <div class="row mb-2"><span>Biaya</span><span>{{ formatRp($servis->biaya) }}</span></div>
    <div class="row mb-2"><span>DP</span><span>{{ formatRp($servis->dp) }}</span></div>
    <div class="row bold mb-2"><span>SISA</span><span>{{ formatRp($servis->biaya - $servis->dp) }}</span></div>

    @if($servis->spareparts && count($servis->spareparts) > 0)
    <div class="line"></div>
    <div class="bold mb-2">SPAREPART:</div>
    @foreach($servis->spareparts as $sp)
    <div class="row mb-2"><span>{{ $sp['nama'] ?? '-' }}</span><span>{{ formatRp($sp['harga'] ?? 0) }}</span></div>
    @endforeach
    @endif

    <div class="line"></div>

    <div class="row mb-2"><span>Garansi</span><span>{{ $servis->garansi }} hari</span></div>
    @if($servis->tanggal_garansi)<div class="row mb-2"><span>s/d</span><span>{{ $servis->tanggal_garansi->format('d/m/Y') }}</span></div>@endif

    <div class="line"></div>

    <div class="center" style="margin-top:8px;font-size:10px">
        <div>Terima kasih telah mempercayakan</div>
        <div>servis HP Anda kepada kami!</div>
        <div style="margin-top:4px">Simpan struk ini sebagai bukti servis</div>
    </div>

    <script>
        setTimeout(function() { window.close(); }, 500);
    </script>
</body>
</html>
