@extends('layouts.app')
@section('title', 'Edit Barang')
@section('content')
<h2 class="mb-4">Edit Barang</h2>
<div class="card">
    <form method="POST" action="{{ route('stok.update', $stok) }}">
        @csrf @method('PUT')
        <div class="form-row">
            <div class="form-group"><label>Kode *</label><input type="text" name="kode" class="form-input" value="{{ $stok->kode }}" required></div>
            <div class="form-group"><label>Nama *</label><input type="text" name="nama" class="form-input" value="{{ $stok->nama }}" required></div>
        </div>
        <div class="form-group"><label>Barcode</label>
            <input type="text" name="barcode" class="form-input" value="{{ $stok->barcode ?? '' }}" placeholder="FXP0000001">
        </div>
        <div class="form-group"><label>Kategori</label>
            <select name="kategori" class="form-input">
                @foreach(['LCD','Baterai','Flexibel','IC','Konektor','Aksesoris','Lainnya'] as $cat)
                <option {{ $stok->kategori === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group"><label>Merk HP</label>
            <select name="merk_hp" class="form-input">
                <option value="">-- Semua Merk --</option>
                @foreach(['Apple','Samsung','Xiaomi','OPPO','Vivo','Realme','Infinix','Tecno','OnePlus','Huawei','Poco','Motorola','Nokia','Asus','Lenovo','Lainnya'] as $merk)
                <option {{ ($stok->merk_hp ?? '') === $merk ? 'selected' : '' }}>{{ $merk }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Stok</label><input type="number" name="stok" class="form-input" value="{{ $stok->stok }}" min="0"></div>
            <div class="form-group"><label>Min Alert</label><input type="number" name="min_alert" class="form-input" value="{{ $stok->min_alert }}" min="0"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Harga Modal (Rp)</label><input type="text" inputmode="numeric" name="modal" class="form-input" value="{{ (int) $stok->modal }}" min="0" data-format-rupiah></div>
            <div class="form-group"><label>Harga Jual (Rp)</label><input type="text" inputmode="numeric" name="jual" class="form-input" value="{{ (int) $stok->jual }}" min="0" data-format-rupiah></div>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            <a href="{{ route('stok.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
