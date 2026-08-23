@extends('layouts.app')
@section('title', 'Edit Transaksi Jual Beli')

@section('content')
<style>
.jb-foto-thumb { width: 100%; height: 90px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 1.5rem; cursor: pointer; overflow: hidden; }
.jb-checklist { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 8px; }
.jb-check-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: .76rem; transition: all .2s; }
.jb-check-item select { padding: 4px 6px; font-size: .7rem; border-radius: 6px; border: 1px solid #e2e8f0; }
.ch-ok { border-color: #bbf7d0 !important; background: #f0fdf4 !important; }
.ch-bad { border-color: #fecaca !important; background: #fef2f2 !important; }
.jb-pm-opt { transition: all .2s; }
.jb-pm-opt:hover { border-color: var(--primary) !important; }
</style>

<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-edit" style="color:var(--primary);margin-right:6px"></i> Edit Transaksi {{ $jualBeli->kode ?? $jualBeli->id }}</h2>
    <a href="{{ route('jualbeli.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="padding:20px">
    <form method="POST" action="{{ route('jualbeli.update', $jualBeli) }}" enctype="multipart/form-data" id="formJualBeli">
        @csrf @method('PUT')

        <div style="display:flex;gap:0;margin-bottom:20px;border-radius:12px;overflow:hidden;border:2px solid #e2e8f0">
            <button type="button" id="btnTipeJual" style="flex:1;padding:12px;font-size:.88rem;font-weight:700;cursor:pointer;border:none;background:{{ $jualBeli->tipe === 'jual' ? '#dcfce7' : '#fff' }};color:{{ $jualBeli->tipe === 'jual' ? '#166534' : '#64748b' }};display:flex;align-items:center;justify-content:center;gap:6px" onclick="setTipeEdit('jual', this)">
                <i class="fas fa-arrow-up"></i> JUAL HP
            </button>
            <button type="button" id="btnTipeBeli" style="flex:1;padding:12px;font-size:.88rem;font-weight:700;cursor:pointer;border:none;background:{{ $jualBeli->tipe === 'beli' ? '#dbeafe' : '#fff' }};color:{{ $jualBeli->tipe === 'beli' ? '#1e40af' : '#64748b' }};display:flex;align-items:center;justify-content:center;gap:6px" onclick="setTipeEdit('beli', this)">
                <i class="fas fa-arrow-down"></i> BELI HP
            </button>
        </div>
        <input type="hidden" name="tipe" id="inputTipe" value="{{ $jualBeli->tipe }}">

        {{-- Data Unit --}}
        <h3 style="font-size:.92rem;margin-bottom:12px"><i class="fas fa-mobile-alt" style="color:var(--primary);margin-right:6px"></i> Data Unit Handphone</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Merk</label>
                <select name="merk" id="jbMerk" class="form-input">
                    <option value="">— Pilih Merk —</option>
                    @foreach($merkList as $m)
                    <option value="{{ $m }}" {{ $jualBeli->merk === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Model / Tipe</label>
                <input type="text" name="model" class="form-input" value="{{ $jualBeli->model }}" placeholder="Contoh: 13 Pro Max">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Nama HP</label><input type="text" name="hp" class="form-input" value="{{ $jualBeli->hp }}" required></div>
            <div class="form-group"><label>Warna</label><input type="text" name="warna" class="form-input" value="{{ $jualBeli->warna }}" placeholder="Hitam, Blue"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>RAM</label><input type="text" name="ram" class="form-input" value="{{ $jualBeli->ram }}" placeholder="8GB"></div>
            <div class="form-group"><label>Kapasitas</label><input type="text" name="kapasitas" class="form-input" value="{{ $jualBeli->kapasitas }}" placeholder="256GB"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>IMEI 1</label><input type="text" name="imei" class="form-input" value="{{ $jualBeli->imei }}" maxlength="20"></div>
            <div class="form-group"><label>IMEI 2</label><input type="text" name="imei2" class="form-input" value="{{ $jualBeli->imei2 }}" maxlength="20"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Serial Number</label><input type="text" name="serial_number" class="form-input" value="{{ $jualBeli->serial_number }}" maxlength="60"></div>
            <div class="form-group"><label>Battery Health (%)</label><input type="number" name="battery_health" class="form-input" value="{{ $jualBeli->battery_health }}" min="0" max="100"></div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Kondisi Fisik</label>
                <select name="kondisi" class="form-input">
                    <option value="Second" {{ ($jualBeli->kondisi ?? 'Second') === 'Second' ? 'selected' : '' }}>Second</option>
                    <option value="Mulus" {{ ($jualBeli->kondisi ?? '') === 'Mulus' ? 'selected' : '' }}>Mulus</option>
                    <option value="Pemilik" {{ ($jualBeli->kondisi ?? '') === 'Pemilik' ? 'selected' : '' }}>Pemilik</option>
                </select>
            </div>
            <div class="form-group"><label>Kelengkapan</label><input type="text" name="kelengkapan" class="form-input" value="{{ $jualBeli->kelengkapan ?? '' }}" placeholder="Dus, Charger, Kabel"></div>
        </div>

        {{-- Foto Unit --}}
        <h3 style="font-size:.92rem;margin:18px 0 12px"><i class="fas fa-camera" style="color:var(--info);margin-right:6px"></i> Foto Unit</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:12px;margin-bottom:16px">
            @php $fotos = ['foto_depan' => 'Depan', 'foto_belakang' => 'Belakang', 'foto_samping' => 'Samping', 'foto_imei' => 'IMEI']; @endphp
            @foreach($fotos as $field => $label)
            <div>
                <label style="font-size:.74rem;font-weight:600;color:#64748b">{{ $label }}</label>
                <label class="jb-foto-thumb" for="input_{{ $field }}" id="thumb_{{ $field }}">
                    @if($jualBeli->{$field})
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($jualBeli->{$field}) }}" style="width:100%;height:100%;object-fit:cover" id="img_{{ $field }}">
                    @else
                    <i class="fas fa-image" id="icon_{{ $field }}"></i>
                    @endif
                </label>
                <input type="file" name="{{ $field }}" id="input_{{ $field }}" accept="image/*" style="display:none" onchange="previewFoto(this,'{{ $field }}')">
                <div style="font-size:.66rem;color:#94a3b8;text-align:center;margin-top:3px">Klik untuk ganti</div>
            </div>
            @endforeach
        </div>

        {{-- Checklist Kondisi --}}
        <h3 style="font-size:.92rem;margin:6px 0 12px"><i class="fas fa-clipboard-check" style="color:var(--success);margin-right:6px"></i> Checklist Kondisi</h3>
        @php
            $checklistItems = [
                'face_id' => 'Face ID / Fingerprint', 'lcd' => 'LCD', 'touchscreen' => 'Touchscreen',
                'kamera_depan' => 'Kamera Depan', 'kamera_belakang' => 'Kamera Belakang', 'speaker' => 'Speaker',
                'mikrofon' => 'Mikrofon', 'wifi' => 'WiFi', 'bluetooth' => 'Bluetooth', 'sinyal' => 'Sinyal',
                'charging' => 'Charging', 'getar' => 'Getar', 'flash' => 'Flash', 'battery_health' => 'Battery Health',
            ];
            $savedChecklist = $jualBeli->checklist_kondisi ?? [];
        @endphp
        <div class="jb-checklist" id="checklistWrap">
            @foreach($checklistItems as $key => $label)
            @php $val = $savedChecklist[$key] ?? 'Belum Dicek'; @endphp
            <div class="jb-check-item {{ $val==='Normal'?'ch-ok':($val==='Rusak'?'ch-bad':'') }}" id="ci_{{ $key }}">
                <span>{{ $label }}</span>
                <select name="checklist_kondisi[{{ $key }}]" onchange="onChecklistChange('{{ $key }}', this.value)">
                    <option value="Belum Dicek" {{ $val==='Belum Dicek'?'selected':'' }}>Belum Dicek</option>
                    <option value="Normal" {{ $val==='Normal'?'selected':'' }}>Normal</option>
                    <option value="Rusak" {{ $val==='Rusak'?'selected':'' }}>Rusak</option>
                </select>
            </div>
            @endforeach
        </div>
        <div style="margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <label style="font-size:.8rem;font-weight:600;color:#64748b">Status Pemeriksaan:</label>
            <select name="status_pemeriksaan" id="jbStatusPemeriksaan" class="form-input" style="width:auto;padding:6px 10px;font-size:.8rem">
                <option value="Belum Dicek" {{ ($jualBeli->status_pemeriksaan ?? 'Belum Dicek')==='Belum Dicek'?'selected':'' }}>Belum Dicek</option>
                <option value="Normal" {{ ($jualBeli->status_pemeriksaan ?? '')==='Normal'?'selected':'' }}>Normal</option>
                <option value="Rusak" {{ ($jualBeli->status_pemeriksaan ?? '')==='Rusak'?'selected':'' }}>Rusak</option>
            </select>
            <button type="button" class="btn btn-secondary btn-xs" onclick="autoSetStatusPemeriksaan()"><i class="fas fa-magic"></i> Otomatis</button>
        </div>

        {{-- Harga --}}
        <h3 style="font-size:.92rem;margin:18px 0 12px"><i class="fas fa-money-bill-wave" style="color:var(--success);margin-right:6px"></i> Harga</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Harga Beli / Modal (Rp)</label>
                <input type="text" inputmode="numeric" name="harga_beli" id="jbHargaBeli" class="form-input" value="{{ $jualBeli->harga_beli ? number_format((int)$jualBeli->harga_beli, 0, ',', '.') : '' }}" data-format-rupiah oninput="formatRupiah(this); updateLaba()">
            </div>
            <div class="form-group">
                <label>Harga Jual (Rp)</label>
                <input type="text" inputmode="numeric" name="harga_jual" id="jbHargaJual" class="form-input" value="{{ $jualBeli->harga_jual ? number_format((int)$jualBeli->harga_jual, 0, ',', '.') : '' }}" data-format-rupiah oninput="formatRupiah(this); updateLaba()">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Modal Total (Rp)</label>
                <input type="text" inputmode="numeric" name="modal_total" id="jbModalTotal" class="form-input" value="{{ $jualBeli->modal_total ? number_format((int)$jualBeli->modal_total, 0, ',', '.') : '' }}" data-format-rupiah oninput="formatRupiah(this); updateLaba()">
            </div>
            <div class="form-group">
                <label>Estimasi Laba / Rugi</label>
                <div id="estimasiLabaBox" style="padding:10px 14px;border-radius:8px;background:#f1f5f9;color:#64748b;font-weight:700;font-size:1rem">Rp 0</div>
            </div>
        </div>

        {{-- Metode Pembayaran --}}
        <div style="margin-top:12px;margin-bottom:16px">
            <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:8px">Metode Pembayaran *</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @foreach(['Cash','Transfer','QRIS'] as $m)
                <label style="flex:1;min-width:100px;cursor:pointer">
                    <input type="radio" name="metode_bayar" value="{{ $m }}" {{ ($jualBeli->metode_bayar ?? 'Cash') === $m ? 'checked' : '' }} style="display:none" onchange="selectPM('{{ $m }}')">
                    <div class="jb-pm-opt" data-m="{{ $m }}" style="padding:10px;border-radius:10px;border:2px solid {{ ($jualBeli->metode_bayar ?? 'Cash') === $m ? 'var(--primary)' : '#e2e8f0' }};text-align:center;font-weight:{{ ($jualBeli->metode_bayar ?? 'Cash') === $m ? '700' : '600' }};background:{{ ($jualBeli->metode_bayar ?? 'Cash') === $m ? 'var(--primary-bg)' : '#fff' }};color:{{ ($jualBeli->metode_bayar ?? 'Cash') === $m ? 'var(--primary)' : '#64748b' }};transition:all .2s;font-size:.82rem">
                        <i class="fas fa-{{ $m==='Cash'?'money-bill-wave':($m==='Transfer'?'university':'qrcode') }}"></i> {{ $m }}
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Status Unit & Garansi --}}
        <h3 style="font-size:.92rem;margin:6px 0 12px"><i class="fas fa-tags" style="color:var(--accent);margin-right:6px"></i> Status Unit & Garansi</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Status Unit</label>
                <select name="status_unit" class="form-input">
                    @foreach(['Ready Dijual','Booking','Sedang Diservis','Terjual','Retur'] as $su)
                    <option value="{{ $su }}" {{ ($jualBeli->status_unit ?? 'Ready Dijual') === $su ? 'selected' : '' }}>{{ $su }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Garansi Penjualan</label>
                <select name="garansi" class="form-input">
                    @foreach(['Tanpa Garansi','Garansi 7 Hari','Garansi 30 Hari','Garansi 90 Hari'] as $g)
                    <option value="{{ $g }}" {{ ($jualBeli->garansi ?? 'Tanpa Garansi') === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @if($jualBeli->garansi_hingga)
        <div style="font-size:.74rem;color:#64748b;margin-top:-8px;margin-bottom:12px">
            <i class="fas fa-shield-alt"></i> Garansi berlaku hingga: <strong>{{ $jualBeli->garansi_hingga->format('d/m/Y') }}</strong>
        </div>
        @endif

        {{-- Riwayat harga --}}
        @if(!empty($jualBeli->riwayat_harga))
        <h3 style="font-size:.92rem;margin:18px 0 12px"><i class="fas fa-history" style="color:var(--info);margin-right:6px"></i> Riwayat Harga</h3>
        <div class="table-wrap" style="margin-bottom:16px;overflow-x:auto">
            <table style="font-size:.78rem;width:100%;min-width:400px">
                <thead><tr><th>Tanggal</th><th>Harga Beli</th><th>Harga Jual</th><th>Keterangan</th></tr></thead>
                <tbody>
                @foreach($jualBeli->riwayat_harga as $rh)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($rh['tanggal'])->format('d/m/Y H:i') }}</td>
                    <td>Rp {{ number_format($rh['harga_beli'] ?? 0, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($rh['harga_jual'] ?? 0, 0, ',', '.') }}</td>
                    <td>{{ $rh['keterangan'] ?? '-' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Pelanggan --}}
        <div class="form-row">
            <div class="form-group"><label>Tanggal *</label><input type="date" name="tanggal" class="form-input" value="{{ $jualBeli->tanggal?->format('Y-m-d') }}" required></div>
            <div class="form-group"><label>Pelanggan</label><input type="text" name="pelanggan" class="form-input" value="{{ $jualBeli->pelanggan }}"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>No HP Pelanggan</label><input type="text" name="no_hp_pelanggan" class="form-input" value="{{ $jualBeli->no_hp_pelanggan ?? '' }}"></div>
            <div class="form-group"><label>Catatan</label><input type="text" name="catatan" class="form-input" value="{{ $jualBeli->catatan }}"></div>
        </div>

        <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Transaksi</button>
            <a href="{{ route('jualbeli.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<script>
// ========== Format Rupiah ==========
function formatRupiah(input) {
    let val = input.value.replace(/[^0-9]/g, '');
    if (val) {
        val = parseInt(val, 10).toLocaleString('id-ID');
    }
    input.value = val;
}

function numRupiah(id) {
    const val = (document.getElementById(id)?.value || '').replace(/[^0-9]/g, '');
    return val ? parseInt(val, 10) : 0;
}

// ========== Tipe Jual/Beli ==========
function setTipeEdit(tipe, btn) {
    document.getElementById('inputTipe').value = tipe;
    const jual = tipe === 'jual';
    
    const btnJual = document.getElementById('btnTipeJual');
    const btnBeli = document.getElementById('btnTipeBeli');
    
    btnJual.style.background = jual ? '#dcfce7' : '#fff';
    btnJual.style.color = jual ? '#166534' : '#64748b';
    btnBeli.style.background = jual ? '#fff' : '#dbeafe';
    btnBeli.style.color = jual ? '#64748b' : '#1e40af';
}

// ========== Metode Pembayaran ==========
function selectPM(m) {
    document.querySelectorAll('.jb-pm-opt').forEach(el => {
        const isSel = el.dataset.m === m;
        el.style.borderColor = isSel ? 'var(--primary)' : '#e2e8f0';
        el.style.background = isSel ? 'var(--primary-bg)' : '#fff';
        el.style.color = isSel ? 'var(--primary)' : '#64748b';
        el.style.fontWeight = isSel ? '700' : '600';
    });
}

// ========== Estimasi Laba ==========
function updateLaba() {
    const beli = numRupiah('jbHargaBeli');
    const jual = numRupiah('jbHargaJual');
    const modal = numRupiah('jbModalTotal');
    
    const modalAktual = modal > 0 ? modal : beli;
    const laba = jual - modalAktual;
    
    const box = document.getElementById('estimasiLabaBox');
    
    if (jual > 0) {
        const prefix = laba >= 0 ? '+ ' : '- ';
        const absLaba = Math.abs(laba).toLocaleString('id-ID');
        box.textContent = prefix + 'Rp ' + absLaba;
        box.style.background = laba >= 0 ? '#dcfce7' : '#fee2e2';
        box.style.color = laba >= 0 ? '#166534' : '#991b1b';
    } else if (modalAktual > 0) {
        box.textContent = 'Modal: Rp ' + modalAktual.toLocaleString('id-ID');
        box.style.background = '#dbeafe';
        box.style.color = '#1e40af';
    } else {
        box.textContent = 'Rp 0';
        box.style.background = '#f1f5f9';
        box.style.color = '#64748b';
    }
}

// ========== Preview Foto ==========
function previewFoto(input, field) {
    const thumb = document.getElementById('thumb_' + field);
    if (!thumb) return;
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            thumb.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover" id="img_' + field + '">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ========== Checklist Kondisi ==========
function onChecklistChange(key, val) {
    const el = document.getElementById('ci_' + key);
    if (!el) return;
    
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
    if (belum === 0) {
        // Semua sudah dicek
        hasil = rusak > 0 ? 'Rusak' : 'Normal';
    } else if (rusak > 0) {
        // Ada yang rusak meskipun belum semua dicek
        hasil = 'Rusak';
    } else if (normal > 0 && belum === 0) {
        hasil = 'Normal';
    }
    
    const sel = document.getElementById('jbStatusPemeriksaan');
    if (!sel) return;
    
    if (silent) {
        sel.value = hasil;
    } else {
        if (confirm('Set status pemeriksaan ke "' + hasil + '"?')) {
            sel.value = hasil;
        }
    }
}

// ========== Init ==========
document.addEventListener('DOMContentLoaded', function() {
    updateLaba();
    
    // Format input rupiah pada load
    document.querySelectorAll('[data-format-rupiah]').forEach(input => {
        if (input.value) {
            // Jika belum diformat, format sekarang
            if (!input.value.includes('.')) {
                formatRupiah(input);
            }
        }
    });
});
</script>
@endsection