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
                <div id="infoPelanggan" style="display:none;padding:10px;border-radius:8px;background:#f0fdf4;border:1px solid #bbf7d0;font-size:.8rem;color:#166534;margin-bottom:8px"></div>
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
                    <input type="text" id="perangkatInput" name="perangkat" class="form-input" placeholder="Atau ketik manual (contoh: Redmi Note 13)" required style="margin-top:6px">
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

            {{-- Informasi Keamanan & Assign Teknisi --}}
            <div style="margin-top:16px;padding:16px;border-radius:8px;background:#ffffff;border:1px solid #e2e8f0;">
                <h3 style="font-size:.9rem;margin-bottom:12px;color:#334155;display:flex;align-items:center;">
                    <i class="fas fa-user-cog" style="margin-right:6px;color:var(--primary)"></i> Assign Teknisi & Keamanan Perangkat
                </h3>
                
                <div class="form-group">
                    <label>Teknisi yang Bertanggung Jawab</label>
                    <select name="teknisi_id" id="teknisiSelect" class="form-input">
                        <option value="">-- Pilih Teknisi --</option>
                        @foreach($teknisis as $t)
                        <option value="{{ $t->id }}" data-wa="{{ $t->no_wa ?? '' }}">
                            {{ $t->nama }} ({{ $t->spesialisasi }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>No. WA Teknisi</label>
                    <input type="tel" id="noHpTeknisiManual" name="no_hp_teknisi" class="form-input" placeholder="Otomatis terisi dari pilihan teknisi" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    <div class="text-xs text-muted" style="margin-top:4px">
                        <i class="fas fa-info-circle"></i> Otomatis diambil dari data teknisi di atas. Bisa diedit manual jika perlu.
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: minmax(180px, 1fr) minmax(280px, 1.8fr); gap: 12px; align-items: end; margin-bottom: 12px;">
                    <div>
                        <label>PIN / Sandi</label>
                        <input type="password" name="pin" id="pinInput" class="form-input" placeholder="Contoh: 123456" onfocus="this.type='text'" onblur="this.type='password'">
                    </div>

                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <button type="button" id="btnKirimTeknisi" class="btn btn-primary" style="justify-content:center; align-items:center; gap:8px; padding:10px 16px; font-weight:600;" onclick="kirimWaKeTeknisi()">
                            <i class="fab fa-whatsapp" style="font-size:1.1rem;"></i> Kirim Info ke Teknisi
                        </button>
                        
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:.78rem; color:#64748b; margin:0; padding:6px 8px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
                            <input type="checkbox" id="autoWaTeknisiCheckbox" name="auto_wa_teknisi" value="1" style="width:16px; height:16px; accent-color:#2563eb; cursor:pointer; flex-shrink:0;">
                            <span>Auto kirim ke WA teknisi saat tombol Simpan diklik</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 4px;">
                    <label>Gambar Pola Kunci Layar (Opsional)</label>
                    <div style="display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
                        <canvas id="patternCanvas" width="150" height="150" style="border: 2px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; cursor: crosshair; touch-action: none;"></canvas>
                        <div style="flex: 1; min-width: 200px;">
                            <input type="text" name="pola" id="polaInput" class="form-input" placeholder="Akan terisi otomatis saat menggambar" readonly style="background: #f1f5f9; font-weight: 600; color: var(--primary);">
                            <div class="text-xs text-muted" style="margin-top: 8px; line-height: 1.4;">
                                <i class="fas fa-info-circle"></i> Geser mouse/jari pada kotak titik-titik di sebelah kiri.
                            </div>
                            <button type="button" onclick="resetPattern()" class="btn btn-secondary btn-xs" style="margin-top: 8px;"><i class="fas fa-undo"></i> Reset Pola</button>
                        </div>
                    </div>
                </div>

                <div class="text-xs" style="color:#64748b;margin-top:12px;display:flex;align-items:start;gap:6px;">
                    <i class="fas fa-shield-alt" style="margin-top:2px;"></i> 
                    <span><strong>Internal Only:</strong> Data ini ditujukan khusus untuk teknisi. <strong>Jangan dicantumkan di nota/chat pelanggan.</strong></span>
                </div>
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

            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0">
                <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-camera" style="color:var(--info);margin-right:6px"></i> Foto Kondisi HP</h3>
                <div class="form-group">
                    <input type="file" name="foto[]" class="form-input" accept="image/*" multiple>
                    <div class="text-xs text-muted" style="margin-top:4px">Upload foto kondisi HP (bisa lebih dari 1)</div>
                </div>
                <div id="fotoPreview" style="display:flex;gap:8px;flex-wrap:wrap"></div>
            </div>

            <div style="margin-top:20px; padding:14px 16px; background:linear-gradient(135deg, #dcfce7, #f0fdf4); border:1px solid #bbf7d0; border-radius:10px;">
                <label style="display:flex; align-items:center; gap:12px; cursor:pointer; margin:0;">
                    <input type="checkbox" id="autoWaCheckbox" name="auto_wa" value="1" checked style="width:22px; height:22px; accent-color:#25D366; cursor:pointer;">
                    <div>
                        <div style="font-weight:700; color:#166534; font-size:.95rem; display:flex; align-items:center; gap:8px;">
                            <i class="fab fa-whatsapp" style="font-size:1.3rem; color:#25D366;"></i> Auto Kirim Nota via WhatsApp (Pelanggan)
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
        {{-- Card Fitur --}}
        <div class="card">
            <h3 style="font-size:.95rem;margin-bottom:10px"><i class="fas fa-bolt" style="color:var(--accent);margin-right:6px"></i> Fitur</h3>
            <ul style="font-size:.84rem;color:#64748b;line-height:2;padding-left:16px">
                <li><strong style="color:var(--primary)">Pilih pelanggan dari daftar</strong> atau input baru</li>
                <li>Pelanggan baru otomatis dapat akun user</li>
                <li>Auto-fill data dari No HP</li>
                <li>Tracking IMEI perangkat</li>
                <li>Estimasi waktu selesai (ETA)</li>
                <li>Sistem DP & pembayaran</li>
                <li>Kode servis auto-generate</li>
                <li><strong style="color:#2563eb">Kirim Info ke Teknisi</strong> (Auto-ambil nomor dari data teknisi)</li>
                <li><strong style="color:var(--accent)">Gambar Pola Layar Interaktif</strong> (Auto-convert ke teks)</li>
                <li><strong style="color:var(--accent)">Pilih sparepart</strong> (Admin)</li>
                <li><strong style="color:var(--info)">Upload foto kondisi HP</strong></li>
                <li><strong style="color:#25D366">Auto Kirim Nota via WhatsApp</strong> (Pelanggan & Teknisi)</li>
            </ul>
        </div>

        {{-- Card Data Teknisi Tersedia --}}
        <div class="card" style="margin-top: 16px;">
            <h3 style="font-size:.95rem;margin-bottom:12px; display:flex; align-items:center;">
                <i class="fas fa-users-cog" style="color:var(--primary);margin-right:6px"></i> Data Teknisi Tersedia
            </h3>
            <div style="max-height: 250px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                <table style="width: 100%; font-size: 0.85rem; border-collapse: collapse;">
                    <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                        <tr style="border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 10px 12px; text-align: left; color: #475569; font-weight: 600;">Nama Teknisi</th>
                            <th style="padding: 10px 12px; text-align: left; color: #475569; font-weight: 600;">No. WA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teknisis as $t)
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 10px 12px; color: #334155;">
                                <div style="font-weight: 600;">{{ $t->nama }}</div>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">{{ $t->spesialisasi ?? 'Umum' }}</div>
                            </td>
                            <td style="padding: 10px 12px; color: #0d9488; font-weight: 600; font-family: monospace; font-size: 0.9rem;">
                                {{ $t->no_wa ?? '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" style="padding: 16px; text-align: center; color: #94a3b8; font-style: italic;">
                                Belum ada data teknisi di database.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ✨ CARD BARU: Recent Servis Terbaru (DIFILTER PER CABANG) ✨ --}}
        @php
            $user = auth()->user();
            $cabangId = $user ? ($user->cabang_id ?? null) : null;
            $query = \App\Models\Servis::with('pelanggan');
            if ($cabangId) {
                $query->where('cabang_id', $cabangId);
            }
            $recentServis = $query->latest()->limit(5)->get();

            $statusColors = [
                'Selesai' => ['bg' => '#dcfce7', 'text' => '#166534'],
                'Proses'  => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                'Pending' => ['bg' => '#fef3c7', 'text' => '#92400e'],
            ];
        @endphp

        <div class="card" style="margin-top: 16px;">
            <h3 style="font-size:.95rem;margin-bottom:12px; display:flex; align-items:center;">
                <i class="fas fa-history" style="color:var(--accent);margin-right:6px"></i> Servis Terbaru (Cabang Ini)
            </h3>
            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                <table style="width: 100%; font-size: 0.85rem; border-collapse: collapse;">
                    <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                        <tr style="border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 10px 12px; text-align: left; color: #475569; font-weight: 600;">Kode</th>
                            <th style="padding: 10px 12px; text-align: left; color: #475569; font-weight: 600;">Pelanggan & Perangkat</th>
                            <th style="padding: 10px 12px; text-align: left; color: #475569; font-weight: 600;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentServis as $s)
                        @php
                            $status = $s->status ?? 'Masuk';
                            $style = $statusColors[$status] ?? ['bg' => '#f1f5f9', 'text' => '#475569'];
                        @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 10px 12px; color: #334155; font-weight: 600; font-family: monospace; font-size: 0.8rem;">
                                {{ $s->kode_servis ?? $s->kode ?? 'N/A' }}
                            </td>
                            <td style="padding: 10px 12px; color: #334155;">
                                <div style="font-weight: 600;">{{ $s->pelanggan->nama ?? $s->nama_pelanggan ?? 'Umum' }}</div>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">{{ $s->perangkat ?? '-' }}</div>
                            </td>
                            <td style="padding: 10px 12px;">
                                <span style="padding: 4px 8px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background:{{ $style['bg'] }}; color:{{ $style['text'] }};">
                                    {{ $status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" style="padding: 16px; text-align: center; color: #94a3b8; font-style: italic;">
                                Belum ada data servis di cabang ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 12px; text-align: right;">
                <a href="{{ route('servis.index') }}" style="font-size: 0.8rem; color: var(--primary); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                    Lihat Semua Servis <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        {{-- ✨ AKHIR CARD BARU ✨ --}}

    </div>
</div>

<script>
// ===== DATA MERK & TIPE HP (LENGKAP & TERUPDATE SAMPAI 2026) =====
const dataMerekHp = {
    "Apple": ["iPhone 16 Pro Max", "iPhone 16 Pro", "iPhone 16 Plus", "iPhone 16", "iPhone 15 Pro Max", "iPhone 15 Pro", "iPhone 15 Plus", "iPhone 15", "iPhone 14 Pro Max", "iPhone 14 Pro", "iPhone 14 Plus", "iPhone 14", "iPhone 13 Pro Max", "iPhone 13 Pro", "iPhone 13", "iPhone 13 Mini", "iPhone 12 Pro Max", "iPhone 12 Pro", "iPhone 12", "iPhone 12 Mini", "iPhone 11 Pro Max", "iPhone 11 Pro", "iPhone 11", "iPhone XS Max", "iPhone XS", "iPhone XR", "iPhone X", "iPhone 8 Plus", "iPhone 8", "iPhone 7 Plus", "iPhone 7", "iPhone SE (2022)", "iPhone SE (2020)", "iPhone SE (2016)"],
    "Asus": ["ROG Phone 8 Pro", "ROG Phone 8", "ROG Phone 7 Ultimate", "ROG Phone 7", "ROG Phone 6 Pro", "ROG Phone 6", "Zenfone 11 Ultra", "Zenfone 10", "Zenfone 9", "Zenfone 8 Flip", "Zenfone 8"],
    "Google": ["Pixel 8 Pro", "Pixel 8", "Pixel 8a", "Pixel 7 Pro", "Pixel 7", "Pixel 7a", "Pixel 6 Pro", "Pixel 6", "Pixel 6a", "Pixel 5", "Pixel 5a", "Pixel Fold"],
    "Honor": ["Magic 6 Pro", "Magic 6", "Magic 5 Pro", "Magic 5", "Magic 5 Lite", "Magic 4 Pro", "Magic 4", "Magic 4 Lite", "90 Pro", "90", "90 Lite", "80 Pro", "80", "80 Lite", "X9b", "X9a", "X8b", "X8a", "X7b", "X7a", "X6a"],
    "Huawei": ["P60 Pro", "P60", "P60 Art", "P50 Pro", "P50", "P50 Pocket", "P40 Pro+", "P40 Pro", "P40", "P30 Pro", "P30", "P30 Lite", "Mate 60 Pro", "Mate 60", "Mate 50 Pro", "Mate 50", "Mate 40 Pro", "Mate 40", "Nova 11 Pro", "Nova 11", "Nova 11i", "Nova 10 Pro", "Nova 10", "Nova 9 Pro", "Nova 9", "Nova 9 SE"],
    "Infinix": ["Zero 30", "Zero Ultra", "Zero 5G", "Note 40 Pro", "Note 40", "Note 30 Pro", "Note 30", "Note 12 Pro", "Note 12", "Hot 40 Pro", "Hot 40", "Hot 30i", "Hot 30", "Hot 20i", "Hot 20", "Hot 12", "Smart 3", "Smart 3 Plus", "Smart 4", "Smart 4c", "Smart 5", "Smart 5 Pro", "Smart 6", "Smart 6 Plus", "Smart 6 HD", "Smart 7", "Smart 7 HD", "Smart 7 Plus", "Smart 8", "Smart 8 HD", "Smart 8 Pro", "Smart 9", "Smart 9 HD", "Smart 10", "Smart 10 Plus", "Smart 10 HD", "Smart 20", "Smart 20 HD", "Smart 20 Pro"],
    "Itel": ["P55+", "P55", "P55 5G", "P40+", "P40", "P38", "A70", "A70 5G", "A60s", "A60", "A58", "S23", "S23+", "S18", "S17"],
    "Motorola": ["Edge 50 Pro", "Edge 50", "Edge 40 Pro", "Edge 40", "Edge 30 Ultra", "Edge 30 Pro", "Edge 30", "Razr 50 Ultra", "Razr 50", "Razr 40 Ultra", "Razr 40", "Moto G84", "Moto G73", "Moto G54", "Moto G53", "Moto G24", "Moto G23", "Moto G14", "Moto G13"],
    "Nothing": ["Phone (2a) Plus", "Phone (2a)", "Phone (2)", "Phone (1)"],
    "Nokia": ["X30", "X20", "X10", "G60", "G50", "G42", "G22", "G21", "C32", "C22", "C12", "5.4", "5.3", "4.2", "3.4", "2.4", "1.4"],
    "OnePlus": ["12", "12R", "11", "11R", "10 Pro", "10T", "10R", "9 Pro", "9", "9R", "8 Pro", "8T", "8", "Nord 3", "Nord CE 3", "Nord CE 3 Lite", "Nord 2T", "Nord CE 2", "Nord CE 2 Lite", "Nord N30", "Nord N20", "Nord N10"],
    "OPPO": ["Find X7 Ultra", "Find X7", "Find X6 Pro", "Find X6", "Find X5 Pro", "Find X5", "Find N3 Flip", "Find N3", "Find N2 Flip", "Find N2", "Reno 12 Pro", "Reno 12", "Reno 12 F", "Reno 11 Pro", "Reno 11", "Reno 11 F", "Reno 10 Pro+", "Reno 10 Pro", "Reno 10", "Reno 9 Pro", "Reno 9", "Reno 8 Pro", "Reno 8", "A3s", "A5", "A7", "A9", "A1k", "A5s", "A7n", "A91", "A12", "A31", "A52", "A53", "A72", "A92", "A15", "A15s", "A16", "A16e", "A16k", "A33", "A53s", "A54", "A74", "A74 5G", "A94", "A95", "A17", "A17k", "A55", "A57", "A57 5G", "A76", "A77", "A77 5G", "A78", "A96", "A98", "A18", "A38", "A58", "A58x", "A58 5G", "A78 5G", "A79 5G", "A98 5G", "A3", "A3x", "A3 Pro", "A5 Pro", "A60", "A79", "A80", "A59 5G", "A6 Pro 5G", "A6x 5G", "A7 Pro Max 5G", "A80 5G"],
    "Realme": ["GT 5 Pro", "GT 5", "GT Neo 5", "GT Neo 3", "GT 3", "GT 2 Pro", "GT 2", "12 Pro+", "12 Pro", "12", "12x", "11 Pro+", "11 Pro", "11", "11x", "10 Pro+", "10 Pro", "10", "9 Pro+", "9 Pro", "9", "C67", "C55", "C53", "C51", "C35", "C33", "Narzo 60 Pro", "Narzo 60", "Narzo 50 Pro", "Narzo 50"],
    "Redmi": ["Redmi Note 17 Pro Max", "Redmi Note 17 Pro", "Redmi Note 17", "Redmi Note 15R Pro", "Redmi Note 15 SE 5G", "Redmi Note 15 Pro+", "Redmi Note 15 Pro", "Redmi Note 15", "Redmi Note 14 SE 5G", "Redmi Note 14 Pro+", "Redmi Note 14 Pro", "Redmi Note 14", "Redmi Note 13R", "Redmi Note 13 5G", "Redmi Note 13 Pro+", "Redmi Note 13 Pro", "Redmi Note 13", "Redmi Note 12R", "Redmi Note 12 5G", "Redmi Note 12S", "Redmi Note 12 Pro 5G", "Redmi Note 12 Pro+", "Redmi Note 12 Pro", "Redmi Note 12", "Redmi Note 11E Pro", "Redmi Note 11E", "Redmi Note 11T 5G", "Redmi Note 11 5G", "Redmi Note 11S", "Redmi Note 11 Pro+", "Redmi Note 11 Pro", "Redmi Note 11", "Redmi Note 10T", "Redmi Note 10 5G", "Redmi Note 10S", "Redmi Note 10 Pro Max", "Redmi Note 10 Pro", "Redmi Note 10", "Redmi Note 9T", "Redmi Note 9 5G", "Redmi Note 9 4G", "Redmi Note 9S", "Redmi Note 9 Pro Max", "Redmi Note 9 Pro", "Redmi Note 9", "Redmi Note 8T", "Redmi Note 8 Pro", "Redmi Note 8", "Redmi Note 7S", "Redmi Note 7 Pro", "Redmi Note 7", "Redmi 15R 5G", "Redmi 15R", "Redmi 15C 5G", "Redmi 15C", "Redmi 15", "Redmi 14C 5G", "Redmi 14C", "Redmi 13R", "Redmi 13C 5G", "Redmi 13C", "Redmi 13", "Redmi 12R", "Redmi 12 5G", "Redmi 12C", "Redmi 12", "Redmi 11 Prime 5G", "Redmi 11 Prime", "Redmi 10 Power", "Redmi 10 2022", "Redmi 10 Prime", "Redmi 10C", "Redmi 10A", "Redmi 10", "Redmi 9i Sport", "Redmi 9i", "Redmi 9T", "Redmi 9 Power", "Redmi 9C NFC", "Redmi 9C", "Redmi 9A", "Redmi 9", "Redmi 8A Pro", "Redmi 8A Dual", "Redmi 8A", "Redmi 8", "Redmi 7A", "Redmi 7", "Redmi A3x", "Redmi A3", "Redmi A2+", "Redmi A2", "Redmi Pad", "Redmi Pad SE"],
    "Samsung": ["Galaxy S24 Ultra", "Galaxy S24+", "Galaxy S24", "Galaxy S23 Ultra", "Galaxy S23+", "Galaxy S23", "Galaxy S23 FE", "Galaxy S22 Ultra", "Galaxy S22+", "Galaxy S22", "Galaxy S21 Ultra", "Galaxy S21+", "Galaxy S21", "Galaxy S21 FE", "Galaxy Note 20 Ultra", "Galaxy Note 20", "Galaxy Note 10+", "Galaxy Note 10", "Galaxy Z Fold 6", "Galaxy Z Fold 5", "Galaxy Z Fold 4", "Galaxy Z Flip 6", "Galaxy Z Flip 5", "Galaxy Z Flip 4", "Galaxy M55", "Galaxy M54", "Galaxy M34", "Galaxy M14", "Galaxy A10", "Galaxy A10e", "Galaxy A10s", "Galaxy A20", "Galaxy A20e", "Galaxy A20s", "Galaxy A30", "Galaxy A30s", "Galaxy A40", "Galaxy A50", "Galaxy A50s", "Galaxy A60", "Galaxy A70", "Galaxy A70s", "Galaxy A80", "Galaxy A90 5G", "Galaxy A01", "Galaxy A01 Core", "Galaxy A11", "Galaxy A21", "Galaxy A21s", "Galaxy A31", "Galaxy A41", "Galaxy A51", "Galaxy A51 5G", "Galaxy A71", "Galaxy A71 5G", "Galaxy A02", "Galaxy A02s", "Galaxy A03", "Galaxy A03s", "Galaxy A03 Core", "Galaxy A12", "Galaxy A12 Nacho", "Galaxy A22", "Galaxy A22 5G", "Galaxy A32", "Galaxy A32 5G", "Galaxy A52", "Galaxy A52 5G", "Galaxy A52s 5G", "Galaxy A72", "Galaxy A82 5G", "Galaxy A04", "Galaxy A04e", "Galaxy A04s", "Galaxy A13", "Galaxy A13 5G", "Galaxy A23", "Galaxy A23 5G", "Galaxy A33 5G", "Galaxy A53 5G", "Galaxy A73 5G", "Galaxy A05", "Galaxy A05s", "Galaxy A14", "Galaxy A14 5G", "Galaxy A24", "Galaxy A34 5G", "Galaxy A54 5G", "Galaxy A06", "Galaxy A15", "Galaxy A15 5G", "Galaxy A25 5G", "Galaxy A35 5G", "Galaxy A55 5G", "Galaxy A07", "Galaxy A07 5G", "Galaxy A07s", "Galaxy A08", "Galaxy A16", "Galaxy A16 5G", "Galaxy A17", "Galaxy A17 5G", "Galaxy A26 5G", "Galaxy A27 5G", "Galaxy A36 5G", "Galaxy A37 5G", "Galaxy A56 5G", "Galaxy A57 5G"],
    "Sony": ["Xperia 1 VI", "Xperia 1 V", "Xperia 1 IV", "Xperia 5 V", "Xperia 5 IV", "Xperia 5 III", "Xperia 10 VI", "Xperia 10 V", "Xperia 10 IV", "Xperia Pro-I"],
    "Tecno": ["Phantom X2 Pro", "Phantom X2", "Phantom X", "Camon 20 Pro", "Camon 20", "Camon 19 Pro", "Camon 19", "Spark 20 Pro", "Spark 20", "Spark 10 Pro", "Spark 10", "Spark 9 Pro", "Spark 9", "Pova 5 Pro", "Pova 5", "Pova 4", "Pop 8", "Pop 7", "Pop 6"],
    "Vivo": ["X100 Pro", "X100", "X90 Pro", "X90", "X80 Pro", "X80", "X70 Pro", "X70", "V40 Pro", "V40", "V40e", "V30 Pro", "V30", "V30e", "V29 Pro", "V29", "V29e", "V27 Pro", "V27", "V27e", "Y81", "Y81s", "Y83", "Y83 Pro", "Y85", "Y89", "Y91", "Y91i", "Y91C", "Y93", "Y95", "Y97", "Y11", "Y12", "Y15", "Y17", "Y19", "Y30", "Y50", "Y70", "Y11s", "Y12s", "Y15s", "Y20", "Y20i", "Y20s", "Y30i", "Y51", "Y70s", "Y21", "Y21A", "Y21s", "Y21T", "Y21e", "Y22", "Y22s", "Y31", "Y33s", "Y53s", "Y72", "Y73", "Y75", "Y75 5G", "Y76 5G", "Y02", "Y02A", "Y02s", "Y16", "Y35", "Y35m", "Y55", "Y55s", "Y77", "Y77e", "Y100", "Y100A", "Y03", "Y17s", "Y27", "Y27 5G", "Y28", "Y36", "Y36 5G", "Y56 5G", "Y78", "Y78 5G", "Y05", "Y05e", "Y11d", "Y15s", "Y19s", "Y21d", "Y29", "Y31d", "Y31d Pro", "Y38", "Y18", "Y21 5G", "Y31 5G", "Y31t", "Y200", "Y200e", "Y200 Pro", "Y400 5G", "Y400 Pro", "Y500 4G", "Y51 Pro", "Y300", "Y300 Pro", "Y300 Pro+"],
    "Xiaomi": ["Xiaomi 14 Ultra", "Xiaomi 14 Pro", "Xiaomi 14", "Xiaomi 13 Ultra", "Xiaomi 13 Pro", "Xiaomi 13", "Xiaomi 13 Lite", "Xiaomi 12 Pro", "Xiaomi 12", "Xiaomi 12 Lite", "Xiaomi 11 Ultra", "Xiaomi 11 Pro", "Xiaomi 11", "Xiaomi 11 Lite", "POCO X6 Pro", "POCO X6", "POCO X5 Pro", "POCO X5", "POCO F6 Pro", "POCO F6", "POCO F5 Pro", "POCO F5", "POCO M6 Pro", "POCO M5", "POCO C65"]
};

// Fungsi untuk mengisi dropdown merek HP saat halaman dimuat
function populateMerkHp() {
    const merkSelect = document.getElementById('merkHpSelect');
    merkSelect.innerHTML = '<option value="">-- Pilih Merk / Ketik Manual --</option>';
    
    Object.keys(dataMerekHp).sort().forEach(merk => {
        const option = document.createElement('option');
        option.value = merk;
        option.textContent = merk;
        merkSelect.appendChild(option);
    });
}

// Fungsi untuk mengisi dropdown tipe HP berdasarkan merek yang dipilih
function loadTipeHp() {
    const merkSelect = document.getElementById('merkHpSelect');
    const tipeSelect = document.getElementById('tipeHpSelect');
    const tipeOsSelect = document.querySelector('select[name="tipe"]');
    const selectedMerk = merkSelect.value;
    
    tipeSelect.innerHTML = '<option value="">-- Pilih Tipe HP --</option>';
    
    if (selectedMerk && dataMerekHp[selectedMerk]) {
        dataMerekHp[selectedMerk].forEach(tipe => {
            const option = document.createElement('option');
            option.value = tipe;
            option.textContent = tipe;
            tipeSelect.appendChild(option);
        });
        
        // Auto-detect OS berdasarkan merek
        if (tipeOsSelect) {
            const appleMerks = ['Apple', 'iPhone'];
            tipeOsSelect.value = appleMerks.includes(selectedMerk) ? 'Apple' : 'Android';
        }
    } else {
        tipeSelect.innerHTML = '<option value="">-- Pilih Merk dulu --</option>';
    }
}

// Fungsi untuk auto-fill perangkat saat tipe dipilih
function autoFillPerangkat() {
    const merk = document.getElementById('merkHpSelect').value;
    const tipe = document.getElementById('tipeHpSelect').value;
    const input = document.getElementById('perangkatInput');
    if (merk && tipe) {
        input.value = merk + ' ' + tipe;
    }
}

// Panggil fungsi saat halaman dimuat
document.addEventListener('DOMContentLoaded', populateMerkHp);


// ===== LOGIKA KIRIM KE TEKNISI (MANUAL) =====
function kirimWaKeTeknisi() {
    const teknisiSelect = document.getElementById('teknisiSelect');
    const opt = teknisiSelect.options[teknisiSelect.selectedIndex];
    const manualHp = document.getElementById('noHpTeknisiManual')?.value.trim();
    let hpTeknisi = manualHp || opt.dataset.wa;

    const pola = document.getElementById('polaInput').value;
    const pin = document.getElementById('pinInput').value;

    if (!hpTeknisi) {
        alert('Nomor WA teknisi tidak ditemukan!\nSilakan pilih teknisi yang memiliki nomor WA, atau isi kolom "No. WA Teknisi" terlebih dahulu.');
        return;
    }

    const perangkat = document.getElementById('perangkatInput').value || 'Tidak disebutkan';
    const kode = document.getElementById('kodeServis').value || 'Menunggu Generate';

    let rawHp = hpTeknisi.replace(/[^0-9]/g, '');
    let finalHp = rawHp.replace(/^(?:\+?62|0)/, '62');

    let pesan = `*🔔 INFO KEAMANAN PERANGKAT BARU*\n`;
    pesan += `========================\n`;
    pesan += `Kode Servis: *${kode}*\n`;
    pesan += `Perangkat: ${perangkat}\n`;
    pesan += `------------------------\n`;
    if (pola) pesan += `🔓 *Pola:* ${pola}\n`;
    if (pin) pesan += `🔑 *PIN:* ${pin}\n`;
    if (!pola && !pin) pesan += `🔓 *Pola/PIN:* Tidak diisi\n`;
    pesan += `------------------------\n`;
    pesan += `Mohon segera diproses. Jangan bagikan info ini ke pihak lain. Terima kasih.`;

    window.open(`https://api.whatsapp.com/send?phone=${finalHp}&text=${encodeURIComponent(pesan)}`, '_blank');
}

document.getElementById('teknisiSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const hpTeknisi = (selectedOption.getAttribute('data-wa') || '').replace(/[^0-9]/g, '');
    const manualInput = document.getElementById('noHpTeknisiManual');
    if (manualInput) {
        manualInput.value = hpTeknisi;
    }
});


// ===== LOGIKA CANVAS GAMBAR POLA =====
const canvas = document.getElementById('patternCanvas');
const ctx = canvas.getContext('2d');
const points = [];
const spacing = 50;
const offset = 25;
let isDrawing = false;
let currentPath = [];

for(let r = 0; r < 3; r++) {
    for(let c = 0; c < 3; c++) {
        points.push({ x: offset + c * spacing, y: offset + r * spacing, id: (r * 3 + c) + 1 });
    }
}

function drawPattern() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (currentPath.length > 0) {
        ctx.beginPath();
        ctx.moveTo(points[currentPath[0] - 1].x, points[currentPath[0] - 1].y);
        for(let i = 1; i < currentPath.length; i++) {
            ctx.lineTo(points[currentPath[i] - 1].x, points[currentPath[i] - 1].y);
        }
        ctx.strokeStyle = '#0d9488';
        ctx.lineWidth = 4;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.stroke();
    }
    points.forEach(p => {
        ctx.beginPath();
        ctx.arc(p.x, p.y, 8, 0, Math.PI * 2);
        ctx.fillStyle = currentPath.includes(p.id) ? '#0d9488' : '#cbd5e1';
        ctx.fill();
        ctx.fillStyle = currentPath.includes(p.id) ? '#fff' : '#64748b';
        ctx.font = '10px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(p.id, p.x, p.y + 1);
    });
}

function getPointFromEvent(e) {
    const rect = canvas.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    const cx = (clientX - rect.left) * scaleX;
    const cy = (clientY - rect.top) * scaleY;

    for(let p of points) {
        const dist = Math.sqrt((cx - p.x) ** 2 + (cy - p.y) ** 2);
        if (dist < 20) return p;
    }
    return null;
}

function startDrawing(e) {
    e.preventDefault();
    isDrawing = true;
    currentPath = [];
    const p = getPointFromEvent(e);
    if (p) {
        currentPath.push(p.id);
        updatePolaInput();
    }
    drawPattern();
}

function moveDrawing(e) {
    if (!isDrawing) return;
    e.preventDefault();
    const p = getPointFromEvent(e);
    if (p && !currentPath.includes(p.id)) {
        currentPath.push(p.id);
        updatePolaInput();
        drawPattern();
    }
}

function endDrawing(e) {
    if (!isDrawing) return;
    isDrawing = false;
    drawPattern();
}

function updatePolaInput() {
    const polaInput = document.getElementById('polaInput');
    if (currentPath.length > 0) {
        polaInput.value = "Pola: " + currentPath.join(" - ");
    } else {
        polaInput.value = "";
    }
}

function resetPattern() {
    currentPath = [];
    document.getElementById('polaInput').value = "";
    drawPattern();
}

canvas.addEventListener('mousedown', startDrawing);
canvas.addEventListener('mousemove', moveDrawing);
canvas.addEventListener('mouseup', endDrawing);
canvas.addEventListener('mouseleave', endDrawing);
canvas.addEventListener('touchstart', startDrawing, { passive: false });
canvas.addEventListener('touchmove', moveDrawing, { passive: false });
canvas.addEventListener('touchend', endDrawing);
drawPattern();


// Pilih pelanggan dari dropdown
function pilihPelanggan(select) {
    const opt = select.options[select.selectedIndex];
    const infoEl = document.getElementById('infoPelanggan');
    if (opt.value) {
        document.getElementById('noHp').value = opt.dataset.noHp || '';
        document.getElementById('namaP').value = opt.dataset.nama || '';
        document.getElementById('alamatP').value = opt.dataset.alamat || '';
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
        infoEl.style.display = 'none';
    }
}

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

// ===== AUTO WA (PELANGGAN & TEKNISI) SAAT SIMPAN =====
document.querySelectorAll('form[method="POST"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        // 1. AUTO WA PELANGGAN
        const autoWA = document.getElementById('autoWaCheckbox')?.checked;
        if (autoWA) {
            let rawNoHp = document.getElementById('noHp')?.value.replace(/[^0-9]/g, '') || '';
            const noHp = rawNoHp.replace(/^(?:\+?62|0)/, '62');
            
            if (noHp && noHp.length >= 10) {
                const nama = document.getElementById('namaP')?.value || '';
                const kodeServis = document.getElementById('kodeServis')?.value || 'Menunggu Generate';
                const perangkat = document.getElementById('perangkatInput')?.value || '';
                const keluhan = document.getElementById('keluhanInput')?.value || '';
                const biaya = document.getElementById('biayaInput')?.value || '0';
                const dp = document.getElementById('dpInput')?.value || '0';
                const sisa = document.getElementById('totalBayarDisplay')?.textContent || 'Rp 0';
                const eta = document.querySelector('input[name="eta"]')?.value || '';
                
                let pesan = `*NOTA SERVIS DIGITAL*\n`;
                pesan += `========================\n`;
                pesan += `Kode Servis: *${kodeServis}*\n`;
                pesan += `Pelanggan: ${nama}\n`;
                pesan += `No. HP: ${document.getElementById('noHp')?.value}\n`;
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
                          
                const waUrl = `https://api.whatsapp.com/send?phone=${noHp}&text=${encodeURIComponent(pesan)}`;
                window.open(waUrl, '_blank');
            } else {
                console.warn('Nomor HP pelanggan tidak valid untuk kirim WA.');
            }
        }

        // 2. AUTO WA TEKNISI
        const autoWaTeknisi = document.getElementById('autoWaTeknisiCheckbox')?.checked;
        if (autoWaTeknisi) {
            const teknisiSelect = document.getElementById('teknisiSelect');
            const opt = teknisiSelect?.options[teknisiSelect.selectedIndex];
            const manualHp = document.getElementById('noHpTeknisiManual')?.value.trim();
            let hpTeknisi = manualHp || opt?.dataset.wa;
            
            if (hpTeknisi) {
                let rawHp = hpTeknisi.replace(/[^0-9]/g, '');
                let finalHp = rawHp.replace(/^(?:\+?62|0)/, '62');

                if (finalHp && finalHp.length >= 10) {
                    const pola = document.getElementById('polaInput')?.value || '';
                    const pin = document.getElementById('pinInput')?.value || '';
                    const perangkat = document.getElementById('perangkatInput')?.value || 'Tidak disebutkan';
                    const kode = document.getElementById('kodeServis')?.value || 'Menunggu Generate';

                    let pesan = `*🔔 INFO KEAMANAN PERANGKAT BARU*\n`;
                    pesan += `========================\n`;
                    pesan += `Kode Servis: *${kode}*\n`;
                    pesan += `Perangkat: ${perangkat}\n`;
                    pesan += `------------------------\n`;
                    if (pola) pesan += `🔓 *Pola:* ${pola}\n`;
                    if (pin) pesan += `🔑 *PIN:* ${pin}\n`;
                    if (!pola && !pin) pesan += `🔓 *Pola/PIN:* Tidak diisi\n`;
                    pesan += `------------------------\n`;
                    pesan += `Mohon segera diproses. Jangan bagikan info ini ke pihak lain. Terima kasih.`;

                    window.open(`https://api.whatsapp.com/send?phone=${finalHp}&text=${encodeURIComponent(pesan)}`, '_blank');
                }
            }
        }

        // Remove name from sparepart selects/inputs yang kosong
        this.querySelectorAll('.sparepart-select').forEach(sel => {
            if (!sel.value) {
                if (!sel.dataset.origName && sel.hasAttribute('name')) sel.dataset.origName = sel.getAttribute('name');
                sel.removeAttribute('name');
            }
        });
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