<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan {{ $tagihan->kode }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Arial', sans-serif; font-size: 11pt; color: #1e293b; background: #fff; }
        .invoice-box { max-width: 800px; margin: 0 auto; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0d9488; padding-bottom: 16px; margin-bottom: 20px; }
        .header-left h1 { font-size: 20pt; color: #0d9488; margin-bottom: 4px; }
        .header-left p { font-size: 9pt; color: #64748b; }
        .header-right { text-align: right; }
        .header-right .kode { font-size: 14pt; font-weight: 700; color: #0d9488; }
        .header-right .tanggal { font-size: 9pt; color: #64748b; }
        .info-section { display: flex; justify-content: space-between; margin-bottom: 20px; gap: 20px; }
        .info-box { flex: 1; background: #f8fafc; padding: 14px; border-radius: 8px; }
        .info-box h3 { font-size: 9pt; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
        .info-box p { font-size: 10pt; line-height: 1.5; }
        .info-box .name { font-weight: 700; font-size: 11pt; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table th { background: #0d9488; color: #fff; padding: 8px 10px; font-size: 9pt; text-align: left; }
        table th:last-child { text-align: right; }
        table th:nth-child(2) { text-align: center; }
        table td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size: 10pt; }
        table td:last-child { text-align: right; font-weight: 600; }
        table td:nth-child(2) { text-align: center; }
        .totals { display: flex; justify-content: flex-end; }
        .totals-table { width: 280px; }
        .totals-table tr td { padding: 4px 8px; border: none; font-size: 10pt; }
        .totals-table tr td:last-child { text-align: right; }
        .total-row td { font-size: 13pt; font-weight: 800; color: #0d9488; border-top: 2px solid #0d9488; padding-top: 8px; }
        .footer { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature { text-align: center; width: 180px; }
        .signature .line { border-top: 1px solid #1e293b; margin-top: 60px; padding-top: 4px; font-size: 9pt; }
        .status-badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-weight: 700; font-size: 10pt; }
        .status-lunas { background: #dcfce7; color: #166534; }
        .status-belum { background: #fee2e2; color: #991b1b; }
        .status-sebagian { background: #fef3c7; color: #92400e; }
        @media print { body { background: #fff; } .invoice-box { padding: 10px; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="padding:10px 20px;background:#f1f5f9;display:flex;justify-content:space-between;align-items:center">
        <strong>Tagihan {{ $tagihan->kode }}</strong>
        <div style="display:flex;gap:8px">
            <button onclick="window.print()" style="padding:8px 16px;background:#0d9488;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600"><i class="fas fa-print"></i> Cetak</button>
            <button onclick="window.close()" style="padding:8px 16px;background:#64748b;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600">Tutup</button>
        </div>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div class="header-left">
                <h1>FIXPRO</h1>
                <p>{{ $tagihan->cabang?->nama ?? 'Service Center' }}</p>
            </div>
            <div class="header-right">
                <div class="kode">TAGIHAN</div>
                <div class="kode">{{ $tagihan->kode }}</div>
                <div class="tanggal">Tanggal: {{ $tagihan->tanggal?->format('d F Y') }}</div>
                @if($tagihan->tanggal_jatuh_tempo)
                <div class="tanggal" style="color:#dc2626;font-weight:600">Jatuh Tempo: {{ $tagihan->tanggal_jatuh_tempo->format('d F Y') }}</div>
                @endif
            </div>
        </div>

        <div class="info-section">
            <div class="info-box">
                <h3>Kepada</h3>
                <p class="name">{{ $tagihan->nama_toko }}</p>
                @if($tagihan->kontak_toko)<p>{{ $tagihan->kontak_toko }}</p>@endif
                @if($tagihan->alamat_toko)<p>{{ $tagihan->alamat_toko }}</p>@endif
            </div>
            <div class="info-box">
                <h3>Status</h3>
                <p>
                    <span class="status-badge status-{{ $tagihan->status === 'Lunas' ? 'lunas' : ($tagihan->status === 'Belum Dibayar' ? 'belum' : 'sebagian') }}">
                        {{ $tagihan->status }}
                    </span>
                </p>
                @if($tagihan->dibayar > 0)
                <p style="margin-top:8px">Dibayar: Rp {{ number_format($tagihan->dibayar, 0, ',', '.') }}</p>
                @if($tagihan->sisa > 0)
                <p style="color:#dc2626">Sisa: Rp {{ number_format($tagihan->sisa, 0, ',', '.') }}</p>
                @endif
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th style="text-align:center">Qty</th>
                    <th style="text-align:right">Harga Satuan</th>
                    <th style="text-align:right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tagihan->items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->nama_barang }}</td>
                    <td style="text-align:center">{{ $item->qty }}</td>
                    <td style="text-align:right">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    <td style="text-align:right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table class="totals-table">
                <tr><td>Subtotal</td><td>Rp {{ number_format($tagihan->subtotal, 0, ',', '.') }}</td></tr>
                @if($tagihan->diskon_persen > 0)
                <tr><td>Diskon ({{ $tagihan->diskon_persen }}%)</td><td>- Rp {{ number_format($tagihan->subtotal * $tagihan->diskon_persen / 100, 0, ',', '.') }}</td></tr>
                @endif
                @if($tagihan->diskon_nominal > 0)
                <tr><td>Diskon Tambahan</td><td>- Rp {{ number_format($tagihan->diskon_nominal, 0, ',', '.') }}</td></tr>
                @endif
                <tr class="total-row"><td>TOTAL</td><td>Rp {{ number_format($tagihan->total, 0, ',', '.') }}</td></tr>
            </table>
        </div>

        @if($tagihan->catatan)
        <div style="margin-top:16px;padding:10px;background:#f8fafc;border-radius:6px;font-size:9pt;color:#64748b">
            <strong>Catatan:</strong> {{ $tagihan->catatan }}
        </div>
        @endif

        <div class="footer">
            <div class="signature">
                <div style="font-size:9pt;color:#64748b">Pengirim</div>
                <div class="line">{{ $tagihan->user?->name ?? 'Admin' }}</div>
            </div>
            <div class="signature">
                <div style="font-size:9pt;color:#64748b">Penerima</div>
                <div class="line">{{ $tagihan->nama_toko }}</div>
            </div>
        </div>
    </div>
</body>
</html>
