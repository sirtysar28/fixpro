@extends('layouts.app')
@section('title', 'Transaksi Jual Beli HP')

@section('content')
<style>
.jb-container { display: grid; grid-template-columns: 1fr 360px; gap: 20px; }
.jb-preview { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; position: sticky; top: 80px; }
.jb-preview-header { padding: 16px 18px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; }
.jb-preview-body { padding: 20px; }
.jb-type-selector { display: flex; gap: 0; margin-bottom: 20px; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0; }
.jb-type-btn { flex: 1; padding: 14px; font-size: .92rem; font-weight: 700; cursor: pointer; text-align: center; transition: all .2s; border: none; background: #fff; color: #64748b; display: flex; align-items: center; justify-content: center; gap: 8px; }
.jb-type-btn:hover { background: #f8fafc; }
.jb-type-btn.active-jual { background: #dcfce7; color: #166534; }
.jb-type-btn.active-beli { background: #dbeafe; color: #1e40af; }
.jb-payment-option { flex: 1; padding: 10px; border-radius: 10px; border: 2px solid #e2e8f0; text-align: center; cursor: pointer; transition: all .2s; font-size: .8rem; font-weight: 600; color: #64748b; }
.jb-payment-option:hover { border-color: var(--primary); }
.jb-payment-option.selected { border-color: var(--primary); background: var(--primary-bg); color: var(--primary); font-weight: 700; }
.preview-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: .82rem; border-bottom: 1px dashed #f1f5f9; }
.preview-row:last-child { border-bottom: none; }
.preview-total { display: flex; justify-content: space-between; padding: 12px 0; font-size: 1.2rem; font-weight: 800; color: var(--primary); border-top: 2px dashed #e2e8f0; margin-top: 8px; }
.jb-foto-thumb { width: 100%; height: 90px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 1.5rem; cursor: pointer; }
.jb-checklist { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 8px; }
.jb-check-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: .76rem; }
.jb-check-item select { padding: 4px 6px; font-size: .7rem; border-radius: 6px; border: 1px solid #e2e8f0; }
.ch-ok { border-color: #bbf7d0 !important; background: #f0fdf4 !important; }
.ch-bad { border-color: #fecaca !important; background: #fef2f2 !important; }
@media (max-width: 900px) { .jb-container { grid-template-columns: 1fr; } .jb-preview { position: static; } }
</style>

<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-mobile-alt" style="color:var(--primary);margin-right:6px"></i> Transaksi Jual Beli HP</h2>
    <a href="{{ route('jualbeli.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Daftar Transaksi</a>
</div>

<form method="POST" action="{{ route('jualbeli.store') }}" id="jbForm" enctype="multipart/form-data">
@csrf

<div class="jb-container">
    <!-- LEFT: Form -->
    <div class="jb-form">
        <!-- Tipe: Jual / Beli -->
        <div class="jb-type-selector">
            <button type="button" class="jb-type-btn active-jual" id="btnJual" onclick="setTipe('jual')">
                <i class="fas fa-arrow-up"></i> JUAL HP
            </button>
            <button type="button" class="jb-type-btn" id="btnBeli" onclick="setTipe('beli')">
                <i class="fas fa-arrow-down"></i> BELI HP
            </button>
        </div>
        <input type="hidden" name="tipe" id="inputTipe" value="jual">

        {{-- CARD 1: Data Unit --}}
        <div class="card" style="padding:20px">
            <h3 style="font-size:.92rem;margin-bottom:16px"><i class="fas fa-mobile-alt" style="color:var(--primary);margin-right:6px"></i> Data Unit Handphone</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Merk</label>
                    <select name="merk" id="jbMerk" class="form-input" onchange="onMerkChange();updatePreview()">
                        <option value="">— Pilih Merk —</option>
                        @foreach($merkList as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Model / Tipe</label>
                    <input type="text" name="model" id="jbModel" class="form-input" placeholder="Contoh: 13 Pro Max, A52s" oninput="updatePreview()">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Nama HP (otomatis jika kosong)</label>
                    <input type="text" name="hp" id="jbHp" class="form-input" placeholder="Otomatis dari Merk + Model" oninput="updatePreview()">
                </div>
                <div class="form-group">
                    <label>Warna</label>
                    <input type="text" name="warna" id="jbWarna" class="form-input" placeholder="Contoh: Hitam, Blue" oninput="updatePreview()">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>RAM</label>
                    <input type="text" name="ram" id="jbRam" class="form-input" placeholder="Contoh: 8GB" oninput="updatePreview()">
                </div>
                <div class="form-group">
                    <label>Kapasitas Penyimpanan</label>
                    <input type="text" name="kapasitas" id="jbKapasitas" class="form-input" placeholder="Contoh: 256GB" oninput="updatePreview()">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>IMEI 1</label>
                    <input type="text" name="imei" id="jbImei" class="form-input" maxlength="20" placeholder="15 digit IMEI" oninput="updatePreview()">
                </div>
                <div class="form-group">
                    <label>IMEI 2 (Dual SIM)</label>
                    <input type="text" name="imei2" id="jbImei2" class="form-input" maxlength="20" placeholder="Opsional" oninput="updatePreview()">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Serial Number / SKU</label>
                    <input type="text" name="serial_number" id="jbSerial" class="form-input" maxlength="60" placeholder="Serial number perangkat">
                </div>
                <div class="form-group">
                    <label>Battery Health (%) — khusus iPhone</label>
                    <input type="number" name="battery_health" id="jbBattery" class="form-input" min="0" max="100" placeholder="0-100" oninput="updatePreview()">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Kondisi Fisik</label>
                    <select name="kondisi" id="jbKondisi" class="form-input" onchange="updatePreview()">
                        <option value="Second">Second</option>
                        <option value="Mulus">Mulus</option>
                        <option value="Pemilik">Pemilik</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kelengkapan</label>
                    <input type="text" name="kelengkapan" id="jbKelengkapan" class="form-input" placeholder="Contoh: Dus, Charger, Kabel" oninput="updatePreview()">
                </div>
            </div>
        </div>

        {{-- CARD 2: Foto Unit --}}
        <div class="card" style="padding:20px;margin-top:16px">
            <h3 style="font-size:.92rem;margin-bottom:14px"><i class="fas fa-camera" style="color:var(--info);margin-right:6px"></i> Foto Unit</h3>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
                @php $fotos = ['foto_depan' => 'Depan', 'foto_belakang' => 'Belakang', 'foto_samping' => 'Samping', 'foto_imei' => 'IMEI']; @endphp
                @foreach($fotos as $field => $label)
                <div>
                    <label style="font-size:.74rem;font-weight:600;color:#64748b">{{ $label }}</label>
                    <label class="jb-foto-thumb" id="thumb_{{ $field }}" for="input_{{ $field }}">
                        <i class="fas fa-image"></i>
                    </label>
                    <input type="file" name="{{ $field }}" id="input_{{ $field }}" accept="image/*" style="display:none" onchange="previewFoto(this,'{{ $field }}')">
                    <div style="font-size:.66rem;color:#94a3b8;text-align:center;margin-top:3px">Klik untuk upload</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- CARD 3: Checklist Kondisi --}}
        <div class="card" style="padding:20px;margin-top:16px">
            <h3 style="font-size:.92rem;margin-bottom:4px"><i class="fas fa-clipboard-check" style="color:var(--success);margin-right:6px"></i> Checklist Kondisi Unit</h3>
            <div style="font-size:.74rem;color:#94a3b8;margin-bottom:12px">Centang status pemeriksaan tiap komponen.</div>
            <div class="jb-checklist" id="checklistWrap">
                @php
                    $checklistItems = [
                        'face_id' => 'Face ID / Fingerprint',
                        'lcd' => 'LCD',
                        'touchscreen' => 'Touchscreen',
                        'kamera_depan' => 'Kamera Depan',
                        'kamera_belakang' => 'Kamera Belakang',
                        'speaker' => 'Speaker',
                        'mikrofon' => 'Mikrofon',
                        'wifi' => 'WiFi',
                        'bluetooth' => 'Bluetooth',
                        'sinyal' => 'Sinyal',
                        'charging' => 'Charging',
                        'getar' => 'Getar',
                        'flash' => 'Flash',
                        'battery_health' => 'Battery Health',
                    ];
                @endphp
                @foreach($checklistItems as $key => $label)
                <div class="jb-check-item" id="ci_{{ $key }}">
                    <span>{{ $label }}</span>
                    <select name="checklist_kondisi[{{ $key }}]" onchange="onChecklistChange('{{ $key }}', this.value)">
                        <option value="Belum Dicek">Belum Dicek</option>
                        <option value="Normal">Normal</option>
                        <option value="Rusak">Rusak</option>
                    </select>
                </div>
                @endforeach
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
                <label style="font-size:.8rem;font-weight:600;color:#64748b">Status Pemeriksaan:</label>
                <select name="status_pemeriksaan" id="jbStatusPemeriksaan" class="form-input" style="width:auto;padding:6px 10px;font-size:.8rem" onchange="updatePreview()">
                    <option value="Belum Dicek">Belum Dicek</option>
                    <option value="Normal">Normal</option>
                    <option value="Rusak">Rusak</option>
                </select>
                <button type="button" class="btn btn-secondary btn-xs" onclick="autoSetStatusPemeriksaan()"><i class="fas fa-magic"></i> Otomatis</button>
            </div>
        </div>

        {{-- CARD 4: Harga & Pembayaran --}}
        <div class="card" style="padding:20px;margin-top:16px">
            <h3 style="font-size:.92rem;margin-bottom:16px"><i class="fas fa-money-bill-wave" style="color:var(--success);margin-right:6px"></i> Harga & Estimasi Laba</h3>
            <div class="form-row">
                <div class="form-group">
                    <label id="lblHargaBeli">Harga Beli / Modal (Rp)</label>
                    <input type="text" inputmode="numeric" name="harga_beli" id="jbHargaBeli" class="form-input" min="0" placeholder="0" data-format-rupiah oninput="updatePreview()">
                </div>
                <div class="form-group">
                    <label id="lblHargaJual">Harga Jual (Rp)</label>
                    <input type="text" inputmode="numeric" name="harga_jual" id="jbHargaJual" class="form-input" min="0" placeholder="0" data-format-rupiah oninput="updatePreview()">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Modal Total (Rp) — otomatis dari Harga Beli</label>
                    <input type="text" inputmode="numeric" name="modal_total" id="jbModalTotal" class="form-input" min="0" placeholder="0" data-format-rupiah oninput="updatePreview()">
                </div>
                <div class="form-group">
                    <label>Estimasi Laba / Rugi</label>
                    <div id="estimasiLabaBox" style="padding:10px 14px;border-radius:8px;background:#f1f5f9;color:#64748b;font-weight:700;font-size:1rem">Rp 0</div>
                </div>
            </div>

            {{-- Metode Pembayaran --}}
            <div style="margin-top:8px">
                <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:8px">Metode Pembayaran *</label>
                <div style="display:flex;gap:8px">
                    <div class="jb-payment-option selected" onclick="selectPaymentJB('Cash')" data-method="Cash">
                        <i class="fas fa-money-bill-wave" style="display:block;font-size:1.2rem;margin-bottom:4px"></i> Cash
                    </div>
                    <div class="jb-payment-option" onclick="selectPaymentJB('Transfer')" data-method="Transfer">
                        <i class="fas fa-university" style="display:block;font-size:1.2rem;margin-bottom:4px"></i> Transfer
                    </div>
                    <div class="jb-payment-option" onclick="selectPaymentJB('QRIS')" data-method="QRIS">
                        <i class="fas fa-qrcode" style="display:block;font-size:1.2rem;margin-bottom:4px"></i> QRIS
                    </div>
                </div>
                <input type="hidden" name="metode_bayar" id="inputMetode" value="Cash">
            </div>
        </div>

        {{-- CARD 5: Status Unit & Garansi --}}
        <div class="card" style="padding:20px;margin-top:16px">
            <h3 style="font-size:.92rem;margin-bottom:16px"><i class="fas fa-tags" style="color:var(--accent);margin-right:6px"></i> Status Unit & Garansi</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Status Unit</label>
                    <select name="status_unit" id="jbStatusUnit" class="form-input" onchange="updatePreview()">
                        <option value="Ready Dijual">Ready Dijual</option>
                        <option value="Booking">Booking</option>
                        <option value="Sedang Diservis">Sedang Diservis</option>
                        <option value="Terjual">Terjual</option>
                        <option value="Retur">Retur</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Garansi Penjualan</label>
                    <select name="garansi" id="jbGaransi" class="form-input" onchange="updatePreview()">
                        <option value="Tanpa Garansi">Tanpa Garansi</option>
                        <option value="Garansi 7 Hari">Garansi 7 Hari</option>
                        <option value="Garansi 30 Hari">Garansi 30 Hari</option>
                        <option value="Garansi 90 Hari">Garansi 90 Hari</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- CARD 6: Pelanggan --}}
        <div class="card" style="padding:20px;margin-top:16px">
            <h3 style="font-size:.92rem;margin-bottom:16px"><i class="fas fa-user" style="color:var(--info);margin-right:6px"></i> Pelanggan / Penjual</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Nama</label>
                    <input type="text" name="pelanggan" id="jbPelanggan" class="form-input" placeholder="Nama pelanggan/penjual..." oninput="updatePreview()">
                </div>
                <div class="form-group">
                    <label>No HP</label>
                    <input type="text" name="no_hp_pelanggan" id="jbNoHp" class="form-input" placeholder="08xxxxxxxxxx">
                </div>
            </div>
            <div class="form-group">
                <label>Catatan</label>
                <textarea name="catatan" id="jbCatatan" class="form-input" rows="2" placeholder="Catatan tambahan..." style="resize:vertical" oninput="updatePreview()"></textarea>
            </div>
        </div>

        <input type="hidden" name="tanggal" value="{{ now()->format('Y-m-d') }}">

        <div style="display:flex;gap:10px;margin-top:16px">
            <button type="submit" class="btn btn-primary" style="padding:12px 24px;font-size:.92rem" id="btnSubmitJB">
                <i class="fas fa-check-circle"></i> Simpan Transaksi
            </button>
            <a href="{{ route('jualbeli.index') }}" class="btn btn-secondary" style="padding:12px 24px">Batal</a>
        </div>
    </div>

    <!-- RIGHT: Preview / Receipt -->
    <div class="jb-preview">
        <div class="jb-preview-header">
            <div style="display:flex;align-items:center;gap:8px">
                <i class="fas fa-receipt" style="font-size:1.1rem"></i>
                <div>
                    <div style="font-weight:700;font-size:.92rem">Preview Nota</div>
                    <div style="font-size:.7rem;opacity:.8">Perkiraan struk transaksi</div>
                </div>
            </div>
        </div>
        <div class="jb-preview-body">
            <div style="text-align:center;margin-bottom:12px">
                <div style="font-weight:800;font-size:.92rem;color:var(--primary)">FIXPRO</div>
                <div style="font-size:.68rem;color:#94a3b8">{{ auth()->user()->cabang?->nama ?? 'Service Center' }}</div>
            </div>
            <div style="text-align:center;margin-bottom:12px">
                <span id="previewBadge" style="display:inline-block;padding:4px 16px;border-radius:20px;font-size:.78rem;font-weight:700;background:#dcfce7;color:#166534">JUAL HP</span>
            </div>
            <div style="border-top:1px dashed #e2e8f0;padding-top:10px">
                <div class="preview-row"><span style="color:#64748b">Tanggal</span><span id="prevTanggal">{{ now()->format('d/m/Y') }}</span></div>
                <div class="preview-row"><span style="color:#64748b">Unit</span><span id="prevHp" style="font-weight:600">-</span></div>
                <div class="preview-row" id="prevWarnaRow" style="display:none"><span style="color:#64748b">Warna</span><span id="prevWarna">-</span></div>
                <div class="preview-row" id="prevSpekRow" style="display:none"><span style="color:#64748b">RAM/Kapasitas</span><span id="prevSpek">-</span></div>
                <div class="preview-row" id="prevImeiRow" style="display:none"><span style="color:#64748b">IMEI</span><span id="prevImei">-</span></div>
                <div class="preview-row" id="prevBatteryRow" style="display:none"><span style="color:#64748b">Battery</span><span id="prevBattery">-</span></div>
                <div class="preview-row" id="prevKondisiRow" style="display:none"><span style="color:#64748b">Kondisi</span><span id="prevKondisi">-</span></div>
                <div class="preview-row" id="prevKelengkapanRow" style="display:none"><span style="color:#64748b">Kelengkapan</span><span id="prevKelengkapan">-</span></div>
                <div class="preview-row" id="prevStatusPemeriksaanRow" style="display:none"><span style="color:#64748b">Pemeriksaan</span><span id="prevStatusPemeriksaan">-</span></div>
                <div class="preview-row" id="prevStatusUnitRow"><span style="color:#64748b">Status Unit</span><span id="prevStatusUnit">Ready Dijual</span></div>
                <div class="preview-row" id="prevGaransiRow"><span style="color:#64748b">Garansi</span><span id="prevGaransi">Tanpa Garansi</span></div>
                <div class="preview-row" id="prevPelangganRow" style="display:none"><span style="color:#64748b">Pelanggan</span><span id="prevPelanggan">-</span></div>
                <div class="preview-row"><span style="color:#64748b">Metode</span><span id="prevMetode" style="font-weight:600">Cash</span></div>
            </div>
            <div class="preview-row" style="margin-top:8px"><span style="color:#64748b">Harga Beli</span><span id="prevHargaBeli">Rp 0</span></div>
            <div class="preview-row"><span style="color:#64748b">Harga Jual</span><span id="prevHargaJual">Rp 0</span></div>
            <div class="preview-total">
                <span id="previewLabelTotal">Estimasi Laba</span>
                <span id="prevTotal" style="color:#166534">Rp 0</span>
            </div>
            <div id="prevCatatanRow" style="display:none;padding:8px;background:#f8fafc;border-radius:6px;font-size:.72rem;color:#64748b;margin-top:8px">
                <strong>Catatan:</strong> <span id="prevCatatan">-</span>
            </div>
        </div>
    </div>
</div>
</form>

<script>
let currentTipe = 'jual';
let currentMetode = 'Cash';

function setTipe(tipe) {
    currentTipe = tipe;
    document.getElementById('inputTipe').value = tipe;

    const btnJual = document.getElementById('btnJual');
    const btnBeli = document.getElementById('btnBeli');

    if (tipe === 'jual') {
        btnJual.className = 'jb-type-btn active-jual';
        btnBeli.className = 'jb-type-btn';
        document.getElementById('previewBadge').textContent = 'JUAL HP';
        document.getElementById('previewBadge').style.background = '#dcfce7';
        document.getElementById('previewBadge').style.color = '#166534';
        document.getElementById('jbStatusUnit').value = 'Terjual';
    } else {
        btnBeli.className = 'jb-type-btn active-beli';
        btnJual.className = 'jb-type-btn';
        document.getElementById('previewBadge').textContent = 'BELI HP';
        document.getElementById('previewBadge').style.background = '#dbeafe';
        document.getElementById('previewBadge').style.color = '#1e40af';
        document.getElementById('jbStatusUnit').value = 'Ready Dijual';
    }
    updatePreview();
}

function selectPaymentJB(method) {
    currentMetode = method;
    document.getElementById('inputMetode').value = method;
    document.querySelectorAll('.jb-payment-option').forEach(el => {
        if (el.dataset.method === method) el.classList.add('selected');
        else el.classList.remove('selected');
    });
    updatePreview();
}

function formatRp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }
function num(id) { return (document.getElementById(id)?.value || '').replace(/[^0-9]/g, ''); }

function previewFoto(input, field) {
    const thumb = document.getElementById('thumb_' + field);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { thumb.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:8px">'; };
        reader.readAsDataURL(input.files[0]);
    }
}

function onMerkChange() {
    const merk = document.getElementById('jbMerk').value;
    const batteryBox = document.getElementById('jbBattery');
    // Battery health hanya relevan untuk iPhone, tetap tampil untuk semua
    if (merk && merk.indexOf('iPhone') !== -1) {
        batteryBox.focus();
    }
}

function onChecklistChange(key, val) {
    const el = document.getElementById('ci_' + key);
    el.classList.remove('ch-ok', 'ch-bad');
    if (val === 'Normal') el.classList.add('ch-ok');
    else if (val === 'Rusak') el.classList.add('ch-bad');
    autoSetStatusPemeriksaan(true);
}

function autoSetStatusPemeriksaan(silent) {
    let normal = 0, rusak = 0, belum = 0;
    document.querySelectorAll('#checklistWrap select').forEach(s => {
        if (s.value === 'Normal') normal++;
        else if (s.value === 'Rusak') rusak++;
        else belum++;
    });
    let hasil = 'Belum Dicek';
    if (belum === 0) hasil = rusak > 0 ? 'Rusak' : 'Normal';
    else if (rusak > 0 && normal > 0) hasil = 'Rusak';
    const sel = document.getElementById('jbStatusPemeriksaan');
    if (silent || confirm('Set status pemeriksaan ke "' + hasil + '"?')) sel.value = hasil;
    updatePreview();
}

function updatePreview() {
    const hp = document.getElementById('jbHp').value;
    const merk = document.getElementById('jbMerk').value;
    const model = document.getElementById('jbModel').value;
    const warna = document.getElementById('jbWarna').value;
    const ram = document.getElementById('jbRam').value;
    const kapasitas = document.getElementById('jbKapasitas').value;
    const imei = document.getElementById('jbImei').value;
    const imei2 = document.getElementById('jbImei2').value;
    const battery = document.getElementById('jbBattery').value;
    const kondisi = document.getElementById('jbKondisi').value;
    const kelengkapan = document.getElementById('jbKelengkapan').value;
    const pelanggan = document.getElementById('jbPelanggan').value;
    const catatan = document.getElementById('jbCatatan').value;
    const statusUnit = document.getElementById('jbStatusUnit').value;
    const garansi = document.getElementById('jbGaransi').value;
    const statusPemeriksaan = document.getElementById('jbStatusPemeriksaan').value;
    const hargaBeli = parseFloat(num('jbHargaBeli') || num('jbModalTotal') || '0');
    const hargaJual = parseFloat(num('jbHargaJual') || '0');
    const modalTotal = parseFloat(num('jbModalTotal') || num('jbHargaBeli') || '0');

    // Auto-fill nama HP
    const displayHp = hp || [merk, model].filter(Boolean).join(' ') || '-';
    document.getElementById('prevHp').textContent = displayHp;

    document.getElementById('prevWarnaRow').style.display = warna ? '' : 'none';
    document.getElementById('prevWarna').textContent = warna;
    const spek = [ram, kapasitas].filter(Boolean).join(' / ');
    document.getElementById('prevSpekRow').style.display = spek ? '' : 'none';
    document.getElementById('prevSpek').textContent = spek || '-';
    const imeiFull = [imei, imei2].filter(Boolean).join(' / ');
    document.getElementById('prevImeiRow').style.display = imeiFull ? '' : 'none';
    document.getElementById('prevImei').textContent = imeiFull;
    document.getElementById('prevBatteryRow').style.display = battery ? '' : 'none';
    document.getElementById('prevBattery').textContent = battery + '%';
    document.getElementById('prevKondisiRow').style.display = kondisi && kondisi !== 'Second' ? '' : 'none';
    document.getElementById('prevKondisi').textContent = kondisi;
    document.getElementById('prevKelengkapanRow').style.display = kelengkapan ? '' : 'none';
    document.getElementById('prevKelengkapan').textContent = kelengkapan;
    document.getElementById('prevStatusPemeriksaanRow').style.display = statusPemeriksaan && statusPemeriksaan !== 'Belum Dicek' ? '' : 'none';
    document.getElementById('prevStatusPemeriksaan').textContent = statusPemeriksaan;
    document.getElementById('prevStatusUnit').textContent = statusUnit;
    document.getElementById('prevGaransi').textContent = garansi;
    document.getElementById('prevPelangganRow').style.display = pelanggan ? '' : 'none';
    document.getElementById('prevPelanggan').textContent = pelanggan;
    document.getElementById('prevCatatanRow').style.display = catatan ? '' : 'none';
    document.getElementById('prevCatatan').textContent = catatan;
    document.getElementById('prevMetode').textContent = currentMetode;

    document.getElementById('prevHargaBeli').textContent = formatRp(hargaBeli);
    document.getElementById('prevHargaJual').textContent = formatRp(hargaJual);

    // Estimasi laba
    const modal = modalTotal || hargaBeli;
    const laba = hargaJual - modal;
    const labaBox = document.getElementById('estimasiLabaBox');
    const prevTotal = document.getElementById('prevTotal');
    if (hargaJual > 0) {
        labaBox.textContent = (laba >= 0 ? '+ ' : '- ') + formatRp(Math.abs(laba));
        labaBox.style.background = laba >= 0 ? '#dcfce7' : '#fee2e2';
        labaBox.style.color = laba >= 0 ? '#166534' : '#991b1b';
        prevTotal.textContent = (laba >= 0 ? '+ ' : '- ') + formatRp(Math.abs(laba));
        prevTotal.style.color = laba >= 0 ? '#166534' : '#991b1b';
        document.getElementById('previewLabelTotal').textContent = 'Estimasi Laba';
    } else {
        labaBox.textContent = formatRp(modal);
        labaBox.style.background = '#dbeafe';
        labaBox.style.color = '#1e40af';
        prevTotal.textContent = formatRp(modal);
        prevTotal.style.color = '#1e40af';
        document.getElementById('previewLabelTotal').textContent = 'Modal';
    }

    // Sync modal_total otomatis dari harga_beli bila kosong
    const modalInput = document.getElementById('jbModalTotal');
    if (!modalInput.value && hargaBeli > 0) modalInput.value = hargaBeli.toLocaleString('id-ID');
}

// Init
updatePreview();
</script>
@endsection
