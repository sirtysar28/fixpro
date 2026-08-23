@extends('layouts.app')
@section('title', 'Paket Berlangganan')

@section('content')
@php $user = auth()->user(); @endphp

<div class="flex-between mb-4" style="flex-wrap:wrap;gap:10px">
    <h2 style="margin:0"><i class="fas fa-star" style="color:var(--accent);margin-right:6px"></i> Paket Berlangganan</h2>
    @if($user->isSuperAdmin())
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('activateForm').style.display='block'"><i class="fas fa-bolt"></i> Aktifkan Paket</button>
    @endif
</div>

{{-- Info langganan user sendiri --}}
@if(!$user->isSuperAdmin())
@php
    $my = $user->subscriptionSummary();
    $myStatus = $user->subscriptionStatus();
    $myStatusLabel = $user->subscriptionStatusLabel();
@endphp
@if($my)
<div class="saldo-tracker" style="background:linear-gradient(135deg,{{ ($my['type']??'')==='permanent' ? '#16a34a,#15803d' : ($myStatus==='aktif' ? 'var(--primary),var(--primary-dark)' : ($myStatus==='kedaluwarsa' ? '#dc2626,#991b1b' : '#d97706,#b45309')) }});margin-bottom:20px">
    <div style="font-size:2rem">@if(($my['type']??'')==='permanent')@else<i class="fas fa-{{ $myStatusLabel['icon'] }}"></i>@endif</div>
    <div style="flex:1">
        <div class="saldo-label">Status Aktivasi — <strong>{{ $myStatusLabel['label'] }}</strong> @if(!empty($my['label'])) · {{ $my['label'] }}@endif</div>
        @if(($my['days_left'] ?? null) !== null)
        <div class="saldo-value">{{ $my['days_left'] }} hari tersisa</div>
        <div style="font-size:.78rem;opacity:.85;margin-top:2px">Berakhir: {{ ($my['ends_at'] ?? null)?->translatedFormat('d F Y H:i') }}</div>
        @else
        <div class="saldo-value">{{ $myStatusLabel['label'] }}</div>
        <div style="font-size:.78rem;opacity:.85;margin-top:2px">Akses tanpa batas waktu</div>
        @endif
    </div>
    @if(!empty($my['kode']))
    <div style="font-size:.7rem;opacity:.8;text-align:right">Kode<br><strong>{{ $my['kode'] }}</strong></div>
    @endif
</div>
@endif
@endif

