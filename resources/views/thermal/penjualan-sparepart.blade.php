<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk {{ $penjualan_sparepart->kode }}</title>
    <style>
        /* 1. DEFINISIKAN UKURAN KERTAS FISIK KE BROWSER */
        @page {
            size: 58mm auto; /* Lebar 58mm, tinggi otomatis */
            margin: 0;       /* HAPUS semua margin default browser */
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            /* 2. KUNCI LEBAR FISIK: Paksa browser menghormati batas 58mm */
            width: 58mm; 
            margin: 0 auto;
            padding: 2mm 3mm; /* Padding aman: sisa area cetak 52mm (anti-potong tepi printer) */
            color: #000;
            line-height: 1.3;
            overflow-x: hidden; /* 3. PENGAMAN: Cegah elemen "bocor" ke samping */
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .text-sm { font-size: 10px; }
        .text-xs { font-size: 9px; }
        .text-xl { font-size: 14px; }
        
        .divider { border-top: 1px dashed #000; margin: 4px 0; }
        .divider-double { border-top: 2px solid #000; margin: 5px 0; }
        
        .row { 
            display: flex; 
            justify-content: space-between; 
            padding: 2px 0; 
            width: 100%;
        }
        /* 4. PERBAIKAN FLEXBOX: Agar label dan nilai tidak saling menabrak */
        .row span:first-child {
            flex: 1;
            padding-right: 4px;
            word-break: break-word; /* Turun baris jika label kepanjangan */
        }
        .row span:last-child {
            text-align: right;
            word-break: break-word; /* Turun baris jika nilai kepanjangan */
            max-width: 55%; /* Batasi agar kolom kiri tetap punya ruang */
        }
        
        .section-title { 
            font-weight: bold; 
            text-align: center; 
            margin: 6px 0; 
            font-size: 11px; 
            letter-spacing: 1px; 
        }
        
        .garis { height: 1px; background: #000; margin: 4px 0; }
        .mt-2 { margin-top: 4px; }
        .mb-2 { margin-bottom: 4px; }
        .mt-4 { margin-top: 8px; }

        /* 5. TABEL AGAR TIDAK MENYEMPIT / TERPOTONG */
        table.item-table {
            width: 100%;
            table-layout: fixed; /* KUNCI: Memaksa tabel mengikuti lebar parent (52mm) */
            border-collapse: collapse;
        }
        table.item-table td {
            vertical-align: top;
            padding: 2px 0;
            word-break: break-word; /* KUNCI: Pecah kata panjang di dalam tabel */
            overflow-wrap: break-word;
        }
        
        .item-name {
            font-weight: bold;
            width: 65%; /* Beri ruang lebih untuk nama barang */
        }
        
        .item-price-col {
            width: 35%;
            text-align: right;
        }

        .discount-row { font-weight: bold; }
        .grand-total { 
            font-weight: bold; 
            font-size: 13px; 
            border-top: 2px solid #000; 
            padding-top: 4px; 
            margin-top: 4px; 
        }
        
        @media print { 
            body { 
                width: 58mm; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            } 
        }
    </style>
</head>
<body onload="window.print()">
    {{-- HEADER --}}
    <div class="text-center">
        <div class="text-xl bold">{{ $settings['nama_toko'] ?? 'NAMA TOKO' }}</div>
        @if(!empty($settings['alamat']))
        <div class="text-sm">{{ $settings['alamat'] }}</div>
        @endif
        @if(!empty($settings['telp']))
        <div class="text-sm">Telp: {{ $settings['telp'] }}</div>
        @endif
    </div>
    <div class="divider-double"></div>

    <div class="text-center section-title mt-2 mb-2">NOTA PENJUALAN</div>
    <div class="divider"></div>

    {{-- INFO TRANSAKSI --}}
    <div class="row"><span>No. Transaksi:</span><span class="bold">{{ $penjualan_sparepart->no_transaksi ?? $penjualan_sparepart->kode }}</span></div>
    <div class="row"><span>Tanggal:</span><span>{{ $penjualan_sparepart->tanggal?->format('d/m/Y') ?? now()->format('d/m/Y') }}</span></div>
    <div class="row"><span>Waktu:</span><span>{{ $penjualan_sparepart->created_at?->format('H:i') ?? now()->format('H:i') }}</span></div>
    @if(isset($penjualan_sparepart->user))
    <div class="row"><span>Kasir:</span><span>{{ $penjualan_sparepart->user->name }}</span></div>
    @endif
    <div class="garis"></div>

    {{-- PELANGGAN --}}
    @if(isset($penjualan_sparepart->pelanggan))
    <div class="section-title mt-2">PELANGGAN</div>
    <div class="row"><span>Nama:</span><span class="bold">{{ Str::limit($penjualan_sparepart->pelanggan->nama, 20) }}</span></div>
    <div class="row"><span>No. HP:</span><span>{{ $penjualan_sparepart->pelanggan->no_hp ?? '-' }}</span></div>
    <div class="garis"></div>
    @endif

    {{-- DAFTAR BARANG --}}
    <div class="section-title mt-2">DAFTAR BARANG</div>
    
    @foreach($allItems as $item)
    <div class="item-row">
        <table class="item-table">
            <tr>
                <td class="item-name">{{ $item->stok?->nama ?? $item->nama ?? 'Nama Barang' }}</td>
                <td class="item-price-col">
                    {{ $item->qty }}x <br>
                    @php 
                        $harga = (float) ($item->harga_satuan ?? ($item->total / max(1, $item->qty))); 
                    @endphp
                    Rp {{ number_format($harga, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td colspan="2" class="text-right" style="border-top: 1px dotted #999; padding-top: 2px;">
                    <span class="bold">Rp {{ number_format($item->total, 0, ',', '.') }}</span>
                </td>
            </tr>
        </table>
    </div>
    @endforeach

    <div class="divider"></div>

    {{-- RINCIAN BAYAR --}}
    <div class="section-title mt-2">RINCIAN BAYAR</div>
    <div class="row"><span>Total Item ({{ $allItems->count() }}):</span><span>Rp {{ number_format($totalKeseluruhan ?? 0, 0, ',', '.') }}</span></div>

    @if(isset($diskon) && $diskon > 0)
    <div class="row discount-row"><span>- Diskon:</span><span>- Rp {{ number_format($diskon, 0, ',', '.') }}</span></div>
    @endif

    <div class="grand-total row">
        <span>TOTAL BAYAR:</span>
        <span>Rp {{ number_format($totalSetelahDiskon ?? ($totalKeseluruhan - ($diskon ?? 0)), 0, ',', '.') }}</span>
    </div>

    {{-- CATATAN (Opsional, tetap ada jika diisi) --}}
    @if(!empty($penjualan_sparepart->catatan))
    <div class="garis"></div>
    <div class="row"><span>Catatan:</span><span>{{ $penjualan_sparepart->catatan }}</span></div>
    @endif

    {{-- STATUS DIBATALKAN --}}
    @if($penjualan_sparepart->status === 'Dibatalkan')
    <div class="divider-double"></div>
    <div class="text-center bold" style="font-size:12px; margin: 4px 0;">*** DIBATALKAN ***</div>
    @endif

    <div class="divider-double"></div>

    {{-- FOOTER (HANYA TERIMA KASIH) --}}
    <div class="text-center text-sm mt-4">
        <div>Terima Kasih</div>
    </div>

</body>
</html>