@extends('layouts.app')
@section('title', 'Dashboard Saya')

@section('content')
{{-- Alert Expiry --}}
@php
    $daysLeft = auth()->user()->daysUntilExpiry();
    $isExpired = auth()->user()->isLoginExpired();
    $isPermanent = auth()->user()->is_permanent;
@endphp
@if(!$isPermanent && $isExpired)
<div class="alert alert-error" style="margin-bottom:20px">
    <div style="flex:1">
        <strong><i class="fas fa-exclamation-triangle"></i> Masa Berlaku Akun Habis!</strong><br>
        <span style="font-size:.8rem">Akun Anda sudah expired. Hubungi admin untuk mendapatkan Serial Number dan masukkan di halaman <a href="{{ route('profile.edit') }}" style="color:#dc2626;font-weight:700">Profil</a> untuk memperpanjang akun selamanya.</span>
    </div>
</div>
@elseif(!$isPermanent && $daysLeft !== null && $daysLeft <= 7)
<div class="alert alert-warning" style="margin-bottom:20px">
    <div style="flex:1">
        <strong><i class="fas fa-clock"></i> Masa Berlaku Akun Segera Berakhir!</strong><br>
        <span style="font-size:.8rem">Sisa <strong>{{ $daysLeft }} hari</strong> lagi. Hubungi admin untuk Serial Number perpanjangan, lalu masukkan di halaman <a href="{{ route('profile.edit') }}" style="color:#92400e;font-weight:700">Profil</a>.</span>
    </div>
</div>
@elseif(!$isPermanent && $daysLeft !== null)
<div style="margin-bottom:20px;padding:12px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;font-size:.8rem;color:#92400e;display:flex;align-items:center;gap:8px">
    <i class="fas fa-info-circle"></i>
    <span>Masa berlaku akun Anda: <strong>{{ $daysLeft }} hari</strong> lagi ({{ auth()->user()->login_expires_at->format('d/m/Y') }}). Perpanjang dengan Serial Number di <a href="{{ route('profile.edit') }}" style="color:#92400e;font-weight:700">Profil</a>.</span>
</div>
@elseif($isPermanent)
<div style="margin-bottom:20px;padding:10px 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;font-size:.78rem;color:#166534;display:flex;align-items:center;gap:8px">
    <i class="fas fa-check-circle"></i>
    <span><strong>Akun Permanen</strong> — Akses tanpa batas waktu.</span>
</div>
@endif

<div style="margin-bottom:24px">
    <h2 style="font-size:1.3rem;margin:0">Halo, {{ $user->name }}! 👋</h2>
    <p style="color:#94a3b8;font-size:.85rem;margin-top:4px">Pantau status servis HP Anda di sini.</p>
</div>

