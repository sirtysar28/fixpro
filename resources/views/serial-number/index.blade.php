@extends('layouts.app')
@section('title', 'Aktivasi & Lisensi')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-key" style="color:var(--primary);margin-right:6px"></i> Aktivasi & Lisensi</h2>
</div>

{{-- ===== SECTION: Pending Activation Requests ===== --}}
<div class="card mb-6">
    <div class="card-header">
        <h3><i class="fas fa-user-clock" style="color:var(--warning);margin-right:6px"></i> Request Aktivasi User</h3>
        <span class="badge badge-proses">{{ $pendingUsers->count() }} pending</span>
    </div>
    <p class="text-xs text-muted mb-4">Daftar user yang sudah registrasi dan menunggu aktivasi lisensi. Klik "Aktivasi" untuk memberikan akses permanen.</p>

    @if($pendingUsers->count() > 0)
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>No. HP</th>
                    <th>Nama Toko</th>
                    <th>Status</th>
                    <th>Tgl Daftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingUsers as $u)
                <tr style="{{ $u->days_left <= 7 ? 'background:#fffbeb' : '' }}">
                    <td><strong>{{ $u->name }}</strong></td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->phone ?? '-' }}</td>
                    <td>{{ $u->cabang?->nama ?? '-' }}</td>
                    <td>
                        @if($u->days_left <= 0)
                        <span class="badge badge-pending"><i class="fas fa-times-circle"></i> Expired</span>
                        @elseif($u->days_left <= 7)
                        <span class="badge badge-proses"><i class="fas fa-exclamation-triangle"></i> {{ $u->days_left }} hari lagi</span>
                        @else
                        <span class="badge badge-masuk"><i class="fas fa-clock"></i> {{ $u->days_left }} hari lagi</span>
                        @endif
                    </td>
                    <td>{{ $u->created_at?->format('d/m/Y') }}</td>
                    <td>
                        <form method="POST" action="{{ route('serial-number.generate') }}" style="display:inline" onsubmit="return confirm('Aktivasi akun {{ $u->name }} sekarang?')">
                            @csrf
                            <input type="hidden" name="email" value="{{ $u->email }}">
                            <button type="submit" class="btn btn-primary btn-xs"><i class="fas fa-check-circle"></i> Aktivasi</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div style="text-align:center;padding:30px;color:#94a3b8">
        <div style="font-size:2rem;margin-bottom:10px">✅</div>
        <div style="font-size:.88rem;font-weight:600">Tidak ada request aktivasi</div>
        <div style="font-size:.76rem">Semua user sudah aktif atau belum ada yang mendaftar.</div>
    </div>
    @endif
</div>

{{-- ===== SECTION: Serial Number History ===== --}}
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list" style="color:var(--primary);margin-right:6px"></i> Riwayat Serial Number</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Serial Code</th>
                    <th>Email Tujuan</th>
                    <th>Status</th>
                    <th>Dibuat Oleh</th>
                    <th>Dibuat Pada</th>
                    <th>Digunakan Pada</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($serials as $sn)
                <tr>
                    <td><code style="background:#f1f5f9;padding:3px 8px;border-radius:4px;font-weight:700;font-size:.82rem;letter-spacing:1px">{{ $sn->serial_code }}</code></td>
                    <td>{{ $sn->email }}</td>
                    <td>
                        @if($sn->is_used)
                        <span class="badge badge-selesai"><i class="fas fa-check"></i> Digunakan</span>
                        @else
                        <span class="badge badge-proses"><i class="fas fa-clock"></i> Belum Digunakan</span>
                        @endif
                    </td>
                    <td>{{ $sn->creator?->name ?? '-' }}</td>
                    <td>{{ $sn->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $sn->used_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>
                        @if(!$sn->is_used)
                        <form method="POST" action="{{ route('serial-number.destroy', $sn) }}" style="display:inline" onsubmit="return confirm('Hapus serial ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </form>
                        @else
                        <span style="color:#94a3b8;font-size:.72rem">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;display:flex;justify-content:center">
        {{ $serials->withQueryString()->links() }}
    </div>
</div>

{{-- Generate Manual --}}
<div class="card mt-4">
    <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:6px"></i> Generate Manual</h3>
    <form method="POST" action="{{ route('serial-number.generate') }}" style="display:flex;gap:8px;align-items:flex-end">
        @csrf
        <div class="form-group" style="margin:0;flex:1">
            <label>Email User</label>
            <input type="email" name="email" class="form-input" placeholder="user@email.com" required>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Generate & Aktivasi</button>
    </form>
</div>
@endsection
