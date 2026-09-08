@extends('layouts.app')
@section('title', 'Kas Harian')

@section('content')
<h2 class="mb-4"><i class="fas fa-cash-register"></i> Kas Harian</h2>

<!-- FITUR BARU 1: Filter Tanggal & Pencarian -->
<div class="card mb-4">
    <form method="GET" action="{{ route('kas.index') }}" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
        <div class="form-group" style="flex:1; min-width:150px; margin-bottom:0;">
            <label style="font-size:0.85rem; font-weight:600;">Filter Tanggal</label>
            <input type="date" name="tanggal" value="{{ $tanggal ?? date('Y-m-d') }}" class="form-input">
        </div>
        <div class="form-group" style="flex:2; min-width:200px; margin-bottom:0;">
            <label style="font-size:0.85rem; font-weight:600;">Cari Transaksi</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Keterangan, Kode SVC, atau Kategori..." class="form-input">
        </div>
        <div style="display:flex; gap:8px; margin-bottom:0;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
            <a href="{{ route('kas.index') }}" class="btn btn-secondary"><i class="fas fa-sync"></i> Reset</a>
            <!-- Tombol Export (Opsional: hubungkan ke route export excel) -->
            <button type="button" class="btn btn-success" onclick="alert('Fitur Export Excel akan segera hadir!')"><i class="fas fa-file-excel"></i> Export</button>
        </div>
    </form>
</div>

<!-- Saldo Tracker -->
<div class="saldo-tracker mb-6" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 20px; border-radius: 12px; display:flex; justify-content:space-between; align-items:center;">
    <div>
        <div class="saldo-label" style="opacity:0.9; font-size:0.9rem;"><i class="fas fa-wallet"></i> Saldo Kas Saat Ini</div>
        <div class="saldo-value" style="font-size:2rem; font-weight:800; margin-top:4px;">{{ formatRp($saldoTerakhir ?? 0) }}</div>
    </div>
    <div style="text-align:right; border-left: 1px solid rgba(255,255,255,0.3); padding-left: 20px;">
        <div style="font-size:.9rem; opacity:0.9">Masuk Hari Ini: <strong style="color:#86efac">{{ formatRp($masukHariIni ?? 0) }}</strong></div>
        <div style="font-size:.9rem; opacity:0.9; margin-top:6px;">Keluar Hari Ini: <strong style="color:#fca5a5">{{ formatRp($keluarHariIni ?? 0) }}</strong></div>
    </div>
</div>

<!-- Stats Grid dengan Fitur Baru: Jumlah Transaksi -->
<div class="stats-grid mb-6" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
    <div class="stat-card" style="border-left: 4px solid var(--success, #22c55e);">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div class="stat-icon" style="background:#dcfce7;color:#16a34a; width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:8px;"><i class="fas fa-arrow-down"></i></div>
            <span style="font-size:0.75rem; background:#dcfce7; color:#16a34a; padding:2px 8px; border-radius:12px;">{{ $jmlTransaksiMasuk ?? 0 }} Trx</span>
        </div>
        <div class="stat-label" style="margin-top:12px; font-size:0.85rem; color:#666;">Masuk Hari Ini</div>
        <div class="stat-value" style="color:#16a34a; font-size:1.25rem; font-weight:700;">{{ formatRp($masukHariIni ?? 0) }}</div>
    </div>
    
    <div class="stat-card" style="border-left: 4px solid var(--danger, #ef4444);">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div class="stat-icon" style="background:#fee2e2;color:#dc2626; width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:8px;"><i class="fas fa-arrow-up"></i></div>
            <span style="font-size:0.75rem; background:#fee2e2; color:#dc2626; padding:2px 8px; border-radius:12px;">{{ $jmlTransaksiKeluar ?? 0 }} Trx</span>
        </div>
        <div class="stat-label" style="margin-top:12px; font-size:0.85rem; color:#666;">Keluar Hari Ini</div>
        <div class="stat-value" style="color:#dc2626; font-size:1.25rem; font-weight:700;">{{ formatRp($keluarHariIni ?? 0) }}</div>
    </div>
</div>

<!-- Quick Add -->
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:10px"><i class="fas fa-bolt" style="color:#f59e0b;margin-right:6px"></i>Quick Add</h3>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
        <button onclick="quickKas('masuk','DP Servis')" class="btn btn-success btn-sm"><i class="fas fa-plus-circle"></i> DP Servis</button>
        <button onclick="quickKas('masuk','Pelunasan')" class="btn btn-success btn-sm"><i class="fas fa-check-circle"></i> Pelunasan</button>
        <button onclick="quickKas('keluar','Beli Sparepart')" class="btn btn-danger btn-sm"><i class="fas fa-boxes"></i> Beli Sparepart</button>
        <button onclick="quickKas('keluar','Operasional')" class="btn btn-danger btn-sm"><i class="fas fa-store"></i> Operasional</button>
        <button onclick="quickKas('keluar','Gaji Karyawan')" class="btn btn-danger btn-sm"><i class="fas fa-user-tie"></i> Gaji Karyawan</button>
    </div>
</div>

