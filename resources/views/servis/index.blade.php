@extends('layouts.app')
@section('title', 'Daftar Servis')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem">Daftar Semua Servis</h2>
    <a href="{{ route('servis.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Input Servis Baru</a>
</div>

{{-- Toolbar Hapus Massal (Super Admin only) --}}
@if(auth()->user()->isSuperAdmin())
<form id="bulkDeleteForm" method="POST" action="{{ route('servis.bulk-destroy') }}" style="display:none;margin-bottom:12px">
    @csrf
    <input type="hidden" name="ids" id="bulkIdsInput">
    <div class="card" style="padding:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:#fef2f2;border-color:#fecaca">
        <span style="font-size:.82rem;color:#991b1b;font-weight:700"><i class="fas fa-trash"></i> <span id="bulkSelectedCount">0</span> item dipilih</span>
        <button type="button" onclick="clearBulkSelection()" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Batal</button>
        <button type="button" onclick="confirmBulkDelete()" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus yang Dipilih</button>
        <span style="font-size:.72rem;color:#92400e;margin-left:auto">Stok sparepart akan dikembalikan otomatis</span>
    </div>
</form>
@endif

<form method="GET" class="card mb-4">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
        <div style="flex:1;min-width:200px">
            <label class="text-xs font-bold text-muted">Cari</label>
            <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="Cari kode, nama, IMEI...">
        </div>
        <div style="width:140px">
            <label class="text-xs font-bold text-muted">Status</label>
            <select name="status" class="form-input">
                <option value="">Semua Status</option>
                @foreach(['Masuk','Proses','Pending','Selesai','Dibatalkan'] as $st)
                <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div style="width:140px">
            <label class="text-xs font-bold text-muted">Sumber</label>
            <select name="sumber" class="form-input">
                <option value="">Semua</option>
                <option value="user" {{ request('sumber') == 'user' ? 'selected' : '' }}>📱 Dari User</option>
                <option value="admin" {{ request('sumber') == 'admin' ? 'selected' : '' }}>🖥️ Input Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
        <a href="{{ route('servis.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Reset</a>
    </div>
</form>

{{-- Antrian dari User --}}
@php
    $antrianCount = $servis->where('sumber', 'user')->whereIn('status', ['Masuk'])->count();
@endphp
@if($antrianCount > 0)
<div class="alert alert-warning mb-4" style="display:flex;align-items:center;gap:10px">
    <i class="fas fa-bell" style="font-size:1.1rem"></i>
    <div>
        <strong>{{ $antrianCount }} antrian baru dari user!</strong>
        <span style="font-size:.78rem;color:#92400e;margin-left:4px">Servis yang didaftarkan user online dan menunggu diproses.</span>
    </div>
</div>
@endif

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    @if(auth()->user()->isSuperAdmin())<th style="width:36px"><input type="checkbox" id="selectAllServis" onclick="toggleSelectAll(this)" title="Pilih semua"></th>@endif
                    <th>Kode</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Perangkat</th>
                    <th>Cabang</th>
                    <th>Sumber</th>
                    <th>Teknisi</th>
                    <th>Status</th>
                    <th>Biaya</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($servis as $s)
                <tr style="{{ $s->sumber === 'user' && $s->status === 'Masuk' ? 'background:#fffbeb' : '' }}">
                    @if(auth()->user()->isSuperAdmin())<td><input type="checkbox" class="bulk-check-servis" value="{{ $s->id }}" onchange="updateBulkBar()"></td>@endif
                    <td>
                        <strong style="color:var(--primary)">{{ $s->kode }}</strong>
                        @if($s->sumber === 'user' && $s->status === 'Masuk')
                        <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.6rem;margin-left:2px">ANTRIAN</span>
                        @endif
                    </td>
                    <td>{{ $s->tanggal?->format('d/m/Y') }}</td>
                    <td>
                        <div>{{ $s->pelanggan?->nama ?? '-' }}</div>
                        <div class="text-xs text-muted">{{ $s->pelanggan?->no_hp ?? '' }}</div>
                    </td>
                    <td>
                        <div>{{ $s->perangkat }}</div>
                        <div class="text-xs text-muted">{{ $s->tipe }}</div>
                    </td>
                    <td><span class="badge badge-masuk">{{ $s->cabang?->nama ?? '-' }}</span></td>
                    <td>
                        @if($s->sumber === 'user')
                        <span style="font-size:.76rem;color:#2563eb"><i class="fas fa-mobile-alt"></i> User</span>
                        @else
                        <span style="font-size:.76rem;color:#64748b"><i class="fas fa-desktop"></i> Admin</span>
                        @endif
                    </td>
                    <td>{{ $s->teknisi?->nama ?? '-' }}</td>
                    <td>
                        <span class="badge badge-{{ strtolower($s->status) }}">{{ $s->status }}</span>
                        @if($s->status === 'Selesai' && $s->diambil)
                            <span class="badge" style="background:#dcfce7;color:#166534;font-size:.62rem;margin-left:2px">✓ Diambil</span>
                        @elseif($s->status === 'Selesai' && !$s->diambil)
                            <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.62rem;margin-left:2px">⏳ Belum Diambil</span>
                        @endif
                    </td>
                    {{-- Biaya servis = harga KESELURUHAN (sudah termasuk sparepart), lihat laporan keuangan --}}
                    <td style="font-weight:700;color:#16a34a">{{ formatRp($s->biaya) }}</td>
                    <td style="white-space:nowrap">
                        <button onclick="openDetailModal({{ $s->id }})" class="btn btn-secondary btn-xs" title="Detail"><i class="fas fa-eye"></i></button>
                        <a href="{{ route('print.servis', $s) }}" class="btn btn-secondary btn-xs" target="_blank" title="Print Thermal"><i class="fas fa-print"></i></a>
                        @if($s->status !== 'Dibatalkan' && (auth()->user()->isAdmin() || auth()->user()->isStaff()))
                        <button onclick="openQuickStatusModal({{ $s->id }}, '{{ $s->kode }}', '{{ $s->status }}')" class="btn btn-xs" style="background:#f3e8ff;color:#7c3aed" title="Ubah Status"><i class="fas fa-exchange-alt"></i></button>
                        @endif
                        @if($s->status !== 'Dibatalkan')
                        <a href="{{ route('servis.edit', $s) }}" class="btn btn-primary btn-xs" title="Edit / Proses"><i class="fas fa-edit"></i></a>
                        @endif
                        @if($s->status === 'Selesai')
                        <button onclick="openKirimNotaModal({{ $s->id }}, '{{ $s->kode }}')" class="btn btn-xs" style="background:#25D366;color:#fff" title="Kirim Nota ke WhatsApp"><i class="fab fa-whatsapp"></i></button>
                        @endif
                        @if($s->status === 'Selesai' && !$s->diambil)
                        <button onclick="openDiambilModal({{ $s->id }}, '{{ $s->kode }}')" class="btn btn-xs" style="background:#059669;color:#fff" title="Konfirmasi Diambil"><i class="fas fa-check-circle"></i></button>
                        @endif
                        @if($s->status !== 'Dibatalkan' && (auth()->user()->isAdmin() || auth()->user()->isStaff() || auth()->user()->isSuperAdmin()))
                        <button onclick="openBatalServisModal({{ $s->id }}, '{{ $s->kode }}')" class="btn btn-xs" style="background:#dc2626;color:#fff" title="Batalkan Transaksi"><i class="fas fa-ban"></i></button>
                        @endif
                        @if(auth()->user()->isSuperAdmin())
                        <form method="POST" action="{{ route('servis.destroy', $s) }}" style="display:inline" onsubmit="return confirm('Hapus data servis {{ $s->kode }}?\n\nData sparepart akan dikembalikan ke stok secara otomatis.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs" style="background:#7f1d1d;color:#fff;border:1px solid #991b1b" title="Hapus Data"><i class="fas fa-trash"></i></button>
                        </form>
                        @endif
                        @if($s->status === 'Dibatalkan')
                        <span class="badge" style="background:#fef2f2;color:#dc2626;font-size:.68rem"><i class="fas fa-ban"></i> Dibatalkan</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;display:flex;justify-content:center">
        {{ $servis->withQueryString()->links() }}
    </div>
