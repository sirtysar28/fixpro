@use('Illuminate\Support\Facades\Storage')
@extends('layouts.app')
@section('title', 'Request Aktivasi')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-key" style="color:var(--primary);margin-right:6px"></i> Request Aktivasi Lisensi</h2>
    @if(auth()->user()->is_permanent)
    <span class="badge badge-selesai" style="font-size:.82rem;padding:6px 14px"><i class="fas fa-check-circle"></i> Akun Permanen</span>
    @else
    <span class="badge badge-proses" style="font-size:.82rem;padding:6px 14px"><i class="fas fa-clock"></i> Trial — {{ auth()->user()->daysUntilExpiry() }} hari lagi</span>
    @endif
</div>

@if(auth()->user()->is_permanent)
<div class="card" style="text-align:center;padding:40px">
    <div style="font-size:3rem;margin-bottom:12px">✅</div>
    <h3 style="color:var(--success);margin-bottom:8px">Akun Anda Sudah Permanen</h3>
    <p class="text-muted">Anda tidak perlu melakukan request aktivasi lagi. Terima kasih!</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary" style="margin-top:16px"><i class="fas fa-home"></i> Kembali ke Dashboard</a>
</div>
@else

{{-- INFO TRIAL --}}
<div class="card mb-4" style="background:linear-gradient(135deg,#fef3c7,#fde68a);border:1px solid #fcd34d">
    <div style="display:flex;align-items:center;gap:12px">
        <div style="font-size:2rem">⏳</div>
        <div>
            <h3 style="margin:0;color:#92400e;font-size:1rem">Masa Trial Anda</h3>
            <p style="margin:4px 0 0;color:#92400e;font-size:.84rem">
                @php $days = auth()->user()->daysUntilExpiry(); @endphp
                @if($days > 7)
                    <strong>{{ $days }} hari</strong> lagi — masih ada waktu untuk request aktivasi.
                @elseif($days > 0)
                    <strong style="color:#dc2626">{{ $days }} hari</strong> lagi — segera request aktivasi agar tidak terputus!
                @else
                    <strong style="color:#dc2626">Sudah Expired!</strong> — segera request aktivasi untuk melanjutkan.
                @endif
            </p>
            <p style="margin:4px 0 0;color:#92400e;font-size:.78rem">Berakhir: {{ auth()->user()->login_expires_at?->format('d F Y, H:i') }} WIB</p>
        </div>
    </div>
</div>

{{-- INFORMASI REKENING BANK --}}
@if($bankAccounts->count() > 0)
<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-university" style="color:var(--primary);margin-right:6px"></i> Rekening Pembayaran</h3>
    </div>
    <p class="text-xs text-muted mb-4">Silakan transfer ke salah satu rekening berikut, lalu upload bukti transfer:</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px">
        @foreach($bankAccounts as $bank)
        <div style="padding:16px;border:2px solid #e2e8f0;border-radius:12px;background:#f8fafc">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                @if($bank->logo)
                <img src="{{ Storage::url($bank->logo) }}" style="width:40px;height:40px;border-radius:8px;object-fit:contain">
                @else
                <div style="width:40px;height:40px;border-radius:8px;background:var(--primary-bg);display:flex;align-items:center;justify-content:center;color:var(--primary);font-weight:700;font-size:.7rem">{{ strtoupper(substr($bank->nama_bank, 0, 3)) }}</div>
                @endif
                <div>
                    <div style="font-weight:700;font-size:.9rem">{{ $bank->nama_bank }}</div>
                    <div style="font-size:.72rem;color:#64748b">a/n {{ $bank->atas_nama }}</div>
                </div>
            </div>
            <div style="font-size:1.1rem;font-weight:800;font-family:monospace;letter-spacing:1px;color:var(--primary)">{{ $bank->no_rekening }}</div>
            @if($bank->catatan)
            <div style="font-size:.72rem;color:#64748b;margin-top:6px"><i class="fas fa-info-circle"></i> {{ $bank->catatan }}</div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@else
<div class="card mb-4" style="background:#f8fafc;border:1px dashed #e2e8f0">
    <div style="text-align:center;padding:20px">
        <i class="fas fa-university" style="font-size:1.5rem;color:#94a3b8;display:block;margin-bottom:8px"></i>
        <p style="color:#64748b;font-size:.84rem;margin:0">Belum ada informasi rekening bank. Hubungi Admin Pusat.</p>
    </div>
</div>
@endif

