@extends('layouts.app')
@section('title', 'Profil Saya')

@section('content')
@php
    $user = auth()->user();
    $teknisi = $user->teknisiProfile;
    $isPermanent = $user->is_permanent;
    $daysLeft = $user->daysUntilExpiry();
    $expiresAt = $user->login_expires_at;
    $subSummary = $user->subscriptionSummary();
    $activeSub = $user->activeSubscription();
    $allSubs = \App\Models\Subscription::where('user_id', $user->id)->orderByDesc('id')->limit(5)->get();
@endphp

<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-user-circle" style="color:var(--primary);margin-right:6px"></i> Profil Saya</h2>
    @if(!$user->is_super_admin)
    <a href="{{ route('subscription.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-star"></i> Detail Langganan</a>
    @endif
</div>

{{-- Status Langganan / Akun --}}
@if($subSummary)
    @if(($subSummary['type']??'')==='super_admin')
    <div style="padding:14px 20px;background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;display:flex;align-items:center;gap:12px;margin-bottom:16px">
        <div style="width:40px;height:40px;background:#fef3c7;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#92400e"><i class="fas fa-crown"></i></div>
        <div>
            <div style="font-size:.88rem;font-weight:700;color:#92400e">Super Admin</div>
            <div style="font-size:.76rem;color:#b45309">Akses penuh tanpa batas waktu.</div>
        </div>
    </div>
    @elseif(($subSummary['type']??'')==='permanent')
    <div style="padding:14px 20px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;display:flex;align-items:center;gap:12px;margin-bottom:16px">
        <div style="width:40px;height:40px;background:#dcfce7;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#16a34a"><i class="fas fa-shield-alt"></i></div>
        <div>
            <div style="font-size:.88rem;font-weight:700;color:#166534">Akun Permanen ✅</div>
            <div style="font-size:.76rem;color:#16a34a">Akses tanpa batas waktu.</div>
        </div>
    </div>
    @elseif(($subSummary['type']??'')==='subscription' && $activeSub)
    {{-- PAKET BERLANGGANAN AKTIF --}}
    <div style="padding:18px 22px;background:linear-gradient(135deg,{{ ($activeSub->daysLeft()>7)?'var(--primary),var(--primary-dark)':'#d97706,#b45309' }});border-radius:14px;color:#fff;margin-bottom:16px">
        <div style="display:flex;align-items:center;gap:16px">
            <div style="width:48px;height:48px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem"><i class="fas fa-star"></i></div>
            <div style="flex:1">
                <div style="font-size:.72rem;opacity:.85;letter-spacing:.5px;text-transform:uppercase">Paket Berlangganan — {{ ucfirst($activeSub->package) }}</div>
                <div style="font-size:1.3rem;font-weight:800;margin-top:2px">{{ $activeSub->daysLeft() }} hari tersisa</div>
                <div style="font-size:.76rem;opacity:.9;margin-top:2px">Berakhir: {{ $activeSub->ends_at->translatedFormat('d F Y H:i') }} · {{ $activeSub->duration_months }} bulan</div>
            </div>
            <div style="text-align:right">
                <div style="font-size:.62rem;opacity:.7">Kode</div>
                <div style="font-size:.8rem;font-weight:700">{{ $activeSub->kode }}</div>
            </div>
        </div>
        {{-- Progress bar --}}
        @php
            $totalDays = $activeSub->duration_months * 30;
            $usedPct = $totalDays>0 ? min(100, round((1 - $activeSub->daysLeft()/$totalDays) * 100)) : 0;
        @endphp
        <div style="margin-top:12px;height:6px;background:rgba(255,255,255,.25);border-radius:4px;overflow:hidden">
            <div style="width:{{ $usedPct }}%;height:100%;background:rgba(255,255,255,.8)"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.62rem;opacity:.8;margin-top:4px">
            <span>Mulai: {{ $activeSub->started_at->translatedFormat('d/m/Y') }}</span>
            <span>{{ $usedPct }}% terpakai</span>
        </div>
    </div>
    @elseif($daysLeft !== null)
    <div style="padding:14px 20px;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;display:flex;align-items:center;gap:12px;margin-bottom:16px">
        <div style="width:40px;height:40px;background:#fef3c7;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#d97706"><i class="fas fa-clock"></i></div>
        <div style="flex:1">
            <div style="font-size:.88rem;font-weight:700;color:#92400e">Masa Berlaku: {{ $daysLeft }} hari lagi</div>
            <div style="font-size:.76rem;color:#b45309">Expired: {{ $expiresAt?->translatedFormat('d F Y H:i') }}</div>
        </div>
        @if($daysLeft <= 7)
        <span style="background:#fee2e2;color:#dc2626;padding:4px 12px;border-radius:20px;font-size:.72rem;font-weight:700">Segera Berakhir!</span>
        @endif
    </div>
    @endif
@endif

