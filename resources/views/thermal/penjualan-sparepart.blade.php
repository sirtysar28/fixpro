<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Penjualan {{ $penjualan_sparepart->kode }}</title>
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
        .item-row { padding: 2px 0; }
        .item-name { font-weight: bold; }
        .item-detail { padding-left: 8px; font-size: 10px; color: #333; }
        .item-subtotal { text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        .discount-row { font-weight: bold; color: #000; }
        .total-row { font-weight: bold; font-size: 13px; }
        .grand-total { font-weight: bold; font-size: 15px; border-top: 2px solid #000; padding-top: 4px; margin-top: 4px; }
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
        🧾 NOTA PENJUALAN SPAREPART
    </div>
    <div class="divider"></div>

    {{-- INFO TRANSAKSI --}}
    <div class="row"><span>No. Transaksi:</span><span class="bold">{{ $penjualan_sparepart->no_transaksi ?? $penjualan_sparepart->kode }}</span></div>
    <div class="row"><span>Tanggal:</span><span>{{ $penjualan_sparepart->tanggal?->format('d/m/Y') }}</span></div>
    <div class="row"><span>Waktu:</span><span>{{ $penjualan_sparepart->created_at?->format('H:i') }}</span></div>
    @if($penjualan_sparepart->cabang)
    <div class="row"><span>Cabang:</span><span>{{ $penjualan_sparepart->cabang->nama }}</span></div>
    @endif
    @if($penjualan_sparepart->user)
    <div class="row"><span>Kasir:</span><span>{{ $penjualan_sparepart->user->name }}</span></div>
    @endif
    <div class="garis"></div>

    {{-- PELANGGAN --}}
    @if($penjualan_sparepart->pelanggan)
    <div class="section-title mt-2">PELANGGAN</div>
    <div class="row"><span>Nama:</span><span class="bold">{{ $penjualan_sparepart->pelanggan->nama }}</span></div>
    <div class="row"><span>No. HP:</span><span>{{ $penjualan_sparepart->pelanggan->no_hp ?? '-' }}</span></div>
    <div class="garis"></div>
    @endif

    {{-- DAFTAR BARANG --}}
    <div class="section-title mt-2">DAFTAR BARANG</div>
    @foreach($allItems as $item)
    <div class="item-row">
        <table style="width:100%">
            <tr>
                <td class="item-name">{{ $item->stok?->nama ?? '-' }}</td>
                <td class="text-right">{{ $item->qty }}x @php $harga = (float) ($item->harga_satuan ?? $item->total / max(1, $item->qty)) @endphp Rp {{ number_format($harga) }}</td>
            </tr>
            <tr>
                <td class="item-detail" colspan="2">Total: Rp {{ number_format($item->total) }}</td>
            </tr>
        </table>
    </div>
    <div class="divider"></div>
    @endforeach

    {{-- RINCIAN BAYAR --}}
    <div class="section-title mt-2">RINCIAN BAYAR</div>
    <div class="row"><span>Total Item ({{ $allItems->count() }} item):</span><span>Rp {{ number_format($totalKeseluruhan) }}</span></div>

    @if($diskon > 0)
    <div class="row discount-row" style="color:#dc2626"><span>- Diskon:</span><span>- Rp {{ number_format($diskon) }}</span></div>
    @endif

    <div class="grand-total row" style="margin-top:6px">
        <span>TOTAL BAYAR:</span>
        <span>Rp {{ number_format($totalSetelahDiskon) }}</span>
    </div>

    <div class="garis"></div>
    <div class="row"><span>Metode Bayar:</span><span class="bold">{{ $penjualan_sparepart->metode_bayar }}</span></div>

    @if($penjualan_sparepart->catatan)
    <div class="garis"></div>
    <div class="row"><span>Catatan:</span><span>{{ $penjualan_sparepart->catatan }}</span></div>
    @endif

    @if($penjualan_sparepart->status === 'Dibatalkan')
    <div class="divider-double"></div>
    <div class="text-center bold" style="color:#000;font-size:13px">*** DIBATALKAN ***</div>
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

    <div class="text-center text-xs mt-4" style="font-family:monospace;letter-spacing:3px;">
        || {{ $penjualan_sparepart->kode }} ||
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
