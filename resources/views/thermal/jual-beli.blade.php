<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Jual Beli HP</title>
    <style>
        @page {
            size: {{ $settings['thermal_width'] ?? 80 }}mm auto;
            margin: 2mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            width: {{ $settings['thermal_width'] ?? 80 }}mm;
            padding: 2mm;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .text-sm { font-size: 10px; }
        .text-xs { font-size: 9px; }
        .text-xl { font-size: 16px; }
        .divider { border-top: 1px dashed #000; margin: 4px 0; }
        .divider-double { border-top: 2px solid #000; margin: 4px 0; }
        .row { display: flex; justify-content: space-between; padding: 1px 0; }
        .section-title { font-weight: bold; text-align: center; margin: 4px 0; font-size: 10px; letter-spacing: 1px; }
        .garis { height: 1px; background: #000; margin: 3px 0; }
        .mt-2 { margin-top: 4px; }
        .mb-2 { margin-bottom: 4px; }
        .mt-4 { margin-top: 8px; }
        @media print { body { width: {{ $settings['thermal_width'] ?? 80 }}mm; } }
    </style>
</head>
<body>
    {{-- HEADER --}}
    <div class="text-center">
        <div class="text-xl bold">{{ $settings['nama_toko'] }}</div>
        @if($settings['alamat'])
        <div class="text-sm">{{ $settings['alamat'] }}</div>
        @endif
        @if($settings['telp'])
        <div class="text-sm">Telp: {{ $settings['telp'] }}</div>
        @endif
    </div>
    <div class="divider-double"></div>

    {{-- TITLE --}}
    <div class="text-center section-title mt-2 mb-2">
        {{ $jualBeli->tipe === 'jual' ? '📱' : '📲' }} NOTA JUAL BELI HP
    </div>
    <div class="divider"></div>

    {{-- INFO TRANSAKSI --}}
    <div class="row"><span>Jenis:</span><span class="bold">{{ $jualBeli->tipe === 'jual' ? 'JUAL HP' : 'BELI HP' }}</span></div>
    <div class="row"><span>Tanggal:</span><span>{{ $jualBeli->tanggal?->format('d/m/Y') }}</span></div>
    @if($cabang)
    <div class="row"><span>Cabang:</span><span>{{ $cabang->nama }}</span></div>
    @endif
    <div class="garis"></div>

    {{-- DETAIL HP --}}
    <div class="section-title mt-2">DATA HP</div>
    <div class="row"><span>HP / Perangkat:</span><span class="bold">{{ $jualBeli->hp }}</span></div>
    @if($jualBeli->imei)
    <div class="row"><span>IMEI:</span><span>{{ $jualBeli->imei }}</span></div>
    @endif
    <div class="garis"></div>

    {{-- PELANGGAN --}}
    @if($jualBeli->pelanggan)
    <div class="section-title mt-2">PELANGGAN</div>
    <div class="row"><span>Nama:</span><span class="bold">{{ $jualBeli->pelanggan }}</span></div>
    @endif

    {{-- RINCIAN --}}
    <div class="divider-double"></div>
    <div class="section-title mt-2">RINCIAN BAYAR</div>
    <div class="row bold" style="font-size:14px">
        <span>Harga:</span>
        <span>Rp {{ number_format($jualBeli->harga) }}</span>
    </div>
    <div class="row"><span>Metode Bayar:</span><span class="bold">{{ $jualBeli->metode_bayar ?? 'Cash' }}</span></div>
    @if($jualBeli->garansi && $jualBeli->garansi !== 'Tanpa Garansi')
    <div class="row"><span>Garansi:</span><span class="bold">{{ $jualBeli->garansi }}@if($jualBeli->garansi_hingga) s/d {{ $jualBeli->garansi_hingga->format('d/m/Y') }}@endif</span></div>
    @endif
    @if($jualBeli->status_pemeriksaan && $jualBeli->status_pemeriksaan !== 'Belum Dicek')
    <div class="row"><span>Pemeriksaan:</span><span>{{ $jualBeli->status_pemeriksaan }}</span></div>
    @endif
    @if($jualBeli->kondisi)
    <div class="row"><span>Kondisi:</span><span>{{ $jualBeli->kondisi }}</span></div>
    @endif
    @if($jualBeli->kelengkapan)
    <div class="row"><span>Kelengkapan:</span><span>{{ $jualBeli->kelengkapan }}</span></div>
    @endif

    @if($jualBeli->catatan)
    <div class="garis"></div>
    <div class="row"><span>Catatan:</span><span>{{ $jualBeli->catatan }}</span></div>
    @endif

    <div class="divider-double"></div>

    {{-- FOOTER --}}
    <div class="text-center text-sm mt-4">
        <div>Terima kasih atas kunjungan Anda!</div>
        <div class="bold mt-2">{{ $settings['nama_toko'] }}</div>
        @if($settings['telp'])
        <div class="text-xs">{{ $settings['telp'] }}</div>
        @endif
    </div>

    <div class="text-center text-xs mt-4" style="color:#666">
        Dicetak: {{ now()->format('d/m/Y H:i:s') }}
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
