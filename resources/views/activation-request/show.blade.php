@use('Illuminate\Support\Facades\Storage')
@extends('layouts.app')
@section('title', 'Detail Request Aktivasi')

@section('content')
<div class="flex-between mb-4">
    <a href="{{ route('admin.activation-requests.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    <h2 style="margin:0;font-size:1.1rem">Detail Request #{{ $activationRequest->id }}</h2>
</div>

<div class="grid-2">
    <div>
        {{-- Info Pemohon --}}
        <div class="card mb-4">
            <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-user" style="color:var(--primary);margin-right:6px"></i> Data Pemohon</h3>
            <table style="width:100%;font-size:.84rem">
                <tr><td class="text-muted" style="padding:6px 0">Nama</td><td>{{ $activationRequest->user?->name ?? '-' }}</td></tr>
                <tr><td class="text-muted" style="padding:6px 0">Email</td><td>{{ $activationRequest->user?->email ?? '-' }}</td></tr>
                <tr><td class="text-muted" style="padding:6px 0">No. HP</td><td>{{ $activationRequest->user?->phone ?? '-' }}</td></tr>
                <tr><td class="text-muted" style="padding:6px 0">Nama Toko</td><td>{{ $activationRequest->nama_toko ?? '-' }}</td></tr>
                <tr><td class="text-muted" style="padding:6px 0">Cabang</td><td>{{ $activationRequest->cabang?->nama ?? '-' }}</td></tr>
                <tr><td class="text-muted" style="padding:6px 0">Status Trial</td>
                    <td>
                        @if($activationRequest->user?->is_permanent)
                        <span class="badge badge-selesai">Permanen</span>
                        @else
                        <span class="badge badge-proses">Trial {{ $activationRequest->user?->daysUntilExpiry() }} hari</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- Info Request --}}
        <div class="card">
            <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-file-alt" style="color:var(--accent);margin-right:6px"></i> Detail Request</h3>
            <table style="width:100%;font-size:.84rem">
                <tr><td class="text-muted" style="padding:6px 0">Tanggal Request</td><td>{{ $activationRequest->created_at?->format('d/m/Y H:i') }}</td></tr>
                <tr><td class="text-muted" style="padding:6px 0">Durasi</td><td>{{ $activationRequest->durasiLabel() }}</td></tr>
                <tr><td class="text-muted" style="padding:6px 0">Nominal Transfer</td><td>{{ $activationRequest->nominal_bayar ? formatRp($activationRequest->nominal_bayar) : '-' }}</td></tr>
                <tr><td class="text-muted" style="padding:6px 0">Catatan</td><td>{{ $activationRequest->catatan ?? '-' }}</td></tr>
                <tr><td class="text-muted" style="padding:6px 0">Status</td>
                    <td>
                        @if($activationRequest->status === 'pending')
                        <span class="badge badge-proses">Pending</span>
                        @elseif($activationRequest->status === 'approved')
                        <span class="badge badge-selesai">Disetujui</span>
                        @else
                        <span class="badge badge-pending">Ditolak</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div>
        {{-- Bukti Transfer --}}
        @if($activationRequest->bukti_transfer)
        <div class="card mb-4">
            <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-receipt" style="color:var(--success);margin-right:6px"></i> Bukti Transfer</h3>
            <div style="text-align:center">
                <a href="{{ Storage::url($activationRequest->bukti_transfer) }}" target="_blank">
                    <img src="{{ Storage::url($activationRequest->bukti_transfer) }}" style="max-width:100%;max-height:400px;border-radius:10px;border:1px solid #e2e8f0">
                </a>
            </div>
            <div style="text-align:center;margin-top:10px">
                <a href="{{ Storage::url($activationRequest->bukti_transfer) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-external-link-alt"></i> Buka di Tab Baru</a>
            </div>
        </div>
        @endif

        {{-- Aksi --}}
        @if($activationRequest->status === 'pending')
        <div class="card" style="border:2px solid var(--primary)">
            <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-gavel" style="color:var(--primary);margin-right:6px"></i> Proses Request</h3>
            <form method="POST" action="{{ route('admin.activation-requests.approve', $activationRequest) }}">
                @csrf
                <div class="form-group">
                    <label>Catatan (Opsional)</label>
                    <textarea name="admin_note" class="form-input" rows="2" placeholder="Catatan untuk user..."></textarea>
                </div>
                <button type="submit" class="btn btn-success" style="width:100%" onclick="return confirm('Approve request ini? Durasi: {{ $activationRequest->durasiLabel() }}')">
                    <i class="fas fa-check-circle"></i> Approve — {{ $activationRequest->durasiLabel() }}
                </button>
            </form>
            <div style="margin-top:12px;border-top:1px solid #e2e8f0;padding-top:12px">
                <form method="POST" action="{{ route('admin.activation-requests.reject', $activationRequest) }}">
                    @csrf
                    <div class="form-group">
                        <label>Alasan Penolakan *</label>
                        <textarea name="admin_note" class="form-input" rows="2" placeholder="Alasan penolakan..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger" style="width:100%" onclick="return confirm('Tolak request ini?')">
                        <i class="fas fa-times-circle"></i> Tolak Request
                    </button>
                </form>
            </div>
        </div>
        @else
        <div class="card">
            <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-info-circle" style="color:var(--info);margin-right:6px"></i> Info Proses</h3>
            <table style="width:100%;font-size:.84rem">
                <tr><td class="text-muted" style="padding:6px 0">Diproses oleh</td><td>{{ $activationRequest->approvedBy?->name ?? '-' }}</td></tr>
                <tr><td class="text-muted" style="padding:6px 0">Waktu</td><td>{{ $activationRequest->approved_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                <tr><td class="text-muted" style="padding:6px 0">Catatan</td><td>{{ $activationRequest->admin_note ?? '-' }}</td></tr>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
