@extends('layouts.app')
@section('title', 'Tagihan Sparepart')

@section('content')
<style>
.tagihan-grid { display: grid; grid-template-columns: 1fr 420px; gap: 20px; }
.tagihan-form { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 20px; }
.tagihan-items-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
.tagihan-items-table th { text-align: left; padding: 8px 10px; font-size: .72rem; font-weight: 600; color: #64748b; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
.tagihan-items-table td { padding: 8px 10px; font-size: .82rem; border-bottom: 1px solid #f1f5f9; }
.tagihan-items-table input, .tagihan-items-table select { width: 100%; padding: 6px 8px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: .8rem; }
.tagihan-summary { background: #f0fdf4; border-radius: 12px; padding: 16px; margin-top: 16px; }
.tagihan-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
.status-belum { background: #fee2e2; color: #991b1b; }
.status-sebagian { background: #fef3c7; color: #92400e; }
.status-lunas { background: #dcfce7; color: #166534; }
.status-batal { background: #f1f5f9; color: #64748b; }
@media (max-width: 900px) { .tagihan-grid { grid-template-columns: 1fr; } }
body.dark .tagihan-form { background: #1e293b; border-color: #334155; }
body.dark .tagihan-items-table th { background: #0f172a; color: #94a3b8; }
body.dark .tagihan-items-table td { border-color: #334155; color: #e2e8f0; }
body.dark .tagihan-summary { background: #052e16; }
</style>

<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-file-invoice" style="color:var(--primary);margin-right:6px"></i> Tagihan Sparepart</h2>
    <a href="{{ route('penjualan-sparepart.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali ke POS</a>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-clock"></i></div>
        <div class="stat-label">Belum Dibayar</div>
        <div class="stat-value" style="color:#dc2626;font-size:1.2rem">{{ formatRp($totalBelumBayar) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="stat-label">Total Tagihan</div>
        <div class="stat-value" style="color:var(--primary);font-size:1.2rem">{{ formatRp($totalTagihan) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-check-circle"></i></div>
        <div class="stat-label">Sudah Dibayar</div>
        <div class="stat-value" style="color:var(--success);font-size:1.2rem">{{ formatRp($totalDibayar ?? 0) }}</div>
        <div class="text-xs text-muted">Termasuk pembayaran sebagian</div>
    </div>
</div>

<div class="tagihan-grid">
    <!-- LEFT: Tagihan List -->
    <div>
        <!-- Filter -->
        <form method="GET" class="card mb-4" style="padding:14px">
            <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
                <div style="flex:1;min-width:120px">
                    <label class="text-xs font-bold text-muted">Cari</label>
                    <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="Kode / Nama toko...">
                </div>
                <div style="min-width:120px">
                    <label class="text-xs font-bold text-muted">Status</label>
                    <select name="status" class="form-input">
                        <option value="">Semua</option>
                        <option value="Belum Dibayar" {{ request('status') == 'Belum Dibayar' ? 'selected' : '' }}>Belum Dibayar</option>
                        <option value="Sebagian" {{ request('status') == 'Sebagian' ? 'selected' : '' }}>Sebagian</option>
                        <option value="Lunas" {{ request('status') == 'Lunas' ? 'selected' : '' }}>Lunas</option>
                        <option value="Dibatalkan" {{ request('status') == 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>
                <div style="min-width:120px">
                    <label class="text-xs font-bold text-muted">Dari</label>
                    <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}">
                </div>
                <div style="min-width:120px">
                    <label class="text-xs font-bold text-muted">Sampai</label>
                    <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                <a href="{{ route('tagihan-sparepart.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-redo"></i></a>
            </div>
        </form>

        <!-- Tagihan Cards -->
        <div id="tagihanList">
            @forelse($tagihans as $t)
            <div class="card mb-3" style="padding:16px;{{ $t->status === 'Dibatalkan' ? 'opacity:.5' : '' }}">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                            <strong style="color:var(--primary);font-size:.92rem">{{ $t->kode }}</strong>
                            <span class="tagihan-status status-{{ $t->status === 'Belum Dibayar' ? 'belum' : ($t->status === 'Lunas' ? 'lunas' : ($t->status === 'Sebagian' ? 'sebagian' : 'batal')) }}">
                                {{ $t->status }}
                            </span>
                        </div>
                        <div style="font-size:1rem;font-weight:700;color:#1e293b">{{ $t->nama_toko }}</div>
                        @if($t->kontak_toko)
                        <div style="font-size:.72rem;color:#64748b"><i class="fas fa-phone"></i> {{ $t->kontak_toko }}</div>
                        @endif
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:1.1rem;font-weight:800;color:var(--primary)">{{ formatRp($t->total) }}</div>
                        @if($t->status !== 'Lunas' && $t->status !== 'Dibatalkan')
                        <div style="font-size:.76rem;color:#dc2626;font-weight:600">Sisa: {{ formatRp($t->sisa) }}</div>
                        @endif
                    </div>
                </div>

                {{-- Items --}}
                <div style="border-top:1px dashed #e2e8f0;padding-top:8px;margin-bottom:8px">
                    @foreach($t->items as $item)
                    <div style="display:flex;justify-content:space-between;font-size:.78rem;padding:2px 0;color:#64748b">
                        <span>{{ $item->nama_barang }} x{{ $item->qty }}</span>
                        <span>{{ formatRp($item->subtotal) }}</span>
                    </div>
                    @endforeach
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center">
                    <div style="font-size:.72rem;color:#94a3b8">
                        <i class="fas fa-calendar"></i> {{ $t->tanggal?->format('d/m/Y') }}
                        @if($t->tanggal_jatuh_tempo)
                        &nbsp;|&nbsp; Jatuh tempo: {{ $t->tanggal_jatuh_tempo->format('d/m/Y') }}
                        @endif
                        &nbsp;|&nbsp; Dibayar: {{ formatRp($t->dibayar) }}
                    </div>
                    <div style="display:flex;gap:4px">
                        <button onclick="viewTagihan({{ $t->id }})" class="btn btn-secondary btn-xs" title="Detail"><i class="fas fa-eye"></i></button>
                        <a href="{{ route('tagihan-sparepart.print', $t) }}" class="btn btn-secondary btn-xs" target="_blank" title="Cetak"><i class="fas fa-print"></i></a>
                        @if($t->status !== 'Lunas' && $t->status !== 'Dibatalkan')
                        <button onclick="openBayarModal({{ $t->id }}, '{{ $t->kode }}', {{ $t->sisa }})" class="btn btn-xs" style="background:var(--success);color:#fff" title="Bayar"><i class="fas fa-money-bill"></i></button>
                        @endif
                        @if($t->status !== 'Dibatalkan' && $t->dibayar == 0)
                        <button onclick="batalkanTagihan({{ $t->id }}, '{{ $t->kode }}')" class="btn btn-xs" style="background:#dc2626;color:#fff" title="Batalkan"><i class="fas fa-ban"></i></button>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="card" style="text-align:center;padding:40px;color:#94a3b8">
                <i class="fas fa-file-invoice" style="font-size:2rem;opacity:.3;margin-bottom:12px;display:block"></i>
                Belum ada tagihan sparepart.
            </div>
            @endforelse

            {{ $tagihans->links() }}
        </div>
    </div>

    <!-- RIGHT: Create Tagihan Form -->
    <div class="tagihan-form" id="tagihanForm">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:6px"></i> Buat Tagihan Baru</h3>

        <div class="form-group">
            <label>Nama Toko *</label>
            <input type="text" id="tghNamaToko" class="form-input" placeholder="Nama toko tujuan...">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Kontak Toko</label>
                <input type="text" id="tghKontak" class="form-input" placeholder="No HP / WA...">
            </div>
            <div class="form-group">
                <label>Alamat Toko</label>
                <input type="text" id="tghAlamat" class="form-input" placeholder="Alamat...">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Tanggal *</label>
                <input type="date" id="tghTanggal" class="form-input" value="{{ now()->format('Y-m-d') }}">
            </div>
            <div class="form-group">
                <label>Jatuh Tempo</label>
                <input type="date" id="tghJatuhTempo" class="form-input">
            </div>
        </div>

        {{-- Add Item --}}
        <div style="border:1px dashed #e2e8f0;border-radius:10px;padding:12px;margin-bottom:12px">
            <div style="font-size:.78rem;font-weight:700;margin-bottom:8px;color:#64748b">Tambah Barang</div>
            <div style="display:flex;gap:6px;align-items:flex-end;flex-wrap:wrap">
                <div style="flex:2;min-width:140px">
                    <select id="tghStokId" class="form-input" style="padding:8px;font-size:.8rem">
                        <option value="">Pilih Sparepart</option>
                        @foreach($stoks as $s)
                        <option value="{{ $s->id }}" data-harga="{{ $s->jual }}" data-stok="{{ $s->stok }}" data-nama="{{ $s->nama }}">{{ $s->nama }} ({{ $s->stok }}) - {{ formatRp($s->jual) }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="width:70px">
                    <input type="number" id="tghQty" class="form-input" value="1" min="1" placeholder="Qty" style="padding:8px;font-size:.8rem">
                </div>
                <div style="width:110px">
                    <input type="number" id="tghHarga" class="form-input" placeholder="Harga" style="padding:8px;font-size:.8rem">
                </div>
                <button onclick="addTagihanItem()" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i></button>
            </div>
        </div>

        {{-- Items Table --}}
        <table class="tagihan-items-table" id="tghItemsTable">
            <thead>
                <tr><th>Barang</th><th style="width:60px">Qty</th><th style="width:100px">Harga</th><th style="width:100px">Subtotal</th><th style="width:30px"></th></tr>
            </thead>
            <tbody id="tghItemsBody">
                <tr id="tghNoItems"><td colspan="5" style="text-align:center;color:#94a3b8;font-size:.78rem;padding:16px">Belum ada item</td></tr>
            </tbody>
        </table>

        {{-- Discount & Summary --}}
        <div class="tagihan-summary">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-size:.82rem">
                <span style="color:#64748b">Subtotal</span>
                <span id="tghSubtotal" style="font-weight:600">Rp 0</span>
            </div>
            <div style="display:flex;gap:8px;margin-bottom:6px">
                <div style="flex:1">
                    <input type="number" id="tghDiskonPersen" class="form-input" value="0" min="0" max="100" placeholder="Diskon %" style="padding:6px 8px;font-size:.78rem" oninput="calcTagihanTotal()">
                </div>
                <div style="flex:1">
                    <input type="number" id="tghDiskonNominal" class="form-input" value="0" min="0" placeholder="Diskon Rp" style="padding:6px 8px;font-size:.78rem" oninput="calcTagihanTotal()">
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:1.05rem;font-weight:800;color:var(--primary);padding-top:8px;border-top:2px dashed #bbf7d0">
                <span>TOTAL</span>
                <span id="tghTotal">Rp 0</span>
            </div>
        </div>

        <div class="form-group" style="margin-top:12px">
            <label>Catatan</label>
            <textarea id="tghCatatan" class="form-input" rows="2" placeholder="Catatan tagihan..." style="resize:vertical"></textarea>
        </div>

        <button onclick="submitTagihan()" id="btnSubmitTagihan" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;margin-top:8px">
            <i class="fas fa-paper-plane"></i> Buat Tagihan
        </button>
    </div>
</div>

{{-- ==================== MODAL DETAIL TAGIHAN ==================== --}}
<div id="detailTagihanModal" style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center">
    <div onclick="closeDetailModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px)"></div>
    <div style="position:relative;background:#fff;border-radius:16px;max-width:600px;width:92%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,.25);animation:modalIn .25s ease">
        <div style="padding:20px 24px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0">
            <h3 style="margin:0;font-size:1rem"><i class="fas fa-file-invoice" style="color:var(--primary)"></i> Detail Tagihan</h3>
            <button onclick="closeDetailModal()" style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:8px;cursor:pointer"><i class="fas fa-times"></i></button>
        </div>
        <div id="detailTagihanContent" style="padding:20px 24px">
            Loading...
        </div>
    </div>
</div>

{{-- ==================== MODAL BAYAR ==================== --}}
<div id="bayarModal" style="display:none;position:fixed;inset:0;z-index:10001;align-items:center;justify-content:center">
    <div onclick="closeBayarModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px)"></div>
    <div style="position:relative;background:#fff;border-radius:16px;max-width:400px;width:92%;box-shadow:0 25px 60px rgba(0,0,0,.25);animation:modalIn .25s ease">
        <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:40px;height:40px;border-radius:10px;background:#dcfce7;display:flex;align-items:center;justify-content:center;font-size:1.1rem">💰</div>
                <div>
                    <div style="font-size:1rem;font-weight:700;color:var(--success)">Catat Pembayaran</div>
                    <div id="bayarKode" style="font-size:.78rem;color:#64748b">-</div>
                </div>
            </div>
        </div>
        <div style="padding:20px 24px">
            <div style="background:#fef3c7;border-radius:8px;padding:10px;margin-bottom:12px;text-align:center">
                <div style="font-size:.72rem;color:#92400e;font-weight:600">Sisa Tagihan</div>
                <div id="bayarSisa" style="font-size:1.2rem;font-weight:800;color:#92400e">Rp 0</div>
            </div>
            <div class="form-group">
                <label>Jumlah Bayar *</label>
                <input type="number" id="bayarJumlah" class="form-input" min="1" placeholder="Masukkan jumlah...">
            </div>
            <div class="form-group">
                <label>Metode Pembayaran *</label>
                <select id="bayarMetode" class="form-input">
                    <option value="Cash">Cash</option>
                    <option value="Transfer">Transfer</option>
                    <option value="QRIS">QRIS</option>
                </select>
            </div>
        </div>
        <div style="padding:0 24px 20px;display:flex;gap:10px">
            <button onclick="closeBayarModal()" style="flex:1;padding:10px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-weight:600;cursor:pointer">Batal</button>
            <button onclick="submitBayar()" id="btnSubmitBayar" style="flex:1;padding:10px;border-radius:10px;border:none;background:var(--success);color:#fff;font-weight:700;cursor:pointer"><i class="fas fa-check"></i> Bayar</button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
// ==================== TAGIHAN ITEMS ====================
let tagihanItems = [];

// Auto-fill harga when selecting product
document.getElementById('tghStokId').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('tghHarga').value = opt.dataset.harga;
    }
});

function addTagihanItem() {
    const stokSel = document.getElementById('tghStokId');
    const opt = stokSel.options[stokSel.selectedIndex];
    if (!opt.value) { showToast('warning', 'Pilih sparepart terlebih dahulu'); return; }

    const stokId = parseInt(opt.value);
    const nama = opt.dataset.nama;
    const maxStok = parseInt(opt.dataset.stok);
    const qty = parseInt(document.getElementById('tghQty').value) || 1;
    const harga = parseFloat(document.getElementById('tghHarga').value) || 0;

    if (qty <= 0) { showToast('warning', 'Qty harus lebih dari 0'); return; }
    if (harga <= 0) { showToast('warning', 'Harga harus lebih dari 0'); return; }

    // Check existing
    let existing = tagihanItems.find(i => i.stok_id === stokId);
    if (existing) {
        if (existing.qty + qty > maxStok) { showToast('warning', 'Stok tidak cukup! Tersedia: ' + maxStok); return; }
        existing.qty += qty;
        existing.subtotal = existing.qty * existing.harga_satuan;
    } else {
        if (qty > maxStok) { showToast('warning', 'Stok tidak cukup! Tersedia: ' + maxStok); return; }
        tagihanItems.push({ stok_id: stokId, nama: nama, qty: qty, harga_satuan: harga, subtotal: qty * harga, max_stok: maxStok });
    }

    renderTagihanItems();
    // Reset inputs
    stokSel.value = '';
    document.getElementById('tghQty').value = '1';
    document.getElementById('tghHarga').value = '';
}

function removeTagihanItem(idx) {
    tagihanItems.splice(idx, 1);
    renderTagihanItems();
}

function renderTagihanItems() {
    const tbody = document.getElementById('tghItemsBody');
    if (tagihanItems.length === 0) {
        tbody.innerHTML = '<tr id="tghNoItems"><td colspan="5" style="text-align:center;color:#94a3b8;font-size:.78rem;padding:16px">Belum ada item</td></tr>';
    } else {
        tbody.innerHTML = tagihanItems.map((item, idx) => `
            <tr>
                <td style="font-weight:600;font-size:.78rem">${item.nama}</td>
                <td>${item.qty}</td>
                <td>${formatRp(item.harga_satuan)}</td>
                <td style="font-weight:700">${formatRp(item.subtotal)}</td>
                <td><button onclick="removeTagihanItem(${idx})" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:.8rem"><i class="fas fa-times"></i></button></td>
            </tr>
        `).join('');
    }
    calcTagihanTotal();
}

function calcTagihanTotal() {
    const subtotal = tagihanItems.reduce((sum, i) => sum + i.subtotal, 0);
    const diskonPersen = parseFloat(document.getElementById('tghDiskonPersen').value) || 0;
    const diskonNominal = parseFloat(document.getElementById('tghDiskonNominal').value) || 0;
    const totalDiskon = (subtotal * diskonPersen / 100) + diskonNominal;
    const total = Math.max(0, subtotal - totalDiskon);

    document.getElementById('tghSubtotal').textContent = formatRp(subtotal);
    document.getElementById('tghTotal').textContent = formatRp(total);
}

function submitTagihan() {
    if (tagihanItems.length === 0) { showToast('warning', 'Tambahkan minimal 1 item'); return; }
    const namaToko = document.getElementById('tghNamaToko').value.trim();
    if (!namaToko) { showToast('warning', 'Nama toko wajib diisi'); return; }

    const btn = document.getElementById('btnSubmitTagihan');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

    const data = {
        nama_toko: namaToko,
        kontak_toko: document.getElementById('tghKontak').value,
        alamat_toko: document.getElementById('tghAlamat').value,
        tanggal: document.getElementById('tghTanggal').value,
        tanggal_jatuh_tempo: document.getElementById('tghJatuhTempo').value || null,
        diskon_persen: parseFloat(document.getElementById('tghDiskonPersen').value) || 0,
        diskon_nominal: parseFloat(document.getElementById('tghDiskonNominal').value) || 0,
        catatan: document.getElementById('tghCatatan').value,
        items: tagihanItems.map(i => ({ stok_id: i.stok_id, qty: i.qty, harga_satuan: i.harga_satuan })),
    };

    fetch('{{ route("tagihan-sparepart.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('error', data.message || 'Gagal membuat tagihan');
        }
    })
    .catch(() => showToast('error', 'Terjadi kesalahan'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Buat Tagihan';
    });
}

// ==================== VIEW DETAIL ====================
function viewTagihan(id) {
    fetch('{{ url("/tagihan-sparepart") }}/' + id)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const t = res.data;
            const statusClass = t.status === 'Belum Dibayar' ? 'belum' : (t.status === 'Lunas' ? 'lunas' : (t.status === 'Sebagian' ? 'sebagian' : 'batal'));
            let html = `
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                    <div>
                        <div style="font-size:1.1rem;font-weight:800;color:var(--primary)">${t.kode}</div>
                        <div style="font-size:.82rem;color:#64748b">${t.tanggal}</div>
                    </div>
                    <span class="tagihan-status status-${statusClass}">${t.status}</span>
                </div>
                <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:14px">
                    <div style="font-size:.82rem;font-weight:700">${t.nama_toko}</div>
                    ${t.kontak_toko ? '<div style="font-size:.72rem;color:#64748b"><i class="fas fa-phone"></i> ' + t.kontak_toko + '</div>' : ''}
                    ${t.alamat_toko ? '<div style="font-size:.72rem;color:#64748b"><i class="fas fa-map-marker-alt"></i> ' + t.alamat_toko + '</div>' : ''}
                </div>
                <table style="width:100%;border-collapse:collapse;margin-bottom:14px">
                    <thead><tr>
                        <th style="text-align:left;padding:6px;font-size:.72rem;color:#64748b;border-bottom:1px solid #e2e8f0">Barang</th>
                        <th style="text-align:center;padding:6px;font-size:.72rem;color:#64748b;border-bottom:1px solid #e2e8f0">Qty</th>
                        <th style="text-align:right;padding:6px;font-size:.72rem;color:#64748b;border-bottom:1px solid #e2e8f0">Harga</th>
                        <th style="text-align:right;padding:6px;font-size:.72rem;color:#64748b;border-bottom:1px solid #e2e8f0">Subtotal</th>
                    </tr></thead>
                    <tbody>
                        ${t.items.map(i => '<tr><td style="padding:6px;font-size:.82rem">' + i.nama_barang + '</td><td style="padding:6px;text-align:center;font-size:.82rem">' + i.qty + '</td><td style="padding:6px;text-align:right;font-size:.82rem">' + formatRp(i.harga_satuan) + '</td><td style="padding:6px;text-align:right;font-size:.82rem;font-weight:700">' + formatRp(i.subtotal) + '</td></tr>').join('')}
                    </tbody>
                </table>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:.82rem"><span style="color:#64748b">Subtotal</span><span>${formatRp(t.subtotal)}</span></div>
                ${t.diskon_persen > 0 || t.diskon_nominal > 0 ? '<div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:.82rem;color:#dc2626"><span>Diskon</span><span>-' + formatRp((t.subtotal * t.diskon_persen / 100) + (t.diskon_nominal || 0)) + '</span></div>' : ''}
                <div style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:800;color:var(--primary);padding-top:8px;border-top:2px dashed #e2e8f0"><span>TOTAL</span><span>${formatRp(t.total)}</span></div>
                <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-top:8px"><span style="color:var(--success)">Dibayar</span><span style="color:var(--success);font-weight:700">${formatRp(t.dibayar)}</span></div>
                ${t.sisa > 0 ? '<div style="display:flex;justify-content:space-between;font-size:.82rem"><span style="color:#dc2626">Sisa</span><span style="color:#dc2626;font-weight:700">' + formatRp(t.sisa) + '</span></div>' : ''}
                ${t.catatan ? '<div style="margin-top:10px;padding:8px;background:#f8fafc;border-radius:8px;font-size:.78rem;color:#64748b"><i class="fas fa-sticky-note"></i> ' + t.catatan + '</div>' : ''}
            `;
            document.getElementById('detailTagihanContent').innerHTML = html;
            document.getElementById('detailTagihanModal').style.display = 'flex';
        });
}

function closeDetailModal() {
    document.getElementById('detailTagihanModal').style.display = 'none';
}

// ==================== BAYAR ====================
let bayarTagihanId = null;

function openBayarModal(id, kode, sisa) {
    bayarTagihanId = id;
    document.getElementById('bayarKode').textContent = kode;
    document.getElementById('bayarSisa').textContent = formatRp(sisa);
    document.getElementById('bayarJumlah').value = sisa;
    document.getElementById('bayarJumlah').max = sisa;
    document.getElementById('bayarModal').style.display = 'flex';
}

function closeBayarModal() {
    document.getElementById('bayarModal').style.display = 'none';
    bayarTagihanId = null;
}

function submitBayar() {
    if (!bayarTagihanId) return;
    const jumlah = parseFloat(document.getElementById('bayarJumlah').value);
    const metode = document.getElementById('bayarMetode').value;
    if (!jumlah || jumlah <= 0) { showToast('warning', 'Masukkan jumlah bayar'); return; }

    const btn = document.getElementById('btnSubmitBayar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>...';

    fetch('{{ url("/tagihan-sparepart") }}/' + bayarTagihanId + '/bayar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ jumlah: jumlah, metode: metode })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            closeBayarModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(() => showToast('error', 'Terjadi kesalahan'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Bayar';
    });
}

// ==================== BATALKAN ====================
function batalkanTagihan(id, kode) {
    if (!confirm('Batalkan tagihan ' + kode + '? Stok akan dikembalikan.')) return;

    fetch('{{ url("/tagihan-sparepart") }}/' + id + '/batal', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { showToast('success', data.message); setTimeout(() => location.reload(), 1500); }
        else { showToast('error', data.message); }
    })
    .catch(() => showToast('error', 'Terjadi kesalahan'));
}

// ==================== HELPERS ====================
function formatRp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }

function showToast(type, message) {
    const toast = document.getElementById('tagihanToast');
    const icon = document.getElementById('tagihanToastIcon');
    const msg = document.getElementById('tagihanToastMsg');
    msg.textContent = message;
    if (type === 'success') { toast.style.background = '#dcfce7'; toast.style.color = '#166534'; toast.style.border = '1px solid #bbf7d0'; icon.className = 'fas fa-check-circle'; }
    else if (type === 'warning') { toast.style.background = '#fef3c7'; toast.style.color = '#92400e'; toast.style.border = '1px solid #fde68a'; icon.className = 'fas fa-exclamation-triangle'; }
    else { toast.style.background = '#fef2f2'; toast.style.color = '#991b1b'; toast.style.border = '1px solid #fecaca'; icon.className = 'fas fa-times-circle'; }
    toast.style.display = 'flex';
    setTimeout(() => { toast.style.transform = 'translateX(0)'; }, 50);
    setTimeout(() => { toast.style.transform = 'translateX(120%)'; setTimeout(() => { toast.style.display = 'none'; }, 300); }, 3500);
}
</script>

{{-- Toast --}}
<div id="tagihanToast" style="display:none;position:fixed;top:24px;right:24px;z-index:10002;min-width:280px;max-width:420px;padding:14px 20px;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.15);font-size:.88rem;font-weight:600;transition:all .3s ease;transform:translateX(120%);align-items:center;gap:10px">
    <i id="tagihanToastIcon"></i>
    <span id="tagihanToastMsg"></span>
</div>

<style>
@keyframes modalIn { from { opacity: 0; transform: scale(.92) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
</style>
@endsection
