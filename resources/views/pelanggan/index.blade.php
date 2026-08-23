@extends('layouts.app')
@section('title', 'Data Pelanggan')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0">Data Pelanggan</h2>
    <a href="{{ route('pelanggan.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</a>
</div>

<form method="GET" class="card mb-4">
    <div style="display:flex;gap:8px;align-items:flex-end">
        <div style="flex:1"><label class="text-xs font-bold text-muted">Cari</label>
        <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="Cari nama atau no HP..."></div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
    </div>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nama</th><th>No. HP</th><th>Alamat</th><th>Akun User</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($pelanggans as $p)
                <tr>
                    <td>{{ $p->nama }}</td>
                    <td>{{ $p->no_hp }}</td>
                    <td>{{ $p->alamat ?? '-' }}</td>
                    <td>
                        @if($p->user)
                            <span class="badge" style="background:#10b981;color:white;font-size:11px;padding:2px 8px;border-radius:12px">
                                ✓ {{ $p->user->email }}
                            </span>
                        @else
                            <span style="color:#999;font-size:12px">Tidak terhubung</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('pelanggan.edit', $p) }}" class="btn btn-primary btn-xs"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('pelanggan.destroy', $p) }}" style="display:inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px">Belum ada data pelanggan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $pelanggans->links() }}
</div>
@endsection
