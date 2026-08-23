@extends('layouts.app')
@section('title', $pelanggan->exists ? 'Edit Pelanggan Grosir' : 'Pelanggan Grosir Baru')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <h1 style="font-size:1.5rem;margin:0;">{{ $pelanggan->exists ? '✏️ Edit: ' . $pelanggan->nama : '👥 Pelanggan Grosir Baru' }}</h1>
    <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">
        <a href="{{ route('grosir.pelanggan.index') }}">← Kembali ke daftar pelanggan</a>
    </p>
</div>

@if($errors->any())
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i>
    <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ $pelanggan->exists ? route('grosir.pelanggan.update', $pelanggan) : route('grosir.pelanggan.store') }}">
    @csrf
    @if($pelanggan->exists) @method('PUT') @endif

    <div class="card">
        <div class="card-header"><h3>Data Pelanggan</h3></div>
        <div class="form-row">
            <div class="form-group">
                <label>Nama Pelanggan *</label>
                <input type="text" name="nama" value="{{ old('nama', $pelanggan->nama) }}" class="form-input" required>
            </div>
            <div class="form-group">
                <label>No HP (WhatsApp)</label>
                <input type="text" name="no_hp" value="{{ old('no_hp', $pelanggan->no_hp) }}" class="form-input" placeholder="08xxxxxxxxxx">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Tipe Pelanggan</label>
                <select name="tipe" class="form-input">
                    @foreach(\App\Models\PelangganGrosir::TIPE as $t)
                    <option value="{{ $t }}" {{ old('tipe', $pelanggan->tipe ?? 'Grosir') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Level Harga Default</label>
                <select name="level_harga" class="form-input">
                    @foreach(\App\Services\GrosirService::LEVELS as $key => $label)
                    <option value="{{ $key }}" {{ old('level_harga', $pelanggan->level_harga ?? 'grosir1') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Alamat</label>
            <textarea name="alamat" rows="2" class="form-input">{{ old('alamat', $pelanggan->alamat) }}</textarea>
        </div>
        <div class="form-group">
            <label>Alamat Pengiriman (default)</label>
            <textarea name="alamat_kirim" rows="2" class="form-input" placeholder="Dipakai otomatis di nota & surat jalan">{{ old('alamat_kirim', $pelanggan->alamat_kirim) }}</textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Limit Piutang (Rp, 0 = tanpa limit)</label>
                <input type="number" step="any" min="0" name="limit_piutang" value="{{ old('limit_piutang', $pelanggan->limit_piutang ?? 0) }}" class="form-input">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="aktif" class="form-input">
                    <option value="1" {{ old('aktif', $pelanggan->aktif ?? true) ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ !old('aktif', $pelanggan->aktif ?? true) ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Catatan</label>
            <textarea name="catatan" rows="2" class="form-input">{{ old('catatan', $pelanggan->catatan) }}</textarea>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-primary"><i class="fas fa-save"></i> {{ $pelanggan->exists ? 'Update' : 'Simpan' }} Pelanggan</button>
            <a href="{{ route('grosir.pelanggan.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </div>
</form>
@endsection
