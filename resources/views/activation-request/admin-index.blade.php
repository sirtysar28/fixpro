@use('Illuminate\Support\Facades\Storage')
@extends('layouts.app')
@section('title', 'Kelola Request Aktivasi — Super Admin')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-user-clock" style="color:var(--primary);margin-right:6px"></i> Request Aktivasi dari Cabang</h2>
    <div style="display:flex;gap:8px">
        <span class="badge badge-proses" style="font-size:.82rem;padding:6px 14px"><i class="fas fa-clock"></i> {{ $pendingCount }} pending</span>
        <a href="{{ route('activation.status') }}" class="btn btn-secondary"><i class="fas fa-shield-alt"></i> Status Aktivasi</a>
        <a href="{{ route('serial-number.index') }}" class="btn btn-secondary"><i class="fas fa-key"></i> Aktivasi Manual</a>
    </div>
</div>

{{-- DAFTAR REQUEST --}}
<div class="card mb-6">
    <div class="card-header">
        <h3><i class="fas fa-inbox" style="color:var(--warning);margin-right:6px"></i> Semua Request Aktivasi</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Cabang</th>
                    <th>Nama Toko</th>
                    <th>Pemilik</th>
                    <th>No. WA</th>
                    <th>Email</th>
                    <th>Paket</th>
                    <th>User/Perangkat</th>
                    <th>Durasi</th>
                    <th>Nominal</th>
                    <th>Bukti Transfer</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $req)
                <tr style="{{ in_array($req->status, ['pending', 'processing']) ? 'background:#fffbeb' : '' }}">
                    <td>{{ $req->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $req->nama_cabang ?? '-' }}</td>
                    <td><strong>{{ $req->nama_toko ?? '-' }}</strong></td>
                    <td>{{ $req->nama_pemilik ?? $req->user?->name ?? '-' }}</td>
                    <td>{{ $req->no_wa ?? $req->user?->phone ?? '-' }}</td>
                    <td>{{ $req->email ?? $req->user?->email ?? '-' }}</td>
                    <td>{{ ucfirst($req->paket ?? 'standar') }}</td>
                    <td>{{ $req->jumlah_user ?? 1 }} user{{ $req->jumlah_perangkat ? ' / ' . $req->jumlah_perangkat . ' dev' : '' }}</td>
                    <td>{{ $req->durasiLabel() }}</td>
                    <td>{{ $req->nominal_bayar ? formatRp($req->nominal_bayar) : '-' }}</td>
                    <td>
                        @if($req->bukti_transfer)
                        <a href="{{ Storage::url($req->bukti_transfer) }}" target="_blank" class="btn btn-xs btn-primary"><i class="fas fa-image"></i> Lihat</a>
                        @else
                        <span class="text-xs text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $req->statusBadge() }}">{{ $req->statusLabel() }}</span>
                    </td>
                    <td>
                        @if($req->status === 'pending')
                        <form method="POST" action="{{ route('admin.activation-requests.proses', $req) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-xs" style="background:#d97706;color:#fff" title="Tandai sedang diproses">
                                <i class="fas fa-cog"></i> Proses
                            </button>
                        </form>
                        @endif
                        @if(in_array($req->status, ['pending', 'processing']))
                        <div style="display:flex;gap:4px;margin-top:4px">
                            <form method="POST" action="{{ route('admin.activation-requests.approve', $req) }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="admin_note" value="">
                                <button type="submit" class="btn btn-success btn-xs" onclick="return confirm('Approve request dari {{ $req->nama_pemilik ?? $req->user?->name ?? '' }} — {{ $req->durasiLabel() }}? Kode aktivasi akan otomatis dibuat untuk cabang ini.')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <button class="btn btn-danger btn-xs" onclick="showRejectForm({{ $req->id }}, '{{ $req->nama_pemilik ?? $req->user?->name ?? '' }}')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        @elseif($req->admin_note)
                        <span class="text-xs text-muted" title="{{ $req->admin_note }}">{{ Str::limit($req->admin_note, 30) }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                @if($requests->isEmpty())
                <tr>
                    <td colspan="13" style="text-align:center;padding:30px;color:#94a3b8">
                        <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px"></i>
                        Belum ada request aktivasi
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;display:flex;justify-content:center">
        {{ $requests->withQueryString()->links() }}
    </div>
</div>

{{-- MODAL REJECT --}}
<div id="rejectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:none;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:24px;width:90%;max-width:450px;box-shadow:0 20px 60px rgba(0,0,0,.2)">
        <h3 style="margin:0 0 16px;color:#dc2626"><i class="fas fa-times-circle"></i> Tolak Request Aktivasi</h3>
        <p class="text-sm text-muted mb-4">Menolak request dari: <strong id="rejectUserName">-</strong></p>
        <form method="POST" id="rejectForm">
            @csrf
            <div class="form-group">
                <label>Alasan Penolakan *</label>
                <textarea name="admin_note" id="rejectReason" class="form-input" rows="3" placeholder="Tuliskan alasan penolakan..." required></textarea>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('rejectModal').style.display='none'"><i class="fas fa-times"></i> Batal</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Tolak</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectForm(reqId, userName) {
    document.getElementById('rejectUserName').textContent = userName;
    document.getElementById('rejectForm').action = '/admin/activation-requests/' + reqId + '/reject';
    document.getElementById('rejectModal').style.display = 'flex';
}
// Close modal on outside click
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
@endsection