<!-- Form Transaksi -->
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-plus-square"></i> Transaksi Baru</h3>
    <form method="POST" action="{{ route('kas.store') }}">
        @csrf
        <div class="form-row" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
            <div class="form-group">
                <label>Tipe *</label>
                <select name="tipe" id="kasTipe" class="form-input" required>
                    <option value="masuk">Uang Masuk</option>
                    <option value="keluar">Uang Keluar</option>
                </select>
            </div>
            <div class="form-group">
                <label>Jumlah (Rp) *</label>
                <!-- FITUR BARU: ID ditambahkan untuk JS Auto-Format -->
                <input type="text" inputmode="numeric" name="jml" id="kasJml" class="form-input" required placeholder="0" data-format-rupiah>
            </div>
        </div>
        <div class="form-row" style="display:grid; grid-template-columns: 2fr 1fr; gap:16px; margin-top:12px;">
            <div class="form-group">
                <label>Kategori *</label>
                <select name="kategori" class="form-input" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option>DP Servis</option><option>Pelunasan</option><option>Transfer Masuk</option>
                    <option>Pendapatan QRIS</option><option>Jual HP</option><option>Beli Sparepart</option>
                    <option>Beli HP</option><option>Operasional</option><option>Gaji Karyawan</option><option>Modal</option><option>Lainnya</option>
                </select>
            </div>
            <div class="form-group">
                <label>Metode *</label>
                <select name="metode" class="form-input" required>
                    <option>Cash</option><option>Transfer</option><option>QRIS</option>
                </select>
            </div>
        </div>
        <div class="form-row" style="display:grid; grid-template-columns: 2fr 1fr; gap:16px; margin-top:12px;">
            <div class="form-group">
                <label>Keterangan *</label>
                <input type="text" name="ket" id="kasKet" class="form-input" required placeholder="Contoh: Servis LCD iPhone 11">
            </div>
            <div class="form-group">
                <label>Referensi (Opsional)</label>
                <input type="text" name="ref" class="form-input" placeholder="SVC-XXXXXX">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:16px; width:100%;"><i class="fas fa-save"></i> Simpan Transaksi</button>
    </form>
</div>

<!-- Table -->
<div class="card">
    <div class="table-wrap">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc; text-align:left;">
                    <th style="padding:12px;">Waktu</th>
                    <th style="padding:12px;">Tipe</th>
                    <th style="padding:12px;">Kategori</th>
                    <th style="padding:12px;">Keterangan</th>
                    <th style="padding:12px;">Ref</th>
                    <th style="padding:12px; text-align:right;">Jumlah</th>
                    <th style="padding:12px; text-align:right;">Saldo</th>
                    <th style="padding:12px; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kass as $k)
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:12px; font-size:0.85rem; color:#64748b;">{{ $k->waktu?->format('d/m/Y H:i') }}</td>
                    <td style="padding:12px;">
                        <span class="badge" style="background:{{ $k->tipe === 'masuk' ? '#dcfce7' : '#fee2e2' }}; color:{{ $k->tipe === 'masuk' ? '#16a34a' : '#dc2626' }}; padding:4px 8px; border-radius:4px; font-size:0.75rem; font-weight:600; text-transform:uppercase;">
                            {{ $k->tipe }}
                        </span>
                    </td>
                    <td style="padding:12px; font-size:0.9rem;">{{ $k->kategori }}</td>
                    <td style="padding:12px; font-size:0.9rem;">
                        {{ $k->ket }}
                        @if($k->metode !== 'Cash')
                            <span style="font-size:0.75rem; color:#64748b; display:block;">via {{ $k->metode }}</span>
                        @endif
                    </td>
                    <td style="padding:12px; font-size:0.85rem; color:#64748b;">{{ $k->ref ?? '-' }}</td>
                    <td style="padding:12px; text-align:right; font-weight:700; color:{{ $k->tipe === 'masuk' ? '#16a34a' : '#dc2626' }}">
                        {{ $k->tipe === 'masuk' ? '+' : '-' }} {{ formatRp($k->jml) }}
                    </td>
                    <td style="padding:12px; text-align:right; font-weight:600;">{{ formatRp($k->saldo) }}</td>
                    <td style="padding:12px; text-align:center;">
                        <form method="POST" action="{{ route('kas.destroy', $k) }}" style="display:inline" onsubmit="return confirm('Yakin ingin menghapus transaksi ini? Saldo akan disesuaikan ulang.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs" style="padding:4px 8px;"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="padding:24px; text-align:center; color:#64748b;">
                        <i class="fas fa-inbox" style="font-size:2rem; margin-bottom:8px; opacity:0.5;"></i><br>
                        Tidak ada data transaksi pada tanggal ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:16px;">
        {{ $kass->withQueryString()->links() }}
    </div>
</div>

<!-- FITUR BARU 3: JavaScript untuk Auto-Format Rupiah & Quick Add -->
<script>
// 1. Fungsi Quick Add (Sudah ada, sedikit dirapikan)
function quickKas(tipe, kategori) {
    document.getElementById('kasTipe').value = tipe;
    document.querySelector('[name="kategori"]').value = kategori;
    document.getElementById('kasKet').value = kategori;
    
    // Reset jumlah agar user langsung input
    document.getElementById('kasJml').value = '';
    document.getElementById('kasJml').focus();
}

// 2. Fungsi Auto-Format Rupiah saat mengetik
document.getElementById('kasJml').addEventListener('input', function (e) {
    let value = e.target.value.replace(/[^,\d]/g, '').toString();
    let split = value.split(',');
    let sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }

    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    e.target.value = rupiah ? 'Rp ' + rupiah : '';
});

// 3. Bersihkan format Rp saat form di-submit agar yang masuk ke database murni angka
document.querySelector('form').addEventListener('submit', function(e) {
    let jmlInput = document.getElementById('kasJml');
    let cleanValue = jmlInput.value.replace(/[^0-9]/g, '');
    // Buat hidden input atau ubah value langsung (tergantung setup backend, biasanya Laravel bisa handle string angka)
    jmlInput.value = cleanValue; 
});
</script>
@endsection