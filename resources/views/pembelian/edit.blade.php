@extends('layouts.app')
@section('title', 'Edit Pembelian')

@section('content')
<a href="{{ route('pembelian.show', $pembelian) }}" class="btn btn-secondary btn-sm mb-3"><i class="fas fa-arrow-left"></i> Kembali</a>

<h2 class="mb-4"><i class="fas fa-edit" style="color:var(--primary);margin-right:6px"></i> Edit Pembelian {{ $pembelian->kode }}</h2>

@if($pembelian->isDraft())
<div class="card mb-4" style="background:linear-gradient(135deg,#f1f5f9,#e2e8f0);border:1px solid #cbd5e1;padding:12px 16px;font-size:.78rem;color:#334155">
    <i class="fas fa-info-circle"></i> Ini transaksi <strong>Draft</strong> — stok belum ditambahkan, jadi barang & perhitungan masih bisa diubah penuh.
</div>
@else
<div class="card mb-4" style="background:linear-gradient(135deg,#fef3c7,#fde68a);border:1px solid #fcd34d;padding:12px 16px;font-size:.78rem;color:#78350f">
    <i class="fas fa-exclamation-triangle"></i> Transaksi sudah diproses (stok sudah masuk). Hanya <strong>data header</strong> (supplier, tanggal, jatuh tempo, metode, catatan) yang bisa diedit. Perubahan barang harus lewat <strong>Retur</strong>.
</div>
@endif

