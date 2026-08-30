@extends('layouts.app')
@section('title', 'Role & Permission')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-users-cog" style="color:var(--primary)"></i> Role & Permission</h2>
    <a href="{{ route('user-management.index') }}" class="btn btn-primary btn-sm"><i class="fas fa-user-cog"></i> Kelola User</a>
</div>

<div class="card mb-4">
    <div class="card-header"><h3>Daftar Role & Jumlah User</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Role</th><th>Deskripsi</th><th>Jumlah User</th><th>Akses / Permission</th></tr></thead>
            <tbody>
                @foreach($roles as $role)
                <tr>
                    <td><strong>{{ $role->name }}</strong></td>
                    <td>{{ $role->description ?? '-' }}</td>
                    <td><span class="badge badge-masuk">{{ $role->users_count }} user</span></td>
                    <td style="font-size:.76rem;color:#64748b;line-height:1.7">{{ $permissionMap[$role->name] ?? 'Sesuai menu standar peran ini.' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-info-circle"></i> Catatan Keamanan</h3></div>
    <div style="padding:12px 16px;font-size:.82rem;color:#64748b;line-height:1.9">
        <ul style="padding-left:18px">
            <li><b>Super Admin</b> bypass semua pengecekan role dan mendapat akses penuh Control (aktivasi, kode, status, paket, user, role, audit log).</li>
            <li><b>Admin Cabang Anak</b> terkunci ke cabangnya sendiri — stok, invoice, dan master data tidak akan tercampur dengan toko lain.</li>
            <li><b>Void invoice & approval diskon</b> hanya boleh dilakukan Admin / Super Admin; setiap perubahan tercatat di <a href="{{ route('audit-log.index') }}">Audit Log</a>.</li>
            <li><b>Stok cabang/gudang lain tidak boleh tercampur</b> — sistem otomatis memvalidasi kepemilikan stok setiap transaksi invoice.</li>
        </ul>
    </div>
</div>
@endsection
