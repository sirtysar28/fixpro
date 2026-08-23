@extends('layouts.app')
@section('title', 'Edit Teknisi')
@section('content')
<h2 class="mb-4">Edit Teknisi</h2>
<div class="card">
    <form method="POST" action="{{ route('teknisi.update', $teknisi) }}">
        @csrf @method('PUT')
        <div class="form-group"><label>Nama *</label><input type="text" name="nama" class="form-input" value="{{ $teknisi->nama }}" required></div>
        <div class="form-group"><label>No. WhatsApp</label><input type="tel" name="no_wa" class="form-input" value="{{ $teknisi->no_wa }}"></div>
        <div class="form-group"><label>Spesialisasi *</label><input type="text" name="spesialisasi" class="form-input" value="{{ $teknisi->spesialisasi }}" required></div>
        <div class="form-row">
            @if($showCabangPicker)
            <div class="form-group"><label>Cabang *</label><select name="cabang_id" class="form-input" required>
                <option value="">-- Pilih Cabang --</option>
                @foreach($cabangs as $c)
                <option value="{{ $c->id }}" {{ $teknisi->cabang_id == $c->id ? 'selected' : '' }}>{{ $c->nama }}</option>
                @endforeach
            </select></div>
            @else
            <input type="hidden" name="cabang_id" value="{{ $teknisi->cabang_id }}">
            <div class="form-group"><label>Cabang</label><input type="text" class="form-input" value="{{ $teknisi->cabang?->nama ?? '-' }}" disabled style="background:#f1f5f9"></div>
            @endif
            <div class="form-group"><label>Bagi Hasil (%)</label><input type="number" name="bagi_hasil" class="form-input" value="{{ $teknisi->bagi_hasil ?? 35 }}" min="0" max="100" step="0.01"></div>
        </div>
        <div class="form-group"><label>Alamat</label><input type="text" name="alamat" class="form-input" value="{{ $teknisi->alamat }}"></div>
        <div class="form-group">
            <label>Akun Login Teknisi (opsional)</label>
            <select name="link_user_id" class="form-input">
                <option value="">-- Tidak terhubung --</option>
                @foreach(\App\Models\User::whereHas('role', fn($q) => $q->where('name', 'Teknisi'))->orderBy('name')->get() as $u)
                <option value="{{ $u->id }}" {{ $teknisi->users()->where('users.id', $u->id)->exists() ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
            <div style="font-size:.7rem;color:#94a3b8;margin-top:4px">Hubungkan dengan akun user yang role-nya Teknisi agar bisa login ke dashboard</div>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="aktif" class="form-input">
                <option value="1" {{ $teknisi->aktif ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ !$teknisi->aktif ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            <a href="{{ route('teknisi.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
