@extends('layouts.app')
@section('title', 'Tambah Barang')
@section('content')
<h2 class="mb-4">Tambah Barang</h2>
<div class="card">
    <form method="POST" action="{{ route('stok.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group"><label>Kode *</label><input type="text" name="kode" class="form-input" placeholder="LCD-IP11" required></div>
            <div class="form-group"><label>Nama *</label><input type="text" name="nama" class="form-input" required></div>
        </div>
        <div class="form-group"><label>Barcode <span style="font-size:.7rem;color:#94a3b8;font-weight:400">(kosongkan = auto-generate)</span></label>
            <input type="text" name="barcode" class="form-input" placeholder="FXP0000001 (auto jika kosong)">
        </div>
        <div class="form-group"><label>Kategori</label>
            <select name="kategori" class="form-input">
                <option>LCD</option><option>Baterai</option><option>Flexibel</option>
                <option>IC</option><option>Konektor</option><option>Aksesoris</option><option>Lainnya</option>
            </select>
        </div>
        <div class="form-group"><label>Merk HP</label>
            <select name="merk_hp" class="form-input">
                <option value="">-- Semua Merk --</option>
                <option>Apple</option><option>Samsung</option><option>Xiaomi</option><option>OPPO</option>
                <option>Vivo</option><option>Realme</option><option>Infinix</option><option>Tecno</option>
                <option>OnePlus</option><option>Huawei</option><option>Poco</option><option>Motorola</option>
                <option>Nokia</option><option>Asus</option><option>Lenovo</option><option>Lainnya</option>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Stok Awal</label><input type="number" name="stok" class="form-input" value="0" min="0"></div>
            <div class="form-group"><label>Min Alert</label><input type="number" name="min_alert" class="form-input" value="3" min="0"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Harga Modal (Rp)</label><input type="text" inputmode="numeric" name="modal" class="form-input" value="0" min="0" data-format-rupiah></div>
            <div class="form-group"><label>Harga Jual (Rp)</label><input type="text" inputmode="numeric" name="jual" class="form-input" value="0" min="0" data-format-rupiah></div>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="{{ route('stok.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
