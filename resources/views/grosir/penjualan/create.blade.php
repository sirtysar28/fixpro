@extends('layouts.app')
@section('title', 'Transaksi Grosir Baru')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">🛒 Transaksi Grosir Baru</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">Harga otomatis mengikuti level pelanggan (harga khusus > level > eceran)</p>
    </div>
    <a href="{{ route('grosir.penjualan.index') }}" class="btn btn-secondary"><i class="fas fa-history"></i> Riwayat Penjualan</a>
</div>

@if($pesanan)
<div class="alert alert-warning">
    <i class="fas fa-clipboard-list"></i>
    Checkout pesanan <strong>{{ $pesanan->no_pesanan }}</strong> — {{ $pesanan->nama_pelanggan }} ({{ $pesanan->labelLevelHarga() }}).
    Item di bawah sudah terisi otomatis.
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 380px;gap:16px;align-items:start;" class="pos-grid">

    {{-- ============ KOLOM KIRI: PELANGGAN + PRODUK + KERANJANG ============ --}}
    <div>
        <div class="card">
            <div class="card-header"><h3>1️⃣ Pelanggan & Sumber Stok</h3></div>
            <div class="form-row">
                <div class="form-group">
                    <label>Pelanggan Grosir</label>
                    <select id="selPelanggan" class="form-input">
                        <option value="">— Pelanggan Umum (Eceran) —</option>
                        @foreach($pelanggans as $p)
                        <option value="{{ $p->id }}" data-level="{{ $p->level_harga }}" data-alamat="{{ $p->alamat_kirim ?? $p->alamat ?? '' }}"
                            {{ $pesanan && $pesanan->pelanggan_grosir_id === $p->id ? 'selected' : '' }}>
                            {{ $p->nama }} ({{ $p->kode }}) · {{ $p->labelLevelHarga() }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Sumber Stok</label>
                    <select id="selSumber" class="form-input">
                        <option value="{{ $cabangAktif?->id ?? '' }}">🏠 {{ $cabangAktif?->nama ?? 'Toko Aktif' }} (Toko)</option>
                        @foreach($gudangs as $g)
                        <option value="{{ $g['id'] }}">🏬 {{ $g['nama'] }} (Gudang)</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Level Harga</label>
                    <select id="selLevel" class="form-input">
                        @foreach(\App\Services\GrosirService::LEVELS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Alamat Pengiriman</label>
                    <input type="text" id="inpAlamat" class="form-input" placeholder="Untuk surat jalan..." value="{{ old('alamat_kirim', $pesanan?->alamat_kirim) }}">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>2️⃣ Cari Produk</h3></div>
            <div style="display:flex;gap:10px;">
                <input type="text" id="inpCari" class="form-input" placeholder="🔍 Ketik nama / kode / barcode... (Enter untuk tambah cepat)" autocomplete="off">
            </div>
            <div id="hasilCari" style="margin-top:10px;max-height:260px;overflow-y:auto;"></div>
        </div>

        <div class="card">
            <div class="card-header"><h3>3️⃣ Keranjang</h3></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Produk</th><th style="width:90px;text-align:center;">Qty</th><th style="width:130px;">Harga</th><th style="text-align:right;">Subtotal</th><th></th></tr>
                    </thead>
                    <tbody id="isiKeranjang">
                        <tr id="keranjangKosong"><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">Keranjang kosong — cari & tambahkan produk di atas</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============ KOLOM KANAN: PEMBAYARAN ============ --}}
    <div class="card" style="position:sticky;top:80px;">
        <div class="card-header"><h3>💳 Pembayaran</h3></div>

        <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;font-size:.85rem;padding:3px 0;"><span style="color:#64748b;">Subtotal Barang</span><b id="txtSubtotal">Rp 0</b></div>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:.85rem;padding:3px 0;margin-top:6px;">
                <span style="color:#64748b;">Diskon Transaksi</span>
                <input type="number" id="inpDiskon" min="0" step="any" value="0" style="width:120px;text-align:right;" class="form-input">
            </div>
            <hr style="border:none;border-top:1px dashed #cbd5e1;margin:10px 0;">
            <div style="display:flex;justify-content:space-between;font-size:1.05rem;"><b>TOTAL</b><b id="txtTotal" style="color:var(--primary);">Rp 0</b></div>
        </div>

        <div class="form-group">
            <label>Metode Bayar</label>
            <select id="selMetode" class="form-input">
                <option value="Cash">Cash</option>
                <option value="Transfer">Transfer</option>
                <option value="QRIS">QRIS</option>
                <option value="Tempo">Tempo / Piutang</option>
            </select>
        </div>
        <div class="form-group">
            <label>Uang Dibayar (Rp)</label>
            <input type="number" id="inpBayar" min="0" step="any" value="0" class="form-input">
            <div style="display:flex;gap:6px;margin-top:6px;">
                <button type="button" class="btn btn-sm btn-secondary" onclick="bayarPenuh()">Bayar Penuh</button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('inpBayar').value=0;hitung()">Bayar Nanti</button>
            </div>
        </div>
        <div class="form-group" id="wrapJatuhTempo" style="display:none;">
            <label>Jatuh Tempo Piutang *</label>
            <input type="date" id="inpJatuhTempo" class="form-input" value="{{ now()->addDays(14)->format('Y-m-d') }}">
        </div>
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px;margin-bottom:14px;font-size:.85rem;">
            <div style="display:flex;justify-content:space-between;"><span>Pembayaran</span><b id="txtBayar">Rp 0</b></div>
            <div style="display:flex;justify-content:space-between;margin-top:4px;"><span style="color:#b45309;">Piutang</span><b id="txtPiutang" style="color:#b45309;">Rp 0</b></div>
        </div>

        <div class="form-group">
            <label>Catatan</label>
            <textarea id="inpCatatan" rows="2" class="form-input" placeholder="Opsional...">{{ old('catatan', $pesanan?->catatan) }}</textarea>
        </div>

        <button id="btnSimpan" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;font-size:.95rem;">
            <i class="fas fa-print"></i> Simpan & Cetak Nota Grosir
        </button>
        <div id="msgHasil" style="margin-top:10px;"></div>
    </div>
</div>

<style>
    .pos-produk { display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:8px;cursor:pointer;transition:all .15s; }
    .pos-produk:hover { border-color:var(--primary);background:var(--primary-bg); }
    .pos-produk .nm { font-weight:600;font-size:.85rem; }
    .pos-produk .mt { font-size:.7rem;color:#94a3b8;font-family:monospace; }
    .pos-produk .hg { font-weight:700;color:var(--primary);font-size:.85rem;white-space:nowrap; }
    .pos-produk .stok-habis .hg { color:var(--danger); }
    .qty-input { width:70px;text-align:center;padding:6px;border:1.5px solid #e2e8f0;border-radius:6px; }
    .harga-input { width:120px;text-align:right;padding:6px;border:1.5px solid #e2e8f0;border-radius:6px; }
    @media(max-width:1024px){ .pos-grid { grid-template-columns:1fr !important; } }
</style>

<script>
    let keranjang = [];
    let debounceTimer = null;
    const CSRF = '{{ csrf_token() }}';

    // ===== Inisialisasi =====
    document.addEventListener('DOMContentLoaded', () => {
        // Level harga awal dari pelanggan terpilih (atau pesanan)
        @if($pesanan)
        document.getElementById('selLevel').value = '{{ $pesanan->level_harga }}';
        @endif
        syncLevelDariPelanggan();

        document.getElementById('selPelanggan').addEventListener('change', () => { syncLevelDariPelanggan(); refreshHarga(); });
        document.getElementById('selLevel').addEventListener('change', refreshHarga);
        document.getElementById('selSumber').addEventListener('change', () => { cariProduk(); refreshHarga(); });
        document.getElementById('selMetode').addEventListener('change', toggleJatuhTempo);
        document.getElementById('inpDiskon').addEventListener('input', hitung);
        document.getElementById('inpBayar').addEventListener('input', hitung);
        document.getElementById('inpCari').addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(cariProduk, 250);
        });
        document.getElementById('inpCari').addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); tambahDariHasilPertama(); }
        });
        document.getElementById('btnSimpan').addEventListener('click', simpan);

        toggleJatuhTempo();

        // Prefill keranjang dari pesanan
        @if($pesanan && $pesanan->items->count())
        @foreach($pesanan->items as $pi)
        keranjang.push({ id: {{ $pi->stok_id }}, kode: @json($pi->kode), nama: @json($pi->nama), harga: {{ (float) $pi->harga_satuan }}, qty: {{ (int) $pi->qty }}, max: 99999 });
        @endforeach
        renderKeranjang();
        @endif
    });

    function syncLevelDariPelanggan() {
        const opt = document.getElementById('selPelanggan').selectedOptions[0];
        const lvl = opt?.dataset?.level;
        const almt = opt?.dataset?.alamat;
        if (lvl) document.getElementById('selLevel').value = lvl;
        if (almt && !document.getElementById('inpAlamat').value) document.getElementById('inpAlamat').value = almt;
    }

    function toggleJatuhTempo() {
        const tempo = document.getElementById('selMetode').value === 'Tempo';
        const piutang = hitungPiutang() > 0;
        document.getElementById('wrapJatuhTempo').style.display = (tempo || piutang) ? 'block' : 'none';
    }

    // ===== API =====
    async function cariProduk() {
        const q = document.getElementById('inpCari').value.trim();
        const url = `{{ url('grosir/api/produk') }}?q=${encodeURIComponent(q)}&level=${document.getElementById('selLevel').value}` +
            `&pelanggan_id=${document.getElementById('selPelanggan').value}&sumber=${document.getElementById('selSumber').value}`;
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            renderHasil(data.products || []);
        } catch (e) { console.error(e); }
    }

    function renderHasil(products) {
        const box = document.getElementById('hasilCari');
        if (!products.length) {
            box.innerHTML = '<div style="text-align:center;color:#94a3b8;font-size:.82rem;padding:14px;">Tidak ada produk ditemukan di toko/gudang ini</div>';
            return;
        }
        box.innerHTML = products.map(p => `
            <div class="pos-produk ${p.tersedia <= 0 ? 'stok-habis' : ''}" data-id="${p.id}" data-kode="${p.kode}" data-nama="${p.nama.replace(/"/g,'&quot;')}" data-harga="${p.harga}" data-max="${p.stok}">
                <div>
                    <div class="nm">${p.nama}</div>
                    <div class="mt">${p.kode} · Stok ${p.stok}${p.reserved > 0 ? ` (reservasi ${p.reserved})` : ''} · Tersedia ${p.tersedia}</div>
                </div>
                <div class="hg">${formatRp(p.harga)}${p.sumber_harga === 'khusus' ? ' ⭐' : ''}</div>
            </div>
        `).join('');
        box.querySelectorAll('.pos-produk').forEach(el => {
            el.addEventListener('click', () => tambahKeKeranjang(el.dataset));
        });
    }

    function tambahDariHasilPertama() {
        const first = document.querySelector('#hasilCari .pos-produk');
        if (first) tambahKeKeranjang(first.dataset);
    }

    function tambahKeKeranjang(d) {
        const id = parseInt(d.id);
        const existing = keranjang.find(k => k.id === id);
        if (existing) existing.qty++;
        else keranjang.push({ id, kode: d.kode, nama: d.nama, harga: parseFloat(d.harga), qty: 1, max: parseInt(d.max) });
        renderKeranjang();
        document.getElementById('inpCari').value = '';
        document.getElementById('inpCari').focus();
    }

    // Refresh harga semua item di keranjang sesuai level/pelanggan terbaru
    async function refreshHarga() {
        if (!keranjang.length) return;
        const url = `{{ url('grosir/api/produk') }}?q=&level=${document.getElementById('selLevel').value}` +
            `&pelanggan_id=${document.getElementById('selPelanggan').value}&sumber=${document.getElementById('selSumber').value}`;
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            (data.products || []).forEach(p => {
                const item = keranjang.find(k => k.id === p.id);
                if (item) item.harga = p.harga;
            });
            renderKeranjang();
        } catch (e) { console.error(e); }
    }

    // ===== Keranjang =====
    function renderKeranjang() {
        const tbody = document.getElementById('isiKeranjang');
        if (!keranjang.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">Keranjang kosong — cari & tambahkan produk di atas</td></tr>';
            hitung(); return;
        }
        tbody.innerHTML = keranjang.map((k, i) => `
            <tr>
                <td>
                    <div style="font-weight:600;font-size:.85rem;">${k.nama}</div>
                    <div style="font-size:.7rem;color:#94a3b8;font-family:monospace;">${k.kode}</div>
                </td>
                <td style="text-align:center;">
                    <div style="display:flex;gap:4px;justify-content:center;align-items:center;">
                        <button type="button" class="btn btn-sm btn-secondary" style="padding:2px 8px;" onclick="ubahQty(${i},-1)">−</button>
                        <input type="number" class="qty-input" value="${k.qty}" min="1" onchange="setQty(${i}, this.value)">
                        <button type="button" class="btn btn-sm btn-secondary" style="padding:2px 8px;" onclick="ubahQty(${i},1)">+</button>
                    </div>
                </td>
                <td><input type="number" class="harga-input" value="${k.harga}" min="0" step="any" onchange="setHarga(${i}, this.value)"></td>
                <td style="text-align:right;font-weight:700;">${formatRp(k.qty * k.harga)}</td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="hapusItem(${i})"><i class="fas fa-trash"></i></button></td>
            </tr>
        `).join('');
        hitung();
    }

    function ubahQty(i, delta) { keranjang[i].qty = Math.max(1, keranjang[i].qty + delta); renderKeranjang(); }
    function setQty(i, val) { keranjang[i].qty = Math.max(1, parseInt(val) || 1); renderKeranjang(); }
    function setHarga(i, val) { keranjang[i].harga = Math.max(0, parseFloat(val) || 0); renderKeranjang(); }
    function hapusItem(i) { keranjang.splice(i, 1); renderKeranjang(); }

    function hitungSubtotal() { return keranjang.reduce((s, k) => s + k.qty * k.harga, 0); }
    function hitungDiskon() { return Math.max(0, parseFloat(document.getElementById('inpDiskon').value) || 0); }
    function hitungTotal() { return Math.max(0, hitungSubtotal() - hitungDiskon()); }
    function hitungBayar() { return Math.max(0, parseFloat(document.getElementById('inpBayar').value) || 0); }
    function hitungPiutang() { return Math.max(0, hitungTotal() - hitungBayar()); }

    function hitung() {
        document.getElementById('txtSubtotal').textContent = formatRp(hitungSubtotal());
        document.getElementById('txtTotal').textContent = formatRp(hitungTotal());
        document.getElementById('txtBayar').textContent = formatRp(hitungBayar());
        document.getElementById('txtPiutang').textContent = formatRp(hitungPiutang());
        toggleJatuhTempo();
    }

    function bayarPenuh() { document.getElementById('inpBayar').value = hitungTotal(); hitung(); }

    // ===== Simpan =====
    async function simpan() {
        const msg = document.getElementById('msgHasil');
        if (!keranjang.length) { msg.innerHTML = '<div class="alert alert-error" style="margin:0;">Keranjang masih kosong.</div>'; return; }

        const piutang = hitungPiutang();
        if (piutang > 0 && !document.getElementById('inpJatuhTempo').value) {
            msg.innerHTML = '<div class="alert alert-error" style="margin:0;">Ada piutang — isi tanggal jatuh tempo dulu.</div>';
            return;
        }

        const body = {
            items: keranjang.map(k => ({ stok_id: k.id, qty: k.qty, harga_satuan: k.harga })),
            pelanggan_grosir_id: document.getElementById('selPelanggan').value || null,
            level_harga: document.getElementById('selLevel').value,
            sumber: parseInt(document.getElementById('selSumber').value),
            diskon: hitungDiskon(),
            bayar: hitungBayar(),
            metode_bayar: document.getElementById('selMetode').value,
            jatuh_tempo: piutang > 0 ? document.getElementById('inpJatuhTempo').value : null,
            alamat_kirim: document.getElementById('inpAlamat').value,
            catatan: document.getElementById('inpCatatan').value,
            _token: CSRF,
        };
        @if($pesanan)
        body.pesanan_grosir_id = {{ $pesanan->id }};
        @endif

        const btn = document.getElementById('btnSimpan');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

        try {
            const res = await fetch('{{ route('grosir.penjualan.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            if (data.success) {
                window.location.href = '{{ url('grosir/penjualan') }}/' + data.data.id + '/nota?print=1';
            } else {
                msg.innerHTML = `<div class="alert alert-error" style="margin:0;">${data.message || 'Gagal menyimpan.'}</div>`;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-print"></i> Simpan & Cetak Nota Grosir';
            }
        } catch (e) {
            msg.innerHTML = `<div class="alert alert-error" style="margin:0;">Error: ${e.message}</div>`;
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-print"></i> Simpan & Cetak Nota Grosir';
        }
    }
</script>
@endsection
