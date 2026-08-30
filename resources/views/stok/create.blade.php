@extends('layouts.app')
@section('title', 'Tambah Barang')
@section('content')
<h2 class="mb-4">Tambah Barang</h2>

@if ($errors->any())
<div class="card mb-4" style="border-left:4px solid var(--danger);background:#fef2f2">
    <strong style="color:var(--danger)"><i class="fas fa-exclamation-circle"></i> Barang gagal disimpan:</strong>
    <ul style="margin:8px 0 0 18px;color:#b91c1c">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card">
    <form method="POST" action="{{ route('stok.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group"><label>Kode *</label><input type="text" name="kode" class="form-input" value="{{ old('kode') }}" placeholder="LCD-IP11" required></div>
            <div class="form-group"><label>Nama *</label><input type="text" name="nama" class="form-input" value="{{ old('nama') }}" required></div>
        </div>
        <div class="form-group"><label>Barcode <span style="font-size:.7rem;color:#94a3b8;font-weight:400">(kosongkan = auto-generate)</span></label>
            <input type="text" name="barcode" class="form-input" value="{{ old('barcode') }}" placeholder="FXP0000001 (auto jika kosong)">
        </div>
        <div class="form-group"><label>Kategori</label>
            <select name="kategori" class="form-input">
                @foreach(['LCD','Baterai','Flexibel','IC','Konektor','Aksesoris','Lainnya'] as $cat)
                <option value="{{ $cat }}" {{ old('kategori') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group"><label>Merk HP</label>
            <select name="merk_hp" class="form-input">
                <option value="">-- Semua Merk --</option>
                @foreach(['Apple','Samsung','Xiaomi','OPPO','Vivo','Realme','Infinix','Tecno','OnePlus','Huawei','Poco','Motorola','Nokia','Asus','Lenovo','Lainnya'] as $merk)
                <option value="{{ $merk }}" {{ old('merk_hp') === $merk ? 'selected' : '' }}>{{ $merk }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Stok Awal</label><input type="number" name="stok" class="form-input" value="{{ old('stok', '0') }}" min="0"></div>
            <div class="form-group"><label>Min Alert</label><input type="number" name="min_alert" class="form-input" value="{{ old('min_alert', '3') }}" min="0"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Harga Modal (Rp)</label><input type="text" inputmode="numeric" name="modal" class="form-input" value="{{ old('modal', '0') }}" min="0" data-format-rupiah></div>
            <div class="form-group"><label>Harga Jual (Rp)</label><input type="text" inputmode="numeric" name="jual" class="form-input" value="{{ old('jual', '0') }}" min="0" data-format-rupiah></div>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="{{ route('stok.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
