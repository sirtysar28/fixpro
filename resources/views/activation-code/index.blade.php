@extends('layouts.app')
@section('title', 'Kode Aktivasi')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-ticket-alt" style="color:var(--primary);margin-right:6px"></i> Kode Aktivasi Login</h2>
</div>

<div class="card mb-4" style="background:#fffbeb;border:1px solid #fde68a">
    <p style="margin:0;font-size:.82rem;color:#92400e;line-height:1.6">
        <i class="fas fa-info-circle"></i>
        <strong>Cara kerja:</strong> Buat kode di sini → kirim kodenya ke user (via WhatsApp) yang masa aktifnya sudah habis.
        User masukin email + password + kode di halaman login → masa aktifnya otomatis diperpanjang sesuai durasi.
        Nomor WhatsApp untuk tombol "Minta Kode" diatur di <a href="{{ route('settings.index') }}" style="color:var(--primary)">Pengaturan</a>.
    </p>
</div>

{{-- Statistik --}}
<div class="grid-3 mb-4" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
    <div class="card" style="text-align:center;padding:16px">
        <div style="font-size:1.6rem;font-weight:800;color:var(--primary)">{{ $stats['total'] }}</div>
        <div style="font-size:.74rem;color:#64748b">Total Kode</div>
    </div>
    <div class="card" style="text-align:center;padding:16px">
        <div style="font-size:1.6rem;font-weight:800;color:#16a34a">{{ $stats['tersedia'] }}</div>
        <div style="font-size:.74rem;color:#64748b">Tersedia</div>
    </div>
    <div class="card" style="text-align:center;padding:16px">
        <div style="font-size:1.6rem;font-weight:800;color:#64748b">{{ $stats['terpakai'] }}</div>
        <div style="font-size:.74rem;color:#64748b">Terpakai</div>
    </div>
</div>

