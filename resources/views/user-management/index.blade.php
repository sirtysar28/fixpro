@extends('layouts.app')
@section('title', 'Kelola Akun User')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-users-cog" style="color:var(--primary);margin-right:6px"></i> Kelola Akun User</h2>
</div>

<!-- Info Banner -->
@if(auth()->user()->isSuperAdmin())
<div class="card mb-4" style="background:linear-gradient(135deg,#0d9488 0%,#065f46 100%);color:#fff;border:none">
    <div style="display:flex;align-items:center;gap:12px">
        <div style="width:48px;height:48px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem"><i class="fas fa-crown"></i></div>
        <div>
            <div style="font-size:.88rem;font-weight:700">Anda login sebagai Super Admin</div>
            <div style="font-size:.76rem;opacity:.85">Super Admin dapat melihat & mengelola semua cabang. Anda juga dapat mengubah status Super Admin user lain.</div>
        </div>
    </div>
</div>
@else
@php $isEnterprise = auth()->user()->isEnterprise() && auth()->user()->isAdmin(); @endphp
@if($isEnterprise)
<div class="card mb-4" style="background:linear-gradient(135deg,#7c3aed 0%,#4f46e5 100%);color:#fff;border:none">
    <div style="display:flex;align-items:center;gap:12px">
        <div style="width:48px;height:48px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem"><i class="fas fa-building"></i></div>
        <div>
            <div style="font-size:.88rem;font-weight:700">Anda login sebagai Admin Enterprise (Cabang Pusat)</div>
            <div style="font-size:.76rem;opacity:.85">Anda bisa mengelola user di semua cabang group Anda. Gunakan menu <strong>Multi Cabang</strong> untuk membuat akun login langsung di cabang anak (tombol <i class="fas fa-user-plus"></i>).</div>
        </div>
    </div>
</div>
@else
<div class="card mb-4" style="background:linear-gradient(135deg,#2563eb 0%,#1e40af 100%);color:#fff;border:none">
    <div style="display:flex;align-items:center;gap:12px">
        <div style="width:48px;height:48px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem"><i class="fas fa-user-shield"></i></div>
        <div>
            <div style="font-size:.88rem;font-weight:700">Anda login sebagai Admin Cabang</div>
            <div style="font-size:.76rem;opacity:.85">Anda hanya bisa mengelola user di cabang <strong>{{ auth()->user()->cabang?->nama ?? '-' }}</strong>. Anda tidak bisa menambah/mengubah role Admin atau Super Admin.</div>
        </div>
    </div>
</div>
@endif
@endif

