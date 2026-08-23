@extends('layouts.app')
@section('title', 'Daftar Harga Service')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-tags" style="color:var(--primary);margin-right:6px"></i> Daftar Harga Service</h2>
    <div style="display:flex;gap:8px;align-items:center">
        <span style="font-size:.78rem;color:#64748b">{{ $totalItems }} item aktif</span>
        <a href="{{ request()->fullUrlWithQuery(['show_all' => 1]) }}" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Tampil Semua</a>
        <button type="button" class="btn btn-primary btn-sm" onclick="toggleAddForm()"><i class="fas fa-plus"></i> Tambah Harga</button>
    </div>
</div>

<!-- Statistik Ringkas -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px">
    <div style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:12px;padding:16px;text-align:center">
        <div style="font-size:.72rem;opacity:.8">Total Harga Aktif</div>
        <div style="font-size:1.6rem;font-weight:800">{{ $totalItems }}</div>
    </div>
    <div style="background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border-radius:12px;padding:16px;text-align:center">
        <div style="font-size:.72rem;opacity:.8">Rata-rata Harga Jasa</div>
        <div style="font-size:1.3rem;font-weight:800">{{ formatRp($avgPrice) }}</div>
    </div>
    <div style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-radius:12px;padding:16px;text-align:center">
        <div style="font-size:.72rem;opacity:.8">Kategori</div>
        <div style="font-size:1.6rem;font-weight:800">{{ $kategoriList->count() }}</div>
    </div>
</div>

<!-- Form Tambah Harga -->
<div id="addFormPanel" class="card" style="display:none;margin-bottom:20px">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:6px"></i> Tambah Harga Service Baru</h3>
    <form method="POST" action="{{ route('service-prices.store') }}">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group">
                <label>Merk HP (opsional)</label>
                <input type="text" name="merk_hp" class="form-input" placeholder="Contoh: Apple, Samsung, Xiaomi">
            </div>
            <div class="form-group">
                <label>Tipe / Model HP (opsional)</label>
                <input type="text" name="tipe_hp" class="form-input" placeholder="Contoh: iPhone 14, Galaxy S23">
            </div>
        </div>
        <div class="form-group">
            <label>Jenis Kerusakan / Jasa *</label>
            <input type="text" name="kerusakan" class="form-input" required placeholder="Contoh: Ganti LCD, Ganti Baterai, Unlock iCloud">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group">
                <label>Harga Jasa (Rp) *</label>
                <input type="text" inputmode="numeric" name="harga_jasa" class="form-input" required placeholder="0" data-format-rupiah>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori" class="form-input">
                    <option value="umum">Umum</option>
                    <option value="hardware">Hardware</option>
                    <option value="software">Software</option>
                    <option value="ganti-sparepart">Ganti Sparepart</option>
                    <option value="unlock">Unlock / Bypass</option>
                    <option value="water-damage">Water Damage</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Deskripsi (opsional)</label>
            <input type="text" name="deskripsi" class="form-input" placeholder="Keterangan tambahan...">
        </div>
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="is_global" value="1" style="width:18px;height:18px;accent-color:var(--primary)">
                <span>Berlaku Global (semua cabang)</span>
            </label>
            <div class="text-xs text-muted" style="margin-top:2px">Centang jika harga ini berlaku untuk semua cabang. Jika tidak dicentang, hanya berlaku untuk cabang Anda.</div>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <button type="button" class="btn btn-secondary" onclick="toggleAddForm()"><i class="fas fa-times"></i> Batal</button>
        </div>
    </form>
</div>

