@extends('layouts.app')
@section('title', 'Edit Servis')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem">Edit Servis - {{ $servis->kode }}</h2>
    <div style="display:flex;gap:8px">
        <a href="{{ route('print.servis', $servis) }}" class="btn btn-primary btn-sm" target="_blank"><i class="fas fa-print"></i> Print Thermal</a>
        <a href="{{ route('servis.show', $servis) }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
</div>

@if($servis->sumber === 'user')
<div class="alert alert-warning mb-4" style="display:flex;align-items:center;gap:10px">
    <i class="fas fa-mobile-alt" style="font-size:1.1rem"></i>
    <div>
        <strong>Servis dari User Online</strong>
        <span style="font-size:.78rem;color:#92400e;margin-left:4px">— Cabang: {{ $servis->cabang?->nama ?? '-' }}. Proses dengan assign teknisi dan update status.</span>
    </div>
</div>
@endif

<div class="card">
    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#991b1b;font-size:.85rem">
        <i class="fas fa-exclamation-triangle" style="margin-right:6px"></i><strong>Gagal menyimpan!</strong>
        <ul style="margin:6px 0 0 0;padding-left:18px">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    <form method="POST" action="{{ route('servis.update', $servis) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        {{-- Info Pelanggan (readonly) --}}
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                <h3 style="font-size:.88rem;margin:0"><i class="fas fa-user" style="color:var(--primary);margin-right:6px"></i>Pelanggan</h3>
                <span class="badge badge-{{ $servis->sumber === 'user' ? 'Masuk' : 'Proses' }}">{{ $servis->sumber === 'user' ? 'Online' : 'Walk-in' }}</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:.82rem;color:#475569">
                <div><strong>Nama:</strong> {{ $servis->pelanggan?->nama ?? '-' }}</div>
                <div><strong>No HP:</strong> {{ $servis->pelanggan?->no_hp ?? '-' }}</div>
                <div style="grid-column:span 2"><strong>Alamat:</strong> {{ $servis->pelanggan?->alamat ?? '-' }}</div>
            </div>
        </div>

        {{-- Info Perangkat (readonly) --}}
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;margin-bottom:16px">
            <h3 style="font-size:.88rem;margin:0 0 8px 0"><i class="fas fa-mobile-alt" style="color:var(--accent);margin-right:6px"></i>Perangkat</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:.82rem;color:#475569">
                <div><strong>Perangkat:</strong> {{ $servis->perangkat }}</div>
                <div><strong>Tipe OS:</strong> {{ $servis->tipe }}</div>
                @if($servis->imei)
                <div><strong>IMEI:</strong> {{ $servis->imei }}</div>
                @endif
                @if($servis->sn)
                <div><strong>SN:</strong> {{ $servis->sn }}</div>
                @endif
            </div>
            <input type="hidden" name="perangkat" value="{{ $servis->perangkat }}">
            <input type="hidden" name="tipe" value="{{ $servis->tipe }}">
            <input type="hidden" name="imei" value="{{ $servis->imei ?? '' }}">
        </div>

        {{-- Keluhan (editable) --}}
        <div class="form-group" style="margin-bottom:14px">
            <label><i class="fas fa-comment-dots" style="color:var(--accent);margin-right:4px"></i>Keluhan *</label>
            <textarea name="keluhan" class="form-input" rows="2" required placeholder="Keluhan pelanggan...">{{ old('keluhan', $servis->keluhan) }}</textarea>
        </div>

        {{-- Status, Biaya, DP --}}
        <div class="form-row">
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-input">
                    @foreach(['Masuk','Proses','Pending','Selesai'] as $st)
                    <option value="{{ $st }}" {{ old('status', $servis->status) === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Prioritas</label>
                <select name="prioritas" class="form-input">
                    <option value="Normal" {{ old('prioritas', $servis->prioritas) === 'Normal' ? 'selected' : '' }}>Normal</option>
                    <option value="Urgent" {{ old('prioritas', $servis->prioritas) === 'Urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Biaya Servis (Total) (Rp)</label>
                <input type="text" inputmode="numeric" name="biaya" id="biayaInput" class="form-input" value="{{ (int) old('biaya', $servis->biaya) }}" min="0" data-format-rupiah>
                <div class="text-xs text-muted" style="margin-top:4px;color:#64748b">Masukkan harga <strong>keseluruhan</strong> yang ditagihkan ke pelanggan (sudah termasuk jasa + sparepart). Sparepart di bawah hanya untuk tracking & laba.</div>
            </div>
            <div class="form-group">
                <label>DP (Rp)</label>
                <input type="text" inputmode="numeric" name="dp" id="dpInput" class="form-input" value="{{ (int) old('dp', $servis->dp) }}" min="0" data-format-rupiah>
            </div>
        </div>

        {{-- Total Otomatis --}}
        <div id="totalBox" style="background:linear-gradient(135deg,#0d9488,#065f46);color:#fff;border-radius:12px;padding:16px 20px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center">
            <div>
                <div style="font-size:.78rem;opacity:.85">Total yang harus dibayar</div>
                <div style="font-size:.68rem;opacity:.7;margin-top:2px">Biaya Servis - DP</div>
            </div>
            <div style="font-size:1.3rem;font-weight:800" id="totalBayarDisplay">Rp 0</div>
        </div>

        {{-- Teknisi --}}
        <div class="form-group">
            <label>Teknisi</label>
            <select name="teknisi_id" class="form-input">
                <option value="">-- Pilih Teknisi --</option>
                @foreach($teknisis as $t)
                <option value="{{ $t->id }}" {{ old('teknisi_id', $servis->teknisi_id) == $t->id ? 'selected' : '' }}>{{ $t->nama }} ({{ $t->spesialisasi }})</option>
                @endforeach
                {{-- Jika teknisi saat ini tidak aktif, tampilkan sebagai opsi disabled --}}
                @if($servis->teknisi && !$teknisis->contains('id', $servis->teknisi_id))
                <option value="{{ $servis->teknisi_id }}" selected disabled>{{ $servis->teknisi->nama }} (Non-aktif)</option>
                @endif
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Garansi (hari)</label>
                <input type="number" name="garansi" class="form-input" value="{{ old('garansi', $servis->garansi) }}" min="0">
            </div>
            <div class="form-group">
                <label>Catatan</label>
                <input type="text" name="catatan" class="form-input" value="{{ old('catatan', $servis->catatan) }}">
            </div>
        </div>

        {{-- Sparepart Selection (Admin only) --}}
        @if(auth()->user()->isAdmin())
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <h3 style="font-size:.95rem;margin:0"><i class="fas fa-puzzle-piece" style="color:var(--accent);margin-right:6px"></i> Sparepart Digunakan <span style="font-size:.7rem;color:#94a3b8;font-weight:400">(Opsional)</span></h3>
                <button type="button" onclick="toggleSparepartSection()" id="btnToggleSparepart" class="btn btn-sm btn-secondary"><i class="fas fa-plus"></i> Tambah Sparepart</button>
            </div>
            <div id="sparepartInfo" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;font-size:.8rem;color:#166534;margin-bottom:12px">
                <i class="fas fa-info-circle" style="margin-right:4px"></i>
                <strong>Tanpa sparepart</strong> — Biaya servis hanya jasa perbaikan tanpa pergantian komponen. Biaya: <strong>{{ formatRp($servis->biaya) }}</strong>
            </div>
            <div id="sparepartSection" style="display:none">
                <div id="sparepartContainer">
                    @if($servis->spareparts && count($servis->spareparts) > 0)
                        @foreach($servis->spareparts as $existing)
                        <div class="sparepart-row" style="display:flex;gap:8px;align-items:flex-end;margin-bottom:8px">
                            <div style="flex:2">
                                <label class="text-xs font-bold text-muted">Pilih Sparepart</label>
                                <select name="sparepart_ids[]" class="form-input sparepart-select" onchange="updateSparepartPrice(this)">
                                    <option value="">-- Pilih --</option>
                                    @foreach($spareparts as $sp)
                                    <option value="{{ $sp->id }}" data-harga="{{ $sp->jual }}" data-nama="{{ $sp->nama }}" data-stok="{{ $sp->stok }}" {{ $sp->id == ($existing['id'] ?? null) ? 'selected' : '' }}>{{ $sp->nama }} (Stok: {{ $sp->stok }}) - {{ formatRp($sp->jual) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="flex:0 0 70px">
                                <label class="text-xs font-bold text-muted">Qty</label>
                                <input type="number" name="sparepart_qtys[]" class="form-input sparepart-qty" value="{{ $existing['qty'] ?? 1 }}" min="1" style="text-align:center">
                            </div>
                            <div style="flex:1">
                                <label class="text-xs font-bold text-muted">Harga Jual</label>
                                <input type="text" inputmode="numeric" name="sparepart_prices[]" class="form-input sparepart-price" value="{{ (int) ($existing['harga'] ?? 0) }}" min="0" data-format-rupiah>
                            </div>
                            <button type="button" onclick="removeSparepartRow(this)" class="btn btn-danger btn-xs" style="margin-bottom:1px"><i class="fas fa-trash"></i></button>
                        </div>
                        @endforeach
                    @else
                    <div class="sparepart-row" style="display:flex;gap:8px;align-items:flex-end;margin-bottom:8px">
                        <div style="flex:2">
                            <label class="text-xs font-bold text-muted">Pilih Sparepart</label>
                            <select name="sparepart_ids[]" class="form-input sparepart-select" onchange="updateSparepartPrice(this)">
                                <option value="">-- Pilih --</option>
                                @foreach($spareparts as $sp)
                                <option value="{{ $sp->id }}" data-harga="{{ $sp->jual }}" data-nama="{{ $sp->nama }}" data-stok="{{ $sp->stok }}">{{ $sp->nama }} (Stok: {{ $sp->stok }}) - {{ formatRp($sp->jual) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="flex:0 0 70px">
                            <label class="text-xs font-bold text-muted">Qty</label>
                            <input type="number" name="sparepart_qtys[]" class="form-input sparepart-qty" value="1" min="1" style="text-align:center">
                        </div>
                        <div style="flex:1">
                            <label class="text-xs font-bold text-muted">Harga Jual</label>
                            <input type="text" inputmode="numeric" name="sparepart_prices[]" class="form-input sparepart-price" value="0" min="0" data-format-rupiah>
                        </div>
                        <button type="button" onclick="removeSparepartRow(this)" class="btn btn-danger btn-xs" style="margin-bottom:1px"><i class="fas fa-trash"></i></button>
                    </div>
                    @endif
                </div>
                <button type="button" onclick="addSparepartRow()" class="btn btn-secondary btn-sm"><i class="fas fa-plus"></i> Tambah Sparepart Lagi</button>
            </div>
        </div>
        @include('servis._sparepart-combobox')
        @endif

        {{-- Foto Kondisi HP --}}
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0">
            <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-camera" style="color:var(--info);margin-right:6px"></i> Foto Kondisi HP</h3>
            @if($servis->foto && count($servis->foto) > 0)
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
                @foreach($servis->foto as $f)
                <div style="width:100px;height:100px;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;position:relative">
                    <img src="{{ Storage::url($f) }}" style="width:100%;height:100%;object-fit:cover">
                </div>
                @endforeach
            </div>
            @endif
            <div class="form-group">
                <input type="file" name="foto[]" class="form-input" accept="image/*" multiple>
                <div class="text-xs text-muted" style="margin-top:4px">Upload foto baru (akan ditambahkan ke yang sudah ada)</div>
            </div>
            <div id="fotoPreview" style="display:flex;gap:8px;flex-wrap:wrap"></div>
        </div>

        <div style="display:flex;gap:8px;margin-top:20px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            <a href="{{ route('servis.show', $servis) }}" class="btn btn-secondary"><i class="fas fa-times"></i> Batal</a>
        </div>
    </form>
</div>

<script>
// ===== Kalkulasi Total Otomatis =====
function parseRupiah(val) {
    if (!val) return 0;
    return parseInt(String(val).replace(/[^0-9]/g, '')) || 0;
}

function formatRupiahDisplay(num) {
    return 'Rp ' + num.toLocaleString('id-ID');
}

function calculateTotal() {
    const biaya = parseRupiah(document.getElementById('biayaInput')?.value);
    const dp = parseRupiah(document.getElementById('dpInput')?.value);
    // Biaya servis = harga KESELURUHAN (sudah termasuk sparepart).
    // Sparepart TIDAK ditambah lagi agar tidak double-count.
    const sisa = Math.max(0, biaya - dp);
    document.getElementById('totalBayarDisplay').textContent = formatRupiahDisplay(sisa);
}

// Event listener untuk perubahan biaya, dp
document.getElementById('biayaInput')?.addEventListener('input', calculateTotal);
document.getElementById('dpInput')?.addEventListener('input', calculateTotal);

function updateSparepartPrice(select) {
    const option = select.options[select.selectedIndex];
    const row = select.closest('.sparepart-row');
    const priceInput = row.querySelector('.sparepart-price');
    priceInput.value = option.dataset.harga || 0;
    if (window.applyRupiahFormatOnInput) applyRupiahFormatOnInput(priceInput);
    calculateTotal();
}

function addSparepartRow() {
    const container = document.getElementById('sparepartContainer');
    const firstRow = container.querySelector('.sparepart-row');
    const newRow = firstRow.cloneNode(true);
    // Reset & rebuild the searchable sparepart widget on the cloned row
    const clonedSelect = newRow.querySelector('.sparepart-select');
    if (clonedSelect && window.teardownSparepart) {
        teardownSparepart(clonedSelect);
        clonedSelect.value = '';
        if (window.enhanceSparepart) enhanceSparepart(clonedSelect);
    }
    const qty = newRow.querySelector('.sparepart-qty');
    if (qty) qty.value = '1';
    const priceInput = newRow.querySelector('.sparepart-price');
    if (priceInput) {
        priceInput.value = '0';
        if (window.applyRupiahFormatOnInput) applyRupiahFormatOnInput(priceInput);
    }
    container.appendChild(newRow);
}

function removeSparepartRow(btn) {
    const container = document.getElementById('sparepartContainer');
    if (container.querySelectorAll('.sparepart-row').length > 1) {
        btn.closest('.sparepart-row').remove();
        calculateTotal();
    }
}

document.querySelector('input[name="foto[]"]')?.addEventListener('change', function(e) {
    const preview = document.getElementById('fotoPreview');
    preview.innerHTML = '';
    Array.from(e.target.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = function(ev) {
            preview.innerHTML += '<div style="width:80px;height:80px;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0"><img src="' + ev.target.result + '" style="width:100%;height:100%;object-fit:cover"></div>';
        };
        reader.readAsDataURL(file);
    });
});

// ===== Sparepart Section Toggle =====
function toggleSparepartSection() {
    const section = document.getElementById('sparepartSection');
    const info = document.getElementById('sparepartInfo');
    const btn = document.getElementById('btnToggleSparepart');
    const isOpen = section.style.display !== 'none';
    section.style.display = isOpen ? 'none' : 'block';
    info.style.display = isOpen ? 'block' : 'none';
    btn.innerHTML = isOpen ? '<i class="fas fa-plus"></i> Tambah Sparepart' : '<i class="fas fa-minus"></i> Sembunyikan Sparepart';
    toggleSparepartInputs(!isOpen);
}

function toggleSparepartInputs(enable) {
    document.querySelectorAll('#sparepartSection select, #sparepartSection input').forEach(el => {
        if (enable) {
            if (el.dataset.origName) el.setAttribute('name', el.dataset.origName);
        } else {
            if (!el.dataset.origName && el.hasAttribute('name')) el.dataset.origName = el.getAttribute('name');
            el.removeAttribute('name');
        }
    });
}

// Auto-show sparepart section if existing data
(function() {
    const rowsWithData = document.querySelectorAll('.sparepart-row .sparepart-select');
    let hasData = false;
    rowsWithData.forEach(sel => { if (sel.value !== '') hasData = true; });
    if (hasData) {
        document.getElementById('sparepartSection').style.display = 'block';
        document.getElementById('sparepartInfo').style.display = 'none';
        document.getElementById('btnToggleSparepart').innerHTML = '<i class="fas fa-minus"></i> Sembunyikan Sparepart';
    } else {
        toggleSparepartInputs(false);
    }
})();

// Before form submit, strip hidden sparepart inputs
document.querySelector('form[method="POST"]')?.addEventListener('submit', function() {
    const section = document.getElementById('sparepartSection');
    if (section && section.style.display === 'none') {
        section.querySelectorAll('select, input').forEach(el => {
            if (!el.dataset.origName && el.hasAttribute('name')) el.dataset.origName = el.getAttribute('name');
            el.removeAttribute('name');
        });
    }
});

// Init: hitung total saat load
setTimeout(calculateTotal, 300);
</script>
@endsection
