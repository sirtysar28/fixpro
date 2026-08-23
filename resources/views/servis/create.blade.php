@extends('layouts.app')
@section('title', 'Input Servis Baru')

@section('content')
<h2 style="margin-bottom:20px;font-size:1.3rem">Input Servis Baru</h2>

<div class="grid-2">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-keyboard" style="color:var(--primary);margin-right:6px"></i> Form Servis</h3>
        <form method="POST" action="{{ route('servis.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- PILIH PELANGGAN --}}
            <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:2px dashed #e2e8f0">
                <h3 style="font-size:.9rem;margin-bottom:12px;color:#334155">
                    <i class="fas fa-user-friends" style="color:var(--accent);margin-right:6px"></i> Data Pelanggan
                </h3>
                <div class="form-group">
                    <label>Pilih Pelanggan yang Sudah Terdaftar</label>
                    <select id="pelangganSelect" class="form-input" onchange="pilihPelanggan(this)">
                        <option value="">— Pilih Pelanggan / Input Manual —</option>
                        @foreach($pelanggans as $p)
                        <option value="{{ $p->id }}" data-nama="{{ $p->nama }}" data-no-hp="{{ $p->no_hp }}" data-alamat="{{ $p->alamat ?? '' }}" @if($p->user) data-user-email="{{ $p->user->email }}" @endif>
                            {{ $p->nama }} — {{ $p->no_hp }}
                        </option>
                        @endforeach
                    </select>
                    <div class="text-xs text-muted" style="margin-top:4px">Pilih pelanggan yang sudah ada, atau kosongkan lalu isi manual di bawah untuk pelanggan baru</div>
                </div>

                <div class="form-group">
                    <label>No. HP Pelanggan *</label>
                    <input type="tel" name="no_hp" class="form-input" id="noHp" placeholder="08xxx" required>
                </div>
                <div class="form-group">
                    <label>Nama Pelanggan *</label>
                    <input type="text" name="nama" class="form-input" id="namaP" placeholder="Nama pelanggan" required>
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <input type="text" name="alamat" class="form-input" id="alamatP" placeholder="Alamat pelanggan">
                </div>
                <div id="infoPelanggan" style="display:none;padding:10px;border-radius:8px;background:#f0fdf4;border:1px solid #bbf7d0;font-size:.8rem;color:#166534;margin-bottom:8px">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Merk HP *</label>
                    <select id="merkHpSelect" class="form-input" onchange="loadTipeHp()">
                        <option value="">-- Pilih Merk / Ketik Manual --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipe / Model HP *</label>
                    <select id="tipeHpSelect" class="form-input" onchange="autoFillPerangkat()">
                        <option value="">-- Pilih Merk dulu --</option>
                    </select>
                    <input type="text" id="perangkatInput" name="perangkat" class="form-input" placeholder="Atau ketik manual (contoh: iPhone 11)" required style="margin-top:6px">
                </div>
            </div>
            <div class="form-group">
                <label>Tipe OS *</label>
                <select name="tipe" class="form-input" required>
                    <option value="">-- Pilih --</option>
                    <option value="Apple">Apple (iOS)</option>
                    <option value="Android">Android</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>IMEI</label>
                    <input type="text" name="imei" class="form-input" placeholder="15 digit" maxlength="15">
                </div>
                <div class="form-group">
                    <label>Estimasi Selesai</label>
                    <input type="datetime-local" name="eta" class="form-input">
                </div>
            </div>
            <div class="form-group" style="position:relative">
                <label>Keluhan *</label>
                <input type="text" name="keluhan" class="form-input" id="keluhanInput" placeholder="Ganti LCD" required oninput="searchServicePrice(this.value)" onblur="setTimeout(()=>{document.getElementById('priceSuggestions').style.display='none'},300)">
                <div id="priceSuggestions" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 25px rgba(0,0,0,.12);z-index:100;max-height:250px;overflow-y:auto"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Biaya Servis (Total) (Rp)</label>
                    <input type="text" inputmode="numeric" name="biaya" id="biayaInput" class="form-input" value="0" min="0" data-format-rupiah>
                    <div class="text-xs text-muted" style="margin-top:4px;color:#64748b">Masukkan harga <strong>keseluruhan</strong> yang ditagihkan ke pelanggan (sudah termasuk jasa + sparepart). Sparepart di bawah hanya untuk tracking & laba.</div>
                </div>
                <div class="form-group">
                    <label>DP (Rp)</label>
                    <input type="text" inputmode="numeric" name="dp" id="dpInput" class="form-input" value="0" min="0" data-format-rupiah>
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

            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-input">
                        <option>Masuk</option><option>Proses</option><option>Pending</option><option>Selesai</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Prioritas</label>
                    <select name="prioritas" class="form-input">
                        <option>Normal</option><option>Urgent</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Teknisi</label>
                <select name="teknisi_id" class="form-input">
                    <option value="">-- Pilih Teknisi --</option>
                    @foreach($teknisis as $t)
                    <option value="{{ $t->id }}">{{ $t->nama }} ({{ $t->spesialisasi }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Garansi (hari)</label>
                    <input type="number" name="garansi" class="form-input" value="30" min="0">
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <input type="text" name="catatan" class="form-input" placeholder="Opsional">
                </div>
            </div>

            {{-- Sparepart Selection (Admin only) --}}
            @if(auth()->user()->isAdmin())
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0">
                <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-puzzle-piece" style="color:var(--accent);margin-right:6px"></i> Sparepart Digunakan</h3>
                <div id="sparepartContainer">
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
                </div>
                <button type="button" onclick="addSparepartRow()" class="btn btn-secondary btn-sm"><i class="fas fa-plus"></i> Tambah Sparepart</button>
            </div>
            @endif
            @include('servis._sparepart-combobox')

            {{-- Foto Kondisi HP --}}
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0">
                <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-camera" style="color:var(--info);margin-right:6px"></i> Foto Kondisi HP</h3>
                <div class="form-group">
                    <input type="file" name="foto[]" class="form-input" accept="image/*" multiple>
                    <div class="text-xs text-muted" style="margin-top:4px">Upload foto kondisi HP (bisa lebih dari 1)</div>
                </div>
                <div id="fotoPreview" style="display:flex;gap:8px;flex-wrap:wrap"></div>
            </div>

            {{-- AUTO KIRIM WA --}}
            <div style="margin-top:20px; padding:14px 16px; background:linear-gradient(135deg, #dcfce7, #f0fdf4); border:1px solid #bbf7d0; border-radius:10px;">
                <label style="display:flex; align-items:center; gap:12px; cursor:pointer; margin:0;">
                    <input type="checkbox" id="autoWaCheckbox" name="auto_wa" value="1" checked style="width:22px; height:22px; accent-color:#25D366; cursor:pointer;">
                    <div>
                        <div style="font-weight:700; color:#166534; font-size:.95rem; display:flex; align-items:center; gap:8px;">
                            <i class="fab fa-whatsapp" style="font-size:1.3rem; color:#25D366;"></i> Auto Kirim Nota via WhatsApp
                        </div>
                        <div class="text-xs" style="color:#15803d; margin-top:2px;">
                            Nota digital akan otomatis dibuka di WhatsApp Web / Aplikasi pelanggan saat Anda klik Simpan.
                        </div>
                    </div>
                </label>
            </div>

            <div class="form-group" style="margin-top:16px">
                <label>Kode Servis</label>
                <input type="text" id="kodeServis" class="form-input" value="{{ $nextKode }}" readonly style="background:#f8fafc;font-weight:700;color:var(--primary)">
            </div>
            <div style="display:flex;gap:8px;margin-top:20px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                <a href="{{ route('servis.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Batal</a>
            </div>
        </form>
    </div>
    <div>
        <div class="card">
            <h3 style="font-size:.95rem;margin-bottom:10px"><i class="fas fa-bolt" style="color:var(--accent);margin-right:6px"></i>Fitur</h3>
            <ul style="font-size:.84rem;color:#64748b;line-height:2;padding-left:16px">
                <li><strong style="color:var(--primary)">Pilih pelanggan dari daftar</strong> atau input baru</li>
                <li>Pelanggan baru otomatis dapat akun user</li>
                <li>Auto-fill data dari No HP</li>
                <li>Tracking IMEI perangkat</li>
                <li>Estimasi waktu selesai (ETA)</li>
                <li>Sistem DP & pembayaran</li>
                <li>Kode servis auto-generate</li>
                <li><strong style="color:var(--primary)">Assign teknisi</strong></li>
                <li><strong style="color:var(--accent)">Pilih sparepart</strong> (Admin)</li>
                <li><strong style="color:var(--info)">Upload foto kondisi HP</strong></li>
                <li><strong style="color:#25D366">Auto Kirim Nota via WhatsApp</strong></li>
            </ul>
        </div>
    </div>
</div>

<script>
// Pilih pelanggan dari dropdown
function pilihPelanggan(select) {
    const opt = select.options[select.selectedIndex];
    const infoEl = document.getElementById('infoPelanggan');

    if (opt.value) {
        document.getElementById('noHp').value = opt.dataset.noHp || '';
        document.getElementById('namaP').value = opt.dataset.nama || '';
        document.getElementById('alamatP').value = opt.dataset.alamat || '';

        // Tampilkan info kalau pelanggan punya akun user
        if (opt.dataset.userEmail) {
            infoEl.style.display = 'block';
            infoEl.innerHTML = '<i class="fas fa-check-circle"></i> Pelanggan ini sudah punya akun user: <strong>' + opt.dataset.userEmail + '</strong>';
        } else {
            infoEl.style.display = 'block';
            infoEl.innerHTML = '<i class="fas fa-info-circle" style="color:#f59e0b"></i> Pelanggan ini belum punya akun user. Saat simpan, akun user akan otomatis dibuat (password = No HP).';
            infoEl.style.background = '#fefce8';
            infoEl.style.borderColor = '#fde68a';
            infoEl.style.color = '#854d0e';
        }
    } else {
        // Reset kalau pilih "Input Manual"
        infoEl.style.display = 'none';
    }
}

// Auto-fill dari No HP (backup kalau user ketik manual)
document.getElementById('noHp').addEventListener('blur', function() {
    const hp = this.value.trim();
    if (hp.length >= 10) {
        fetch(`/api/pelanggan/search?q=${encodeURIComponent(hp)}`)
            .then(r => r.json())
            .then(data => {
                if (data && data.nama) {
                    document.getElementById('namaP').value = data.nama || '';
                    document.getElementById('alamatP').value = data.alamat || '';
                }
            });
    }
});

// ===== Master Data Tipe HP =====
let merkLoaded = false;
function loadMerks() {
    if (merkLoaded) return;
    fetch('/api/tipe-hp/search?q=')
        .then(r => r.json())
        .then(data => {
            const select = document.getElementById('merkHpSelect');
            // Get unique merks
            const merks = [...new Set(data.map(d => d.merk))].sort();
            merks.forEach(merk => {
                const opt = document.createElement('option');
                opt.value = merk;
                opt.textContent = merk;
                select.appendChild(opt);
            });
            // Store full data for tipe lookup
            window._tipeHpData = data;
            merkLoaded = true;
        })
        .catch(() => {});
}
loadMerks();

function loadTipeHp() {
    const merk = document.getElementById('merkHpSelect').value;
    const tipeSelect = document.getElementById('tipeHpSelect');
    tipeSelect.innerHTML = '<option value="">-- Pilih Tipe --</option>';

    if (!merk || !window._tipeHpData) {
        tipeSelect.innerHTML = '<option value="">-- Pilih Merk dulu --</option>';
        return;
    }

    const types = window._tipeHpData
        .filter(d => d.merk === merk)
        .sort((a, b) => a.tipe.localeCompare(b.tipe));

    types.forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.tipe;
        opt.textContent = t.tipe;
        opt.dataset.merk = t.merk;
        tipeSelect.appendChild(opt);
    });

    // Auto-set OS tipe based on merk
    const tipeOs = document.querySelector('select[name="tipe"]');
    const appleMerks = ['Apple', 'iPhone', 'iPad'];
    if (appleMerks.includes(merk)) {
        tipeOs.value = 'Apple';
    } else {
        tipeOs.value = 'Android';
    }
}

function autoFillPerangkat() {
    const merk = document.getElementById('merkHpSelect').value;
    const tipe = document.getElementById('tipeHpSelect').value;
    const input = document.getElementById('perangkatInput');
    if (merk && tipe) {
        input.value = merk + ' ' + tipe;
    }
}

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
    const sisa = Math.max(0, biaya - dp);
    document.getElementById('totalBayarDisplay').textContent = formatRupiahDisplay(sisa);
}

// Event listener untuk perubahan biaya, dp, dan sparepart
document.getElementById('biayaInput')?.addEventListener('input', calculateTotal);
document.getElementById('dpInput')?.addEventListener('input', calculateTotal);

// Sparepart management
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

// Foto preview
document.querySelector('input[name="foto[]"]').addEventListener('change', function(e) {
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

// ===== SERVICE PRICE AUTO-COMPLETE =====
let priceSearchTimer = null;
function searchServicePrice(query) {
    clearTimeout(priceSearchTimer);
    const suggestions = document.getElementById('priceSuggestions');
    if (!query || query.length < 2) {
        suggestions.style.display = 'none';
        return;
    }
    priceSearchTimer = setTimeout(() => {
        const merk = document.getElementById('merkHpSelect')?.value || '';
        fetch('/api/service-prices/search?q=' + encodeURIComponent(query) + '&merk=' + encodeURIComponent(merk))
            .then(r => r.json())
            .then(data => {
                if (!data || data.length === 0) {
                    suggestions.style.display = 'none';
                    return;
                }
                suggestions.innerHTML = '';
                data.forEach(sp => {
                    const div = document.createElement('div');
                    div.style.cssText = 'padding:10px 14px;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s;display:flex;justify-content:space-between;align-items:center';
                    div.onmouseover = function() { this.style.background = '#f0fdf4'; };
                    div.onmouseout = function() { this.style.background = 'transparent'; };
                    const leftHtml = '<div>' +
                        '<div style="font-weight:600;font-size:.84rem;color:#1e293b">' + sp.kerusakan + '</div>' +
                        '<div style="font-size:.68rem;color:#64748b">' +
                            (sp.merk_hp ? sp.merk_hp : 'Semua Merk') +
                            (sp.tipe_hp ? ' — ' + sp.tipe_hp : '') +
                            (sp.kategori ? ' — ' + sp.kategori : '') +
                        '</div>' +
                    '</div>';
                    const rightHtml = '<div style="text-align:right">' +
                        '<div style="font-weight:700;color:var(--success);font-size:.9rem">' + formatRupiahDisplay(sp.harga_jasa) + '</div>' +
                    '</div>';
                    div.innerHTML = leftHtml + rightHtml;
                    div.addEventListener('click', function() {
                        document.getElementById('keluhanInput').value = sp.kerusakan;
                        document.getElementById('biayaInput').value = sp.harga_jasa;
                        if (window.applyRupiahFormatOnInput) window.applyRupiahFormatOnInput(document.getElementById('biayaInput'));
                        suggestions.style.display = 'none';
                        calculateTotal();
                    });
                    suggestions.appendChild(div);
                });
                suggestions.style.display = 'block';
            })
            .catch(() => { suggestions.style.display = 'none'; });
    }, 300);
}

// ===== CRITICAL: Strip empty sparepart inputs before submit + AUTO WA =====
document.querySelectorAll('form[method="POST"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        // ===== AUTO KIRIM WA =====
        const autoWA = document.getElementById('autoWaCheckbox')?.checked;
        if (autoWA) {
            const noHp = document.getElementById('noHp').value.replace(/^0/, '62').replace(/[^0-9]/g, '');
            const nama = document.getElementById('namaP').value;
            const kodeServis = document.getElementById('kodeServis')?.value || 'Menunggu Generate';
            const perangkat = document.getElementById('perangkatInput').value;
            const keluhan = document.getElementById('keluhanInput').value;
            const biaya = document.getElementById('biayaInput').value;
            const dp = document.getElementById('dpInput').value;
            const sisa = document.getElementById('totalBayarDisplay').textContent;
            const eta = document.querySelector('input[name="eta"]').value;
            
            let pesan = `*NOTA SERVIS DIGITAL*\n`;
            pesan += `========================\n`;
            pesan += `Kode Servis: *${kodeServis}*\n`;
            pesan += `Pelanggan: ${nama}\n`;
            pesan += `No. HP: ${document.getElementById('noHp').value}\n`;
            pesan += `Perangkat: ${perangkat}\n`;
            pesan += `------------------------\n`;
            pesan += `*Keluhan:*\n${keluhan}\n\n`;
            pesan += `*Rincian Biaya:*\n`;
            pesan += `Total Biaya: ${biaya}\n`;
            pesan += `DP Dibayar: ${dp}\n`;
            pesan += `Sisa Pembayaran: *${sisa}*\n`;
            if(eta) pesan += `Estimasi Selesai: ${eta}\n`;
            pesan += `========================\n`;
            pesan += `Terima kasih telah mempercayakan servis Anda kepada kami. Anda dapat memantau status servis menggunakan kode di atas.`;
                          
            // Buka WhatsApp Web di tab baru bersamaan dengan submit form
            const waUrl = `https://api.whatsapp.com/send?phone=${noHp}&text=${encodeURIComponent(pesan)}`;
            window.open(waUrl, '_blank');
        }

        // Remove name from sparepart selects/inputs yang kosong
        this.querySelectorAll('.sparepart-select').forEach(sel => {
            if (!sel.value) {
                if (!sel.dataset.origName && sel.hasAttribute('name')) sel.dataset.origName = sel.getAttribute('name');
                sel.removeAttribute('name');
            }
        });
        // Hapus sparepart_prices[] yang pairing-nya kosong
        this.querySelectorAll('.sparepart-price').forEach(inp => {
            const row = inp.closest('.sparepart-row');
            if (row) {
                const sel = row.querySelector('.sparepart-select');
                if (!sel || !sel.value) {
                    if (!inp.dataset.origName && inp.hasAttribute('name')) inp.dataset.origName = inp.getAttribute('name');
                    inp.removeAttribute('name');
                }
            }
        });
        // Hapus sparepart_qtys[] yang pairing-nya kosong
        this.querySelectorAll('.sparepart-qty').forEach(inp => {
            const row = inp.closest('.sparepart-row');
            if (row) {
                const sel = row.querySelector('.sparepart-select');
                if (!sel || !sel.value) {
                    if (!inp.dataset.origName && inp.hasAttribute('name')) inp.dataset.origName = inp.getAttribute('name');
                    inp.removeAttribute('name');
                }
            }
        });
    });
});
</script>
@endsection