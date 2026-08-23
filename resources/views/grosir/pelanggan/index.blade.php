@extends('layouts.app')
@section('title', 'Pelanggan Grosir')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">👥 Pelanggan Grosir</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">Data pelanggan grosir toko aktif — Reseller, Member, Grosir, Distributor</p>
    </div>
    <a href="{{ route('grosir.pelanggan.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Pelanggan Baru</a>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

<form method="GET" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <div style="flex:1;min-width:180px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Cari</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / kode / no HP..." class="form-input">
    </div>
    <div style="min-width:150px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Tipe</label>
        <select name="tipe" class="form-input">
            <option value="">Semua Tipe</option>
            @foreach(\App\Models\PelangganGrosir::TIPE as $t)
            <option value="{{ $t }}" {{ request('tipe') === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
</form>

<div class="card">
    <div class="card-header"><h3>{{ $pelanggans->total() }} Pelanggan</h3></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th><th>Nama</th><th>Tipe</th><th>Level Harga</th>
                    <th>No HP</th><th style="text-align:right;">Limit Piutang</th>
                    <th style="text-align:right;">Omzet</th><th style="text-align:right;">Piutang Aktif</th>
                    <th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($pelanggans as $p)
                <tr>
                    <td style="font-family:monospace;font-weight:700;">{{ $p->kode }}</td>
                    <td style="font-weight:600;">{{ $p->nama }}</td>
                    <td><span class="badge badge-normal">{{ $p->tipe }}</span></td>
                    <td><span class="badge badge-proses">{{ $p->labelLevelHarga() }}</span></td>
                    <td>{{ $p->no_hp ?? '-' }}</td>
                    <td style="text-align:right;">{{ $p->limit_piutang > 0 ? formatRp($p->limit_piutang) : '∞' }}</td>
                    <td style="text-align:right;font-weight:600;">{{ formatRp($p->total_omzet) }}</td>
                    <td style="text-align:right;font-weight:700;color:{{ $p->piutang_aktif > 0 ? 'var(--danger)' : 'inherit' }};">{{ formatRp($p->piutang_aktif) }}</td>
                    <td>{!! $p->aktif ? '<span class="badge badge-selesai">Aktif</span>' : '<span class="badge badge-pending">Nonaktif</span>' !!}</td>
                    <td style="white-space:nowrap;">
                        <a href="{{ route('grosir.pelanggan.edit', $p) }}" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('grosir.pelanggan.destroy', $p) }}" style="display:inline;" onsubmit="return confirm('Hapus pelanggan {{ $p->nama }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center;color:#94a3b8;padding:28px;">Belum ada pelanggan grosir. <a href="{{ route('grosir.pelanggan.create') }}">Tambah pelanggan →</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $pelanggans->links() }}
</div>
@endsection