<form method="POST" action="{{ route('pembelian.update', $pembelian) }}" id="formPembelian">
    @csrf
    @method('PUT')

    <div class="card mb-4">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-building"></i> Data Supplier</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px">
            <div class="form-group">
                <label>Nama Supplier *</label>
                <input type="text" name="supplier_nama" class="form-input" required value="{{ old('supplier_nama', $pembelian->supplier_nama) }}">
            </div>
            <div class="form-group">
                <label>Kontak (No HP)</label>
                <input type="text" name="supplier_kontak" class="form-input" value="{{ old('supplier_kontak', $pembelian->supplier_kontak) }}">
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label>Alamat</label>
                <input type="text" name="supplier_alamat" class="form-input" value="{{ old('supplier_alamat', $pembelian->supplier_alamat) }}">
            </div>
            <div class="form-group">
                <label>Tanggal Pembelian *</label>
                <input type="date" name="tanggal" class="form-input" required value="{{ old('tanggal', $pembelian->tanggal?->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label>Jatuh Tempo</label>
                <input type="date" name="tanggal_jatuh_tempo" class="form-input" value="{{ old('tanggal_jatuh_tempo', $pembelian->tanggal_jatuh_tempo?->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label>Metode Bayar</label>
                <select name="metode_bayar" class="form-input">
                    @foreach(['Cash','Transfer','QRIS'] as $m)
                    <option value="{{ $m }}" {{ old('metode_bayar', $pembelian->metode_bayar) === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if($pembelian->isDraft())
    {{-- ===== Edit penuh item (hanya Draft) ===== --}}
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
                <thead><tr><th>#</th><th>Barang</th><th>Qty</th><th>Harga Beli</th><th>Diskon Item</th><th>Harga Jual</th><th>Subtotal</th><th></th></tr></thead>
                <tbody id="itemBody"></tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-calculator"></i> Perhitungan</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px" class="grid-responsive">
            <div>
                <div class="form-group">
                    <label>Diskon Transaksi (%)</label>
                    <input type="number" name="diskon_persen" id="diskonPersen" class="form-input" min="0" max="100" step="0.01" value="{{ old('diskon_persen', $pembelian->diskon_persen) }}" oninput="recalc()">
                </div>
                <div class="form-group">
                    <label>Diskon Nominal (Rp)</label>
                    <input type="number" name="diskon_nominal" id="diskonNominal" class="form-input" min="0" step="1000" value="{{ old('diskon_nominal', $pembelian->diskon_nominal) }}" oninput="recalc()">
                </div>
                <div class="form-group">
                    <label>Biaya Tambahan (Rp)</label>
                    <input type="number" name="biaya_tambahan" id="biayaTambahan" class="form-input" min="0" step="1000" value="{{ old('biaya_tambahan', $pembelian->biaya_tambahan) }}" oninput="recalc()">
                </div>
                <div class="form-group">
                    <label>Ongkir (Rp)</label>
                    <input type="number" name="ongkir" id="ongkir" class="form-input" min="0" step="1000" value="{{ old('ongkir', $pembelian->ongkir) }}" oninput="recalc()">
                </div>
            </div>
            <div style="background:#f8fafc;border-radius:12px;padding:16px;align-self:start">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px"><span>Subtotal</span><strong id="sumSubtotal">Rp 0</strong></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;color:#dc2626"><span>Diskon</span><strong id="sumDiskon">- Rp 0</strong></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;color:#2563eb"><span>Biaya Tambahan + Ongkir</span><strong id="sumBiaya">+ Rp 0</strong></div>
                <div style="display:flex;justify-content:space-between;padding-top:10px;border-top:2px solid #e2e8f0;font-size:1.1rem"><strong>Total</strong><strong id="sumTotal" style="color:var(--primary)">Rp 0</strong></div>
            </div>
        </div>
    </div>
    @endif

    <div class="card mb-4">
        <div class="form-group">
            <label>Catatan Pembelian</label>
            <textarea name="catatan" class="form-input" rows="2">{{ old('catatan', $pembelian->catatan) }}</textarea>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
            <a href="{{ route('pembelian.show', $pembelian) }}" class="btn btn-secondary">Batal</a>
        </div>
    </div>
</form>

@if($pembelian->isDraft())
<script>
@php
    $stokListData = $stoks->map(function($s) {
        return ['id'=>$s->id,'kode'=>$s->kode,'barcode'=>$s->barcode,'nama'=>$s->nama,'modal'=>(float)$s->modal,'jual'=>(float)$s->jual,'stok'=>(int)$s->stok];
    });
    $existingItems = old('items', $pembelian->items ?? []);
@endphp
const stokList = @json($stokListData);
const existingItems = @json($existingItems);
let rowCount = 0;

function escHtml(s) { return (s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

function addRow(data = {}) {
    rowCount++;
    const body = document.getElementById('itemBody');
    const tr = document.createElement('tr');
    tr.id = 'row_' + rowCount;
    const idx = rowCount - 1;
    const stok = data.stok_id ? stokList.find(s => s.id == data.stok_id) : null;

    tr.innerHTML =
        '<td>' + rowCount + '</td>' +
        '<td style="min-width:260px">' +
            '<input type="text" class="form-input nama-input" name="items[' + idx + '][nama]" placeholder="Nama barang" required style="font-size:.78rem;padding:5px 8px" value="' + escHtml(data.nama || '') + '">' +
            '<div style="font-size:.66rem;color:#94a3b8;margin-top:2px" class="kode-info">' + (data.kode ? escHtml(data.kode) : 'barang baru') + '</div>' +
            '<input type="hidden" class="stok-id" name="items[' + idx + '][stok_id]" value="' + (data.stok_id || '') + '">' +
            '<input type="hidden" class="kode-input" name="items[' + idx + '][kode]" value="' + escHtml(data.kode || '') + '">' +
        '</td>' +
        '<td><input type="number" class="form-input qty-input" name="items[' + idx + '][qty]" min="1" value="' + (data.qty || 1) + '" required style="width:75px;font-size:.78rem;padding:5px 8px" oninput="recalc()"></td>' +
        '<td><input type="number" class="form-input beli-input" name="items[' + idx + '][harga_beli]" min="0" step="100" required value="' + (data.harga_beli ?? (stok ? stok.modal : 0)) + '" style="width:115px;font-size:.78rem;padding:5px 8px" oninput="recalc()"></td>' +
        '<td><input type="number" class="form-input diskon-item-input" name="items[' + idx + '][diskon_item]" min="0" step="100" value="' + (data.diskon_item || 0) + '" style="width:100px;font-size:.78rem;padding:5px 8px" oninput="recalc()"></td>' +
        '<td><input type="number" class="form-input jual-input" name="items[' + idx + '][harga_jual]" min="0" step="100" value="' + (data.harga_jual ?? (stok ? stok.jual : 0)) + '" style="width:115px;font-size:.78rem;padding:5px 8px"></td>' +
        '<td style="font-weight:600" class="sub-cell">Rp 0</td>' +
        '<td><button type="button" onclick="removeRow(\'row_' + rowCount + '\')" style="background:#fee2e2;border:none;color:#dc2626;width:30px;height:30px;border-radius:6px;cursor:pointer"><i class="fas fa-times"></i></button></td>';
    body.appendChild(tr);
    recalc();
}

function removeRow(id) { document.getElementById(id)?.remove(); recalc(); }

function recalc() {
    let subtotal = 0;
    document.querySelectorAll('#itemBody tr').forEach(tr => {
        const qty  = parseFloat(tr.querySelector('.qty-input')?.value || 0);
        const beli = parseFloat(tr.querySelector('.beli-input')?.value || 0);
        const disk = parseFloat(tr.querySelector('.diskon-item-input')?.value || 0);
        const sub  = Math.max(0, qty * beli - disk);
        subtotal += sub;
        const cell = tr.querySelector('.sub-cell');
        if (cell) cell.textContent = 'Rp ' + formatRpJs(sub);
    });
    const dpersen = parseFloat(document.getElementById('diskonPersen')?.value || 0);
    const dnom    = parseFloat(document.getElementById('diskonNominal')?.value || 0);
    const biaya   = parseFloat(document.getElementById('biayaTambahan')?.value || 0);
    const ongkir  = parseFloat(document.getElementById('ongkir')?.value || 0);
    const diskon  = (subtotal * dpersen / 100) + dnom;
    const total   = Math.max(0, subtotal - diskon) + biaya + ongkir;

    document.getElementById('sumSubtotal').textContent = 'Rp ' + formatRpJs(subtotal);
    document.getElementById('sumDiskon').textContent   = '- Rp ' + formatRpJs(diskon);
    document.getElementById('sumBiaya').textContent    = '+ Rp ' + formatRpJs(biaya + ongkir);
    document.getElementById('sumTotal').textContent    = 'Rp ' + formatRpJs(total);
}

function formatRpJs(n) { n = Math.round(n || 0); return n.toLocaleString('id-ID'); }

// Pencarian produk
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
    const list = stokList.filter(s => !q || s.nama.toLowerCase().includes(q) || s.kode.toLowerCase().includes(q) || (s.barcode||'').toLowerCase().includes(q)).slice(0, 30);
    dropdown.innerHTML = list.length === 0
        ? '<div style="padding:10px;color:#94a3b8;font-size:.78rem">Tidak ditemukan.</div>'
        : list.map(s => `<div class="produk-item" data-id="${s.id}" style="padding:8px 12px;cursor:pointer;display:flex;justify-content:space-between;font-size:.8rem;border-bottom:1px solid #f1f5f9"><div><strong>${escHtml(s.nama)}</strong><div style="font-size:.68rem;color:#94a3b8">${s.kode}</div></div><div style="color:#16a34a;font-weight:600">${formatRpJs(s.modal)}</div></div>`).join('');
    dropdown.querySelectorAll('.produk-item').forEach(el => {
        el.addEventListener('click', () => {
            const s = stokList.find(x => x.id == el.dataset.id);
            if (s) addRow({ stok_id: s.id, nama: s.nama, kode: s.kode, qty: 1, harga_beli: s.modal, harga_jual: s.jual, diskon_item: 0 });
            dropdown.style.display = 'none';
            searchInput.value = '';
        });
    });
    dropdown.style.display = 'block';
}
searchInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const first = dropdown.querySelector('.produk-item');
        if (first) first.click();
    }
});

// Muat item yang sudah ada
existingItems.forEach(it => addRow(it));
if (existingItems.length === 0) addRow();
recalc();
</script>
@endif

<style>
@media (max-width: 900px) { .grid-responsive { grid-template-columns: 1fr !important; } }
</style>
@endsection