<!-- Form tambah user -->
<div class="card">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-user-plus" style="color:var(--primary);margin-right:6px"></i> Tambah Akun Baru</h3>
    <form method="POST" action="{{ route('user-management.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Nama Lengkap *</label>
                <input type="text" name="name" class="form-input" required placeholder="Nama lengkap">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-input" required placeholder="email@contoh.com">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" class="form-input" required minlength="6" placeholder="Min. 6 karakter">
            </div>
            <div class="form-group">
                <label>No. HP</label>
                <input type="tel" name="phone" class="form-input" placeholder="08xxxxxxxxxx">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Role *</label>
                <select name="role_id" class="form-input" required>
                    @foreach($roles as $r)
                        @if(!auth()->user()->isSuperAdmin() && !$isEnterprise && $r->name === 'Admin')
                            @continue
                        @endif
                    <option value="{{ $r->id }}" {{ $r->name === 'Staff' ? 'selected' : '' }}>{{ $r->name }} — {{ $r->description }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Cabang *</label>
                <select name="cabang_id" class="form-input" required>
                    @foreach($cabangs as $c)
                    <option value="{{ $c->id }}">{{ $c->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <div class="form-row">
            <div class="form-group">
                <label>Paket</label>
                <select name="paket" class="form-input">
                    <option value="standar">Standar (1 cabang)</option>
                    <option value="enterprise">Enterprise (1 pusat + 3 cabang anak + transfer stok)</option>
                </select>
                <div class="text-xs text-muted" style="margin-top:4px">
                    <strong>Standar</strong>: 1 cabang saja. <strong>Enterprise</strong>: 1 pusat + 3 cabang anak + transfer stok antar cabang.
                </div>
            </div>
            <div class="form-group"></div>
        </div>
        @endif

        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="is_active" value="1" checked style="width:18px;height:18px;accent-color:var(--primary)">
                <span>Akun Aktif</span>
            </label>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Akun</button>
    </form>
</div>

<!-- Daftar user -->
<div class="card">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-list" style="color:var(--primary);margin-right:6px"></i> Daftar Akun ({{ $users->count() }})</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    @if(auth()->user()->isSuperAdmin())<th>Paket</th>@endif
                    <th>Cabang</th>
                    <th>Status</th>
                    <th>Masa Berlaku</th>
                    <th>Pelanggan</th>
                    @if(auth()->user()->isSuperAdmin())<th>Super Admin</th><th>Aksi</th>@else<th>Aksi</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="width:32px;height:32px;border-radius:50%;background:var(--primary-bg);display:flex;align-items:center;justify-content:center;color:var(--primary);font-weight:700;font-size:.7rem">{{ strtoupper(substr($u->name, 0, 2)) }}</div>
                            <strong>{{ $u->name }}</strong>
                        </div>
                    </td>
                    <td style="font-size:.78rem;color:#64748b">{{ $u->email }}</td>
                    <td><span class="badge role-{{ strtolower($u->role?->name ?? 'user') }}">{{ $u->role?->name ?? 'User' }}</span></td>
                    @if(auth()->user()->isSuperAdmin())
                    <td>
                        @if($u->isSuperAdmin())
                            <span class="badge" style="background:linear-gradient(135deg,#0d9488,#065f46);color:#fff"><i class="fas fa-crown" style="margin-right:3px"></i> Super</span>
                        @elseif($u->paket === 'enterprise')
                            <span class="badge" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff"><i class="fas fa-building" style="margin-right:3px"></i> Enterprise</span>
                        @else
                            <span class="badge" style="background:#eff6ff;color:#1e40af">Standar</span>
                        @endif
                    </td>
                    @endif
                    <td>
                        @if($u->isSuperAdmin())
                            <span class="badge" style="background:#fef3c7;color:#92400e"><i class="fas fa-infinity" style="margin-right:3px"></i> Semua Cabang</span>
                        @else
                            <span class="badge badge-masuk">{{ $u->cabang?->nama ?? '-' }}</span>
                        @endif
                    </td>
                    <td>
                        @if($u->is_active)
                            <span class="badge badge-selesai">Aktif</span>
                        @else
                            <span class="badge badge-pending">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        @if($u->isSuperAdmin() || $u->isAdmin())
                            <span class="badge" style="background:#f0fdf4;color:#166534">∞ Permanen</span>
                        @elseif($u->is_permanent)
                            <span class="badge" style="background:#f0fdf4;color:#166534">✅ Permanen</span>
                        @elseif($u->login_expires_at)
                            @php $dLeft = max(0, now()->diffInDays($u->login_expires_at, false)) @endphp
                            @if($dLeft <= 0)
                                <span class="badge badge-pending"><i class="fas fa-times-circle"></i> Expired</span>
                            @elseif($dLeft <= 7)
                                <span class="badge badge-urgent">⏰ {{ $dLeft }} hari</span>
                            @else
                                <span class="badge badge-proses">⏳ {{ $dLeft }} hari</span>
                            @endif
                            <div style="font-size:.65rem;color:#94a3b8">{{ $u->login_expires_at->format('d/m/Y') }}</div>
                        @else
                            <span class="badge badge-normal">-</span>
                        @endif
                    </td>
                    <td>
                        @if($u->pelanggan)
                            <span class="badge" style="background:#eff6ff;color:#1e40af;font-size:10px"><i class="fas fa-link" style="margin-right:2px"></i> {{ $u->pelanggan->nama }}</span>
                            <div style="font-size:.6rem;color:#94a3b8">{{ $u->pelanggan->no_hp }}</div>
                        @else
                            <span style="color:#cbd5e1;font-size:.75rem">—</span>
                        @endif
                    </td>
                    @if(auth()->user()->isSuperAdmin())
                    <td>
                        @if($u->isSuperAdmin())
                            <span style="color:var(--accent);font-size:1rem"><i class="fas fa-crown"></i></span>
                        @else
                            <span style="color:#cbd5e1;font-size:1rem"><i class="far fa-circle"></i></span>
                        @endif
                    </td>
                    @endif
                    <td style="white-space:nowrap">
                        <button type="button" class="btn btn-primary btn-xs" onclick="editUser({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ $u->email }}', '{{ $u->phone ?? '' }}', {{ $u->role_id }}, {{ $u->cabang_id ?? 0 }}, {{ $u->is_active ? 1 : 0 }})"><i class="fas fa-edit"></i></button>

                        @if(auth()->user()->isSuperAdmin() && !$u->isSuperAdmin())
                        <form method="POST" action="{{ route('user-management.toggle-paket', $u) }}" style="display:inline" title="Ubah Paket">
                            @csrf
                            <input type="hidden" name="paket" value="{{ $u->isEnterprise() ? 'standar' : 'enterprise' }}">
                            <button type="submit" class="btn btn-xs" style="background:{{ $u->isEnterprise() ? '#eff6ff' : '#fef3c7' }};color:{{ $u->isEnterprise() ? '#1e40af' : '#92400e' }};border:1px solid {{ $u->isEnterprise() ? '#bfdbfe' : '#fde68a' }}"><i class="fas fa-{{ $u->isEnterprise() ? 'building' : 'box' }}"></i></button>
                        </form>
                        @endif

                        @if(auth()->user()->isSuperAdmin() && $u->id !== auth()->id())
                        <form method="POST" action="{{ route('user-management.toggle-super', $u) }}" style="display:inline" title="{{ $u->isSuperAdmin() ? 'Cabut Super Admin' : 'Jadikan Super Admin' }}">
                            @csrf
                            <button type="submit" class="btn btn-xs" style="background:{{ $u->isSuperAdmin() ? '#fee2e2' : '#fef3c7' }};color:{{ $u->isSuperAdmin() ? '#dc2626' : '#92400e' }};border:1px solid {{ $u->isSuperAdmin() ? '#fecaca' : '#fde68a' }}"><i class="fas fa-crown"></i></button>
                        </form>
                        @endif

                        @if(!$u->isSuperAdmin())
                        <form method="POST" action="{{ route('user-management.destroy', $u) }}" style="display:inline" onsubmit="return confirm('Hapus akun {{ $u->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:28px;max-width:540px;width:90%">
        <h3 style="font-size:1rem;margin-bottom:16px"><i class="fas fa-user-edit" style="color:var(--primary);margin-right:6px"></i> Edit Akun</h3>
        <form id="editForm" method="POST" action="">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label>Nama *</label>
                    <input type="text" name="name" id="editName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="editEmail" class="form-input" required>
                </div>
            </div>
            <div class="form-group">
                <label>Password Baru (kosongkan jika tidak ganti)</label>
                <input type="password" name="password" class="form-input" minlength="6" placeholder="Kosongkan jika tidak diubah">
            </div>
            <div class="form-group">
                <label>No. HP</label>
                <input type="tel" name="phone" id="editPhone" class="form-input">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role_id" id="editRole" class="form-input" required>
                        @foreach($roles as $r)
                            @if(!auth()->user()->isSuperAdmin() && !$isEnterprise && $r->name === 'Admin')
                                @continue
                            @endif
                        <option value="{{ $r->id }}">{{ $r->name }} — {{ $r->description }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Cabang *</label>
                    <select name="cabang_id" id="editCabang" class="form-input" required>
                        @foreach($cabangs as $c)
                        <option value="{{ $c->id }}">{{ $c->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if(auth()->user()->isSuperAdmin())
            <div class="form-row">
                <div class="form-group">
                    <label>Paket</label>
                    <select name="paket" id="editPaket" class="form-input">
                        <option value="standar">Standar (1 cabang)</option>
                        <option value="enterprise">Enterprise (1 pusat + 3 cabang anak + transfer stok)</option>
                    </select>
                </div>
                <div class="form-group"></div>
            </div>
            @endif
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="is_active" id="editActive" value="1" style="width:18px;height:18px;accent-color:var(--primary)">
                    <span>Akun Aktif</span>
                </label>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()"><i class="fas fa-times"></i> Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(id, name, email, phone, roleId, cabangId, isActive) {
    document.getElementById('editForm').action = '/user-management/' + id;
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editPhone').value = phone;
    document.getElementById('editRole').value = roleId;
    document.getElementById('editCabang').value = cabangId;
    document.getElementById('editActive').checked = isActive == 1;
    @if(auth()->user()->isSuperAdmin())
    // Fetch paket
    fetch('/api/user/' + id + '/paket').then(r=>r.json()).then(d=>{
        document.getElementById('editPaket').value = d.paket || 'standar';
    });
    @endif
    document.getElementById('editModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>
@endsection