{{-- ===== FORM GENERATE ===== --}}
<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:6px"></i> Generate Kode Aktivasi</h3>
    </div>
    <form method="POST" action="{{ route('activation-code.generate') }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:flex-end">
            <div class="form-group" style="margin:0">
                <label>Masa Berlaku (mengikuti paket)</label>
                <select name="durasi" class="form-input" required>
                    <option value="register" selected>Sesuai yang di-Register (paket request cabang)</option>
                    <option value="standard_1_tahun">Standard — 1 Tahun</option>
                    <option value="enterprise_1_tahun">Enterprise — 1 Tahun</option>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label>Jumlah Kode</label>
                <input type="number" name="jumlah" class="form-input" value="1" min="1" max="100" required>
            </div>
            <div class="form-group" style="margin:0">
                <label>Cabang (opsional — kode terikat cabang)</label>
                <select name="cabang_id" class="form-input">
                    <option value="">Umum (tidak terikat cabang)</option>
                    @foreach(\App\Models\Cabang::orderBy('nama')->get() as $cb)
                    <option value="{{ $cb->id }}">{{ $cb->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label>Paket</label>
                <select name="paket" class="form-input">
                    <option value="standar">Standar</option>
                    <option value="enterprise">Enterprise</option>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label>Jumlah User</label>
                <input type="number" name="jumlah_user" class="form-input" value="1" min="1" max="100">
            </div>
            <div class="form-group" style="margin:0">
                <label>Catatan (opsional)</label>
                <input type="text" name="note" class="form-input" placeholder="Misal: untuk toko X" maxlength="200">
            </div>
            <button type="submit" class="btn btn-primary" style="height:42px"><i class="fas fa-magic"></i> Generate</button>
        </div>
    </form>

    {{-- Tampilkan kode yang baru digenerate --}}
    @php $generated = session('generated_codes'); @endphp
    @if(!empty($generated))
    <div style="margin-top:16px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px">
        <div style="font-size:.82rem;font-weight:700;color:#166534;margin-bottom:8px">
            <i class="fas fa-check-circle"></i> {{ count($generated) }} kode berhasil dibuat — salin & kirim ke user:
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px">
            @foreach($generated as $code)
            <div style="display:flex;align-items:center;gap:6px;background:#fff;border:1px solid #bbf7d0;border-radius:8px;padding:6px 10px">
                <code style="font-size:.88rem;font-weight:700;letter-spacing:1px;color:#0d9488">{{ $code }}</code>
                <button type="button" class="btn btn-xs" onclick="copyCode('{{ $code }}', this)" title="Salin" style="padding:2px 8px"><i class="fas fa-copy"></i></button>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- ===== DAFTAR KODE ===== --}}
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list" style="color:var(--primary);margin-right:6px"></i> Daftar Kode Aktivasi</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Cabang</th>
                    <th>Durasi</th>
                    <th>Paket</th>
                    <th>User</th>
                    <th>Status</th>
                    <th>Masa Berlaku</th>
                    <th>Digunakan Oleh</th>
                    <th>Dibuat Pada</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($codes as $c)
                <tr>
                    <td>
                        <code style="background:#f1f5f9;padding:3px 8px;border-radius:4px;font-weight:700;font-size:.82rem;letter-spacing:1px;color:#0d9488">{{ $c->code }}</code>
                        @if(!$c->is_used)
                        <button type="button" class="btn btn-xs" onclick="copyCode('{{ $c->code }}', this)" title="Salin kode" style="padding:2px 6px;margin-left:4px"><i class="fas fa-copy"></i></button>
                        @endif
                    </td>
                    <td>@if($c->cabang){{ $c->cabang->nama }}@else<span style="color:#94a3b8">Umum</span>@endif</td>
                    <td>{{ $c->durasiLabel() }}</td>
                    <td>{{ ucfirst($c->paket ?? 'standar') }}</td>
                    <td>{{ $c->jumlah_user ?? 1 }}</td>
                    <td>
                        @if($c->is_used)
                        <span class="badge badge-selesai"><i class="fas fa-check"></i> Terpakai</span>
                        @elseif($c->statusBerlakuLabel() === 'Aktif')
                        <span class="badge badge-proses"><i class="fas fa-unlock"></i> Aktif</span>
                        @elseif($c->statusBerlakuLabel() === 'Expired')
                        <span class="badge badge-dibatalkan"><i class="fas fa-clock"></i> Expired</span>
                        @else
                        <span class="badge badge-pending"><i class="fas fa-lock"></i> Nonaktif</span>
                        @endif
                    </td>
                    <td style="font-size:.72rem">
                        {{ $c->mulai_berlaku?->format('d/m/y') ?? '-' }} → {{ $c->berakhir_berlaku?->format('d/m/y') ?? 'Permanen' }}
                    </td>
                    <td>{{ $c->usedBy?->name ?? '-' }}<br><small style="color:#94a3b8">{{ $c->usedBy?->email ?? '' }}</small></td>
                    <td>{{ $c->created_at?->format('d/m/Y H:i') }}</td>
                    <td style="white-space:nowrap">
                        <form method="POST" action="{{ route('activation-code.toggle', $c) }}" style="display:inline"
                              onsubmit="return confirm('{{ $c->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }} kode {{ $c->code }}?')">
                            @csrf
                            <button class="btn btn-xs {{ $c->status === 'aktif' ? 'btn-danger' : 'btn-success' }}" title="{{ $c->status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}">
                                <i class="fas fa-{{ $c->status === 'aktif' ? 'lock' : 'unlock' }}"></i>
                            </button>
                        </form>
                        @if(!$c->is_used)
                        <form method="POST" action="{{ route('activation-code.destroy', $c) }}" style="display:inline" onsubmit="return confirm('Hapus kode ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:30px;color:#94a3b8">
                        Belum ada kode aktivasi. Generate kode di atas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;display:flex;justify-content:center">
        {{ $codes->withQueryString()->links() }}
    </div>
</div>

<script>
function copyCode(code, btn) {
    navigator.clipboard.writeText(code).then(function() {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(function() { btn.innerHTML = orig; }, 1200);
    });
}
</script>
@endsection
