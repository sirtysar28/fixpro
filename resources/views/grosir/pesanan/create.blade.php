@extends('layouts.app')
@section('title', 'Pesanan Grosir Baru')

@section('content')
<div class="page-header" style="margin-bottom:16px;">
    <h1 style="font-size:1.5rem;margin:0;">📋 Pesanan Grosir Baru</h1>
    <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;"><a href="{{ route('grosir.pesanan.index') }}">← Daftar pesanan</a> · Stok direservasi saat pesanan diproses</p>
</div>

@if($errors->any())
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i>
    <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('grosir.pesanan.store') }}" id="formPesanan">
    @csrf
    <div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start;" class="pos-grid">
        <div>
            <div class="card">
                <div class="card-header"><h3>Pelanggan</h3></div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Pelanggan Grosir</label>
                        <select name="pelanggan_grosir_id" id="selPelanggan" class="form-input">
                            <option value="">— Pelanggan Umum —</option>
                            @foreach($pelanggans as $p)
                            <option value="{{ $p->id }}" data-level="{{ $p->level_harga }}">{{ $p->nama }} ({{ $p->kode }}) · {{ $p->labelLevelHarga() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sumber Stok</label>
                        <select name="sumber" id="selSumber" class="form-input">
                            <option value="{{ auth()->user()->getActiveCabangId() }}">🏠 Toko Aktif</option>
                            @foreach($gudangs as $g)
                            <option value="{{ $g['id'] }}">🏬 {{ $g['nama'] }} (Gudang)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Level Harga</label>
                        <select name="level_harga" id="selLevel" class="form-input">
                            @foreach(\App\Services\GrosirService::LEVELS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Alamat Pengiriman</label>
                        <input type="text" name="alamat_kirim" class="form-input" placeholder="Opsional...">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3>Cari Produk</h3></div>
                <input type="text" id="inpCari" class="form-input" placeholder="🔍 Nama / kode / barcode..." autocomplete="off">
                <div id="hasilCari" style="margin-top:10px;max-height:220px;overflow-y:auto;"></div>
            </div>

            <div class="card">
                <div class="card-header"><h3>Item Pesanan</h3></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Produk</th><th style="width:110px;text-align:center;">Qty</th><th style="text-align:right;">Harga</th><th style="text-align:right;">Subtotal</th><th></th></tr></thead>
                        <tbody id="isiKeranjang">
                            <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px;">Keranjang kosong</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card" style="position:sticky;top:80px;">
            <div class="card-header"><h3>Ringkasan</h3></div>
            <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:14px;">
                <div style="display:flex;justify-content:space-between;font-size:.85rem;padding:3px 0;"><span style="color:#64748b;">Subtotal</span><b id="txtSubtotal">Rp 0</b></div>
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:.85rem;padding:3px 0;margin-top:6px;">
                    <span style="color:#64748b;">Diskon</span>
                    <input type="number" name="diskon" id="inpDiskon" min="0" step="any" value="0" style="width:110px;text-align:right;" class="form-input">
                </div>
                <hr style="border:none;border-top:1px dashed #cbd5e1;margin:10px 0;">
                <div style="display:flex;justify-content:space-between;font-size:1rem;"><b>TOTAL</b><b id="txtTotal" style="color:var(--primary);">Rp 0</b></div>
            </div>
            <div class="form-group">
                <label>Catatan</label>
                <textarea name="catatan" rows="3" class="form-input" placeholder="Instruksi pesanan..."></textarea>
            </div>
            <button class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-save"></i> Simpan Pesanan</button>
        </div>
    </div>
</form>

<style>
    .pos-produk { display:flex;justify-content:space-between;align-items:center;gap:10px;padding:9px 12px;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:7px;cursor:pointer; }
    .pos-produk:hover { border-color:var(--primary);background:var(--primary-bg); }
    .qty-input { width:70px;text-align:center;padding:6px;border:1.5px solid #e2e8f0;border-radius:6px; }
    @media(max-width:1024px){ .pos-grid { grid-template-columns:1fr !important; } }
</style>

<script>
    let keranjang = [];
    let debounceTimer = null;

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('selPelanggan').addEventListener('change', function () {
            const lvl = this.selectedOptions[0]?.dataset?.level;
            if (lvl) document.getElementById('selLevel').value = lvl;
            refreshHarga();
        });
        document.getElementById('selLevel').addEventListener('change', refreshHarga);
        document.getElementById('selSumber').addEventListener('change', refreshHarga);
        document.getElementById('inpDiskon').addEventListener('input', hitung);
        document.getElementById('inpCari').addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(cariProduk, 250);
        });
        document.getElementById('formPesanan').addEventListener('submit', function (e) {
            // Inject item keranjang sebelum submit
            document.querySelectorAll('.item-input').forEach(el => el.remove());
            keranjang.forEach((k, i) => {
                ['stok_id', 'qty', 'harga_satuan'].forEach(f => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.className = 'item-input';
                    inp.name = `items[${i}][${f}]`;
                    inp.value = f === 'stok_id' ? k.id : (f === 'qty' ? k.qty : k.harga);
                    this.appendChild(inp);
                });
            });
            if (!keranjang.length) { e.preventDefault(); alert('Keranjang kosong!'); }
        });
    });

    async function cariProduk() {
        const q = document.getElementById('inpCari').value.trim();
        const url = `{{ url('grosir/pesanan/api/produk') }}?q=${encodeURIComponent(q)}&level=${document.getElementById('selLevel').value}` +
            `&pelanggan_id=${document.getElementById('selPelanggan').value}&sumber=${document.getElementById('selSumber').value}`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        renderHasil(data.products || []);
    }

    function renderHasil(products) {
        const box = document.getElementById('hasilCari');
        if (!products.length) {
            box.innerHTML = '<div style="text-align:center;color:#94a3b8;font-size:.82rem;padding:12px;">Produk tidak ditemukan</div>';
            return;
        }
        box.innerHTML = products.map(p => `
            <div class="pos-produk" data-id="${p.id}" data-kode="${p.kode}" data-nama="${p.nama.replace(/"/g,'&quot;')}" data-harga="${p.harga}">
                <div><div style="font-weight:600;font-size:.85rem;">${p.nama}</div>
                <div style="font-size:.7rem;color:#94a3b8;font-family:monospace;">${p.kode} · Tersedia ${p.tersedia}</div></div>
                <div style="font-weight:700;color:var(--primary);">${formatRp(p.harga)}</div>
            </div>`).join('');
        box.querySelectorAll('.pos-produk').forEach(el => el.addEventListener('click', () => {
            const id = parseInt(el.dataset.id);
            const ex = keranjang.find(k => k.id === id);
            if (ex) ex.qty++; else keranjang.push({ id, kode: el.dataset.kode, nama: el.dataset.nama, harga: parseFloat(el.dataset.harga), qty: 1 });
            renderKeranjang();
            document.getElementById('inpCari').value = '';
            document.getElementById('inpCari').focus();
        }));
    }

    async function refreshHarga() {
        if (!keranjang.length) return;
        const url = `{{ url('grosir/pesanan/api/produk') }}?q=&level=${document.getElementById('selLevel').value}` +
            `&pelanggan_id=${document.getElementById('selPelanggan').value}&sumber=${document.getElementById('selSumber').value}`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        (data.products || []).forEach(p => { const it = keranjang.find(k => k.id === p.id); if (it) it.harga = p.harga; });
        renderKeranjang();
    }

    function renderKeranjang() {
        const tbody = document.getElementById('isiKeranjang');
        if (!keranjang.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px;">Keranjang kosong</td></tr>';
            hitung(); return;
        }
        tbody.innerHTML = keranjang.map((k, i) => `
            <tr>
                <td><b>${k.nama}</b><div style="font-size:.7rem;color:#94a3b8;font-family:monospace;">${k.kode}</div></td>
                <td style="text-align:center;"><input type="number" class="qty-input" value="${k.qty}" min="1" onchange="setQty(${i}, this.value)"></td>
                <td style="text-align:right;">${formatRp(k.harga)}</td>
                <td style="text-align:right;font-weight:700;">${formatRp(k.qty * k.harga)}</td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="keranjang.splice(${i},1);renderKeranjang()"><i class="fas fa-trash"></i></button></td>
            </tr>`).join('');
        hitung();
    }

    function setQty(i, v) { keranjang[i].qty = Math.max(1, parseInt(v) || 1); renderKeranjang(); }

    function hitung() {
        const sub = keranjang.reduce((s, k) => s + k.qty * k.harga, 0);
        const disk = Math.max(0, parseFloat(document.getElementById('inpDiskon').value) || 0);
        document.getElementById('txtSubtotal').textContent = formatRp(sub);
        document.getElementById('txtTotal').textContent = formatRp(Math.max(0, sub - disk));
    }
</script>
@endsection
