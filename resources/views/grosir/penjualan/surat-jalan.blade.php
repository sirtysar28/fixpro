<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Jalan {{ $penjualan_grosir->no_nota }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #e2e8f0; color: #1e293b; font-size: 13px; }
    .page { width: 210mm; min-height: 297mm; margin: 20px auto; background: #fff; padding: 16mm; box-shadow: 0 4px 30px rgba(0,0,0,.12); }

    .sj-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px double #0f172a; padding-bottom: 12px; }
    .sj-head .toko { display: flex; gap: 12px; align-items: center; }
    .sj-head .logo { width: 58px; height: 58px; object-fit: cover; border-radius: 10px; }
    .sj-head h1 { font-size: 19px; color: #0d9488; }
    .sj-head p { font-size: 11px; color: #64748b; line-height: 1.55; }
    .sj-title { text-align: right; }
    .sj-title h2 { font-size: 22px; letter-spacing: 2px; }
    .sj-title .no { font-family: monospace; font-weight: 700; margin-top: 4px; }

    .kotak-row { display: flex; gap: 12px; margin-top: 14px; }
    .kotak { flex: 1; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; }
    .kotak h3 { font-size: 10.5px; text-transform: uppercase; letter-spacing: 1px; color: #0d9488; margin-bottom: 6px; }
    .kotak table { font-size: 12px; }
    .kotak td { padding: 2px 0; }
    .kotak td.lbl { color: #94a3b8; width: 90px; }

    table.items { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 12.5px; }
    table.items thead th { border: 1.5px solid #0f172a; background: #f1f5f9; padding: 7px 10px; text-align: left; font-size: 11px; text-transform: uppercase; }
    table.items thead th.c { text-align: center; }
    table.items tbody td { border: 1px solid #94a3b8; padding: 7px 10px; }
    table.items td.c { text-align: center; }
    table.items td.cek { height: 26px; }

    .catatan { margin-top: 12px; border: 1px dashed #94a3b8; border-radius: 8px; padding: 10px 12px; font-size: 11px; color: #475569; }
    .catatan b { color: #1e293b; }

    .ttd { display: flex; justify-content: space-between; margin-top: 40px; text-align: center; }
    .ttd .kolom { width: 200px; font-size: 12px; }
    .ttd .nm { margin-top: 48px; border-top: 1.5px solid #475569; padding-top: 5px; }

    .btn-print { display: block; width: 210mm; margin: 0 auto; padding: 11px; background: #0d9488; color: #fff; border: none; font-family: inherit; font-weight: 700; font-size: 14px; cursor: pointer; border-radius: 10px 10px 0 0; }
    @media print {
        body { background: #fff; }
        .btn-print { display: none; }
        .page { margin: 0; box-shadow: none; width: 100%; }
        @page { size: A4; margin: 10mm; }
    }
</style>
</head>
<body>
<button class="btn-print" onclick="window.print()">🖨️ Cetak Surat Jalan (A4)</button>

<div class="page">
    <div class="sj-head">
        <div class="toko">
            @if(!empty($settings['logo']))<img src="{{ $settings['logo'] }}" class="logo" alt="logo">@endif
            <div>
                <h1>{{ $settings['nama_toko'] }}</h1>
                <p>{{ $settings['alamat'] }}<br>WA: {{ $settings['wa'] }} · Telp: {{ $settings['telp'] }}</p>
            </div>
        </div>
        <div class="sj-title">
            <h2>SURAT JALAN</h2>
            <div class="no">No. SJ/{{ $penjualan_grosir->no_nota }}</div>
            <div style="font-size:11px;color:#64748b;">{{ $penjualan_grosir->tanggal->format('d F Y') }}</div>
        </div>
    </div>

    <div class="kotak-row">
        <div class="kotak">
            <h3>Dikirim Kepada</h3>
            <table>
                <tr><td class="lbl">Nama</td><td>: <b>{{ $penjualan_grosir->nama_pelanggan }}</b></td></tr>
                <tr><td class="lbl">No HP</td><td>: {{ $penjualan_grosir->pelanggan?->no_hp ?? '-' }}</td></tr>
                <tr><td class="lbl">Alamat</td><td>: {{ $penjualan_grosir->alamat_kirim ?? $penjualan_grosir->pelanggan?->alamat ?? '-' }}</td></tr>
            </table>
        </div>
        <div class="kotak">
            <h3>Pengiriman</h3>
            <table>
                <tr><td class="lbl">No Nota</td><td>: {{ $penjualan_grosir->no_nota }}</td></tr>
                <tr><td class="lbl">Sumber</td><td>: {{ $penjualan_grosir->sumberCabang?->nama ?? '-' }} ({{ $penjualan_grosir->sumberCabang?->isGudang() ? 'Gudang' : 'Toko' }})</td></tr>
                <tr><td class="lbl">Kasir</td><td>: {{ $penjualan_grosir->user?->name ?? '-' }}</td></tr>
            </table>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:34px;">No</th>
                <th>Nama Barang</th>
                <th style="width:110px;">Kode</th>
                <th class="c" style="width:60px;">Qty</th>
                <th class="c" style="width:100px;">Checklist ✓</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penjualan_grosir->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->nama }}</td>
                <td style="font-family:monospace;">{{ $item->kode }}</td>
                <td class="c"><b>{{ $item->qty }}</b> {{ $item->stok?->satuan ?? 'pcs' }}</td>
                <td class="cek"></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="catatan">
        <b>Ketentuan:</b>
        1) Barang diterima dalam kondisi baik sesuai daftar di atas. 2) Surat jalan ini bukan bukti pembayaran.
        3) Kerusakan/kehilangan setelah barang diterima menjadi tanggung jawab penerima.
        @if($penjualan_grosir->catatan)<br><b>Catatan khusus:</b> {{ $penjualan_grosir->catatan }}@endif
    </div>

    <div class="ttd">
        <div class="kolom">Pengirim<div class="nm">( {{ $penjualan_grosir->user?->name ?? '...........' }} )</div></div>
        <div class="kolom">Penerima<div class="nm">( .................. )</div></div>
        <div class="kolom">No. Kendaraan<div class="nm">( .................. )</div></div>
    </div>
</div>
</body>
</html>
