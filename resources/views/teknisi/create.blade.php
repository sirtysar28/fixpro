@extends('layouts.app')
@section('title', 'Tambah Teknisi')
@section('content')
<h2 class="mb-4">Tambah Teknisi</h2>

@if(!$showCabangPicker && $activeCabang)
<div class="card mb-4" style="background:#eff6ff;border-left:4px solid #3b82f6;padding:12px 16px;display:flex;align-items:center;gap:10px">
    <i class="fas fa-store" style="color:#3b82f6;font-size:1.1rem"></i>
    <div>
        <strong style="font-size:.85rem">Cabang: {{ $activeCabang->nama }}</strong>
        <div style="font-size:.75rem;color:#64748b">Teknisi akan otomatis terdaftar di cabang ini</div>
    </div>
</div>
@endif

<div class="card">
    <form method="POST" action="{{ route('teknisi.store') }}">
        @csrf
        <div class="form-group"><label>Nama *</label><input type="text" name="nama" class="form-input" required></div>
        <div class="form-group"><label>No. WhatsApp</label><input type="tel" name="no_wa" class="form-input" placeholder="08xxx"></div>
        <div class="form-group"><label>Spesialisasi *</label><input type="text" name="spesialisasi" class="form-input" placeholder="Apple, Samsung, LCD..." required></div>
        <div class="form-row">
            @if($showCabangPicker)
            <div class="form-group"><label>Cabang *</label><select name="cabang_id" class="form-input" required>
                <option value="">-- Pilih Cabang --</option>
                @foreach($cabangs as $c)
                <option value="{{ $c->id }}">{{ $c->nama }}</option>
                @endforeach
            </select></div>
            @else
            <input type="hidden" name="cabang_id" value="{{ $activeCabang?->id }}">
            <div class="form-group"><label>Cabang</label><input type="text" class="form-input" value="{{ $activeCabang?->nama ?? '-' }}" disabled style="background:#f1f5f9"></div>
            @endif
            <div class="form-group"><label>Bagi Hasil (%)</label><input type="number" name="bagi_hasil" class="form-input" value="35" min="0" max="100" step="0.01" placeholder="35"></div>
        </div>
        <div class="form-group"><label>Alamat</label><input type="text" name="alamat" class="form-input"></div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="{{ route('teknisi.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
