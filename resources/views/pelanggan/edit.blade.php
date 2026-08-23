@extends('layouts.app')
@section('title', 'Edit Pelanggan')

@section('content')
<h2 class="mb-4">Edit Pelanggan</h2>
@if($pelanggan->user)
<div class="card mb-4" style="background:#f0fdf4;border-left:4px solid #10b981;padding:12px">
    <p style="margin:0;font-size:13px;color:#166534">
        <i class="fas fa-link"></i> <strong>Terhubung ke Akun User:</strong> {{ $pelanggan->user->email }} — Perubahan nama & no HP akan otomatis sinkron ke akun user.
    </p>
</div>
@else
<div class="card mb-4" style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px">
    <p style="margin:0;font-size:13px;color:#92400e">
        <i class="fas fa-exclamation-triangle"></i> Pelanggan ini <strong>belum terhubung</strong> ke akun user manapun.
    </p>
</div>
@endif
<div class="card">
    <form method="POST" action="{{ route('pelanggan.update', $pelanggan) }}">
        @csrf @method('PUT')
        <div class="form-group"><label>Nama *</label><input type="text" name="nama" class="form-input" value="{{ $pelanggan->nama }}" required></div>
        <div class="form-group"><label>No. HP *</label><input type="tel" name="no_hp" class="form-input" value="{{ $pelanggan->no_hp }}" required></div>
        <div class="form-group"><label>Alamat</label><input type="text" name="alamat" class="form-input" value="{{ $pelanggan->alamat }}"></div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            <a href="{{ route('pelanggan.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
