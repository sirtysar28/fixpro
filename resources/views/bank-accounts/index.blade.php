@use('Illuminate\Support\Facades\Storage')
@extends('layouts.app')
@section('title', 'Kelola Rekening Bank — Super Admin')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-university" style="color:var(--primary);margin-right:6px"></i> Kelola Rekening Bank</h2>
</div>

<div class="grid-2">
    <div>
        {{-- Form Tambah --}}
        <div class="card mb-4" style="border:2px solid var(--primary)">
            <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:6px"></i> Tambah Rekening Bank</h3>
            <form method="POST" action="{{ route('bank-accounts.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>Nama Bank *</label>
                    <input type="text" name="nama_bank" class="form-input" placeholder="BCA, BRI, Mandiri, BSI..." required>
                </div>
                <div class="form-group">
                    <label>Atas Nama *</label>
                    <input type="text" name="atas_nama" class="form-input" placeholder="Nama pemilik rekening" required>
                </div>
                <div class="form-group">
                    <label>Nomor Rekening *</label>
                    <input type="text" name="no_rekening" class="form-input" placeholder="1234567890" required>
                </div>
                <div class="form-group">
                    <label>Logo Bank (Opsional)</label>
                    <input type="file" name="logo" class="form-input" accept="image/*">
                    <div class="text-xs text-muted" style="margin-top:4px">Logo akan ditampilkan di halaman request aktivasi</div>
                </div>
                <div class="form-group">
                    <label>Catatan (Opsional)</label>
                    <textarea name="catatan" class="form-input" rows="2" placeholder="Contoh: Mohon transfer sesuai nominal yang tertera"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Rekening</button>
            </form>
        </div>
    </div>
    <div>
        {{-- Daftar Rekening --}}
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list" style="color:var(--primary);margin-right:6px"></i> Daftar Rekening Bank</h3>
                <span class="badge" style="background:var(--primary-bg);color:var(--primary)">{{ $banks->total() }} rekening</span>
            </div>
            @if($banks->count() > 0)
            <div style="display:flex;flex-direction:column;gap:10px">
                @foreach($banks as $bank)
                <div style="padding:14px;border:1.5px solid {{ $bank->aktif ? '#e2e8f0' : '#fecaca' }};border-radius:10px;background:{{ $bank->aktif ? '#fff' : '#fff5f5' }};">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div style="display:flex;gap:10px;align-items:center">
                            @if($bank->logo)
                            <img src="{{ Storage::url($bank->logo) }}" style="width:36px;height:36px;border-radius:8px;object-fit:contain">
                            @else
                            <div style="width:36px;height:36px;border-radius:8px;background:var(--primary-bg);display:flex;align-items:center;justify-content:center;color:var(--primary);font-weight:700;font-size:.65rem">{{ strtoupper(substr($bank->nama_bank, 0, 3)) }}</div>
                            @endif
                            <div>
                                <div style="font-weight:700;font-size:.88rem">{{ $bank->nama_bank }} <span class="badge {{ $bank->aktif ? 'badge-selesai' : 'badge-pending' }}" style="font-size:.6rem">{{ $bank->aktif ? 'Aktif' : 'Nonaktif' }}</span></div>
                                <div style="font-size:.72rem;color:#64748b">a/n {{ $bank->atas_nama }}</div>
                                <div style="font-size:1rem;font-weight:800;font-family:monospace;letter-spacing:1px;color:var(--primary);margin-top:2px">{{ $bank->no_rekening }}</div>
                                @if($bank->catatan)
                                <div style="font-size:.68rem;color:#94a3b8;margin-top:2px"><i class="fas fa-info-circle"></i> {{ $bank->catatan }}</div>
                                @endif
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:4px">
                            <form method="POST" action="{{ route('bank-accounts.update', $bank) }}" style="display:inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="nama_bank" value="{{ $bank->nama_bank }}">
                                <input type="hidden" name="atas_nama" value="{{ $bank->atas_nama }}">
                                <input type="hidden" name="no_rekening" value="{{ $bank->no_rekening }}">
                                <input type="hidden" name="catatan" value="{{ $bank->catatan }}">
                                <input type="hidden" name="aktif" value="{{ $bank->aktif ? '0' : '1' }}">
                                <button type="submit" class="btn btn-xs {{ $bank->aktif ? 'btn-secondary' : 'btn-success' }}" onclick="return confirm('Ubah status rekening ini?')">
                                    {{ $bank->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('bank-accounts.destroy', $bank) }}" style="display:inline" onsubmit="return confirm('Hapus rekening ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div style="margin-top:16px;display:flex;justify-content:center">
                {{ $banks->withQueryString()->links() }}
            </div>
            @else
            <div style="text-align:center;padding:30px;color:#94a3b8">
                <div style="font-size:2rem;margin-bottom:10px">🏦</div>
                <p style="font-size:.88rem">Belum ada rekening bank</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