{{-- Serial Number Redeem --}}
@php $canRedeem = !$user->is_super_admin && !$user->is_permanent; @endphp
@if($canRedeem)
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-key" style="color:var(--primary);margin-right:6px"></i> Aktivasi Lisensi</h3>
    @if(session('serial_success'))
    <div style="padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#166534;font-size:.84rem;margin-bottom:10px"><i class="fas fa-check-circle"></i> {{ session('serial_success') }}</div>
    @endif
    @if(session('serial_error'))
    <div style="padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#991b1b;font-size:.84rem;margin-bottom:10px"><i class="fas fa-exclamation-circle"></i> {{ session('serial_error') }}</div>
    @endif
    <form method="POST" action="{{ route('profile.redeem-serial') }}">
        @csrf
        <div style="display:flex;gap:8px;align-items:flex-end">
            <div style="flex:1">
                <label style="display:block;margin-bottom:4px;font-size:.8rem;font-weight:600">Serial Number</label>
                <input type="text" name="serial_code" class="form-input" placeholder="FP-XXXXXXXX-XXXXXX" required style="font-family:monospace;letter-spacing:2px;text-transform:uppercase">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Redeem</button>
        </div>
    </form>
</div>
@endif

{{-- Riwayat Langganan --}}
@if($allSubs->isNotEmpty())
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-history" style="color:var(--primary);margin-right:6px"></i> Riwayat Paket Berlangganan</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Paket</th><th>Durasi</th><th>Mulai</th><th>Berakhir</th><th>Sisa</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($allSubs as $sb)
                <tr>
                    <td><strong style="color:var(--primary)">{{ $sb->kode }}</strong></td>
                    <td>{{ ucfirst($sb->package) }}</td>
                    <td>{{ $sb->duration_months }} bln</td>
                    <td style="font-size:.76rem">{{ $sb->started_at?->format('d/m/Y') }}</td>
                    <td style="font-size:.76rem">{{ $sb->ends_at?->format('d/m/Y') }}</td>
                    <td>{{ $sb->isActive() ? $sb->daysLeft().' hari' : '—' }}</td>
                    <td>
                        @if($sb->isActive())<span class="badge badge-selesai">Aktif</span>
                        @elseif($sb->status==='cancelled')<span class="badge badge-urgent">Dibatalkan</span>
                        @else<span class="badge badge-pending">Kedaluwarsa</span>@endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ==================== PROFIL TEKNISI ==================== --}}