{{-- FORM REQUEST --}}
@if(!$hasPending)
<div class="card mb-4" style="border:2px solid var(--primary)">
    <div class="card-header">
        <h3><i class="fas fa-paper-plane" style="color:var(--primary);margin-right:6px"></i> Kirim Request Aktivasi Permanen</h3>
    </div>
    <form method="POST" action="{{ route('activation-request.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Nama Cabang *</label>
                <input type="text" name="nama_cabang" class="form-input" value="{{ auth()->user()->cabang?->nama ?? '' }}" required placeholder="Contoh: FIXPRO Cabang Jakarta">
            </div>
            <div class="form-group">
                <label>Nama Pemilik / Admin *</label>
                <input type="text" name="nama_pemilik" class="form-input" value="{{ auth()->user()->name }}" required>
            </div>
        </div>
        <div class="form-group">
            <label>Alamat Cabang</label>
            <textarea name="alamat" class="form-input" rows="2" placeholder="Alamat lengkap cabang/toko...">{{ auth()->user()->cabang?->alamat ?? '' }}</textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Nomor WhatsApp</label>
                <input type="text" name="no_wa" class="form-input" value="{{ auth()->user()->phone }}" placeholder="08xxxxxxxxxx">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-input" value="{{ auth()->user()->email }}" placeholder="email@contoh.com">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Paket / Langganan</label>
                <select name="paket" id="reqPaket" class="form-input">
                    <option value="standar">Standard (1 cabang)</option>
                    <option value="enterprise">Enterprise (pusat + cabang anak)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Masa Berlaku Aktivasi</label>
                <select name="durasi" id="reqDurasi" class="form-input">
                    <option value="standard_1_tahun" selected>Standard — 1 Tahun</option>
                    <option value="enterprise_1_tahun">Enterprise — 1 Tahun</option>
                </select>
                <div class="text-xs text-muted" style="margin-top:4px">Tidak ada opsi permanen — masa berlaku mengikuti paket yang didaftarkan (Standard / Enterprise, masing-masing 1 tahun).</div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Jumlah User</label>
                <input type="number" name="jumlah_user" class="form-input" value="1" min="1" max="100">
            </div>
            <div class="form-group">
                <label>Jumlah Perangkat (opsional)</label>
                <input type="number" name="jumlah_perangkat" class="form-input" value="" min="1" max="100" placeholder="Kosongkan jika tidak perlu">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Nominal Transfer (Rp)</label>
                <input type="number" name="nominal_bayar" class="form-input" placeholder="0" min="0" step="1000">
            </div>
            <div class="form-group">
                <label>Upload Bukti Transfer *</label>
                <input type="file" name="bukti_transfer" class="form-input" accept="image/*" required>
                <div class="text-xs text-muted" style="margin-top:4px">JPG/PNG, max 2MB</div>
            </div>
        </div>
        <div class="form-group">
            <label>Catatan (Opsional)</label>
            <textarea name="catatan" class="form-input" rows="3" placeholder="Catatan tambahan jika ada..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Kirim request aktivasi? Pastikan bukti transfer sudah benar.')">
            <i class="fas fa-paper-plane"></i> Kirim Request Aktivasi
        </button>
    </form>
</div>
@else
<div class="card mb-4" style="background:#fef3c7;border:1px solid #fcd34d">
    <div style="display:flex;align-items:center;gap:10px">
        <i class="fas fa-clock" style="font-size:1.5rem;color:#92400e"></i>
        <div>
            <h3 style="margin:0;color:#92400e;font-size:.95rem">Request Sedang Diproses</h3>
            <p style="margin:4px 0 0;color:#92400e;font-size:.82rem">Anda sudah memiliki request yang sedang menunggu konfirmasi dari Admin Pusat. Harap tunggu.</p>
        </div>
    </div>
</div>
@endif

{{-- RIWAYAT REQUEST --}}
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history" style="color:var(--accent);margin-right:6px"></i> Riwayat Request</h3>
    </div>
    @if($myRequests->count() > 0)
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Toko</th>
                    <th>Durasi</th>
                    <th>Nominal</th>
                    <th>Bukti</th>
                    <th>Status</th>
                    <th>Catatan Admin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($myRequests as $req)
                <tr>
                    <td>{{ $req->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $req->nama_toko ?? '-' }}</td>
                    <td>{{ $req->durasiLabel() }}</td>
                    <td>{{ $req->nominal_bayar ? formatRp($req->nominal_bayar) : '-' }}</td>
                    <td>
                        @if($req->bukti_transfer)
                        <a href="{{ Storage::url($req->bukti_transfer) }}" target="_blank" class="btn btn-xs btn-secondary"><i class="fas fa-image"></i> Lihat</a>
                        @else
                        -
                        @endif
                    </td>
                    <td>
                        @if($req->status === 'pending')
                        <span class="badge badge-proses"><i class="fas fa-clock"></i> Pending</span>
                        @elseif($req->status === 'approved')
                        <span class="badge badge-selesai"><i class="fas fa-check-circle"></i> Disetujui</span>
                        @else
                        <span class="badge badge-pending"><i class="fas fa-times-circle"></i> Ditolak</span>
                        @endif
                    </td>
                    <td>{{ $req->admin_note ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div style="text-align:center;padding:30px;color:#94a3b8">
        <div style="font-size:1.5rem;margin-bottom:8px">📋</div>
        <p>Belum ada riwayat request aktivasi.</p>
    </div>
    @endif
</div>

@endif

<script>
    // Sinkronkan paket & masa berlaku: Standard — 1 Tahun ↔ Enterprise — 1 Tahun
    document.addEventListener('DOMContentLoaded', function () {
        var durasi = document.getElementById('reqDurasi');
        var paket = document.getElementById('reqPaket');
        if (durasi && paket) {
            durasi.addEventListener('change', function () {
                paket.value = this.value.indexOf('enterprise') === 0 ? 'enterprise' : 'standar';
            });
            paket.addEventListener('change', function () {
                durasi.value = this.value === 'enterprise' ? 'enterprise_1_tahun' : 'standard_1_tahun';
            });
        }
    });
</script>
@endsection
