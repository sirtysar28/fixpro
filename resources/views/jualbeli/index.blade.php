@extends('layouts.app')
@section('title', 'Jual Beli HP')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-mobile-alt" style="color:var(--primary);margin-right:6px"></i> Jual Beli HP Second</h2>
    <a href="{{ route('jualbeli.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Transaksi Baru</a>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-label">Jual HP Hari Ini</div>
        <div class="stat-value" style="color:var(--success);font-size:1.2rem">{{ formatRp($totalJual) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:var(--info)"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-label">Beli HP Hari Ini</div>
        <div class="stat-value" style="color:var(--info);font-size:1.2rem">{{ formatRp($totalBeli) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-receipt"></i></div>
        <div class="stat-label">Transaksi Hari Ini</div>
        <div class="stat-value" style="color:var(--primary);font-size:1.2rem">{{ $totalTransaksi }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--accent)"><i class="fas fa-warehouse"></i></div>
        <div class="stat-label">Stok Unit Siap Jual</div>
        <div class="stat-value" style="color:var(--accent);font-size:1.2rem">{{ $stokUnit }} unit</div>
    </div>
</div>

<!-- Filter -->
<form method="GET" class="card mb-4" style="padding:14px">
    <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:140px">
            <label class="text-xs font-bold text-muted">Cari</label>
            <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="HP / IMEI / Kode...">
        </div>
        <div style="min-width:100px">
            <label class="text-xs font-bold text-muted">Tipe</label>
            <select name="tipe" class="form-input">
                <option value="">Semua</option>
                <option value="jual" {{ request('tipe') == 'jual' ? 'selected' : '' }}>Jual</option>
                <option value="beli" {{ request('tipe') == 'beli' ? 'selected' : '' }}>Beli</option>
            </select>
        </div>
        <div style="min-width:110px">
            <label class="text-xs font-bold text-muted">Metode</label>
            <select name="metode" class="form-input">
                <option value="">Semua</option>
                <option value="Cash" {{ request('metode') == 'Cash' ? 'selected' : '' }}>Cash</option>
                <option value="Transfer" {{ request('metode') == 'Transfer' ? 'selected' : '' }}>Transfer</option>
                <option value="QRIS" {{ request('metode') == 'QRIS' ? 'selected' : '' }}>QRIS</option>
            </select>
        </div>
        <div style="min-width:130px">
            <label class="text-xs font-bold text-muted">Status Unit</label>
            <select name="status_unit" class="form-input">
                <option value="">Semua</option>
                @foreach(['Ready Dijual','Booking','Sedang Diservis','Terjual','Retur'] as $su)
                <option value="{{ $su }}" {{ request('status_unit') == $su ? 'selected' : '' }}>{{ $su }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:120px">
            <label class="text-xs font-bold text-muted">Tanggal</label>
            <input type="date" name="date" class="form-input" value="{{ request('date') }}">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        <a href="{{ route('jualbeli.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-redo"></i></a>
    </div>
</form>

<!-- Tabel -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3>Riwayat Transaksi Jual Beli</h3>
        <span style="font-size:.74rem;color:#94a3b8">Centang baris lalu klik tombol hapus</span>
    </div>
    {{-- Bulk delete toolbar --}}
    <form id="bulkDeleteFormJB" method="POST" action="{{ route('jualbeli.bulk-destroy') }}" style="display:none;padding:10px 16px;background:#fef2f2;border-bottom:1px solid #fecaca">
        @csrf
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <span style="font-size:.8rem;color:#991b1b;font-weight:700"><i class="fas fa-trash"></i> <span id="bulkSelectedCountJB">0</span> item dipilih</span>
            <button type="button" onclick="clearBulkJB()" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Batal</button>
            <button type="button" onclick="confirmBulkJB()" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus yang Dipilih</button>
        </div>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:36px"><input type="checkbox" id="selectAllJB" onclick="toggleAllJB(this)" title="Pilih semua"></th>
                    <th>Kode</th>
                    <th>Tanggal</th>
                    <th>HP / IMEI</th>
                    <th>Tipe</th>
                    <th>Status Unit</th>
                    <th>Harga</th>
                    <th>Est. Laba</th>
                    <th>Garansi</th>
                    <th>Pelanggan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i)
                <tr style="{{ $i->status === 'Dibatalkan' ? 'opacity:.5;background:#fef2f2' : '' }}">
                    <td><input type="checkbox" class="bulk-check-jb" value="{{ $i->id }}" onchange="updateBulkBarJB()"></td>
                    <td>
                        <strong style="color:var(--primary)">{{ $i->kode ?? '-' }}</strong>
                        @if($i->status === 'Dibatalkan')
                        <br><span class="badge" style="font-size:.6rem;background:#fef2f2;color:#dc2626"><i class="fas fa-ban"></i> Dibatalkan</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap">{{ $i->tanggal?->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $i->hp }}</strong>
                        @if($i->merk || $i->warna || $i->kapasitas)
                        <div class="text-xs" style="color:#64748b">{{ collect([$i->merk, $i->warna, $i->ram, $i->kapasitas])->filter()->implode(' · ') }}</div>
                        @endif
                        @if($i->imei || $i->imei2)
                        <div class="text-xs" style="color:#94a3b8">IMEI: {{ collect([$i->imei, $i->imei2])->filter()->implode(' / ') }}</div>
                        @endif
                        @if($i->battery_health)
                        <div class="text-xs" style="color:#16a34a"><i class="fas fa-battery-three-quarters"></i> {{ $i->battery_health }}%</div>
                        @endif
                    </td>
                    <td>
                        @if($i->tipe === 'jual')
                        <span class="badge badge-masuk-kas"><i class="fas fa-arrow-up"></i> Jual</span>
                        @else
                        <span class="badge badge-keluar"><i class="fas fa-arrow-down"></i> Beli</span>
                        @endif
                        <div class="text-xs" style="color:#94a3b8;margin-top:2px">{{ $i->metode_bayar ?? 'Cash' }}</div>
                        @if(($i->status_pemeriksaan ?? 'Belum Dicek') !== 'Belum Dicek')
                        <div style="margin-top:2px"><span class="badge" style="font-size:.58rem;background:{{ $i->status_pemeriksaan==='Normal'?'#dcfce7':'#fee2e2' }};color:{{ $i->status_pemeriksaan==='Normal'?'#166534':'#991b1b' }}">{{ $i->status_pemeriksaan }}</span></div>
                        @endif
                    </td>
                    <td>
                        @php $badge = $i->statusUnitBadge(); @endphp
                        <span class="badge" style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};font-size:.66rem">{{ $i->status_unit ?? 'Ready Dijual' }}</span>
                    </td>
                    <td style="font-weight:700;white-space:nowrap">
                        {{ formatRp($i->harga) }}
                        @if($i->tipe === 'beli' && $i->harga_jual)
                        <div class="text-xs" style="color:var(--success)">Jual: {{ formatRp($i->harga_jual) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($i->estimasi_laba_calc !== null)
                        @php $laba = (float) $i->estimasi_laba_calc; @endphp
                        <span style="font-weight:700;color:{{ $laba >= 0 ? '#16a34a' : '#dc2626' }}">{{ $laba >= 0 ? '+' : '-' }} {{ formatRp(abs($laba)) }}</span>
                        @else <span class="text-xs" style="color:#94a3b8">—</span> @endif
                    </td>
                    <td>
                        @if($i->garansi && $i->garansi !== 'Tanpa Garansi')
                        <span class="badge" style="font-size:.62rem;background:#e0e7ff;color:#3730a3"><i class="fas fa-shield-alt"></i> {{ $i->garansi }}</span>
                        @if($i->garansiAktif())
                        <div class="text-xs" style="color:#64748b">s/d {{ $i->garansi_hingga?->format('d/m/Y') }}</div>
                        @endif
                        @else <span class="text-xs" style="color:#cbd5e1">—</span> @endif
                    </td>
                    <td>{{ $i->pelanggan ?? '-' }}</td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('print.jual-beli', $i) }}" class="btn btn-secondary btn-xs" target="_blank" title="Cetak Struk"><i class="fas fa-print"></i></a>
                        <a href="{{ route('jualbeli.edit', $i) }}" class="btn btn-primary btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
                        @if($i->status !== 'Dibatalkan')
                        <button onclick="openBatalJBModal({{ $i->id }}, '{{ $i->kode ?? $i->id }}')" class="btn btn-xs" style="background:#dc2626;color:#fff" title="Batalkan"><i class="fas fa-ban"></i></button>
                        @endif
                        <form method="POST" action="{{ route('jualbeli.destroy', $i) }}" style="display:inline" onsubmit="return confirm('Hapus transaksi ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs" title="Hapus"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" style="text-align:center;color:#94a3b8;padding:20px">Belum ada data transaksi jual beli.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $items->links() }}
</div>

{{-- ==================== MODAL BATALKAN ==================== --}}
<div id="batalJBModal" style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center">
    <div onclick="closeBatalJBModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px)"></div>
    <div style="position:relative;background:#fff;border-radius:16px;max-width:440px;width:92%;box-shadow:0 25px 60px rgba(0,0,0,.25)">
        <div style="padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:40px;height:40px;border-radius:10px;background:#fef2f2;display:flex;align-items:center;justify-content:center;font-size:1.1rem">🚫</div>
                <div>
                    <div style="font-size:1rem;font-weight:700;color:#dc2626">Batalkan Transaksi</div>
                    <div id="batalJBKode" style="font-size:.78rem;color:#64748b">-</div>
                </div>
            </div>
            <button onclick="closeBatalJBModal()" style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:8px;cursor:pointer"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:20px 24px">
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px;margin-bottom:16px">
                <div style="font-size:.82rem;color:#991b1b"><i class="fas fa-exclamation-triangle"></i> Kas akan dikoreksi secara otomatis.</div>
            </div>
            <label style="font-size:.82rem;font-weight:700;display:block;margin-bottom:6px">Alasan Pembatalan *</label>
            <textarea id="batalJBAlasan" rows="3" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.84rem;resize:vertical" placeholder="Masukkan alasan..."></textarea>
        </div>
        <div style="padding:0 24px 20px;display:flex;gap:10px">
            <button onclick="closeBatalJBModal()" style="flex:1;padding:10px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-weight:600;cursor:pointer">Kembali</button>
            <button onclick="submitBatalJB()" id="btnSubmitBatalJB" style="flex:1;padding:10px;border-radius:10px;border:none;background:#dc2626;color:#fff;font-weight:700;cursor:pointer"><i class="fas fa-ban"></i> Batalkan</button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="jbToast" style="display:none;position:fixed;top:24px;right:24px;z-index:10001;min-width:280px;padding:14px 20px;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.15);font-size:.88rem;font-weight:600;transition:all .3s ease;transform:translateX(120%);align-items:center;gap:10px">
    <i id="jbToastIcon"></i>
    <span id="jbToastMsg"></span>
</div>

<script>
let batalJBId = null;

function openBatalJBModal(id, kode) {
    batalJBId = id;
    document.getElementById('batalJBKode').textContent = kode;
    document.getElementById('batalJBAlasan').value = '';
    document.getElementById('batalJBModal').style.display = 'flex';
}
function closeBatalJBModal() {
    document.getElementById('batalJBModal').style.display = 'none';
    batalJBId = null;
}
function submitBatalJB() {
    if (!batalJBId) return;
    const alasan = document.getElementById('batalJBAlasan').value.trim();
    if (!alasan || alasan.length < 3) { showJBToast('error','Alasan minimal 3 karakter'); return; }
    const btn = document.getElementById('btnSubmitBatalJB');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>...';
    fetch('{{ url("/jualbeli") }}/' + batalJBId + '/batal', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ alasan })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { showJBToast('success', data.message); closeBatalJBModal(); setTimeout(() => location.reload(), 1200); }
        else { showJBToast('error', data.message); }
    })
    .catch(() => showJBToast('error', 'Terjadi kesalahan'))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-ban"></i> Batalkan'; });
}

