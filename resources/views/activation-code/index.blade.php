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
                <label>Durasi Masa Aktif</label>
                <select name="durasi" class="form-input" required>
                    <option value="1_bulan">1 Bulan</option>
                    <option value="3_bulan">3 Bulan</option>
                    <option value="6_bulan">6 Bulan</option>
                    <option value="1_tahun" selected>1 Tahun</option>
                    <option value="permanen">Permanen</option>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label>Jumlah Kode</label>
                <input type="number" name="jumlah" class="form-input" value="1" min="1" max="100" required>
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
                    <th>Durasi</th>
                    <th>Status</th>
                    <th>Digunakan Oleh</th>
                    <th>Dibuat Pada</th>
                    <th>Digunakan Pada</th>
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
                    <td>{{ $c->durasiLabel() }}</td>
                    <td>
                        @if($c->is_used)
                        <span class="badge badge-selesai"><i class="fas fa-check"></i> Terpakai</span>
                        @else
                        <span class="badge badge-proses"><i class="fas fa-clock"></i> Tersedia</span>
                        @endif
                    </td>
                    <td>{{ $c->usedBy?->name ?? '-' }}<br><small style="color:#94a3b8">{{ $c->usedBy?->email ?? '' }}</small></td>
                    <td>{{ $c->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $c->used_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>
                        @if(!$c->is_used)
                        <form method="POST" action="{{ route('activation-code.destroy', $c) }}" style="display:inline" onsubmit="return confirm('Hapus kode ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </form>
                        @else
                        <span style="color:#94a3b8;font-size:.72rem">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:30px;color:#94a3b8">
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
