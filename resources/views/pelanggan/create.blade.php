@extends('layouts.app')
@section('title', 'Tambah Pelanggan')

@section('content')
<h2 class="mb-4">Tambah Pelanggan</h2>
<div class="card mb-4" style="background:#eff6ff;border-left:4px solid #3b82f6;padding:12px">
    <p style="margin:0;font-size:13px;color:#1e40af">
        <i class="fas fa-info-circle"></i> <strong>Info:</strong> Saat menambahkan pelanggan baru, sistem akan otomatis membuatkan akun user dengan <strong>password = No. HP</strong>. Pelanggan bisa login ke aplikasi menggunakan email yang digenerate (<code>nohp@fixpro.local</code>) atau No. HP mereka.
    </p>
</div>
<div class="card">
    <form method="POST" action="{{ route('pelanggan.store') }}">
        @csrf
        <div class="form-group"><label>Nama *</label><input type="text" name="nama" class="form-input" required></div>
        <div class="form-group"><label>No. HP *</label><input type="tel" name="no_hp" class="form-input" required></div>
        <div class="form-group"><label>Alamat</label><input type="text" name="alamat" class="form-input"></div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="{{ route('pelanggan.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
