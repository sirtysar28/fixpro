@extends('layouts.app')
@section('title', $viewRole === 'user' ? 'Riwayat & Lacak Servis Saya' : 'Arsip & Lacak Servis')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem">
        <i class="fas fa-archive" style="color:var(--primary);margin-right:6px"></i>
        {{ $viewRole === 'user' ? 'Riwayat & Lacak Servis Saya' : 'Arsip & Lacak Servis' }}
    </h2>
    @if($viewRole === 'superadmin')
    <div style="font-size:.78rem;color:var(--warning);font-weight:600"><i class="fas fa-crown"></i> Super Admin — Semua Cabang</div>
    @elseif($viewRole === 'admin')
    <div style="font-size:.78rem;color:var(--info);font-weight:600"><i class="fas fa-store"></i> Cabang: {{ auth()->user()->cabang?->nama ?? 'Pusat' }}</div>
    @endif
</div>

<div class="stats-grid mb-6">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-check-circle"></i></div>
        <div class="stat-label">{{ $viewRole === 'user' ? 'Servis Saya Selesai' : 'Selesai Servis' }}</div>
        <div class="stat-value" style="color:var(--success)">{{ $totalSelesai }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:var(--info)"><i class="fas fa-hand-holding"></i></div>
        <div class="stat-label">Sudah Diambil</div>
        <div class="stat-value" style="color:var(--info)">{{ $totalDiambil }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-shield-alt"></i></div>
        <div class="stat-label">Garansi Aktif</div>
        <div class="stat-value" style="color:var(--warning)">{{ $totalGaransi }}</div>
    </div>
</div>

{{-- Lacak Servis --}}
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-search" style="color:var(--primary);margin-right:6px"></i>Lacak Servis</h3>
    <form onsubmit="lacakServis(event)" style="display:flex;gap:8px">
        <input type="text" id="lacakKode" class="form-input" placeholder="Masukkan kode servis (contoh: SVC-260520-001)" value="{{ request('kode') }}" style="flex:1">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Lacak</button>
    </form>
</div>

{{-- Filter --}}
<form method="GET" class="card mb-4">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
        <div style="flex:1;min-width:180px">
            <label class="text-xs font-bold text-muted">Cari</label>
            <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="Kode, nama, IMEI...">
        </div>
        <div style="width:130px">
            <label class="text-xs font-bold text-muted">Status</label>
            <select name="status" class="form-input">
                <option value="">Semua</option>
                <option value="Masuk" {{ request('status') == 'Masuk' ? 'selected' : '' }}>Masuk</option>
                <option value="Proses" {{ request('status') == 'Proses' ? 'selected' : '' }}>Proses</option>
                <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="Selesai" {{ request('status') == 'Selesai' ? 'selected' : '' }}>Selesai</option>
            </select>
        </div>
        @if($viewRole !== 'user')
        <div style="width:120px">
            <label class="text-xs font-bold text-muted">Tipe</label>
            <select name="tipe" class="form-input">
                <option value="">Semua</option>
                <option value="Apple" {{ request('tipe') == 'Apple' ? 'selected' : '' }}>Apple</option>
                <option value="Android" {{ request('tipe') == 'Android' ? 'selected' : '' }}>Android</option>
            </select>
        </div>
        @endif
        <div style="width:140px">
            <label class="text-xs font-bold text-muted">Dari</label>
            <input type="date" name="dari" class="form-input" value="{{ request('dari') }}">
        </div>
        <div style="width:140px">
            <label class="text-xs font-bold text-muted">Sampai</label>
            <input type="date" name="sampai" class="form-input" value="{{ request('sampai') }}">
        </div>
        @if($viewRole === 'superadmin')
        <div style="width:160px">
            <label class="text-xs font-bold text-muted">Cabang</label>
            <select name="cabang_id" class="form-input">
                <option value="">Semua Cabang</option>
                @foreach($cabangs as $cab)
                <option value="{{ $cab->id }}" {{ request('cabang_id') == $cab->id ? 'selected' : '' }}>{{ $cab->nama }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('arsip-servis.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
    </div>
</form>

<div class="card">
    @if($servis->count() === 0)
    <div style="text-align:center;padding:40px 20px">
        <div style="font-size:2.5rem;margin-bottom:12px">📋</div>
        <div style="font-size:1rem;font-weight:700;color:#374151">Belum ada data servis</div>
        <div style="font-size:.82rem;color:#94a3b8;margin-top:4px">
            @if($viewRole === 'user')
            Anda belum memiliki riwayat servis. <a href="{{ route('my-service.create') }}" style="color:var(--primary);font-weight:600">Daftarkan servis HP Anda</a> sekarang!
            @else
            Belum ada data servis yang cocok dengan filter.
            @endif
        </div>
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Tanggal</th>
                    @if($viewRole !== 'user')<th>Pelanggan</th>@endif
                    <th>Perangkat</th>
                    @if($viewRole !== 'user')
                    <th>Tipe</th>
                    <th>Teknisi</th>
                    @endif
                    @if($viewRole === 'superadmin')<th>Cabang</th>@endif
                    <th>Status</th>
                    @if($viewRole !== 'user')
                    <th>Diambil</th>
                    <th>Garansi</th>
                    @endif
                    <th>Biaya</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($servis as $s)
                <tr>
                    <td><strong style="color:var(--primary)">{{ $s->kode }}</strong></td>
                    <td>{{ $s->tanggal?->format('d/m/Y') }}</td>
                    @if($viewRole !== 'user')
                    <td>
                        <div>{{ $s->pelanggan?->nama ?? '-' }}</div>
                        <div class="text-xs text-muted">{{ $s->pelanggan?->no_hp ?? '' }}</div>
                    </td>
                    @endif
                    <td>{{ $s->perangkat }}</td>
                    @if($viewRole !== 'user')
                    <td><span class="badge {{ $s->tipe === 'Apple' ? 'badge-masuk' : 'badge-selesai' }}">{{ $s->tipe }}</span></td>
                    <td>{{ $s->teknisi?->nama ?? '-' }}</td>
                    @endif
                    @if($viewRole === 'superadmin')
                    <td><span class="badge badge-masuk">{{ $s->cabang?->nama ?? '-' }}</span></td>
                    @endif
                    <td><span class="badge badge-{{ strtolower($s->status) }}">{{ $s->status }}</span></td>
                    @if($viewRole !== 'user')
                    <td>
                        @if($s->diambil)
                            <span class="badge badge-selesai">✓ Diambil</span>
                        @elseif($s->status === 'Selesai')
                            <span class="badge badge-pending">Belum</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($s->tanggal_garansi)
                            @if($s->tanggal_garansi >= now())
                                <span class="badge badge-selesai">{{ $s->tanggal_garansi->format('d/m/Y') }}</span>
                            @else
                                <span class="badge badge-pending" style="text-decoration:line-through">{{ $s->tanggal_garansi->format('d/m/Y') }}</span>
                            @endif
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    @endif
                    <td>{{ formatRp($s->biaya) }}</td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('arsip-servis.lacak', $s->kode) }}" class="btn btn-secondary btn-xs" title="Lacak"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('arsip-servis.print', $s->id) }}" class="btn btn-primary btn-xs" title="Print" target="_blank"><i class="fas fa-print"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;display:flex;justify-content:center">
        {{ $servis->withQueryString()->links() }}
    </div>
    @endif
</div>

<script>
function lacakServis(e) {
    e.preventDefault();
    const kode = document.getElementById('lacakKode').value.trim();
    if (!kode) {
        alert('Masukkan kode servis terlebih dahulu!');
        return;
    }
    window.location.href = '/arsip-servis/lacak/' + encodeURIComponent(kode);
}
</script>
@endsection
