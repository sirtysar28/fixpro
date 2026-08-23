@extends('layouts.app')
@section('title', 'Daftar Servis HP')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0">Daftar Servis HP</h2>
    @if(auth()->user()->isUser() && !auth()->user()->isAdmin() && !auth()->user()->isStaff())
    <a href="{{ route('my-service.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Daftar Servis Baru</a>
    @endif
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kode</th><th>Tanggal</th><th>Cabang</th><th>Perangkat</th><th>Keluhan</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($servis as $s)
                <tr>
                    <td><strong style="color:var(--primary)">{{ $s->kode }}</strong></td>
                    <td>{{ $s->tanggal?->format('d/m/Y') }}</td>
                    <td><span class="badge badge-masuk">{{ $s->cabang?->nama ?? '-' }}</span></td>
                    <td>{{ $s->perangkat }}</td>
                    <td>{{ $s->keluhan }}</td>
                    <td><span class="badge badge-{{ strtolower($s->status) }}">{{ $s->status }}</span></td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('my-service.show', $s) }}" class="btn btn-secondary btn-xs"><i class="fas fa-eye"></i> Detail</a>
                        @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
                            {{-- Quick status Selesai --}}
                            @if($s->status !== 'Selesai' && $s->status !== 'Dibatalkan')
                            <form method="POST" action="{{ route('my-service.update-status', $s) }}" style="display:inline" onsubmit="return confirm('Ubah status servis {{ $s->kode }} menjadi Selesai?')">
                                @csrf
                                <input type="hidden" name="status" value="Selesai">
                                <button type="submit" class="btn btn-xs" style="background:#dcfce7;color:#166534;border:1px solid #bbf7d0"><i class="fas fa-check"></i> Selesai</button>
                            </form>
                            @endif

                            {{-- Hapus servis: Super Admin & Admin Cabang --}}
                            <form method="POST" action="{{ route('my-service.destroy', $s) }}" style="display:inline" onsubmit="return confirmDelete(event, '{{ $s->kode }}', '{{ $s->perangkat }}', '{{ $s->pelanggan?->nama ?? '-' }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" title="Hapus servis"><i class="fas fa-trash"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8">
                    <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px"></i>
                    Belum ada servis.
                    @if(auth()->user()->isUser() && !auth()->user()->isAdmin() && !auth()->user()->isStaff())
                    <a href="{{ route('my-service.create') }}" style="color:var(--primary)">Daftar sekarang</a>
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $servis->links() }}
</div>

{{-- Modal Konfirmasi Hapus --}}
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:28px;max-width:420px;width:90%;text-align:center">
        <div style="width:56px;height:56px;border-radius:50%;background:#fee2e2;display:inline-flex;align-items:center;justify-content:center;font-size:1.4rem;color:#dc2626;margin-bottom:16px"><i class="fas fa-exclamation-triangle"></i></div>
        <h3 style="font-size:1rem;margin:0 0 8px">Hapus Servis?</h3>
        <p style="color:#64748b;font-size:.85rem;margin:0 0 6px">Anda akan menghapus servis berikut:</p>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px;margin-bottom:16px;text-align:left;font-size:.82rem">
            <div style="display:flex;justify-content:space-between"><span style="color:#64748b">Kode</span><strong id="delKode" style="color:var(--primary)">-</strong></div>
            <div style="display:flex;justify-content:space-between;margin-top:4px"><span style="color:#64748b">Perangkat</span><strong id="delPerangkat">-</strong></div>
            <div style="display:flex;justify-content:space-between;margin-top:4px"><span style="color:#64748b">Pelanggan</span><strong id="delPelanggan">-</strong></div>
        </div>
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px;margin-bottom:16px;font-size:.78rem;color:#92400e">
            <i class="fas fa-info-circle"></i> Stok sparepart akan dikembalikan & DP akan dikoreksi ke Kas.
        </div>
        <form id="deleteForm" method="POST" style="display:flex;gap:8px;justify-content:center">
            @csrf @method('DELETE')
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()"><i class="fas fa-times"></i> Batal</button>
            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Ya, Hapus</button>
        </form>
    </div>
</div>

<script>
function confirmDelete(event, kode, perangkat, pelanggan) {
    event.preventDefault();
    document.getElementById('delKode').textContent = kode;
    document.getElementById('delPerangkat').textContent = perangkat;
    document.getElementById('delPelanggan').textContent = pelanggan;
    const form = event.target.closest('form');
    document.getElementById('deleteForm').action = form.action;
    // Copy CSRF token
    const csrfInput = form.querySelector('input[name="_token"]');
    const methodInput = form.querySelector('input[name="_method"]');
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.querySelector('input[name="_token"]').value = csrfInput.value;
    deleteForm.querySelector('input[name="_method"]').value = methodInput.value;
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
</script>
@endsection
