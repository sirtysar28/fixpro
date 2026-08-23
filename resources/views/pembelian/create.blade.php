@extends('layouts.app')
@section('title', 'Pembelian Baru')

@section('content')
<h2 class="mb-4"><i class="fas fa-truck-loading" style="color:var(--primary);margin-right:6px"></i>Pembelian Baru</h2>

<div class="card mb-4" style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1px solid #6ee7b7;padding:12px 16px;font-size:.78rem;color:#065f46">
    <i class="fas fa-info-circle"></i> Nomor pembelian dibuat <strong>otomatis</strong> ({{ $kodePreview }}). Pembelian non-draft akan <strong>menambah stok otomatis</strong> & memperbarui <strong>harga modal (HPP)</strong>. Jika bayar kurang dari total, sisanya tercatat sebagai <strong>hutang supplier</strong>. Simpan sebagai <strong>Draft</strong> jika barang belum datang (stok belum berubah).
</div>

<form method="POST" action="{{ route('pembelian.store') }}" id="formPembelian">
    @csrf

    {{-- ===== 1. DATA SUPPLIER ===== --}}
    <div class="card mb-4">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-building"></i> Data Supplier</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px">
            <div class="form-group">
                <label>Pilih / Nama Supplier *</label>
                <input type="text" name="supplier_nama" id="supplierNama" class="form-input" list="supplierList" required placeholder="PT Sumber Sparepart" value="{{ old('supplier_nama') }}" autocomplete="off">
                <datalist id="supplierList">
                    @foreach($suppliers as $s)
                    <option value="{{ $s->supplier_nama }}">{{ $s->supplier_kontak }}</option>
                    @endforeach
                </datalist>
            </div>
            <div class="form-group">
                <label>Kontak (No HP)</label>
                <input type="text" name="supplier_kontak" id="supplierKontak" class="form-input" placeholder="0812..." value="{{ old('supplier_kontak') }}">
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label>Alamat</label>
                <input type="text" name="supplier_alamat" id="supplierAlamat" class="form-input" placeholder="Alamat supplier" value="{{ old('supplier_alamat') }}">
            </div>
            <div class="form-group">
                <label>Tanggal Pembelian *</label>
                <input type="date" name="tanggal" class="form-input" required value="{{ old('tanggal', date('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label>Jatuh Tempo (opsional)</label>
                <input type="date" name="tanggal_jatuh_tempo" class="form-input" value="{{ old('tanggal_jatuh_tempo') }}">
            </div>
            <div class="form-group">
                <label>Metode Bayar</label>
                <select name="metode_bayar" class="form-input">
                    @foreach(['Cash','Transfer','QRIS'] as $m)
                    <option value="{{ $m }}" {{ old('metode_bayar') === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Status Transaksi</label>
                <select name="status_transaksi" class="form-input" id="statusTransaksi">
                    <option value="Selesai" {{ old('status_transaksi') === 'Selesai' ? 'selected' : '' }}>Selesai (barang datang, stok langsung masuk)</option>
                    <option value="Diproses" {{ old('status_transaksi') === 'Diproses' ? 'selected' : '' }}>Diproses (stok masuk, transaksi berjalan)</option>
                    <option value="Draft" {{ old('status_transaksi') === 'Draft' ? 'selected' : '' }}>Draft (belum menambah stok)</option>
                </select>
            </div>
        </div>
    </div>

    {{-- ===== 2. DETAIL BARANG ===== --}}
    <div class="card mb-4">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px">
            <h3 style="font-size:.95rem;margin:0"><i class="fas fa-boxes"></i> Detail Barang</h3>
            <div style="display:flex;gap:8px;align-items:center">
                <div style="position:relative">
                    <input type="text" id="produkSearch" class="form-input" placeholder="🔍 Cari produk / scan barcode..." style="padding:8px 12px;font-size:.8rem;width:260px" autocomplete="off">
                    <div id="produkDropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 20px rgba(0,0,0,.12);max-height:260px;overflow-y:auto;z-index:50"></div>
                </div>
                <button type="button" onclick="addRow()" class="btn btn-success btn-sm" style="background:#16a34a;color:#fff"><i class="fas fa-plus"></i> Tambah Baris</button>
            </div>
        </div>
        <div class="table-wrap">
            <table id="itemTable">
                <thead><tr><th>#</th><th>Barang (pilih / ketik baru)</th><th>Qty</th><th>Harga Beli (Modal)</th><th>Diskon Item (Rp)</th><th>Harga Jual</th><th>Subtotal</th><th></th></tr></thead>
                <tbody id="itemBody"></tbody>
            </table>
        </div>
        <div style="font-size:.72rem;color:#94a3b8;margin-top:8px"><i class="fas fa-lightbulb" style="color:#f59e0b"></i> Gunakan kolom pencarian untuk mencari produk, atau scan barcode/SKU — produk otomatis ditambahkan ke daftar.</div>
    </div>

    {{-- ===== 3. PERHITUNGAN & PEMBAYARAN ===== --}}
    <div class="card mb-4">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-calculator"></i> Perhitungan & Pembayaran</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px" class="grid-responsive">
            <div>
                <div class="form-group">
                    <label>Diskon Transaksi (%)</label>
                    <input type="number" name="diskon_persen" id="diskonPersen" class="form-input" min="0" max="100" step="0.01" value="{{ old('diskon_persen', 0) }}" oninput="recalc()">
                </div>
                <div class="form-group">
                    <label>Diskon Transaksi Nominal (Rp)</label>
                    <input type="number" name="diskon_nominal" id="diskonNominal" class="form-input" min="0" step="1000" value="{{ old('diskon_nominal', 0) }}" oninput="recalc()">
                </div>
                <div class="form-group">
                    <label>Biaya Tambahan (Rp)</label>
                    <input type="number" name="biaya_tambahan" id="biayaTambahan" class="form-input" min="0" step="1000" value="{{ old('biaya_tambahan', 0) }}" oninput="recalc()" placeholder="Biaya admin, kemasan, dll">
                </div>
                <div class="form-group">
                    <label>Ongkir (Rp)</label>
                    <input type="number" name="ongkir" id="ongkir" class="form-input" min="0" step="1000" value="{{ old('ongkir', 0) }}" oninput="recalc()" placeholder="Ongkos kirim">
                </div>
                <div class="form-group">
                    <label>Catatan Pembelian</label>
                    <textarea name="catatan" class="form-input" rows="2">{{ old('catatan') }}</textarea>
                </div>
            </div>
            <div style="background:#f8fafc;border-radius:12px;padding:16px;align-self:start">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px"><span>Subtotal</span><strong id="sumSubtotal">Rp 0</strong></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;color:#dc2626"><span>Diskon Transaksi</span><strong id="sumDiskon">- Rp 0</strong></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;color:#2563eb"><span>Biaya Tambahan</span><strong id="sumBiaya">+ Rp 0</strong></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;color:#2563eb"><span>Ongkir</span><strong id="sumOngkir">+ Rp 0</strong></div>
                <div style="display:flex;justify-content:space-between;padding-top:10px;border-top:2px solid #e2e8f0;margin-bottom:14px;font-size:1.1rem"><strong>Total Pembelian</strong><strong id="sumTotal" style="color:var(--primary)">Rp 0</strong></div>
                <div class="form-group">
                    <label>Dibayar (Rp)</label>
                    <input type="number" name="dibayar" id="dibayar" class="form-input" min="0" step="1000" value="{{ old('dibayar', 0) }}" oninput="recalc()">
                    <div style="display:flex;gap:6px;margin-top:6px">
                        <button type="button" class="btn btn-xs" style="background:#dcfce7;color:#16a34a" onclick="setDibayar('lunas')">Bayar Lunas</button>
                        <button type="button" class="btn btn-xs" style="background:#fee2e2;color:#dc2626" onclick="setDibayar(0)">Hutang Semua</button>
                    </div>
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:10px;color:#dc2626;font-weight:700"><span>Sisa Hutang (otomatis)</span><strong id="sumSisa">Rp 0</strong></div>
                <div id="statusPreview" style="margin-top:10px;text-align:center;font-weight:700;font-size:.8rem"></div>
            </div>
        </div>
        <div style="margin-top:16px;display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Pembelian</button>
            <a href="{{ route('pembelian.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </div>
</form>

<script>
@php
    $stokListData = $stoks->map(function($s) {
        return ['id'=>$s->id,'kode'=>$s->kode,'barcode'=>$s->barcode,'nama'=>$s->nama,'modal'=>(float)$s->modal,'jual'=>(float)$s->jual,'stok'=>(int)$s->stok];
    });
@endphp
const stokList = @json($stokListData);
let rowCount = 0;

// ===== Pencarian produk / barcode =====
const searchInput = document.getElementById('produkSearch');
const dropdown = document.getElementById('produkDropdown');
let searchDebounce = null;

searchInput.addEventListener('input', function () {
    clearTimeout(searchDebounce);
    const q = this.value.trim().toLowerCase();
    searchDebounce = setTimeout(() => renderDropdown(q), 150);
});
searchInput.addEventListener('focus', () => renderDropdown(searchInput.value.trim().toLowerCase()));
document.addEventListener('click', e => {
    if (!e.target.closest('#produkSearch') && !e.target.closest('#produkDropdown')) dropdown.style.display = 'none';
});

function renderDropdown(q) {
    const list = stokList.filter(s =>
        !q || s.nama.toLowerCase().includes(q) || s.kode.toLowerCase().includes(q) || (s.barcode||'').toLowerCase().includes(q)
    ).slice(0, 30);

    if (list.length === 0) {
        dropdown.innerHTML = '<div style="padding:10px;color:#94a3b8;font-size:.78rem">Produk tidak ditemukan — ketik manual di baris tabel.</div>';
    } else {
        dropdown.innerHTML = list.map(s => `
            <div class="produk-item" data-id="${s.id}" style="padding:8px 12px;cursor:pointer;display:flex;justify-content:space-between;gap:8px;font-size:.8rem;border-bottom:1px solid #f1f5f9" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background=''">
                <div>
                    <strong>${escHtml(s.nama)}</strong>
                    <div style="font-size:.68rem;color:#94a3b8">${s.kode} ${s.barcode ? '• ' + s.barcode : ''}</div>
                </div>
                <div style="text-align:right;white-space:nowrap">
                    <div style="color:#16a34a;font-weight:600">${formatRpJs(s.modal)}</div>
                    <div style="font-size:.68rem;color:#94a3b8">stok: ${s.stok}</div>
                </div>
            </div>`).join('');
        dropdown.querySelectorAll('.produk-item').forEach(el => {
            el.addEventListener('click', () => {
                const s = stokList.find(x => x.id == el.dataset.id);
                if (s) addRow({ stok: s, qty: 1 });
                dropdown.style.display = 'none';
                searchInput.value = '';
                searchInput.focus();
            });
        });
    }
    dropdown.style.display = 'block';
}

// Enter pada pencarian = tambah produk pertama hasil (berguna untuk scan barcode)
searchInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        // Barcode/SKU exact match → langsung tambah
        const q = this.value.trim().toLowerCase();
        const exact = stokList.find(s => s.kode.toLowerCase() === q || (s.barcode||'').toLowerCase() === q);
        if (exact) {
            addRow({ stok: exact, qty: 1 });
            this.value = '';
            dropdown.style.display = 'none';
            return;
        }
        const first = dropdown.querySelector('.produk-item');
        if (first) first.click();
    }
});

