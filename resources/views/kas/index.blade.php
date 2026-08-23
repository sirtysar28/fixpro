@extends('layouts.app')
@section('title', 'Kas Harian')

@section('content')
<h2 class="mb-4">Kas Harian</h2>

<div class="saldo-tracker mb-6">
    <div>
        <div class="saldo-label"><i class="fas fa-wallet"></i> Saldo Kas Saat Ini</div>
        <div class="saldo-value">{{ formatRp($saldo) }}</div>
    </div>
    <div style="margin-left:auto;text-align:right">
        <div style="font-size:.85rem;opacity:.8">Masuk: <strong>{{ formatRp($masukHariIni) }}</strong></div>
        <div style="font-size:.85rem;opacity:.8">Keluar: <strong>{{ formatRp($keluarHariIni) }}</strong></div>
    </div>
</div>

<div class="stats-grid mb-6">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-arrow-circle-down"></i></div>
        <div class="stat-label">Masuk Hari Ini</div>
        <div class="stat-value" style="color:var(--success)">{{ formatRp($masukHariIni) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-arrow-circle-up"></i></div>
        <div class="stat-label">Keluar Hari Ini</div>
        <div class="stat-value" style="color:var(--danger)">{{ formatRp($keluarHariIni) }}</div>
    </div>
</div>

<!-- Quick Add -->
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:10px"><i class="fas fa-bolt" style="color:var(--accent);margin-right:6px"></i>Quick Add</h3>
    <div style="display:flex;flex-wrap:wrap;gap:6px">
        <button onclick="quickKas('masuk','DP Servis')" class="btn btn-success btn-sm"><i class="fas fa-plus-circle"></i> DP Servis</button>
        <button onclick="quickKas('masuk','Pelunasan')" class="btn btn-success btn-sm"><i class="fas fa-check-circle"></i> Pelunasan</button>
        <button onclick="quickKas('keluar','Beli Sparepart')" class="btn btn-danger btn-sm"><i class="fas fa-boxes"></i> Beli Sparepart</button>
        <button onclick="quickKas('keluar','Operasional')" class="btn btn-danger btn-sm"><i class="fas fa-store"></i> Operasional</button>
    </div>
</div>

<!-- Form -->
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:16px">Transaksi Baru</h3>
    <form method="POST" action="{{ route('kas.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Tipe *</label>
                <select name="tipe" id="kasTipe" class="form-input" required>
                    <option value="masuk">Masuk</option>
                    <option value="keluar">Keluar</option>
                </select>
            </div>
            <div class="form-group">
                <label>Jumlah (Rp) *</label>
                <input type="text" inputmode="numeric" name="jml" id="kasJml" class="form-input" required min="0" data-format-rupiah>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Kategori *</label>
                <select name="kategori" class="form-input" required>
                    <option value="">-- Pilih --</option>
                    <option>DP Servis</option><option>Pelunasan</option><option>Transfer Masuk</option>
                    <option>Pendapatan QRIS</option><option>Jual HP</option><option>Beli Sparepart</option>
                    <option>Beli HP</option><option>Operasional</option><option>Modal</option><option>Lainnya</option>
                </select>
            </div>
            <div class="form-group">
                <label>Metode *</label>
                <select name="metode" class="form-input" required>
                    <option>Cash</option><option>Transfer</option><option>QRIS</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Keterangan *</label>
            <input type="text" name="ket" id="kasKet" class="form-input" required>
        </div>
        <div class="form-group">
            <label>Referensi (Kode Servis)</label>
            <input type="text" name="ref" class="form-input" placeholder="SVC-XXXXXX-XXX">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
    </form>
</div>

<!-- Table -->
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Waktu</th><th>Tipe</th><th>Metode</th><th>Kategori</th><th>Keterangan</th><th>Jumlah</th><th>Saldo</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($kass as $k)
                <tr>
                    <td class="text-xs">{{ $k->waktu?->format('d/m H:i') }}</td>
                    <td><span class="badge badge-{{ $k->tipe === 'masuk' ? 'masuk-kas' : 'keluar' }}">{{ $k->tipe }}</span></td>
                    <td>{{ $k->metode }}</td>
                    <td>{{ $k->kategori }}</td>
                    <td>{{ $k->ket }}</td>
                    <td style="font-weight:700;color:{{ $k->tipe === 'masuk' ? 'var(--success)' : 'var(--danger)' }}">{{ $k->tipe === 'masuk' ? '+' : '-' }}{{ formatRp($k->jml) }}</td>
                    <td>{{ formatRp($k->saldo) }}</td>
                    <td>
                        <form method="POST" action="{{ route('kas.destroy', $k) }}" style="display:inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $kass->links() }}
</div>

<script>
function quickKas(tipe, kategori) {
    document.getElementById('kasTipe').value = tipe;
    document.querySelector('[name="kategori"]').value = kategori;
    document.getElementById('kasKet').value = kategori;
    document.getElementById('kasJml').focus();
}
</script>
@endsection