{{-- Form aktivasi (Super Admin) --}}
@if($user->isSuperAdmin())
<div class="card mb-4" id="activateForm" style="display:none;border:2px solid var(--primary)">
    <h3 style="font-size:.95rem;margin-bottom:14px"><i class="fas fa-bolt" style="color:var(--primary)"></i> Aktifkan Paket Berlangganan (3 Bulan)</h3>
    <form method="POST" action="{{ route('subscription.activate') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Akun Admin Cabang *</label>
                <select name="user_id" class="form-input" required>
                    <option value="">-- Pilih Akun --</option>
                    @foreach($targetUsers as $tu)
                    <option value="{{ $tu->id }}">{{ $tu->name }} ({{ $tu->email }}) — {{ $tu->cabang?->nama ?? '-' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Paket *</label>
                <select name="package" class="form-input" required id="pkgSelect" onchange="updatePkgInfo()">
                    <option value="standar">Standar — 1 Cabang (3 Bulan)</option>
                    <option value="enterprise">Enterprise — 1 Pusat + 3 Cabang Anak (3 Bulan)</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Durasi (bulan) *</label>
                <input type="number" name="duration_months" class="form-input" value="3" min="1" max="36" required>
            </div>
            <div class="form-group">
                <label>Nominal (opsional)</label>
                <input type="text" name="amount" class="form-input" placeholder="0" data-format-rupiah>
            </div>
        </div>
        <div class="form-group">
            <label>Catatan</label>
            <input type="text" name="note" class="form-input" placeholder="Mis. Pembayaran via Transfer BCA">
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="extend" checked> Perpanjang dari sisa langganan aktif (jika masih berlaku, tambahkan ke belakang)</label>
        </div>
        <div id="pkgInfo" style="padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:.82rem;color:#166534;margin-bottom:12px"></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Aktifkan Paket</button>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('activateForm').style.display='none'">Batal</button>
    </form>
</div>
<script>
var PKG = @json($packages);
function updatePkgInfo(){
    var v = document.getElementById('pkgSelect').value;
    var p = PKG[v] || {};
    document.getElementById('pkgInfo').innerHTML = '<i class="fas fa-info-circle"></i> ' + (p.desc || '') + ' — durasi default ' + (p.duration_months||3) + ' bulan, maks ' + (p.max_cabang||1) + ' cabang.';
}
updatePkgInfo();
</script>
@endif

{{-- Filter --}}
<div class="card mb-4">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
        <input type="text" name="search" value="{{ request('search') }}" class="form-input" style="flex:1;min-width:200px;padding:8px 12px" placeholder="Cari nama / email / kode...">
        <select name="status" class="form-input" style="width:auto;padding:8px 12px">
            <option value="">Semua Status</option>
            <option value="active" {{ request('status')==='active'?'selected':'' }}>Aktif</option>
            <option value="expired" {{ request('status')==='expired'?'selected':'' }}>Kedaluwarsa</option>
            <option value="cancelled" {{ request('status')==='cancelled'?'selected':'' }}>Dibatalkan</option>
        </select>
        <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filter</button>
    </form>
</div>

{{-- Daftar langganan --}}
<div class="card">
    <div class="card-header">
        <h3 style="margin:0;font-size:.92rem"><i class="fas fa-list"></i> Riwayat Langganan</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Akun</th>
                    <th>Paket</th>
                    <th>Durasi</th>
                    <th>Mulai</th>
                    <th>Berakhir</th>
                    <th>Sisa Hari</th>
                    <th>Status</th>
                    @if($user->isSuperAdmin())<th>Aksi</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($subs as $sub)
                <tr>
                    <td><strong style="color:var(--primary)">{{ $sub->kode }}</strong></td>
                    <td>
                        <div style="font-weight:600">{{ $sub->user?->name }}</div>
                        <div style="font-size:.7rem;color:#94a3b8">{{ $sub->user?->email }}</div>
                    </td>
                    <td><span class="badge" style="background:{{ $sub->package==='enterprise'?'#fef3c7':'#eff6ff' }};color:{{ $sub->package==='enterprise'?'#92400e':'#1e40af' }}">{{ ucfirst($sub->package) }}</span></td>
                    <td>{{ $sub->duration_months }} bln</td>
                    <td style="font-size:.76rem">{{ $sub->started_at?->format('d/m/Y') }}</td>
                    <td style="font-size:.76rem">{{ $sub->ends_at?->format('d/m/Y') }}</td>
                    <td>
                        @if($sub->isActive())
                        <strong style="color:var(--primary)">{{ $sub->daysLeft() }}</strong> hari
                        @else <span style="color:#94a3b8">—</span> @endif
                    </td>
                    <td>
                        @php
                            // Fitur #11: kategorisasi dinamis berdasarkan sisa hari
                            $rowStatus = 'aktif';
                            if (!$sub->isActive()) {
                                $rowStatus = 'kedaluwarsa';
                            } else {
                                $dl = $sub->daysLeft();
                                if ($dl <= 7) $rowStatus = 'segera_berakhir';
                                elseif ($dl <= 30) $rowStatus = 'akan_berakhir';
                            }
                            $rowStyle = [
                                'aktif'           => ['bg' => '#dcfce7', 'color' => '#166534', 'text' => 'Aktif'],
                                'akan_berakhir'   => ['bg' => '#fef3c7', 'color' => '#92400e', 'text' => 'Akan Berakhir'],
                                'segera_berakhir' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'text' => 'Segera Berakhir'],
                                'kedaluwarsa'     => ['bg' => '#fee2e2', 'color' => '#991b1b', 'text' => 'Kedaluwarsa'],
                            ][$rowStatus] ?? null;
                            if ($sub->status === 'cancelled') $rowStyle = ['bg' => '#f1f5f9','color' => '#64748b','text' => 'Dibatalkan'];
                        @endphp
                        @if($rowStyle)
                        <span class="badge" style="background:{{ $rowStyle['bg'] }};color:{{ $rowStyle['color'] }}">{{ $rowStyle['text'] }}</span>
                        @endif
                    </td>
                    @if($user->isSuperAdmin())
                    <td>
                        @if($sub->status==='active')
                        <form method="POST" action="{{ route('subscription.cancel', $sub) }}" style="display:inline" onsubmit="return confirm('Batalkan langganan ini?')">
                            @csrf
                            <button class="btn btn-warning btn-xs"><i class="fas fa-times"></i> Batalkan</button>
                        </form>
                        @endif
                    </td>
                    @endif
                </tr>
                @endforeach
                @if($subs->isEmpty())
                <tr><td colspan="{{ $user->isSuperAdmin()?9:8 }}" style="text-align:center;padding:24px;color:#94a3b8">Belum ada langganan.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    {{ $subs->links() }}
</div>
@endsection
