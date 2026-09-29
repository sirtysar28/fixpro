<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Jual Beli HP</title>
    <style>
        @php
            $paperWidth   = (int) ($settings['thermal_width'] ?? 58);
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
        .divider-double { border-top: 2px solid #000; margin: 3px 0; }
        .row { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 2px; padding: 1px 0; }
        .row > span { max-width: 100%; word-break: break-word; }
        .section-title { font-weight: bold; text-align: center; margin: 3px 0; font-size: 9px; letter-spacing: 0; }
        .garis { height: 1px; background: #000; margin: 3px 0; }
        .mt-2 { margin-top: 4px; }
        .mb-2 { margin-bottom: 4px; }
        .mt-4 { margin-top: 8px; }
        @media print { body { width: {{ $receiptWidth }}mm; } }
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
    <div class="row bold" style="font-size:11px">
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