<div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start">
    <!-- Kolom kiri: konten -->
    <div>
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-tools"></i></div>
                <div class="stat-label">Total Servis Saya</div>
                <div class="stat-value" style="color:var(--primary)">{{ $totalServis }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-clock"></i></div>
                <div class="stat-label">Sedang Proses</div>
                <div class="stat-value" style="color:var(--warning)">{{ $servisProses }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-check-circle"></i></div>
                <div class="stat-label">Selesai</div>
                <div class="stat-value" style="color:var(--success)">{{ $servisSelesai }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#dbeafe;color:var(--info)"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-label">Total Biaya</div>
                <div class="stat-value" style="color:var(--info)">{{ formatRp($totalBiaya) }}</div>
            </div>
        </div>

        <!-- Status ringkasan -->
        <div class="card" style="margin-bottom:20px">
            <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-chart-pie" style="color:var(--primary);margin-right:6px"></i> Ringkasan Status Servis</h3>
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <div style="flex:1;min-width:100px;padding:12px;background:#dbeafe;border-radius:10px;text-align:center">
                    <div style="font-size:1.2rem;font-weight:800;color:#1e40af">{{ $servisMasuk }}</div>
                    <div style="font-size:.72rem;color:#3b82f6;font-weight:600">Masuk</div>
                </div>
                <div style="flex:1;min-width:100px;padding:12px;background:#fef3c7;border-radius:10px;text-align:center">
                    <div style="font-size:1.2rem;font-weight:800;color:#92400e">{{ $servisProses }}</div>
                    <div style="font-size:.72rem;color:#f59e0b;font-weight:600">Proses</div>
                </div>
                <div style="flex:1;min-width:100px;padding:12px;background:#fee2e2;border-radius:10px;text-align:center">
                    <div style="font-size:1.2rem;font-weight:800;color:#991b1b">{{ $servisPending }}</div>
                    <div style="font-size:.72rem;color:#dc2626;font-weight:600">Pending</div>
                </div>
                <div style="flex:1;min-width:100px;padding:12px;background:#dcfce7;border-radius:10px;text-align:center">
                    <div style="font-size:1.2rem;font-weight:800;color:#166534">{{ $servisSelesai }}</div>
                    <div style="font-size:.72rem;color:#16a34a;font-weight:600">Selesai</div>
                </div>
            </div>
        </div>

        <!-- Daftar servis terbaru -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list" style="color:var(--primary);margin-right:6px"></i> Servis Saya</h3>
                <a href="{{ route('my-service.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Daftar Servis Baru</a>
            </div>
            @if($myServis->count() > 0)
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Kode</th><th>Perangkat</th><th>Keluhan</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        @foreach($myServis as $s)
                        <tr>
                            <td><strong style="color:var(--primary)">{{ $s->kode }}</strong></td>
                            <td>{{ $s->perangkat }}</td>
                            <td>{{ Str::limit($s->keluhan, 30) }}</td>
                            <td><span class="badge badge-{{ strtolower($s->status) }}">{{ $s->status }}</span></td>
                            <td>{{ $s->tanggal?->format('d/m/Y') }}</td>
                            <td><a href="{{ route('my-service.show', $s) }}" class="btn btn-secondary btn-xs"><i class="fas fa-eye"></i></a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align:center;padding:40px 20px;color:#94a3b8">
                <i class="fas fa-box-open" style="font-size:2rem;margin-bottom:8px;display:block"></i>
                <p>Belum ada servis terdaftar.</p>
                <a href="{{ route('my-service.create') }}" class="btn btn-primary" style="margin-top:12px"><i class="fas fa-plus"></i> Daftar Servis Sekarang</a>
            </div>
            @endif
        </div>
    </div>

    <!-- Kolom kanan: Banner iklan portrait -->
    <div>
        @foreach($banners as $banner)
        <div style="display:block;margin-bottom:16px;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;transition:transform .2s,box-shadow .2s;background:#fff" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.08)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            {{-- Gambar tanpa overlay --}}
            <img src="{{ str_starts_with($banner->gambar, 'http') ? $banner->gambar : Storage::url($banner->gambar) }}" alt="{{ $banner->judul }}" style="width:100%;height:auto;display:block;aspect-ratio:2/3;object-fit:cover">

            {{-- Area konten putih --}}
            <div style="padding:16px 18px 18px;background:#fff">
                <div style="font-size:.95rem;font-weight:800;color:#0f172a;margin-bottom:6px;line-height:1.3">{{ $banner->judul }}</div>
                @if($banner->deskripsi)
                <div class="banner-desc" style="font-size:.78rem;color:#475569;line-height:1.6;margin-bottom:14px">
                    {!! $banner->deskripsi !!}
                </div>
                @endif
                <div style="display:flex;gap:8px">
                    <a href="{{ $banner->link ?: '#' }}" target="_blank" style="display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:10px 16px;background:var(--primary);color:#fff;border-radius:10px;font-size:.78rem;font-weight:700;text-decoration:none;transition:opacity .2s;flex:1" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                        <i class="fas fa-rocket"></i> Daftar Sekarang!
                    </a>
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings['telp'] ?? '6281234567890') }}?text=Halo%20FixPro,%20saya%20tertarik%20dengan%20{{ urlencode($banner->judul) }}" target="_blank" style="display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:10px 16px;background:#25D366;color:#fff;border-radius:10px;font-size:.78rem;font-weight:700;text-decoration:none;transition:opacity .2s;flex:1" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>
        @endforeach

        <!-- Info box -->
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px;font-size:.78rem;color:#166534;line-height:1.6">
            <strong><i class="fas fa-info-circle"></i> Tips:</strong><br>
            • Cek status servis secara berkala<br>
            • Bawa bukti servis saat pengambilan<br>
            • Garansi berlaku 30 hari
        </div>
    </div>
</div>

<style>
    @media (max-width: 900px) {
        .page-content > div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
        }
    }
    /* Banner WYSIWYG content styling */
    .banner-desc p { margin: 0 0 6px; }
    .banner-desc ul, .banner-desc ol { margin: 4px 0; padding-left: 18px; }
    .banner-desc li { margin-bottom: 2px; }
    .banner-desc strong { color: #1e293b; }
    .banner-desc a { color: #0d9488; text-decoration: underline; }
    .banner-desc h1,.banner-desc h2,.banner-desc h3,.banner-desc h4 { font-size: .85rem; font-weight: 700; margin: 6px 0 4px; color: #0f172a; }
</style>
@endsection