</div>

{{-- ==================== MODAL DETAIL SERVIS ==================== --}}
<div id="detailModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center">
    <!-- Overlay -->
    <div onclick="closeDetailModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px)"></div>
    <!-- Modal Box -->
    <div style="position:relative;background:#fff;border-radius:16px;max-width:520px;width:92%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,.25);animation:modalIn .25s ease">
        <!-- Header -->
        <div style="padding:20px 24px 0;display:flex;justify-content:space-between;align-items:flex-start">
            <div>
                <div style="font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.5px">Detail Servis</div>
                <div id="modalKode" style="font-size:1.3rem;font-weight:800;color:var(--primary);margin-top:2px">-</div>
            </div>
            <button onclick="closeDetailModal()" style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:1rem;color:#64748b;display:flex;align-items:center;justify-content:center" title="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Status Badge -->
        <div style="padding:12px 24px 0;display:flex;gap:8px;flex-wrap:wrap">
            <span id="modalStatusBadge" class="badge badge-masuk" style="font-size:.78rem;padding:4px 14px">-</span>
            <span id="modalPrioritasBadge" class="badge badge-normal" style="font-size:.78rem;padding:4px 14px">-</span>
            <span id="modalSumberBadge" class="badge" style="font-size:.72rem;padding:3px 10px;background:#f1f5f9;color:#475569">-</span>
        </div>

        <!-- Content Sections -->
        <div style="padding:16px 24px">
            <!-- Info Pelanggan -->
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;margin-bottom:12px">
                <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px"><i class="fas fa-user" style="margin-right:4px;color:var(--info)"></i> Pelanggan</div>
                <table style="width:100%">
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b;width:90px">Nama</td><td id="modalNama" style="padding:3px 0;font-size:.82rem;font-weight:600">-</td></tr>
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b">No. HP</td><td id="modalHP" style="padding:3px 0;font-size:.82rem;font-weight:600">-</td></tr>
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b">Alamat</td><td id="modalAlamat" style="padding:3px 0;font-size:.82rem">-</td></tr>
                </table>
            </div>

            <!-- Info Perangkat -->
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;margin-bottom:12px">
                <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px"><i class="fas fa-mobile-alt" style="margin-right:4px;color:var(--accent)"></i> Perangkat</div>
                <table style="width:100%">
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b;width:90px">Perangkat</td><td id="modalPerangkat" style="padding:3px 0;font-size:.82rem;font-weight:600">-</td></tr>
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b">Tipe</td><td id="modalTipe" style="padding:3px 0;font-size:.82rem">-</td></tr>
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b">IMEI</td><td id="modalIMEI" style="padding:3px 0;font-size:.82rem">-</td></tr>
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b">Keluhan</td><td id="modalKeluhan" style="padding:3px 0;font-size:.82rem">-</td></tr>
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b">Teknisi</td><td id="modalTeknisi" style="padding:3px 0;font-size:.82rem;font-weight:600">-</td></tr>
                </table>
            </div>

            <!-- Info Biaya -->
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;margin-bottom:12px">
                <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px"><i class="fas fa-money-bill-wave" style="margin-right:4px;color:var(--success)"></i> Biaya</div>
                <table style="width:100%">
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b;width:110px">Total Biaya Servis</td><td id="modalBiaya" style="padding:3px 0;font-size:.82rem;font-weight:700">-</td></tr>
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b">DP</td><td id="modalDP" style="padding:3px 0;font-size:.82rem">-</td></tr>
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b;font-weight:600">Sisa</td><td id="modalSisa" style="padding:3px 0;font-size:.9rem;font-weight:800;color:var(--danger)">-</td></tr>
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b">Garansi</td><td id="modalGaransi" style="padding:3px 0;font-size:.82rem">-</td></tr>
                </table>
            </div>

            <!-- Info Lain -->
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;margin-bottom:12px">
                <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px"><i class="fas fa-info-circle" style="margin-right:4px;color:var(--primary)"></i> Lainnya</div>
                <table style="width:100%">
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b;width:90px">Tanggal</td><td id="modalTanggal" style="padding:3px 0;font-size:.82rem">-</td></tr>
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#64748b">Cabang</td><td id="modalCabang" style="padding:3px 0;font-size:.82rem">-</td></tr>
                    <tr id="modalCatatanRow" style="display:none"><td style="padding:3px 0;font-size:.82rem;color:#64748b">Catatan</td><td id="modalCatatan" style="padding:3px 0;font-size:.82rem">-</td></tr>
                </table>
            </div>

            <!-- Sparepart -->
            <div id="modalSparepartSection" style="background:#f8fafc;border-radius:10px;padding:14px 16px;margin-bottom:12px;display:none">
                <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px"><i class="fas fa-puzzle-piece" style="margin-right:4px;color:var(--accent)"></i> Sparepart</div>
                <table style="width:100%">
                    <thead><tr><th style="font-size:.7rem;padding:3px 0">Nama</th><th style="font-size:.7rem;padding:3px 0">Harga</th></tr></thead>
                    <tbody id="modalSparepartBody"></tbody>
                </table>
            </div>

            <!-- Info Pembatalan (hidden by default) -->
            <div id="modalPembatalanSection" style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px 16px;margin-bottom:12px;display:none">
                <div style="font-size:.72rem;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px"><i class="fas fa-ban" style="margin-right:4px"></i> Transaksi Dibatalkan</div>
                <table style="width:100%">
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#991b1b;width:90px">Alasan</td><td id="modalAlasanBatal" style="padding:3px 0;font-size:.82rem;font-weight:600;color:#991b1b">-</td></tr>
                    <tr><td style="padding:3px 0;font-size:.82rem;color:#991b1b">Dibatalkan</td><td id="modalTglBatal" style="padding:3px 0;font-size:.82rem;color:#991b1b">-</td></tr>
                </table>
            </div>

            <!-- Info Diambil (hidden by default) -->
            <div id="modalDiambilSection" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 16px;margin-bottom:12px;display:none">
                <div style="font-size:.72rem;font-weight:700;color:#166534;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px"><i class="fas fa-check-circle" style="margin-right:4px"></i> HP Sudah Diambil</div>
                <div id="modalTglDiambil" style="font-size:.82rem;color:#166534">-</div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="padding:0 24px 20px;display:flex;gap:10px;flex-wrap:wrap">
            <a id="modalBtnWA" href="#" target="_blank" style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 16px;border-radius:10px;font-size:.88rem;font-weight:700;text-decoration:none;background:#25D366;color:#fff;transition:all .2s;min-width:200px">
                <i class="fab fa-whatsapp" style="font-size:1.2rem"></i> Hubungi via WhatsApp
            </a>
            <a id="modalBtnEdit" href="#" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:12px 16px;border-radius:10px;font-size:.84rem;font-weight:600;text-decoration:none;background:var(--primary);color:#fff;transition:all .2s">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>
