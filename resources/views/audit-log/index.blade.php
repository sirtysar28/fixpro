@extends('layouts.app')
@section('title', 'Audit & Log Aktivitas')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-clipboard-list" style="color:var(--primary);margin-right:6px"></i> Audit & Log Aktivitas</h2>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="{{ route('audit-log.export-csv', request()->query()) }}" class="btn btn-primary btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
        <button class="btn btn-secondary btn-sm" onclick="document.getElementById('clearForm').style.display='block'"><i class="fas fa-broom"></i> Bersihkan Log Lama</button>
    </div>
</div>

<!-- Info -->
<div class="card mb-4" style="background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);color:#fff;border:none">
    <div style="display:flex;align-items:center;gap:12px">
        <div style="width:48px;height:48px;background:rgba(255,255,255,.1);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem"><i class="fas fa-shield-alt"></i></div>
        <div>
            <div style="font-size:.88rem;font-weight:700">Rekam Jejak Aktivitas Sistem</div>
            <div style="font-size:.76rem;opacity:.75">Semua aktivitas user tercatat secara otomatis — hanya visible untuk Super Admin.</div>
        </div>
    </div>
</div>

<!-- Clear Form (hidden) -->
<div id="clearForm" class="card" style="display:none;margin-bottom:20px;border:2px solid #fca5a5;background:#fff5f5">
    <h3 style="font-size:.9rem;margin:0 0 12px;color:#991b1b"><i class="fas fa-exclamation-triangle"></i> Bersihkan Log Lama</h3>
    <form method="POST" action="{{ route('audit-log.clear') }}" style="display:flex;gap:8px;align-items:flex-end">
        @csrf
        <div class="form-group" style="margin:0;flex:1">
            <label>Hapus log lebih dari (hari)</label>
            <input type="number" name="days" class="form-input" value="90" min="30" style="max-width:120px">
        </div>
        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus log lama?')"><i class="fas fa-trash"></i> Hapus</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('clearForm').style.display='none'"><i class="fas fa-times"></i> Batal</button>
    </form>
</div>

