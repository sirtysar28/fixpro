<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Servis {{ $servis->kode }}</title>

    <style>
        /* ⚠️ PERBAIKAN 1: Hapus 'auto' height, gunakan tinggi tetap */
        @page {
            size: 58mm 200mm;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            font-family: 'Courier New', Courier, monospace;
            color: #000;
            background: #fff;
            -webkit-font-smoothing: antialiased;
        }

        /* ⚠️ PERBAIKAN 2: Tambahkan page-break-inside: avoid */
        .receipt {
            width: 48mm;
            margin: 0 auto;
            padding: 3.5mm 0 4mm 0;
            font-size: 8.5px;
            line-height: 1.4;
            position: relative;
            page-break-inside: avoid;
        }

        /* ===== HEADER ===== */
        .brand {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 2px;
            text-align: center;
            margin-bottom: 1mm;
        }

        .sub-text {
            font-size: 7.5px;
            text-align: center;
            margin-bottom: 0.4mm;
            letter-spacing: 0.5px;
            color: #111;
        }

        /* ===== DIVIDERS ===== */
        .line-solid {
            border-top: 1.5px solid #000;
            margin: 2.5mm 0;
        }

        .line-dashed {
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }

        /* ===== SECTION TITLE ===== */
        .section-title {
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 2px;
            text-align: center;
            margin: 2mm 0 1.2mm 0;
            text-transform: uppercase;
        }

        .kode-servis {
            font-size: 12px;
            font-weight: 900;
            text-align: center;
            margin: 1mm 0 2.5mm 0;
            letter-spacing: 1px;
        }

        /* ===== TABEL DATA ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        td {
            padding: 0.5mm 0;
            vertical-align: top;
        }

        .col-label {
            text-align: left;
            width: 19mm;
            font-weight: normal;
            white-space: nowrap;
        }

        .col-value {
            text-align: left;
            font-weight: bold;
        }

        .col-value.light {
            font-weight: normal;
            color: #222;
        }

        /* ===== PEMBAYARAN ===== */
        .pay-total td {
            font-size: 11px;
            font-weight: 900;
            padding-top: 1.5mm;
            border-top: 1px dashed #000;
            letter-spacing: 0.5px;
        }

        /* ===== FOOTER ===== */
        .footer-thanks {
            font-size: 11px;
            font-weight: 900;
            text-align: center;
            margin: 3mm 0 1mm 0;
            letter-spacing: 2px;
        }

        /* ⚠️ PERBAIKAN 3: Cegah page-break di dalam tabel dan section penting */
        table,
        .section-title,
        .footer-thanks,
        .brand {
            page-break-inside: avoid;
        }

        @media print {
            @page {
                size: 58mm 200mm;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0;
            }

            /* ⚠️ PERBAIKAN 4: Sembunyikan elemen non-print jika ada */
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>

@php
    $biaya = (float) ($servis->biaya ?? 0);
    $dp = (float) ($servis->dp ?? 0);
    $dp = min($dp, $biaya);
    $sisa = max(0, $biaya - $dp);
    $garansi = (int) ($servis->garansi ?? 0);
@endphp

<div class="receipt">

    {{-- HEADER --}}
    <div class="brand">
        {{ strtoupper($settings['nama_toko'] ?? 'TOKO SAYA') }}
    </div>

    @if(!empty($settings['tagline']))
        <div class="sub-text">{{ $settings['tagline'] }}</div>
    @endif

    @if(!empty($settings['alamat']))
        <div class="sub-text">{{ $settings['alamat'] }}</div>
    @endif

    @if(!empty($settings['telp']))
        <div class="sub-text">{{ $settings['telp'] }}</div>
    @endif

    <div class="line-solid"></div>

    {{-- JUDUL & KODE SERVIS --}}
    <div class="section-title">
        {{ $servis->status === 'Selesai' ? 'BUKTI SERVIS' : 'NOTA SERVIS' }}
    </div>

    <div class="kode-servis">
        {{ $servis->kode }}
    </div>

    <div class="line-solid"></div>

    {{-- INFORMASI SERVIS --}}
    <table>
        <tr>
            <td class="col-label">Tanggal</td>
            <td class="col-value light">: {{ $servis->tanggal?->format('d/m/Y H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="col-label">Pelanggan</td>
            <td class="col-value">: {{ $servis->pelanggan?->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="col-label">Perangkat</td>
            <td class="col-value">: {{ $servis->perangkat ?? '-' }}</td>
        </tr>
        @if($servis->keluhan)
        <tr>
            <td class="col-label">Keluhan</td>
            <td class="col-value light">: {{ $servis->keluhan }}</td>
        </tr>
        @endif
        <tr>
            <td class="col-label">Teknisi</td>
            <td class="col-value">: {{ $servis->teknisi?->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="col-label">Masa Garansi</td>
            <td class="col-value">: {{ $garansi > 0 ? $garansi . ' Hari' : 'Tidak Ada' }}</td>
        </tr>
        <tr>
            <td class="col-label">Berlaku S/D</td>
            <td class="col-value">: {{ $servis->tanggal_garansi?->format('d/m/Y') ?? '-' }}</td>
        </tr>
    </table>

    <div class="line-dashed"></div>

    {{-- RINCIAN PEMBAYARAN --}}
    <div class="section-title">RINCIAN PEMBAYARAN</div>

    <table>
        <tr>
            <td class="col-label">Biaya Servis</td>
            <td class="col-value">: Rp {{ number_format($biaya, 0, ',', '.') }}</td>
        </tr>
        @if($dp > 0)
        <tr>
            <td class="col-label">Dibayar (DP)</td>
            <td class="col-value">: Rp {{ number_format($dp, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="pay-total">
            <td class="col-label">Sisa Bayar</td>
            <td class="col-value">: Rp {{ number_format($sisa, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="line-solid"></div>

    {{-- FOOTER --}}
    <div class="footer-thanks">TERIMA KASIH</div>

</div>

<script>
    /*
     * AUTO PRINT - Versi Aman Anti-Dobel
     */
    (function () {
        // Cek apakah sudah pernah diprint di sesi ini
        if (sessionStorage.getItem('struk_printed_' + '{{ $servis->kode }}')) {
            return;
        }

        let printTriggered = false;

        window.addEventListener('load', function () {
            if (printTriggered) return;
            printTriggered = true;

            // Tandai sudah diprint
            sessionStorage.setItem('struk_printed_' + '{{ $servis->kode }}', '1');

            setTimeout(function () {
                window.print();
            }, 500); // Delay lebih lama untuk memastikan render selesai
        });

        // Cegah print ganda via keyboard shortcut
        window.addEventListener('beforeprint', function () {
            if (printTriggered) {
                // Biarkan print kedua jika user manual trigger
            }
        });
    })();
</script>

</body>
</html>