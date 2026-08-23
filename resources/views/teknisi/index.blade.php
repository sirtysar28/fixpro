@extends('layouts.app')
@section('title', 'Data Teknisi')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0">Data Teknisi</h2>
    <a href="{{ route('teknisi.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Teknisi</a>
</div>

@if($showAll && $teknisiByCabang && $teknisiByCabang->count() > 1)
{{-- Mode Super Admin: tampil per kelompok cabang --}}
@foreach($teknisiByCabang as $cabangNama => $list)
<div class="card mb-4">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:2px solid #e2e8f0">
        <div style="display:flex;align-items:center;gap:8px">
            <i class="fas fa-store" style="color:var(--primary)"></i>
            <h3 style="margin:0;font-size:.95rem">{{ $cabangNama }}</h3>
        </div>
        <span class="badge" style="background:#eff6ff;color:#3b82f6">{{ $list->count() }} teknisi</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nama</th><th>No. WA</th><th>Spesialisasi</th><th>Bagi Hasil</th><th>Servis Aktif</th><th>Selesai</th><th>Omset</th><th>Laba Bersih</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($list as $t)
                <tr>
                    <td><strong>{{ $t->nama }}</strong></td>
                    <td>{{ $t->no_wa ?? '-' }}</td>
                    <td>{{ $t->spesialisasi }}</td>
                    <td><span class="badge" style="background:#fef3c7;color:#92400e">{{ $t->bagi_hasil ?? 35 }}%</span></td>
                    <td><span class="badge badge-proses">{{ $t->aktif_count ?? 0 }}</span></td>
                    <td><span class="badge badge-selesai">{{ $t->selesai_count ?? 0 }}</span></td>
                    <td><strong style="color:var(--primary)">{{ formatRp($t->omset ?? 0) }}</strong></td>
                    @php $labaBersih = ($t->omset ?? 0) * (($t->bagi_hasil ?? 35) / 100); @endphp
                    <td><strong style="color:var(--success)">{{ formatRp($labaBersih) }}</strong></td>
                    <td>{!! $t->aktif ? '<span class="badge badge-selesai"><i class="fas fa-circle" style="font-size:.5rem;margin-right:3px"></i> Aktif</span>' : '<span class="badge badge-pending"><i class="fas fa-circle" style="font-size:.5rem;margin-right:3px"></i> Nonaktif</span>' !!}</td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('teknisi.edit', $t) }}" class="btn btn-primary btn-xs"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('teknisi.destroy', $t) }}" style="display:inline" onsubmit="return confirm('Hapus teknisi {{ $t->nama }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach
@else
{{-- Mode Admin Cabang atau Super Admin pilih cabang spesifik: 1 tabel saja --}}
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nama</th><th>Cabang</th><th>No. WA</th><th>Spesialisasi</th><th>Bagi Hasil</th><th>Servis Aktif</th><th>Selesai</th><th>Omset</th><th>Laba Bersih</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($teknisis as $t)
                <tr>
                    <td><strong>{{ $t->nama }}</strong></td>
                    <td><span class="badge badge-masuk">{{ $t->cabang?->nama ?? '-' }}</span></td>
                    <td>{{ $t->no_wa ?? '-' }}</td>
                    <td>{{ $t->spesialisasi }}</td>
                    <td><span class="badge" style="background:#fef3c7;color:#92400e">{{ $t->bagi_hasil ?? 35 }}%</span></td>
                    <td><span class="badge badge-proses">{{ $t->aktif_count ?? 0 }}</span></td>
                    <td><span class="badge badge-selesai">{{ $t->selesai_count ?? 0 }}</span></td>
                    <td><strong style="color:var(--primary)">{{ formatRp($t->omset ?? 0) }}</strong></td>
                    @php $labaBersih = ($t->omset ?? 0) * (($t->bagi_hasil ?? 35) / 100); @endphp
                    <td><strong style="color:var(--success)">{{ formatRp($labaBersih) }}</strong></td>
                    <td>{!! $t->aktif ? '<span class="badge badge-selesai"><i class="fas fa-circle" style="font-size:.5rem;margin-right:3px"></i> Aktif</span>' : '<span class="badge badge-pending"><i class="fas fa-circle" style="font-size:.5rem;margin-right:3px"></i> Nonaktif</span>' !!}</td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('teknisi.edit', $t) }}" class="btn btn-primary btn-xs"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('teknisi.destroy', $t) }}" style="display:inline" onsubmit="return confirm('Hapus teknisi {{ $t->nama }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" style="text-align:center;padding:30px;color:#94a3b8">
                    <i class="fas fa-user-slash" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>
                    Belum ada teknisi di cabang ini.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