<!-- Stat Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-label">Hari Ini</div>
        <div class="stat-value" style="color:#2563eb;font-size:1.4rem">{{ $statsToday }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-calendar-week"></i></div>
        <div class="stat-label">Minggu Ini</div>
        <div class="stat-value" style="color:#16a34a;font-size:1.4rem">{{ $statsWeek }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#f59e0b"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-label">Bulan Ini</div>
        <div class="stat-value" style="color:#f59e0b;font-size:1.4rem">{{ $statsMonth }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f1f5f9;color:#64748b"><i class="fas fa-database"></i></div>
        <div class="stat-label">Total Log</div>
        <div class="stat-value" style="color:#64748b;font-size:1.4rem">{{ $statsTotal }}</div>
    </div>
</div>

<!-- Activity Summary Row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
    <!-- Top Modules -->
    <div class="card" style="margin:0">
        <h3 style="font-size:.85rem;margin:0 0 12px"><i class="fas fa-chart-pie" style="color:var(--primary);margin-right:6px"></i> Aktivitas per Modul Hari Ini</h3>
        @if($moduleStats->count() > 0)
        @foreach($moduleStats as $ms)
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
            <div style="width:110px;font-size:.75rem;font-weight:600;color:#374151;text-align:right">
                @php
                    $tmpLog = new \App\Models\AuditLog(['module' => $ms->module]);
                @endphp
                {{ $tmpLog->getModuleLabel() }}
            </div>
            <div style="flex:1;height:22px;background:#f1f5f9;border-radius:6px;overflow:hidden;position:relative">
                @php $maxMod = $moduleStats->max('total'); $pct = $maxMod > 0 ? ($ms->total / $maxMod * 100) : 0; @endphp
                <div style="height:100%;width:{{ $pct }}%;background:var(--primary);border-radius:6px;transition:width .5s"></div>
                <span style="position:absolute;right:8px;top:50%;transform:translateY(-50%);font-size:.7rem;font-weight:700;color:#475569">{{ $ms->total }}</span>
            </div>
        </div>
        @endforeach
        @else
        <div style="text-align:center;padding:20px;color:#94a3b8;font-size:.8rem"><i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:6px"></i>Belum ada aktivitas hari ini</div>
        @endif
    </div>
    <!-- Top Users -->
    <div class="card" style="margin:0">
        <h3 style="font-size:.85rem;margin:0 0 12px"><i class="fas fa-users" style="color:var(--primary);margin-right:6px"></i> User Paling Aktif Hari Ini</h3>
        @if($userStats->count() > 0)
        @foreach($userStats as $idx => $us)
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
            <div style="width:28px;height:28px;border-radius:50%;background:{{ ['#dbeafe','#fef3c7','#dcfce7','#fce7f3','#f3e8ff'][$idx % 5] }};display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#475569">
                {{ $idx + 1 }}
            </div>
            <div style="flex:1">
                <div style="font-size:.8rem;font-weight:600;color:#1e293b">{{ $us->user?->name ?? 'System' }}</div>
                <div style="font-size:.68rem;color:#94a3b8">{{ $us->user?->email ?? '-' }}</div>
            </div>
            <div style="font-size:.8rem;font-weight:800;color:var(--primary)">{{ $us->total }} <span style="font-size:.65rem;font-weight:500">aktivitas</span></div>
        </div>
        @endforeach
        @else
        <div style="text-align:center;padding:20px;color:#94a3b8;font-size:.8rem"><i class="fas fa-user-clock" style="font-size:1.5rem;display:block;margin-bottom:6px"></i>Belum ada aktivitas hari ini</div>
        @endif
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px">
    <h3 style="font-size:.85rem;margin:0 0 12px"><i class="fas fa-filter" style="color:var(--primary);margin-right:6px"></i> Filter Log</h3>
    <form method="GET" action="{{ route('audit-log.index') }}" id="filterForm">
        <div class="form-row" style="grid-template-columns:2fr 1fr 1fr 1fr 1fr">
            <div class="form-group" style="margin:0">
                <label>Cari Deskripsi</label>
                <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="Cari aktivitas...">
            </div>
            <div class="form-group" style="margin:0">
                <label>Modul</label>
                <select name="module" class="form-input">
                    <option value="">Semua</option>
                    <option value="auth" {{ request('module') === 'auth' ? 'selected' : '' }}>Autentikasi</option>
                    <option value="servis" {{ request('module') === 'servis' ? 'selected' : '' }}>Servis</option>
                    <option value="pelanggan" {{ request('module') === 'pelanggan' ? 'selected' : '' }}>Pelanggan</option>
                    <option value="teknisi" {{ request('module') === 'teknisi' ? 'selected' : '' }}>Teknisi</option>
                    <option value="stok" {{ request('module') === 'stok' ? 'selected' : '' }}>Stok</option>
                    <option value="kas" {{ request('module') === 'kas' ? 'selected' : '' }}>Kas</option>
                    <option value="jual_beli" {{ request('module') === 'jual_beli' ? 'selected' : '' }}>Jual Beli</option>
                    <option value="penjualan_sparepart" {{ request('module') === 'penjualan_sparepart' ? 'selected' : '' }}>Penjualan Sparepart</option>
                    <option value="user_management" {{ request('module') === 'user_management' ? 'selected' : '' }}>Kelola Akun</option>
                    <option value="serial_number" {{ request('module') === 'serial_number' ? 'selected' : '' }}>Serial Number</option>
                    <option value="cabang" {{ request('module') === 'cabang' ? 'selected' : '' }}>Cabang</option>
                    <option value="settings" {{ request('module') === 'settings' ? 'selected' : '' }}>Pengaturan</option>
                    <option value="banner" {{ request('module') === 'banner' ? 'selected' : '' }}>Banner</option>
                    <option value="profile" {{ request('module') === 'profile' ? 'selected' : '' }}>Profil</option>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label>Aksi</label>
                <select name="action" class="form-input">
                    <option value="">Semua</option>
                    <option value="login" {{ request('action') === 'login' ? 'selected' : '' }}>Login</option>
                    <option value="logout" {{ request('action') === 'logout' ? 'selected' : '' }}>Logout</option>
                    <option value="create" {{ request('action') === 'create' ? 'selected' : '' }}>Tambah</option>
                    <option value="update" {{ request('action') === 'update' ? 'selected' : '' }}>Ubah</option>
                    <option value="delete" {{ request('action') === 'delete' ? 'selected' : '' }}>Hapus</option>
                    <option value="redeem" {{ request('action') === 'redeem' ? 'selected' : '' }}>Redeem</option>
                    <option value="generate" {{ request('action') === 'generate' ? 'selected' : '' }}>Generate</option>
                    <option value="toggle" {{ request('action') === 'toggle' ? 'selected' : '' }}>Toggle</option>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label>User</label>
                <select name="user_id" class="form-input">
                    <option value="">Semua</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:6px;align-items:flex-end">
                <button type="submit" class="btn btn-primary btn-sm" style="height:38px"><i class="fas fa-search"></i></button>
                <a href="{{ route('audit-log.index') }}" class="btn btn-secondary btn-sm" style="height:38px;text-decoration:none"><i class="fas fa-redo"></i></a>
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:8px">
            <div class="form-group" style="margin:0;flex:1">
                <label style="font-size:.7rem">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}" style="padding:6px 10px;font-size:.8rem">
            </div>
            <div class="form-group" style="margin:0;flex:1">
                <label style="font-size:.7rem">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}" style="padding:6px 10px;font-size:.8rem">
            </div>
        </div>
    </form>
</div>

<!-- Log Table -->
<div class="card">
    <div class="card-header">
        <h3 style="font-size:.9rem"><i class="fas fa-stream" style="color:var(--primary);margin-right:6px"></i> Riwayat Aktivitas ({{ $logs->total() }})</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:36px">#</th>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Modul</th>
                    <th>Aksi</th>
                    <th>Deskripsi</th>
                    <th>IP Address</th>
                    <th style="width:60px">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr style="cursor:pointer" onclick="showDetail({{ $log->id }})">
                    <td style="font-size:.72rem;color:#94a3b8">{{ $log->id }}</td>
                    <td style="white-space:nowrap">
                        <div style="font-size:.8rem;font-weight:600">{{ $log->created_at->format('d/m/Y') }}</div>
                        <div style="font-size:.72rem;color:#64748b">{{ $log->created_at->format('H:i:s') }}</div>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="width:30px;height:30px;border-radius:50%;background:var(--primary-bg);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;color:var(--primary)">
                                {{ strtoupper(substr($log->user?->name ?? 'SY', 0, 2)) }}
                            </div>
                            <div>
                                <div style="font-size:.8rem;font-weight:600">{{ $log->user?->name ?? 'System' }}</div>
                                <div style="font-size:.65rem;color:#94a3b8">{{ $log->user?->role?->name ?? '-' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge" style="{{ $log->getModuleBadge() }}">{{ $log->getModuleLabel() }}</span>
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:.76rem;font-weight:600;color:{{ $log->getActionColor() }}">
                            <i class="{{ $log->getActionIcon() }}" style="font-size:.7rem"></i>
                            {{ $log->getActionLabel() }}
                        </span>
                    </td>
                    <td style="max-width:300px">
                        <div style="font-size:.8rem;line-height:1.4;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">{{ $log->description }}</div>
                    </td>
                    <td>
                        <code style="font-size:.72rem;background:#f1f5f9;padding:2px 6px;border-radius:4px">{{ $log->ip_address ?? '-' }}</code>
                    </td>
                    <td>
                        <button class="btn btn-secondary btn-xs" onclick="event.stopPropagation();showDetail({{ $log->id }})" title="Detail">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:60px 20px;color:#94a3b8">
                        <i class="fas fa-clipboard-list" style="font-size:3rem;display:block;margin-bottom:12px;opacity:.5"></i>
                        <div style="font-size:.95rem;font-weight:600;margin-bottom:4px">Belum Ada Log Aktivitas</div>
                        <div style="font-size:.8rem">Aktivitas akan tercatat otomatis saat user berinteraksi dengan sistem.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div style="margin-top:16px;display:flex;justify-content:center;gap:4px;flex-wrap:wrap">
        {{ $logs->links() }}
    </div>
    @endif
</div>

<!-- Detail Modal -->
<div id="detailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:28px;max-width:640px;width:92%;max-height:85vh;overflow-y:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h3 style="font-size:1rem;margin:0"><i class="fas fa-info-circle" style="color:var(--primary);margin-right:6px"></i> Detail Log Aktivitas</h3>
            <button type="button" onclick="closeModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#94a3b8"><i class="fas fa-times"></i></button>
        </div>
        <div id="detailContent"></div>
    </div>
</div>

<script>
function showDetail(id) {
    fetch('/audit-log/' + id)
        .then(r => r.json())
        .then(data => {
            let html = '<div style="display:grid;gap:14px">';

            // Header
            html += '<div style="display:flex;align-items:center;gap:12px;padding:12px;background:#f8fafc;border-radius:10px">';
            html += '<div style="font-size:1.2rem"><i class="fas fa-user-shield" style="color:var(--primary)"></i></div>';
            html += '<div><div style="font-weight:700">' + (data.user || 'System') + '</div>';
            html += '<div style="font-size:.78rem;color:#64748b">' + (data.email || '-') + '</div></div>';
            html += '<div style="margin-left:auto;text-align:right"><div style="font-size:.8rem;font-weight:600">' + data.created_at + '</div>';
            html += '<div style="font-size:.72rem;color:#64748b">' + data.ip_address + '</div></div>';
            html += '</div>';

            // Info Grid
            html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">';
            html += detailItem('Modul', data.module);
            html += detailItem('Aksi', data.action);
            html += detailItem('Model Type', data.model_type || '-');
            html += detailItem('Model ID', data.model_id || '-');
            html += '</div>';

            // Description
            html += '<div style="padding:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px">';
            html += '<div style="font-size:.72rem;font-weight:600;color:#166534;margin-bottom:4px"><i class="fas fa-comment-alt"></i> Deskripsi</div>';
            html += '<div style="font-size:.85rem;color:#1e293b;line-height:1.5">' + data.description + '</div>';
            html += '</div>';

            // Old / New values
            if (data.old_values || data.new_values) {
                html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">';
                if (data.old_values) {
                    html += '<div style="padding:12px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px">';
                    html += '<div style="font-size:.72rem;font-weight:600;color:#991b1b;margin-bottom:6px"><i class="fas fa-arrow-left"></i> Nilai Lama</div>';
                    html += '<pre style="font-size:.72rem;color:#1e293b;margin:0;white-space:pre-wrap;max-height:200px;overflow-y:auto">' + JSON.stringify(data.old_values, null, 2) + '</pre>';
                    html += '</div>';
                }
                if (data.new_values) {
                    html += '<div style="padding:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px">';
                    html += '<div style="font-size:.72rem;font-weight:600;color:#166534;margin-bottom:6px"><i class="fas fa-arrow-right"></i> Nilai Baru</div>';
                    html += '<pre style="font-size:.72rem;color:#1e293b;margin:0;white-space:pre-wrap;max-height:200px;overflow-y:auto">' + JSON.stringify(data.new_values, null, 2) + '</pre>';
                    html += '</div>';
                }
                html += '</div>';
            }

            // User Agent
            if (data.user_agent) {
                html += '<div style="padding:10px;background:#f8fafc;border-radius:8px;font-size:.72rem;color:#64748b;word-break:break-all">';
                html += '<strong>User Agent:</strong> ' + data.user_agent;
                html += '</div>';
            }

            html += '</div>';
            document.getElementById('detailContent').innerHTML = html;
            document.getElementById('detailModal').style.display = 'flex';
        })
        .catch(err => {
            alert('Gagal memuat detail log.');
            console.error(err);
        });
}

function detailItem(label, value) {
    return '<div style="padding:8px 12px;background:#f8fafc;border-radius:8px"><div style="font-size:.68rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.3px">' + label + '</div><div style="font-size:.84rem;font-weight:600;color:#1e293b;margin-top:2px">' + value + '</div></div>';
}

function closeModal() {
    document.getElementById('detailModal').style.display = 'none';
}

// Close modal on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
// Close modal on outside click
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endsection