@if($user->isTeknisi() && $teknisi)
<div class="card mb-4" style="border:2px solid var(--primary)">
    <div style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));margin:-20px -20px 16px;padding:20px;border-radius:12px 12px 0 0;color:#fff;display:flex;align-items:center;gap:16px">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:700">{{ strtoupper(substr($teknisi->nama, 0, 2)) }}</div>
        <div>
            <div style="font-size:1.1rem;font-weight:700">{{ $teknisi->nama }}</div>
            <div style="font-size:.78rem;opacity:.85"><i class="fas fa-wrench"></i> {{ $teknisi->spesialisasi }} — {{ $teknisi->cabang?->nama ?? '-' }}</div>
            @if($teknisi->no_wa)<div style="font-size:.72rem;opacity:.75"><i class="fab fa-whatsapp"></i> {{ $teknisi->no_wa }}</div>@endif
        </div>
        <div style="margin-left:auto;text-align:center">
            <div style="font-size:.68rem;opacity:.8">Status</div>
            <div style="font-size:.88rem;font-weight:700">
                @if($teknisi->aktif)
                <i class="fas fa-circle" style="font-size:.5rem;color:#4ade80"></i> Aktif
                @else
                <i class="fas fa-circle" style="font-size:.5rem;color:#f87171"></i> Nonaktif
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    @php
        $servisAktif = \App\Models\Servis::where('teknisi_id', $teknisi->id)->whereIn('status', ['Masuk','Proses','Pending'])->count();
        $servisSelesai = \App\Models\Servis::where('teknisi_id', $teknisi->id)->where('status', 'Selesai')->count();
        $omsetBulanIni = \App\Models\Servis::where('teknisi_id', $teknisi->id)->where('status', 'Selesai')->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)->sum('biaya');
        $bagiHasil = $teknisi->bagi_hasil ?? 35;
    @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:12px">
        <div style="text-align:center;padding:12px;background:#f8fafc;border-radius:10px">
            <div style="font-size:.68rem;color:#64748b">Servis Aktif</div>
            <div style="font-size:1.3rem;font-weight:800;color:#f59e0b">{{ $servisAktif }}</div>
        </div>
        <div style="text-align:center;padding:12px;background:#f8fafc;border-radius:10px">
            <div style="font-size:.68rem;color:#64748b">Selesai</div>
            <div style="font-size:1.3rem;font-weight:800;color:#16a34a">{{ $servisSelesai }}</div>
        </div>
        <div style="text-align:center;padding:12px;background:#f8fafc;border-radius:10px">
            <div style="font-size:.68rem;color:#64748b">Omset Bulan Ini</div>
            <div style="font-size:1.1rem;font-weight:800;color:var(--primary)">{{ formatRp($omsetBulanIni) }}</div>
        </div>
        <div style="text-align:center;padding:12px;background:#f8fafc;border-radius:10px">
            <div style="font-size:.68rem;color:#64748b">Bagi Hasil</div>
            <div style="font-size:1.3rem;font-weight:800;color:#0d9488">{{ $bagiHasil }}%</div>
        </div>
    </div>

    {{-- Servis Terbaru --}}
    @php $servisTerbaru = \App\Models\Servis::where('teknisi_id', $teknisi->id)->with('pelanggan')->orderBy('created_at','desc')->limit(5)->get(); @endphp
    @if($servisTerbaru->count() > 0)
    <h4 style="font-size:.84rem;font-weight:700;margin-bottom:8px"><i class="fas fa-list" style="color:var(--primary);margin-right:4px"></i> Servis Terbaru Saya</h4>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Tanggal</th><th>Perangkat</th><th>Status</th><th>Biaya</th></tr></thead>
            <tbody>
                @foreach($servisTerbaru as $s)
                <tr>
                    <td><strong style="color:var(--primary)">{{ $s->kode }}</strong></td>
                    <td style="font-size:.78rem">{{ $s->tanggal?->format('d/m/Y') }}</td>
                    <td>{{ $s->perangkat }}</td>
                    <td>
                        @if($s->status==='Selesai')<span class="badge badge-selesai">Selesai</span>
                        @elseif($s->status==='Proses')<span class="badge badge-proses">Proses</span>
                        @elseif($s->status==='Masuk')<span class="badge badge-masuk">Masuk</span>
                        @elseif($s->status==='Pending')<span class="badge badge-pending">Pending</span>
                        @else<span class="badge" style="background:#f1f5f9;color:#64748b">{{ $s->status }}</span>@endif
                    </td>
                    <td style="font-weight:700">{{ formatRp($s->biaya) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top:10px;text-align:center">
        <a href="{{ route('teknisi-dashboard.index') }}" class="btn btn-primary btn-sm"><i class="fas fa-tachometer-alt"></i> Lihat Dashboard Lengkap</a>
    </div>
    @else
    <div style="text-align:center;padding:16px;color:#94a3b8;font-size:.84rem">Belum ada data servis yang ditugaskan.</div>
    @endif
</div>
@elseif($user->isTeknisi() && !$teknisi)
<div class="card mb-4" style="border:2px solid #fecaca;background:#fef2f2">
    <div style="text-align:center;padding:24px">
        <div style="font-size:2rem;margin-bottom:10px">⚠️</div>
        <div style="font-size:1rem;font-weight:700;color:#991b1b;margin-bottom:6px">Akun Belum Terhubung ke Data Teknisi</div>
        <div style="font-size:.82rem;color:#7f1d1d">Akun Anda sudah diatur sebagai Teknisi, tapi admin belum menghubungkan ke data teknisi Anda. Silakan hubungi admin untuk menghubungkan akun ini ke profil teknisi melalui menu <strong>Teknisi → Edit → Akun Login Teknisi</strong>.</div>
    </div>
</div>
@endif
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-user-edit" style="color:var(--primary);margin-right:6px"></i> Edit Profil</h3>
    <form method="POST" action="{{ route('profile.update') }}">
        @csrf @method('PATCH')
        <div class="form-row">
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>No HP / Phone</label>
                <input type="text" name="phone" class="form-input" value="{{ old('phone', $user->phone) }}">
            </div>
            <div class="form-group">
                <label>Role</label>
                <input type="text" class="form-input" value="{{ $user->role?->name ?? '-' }}" disabled style="background:#f8fafc">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Cabang</label>
                <input type="text" class="form-input" value="{{ $user->cabang?->nama ?? '-' }}" disabled style="background:#f8fafc">
            </div>
            <div class="form-group">
                <label>Terdaftar Sejak</label>
                <input type="text" class="form-input" value="{{ $user->created_at?->format('d F Y') }}" disabled style="background:#f8fafc">
            </div>
        </div>
        @if(session('status') === 'profile-updated')
        <div class="alert alert-success" style="margin-top:10px"><i class="fas fa-check-circle"></i> Profil berhasil diupdate!</div>
        @endif
        <button type="submit" class="btn btn-primary" style="margin-top:10px"><i class="fas fa-save"></i> Simpan Perubahan</button>
    </form>
</div>

{{-- ==================== GANTI PASSWORD ==================== --}}
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-lock" style="color:var(--accent);margin-right:6px"></i> Ganti Password</h3>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf @method('PUT')
        <div class="form-group">
            <label>Password Saat Ini</label>
            <input type="password" name="current_password" class="form-input" required autocomplete="current-password">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="password" class="form-input" required minlength="6" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" class="form-input" required minlength="6" autocomplete="new-password">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:6px"><i class="fas fa-key"></i> Ganti Password</button>
    </form>
</div>
@endsection
