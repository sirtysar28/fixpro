<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Servis - {{ $servis->kode }}</title>
    <style>
        @page { margin: 5mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.5; max-width: 80mm; margin: 0 auto; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 4px 0; }
        .row { display: flex; justify-content: space-between; }
        .mb-2 { margin-bottom: 2px; }
        .mb-4 { margin-bottom: 4px; }
        .mb-8 { margin-bottom: 8px; }
        .big { font-size: 16px; font-weight: bold; }
        h1 { font-size: 18px; text-align: center; }
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