function escHtml(s) { return (s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

// ===== Tabel item =====
function addRow(data = {}) {
    rowCount++;
    const body = document.getElementById('itemBody');
    const tr = document.createElement('tr');
    tr.id = 'row_' + rowCount;
    const idx = rowCount - 1;

    tr.innerHTML =
        '<td>' + rowCount + '</td>' +
        '<td style="min-width:260px">' +
            '<input type="text" class="form-input nama-input" name="items[' + idx + '][nama]" placeholder="Nama barang (ketik jika baru)" required style="font-size:.78rem;padding:5px 8px" value="' + escHtml(data.stok ? data.stok.nama : '') + '">' +
            '<div style="font-size:.66rem;color:#94a3b8;margin-top:2px" class="kode-info">' + (data.stok ? (data.stok.kode + (data.stok.barcode ? ' • ' + data.stok.barcode : '') + ' • stok: ' + data.stok.stok) : 'barang baru') + '</div>' +
            '<input type="hidden" class="stok-id" name="items[' + idx + '][stok_id]" value="' + (data.stok ? data.stok.id : '') + '">' +
            '<input type="hidden" class="kode-input" name="items[' + idx + '][kode]" value="' + (data.stok ? data.stok.kode : '') + '">' +
        '</td>' +
        '<td><input type="number" class="form-input qty-input" name="items[' + idx + '][qty]" min="1" value="' + (data.qty || 1) + '" required style="width:75px;font-size:.78rem;padding:5px 8px" oninput="recalc()"></td>' +
        '<td><input type="number" class="form-input beli-input" name="items[' + idx + '][harga_beli]" min="0" step="100" required value="' + (data.stok ? data.stok.modal : 0) + '" style="width:115px;font-size:.78rem;padding:5px 8px" oninput="recalc()"></td>' +
        '<td><input type="number" class="form-input diskon-item-input" name="items[' + idx + '][diskon_item]" min="0" step="100" value="0" style="width:105px;font-size:.78rem;padding:5px 8px" oninput="recalc()"></td>' +
        '<td><input type="number" class="form-input jual-input" name="items[' + idx + '][harga_jual]" min="0" step="100" value="' + (data.stok ? data.stok.jual : 0) + '" style="width:115px;font-size:.78rem;padding:5px 8px"></td>' +
        '<td style="font-weight:600" class="sub-cell">Rp 0</td>' +
        '<td><button type="button" onclick="removeRow(\'row_' + rowCount + '\')" style="background:#fee2e2;border:none;color:#dc2626;width:30px;height:30px;border-radius:6px;cursor:pointer"><i class="fas fa-times"></i></button></td>';
    body.appendChild(tr);
    recalc();
}

function removeRow(id) {
    document.getElementById(id)?.remove();
    recalc();
}

function recalc() {
    let subtotal = 0;
    document.querySelectorAll('#itemBody tr').forEach(tr => {
        const qty   = parseFloat(tr.querySelector('.qty-input')?.value || 0);
        const beli  = parseFloat(tr.querySelector('.beli-input')?.value || 0);
        const disk  = parseFloat(tr.querySelector('.diskon-item-input')?.value || 0);
        const sub   = Math.max(0, qty * beli - disk);
        subtotal += sub;
        const cell = tr.querySelector('.sub-cell');
        if (cell) cell.textContent = 'Rp ' + formatRpJs(sub);
    });

    const dpersen   = parseFloat(document.getElementById('diskonPersen')?.value || 0);
    const dnom      = parseFloat(document.getElementById('diskonNominal')?.value || 0);
    const biaya     = parseFloat(document.getElementById('biayaTambahan')?.value || 0);
    const ongkir    = parseFloat(document.getElementById('ongkir')?.value || 0);
    const diskon    = (subtotal * dpersen / 100) + dnom;
    const total     = Math.max(0, subtotal - diskon) + biaya + ongkir;

    const isDraft = document.getElementById('statusTransaksi').value === 'Draft';
    const dibayar = isDraft ? 0 : parseFloat(document.getElementById('dibayar')?.value || 0);
    const sisa    = Math.max(0, total - dibayar);

    document.getElementById('sumSubtotal').textContent = 'Rp ' + formatRpJs(subtotal);
    document.getElementById('sumDiskon').textContent   = '- Rp ' + formatRpJs(diskon);
    document.getElementById('sumBiaya').textContent    = '+ Rp ' + formatRpJs(biaya);
    document.getElementById('sumOngkir').textContent   = '+ Rp ' + formatRpJs(ongkir);
    document.getElementById('sumTotal').textContent    = 'Rp ' + formatRpJs(total);
    document.getElementById('sumSisa').textContent     = 'Rp ' + formatRpJs(sisa);

    const preview = document.getElementById('statusPreview');
    if (isDraft) {
        preview.textContent = 'DRAFT — stok belum ditambahkan, belum ada pembayaran';
        preview.style.background = '#f1f5f9'; preview.style.color = '#64748b';
    } else if (sisa <= 0) {
        preview.textContent = 'LUNAS';
        preview.style.background = '#dcfce7'; preview.style.color = '#16a34a';
    } else if (dibayar > 0) {
        preview.textContent = 'BAYAR SEBAGIAN';
        preview.style.background = '#fef3c7'; preview.style.color = '#b45309';
    } else {
        preview.textContent = 'HUTANG';
        preview.style.background = '#fee2e2'; preview.style.color = '#dc2626';
    }
    preview.style.borderRadius = '8px';
    preview.style.padding = '6px';
}

function setDibayar(mode) {
    if (mode === 'lunas') {
        const dpersen = parseFloat(document.getElementById('diskonPersen')?.value || 0);
        const dnom = parseFloat(document.getElementById('diskonNominal')?.value || 0);
        const biaya = parseFloat(document.getElementById('biayaTambahan')?.value || 0);
        const ongkir = parseFloat(document.getElementById('ongkir')?.value || 0);
        let subtotal = 0;
        document.querySelectorAll('#itemBody tr').forEach(tr => {
            const qty = parseFloat(tr.querySelector('.qty-input')?.value || 0);
            const beli = parseFloat(tr.querySelector('.beli-input')?.value || 0);
            const disk = parseFloat(tr.querySelector('.diskon-item-input')?.value || 0);
            subtotal += Math.max(0, qty * beli - disk);
        });
        const total = Math.max(0, subtotal - (subtotal * dpersen / 100) - dnom) + biaya + ongkir;
        document.getElementById('dibayar').value = Math.round(total);
    } else {
        document.getElementById('dibayar').value = mode;
    }
    recalc();
}

document.getElementById('statusTransaksi').addEventListener('change', () => {
    const isDraft = document.getElementById('statusTransaksi').value === 'Draft';
    document.getElementById('dibayar').disabled = isDraft;
    document.getElementById('dibayar').value = isDraft ? 0 : document.getElementById('dibayar').value;
    recalc();
});

function formatRpJs(n) {
    n = Math.round(n || 0);
    return n.toLocaleString('id-ID');
}

// init 1 baris kosong
addRow();
recalc();
</script>
@endsection