function showJBToast(type, message) {
    const t = document.getElementById('jbToast');
    document.getElementById('jbToastMsg').textContent = message;
    const icon = document.getElementById('jbToastIcon');
    if (type==='success') { t.style.background='#dcfce7'; t.style.color='#166534'; t.style.border='1px solid #bbf7d0'; icon.className='fas fa-check-circle'; }
    else { t.style.background='#fef2f2'; t.style.color='#991b1b'; t.style.border='1px solid #fecaca'; icon.className='fas fa-times-circle'; }
    t.style.display = 'flex';
    setTimeout(() => { t.style.transform = 'translateX(0)'; }, 50);
    setTimeout(() => { t.style.transform = 'translateX(120%)'; setTimeout(() => { t.style.display = 'none'; }, 300); }, 3500);
}

// ===== BULK DELETE (checkbox) =====
function updateBulkBarJB() {
    const checked = document.querySelectorAll('.bulk-check-jb:checked');
    const bar = document.getElementById('bulkDeleteFormJB');
    const counter = document.getElementById('bulkSelectedCountJB');
    const selectAll = document.getElementById('selectAllJB');
    if (bar) bar.style.display = checked.length > 0 ? 'block' : 'none';
    if (counter) counter.textContent = checked.length;
    if (selectAll) selectAll.checked = checked.length > 0 && checked.length === document.querySelectorAll('.bulk-check-jb').length;
}
function toggleAllJB(master) {
    document.querySelectorAll('.bulk-check-jb').forEach(cb => { cb.checked = master.checked; });
    updateBulkBarJB();
}
function clearBulkJB() {
    document.querySelectorAll('.bulk-check-jb').forEach(cb => { cb.checked = false; });
    const selectAll = document.getElementById('selectAllJB'); if (selectAll) selectAll.checked = false;
    updateBulkBarJB();
}
function confirmBulkJB() {
    const checked = document.querySelectorAll('.bulk-check-jb:checked');
    if (checked.length === 0) { showJBToast('error', 'Pilih minimal satu item.'); return; }
    if (!confirm('Hapus ' + checked.length + ' transaksi terpilih? Tindakan ini tidak bisa dibatalkan.')) return;
    const ids = Array.from(checked).map(cb => cb.value);
    const form = document.getElementById('bulkDeleteFormJB');
    form.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
    ids.forEach(id => { const inp = document.createElement('input'); inp.type='hidden'; inp.name='ids[]'; inp.value=id; form.appendChild(inp); });
    form.submit();
}
</script>
@endsection