</div>

<style>
@keyframes modalIn {
    from { opacity: 0; transform: scale(.92) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
/* Badge Dibatalkan */
.badge-dibatalkan { background: #fef2f2 !important; color: #dc2626 !important; }
/* Dark mode for modal */
body.dark #detailModal > div:last-child { background: #1e293b; }
body.dark #detailModal .modal-close-btn { background: #334155; color: #e2e8f0; }
body.dark #detailModal div[style*="background:#f8fafc"] { background: #0f172a !important; }
body.dark #detailModal div[style*="color:#64748b"],
body.dark #detailModal td[style*="color:#64748b"] { color: #94a3b8 !important; }
body.dark #detailModal td { color: #e2e8f0; }
body.dark #detailModal #modalKode { color: #2dd4bf; }
body.dark #detailModal div[style*="color:#94a3b8"] { color: #64748b !important; }
</style>

<script>
let currentServisData = null;

function openDetailModal(servisId) {
    const modal = document.getElementById('detailModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Show loading state
    document.getElementById('modalKode').textContent = 'Memuat...';

    fetch('{{ url("/servis") }}/' + servisId + '/detail-json')
        .then(r => r.json())
        .then(data => {
            currentServisData = data;
            // Header
            document.getElementById('modalKode').textContent = data.kode;

            // Status badges
            const statusBadge = document.getElementById('modalStatusBadge');
            statusBadge.textContent = data.status;
            statusBadge.className = 'badge badge-' + data.status.toLowerCase();
            statusBadge.style.fontSize = '.78rem';
            statusBadge.style.padding = '4px 14px';

            const prioBadge = document.getElementById('modalPrioritasBadge');
            prioBadge.textContent = data.prioritas;
            prioBadge.className = 'badge badge-' + data.prioritas.toLowerCase();
            prioBadge.style.fontSize = '.78rem';
            prioBadge.style.padding = '4px 14px';

            const sumberBadge = document.getElementById('modalSumberBadge');
            if (data.sumber === 'user') {
                sumberBadge.innerHTML = '<i class="fas fa-mobile-alt"></i> Input User';
                sumberBadge.style.background = '#dbeafe';
                sumberBadge.style.color = '#1e40af';
            } else {
                sumberBadge.innerHTML = '<i class="fas fa-desktop"></i> Input Admin';
                sumberBadge.style.background = '#f1f5f9';
                sumberBadge.style.color = '#475569';
            }

            // Pelanggan
            document.getElementById('modalNama').textContent = data.pelanggan_nama;
            document.getElementById('modalHP').textContent = data.pelanggan_hp;
            document.getElementById('modalAlamat').textContent = data.pelanggan_alamat;

            // Perangkat
            document.getElementById('modalPerangkat').textContent = data.perangkat;
            document.getElementById('modalTipe').textContent = data.tipe;
            document.getElementById('modalIMEI').textContent = data.imei;
            document.getElementById('modalKeluhan').textContent = data.keluhan;
            document.getElementById('modalTeknisi').textContent = data.teknisi;

            // Biaya
            document.getElementById('modalBiaya').textContent = data.biaya_formatted;
            document.getElementById('modalDP').textContent = data.dp_formatted;
            document.getElementById('modalSisa').textContent = data.sisa_formatted;
            document.getElementById('modalGaransi').textContent = data.garansi + ' hari (s/d ' + data.tanggal_garansi + ')';

            // Lainnya
            document.getElementById('modalTanggal').textContent = data.tanggal;
            document.getElementById('modalCabang').textContent = data.cabang;

            // Catatan
            if (data.catatan && data.catatan.trim() !== '') {
                document.getElementById('modalCatatanRow').style.display = '';
                document.getElementById('modalCatatan').textContent = data.catatan;
            } else {
                document.getElementById('modalCatatanRow').style.display = 'none';
            }

            // Spareparts
            let sparepartSection = document.getElementById('modalSparepartSection');
            if (data.spareparts && data.spareparts.length > 0) {
                sparepartSection.style.display = '';
                let html = '';
                data.spareparts.forEach(function(sp) {
                    html += '<tr><td style="padding:3px 0;font-size:.82rem">' + (sp.nama || '-') + '</td><td style="padding:3px 0;font-size:.82rem;font-weight:600">Rp ' + Number(sp.harga || 0).toLocaleString('id-ID') + '</td></tr>';
                });
                document.getElementById('modalSparepartBody').innerHTML = html;
            } else {
                sparepartSection.style.display = 'none';
            }

            // Pembatalan info
            let pembatalanSection = document.getElementById('modalPembatalanSection');
            if (data.status === 'Dibatalkan' && data.alasan_pembatalan) {
                pembatalanSection.style.display = '';
                document.getElementById('modalAlasanBatal').textContent = data.alasan_pembatalan;
                document.getElementById('modalTglBatal').textContent = data.dibatalkan_pada || '-';
            } else {
                pembatalanSection.style.display = 'none';
            }

            // Diambil info
            let diambilSection = document.getElementById('modalDiambilSection');
            if (data.diambil) {
                diambilSection.style.display = '';
                document.getElementById('modalTglDiambil').textContent = 'Diambil pada: ' + (data.tgl_diambil || '-');
            } else {
                diambilSection.style.display = 'none';
            }

            // Edit button
            document.getElementById('modalBtnEdit').href = '{{ url("/servis") }}/' + servisId + '/edit';

            // WhatsApp button
            setupWhatsApp(data);
        })
        .catch(err => {
            document.getElementById('modalKode').textContent = 'Gagal memuat data';
            console.error(err);
        });
}

function setupWhatsApp(data) {
    const btnWA = document.getElementById('modalBtnWA');
    let phone = (data.pelanggan_hp || '').replace(/[^0-9]/g, '');
    
    // Format nomor HP Indonesia
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    } else if (phone.startsWith('+62')) {
        phone = phone.substring(1);
    } else if (!phone.startsWith('62')) {
        phone = '62' + phone;
    }

    // Pesan berdasarkan status
    let message = '';
    if (data.status === 'Selesai') {
        message = `Halo Kak ${data.pelanggan_nama},\n\nServis HP Anda sudah *SELESAI*! ✅\n\n` +
            `📱 *Detail Servis:*\n` +
            `• Kode: ${data.kode}\n` +
            `• Perangkat: ${data.perangkat} (${data.tipe})\n` +
            `• Keluhan: ${data.keluhan}\n\n` +
            `💰 *Biaya:*\n` +
            `• Total: ${data.biaya_formatted}\n` +
            `• DP: ${data.dp_formatted}\n` +
            `• Sisa: ${data.sisa_formatted}\n\n` +
            `Silakan ambil HP Anda di cabang *${data.cabang}*. Terima kasih! 🙏`;
    } else {
        message = `Halo Kak ${data.pelanggan_nama},\n\nBerikut update servis HP Anda:\n\n` +
            `📱 *Detail Servis:*\n` +
            `• Kode: ${data.kode}\n` +
            `• Perangkat: ${data.perangkat} (${data.tipe})\n` +
            `• Keluhan: ${data.keluhan}\n` +
            `• Status: *${data.status}*\n` +
            `• Teknisi: ${data.teknisi}\n\n` +
            `💰 *Biaya:*\n` +
            `• Total: ${data.biaya_formatted}\n` +
            `• DP: ${data.dp_formatted}\n` +
            `• Sisa: ${data.sisa_formatted}\n\n` +
            `Terima kasih sudah mempercayakan servis HP Anda kepada kami! 🙏`;
    }

    btnWA.href = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(message);

    // Warna berbeda kalau selesai
    if (data.status === 'Selesai') {
        btnWA.innerHTML = '<i class="fab fa-whatsapp" style="font-size:1.2rem"></i> 🎉 Ingatkan Selesai via WA';
        btnWA.style.background = '#25D366';
    } else {
        btnWA.innerHTML = '<i class="fab fa-whatsapp" style="font-size:1.2rem"></i> Hubungi via WhatsApp';
        btnWA.style.background = '#25D366';
    }
}

function closeDetailModal() {
    const modal = document.getElementById('detailModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    currentServisData = null;
}

{{-- ===== Konfirmasi Diambil ===== --}}
let diambilServisId = null;

function openDiambilModal(id, kode) {
    diambilServisId = id;
    document.getElementById('diambilKode').textContent = kode;
    document.getElementById('diambilModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeDiambilModal() {
    document.getElementById('diambilModal').style.display = 'none';
    document.body.style.overflow = '';
    diambilServisId = null;
}

function submitDiambil() {
    if (!diambilServisId) return;
    const btn = document.getElementById('btnSubmitDiambil');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

    fetch('{{ url("/servis") }}/' + diambilServisId + '/diambil', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        accept: 'application/json'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            closeDiambilModal();
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(err => {
        showToast('error', 'Terjadi kesalahan. Coba lagi.');
        console.error(err);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Ya, Sudah Diambil';
    });
}

{{-- ===== Batalkan Transaksi Servis ===== --}}
let batalServisId = null;

function openBatalServisModal(id, kode) {
    batalServisId = id;
    document.getElementById('batalServisKode').textContent = kode;
    document.getElementById('batalServisAlasan').value = '';
    document.getElementById('batalServisModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeBatalServisModal() {
    document.getElementById('batalServisModal').style.display = 'none';
    document.body.style.overflow = '';
    batalServisId = null;
}

function submitBatalServis() {
    if (!batalServisId) return;
    const alasan = document.getElementById('batalServisAlasan').value.trim();
    if (!alasan || alasan.length < 3) {
        showToast('error', 'Alasan pembatalan wajib diisi (minimal 3 karakter).');
        document.getElementById('batalServisAlasan').focus();
        return;
    }
    const btn = document.getElementById('btnSubmitBatalServis');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membatalkan...';

    fetch('{{ url("/servis") }}/' + batalServisId + '/batal', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ alasan: alasan })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            closeBatalServisModal();
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(err => {
        showToast('error', 'Terjadi kesalahan. Coba lagi.');
        console.error(err);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-ban"></i> Batalkan Transaksi';
    });
}

{{-- ===== Quick Status Change ===== --}}
let quickStatusId = null;

function openQuickStatusModal(id, kode, currentStatus) {
    quickStatusId = id;
    document.getElementById('qsKode').textContent = kode;
    document.getElementById('qsCurrentStatus').textContent = currentStatus;
    document.getElementById('qsCurrentBadge').textContent = currentStatus;
    document.getElementById('qsCurrentBadge').className = 'badge badge-' + currentStatus.toLowerCase();

    // Disable current status in dropdown
    const select = document.getElementById('qsNewStatus');
    select.value = 'Masuk';
    Array.from(select.options).forEach(opt => {
        opt.disabled = (opt.value === currentStatus);
    });

    document.getElementById('quickStatusModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeQuickStatusModal() {
    document.getElementById('quickStatusModal').style.display = 'none';
    document.body.style.overflow = '';
    quickStatusId = null;
}

function submitQuickStatus() {
    if (!quickStatusId) return;
    const newStatus = document.getElementById('qsNewStatus').value;
    if (!newStatus) return;

    const btn = document.getElementById('btnSubmitQuickStatus');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengubah...';

    fetch('{{ url("/servis") }}/' + quickStatusId + '/quick-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ status: newStatus })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            closeQuickStatusModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(err => {
        showToast('error', 'Terjadi kesalahan.');
        console.error(err);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-exchange-alt"></i> Ubah Status';
    });
}

{{-- ===== Toast Notification ===== --}}
function showToast(type, message) {
    const toast = document.getElementById('toastNotif');
    const icon = document.getElementById('toastIcon');
    const msg = document.getElementById('toastMessage');

    msg.textContent = message;
    if (type === 'success') {
        toast.style.background = '#dcfce7';
        toast.style.color = '#166534';
        toast.style.border = '1px solid #bbf7d0';
        icon.className = 'fas fa-check-circle';
    } else {
        toast.style.background = '#fef2f2';
        toast.style.color = '#991b1b';
        toast.style.border = '1px solid #fecaca';
        icon.className = 'fas fa-exclamation-circle';
    }

    toast.style.display = 'block';
    setTimeout(() => { toast.style.transform = 'translateX(0)'; }, 50);
    setTimeout(() => {
        toast.style.transform = 'translateX(120%)';
        setTimeout(() => { toast.style.display = 'none'; }, 300);
    }, 4000);
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailModal();
        closeDiambilModal();
        closeBatalServisModal();
        closeQuickStatusModal();
    }
});

// Quick status: show note when Selesai selected
document.getElementById('qsNewStatus')?.addEventListener('change', function() {
    const note = document.getElementById('qsNoteSelesai');
    note.style.display = this.value === 'Selesai' ? '' : 'none';
});

{{-- ===== BULK DELETE (checkbox) ===== --}}
function updateBulkBar() {
    const checked = document.querySelectorAll('.bulk-check-servis:checked');
    const bar = document.getElementById('bulkDeleteForm');
    const counter = document.getElementById('bulkSelectedCount');
    const selectAll = document.getElementById('selectAllServis');
    if (bar) bar.style.display = checked.length > 0 ? 'block' : 'none';
    if (counter) counter.textContent = checked.length;
    if (selectAll) selectAll.checked = checked.length > 0 && checked.length === document.querySelectorAll('.bulk-check-servis').length;
}
function toggleSelectAll(master) {
    document.querySelectorAll('.bulk-check-servis').forEach(cb => { cb.checked = master.checked; });
    updateBulkBar();
}
function clearBulkSelection() {
    document.querySelectorAll('.bulk-check-servis').forEach(cb => { cb.checked = false; });
    const selectAll = document.getElementById('selectAllServis');
    if (selectAll) selectAll.checked = false;
    updateBulkBar();
}
function confirmBulkDelete() {
    const checked = document.querySelectorAll('.bulk-check-servis:checked');
    if (checked.length === 0) { showToast('error', 'Pilih minimal satu item.'); return; }
    if (!confirm('Hapus ' + checked.length + ' data servis terpilih?\n\nStok sparepart akan dikembalikan secara otomatis. Tindakan ini tidak bisa dibatalkan.')) return;
    const ids = Array.from(checked).map(cb => cb.value);
    const form = document.getElementById('bulkDeleteForm');
    // hapus input hidden tunggal, ganti dengan multiple ids[]
    const hiddenInput = document.getElementById('bulkIdsInput');
    if (hiddenInput) hiddenInput.parentNode.removeChild(hiddenInput);
    form.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
    ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
        form.appendChild(inp);
    });
    form.submit();
}

{{-- ===== KIRIM NOTA WHATSAPP ===== --}}
let kirimNotaId = null;
let kirimNotaData = null;

function openKirimNotaModal(id, kode) {
    kirimNotaId = id;
    kirimNotaData = null;
    document.getElementById('kirimNotaKode').textContent = kode;
    document.getElementById('kirimNotaInfo').textContent = 'Memuat data...';
    document.getElementById('kirimNotaPesan').value = '';
    document.getElementById('kirimNotaFonnteNote').style.display = 'none';
    document.getElementById('btnKirimOtomatis').disabled = false;
    document.getElementById('btnKirimOtomatis').innerHTML = '<i class="fab fa-whatsapp" style="font-size:1.1rem"></i> Kirim Otomatis (Pesan + PDF Nota)';
    document.getElementById('linkWaManual').href = '#';
    document.getElementById('linkPdfNota').href = '#';
    document.getElementById('kirimNotaModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    fetch('{{ url("/servis") }}/' + id + '/preview-wa-nota')
        .then(r => r.json())
        .then(data => {
            kirimNotaData = data;
            document.getElementById('kirimNotaInfo').innerHTML =
                '<i class="fas fa-user" style="color:#0d9488;margin-right:4px"></i> <strong>' + (data.pelanggan || '-') + '</strong>' +
                ' &nbsp; <i class="fas fa-phone" style="color:#0d9488;margin-right:4px"></i> ' + (data.phone || '-');
            document.getElementById('kirimNotaPesan').value = data.message;
            document.getElementById('linkPdfNota').href = data.pdf_url;
            if (data.wa_url) document.getElementById('linkWaManual').href = data.wa_url;
            if (!data.fonnte_aktif) {
                document.getElementById('kirimNotaFonnteNote').style.display = '';
                document.getElementById('btnKirimOtomatis').style.opacity = '.55';
            } else {
                document.getElementById('btnKirimOtomatis').style.opacity = '1';
            }
        })
        .catch(() => {
            document.getElementById('kirimNotaInfo').textContent = 'Gagal memuat data.';
        });
}

function closeKirimNotaModal() {
    document.getElementById('kirimNotaModal').style.display = 'none';
    document.body.style.overflow = '';
    kirimNotaId = null;
    kirimNotaData = null;
}

function kirimOtomatisNota() {
    if (!kirimNotaId) return;
    const btn = document.getElementById('btnKirimOtomatis');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
    fetch('{{ url("/servis") }}/' + kirimNotaId + '/kirim-wa-nota', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            // update link PDF bila tersedia
            if (data.pdf_url) document.getElementById('linkPdfNota').href = data.pdf_url;
            setTimeout(closeKirimNotaModal, 1500);
        } else {
            showToast('error', data.message);
            if (data.manual_mode && data.pdf_url) {
                document.getElementById('linkPdfNota').href = data.pdf_url;
                document.getElementById('kirimNotaFonnteNote').style.display = '';
            }
        }
    })
    .catch(() => showToast('error', 'Koneksi gagal. Coba lagi.'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fab fa-whatsapp" style="font-size:1.1rem"></i> Kirim Otomatis (Pesan + PDF Nota)';
    });
}
</script>

{{-- ==================== MODAL KONFIRMASI DIAMBIL ==================== --}}
<div id="diambilModal" style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center">
    <div onclick="closeDiambilModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px)"></div>
    <div style="position:relative;background:#fff;border-radius:16px;max-width:420px;width:92%;box-shadow:0 25px 60px rgba(0,0,0,.25);animation:modalIn .25s ease">
        <div style="padding:20px 24px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f1f5f9">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:40px;height:40px;border-radius:10px;background:#dcfce7;display:flex;align-items:center;justify-content:center;font-size:1.1rem">📦</div>
                <div>
                    <div style="font-size:1rem;font-weight:700">Konfirmasi Diambil</div>
                    <div id="diambilKode" style="font-size:.78rem;color:#64748b">-</div>
                </div>
            </div>
            <button onclick="closeDiambilModal()" style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:1rem;color:#64748b;display:flex;align-items:center;justify-content:center">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div style="padding:20px 24px">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px;margin-bottom:16px;text-align:center">
                <div style="font-size:2rem;margin-bottom:6px">✅</div>
                <div style="font-size:.88rem;font-weight:600;color:#166534">HP sudah diambil oleh pelanggan?</div>
                <div style="font-size:.76rem;color:#15803d;margin-top:4px">Pastikan pelanggan sudah membayar sisa biaya (jika ada)</div>
            </div>
        </div>
        <div style="padding:0 24px 20px;display:flex;gap:10px">
            <button onclick="closeDiambilModal()" style="flex:1;padding:10px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-weight:600;cursor:pointer;font-size:.88rem">Batal</button>
            <button onclick="submitDiambil()" id="btnSubmitDiambil" style="flex:1;padding:10px;border-radius:10px;border:none;background:#059669;color:#fff;font-weight:700;cursor:pointer;font-size:.88rem;display:flex;align-items:center;justify-content:center;gap:6px">
                <i class="fas fa-check-circle"></i> Ya, Sudah Diambil
            </button>
        </div>
    </div>
</div>

{{-- ==================== MODAL BATALKAN TRANSAKSI SERVIS ==================== --}}
<div id="batalServisModal" style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center">
    <div onclick="closeBatalServisModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px)"></div>
    <div style="position:relative;background:#fff;border-radius:16px;max-width:440px;width:92%;box-shadow:0 25px 60px rgba(0,0,0,.25);animation:modalIn .25s ease">
        <div style="padding:20px 24px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f1f5f9">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:40px;height:40px;border-radius:10px;background:#fef2f2;display:flex;align-items:center;justify-content:center;font-size:1.1rem">🚫</div>
                <div>
                    <div style="font-size:1rem;font-weight:700;color:#dc2626">Batalkan Transaksi</div>
                    <div id="batalServisKode" style="font-size:.78rem;color:#64748b">-</div>
                </div>
            </div>
            <button onclick="closeBatalServisModal()" style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:1rem;color:#64748b;display:flex;align-items:center;justify-content:center">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div style="padding:20px 24px">
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px;margin-bottom:16px">
                <div style="font-size:.82rem;color:#991b1b;display:flex;align-items:flex-start;gap:8px">
                    <i class="fas fa-exclamation-triangle" style="margin-top:2px"></i>
                    <div>Anda akan membatalkan transaksi ini. <strong>Stok barang akan dikembalikan secara otomatis.</strong></div>
                </div>
            </div>
            <div>
                <label style="font-size:.82rem;font-weight:700;color:#374151;display:block;margin-bottom:6px">Alasan Pembatalan <span style="color:#dc2626">*</span></label>
                <textarea id="batalServisAlasan" rows="3" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.84rem;resize:vertical;transition:border .2s" placeholder="Masukkan alasan pembatalan..." onfocus="this.style.borderColor='#dc2626'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
            </div>
        </div>
        <div style="padding:0 24px 20px;display:flex;gap:10px">
            <button onclick="closeBatalServisModal()" style="flex:1;padding:10px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-weight:600;cursor:pointer;font-size:.88rem">Kembali</button>
            <button onclick="submitBatalServis()" id="btnSubmitBatalServis" style="flex:1;padding:10px;border-radius:10px;border:none;background:#dc2626;color:#fff;font-weight:700;cursor:pointer;font-size:.88rem;display:flex;align-items:center;justify-content:center;gap:6px">
                <i class="fas fa-ban"></i> Batalkan Transaksi
            </button>
        </div>
    </div>
</div>

{{-- ==================== MODAL QUICK STATUS ==================== --}}
<div id="quickStatusModal" style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center">
    <div onclick="closeQuickStatusModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px)"></div>
    <div style="position:relative;background:#fff;border-radius:16px;max-width:440px;width:92%;box-shadow:0 25px 60px rgba(0,0,0,.25);animation:modalIn .25s ease">
        <div style="padding:20px 24px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f1f5f9">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:40px;height:40px;border-radius:10px;background:#f3e8ff;display:flex;align-items:center;justify-content:center;font-size:1.1rem">🔄</div>
                <div>
                    <div style="font-size:1rem;font-weight:700">Ubah Status Servis</div>
                    <div id="qsKode" style="font-size:.78rem;color:#64748b">-</div>
                </div>
            </div>
            <button onclick="closeQuickStatusModal()" style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:1rem;color:#64748b;display:flex;align-items:center;justify-content:center">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div style="padding:20px 24px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                <span style="font-size:.82rem;color:#64748b;font-weight:600">Status saat ini:</span>
                <span id="qsCurrentBadge" class="badge badge-masuk" style="font-size:.82rem;padding:4px 14px">-</span>
            </div>
            <div>
                <label style="font-size:.82rem;font-weight:700;color:#374151;display:block;margin-bottom:6px">Ubah ke:</label>
                <select id="qsNewStatus" class="form-input" style="padding:10px 14px;font-size:.9rem">
                    <option value="Masuk">Masuk</option>
                    <option value="Proses">Proses</option>
                    <option value="Pending">Pending</option>
                    <option value="Selesai">Selesai</option>
                </select>
            </div>
            <div id="qsNoteSelesai" style="display:none;margin-top:10px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:.78rem;color:#166534">
                <i class="fas fa-info-circle" style="margin-right:4px"></i>
                Status Selesai → teknisi selesai memperbaiki. Setelah pelanggan mengambil HP, klik tombol <strong>"Konfirmasi Diambil"</strong> untuk mencatat pelunasan.
            </div>
        </div>
        <div style="padding:0 24px 20px;display:flex;gap:10px">
            <button onclick="closeQuickStatusModal()" style="flex:1;padding:10px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-weight:600;cursor:pointer;font-size:.88rem">Batal</button>
            <button onclick="submitQuickStatus()" id="btnSubmitQuickStatus" style="flex:1;padding:10px;border-radius:10px;border:none;background:#7c3aed;color:#fff;font-weight:700;cursor:pointer;font-size:.88rem;display:flex;align-items:center;justify-content:center;gap:6px">
                <i class="fas fa-exchange-alt"></i> Ubah Status
            </button>
        </div>
    </div>
</div>

{{-- ==================== TOAST NOTIFICATION ==================== --}}
<div id="toastNotif" style="display:none;position:fixed;top:24px;right:24px;z-index:10001;min-width:300px;padding:14px 20px;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.15);font-size:.88rem;font-weight:600;transition:all .3s ease;transform:translateX(120%)">
    <div style="display:flex;align-items:center;gap:10px">
        <i id="toastIcon"></i>
        <span id="toastMessage"></span>
    </div>
</div>

{{-- ==================== MODAL KIRIM NOTA WA ==================== --}}
<div id="kirimNotaModal" style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center">
    <div onclick="closeKirimNotaModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px)"></div>
    <div style="position:relative;background:#fff;border-radius:16px;max-width:560px;width:94%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,.25);animation:modalIn .25s ease">
        <div style="padding:18px 22px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f1f5f9;position:sticky;top:0;background:#fff;z-index:2;border-radius:16px 16px 0 0">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:40px;height:40px;border-radius:10px;background:#dcfce7;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#16a34a"><i class="fab fa-whatsapp"></i></div>
                <div>
                    <div style="font-size:1rem;font-weight:700">Kirim Nota ke WhatsApp</div>
                    <div id="kirimNotaKode" style="font-size:.78rem;color:#64748b">-</div>
                </div>
            </div>
            <button onclick="closeKirimNotaModal()" style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:1rem;color:#64748b;display:flex;align-items:center;justify-content:center"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:18px 22px">
            <div id="kirimNotaInfo" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;margin-bottom:14px;font-size:.8rem;color:#475569">
                Memuat data...
            </div>

            <label style="font-size:.8rem;font-weight:700;color:#374151;display:block;margin-bottom:6px">Pesan WhatsApp <span style="font-weight:400;color:#94a3b8">(boleh diedit sebelum kirim manual)</span></label>
            <textarea id="kirimNotaPesan" rows="8" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.78rem;resize:vertical;font-family:inherit;line-height:1.5" placeholder="Memuat pesan..."></textarea>

            <div id="kirimNotaFonnteNote" style="display:none;margin-top:10px;padding:10px 12px;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;font-size:.74rem;color:#92400e">
                <i class="fas fa-info-circle"></i> API WhatsApp (Fonnte) belum dikonfigurasi. Gunakan tombol <strong>Buka WhatsApp Manual</strong> di bawah, lalu lampirkan PDF yang sudah didownload.
            </div>
        </div>
        <div style="padding:0 22px 20px;display:flex;flex-direction:column;gap:8px">
            <button onclick="kirimOtomatisNota()" id="btnKirimOtomatis" style="width:100%;padding:12px;border-radius:10px;border:none;background:linear-gradient(135deg,#25D366,#16a34a);color:#fff;font-weight:700;cursor:pointer;font-size:.9rem;display:flex;align-items:center;justify-content:center;gap:8px">
                <i class="fab fa-whatsapp" style="font-size:1.1rem"></i> Kirim Otomatis (Pesan + PDF Nota)
            </button>
            <div style="display:flex;gap:8px">
                <a id="linkWaManual" href="#" target="_blank" style="flex:1;padding:11px;border-radius:10px;border:1.5px solid #bbf7d0;background:#f0fdf4;color:#166534;font-weight:600;cursor:pointer;font-size:.84rem;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none">
                    <i class="fab fa-whatsapp"></i> Buka WA Manual
                </a>
                <a id="linkPdfNota" href="#" target="_blank" style="flex:1;padding:11px;border-radius:10px;border:1.5px solid #cbd5e1;background:#f8fafc;color:#334155;font-weight:600;cursor:pointer;font-size:.84rem;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none">
                    <i class="fas fa-file-pdf" style="color:#dc2626"></i> Download PDF Nota
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