<!-- Filter & Pencarian -->
<div class="card" style="margin-bottom:20px">
    <form method="GET" action="{{ route('service-prices.index') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:2;min-width:200px">
            <label class="text-xs font-bold text-muted">Cari Kerusakan / Jasa</label>
            <input type="text" name="search" class="form-input" placeholder="Cari..." value="{{ request('search') }}">
        </div>
        <div style="flex:1;min-width:150px">
            <label class="text-xs font-bold text-muted">Merk HP</label>
            <select name="merk" class="form-input">
                <option value="">Semua Merk</option>
                @foreach($merkList as $m)
                <option value="{{ $m }}" {{ request('merk') == $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1;min-width:150px">
            <label class="text-xs font-bold text-muted">Kategori</label>
            <select name="kategori" class="form-input">
                <option value="">Semua</option>
                @foreach($kategoriList as $k)
                <option value="{{ $k }}" {{ request('kategori') == $k ? 'selected' : '' }}>{{ ucfirst($k) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        <a href="{{ route('service-prices.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i> Reset</a>
    </form>
</div>

<!-- Tabel Daftar Harga -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list" style="color:var(--primary);margin-right:6px"></i> Daftar Harga Jasa Service</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Merk / Tipe</th>
                    <th>Kerusakan / Jasa</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Harga Jasa</th>
                    <th>Scope</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($prices as $idx => $sp)
                <tr>
                    <td>{{ ($prices->currentPage() - 1) * $prices->perPage() + $idx + 1 }}</td>
                    <td>
                        @if($sp->merk_hp)
                        <strong style="color:var(--primary)">{{ $sp->merk_hp }}</strong>
                        @if($sp->tipe_hp)
                        <div style="font-size:.72rem;color:#64748b">{{ $sp->tipe_hp }}</div>
                        @endif
                        @else
                        <span style="color:#94a3b8;font-size:.8rem">Semua Merk</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $sp->kerusakan }}</strong>
                    </td>
                    <td>
                        <span class="badge badge-{{ $sp->kategori === 'hardware' ? 'pending' : ($sp->kategori === 'software' ? 'proses' : 'normal') }}">
                            {{ ucfirst($sp->kategori) }}
                        </span>
                    </td>
                    <td style="font-size:.78rem;color:#64748b">{{ $sp->deskripsi ?? '-' }}</td>
                    <td>
                        <strong style="color:var(--success);font-size:.95rem">{{ formatRp($sp->harga_jasa) }}</strong>
                    </td>
                    <td>
                        @if($sp->cabang_id)
                        <span style="font-size:.72rem;color:#64748b"><i class="fas fa-store"></i> {{ $sp->cabang?->nama ?? 'Cabang' }}</span>
                        @else
                        <span style="font-size:.72rem;color:var(--primary)"><i class="fas fa-globe"></i> Global</span>
                        @endif
                        @if(!$sp->aktif)
                        <span class="badge badge-pending" style="margin-left:4px">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px">
                            <button type="button" class="btn btn-primary btn-xs" onclick="editHarga({{ $sp->id }}, '{{ addslashes($sp->merk_hp ?? '') }}', '{{ addslashes($sp->tipe_hp ?? '') }}', '{{ addslashes($sp->kerusakan) }}', '{{ addslashes($sp->deskripsi ?? '') }}', '{{ $sp->harga_jasa }}', '{{ addslashes($sp->kategori ?? 'umum') }}', {{ $sp->aktif ? 1 : 0 }})"><i class="fas fa-edit"></i></button>
                            <form method="POST" action="{{ route('service-prices.destroy', $sp) }}" style="display:inline" onsubmit="return confirm('Hapus harga jasa ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
                @if($prices->count() === 0)
                <tr>
                    <td colspan="8" style="text-align:center;color:#94a3b8;padding:30px">
                        <i class="fas fa-tags" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4"></i>
                        Belum ada daftar harga service.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    <div style="margin-top:12px">
        {{ $prices->links() }}
    </div>
</div>

<!-- Modal Edit -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:28px;max-width:540px;width:90%">
        <h3 style="font-size:1rem;margin-bottom:16px"><i class="fas fa-edit" style="color:var(--primary);margin-right:6px"></i> Edit Harga Service</h3>
        <form id="editForm" method="POST" action="">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label>Merk HP</label>
                    <input type="text" name="merk_hp" id="editMerk" class="form-input">
                </div>
                <div class="form-group">
                    <label>Tipe HP</label>
                    <input type="text" name="tipe_hp" id="editTipe" class="form-input">
                </div>
            </div>
            <div class="form-group">
                <label>Jenis Kerusakan *</label>
                <input type="text" name="kerusakan" id="editKerusakan" class="form-input" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label>Harga Jasa (Rp) *</label>
                    <input type="text" inputmode="numeric" name="harga_jasa" id="editHarga" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori" id="editKategori" class="form-input">
                        <option value="umum">Umum</option>
                        <option value="hardware">Hardware</option>
                        <option value="software">Software</option>
                        <option value="ganti-sparepart">Ganti Sparepart</option>
                        <option value="unlock">Unlock / Bypass</option>
                        <option value="water-damage">Water Damage</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <input type="text" name="deskripsi" id="editDeskripsi" class="form-input">
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="aktif" id="editAktif" value="1" style="width:18px;height:18px;accent-color:var(--primary)">
                    <span>Aktif</span>
                </label>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()"><i class="fas fa-times"></i> Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAddForm() {
    const panel = document.getElementById('addFormPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function editHarga(id, merk, tipe, kerusakan, deskripsi, harga, kategori, aktif) {
    document.getElementById('editForm').action = '/service-prices/' + id;
    document.getElementById('editMerk').value = merk;
    document.getElementById('editTipe').value = tipe;
    document.getElementById('editKerusakan').value = kerusakan;
    document.getElementById('editDeskripsi').value = deskripsi;
    document.getElementById('editHarga').value = harga;
    document.getElementById('editKategori').value = kategori;
    document.getElementById('editAktif').checked = aktif == 1;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>
@endsection
