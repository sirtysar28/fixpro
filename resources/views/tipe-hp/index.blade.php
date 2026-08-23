@extends('layouts.app')
@section('title', 'Master Data Tipe HP')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-mobile-alt" style="color:var(--primary);margin-right:6px"></i> Master Data Tipe HP</h2>
    <button class="btn btn-primary" onclick="document.getElementById('formAdd').style.display = document.getElementById('formAdd').style.display === 'none' ? 'block' : 'none'"><i class="fas fa-plus"></i> Tambah Tipe HP</button>
</div>

{{-- Form Tambah --}}
<div class="card" id="formAdd" style="display:none;margin-bottom:20px;border:2px solid var(--primary)">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:6px"></i> Tambah Tipe HP Baru</h3>
    <form method="POST" action="{{ route('tipe-hp.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Merk *</label>
                <input type="text" name="merk" class="form-input" placeholder="Apple, Samsung, Xiaomi..." required list="merkList">
                <datalist id="merkList">
                    <option value="Apple">
                    <option value="Samsung">
                    <option value="Xiaomi">
                    <option value="Oppo">
                    <option value="Vivo">
                    <option value="Realme">
                    <option value="Infinix">
                    <option value="Tecno">
                    <option value="Nokia">
                    <option value="ASUS">
                    <option value="OnePlus">
                    <option value="Huawei">
                    <option value="Sony">
                    <option value="LG">
                    <option value="Motorola">
                    <option value="Lenovo">
                    <option value="Advan">
                    <option value="Evercoss">
                    <option value="Polytron">
                    <option value="Lainnya">
                </datalist>
            </div>
            <div class="form-group">
                <label>Tipe / Model *</label>
                <input type="text" name="tipe" class="form-input" placeholder="iPhone 13 Pro Max, Samsung A54..." required>
            </div>
        </div>
        <div class="form-group">
            <label>Kategori</label>
            <select name="kategori" class="form-input">
                <option value="Smartphone">Smartphone</option>
                <option value="Tablet">Tablet</option>
                <option value="Feature Phone">Feature Phone</option>
                <option value="Lainnya">Lainnya</option>
            </select>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('formAdd').style.display='none'"><i class="fas fa-times"></i> Batal</button>
        </div>
    </form>
</div>

{{-- Filter --}}
<div class="card mb-4">
    <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="margin:0;flex:1;min-width:150px">
            <label>Cari</label>
            <input type="text" name="search" class="form-input" placeholder="Cari merk atau tipe..." value="{{ request('search') }}">
        </div>
        <div class="form-group" style="margin:0;flex:1;min-width:150px">
            <label>Merk</label>
            <select name="merk" class="form-input">
                <option value="">Semua Merk</option>
                @foreach($merks as $m)
                <option value="{{ $m }}" {{ request('merk') === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filter</button>
        <a href="{{ route('tipe-hp.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i> Reset</a>
    </form>
</div>

{{-- Tabel --}}
<div class="card">
    <div class="card-header">
        <h3>Daftar Tipe HP ({{ $tipeHps->total() }})</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Merk</th>
                    <th>Tipe / Model</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tipeHps as $i => $th)
                <tr>
                    <td>{{ $tipeHps->firstItem() + $i }}</td>
                    <td><strong>{{ $th->merk }}</strong></td>
                    <td>{{ $th->tipe }}</td>
                    <td>{{ $th->kategori ?? '-' }}</td>
                    <td>
                        <form method="POST" action="{{ route('tipe-hp.update', $th) }}" style="display:inline">
                            @csrf @method('PUT')
                            <input type="hidden" name="merk" value="{{ $th->merk }}">
                            <input type="hidden" name="tipe" value="{{ $th->tipe }}">
                            <input type="hidden" name="kategori" value="{{ $th->kategori ?? 'Smartphone' }}">
                            <input type="hidden" name="aktif" value="{{ $th->aktif ? '0' : '1' }}">
                            <button type="submit" class="btn btn-xs {{ $th->aktif ? 'btn-success' : 'btn-secondary' }}" onclick="return confirm('Ubah status tipe HP ini?')">
                                {{ $th->aktif ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('tipe-hp.destroy', $th) }}" style="display:inline" onsubmit="return confirm('Hapus tipe HP ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
                @if($tipeHps->isEmpty())
                <tr>
                    <td colspan="6" style="text-align:center;padding:30px;color:#94a3b8">
                        <i class="fas fa-mobile-alt" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>
                        Belum ada data tipe HP
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;display:flex;justify-content:center">
        {{ $tipeHps->withQueryString()->links() }}
    </div>
</div>
@endsection
