@extends('layouts.app')
@section('title', 'Penjualan Sparepart - POS')

@section('content')
<style>
.pos-container { display: grid; grid-template-columns: 1fr 380px; gap: 20px; height: calc(100vh - 180px); min-height: 600px; }
.pos-left { display: flex; flex-direction: column; gap: 16px; overflow: hidden; }
.pos-right { display: flex; flex-direction: column; background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; }
.pos-search { display: flex; gap: 10px; align-items: center; }
.pos-search input { flex: 1; padding: 12px 16px 12px 44px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: .92rem; font-weight: 500; transition: all .2s; }
.pos-search input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-bg); outline: none; }
.pos-search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1rem; }
.btn-scan { width: 48px; height: 48px; border-radius: 12px; border: 2px solid var(--primary); background: var(--primary-bg); color: var(--primary); font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .2s; flex-shrink: 0; }
.btn-scan:hover { background: var(--primary); color: #fff; }
.btn-scan.scanning { background: #dc2626; border-color: #dc2626; color: #fff; animation: pulse-scan 1s infinite; }
@keyframes pulse-scan { 0%,100% { box-shadow: 0 0 0 0 rgba(220,38,38,.4); } 50% { box-shadow: 0 0 0 8px rgba(220,38,38,0); } }
.pos-tabs { display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px; }
.pos-tab { padding: 6px 16px; border-radius: 20px; border: 1.5px solid #e2e8f0; background: #fff; font-size: .76rem; font-weight: 600; color: #64748b; cursor: pointer; white-space: nowrap; transition: all .2s; }
.pos-tab:hover { border-color: var(--primary); color: var(--primary); }
.pos-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.pos-products { flex: 1; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; align-content: start; padding: 4px; }
.pos-product { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px; cursor: pointer; transition: all .2s; display: flex; flex-direction: column; gap: 6px; }
.pos-product:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(13,148,136,.1); transform: translateY(-2px); }
.pos-product.out-of-stock { opacity: .4; pointer-events: none; }
.pos-product-name { font-size: .78rem; font-weight: 600; color: #1e293b; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.pos-product-code { font-size: .64rem; color: #94a3b8; font-weight: 500; }
.pos-product-price { font-size: .88rem; font-weight: 800; color: var(--primary); margin-top: auto; }
.pos-product-stock { font-size: .64rem; color: #64748b; }
.pos-product-stock.low { color: #dc2626; font-weight: 700; }
.cart-header { padding: 16px 18px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; }
.cart-header h3 { font-size: 1rem; margin: 0; display: flex; align-items: center; gap: 8px; }
.cart-badge { background: rgba(255,255,255,.25); padding: 2px 10px; border-radius: 12px; font-size: .72rem; font-weight: 700; }
.cart-items { flex: 1; overflow-y: auto; padding: 8px; }
.cart-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 20px; color: #94a3b8; }
.cart-empty i { font-size: 2.5rem; margin-bottom: 12px; opacity: .3; }
.cart-empty span { font-size: .84rem; font-weight: 500; }
.cart-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; border: 1px solid #f1f5f9; margin-bottom: 6px; transition: all .2s; }
.cart-item:hover { background: #f8fafc; }
.cart-item-info { flex: 1; min-width: 0; }
.cart-item-name { font-size: .78rem; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cart-item-price { font-size: .68rem; color: #64748b; }
.cart-item-qty { display: flex; align-items: center; gap: 4px; }
.cart-item-qty button { width: 24px; height: 24px; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; font-size: .7rem; display: flex; align-items: center; justify-content: center; transition: all .15s; }
.cart-item-qty button:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
.cart-item-total { font-size: .84rem; font-weight: 700; color: var(--primary); min-width: 70px; text-align: right; }
.cart-item-remove { width: 24px; height: 24px; border-radius: 6px; border: none; background: #fef2f2; color: #dc2626; cursor: pointer; font-size: .68rem; display: flex; align-items: center; justify-content: center; transition: all .15s; }
.cart-item-remove:hover { background: #dc2626; color: #fff; }
.cart-footer { border-top: 1px solid #e2e8f0; padding: 16px; }
.cart-summary-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: .82rem; }
.cart-summary-row.total { font-size: 1.1rem; font-weight: 800; color: var(--primary); margin: 10px 0; padding-top: 10px; border-top: 2px dashed #e2e8f0; }
.cart-actions { display: flex; flex-direction: column; gap: 8px; margin-top: 12px; }
.btn-checkout { width: 100%; padding: 14px; border-radius: 12px; border: none; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; font-size: .95rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all .2s; }
.btn-checkout:hover { opacity: .9; transform: translateY(-1px); box-shadow: 0 4px 15px rgba(13,148,136,.3); }
.btn-checkout:disabled { opacity: .4; cursor: not-allowed; transform: none; box-shadow: none; }
.btn-clear-cart { width: 100%; padding: 10px; border-radius: 10px; border: 1.5px solid #fecaca; background: #fff; color: #dc2626; font-size: .82rem; font-weight: 600; cursor: pointer; transition: all .2s; }
.btn-clear-cart:hover { background: #fef2f2; }
.pos-page-tabs { display: flex; gap: 0; margin-bottom: 20px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
.pos-page-tab { flex: 1; padding: 12px 16px; font-size: .88rem; font-weight: 600; color: #64748b; cursor: pointer; text-align: center; transition: all .2s; border-bottom: 3px solid transparent; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
.pos-page-tab:hover { color: var(--primary); background: var(--primary-bg); }
.pos-page-tab.active { color: var(--primary); border-bottom-color: var(--primary); background: var(--primary-bg); }
@media (max-width: 900px) { .pos-container { grid-template-columns: 1fr; height: auto; } .pos-right { max-height: 50vh; } .pos-products { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); } }
body.dark .pos-product { background: #1e293b; border-color: #334155; }
body.dark .pos-product-name { color: #e2e8f0; }
body.dark .pos-tab { background: #1e293b; border-color: #334155; color: #94a3b8; }
body.dark .pos-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); }
body.dark .cart-item { border-color: #334155; }
body.dark .cart-item-name { color: #e2e8f0; }
body.dark .btn-clear-cart { background: #1e293b; border-color: #7f1d1d; }
body.dark .pos-page-tabs { background: #1e293b; border-color: #334155; }
body.dark .pos-page-tab { color: #94a3b8; }
body.dark .pos-page-tab.active { color: #2dd4bf; background: rgba(13,148,136,.15); border-bottom-color: #2dd4bf; }
</style>

{{-- Page Tabs --}}
<div class="pos-page-tabs">
    <div class="pos-page-tab active" onclick="switchTab('pos')">
        <i class="fas fa-cash-register"></i> Kasir POS
    </div>
    <div class="pos-page-tab" onclick="switchTab('riwayat')">
        <i class="fas fa-history"></i> Riwayat Transaksi
    </div>
    <a href="{{ route('tagihan-sparepart.index') }}" class="pos-page-tab" style="text-decoration:none">
        <i class="fas fa-file-invoice"></i> Buat Tagihan
    </a>
</div>

{{-- ===== TAB: POS ===== --}}
<div id="tab-pos">
    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom:16px">
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-cash-register"></i></div>
            <div class="stat-label">Omset Hari Ini</div>
            <div class="stat-value" style="color:var(--primary);font-size:1.3rem">{{ formatRp($omsetHariIni) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#dcfce7;color:var(--success)"><i class="fas fa-chart-line"></i></div>
            <div class="stat-label">Laba Hari Ini</div>
            <div class="stat-value" style="color:var(--success);font-size:1.3rem">{{ formatRp($labaHariIni) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe;color:var(--info)"><i class="fas fa-receipt"></i></div>
            <div class="stat-label">Transaksi Hari Ini</div>
            <div class="stat-value" style="color:var(--info);font-size:1.3rem">{{ $totalTransaksi }}</div>
        </div>
    </div>

    <div class="pos-container">
        <!-- LEFT: Products -->
        <div class="pos-left">
            <!-- Search & Barcode -->
            <div class="pos-search" style="position:relative">
                <i class="fas fa-search pos-search-icon"></i>
                <input type="text" id="posSearchInput" placeholder="Cari nama / kode / barcode / SKU..." autocomplete="off" autofocus
                    oninput="onPosSearchInput(this.value)"
                    onkeydown="if(event.key==='Enter'){searchAndAddProduct(this.value);this.value='';hideSearchSuggest();}"
                    onfocus="onPosSearchInput(this.value)">
                <button class="btn-scan" id="btnScanBarcode" onclick="toggleBarcodeScanner()" title="Scan Barcode dengan Kamera">
                    <i class="fas fa-barcode"></i>
                </button>
                <!-- Dropdown hasil pencarian real-time (Fitur #12) -->
                <div id="posSearchSuggest" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 12px 32px rgba(0,0,0,.12);z-index:50;max-height:340px;overflow-y:auto"></div>
            </div>

            <!-- Barcode Scanner Camera -->
            <div id="barcodeScannerPanel" style="display:none;background:#000;border-radius:12px;overflow:hidden;position:relative">
                <div id="barcodeReader" style="width:100%;height:250px"></div>
                <button onclick="toggleBarcodeScanner()" style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,.6);color:#fff;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:.8rem">
                    <i class="fas fa-times"></i>
                </button>
                <div style="position:absolute;bottom:10px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.6);color:#fff;padding:4px 14px;border-radius:20px;font-size:.72rem">
                    <i class="fas fa-camera"></i> Arahkan barcode ke kamera
                </div>
            </div>

            <!-- Category Tabs -->
            <div class="pos-tabs" id="categoryTabs">
                <div class="pos-tab active" onclick="filterCategory('')">Semua</div>
            </div>

            <!-- Product Grid -->
            <div class="pos-products" id="productGrid">
                @foreach($stoks as $s)
                <div class="pos-product {{ $s->stok <= 0 ? 'out-of-stock' : '' }}"
                     onclick="addToCart({{ $s->id }}, '{{ addslashes($s->nama) }}', {{ $s->jual }}, {{ $s->stok }}, '{{ $s->satuan ?? 'pcs' }}')"
                     data-id="{{ $s->id }}" data-nama="{{ $s->nama }}" data-kategori="{{ $s->kategori }}"
                     data-barcode="{{ $s->barcode ?? '' }}" data-kode="{{ $s->kode }}">
                    <div class="pos-product-name">{{ $s->nama }}</div>
                    <div class="pos-product-code">{{ $s->kode }} @if($s->barcode) · {{ $s->barcode }} @endif</div>
                    <div class="pos-product-price">{{ formatRp($s->jual) }}</div>
                    <div class="pos-product-stock {{ $s->stok <= ($s->min_alert ?? 3) ? 'low' : '' }}">Stok: {{ $s->stok }} {{ $s->satuan ?? 'pcs' }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- RIGHT: Cart -->
        <div class="pos-right">
            <div class="cart-header">
                <h3><i class="fas fa-shopping-cart"></i> Keranjang Belanja</h3>
                <span class="cart-badge" id="cartCount">0 item</span>
            </div>

            <!-- Customer info -->
            <div style="padding:10px 14px;border-bottom:1px solid #f1f5f9">
                <div style="display:flex;gap:8px">
                    <select id="cartPelanggan" class="form-input" style="flex:1;padding:8px 10px;font-size:.78rem;border-radius:8px">
                        <option value="">Umum / Tanpa Pelanggan</option>
                        @foreach($pelanggans as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->no_hp }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;gap:6px;margin-top:6px">
                    <input type="text" id="cartNamaPelanggan" class="form-input" placeholder="Nama pelanggan baru..." style="flex:1;padding:7px 10px;font-size:.76rem;border-radius:8px">
                    <input type="text" id="cartNoHp" class="form-input" placeholder="No HP" style="width:100px;padding:7px 10px;font-size:.76rem;border-radius:8px">
                </div>
            </div>

            <!-- Cart Items -->
            <div class="cart-items" id="cartItems">
                <div class="cart-empty" id="cartEmpty">
                    <i class="fas fa-shopping-basket"></i>
                    <span>Keranjang kosong</span>
                    <span style="font-size:.7rem;margin-top:4px">Scan barcode atau klik produk</span>
                </div>
            </div>

            <!-- Discount -->
            <div style="padding:8px 14px;border-top:1px solid #f1f5f9">
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:.76rem;font-weight:600;color:#64748b;white-space:nowrap"><i class="fas fa-tags"></i> Diskon:</span>
                    <input type="number" id="cartDiskon" value="0" min="0" class="form-input" style="flex:1;padding:6px 10px;font-size:.8rem;border-radius:8px" oninput="updateCartTotals()">
                </div>
            </div>

            <!-- Cart Footer -->
            <div class="cart-footer">
                <div class="cart-summary-row">
                    <span style="color:#64748b">Subtotal</span>
                    <span id="cartSubtotal" style="font-weight:600">Rp 0</span>
                </div>
                <div class="cart-summary-row">
                    <span style="color:#64748b">Diskon</span>
                    <span id="cartDiskonDisplay" style="color:#dc2626;font-weight:600">- Rp 0</span>
                </div>
                <div class="cart-summary-row total">
                    <span>TOTAL</span>
                    <span id="cartTotal">Rp 0</span>
                </div>

                <!-- Payment Method -->
                <div style="display:flex;gap:6px;margin-bottom:10px">
                    <label style="flex:1;cursor:pointer">
                        <input type="radio" name="metode_bayar" value="Cash" checked style="display:none">
                        <div class="payment-option" data-method="Cash" onclick="selectPayment('Cash')" style="padding:8px;border-radius:8px;border:2px solid var(--primary);background:var(--primary-bg);text-align:center;font-size:.76rem;font-weight:700;color:var(--primary);transition:all .2s">
                            <i class="fas fa-money-bill-wave"></i> Cash
                        </div>
                    </label>
                    <label style="flex:1;cursor:pointer">
                        <input type="radio" name="metode_bayar" value="Transfer" style="display:none">
                        <div class="payment-option" data-method="Transfer" onclick="selectPayment('Transfer')" style="padding:8px;border-radius:8px;border:2px solid #e2e8f0;text-align:center;font-size:.76rem;font-weight:600;color:#64748b;transition:all .2s">
                            <i class="fas fa-university"></i> Transfer
                        </div>
                    </label>
                    <label style="flex:1;cursor:pointer">
                        <input type="radio" name="metode_bayar" value="QRIS" style="display:none">
                        <div class="payment-option" data-method="QRIS" onclick="selectPayment('QRIS')" style="padding:8px;border-radius:8px;border:2px solid #e2e8f0;text-align:center;font-size:.76rem;font-weight:600;color:#64748b;transition:all .2s">
                            <i class="fas fa-qrcode"></i> QRIS
                        </div>
                    </label>
                </div>

                <div class="cart-actions">
                    <button class="btn-checkout" id="btnCheckout" onclick="checkout()" disabled>
                        <i class="fas fa-check-circle"></i> <span id="btnCheckoutText">Bayar Sekarang</span>
                    </button>
                    <button class="btn-clear-cart" onclick="clearCart()">
                        <i class="fas fa-trash"></i> Kosongkan Keranjang
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== TAB: RIWAYAT ===== --}}
<div id="tab-riwayat" style="display:none">
    <!-- Filter -->
    <form method="GET" class="card mb-4">
        <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
            <div style="flex:1;min-width:150px"><label class="text-xs font-bold text-muted">Cari</label>
            <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="Kode / No Transaksi / sparepart..."></div>
            <div style="min-width:140px"><label class="text-xs font-bold text-muted">Tanggal</label>
            <input type="date" name="date" class="form-input" value="{{ request('date') }}"></div>
            <div style="min-width:120px"><label class="text-xs font-bold text-muted">Metode</label>
            <select name="metode" class="form-input">
                <option value="">Semua</option>
                <option value="Cash" {{ request('metode') == 'Cash' ? 'selected' : '' }}>Cash</option>
                <option value="Transfer" {{ request('metode') == 'Transfer' ? 'selected' : '' }}>Transfer</option>
                <option value="QRIS" {{ request('metode') == 'QRIS' ? 'selected' : '' }}>QRIS</option>
            </select></div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            <a href="{{ route('penjualan-sparepart.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-redo"></i></a>
        </div>
    </form>

    <!-- Tabel -->
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h3>Riwayat Penjualan</h3>
            <span style="font-size:.74rem;color:#94a3b8">Centang baris lalu klik tombol hapus</span>
        </div>
        {{-- Bulk delete toolbar --}}
        <form id="bulkDeleteFormSP" method="POST" action="{{ route('penjualan-sparepart.bulk-destroy') }}" style="display:none;padding:10px 16px;background:#fef2f2;border-bottom:1px solid #fecaca">
            @csrf
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <span style="font-size:.8rem;color:#991b1b;font-weight:700"><i class="fas fa-trash"></i> <span id="bulkSelectedCountSP">0</span> item dipilih</span>
                <button type="button" onclick="clearBulkSP()" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Batal</button>
                <button type="button" onclick="confirmBulkSP()" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus yang Dipilih</button>
                <span style="font-size:.7rem;color:#92400e;margin-left:auto">Stok dikembalikan otomatis</span>
            </div>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th style="width:36px"><input type="checkbox" id="selectAllSP" onclick="toggleAllSP(this)" title="Pilih semua"></th><th>Kode</th><th>No. Transaksi</th><th>Tanggal</th><th>Sparepart</th><th>Qty</th><th>Harga</th><th>Total</th><th>Laba</th><th>Metode</th><th>Pelanggan</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($penjualans as $p)
                    <tr style="{{ $p->status === 'Dibatalkan' ? 'opacity:.55;background:#fef2f2' : '' }}">
                        <td><input type="checkbox" class="bulk-check-sp" value="{{ $p->id }}" onchange="updateBulkBarSP()"></td>
                        <td><strong style="color:var(--primary)">{{ $p->kode }}</strong>
                            @if($p->status === 'Dibatalkan')
                            <br><span class="badge badge-dibatalkan" style="font-size:.6rem"><i class="fas fa-ban"></i> Dibatalkan</span>
                            @endif
                        </td>
                        <td style="font-size:.72rem;color:#64748b">{{ $p->no_transaksi ?? '-' }}</td>
                        <td>{{ $p->tanggal?->format('d/m/Y') }}</td>
                        <td>{{ $p->stok?->nama ?? '-' }}</td>
                        <td>{{ $p->qty }}</td>
                        <td>{{ formatRp($p->harga_satuan) }}</td>
                        <td><strong>{{ formatRp($p->total) }}</strong></td>
                        <td style="color:var(--success);font-weight:700">{{ formatRp($p->laba_bersih ?? ($p->total - $p->modal_total)) }}</td>
                        <td><span class="badge badge-masuk">{{ $p->metode_bayar }}</span></td>
                        <td>{{ $p->pelanggan?->nama ?? 'Umum' }}</td>
                        <td style="white-space:nowrap">
                            <a href="{{ route('penjualan-sparepart.show', $p) }}" class="btn btn-secondary btn-xs" title="Detail"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('print.penjualan-sparepart', $p) }}" class="btn btn-secondary btn-xs" target="_blank" title="Print Thermal"><i class="fas fa-print"></i></a>
                            @if($p->status !== 'Dibatalkan')
                            <button onclick="openBatalPenjualanModal({{ $p->id }}, '{{ $p->kode }}')" class="btn btn-xs" style="background:#dc2626;color:#fff" title="Batalkan Transaksi"><i class="fas fa-ban"></i></button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="12" style="text-align:center;color:#94a3b8;padding:20px">Belum ada data penjualan sparepart.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $penjualans->links() }}
    </div>
</div>
{{-- html5-qrcode library for barcode scanning --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
// ==================== TAB SWITCHING ====================
function switchTab(tab) {
    document.querySelectorAll('.pos-page-tab').forEach((el, i) => {
        if (i < 2) el.classList.toggle('active', (tab === 'pos' && i === 0) || (tab === 'riwayat' && i === 1));
    });
    document.getElementById('tab-pos').style.display = tab === 'pos' ? '' : 'none';
    document.getElementById('tab-riwayat').style.display = tab === 'riwayat' ? '' : 'none';
    if (tab === 'riwayat') {
        // Close scanner if open
        if (scannerRunning) toggleBarcodeScanner();
    }
}

// ==================== CART SYSTEM ====================
let cart = [];
let selectedPayment = 'Cash';

function addToCart(stokId, nama, harga, stokTersedia, satuan) {
    if (stokTersedia <= 0) return;

    let existing = cart.find(item => item.stok_id === stokId);
    if (existing) {
        if (existing.qty >= stokTersedia) {
            showToast('warning', 'Stok tidak cukup! Tersedia: ' + stokTersedia + ' ' + satuan);
            return;
        }
        existing.qty++;
    } else {
        cart.push({ stok_id: stokId, nama: nama, harga_satuan: harga, qty: 1, stok: stokTersedia, satuan: satuan });
    }
    renderCart();
}

function removeFromCart(stokId) {
    cart = cart.filter(item => item.stok_id !== stokId);
    renderCart();
}

function updateQty(stokId, delta) {
    let item = cart.find(i => i.stok_id === stokId);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) {
        removeFromCart(stokId);
        return;
    }
    if (item.qty > item.stok) {
        item.qty = item.stok;
        showToast('warning', 'Maksimal stok: ' + item.stok + ' ' + item.satuan);
    }
    renderCart();
}

function setQty(stokId, value) {
    let item = cart.find(i => i.stok_id === stokId);
    if (!item) return;
    let qty = parseInt(value) || 1;
    if (qty <= 0) qty = 1;
    if (qty > item.stok) qty = item.stok;
    item.qty = qty;
    renderCart();
}

function clearCart() {
    if (cart.length === 0) return;
    if (!confirm('Kosongkan semua item dari keranjang?')) return;
    cart = [];
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const emptyEl = document.getElementById('cartEmpty');
    const countEl = document.getElementById('cartCount');
    const btnCheckout = document.getElementById('btnCheckout');

    if (cart.length === 0) {
        container.innerHTML = '<div class="cart-empty" id="cartEmpty"><i class="fas fa-shopping-basket"></i><span>Keranjang kosong</span><span style="font-size:.7rem;margin-top:4px">Scan barcode atau klik produk</span></div>';
        countEl.textContent = '0 item';
        btnCheckout.disabled = true;
        updateCartTotals();
        return;
    }

    let html = '';
    cart.forEach((item, idx) => {
        const subtotal = item.qty * item.harga_satuan;
        html += `
        <div class="cart-item">
            <div class="cart-item-info">
                <div class="cart-item-name">${item.nama}</div>
                <div class="cart-item-price">${formatRupiah(item.harga_satuan)} / ${item.satuan}</div>
            </div>
            <div class="cart-item-qty">
                <button onclick="updateQty(${item.stok_id}, -1)"><i class="fas fa-minus"></i></button>
                <input type="number" value="${item.qty}" min="1" max="${item.stok}"
                    onchange="setQty(${item.stok_id}, this.value)"
                    style="width:36px;text-align:center;border:1px solid #e2e8f0;border-radius:6px;padding:2px;font-size:.8rem;font-weight:700">
                <button onclick="updateQty(${item.stok_id}, 1)"><i class="fas fa-plus"></i></button>
            </div>
            <div class="cart-item-total">${formatRupiah(subtotal)}</div>
            <button class="cart-item-remove" onclick="removeFromCart(${item.stok_id})"><i class="fas fa-times"></i></button>
        </div>`;
    });
    container.innerHTML = html;

    const totalItems = cart.reduce((sum, i) => sum + i.qty, 0);
    countEl.textContent = totalItems + ' item';
    btnCheckout.disabled = false;
    updateCartTotals();
}

function updateCartTotals() {
    const subtotal = cart.reduce((sum, i) => sum + (i.qty * i.harga_satuan), 0);
    const diskon = parseFloat(document.getElementById('cartDiskon').value) || 0;
    const total = Math.max(0, subtotal - diskon);

    document.getElementById('cartSubtotal').textContent = formatRupiah(subtotal);
    document.getElementById('cartDiskonDisplay').textContent = '- ' + formatRupiah(diskon);
    document.getElementById('cartTotal').textContent = formatRupiah(total);
    document.getElementById('btnCheckoutText').textContent = 'Bayar ' + formatRupiah(total);
}

function formatRupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

// ==================== PAYMENT METHOD ====================
function selectPayment(method) {
    selectedPayment = method;
    document.querySelectorAll('.payment-option').forEach(el => {
        const m = el.dataset.method;
        if (m === method) {
            el.style.border = '2px solid var(--primary)';
            el.style.background = 'var(--primary-bg)';
            el.style.color = 'var(--primary)';
            el.style.fontWeight = '700';
        } else {
            el.style.border = '2px solid #e2e8f0';
            el.style.background = 'transparent';
            el.style.color = '#64748b';
            el.style.fontWeight = '600';
        }
    });
}

// ==================== SEARCH & BARCODE ====================
let _suggestTimer = null;
let _suggestAbort = null;

/** Real-time search dropdown (Fitur #12) — debounce 200ms */
function onPosSearchInput(q) {
    const box = document.getElementById('posSearchSuggest');
    if (!q || q.trim().length < 1) { hideSearchSuggest(); return; }
    clearTimeout(_suggestTimer);
    _suggestTimer = setTimeout(() => {
        if (_suggestAbort) { try { _suggestAbort.abort(); } catch(e){} }
        _suggestAbort = new AbortController();
        fetch('{{ route("penjualan-sparepart.api.search-suggest") }}?q=' + encodeURIComponent(q), { signal: _suggestAbort.signal })
            .then(r => r.json())
            .then(data => renderSearchSuggest(data.products || [], q))
            .catch(err => { if (err.name !== 'AbortError') hideSearchSuggest(); });
    }, 200);
}

function renderSearchSuggest(products, q) {
    const box = document.getElementById('posSearchSuggest');
    if (!products.length) {
        box.innerHTML = '<div style="padding:14px 16px;color:#94a3b8;font-size:.82rem;text-align:center"><i class="fas fa-search" style="margin-right:6px"></i>Tidak ada produk untuk "' + escapeHtml(q) + '"</div>';
        box.style.display = 'block';
        return;
    }
    box.innerHTML = products.map(p => {
        const out = p.stok <= 0;
        const low = p.low_stock && !out;
        return '<div onclick="pickSuggest(' + p.id + ',\'' + escapeHtml(p.nama).replace(/'/g, "\\'") + '\',' + p.harga_jual + ',' + p.stok + ',\'' + (p.satuan||'pcs') + '\')" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid #f1f5f9;cursor:pointer' + (out ? ';opacity:.55' : '') + '" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'transparent\'">'
            + '<div style="width:36px;height:36px;border-radius:8px;background:' + (out ? '#fee2e2' : 'var(--primary-bg)') + ';display:flex;align-items:center;justify-content:center;color:' + (out ? '#dc2626' : 'var(--primary)') + '"><i class="fas fa-' + (out ? 'times' : 'box') + '"></i></div>'
            + '<div style="flex:1;min-width:0">'
            + '<div style="font-weight:600;font-size:.84rem;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + escapeHtml(p.nama) + '</div>'
            + '<div style="font-size:.66rem;color:#94a3b8">' + (p.kode||'-') + (p.barcode ? ' · ' + p.barcode : '') + ' · modal Rp ' + (p.harga_modal||0).toLocaleString('id-ID') + '</div>'
            + '</div>'
            + '<div style="text-align:right">'
            + '<div style="font-weight:700;font-size:.84rem;color:var(--primary)">Rp ' + (p.harga_jual||0).toLocaleString('id-ID') + '</div>'
            + '<div style="font-size:.62rem;color:' + (out ? '#dc2626' : (low ? '#d97706' : '#16a34a')) + '">' + (out ? 'Stok habis' : 'Stok: ' + p.stok) + '</div>'
            + '</div>'
            + '</div>';
    }).join('');
    box.style.display = 'block';
}

function hideSearchSuggest() {
    const box = document.getElementById('posSearchSuggest');
    if (box) box.style.display = 'none';
}

function pickSuggest(id, nama, harga, stok, satuan) {
    hideSearchSuggest();
    const input = document.getElementById('posSearchInput');
    if (input) input.value = '';
    if (stok <= 0) {
        showToast('error', 'Stok ' + nama + ' habis');
        return;
    }
    addToCart(id, nama, harga, stok, satuan);
    showToast('success', nama + ' ditambahkan ke keranjang');
}

function escapeHtml(s) {
    return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function searchAndAddProduct(query) {
    if (!query.trim()) return;
    hideSearchSuggest();

    // First try to find in loaded products (cepat)
    const products = document.querySelectorAll('.pos-product');
    let found = false;

    products.forEach(el => {
        if (found) return;
        const barcode = el.dataset.barcode;
        const kode = el.dataset.kode;
        if (barcode === query || kode === query) {
            const id = parseInt(el.dataset.id);
            const nama = el.dataset.nama;
            const hargaText = el.querySelector('.pos-product-price').textContent.replace(/[^\d]/g, '');
            const harga = parseInt(hargaText);
            const stokText = el.querySelector('.pos-product-stock').textContent.replace(/[^\d]/g, '');
            const stok = parseInt(stokText);
            addToCart(id, nama, harga, stok, 'pcs');
            found = true;
            el.style.background = 'var(--primary-bg)';
            el.style.borderColor = 'var(--primary)';
            setTimeout(() => { el.style.background = ''; el.style.borderColor = ''; }, 800);
        }
    });

    if (found) { showToast('success', 'Produk ditambahkan ke keranjang'); return; }

    // Search via API — tangani status berbeda (Fitur #12)
    const input = document.getElementById('posSearchInput');
    fetch('{{ route("penjualan-sparepart.api.search") }}?q=' + encodeURIComponent(query))
        .then(r => r.json())
        .then(data => {
            // Kasus: produk tidak ditemukan
            if (!data.found || data.status === 'not_found') {
                showToast('warning', data.message || ('Produk "' + query + '" tidak ditemukan'));
                return;
            }
            const p = data.product;
            // Kasus: stok habis
            if (data.status === 'out_of_stock') {
                showToast('error', 'Stok ' + p.nama + ' habis');
                return;
            }
            // Kasus: stok tidak cukup
            if (data.status === 'insufficient') {
                showToast('error', 'Stok ' + p.nama + ' tidak mencukupi (sisa ' + p.stok + ')');
                return;
            }
            // OK
            addToCart(p.id, p.nama, p.harga_jual, p.stok, p.satuan);
            showToast('success', p.nama + ' ditambahkan ke keranjang');
        })
        .catch(() => showToast('error', 'Gagal mencari produk'));
}

// Klik di luar dropdown → tutup
document.addEventListener('click', e => {
    const box = document.getElementById('posSearchSuggest');
    const input = document.getElementById('posSearchInput');
    if (box && !box.contains(e.target) && e.target !== input) hideSearchSuggest();
});

// ==================== BARCODE SCANNER ====================
let scannerRunning = false;
let html5QrCode = null;

function toggleBarcodeScanner() {
    const panel = document.getElementById('barcodeScannerPanel');
    const btn = document.getElementById('btnScanBarcode');

    if (scannerRunning) {
        // Stop scanner
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
                panel.style.display = 'none';
                btn.classList.remove('scanning');
                scannerRunning = false;
            }).catch(() => {
                panel.style.display = 'none';
                btn.classList.remove('scanning');
                scannerRunning = false;
            });
        }
        return;
    }

    // Start scanner
    panel.style.display = 'block';
    btn.classList.add('scanning');

    if (!html5QrCode) {
        html5QrCode = new Html5Qrcode("barcodeReader");
    }

    html5QrCode.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: { width: 250, height: 120 },
            formatsToSupport: [
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.QR_CODE,
            ]
        },
        (decodedText) => {
            // Barcode detected!
            searchAndAddProduct(decodedText);
            showToast('success', 'Barcode terdeteksi: ' + decodedText);
            // Beep sound
            try { new Audio('data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQAAAAA=').play(); } catch(e) {}
        },
        () => {} // ignore errors during scanning
    ).then(() => {
        scannerRunning = true;
    }).catch(err => {
        showToast('error', 'Gagal mengakses kamera: ' + err);
        panel.style.display = 'none';
        btn.classList.remove('scanning');
    });
}

// ==================== CHECKOUT ====================
function checkout() {
    if (cart.length === 0) return;

    const btn = document.getElementById('btnCheckout');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

    const data = {
        items: cart.map(i => ({
            stok_id: i.stok_id,
            qty: i.qty,
            harga_satuan: i.harga_satuan,
        })),
        metode_bayar: selectedPayment,
        pelanggan_id: document.getElementById('cartPelanggan').value || null,
        nama_pelanggan: document.getElementById('cartNamaPelanggan').value || null,
        no_hp_pelanggan: document.getElementById('cartNoHp').value || null,
        catatan: '',
        diskon: parseFloat(document.getElementById('cartDiskon').value) || 0,
    };

    // Use XMLHttpRequest for more reliable response handling
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '{{ route("penjualan-sparepart.store-cart") }}');
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.onload = function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> <span id="btnCheckoutText">Bayar Sekarang</span>';
        updateCartTotals();

        // Try parse JSON
        var responseData;
        try {
            responseData = JSON.parse(xhr.responseText);
        } catch(e) {
            // Response bukan JSON - kemungkinan HTML error page atau redirect
            // Cek status code - kalau 200/201 kemungkinan berhasil tapi return HTML
            if (xhr.status === 200 || xhr.status === 201 || xhr.status === 302) {
                // Transaksi mungkin berhasil, arahkan ke riwayat
                cart = [];
                renderCart();
                showToast('success', 'Transaksi berhasil! Mengalihkan...');
                setTimeout(function() {
                    switchTab('riwayat');
                    location.reload();
                }, 1500);
            } else {
                showToast('error', 'Server error (HTTP ' + xhr.status + '). Cek riwayat transaksi.');
                console.error('Response:', xhr.responseText.substring(0, 500));
            }
            return;
        }

        // Parsed JSON successfully
        if (responseData.success) {
            // Clear cart
            cart = [];
            renderCart();

            var noTrx = responseData.data ? responseData.data.no_transaksi : '-';
            var total = responseData.data ? responseData.data.total : 0;
            var itemCount = responseData.data ? responseData.data.items_count : 0;
            var printId = (responseData.data && responseData.data.ids && responseData.data.ids.length > 0) ? responseData.data.ids[0] : null;
            showSuccessModal(noTrx, total, itemCount, printId);
        } else {
            showToast('error', responseData.message || 'Gagal memproses transaksi');
        }
    };

    xhr.onerror = function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> <span id="btnCheckoutText">Bayar Sekarang</span>';
        updateCartTotals();
        showToast('error', 'Koneksi gagal. Cek internet Anda.');
    };

    xhr.send(JSON.stringify(data));
}

// ==================== SUCCESS MODAL ====================
function showSuccessModal(noTrx, total, itemCount, printId) {
    const modal = document.getElementById('successModal');
    document.getElementById('successNoTrx').textContent = noTrx;
    document.getElementById('successTotal').textContent = formatRupiah(total);
    document.getElementById('successItems').textContent = itemCount + ' item';
    document.getElementById('successMetode').textContent = selectedPayment;

    // Print button
    const btnPrint = document.getElementById('btnPrintStruk');
    if (printId) {
        btnPrint.style.display = 'flex';
        btnPrint.onclick = function() {
            window.open('{{ route("print.penjualan-sparepart", 0) }}'.replace('/0', '/' + printId), '_blank');
        };
    } else {
        btnPrint.style.display = 'none';
    }

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeSuccessModal() {
    document.getElementById('successModal').style.display = 'none';
    document.body.style.overflow = '';
    // Redirect ke tab riwayat
    switchTab('riwayat');
    location.reload();
}

// ==================== CATEGORY FILTER ====================
function filterCategory(kategori) {
    document.querySelectorAll('.pos-tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');

    document.querySelectorAll('.pos-product').forEach(p => {
        if (!kategori || p.dataset.kategori === kategori) {
            p.style.display = '';
        } else {
            p.style.display = 'none';
        }
    });
}

// Build category tabs from loaded products
(function() {
    const cats = new Set();
    document.querySelectorAll('.pos-product').forEach(p => {
        if (p.dataset.kategori) cats.add(p.dataset.kategori);
    });
    const tabsContainer = document.getElementById('categoryTabs');
    cats.forEach(cat => {
        const tab = document.createElement('div');
        tab.className = 'pos-tab';
        tab.textContent = cat;
        tab.onclick = function() { filterCategory(cat); };
        tabsContainer.appendChild(tab);
    });
})();

// ==================== TOAST NOTIFICATION ====================
function showToast(type, message) {
    const toast = document.getElementById('posToast');
    const icon = document.getElementById('posToastIcon');
    const msg = document.getElementById('posToastMsg');

    msg.textContent = message;
    if (type === 'success') {
        toast.style.background = '#dcfce7'; toast.style.color = '#166534'; toast.style.border = '1px solid #bbf7d0';
        icon.className = 'fas fa-check-circle';
    } else if (type === 'warning') {
        toast.style.background = '#fef3c7'; toast.style.color = '#92400e'; toast.style.border = '1px solid #fde68a';
        icon.className = 'fas fa-exclamation-triangle';
    } else {
        toast.style.background = '#fef2f2'; toast.style.color = '#991b1b'; toast.style.border = '1px solid #fecaca';
        icon.className = 'fas fa-times-circle';
    }

    toast.style.display = 'flex';
    setTimeout(() => { toast.style.transform = 'translateX(0)'; }, 50);
    setTimeout(() => {
        toast.style.transform = 'translateX(120%)';
        setTimeout(() => { toast.style.display = 'none'; }, 300);
    }, 3500);
}

// ==================== BATALKAN TRANSAKSI ====================
let batalPenjualanId = null;

function openBatalPenjualanModal(id, kode) {
    batalPenjualanId = id;
    document.getElementById('batalPenjualanKode').textContent = kode;
    document.getElementById('batalPenjualanAlasan').value = '';
    document.getElementById('batalPenjualanModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeBatalPenjualanModal() {
    document.getElementById('batalPenjualanModal').style.display = 'none';
    document.body.style.overflow = '';
    batalPenjualanId = null;
}

function submitBatalPenjualan() {
    if (!batalPenjualanId) return;
    const alasan = document.getElementById('batalPenjualanAlasan').value.trim();
    if (!alasan || alasan.length < 3) {
        showToast('error', 'Alasan pembatalan wajib diisi (minimal 3 karakter).');
        return;
    }
    const btn = document.getElementById('btnSubmitBatalPenjualan');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membatalkan...';

    fetch('{{ url("/penjualan-sparepart") }}/' + batalPenjualanId + '/batal', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ alasan: alasan })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            closeBatalPenjualanModal();
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(() => showToast('error', 'Terjadi kesalahan. Coba lagi.'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-ban"></i> Batalkan Transaksi';
    });
}

// ===== BULK DELETE (checkbox) =====
function updateBulkBarSP() {
    const checked = document.querySelectorAll('.bulk-check-sp:checked');
    const bar = document.getElementById('bulkDeleteFormSP');
    const counter = document.getElementById('bulkSelectedCountSP');
    const selectAll = document.getElementById('selectAllSP');
    if (bar) bar.style.display = checked.length > 0 ? 'block' : 'none';
    if (counter) counter.textContent = checked.length;
    if (selectAll) selectAll.checked = checked.length > 0 && checked.length === document.querySelectorAll('.bulk-check-sp').length;
}
function toggleAllSP(master) {
    document.querySelectorAll('.bulk-check-sp').forEach(cb => { cb.checked = master.checked; });
    updateBulkBarSP();
}
function clearBulkSP() {
    document.querySelectorAll('.bulk-check-sp').forEach(cb => { cb.checked = false; });
    const selectAll = document.getElementById('selectAllSP'); if (selectAll) selectAll.checked = false;
    updateBulkBarSP();
}
function confirmBulkSP() {
    const checked = document.querySelectorAll('.bulk-check-sp:checked');
    if (checked.length === 0) { showToast('error', 'Pilih minimal satu item.'); return; }
    if (!confirm('Hapus ' + checked.length + ' transaksi terpilih?\n\nStok akan dikembalikan otomatis. Tindakan ini tidak bisa dibatalkan.')) return;
    const ids = Array.from(checked).map(cb => cb.value);
    const form = document.getElementById('bulkDeleteFormSP');
    form.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
    ids.forEach(id => { const inp = document.createElement('input'); inp.type='hidden'; inp.name='ids[]'; inp.value=id; form.appendChild(inp); });
    form.submit();
}
</script>

{{-- ==================== MODAL SUKSES CHECKOUT ==================== --}}
<div id="successModal" style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center">
    <div onclick="closeSuccessModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px)"></div>
    <div style="position:relative;background:#fff;border-radius:20px;max-width:460px;width:92%;box-shadow:0 25px 60px rgba(0,0,0,.25);animation:modalIn .25s ease;overflow:hidden">
        {{-- Header --}}
        <div style="padding:28px 24px 16px;text-align:center;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff">
            <div style="width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 12px">
                <i class="fas fa-check-circle"></i>
            </div>
            <div style="font-size:1.2rem;font-weight:800">Transaksi Berhasil!</div>
            <div style="font-size:.8rem;opacity:.85;margin-top:4px">Pembayaran telah dikonfirmasi</div>
        </div>

        {{-- Detail --}}
        <div style="padding:20px 24px">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px;margin-bottom:16px">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.84rem">
                    <span style="color:#64748b">No. Transaksi</span>
                    <strong style="color:var(--primary)" id="successNoTrx">-</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.84rem">
                    <span style="color:#64748b">Total Item</span>
                    <strong id="successItems">-</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.84rem">
                    <span style="color:#64748b">Metode Bayar</span>
                    <strong id="successMetode">-</strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding-top:10px;border-top:2px dashed #bbf7d0;font-size:1.15rem;font-weight:800">
                    <span style="color:var(--primary-dark)">Total Bayar</span>
                    <span style="color:var(--primary)" id="successTotal">Rp 0</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div style="display:flex;flex-direction:column;gap:8px">
                <button id="btnPrintStruk" onclick="" style="width:100%;padding:12px;border-radius:12px;border:none;background:linear-gradient(135deg,#1e293b,#334155);color:#fff;font-weight:700;cursor:pointer;font-size:.9rem;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s">
                    <i class="fas fa-print"></i> Cetak Struk Thermal
                </button>
                <button onclick="closeSuccessModal()" style="width:100%;padding:12px;border-radius:12px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-weight:600;cursor:pointer;font-size:.88rem;display:flex;align-items:center;justify-content:center;gap:8px">
                    <i class="fas fa-check"></i> Selesai
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="posToast" style="display:none;position:fixed;top:24px;right:24px;z-index:10001;min-width:300px;max-width:420px;padding:14px 20px;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.15);font-size:.88rem;font-weight:600;transition:all .3s ease;transform:translateX(120%);align-items:center;gap:10px">
    <i id="posToastIcon"></i>
    <span id="posToastMsg"></span>
</div>

{{-- Modal Batalkan --}}
<div id="batalPenjualanModal" style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center">
    <div onclick="closeBatalPenjualanModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px)"></div>
    <div style="position:relative;background:#fff;border-radius:16px;max-width:440px;width:92%;box-shadow:0 25px 60px rgba(0,0,0,.25);animation:modalIn .25s ease">
        <div style="padding:20px 24px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f1f5f9">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:40px;height:40px;border-radius:10px;background:#fef2f2;display:flex;align-items:center;justify-content:center;font-size:1.1rem">🚫</div>
                <div>
                    <div style="font-size:1rem;font-weight:700;color:#dc2626">Batalkan Transaksi</div>
                    <div id="batalPenjualanKode" style="font-size:.78rem;color:#64748b">-</div>
                </div>
            </div>
            <button onclick="closeBatalPenjualanModal()" style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:1rem;color:#64748b;display:flex;align-items:center;justify-content:center"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:20px 24px">
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px;margin-bottom:16px">
                <div style="font-size:.82rem;color:#991b1b;display:flex;align-items:flex-start;gap:8px">
                    <i class="fas fa-exclamation-triangle" style="margin-top:2px"></i>
                    <div>Stok barang akan dikembalikan secara otomatis.</div>
                </div>
            </div>
            <label style="font-size:.82rem;font-weight:700;color:#374151;display:block;margin-bottom:6px">Alasan Pembatalan <span style="color:#dc2626">*</span></label>
            <textarea id="batalPenjualanAlasan" rows="3" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.84rem;resize:vertical" placeholder="Masukkan alasan pembatalan..."></textarea>
        </div>
        <div style="padding:0 24px 20px;display:flex;gap:10px">
            <button onclick="closeBatalPenjualanModal()" style="flex:1;padding:10px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-weight:600;cursor:pointer">Kembali</button>
            <button onclick="submitBatalPenjualan()" id="btnSubmitBatalPenjualan" style="flex:1;padding:10px;border-radius:10px;border:none;background:#dc2626;color:#fff;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px"><i class="fas fa-ban"></i> Batalkan</button>
        </div>
    </div>
</div>

<style>
@keyframes modalIn { from { opacity: 0; transform: scale(.92) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.badge-dibatalkan { background: #fef2f2 !important; color: #dc2626 !important; }
</style>
@endsection
