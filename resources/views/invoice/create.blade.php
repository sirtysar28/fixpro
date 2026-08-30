@extends('layouts.app')
@section('title', 'Invoice Sparepart - FIXPRO')

@section('content')
<style>
.inv-container { display: grid; grid-template-columns: 1fr 420px; gap: 18px; height: calc(100vh - 210px); min-height: 620px; }
.inv-left { display: flex; flex-direction: column; gap: 14px; overflow: hidden; }
.inv-right { display: flex; flex-direction: column; background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; }
.inv-search { display: flex; gap: 10px; align-items: center; position: relative; }
.inv-search input { flex: 1; padding: 12px 16px 12px 44px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: .92rem; font-weight: 500; transition: all .2s; }
.inv-search input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-bg); outline: none; }
.inv-search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1rem; z-index: 2; }
.inv-products { flex: 1; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(155px, 1fr)); gap: 10px; align-content: start; padding: 4px; }
.inv-product { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px; cursor: pointer; transition: all .2s; display: flex; flex-direction: column; gap: 4px; }
.inv-product:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(13,148,136,.1); transform: translateY(-2px); }
.inv-product.out { opacity: .4; pointer-events: none; }
.inv-product-name { font-size: .76rem; font-weight: 600; color: #1e293b; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.inv-product-code { font-size: .62rem; color: #94a3b8; font-weight: 500; }
.inv-product-price { font-size: .86rem; font-weight: 800; color: var(--primary); }
.inv-product-tiers { font-size: .58rem; color: #64748b; line-height: 1.5; }
.inv-product-tiers b { color: #0d9488; }
.inv-product-stock { font-size: .62rem; color: #64748b; }
.inv-product-stock.low { color: #dc2626; font-weight: 700; }
.cart-header { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; }
.cart-header h3 { font-size: .95rem; margin: 0; display: flex; align-items: center; gap: 8px; }
.cart-items { flex: 1; overflow-y: auto; padding: 8px; }
.cart-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 36px 16px; color: #94a3b8; font-size: .84rem; }
.ci { border: 1px solid #f1f5f9; border-radius: 10px; padding: 8px 10px; margin-bottom: 6px; }
.ci:hover { background: #f8fafc; }
.ci-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.ci-name { font-size: .78rem; font-weight: 700; color: #1e293b; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ci-tier { font-size: .58rem; font-weight: 800; padding: 2px 8px; border-radius: 10px; background: var(--primary-bg); color: var(--primary); white-space: nowrap; }
.ci-tier.manual { background: #fef3c7; color: #b45309; }
.ci-tier.khusus { background: #ede9fe; color: #7c3aed; }
.ci-mid { display: flex; align-items: center; gap: 6px; margin-top: 6px; flex-wrap: wrap; }
.ci-mid input { border: 1px solid #e2e8f0; border-radius: 6px; padding: 3px 6px; font-size: .76rem; font-weight: 700; }
.ci-mid input:focus { outline: none; border-color: var(--primary); }
.ci-qty { display: flex; align-items: center; gap: 3px; }
.ci-qty button { width: 22px; height: 22px; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; font-size: .64rem; display: flex; align-items: center; justify-content: center; }
.ci-qty button:hover { background: var(--primary); color: #fff; }
.ci-sub { font-size: .84rem; font-weight: 800; color: var(--primary); margin-left: auto; }
.cart-footer { border-top: 1px solid #e2e8f0; padding: 12px 14px; }
.sum-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; font-size: .8rem; }
.sum-row.total { font-size: 1.05rem; font-weight: 800; color: var(--primary); margin: 8px 0 4px; padding-top: 8px; border-top: 2px dashed #e2e8f0; }
.pm-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin: 10px 0; }
.pm-opt { padding: 7px 2px; border-radius: 8px; border: 2px solid #e2e8f0; text-align: center; font-size: .68rem; font-weight: 700; color: #64748b; cursor: pointer; transition: all .15s; }
.pm-opt.active { border-color: var(--primary); background: var(--primary-bg); color: var(--primary); }
.btn-inv-checkout { width: 100%; padding: 13px; border-radius: 12px; border: none; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; font-size: .92rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all .2s; }
.btn-inv-checkout:hover { opacity: .9; transform: translateY(-1px); }
.btn-inv-checkout:disabled { opacity: .4; cursor: not-allowed; transform: none; }
.inv-tabs { display: flex; gap: 0; margin-bottom: 16px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
.inv-tab { flex: 1; padding: 11px 14px; font-size: .84rem; font-weight: 600; color: #64748b; cursor: pointer; text-align: center; transition: all .2s; border-bottom: 3px solid transparent; text-decoration: none; }
.inv-tab:hover { color: var(--primary); background: var(--primary-bg); }
.inv-tab.active { color: var(--primary); border-bottom-color: var(--primary); background: var(--primary-bg); }
.alert-limit { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; padding: 8px 10px; font-size: .72rem; margin-top: 6px; }
.approval-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 10px; margin-top: 8px; font-size: .74rem; color: #92400e; }
.approval-box input { border: 1px solid #fde68a; border-radius: 6px; padding: 5px 8px; font-size: .76rem; width: 100%; margin-top: 4px; }
body.dark .inv-product, body.dark .ci { background: #1e293b; border-color: #334155; }
body.dark .inv-product-name, body.dark .ci-name { color: #e2e8f0; }
body.dark .ci:hover { background: #0f172a; }
@media (max-width: 900px) { .inv-container { grid-template-columns: 1fr; height: auto; } .inv-right { max-height: 60vh; } }
</style>

<div class="inv-tabs">
    <div class="inv-tab active"><i class="fas fa-file-invoice"></i> Invoice Sparepart</div>
    <a href="{{ route('invoice.riwayat') }}" class="inv-tab"><i class="fas fa-history"></i> Riwayat Invoice</a>
    <a href="{{ route('invoice.piutang') }}" class="inv-tab"><i class="fas fa-hand-holding-usd"></i> Piutang</a>
    <a href="{{ route('invoice.retur') }}" class="inv-tab"><i class="fas fa-undo"></i> Retur</a>
</div>

{{-- Stats --}}
<div class="stats-grid" style="margin-bottom:14px">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-cash-register"></i></div>
        <div class="stat-label">Penjualan Hari Ini</div>
        <div class="stat-value" style="color:var(--primary);font-size:1.2rem">{{ formatRp($omsetHariIni) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="stat-label">Total Piutang</div>
        <div class="stat-value" style="color:var(--warning);font-size:1.2rem">{{ formatRp($piutangAktif) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-alarm-clock"></i></div>
        <div class="stat-label">Jatuh Tempo</div>
        <div class="stat-value" style="color:var(--danger);font-size:1.2rem">{{ $jatuhTempo }}</div>
    </div>
</div>

<div class="inv-container">
    <!-- KIRI: PRODUK -->
    <div class="inv-left">
        <div class="inv-search">
            <i class="fas fa-search inv-search-icon"></i>
            <input type="text" id="invSearch" placeholder="Cari sparepart (nama / kode / merk HP)..." autocomplete="off" autofocus>
        </div>
        <div class="inv-products" id="invGrid">
            @foreach($stoks as $s)
            @php
                $hg = $hargaMap->get($s->id);
                $tiers = [
                    'grosir1' => ['harga' => $hg ? (float)$hg->harga_grosir1 : 0, 'min' => $hg ? (int)$hg->min_qty_grosir1 : 5],
                    'grosir2' => ['harga' => $hg ? (float)$hg->harga_grosir2 : 0, 'min' => $hg ? (int)$hg->min_qty_grosir2 : 10],
                    'grosir3' => ['harga' => $hg ? (float)$hg->harga_grosir3 : 0, 'min' => $hg ? (int)$hg->min_qty_grosir3 : 20],
                    'reseller' => ['harga' => $hg ? (float)$hg->harga_reseller : 0, 'min' => 1],
                    'member' => ['harga' => $hg ? (float)$hg->harga_member : 0, 'min' => 1],
                ];
            @endphp
            <div class="inv-product {{ $s->stok <= 0 ? 'out' : '' }}"
                 onclick="addProduct({{ $s->id }})"
                 data-id="{{ $s->id }}"
                 data-nama="{{ $s->nama }}"
                 data-kode="{{ $s->kode }}"
                 data-kategori="{{ $s->kategori }}"
                 data-retail="{{ (float) $s->jual }}"
                 data-stok="{{ (int) $s->stok }}"
                 data-tiers='@json($tiers)'
                 data-search="{{ mb_strtolower($s->nama . ' ' . $s->kode . ' ' . ($s->merk_hp ?? '') . ' ' . $s->kategori) }}">
                <div class="inv-product-name">{{ $s->nama }}</div>
                <div class="inv-product-code">{{ $s->kode }}@if($s->merk_hp) · {{ $s->merk_hp }}@endif</div>
                <div class="inv-product-price">{{ formatRp($s->jual) }}</div>
                <div class="inv-product-tiers">
                    @if($tiers['grosir1']['harga'] > 0)<span>G1≥{{ $tiers['grosir1']['min'] }}: <b>{{ number_format($tiers['grosir1']['harga'], 0, ',', '.') }}</b></span>@endif
                    @if($tiers['grosir2']['harga'] > 0)<span>G2≥{{ $tiers['grosir2']['min'] }}: <b>{{ number_format($tiers['grosir2']['harga'], 0, ',', '.') }}</b></span>@endif
                    @if($tiers['grosir3']['harga'] > 0)<span>G3≥{{ $tiers['grosir3']['min'] }}: <b>{{ number_format($tiers['grosir3']['harga'], 0, ',', '.') }}</b></span>@endif
                </div>
                <div class="inv-product-stock {{ $s->stok <= ($s->min_alert ?? 3) ? 'low' : '' }}">Stok: {{ $s->stok }} {{ $s->satuan ?? 'pcs' }}</div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- KANAN: KERANJANG -->
    <div class="inv-right">
        <div class="cart-header">
            <h3><i class="fas fa-file-invoice-dollar"></i> Invoice Baru</h3>
            <span id="invCount" style="background:rgba(255,255,255,.25);padding:2px 10px;border-radius:12px;font-size:.7rem;font-weight:700">0 item</span>
        </div>

        <div style="padding:10px 12px;border-bottom:1px solid #f1f5f9;max-height:230px;overflow-y:auto">
            <select id="invPelanggan" class="form-input" style="width:100%;padding:8px 10px;font-size:.78rem;border-radius:8px" onchange="onPelangganChange()">
                <option value="">— Umum / Tanpa Pelanggan —</option>
                @foreach($pelanggans as $p)
                <option value="{{ $p->id }}"
                        data-tipe="{{ $p->tipe }}"
                        data-nama="{{ $p->nama }}"
                        data-wa="{{ $p->no_hp }}"
                        data-alamat="{{ $p->alamat }}"
                        data-limit="{{ (float) $p->limit_piutang }}"
                        data-outstanding="{{ (float) ($outstanding[$p->id] ?? 0) }}">
                    [{{ $p->tipe }}] {{ $p->nama }} — {{ $p->no_hp }}
                </option>
                @endforeach
            </select>

            <div style="display:flex;gap:6px;margin-top:6px">
                <input type="text" id="invNamaBaru" class="form-input" placeholder="Nama pelanggan baru..." style="flex:1;padding:7px 10px;font-size:.74rem;border-radius:8px">
                <input type="text" id="invWaBaru" class="form-input" placeholder="No. WA" style="width:95px;padding:7px 10px;font-size:.74rem;border-radius:8px">
            </div>
            <input type="text" id="invAlamatBaru" class="form-input" placeholder="Alamat (opsional)" style="width:100%;margin-top:6px;padding:7px 10px;font-size:.74rem;border-radius:8px">

            @if(count($sumberCabangs) > 1)
            <div style="display:flex;gap:6px;margin-top:6px;align-items:center">
                <span style="font-size:.68rem;font-weight:700;color:#64748b;white-space:nowrap"><i class="fas fa-warehouse"></i> Stok dari:</span>
                <select id="invSumber" class="form-input" style="flex:1;padding:6px 8px;font-size:.72rem;border-radius:8px">
                    @foreach($sumberCabangs as $c)
                    <option value="{{ $c->id }}" {{ $c->id == auth()->user()->getActiveCabangId() ? 'selected' : '' }}>{{ $c->nama }} ({{ $c->tipe ?? 'toko' }})</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div id="invLimitInfo"></div>
        </div>

        <div class="cart-items" id="invItems">
            <div class="cart-empty"><i class="fas fa-shopping-basket" style="font-size:2rem;opacity:.3;margin-bottom:8px"></i><span>Keranjang kosong — klik produk di kiri</span></div>
        </div>

        <div class="cart-footer">
            <div class="sum-row"><span style="color:#64748b">Subtotal</span><span id="invSubtotal" style="font-weight:600">Rp 0</span></div>
            <div class="sum-row"><span style="color:#64748b">Diskon Item</span><span id="invDiskonItem" style="color:#dc2626;font-weight:600">- Rp 0</span></div>
            <div class="sum-row" style="gap:8px">
                <span style="color:#64748b;white-space:nowrap"><i class="fas fa-tags"></i> Diskon Nota</span>
                <input type="number" id="invDiskonTotal" value="0" min="0" style="flex:1;width:80px;padding:4px 8px;font-size:.78rem;font-weight:700;border:1px solid #e2e8f0;border-radius:6px;text-align:right" oninput="renderSummary()">
            </div>
            <div class="sum-row total"><span>TOTAL</span><span id="invTotal">Rp 0</span></div>

            <div class="pm-grid">
                @foreach(['Tunai','Transfer','QRIS','DP','Tempo'] as $m)
                <div class="pm-opt {{ $m === 'Tunai' ? 'active' : '' }}" data-metode="{{ $m }}" onclick="selectMetode('{{ $m }}')">{{ $m }}</div>
                @endforeach
            </div>

            <div id="invBayarBox" style="display:none">
                <div style="display:flex;gap:6px;align-items:center;margin-bottom:6px">
                    <span style="font-size:.72rem;font-weight:700;color:#64748b;white-space:nowrap">Bayar Sekarang</span>
                    <input type="number" id="invBayar" value="0" min="0" style="flex:1;padding:5px 8px;font-size:.78rem;font-weight:700;border:1px solid #e2e8f0;border-radius:6px;text-align:right" oninput="renderSummary()">
                </div>
                <div style="display:flex;gap:6px;align-items:center">
                    <span style="font-size:.72rem;font-weight:700;color:#64748b;white-space:nowrap">Jatuh Tempo</span>
                    <input type="date" id="invJatuhTempo" class="form-input" style="flex:1;padding:5px 8px;font-size:.74rem;border-radius:6px" value="{{ now()->addDays(30)->format('Y-m-d') }}">
                    <select id="invMetodeDp" class="form-input" style="padding:5px 8px;font-size:.72rem;border-radius:6px">
                        <option value="Tunai">Tunai</option><option value="Transfer">Transfer</option><option value="QRIS">QRIS</option>
                    </select>
                </div>
                <div class="sum-row" style="margin-top:6px"><span style="color:#dc2626;font-weight:700">SISA (PIUTANG)</span><span id="invSisa" style="color:#dc2626;font-weight:800">Rp 0</span></div>
            </div>

            <div id="invApprovalBox" class="approval-box" style="display:none">
                <b><i class="fas fa-user-shield"></i> Approval Diskon dibutuhkan (>{{ $maxDiskonPersen }}%)</b>
                <input type="email" id="invApprovalEmail" placeholder="Email Admin (approver)">
                <input type="password" id="invApprovalPassword" placeholder="Password Admin">
            </div>

            <button class="btn-inv-checkout" id="invBtnCheckout" onclick="submitInvoice()" style="margin-top:10px">
                <i class="fas fa-check-circle"></i> <span id="invBtnText">Simpan Invoice</span>
            </button>
        </div>
    </div>
</div>

{{-- ===== MODAL SUKSES ===== --}}
<div id="invSuccessModal" style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center">
    <div onclick="closeInvSuccess()" style="position:absolute;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px)"></div>
    <div style="position:relative;background:#fff;border-radius:20px;max-width:460px;width:92%;box-shadow:0 25px 60px rgba(0,0,0,.25);overflow:hidden">
        <div style="padding:26px 22px 14px;text-align:center;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff">
            <div style="width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 10px"><i class="fas fa-check-circle"></i></div>
            <div style="font-size:1.15rem;font-weight:800">Invoice Berhasil!</div>
            <div style="font-size:.78rem;opacity:.85;margin-top:4px" id="invSuccessNo">-</div>
        </div>
        <div style="padding:18px 22px">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px;margin-bottom:14px;font-size:.84rem">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="color:#64748b">Status</span><strong id="invSuccessStatus">-</strong></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="color:#64748b">Total</span><strong id="invSuccessTotal">-</strong></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="color:#64748b">Dibayar</span><strong id="invSuccessBayar">-</strong></div>
                <div style="display:flex;justify-content:space-between"><span style="color:#64748b">Sisa</span><strong id="invSuccessSisa" style="color:#dc2626">-</strong></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <a id="btnInv58" href="#" target="_blank" class="btn btn-secondary btn-sm" style="text-align:center;text-decoration:none"><i class="fas fa-print"></i> Thermal 58mm</a>
                <a id="btnInv80" href="#" target="_blank" class="btn btn-secondary btn-sm" style="text-align:center;text-decoration:none"><i class="fas fa-print"></i> Thermal 80mm</a>
                <a id="btnInvPdf" href="#" target="_blank" class="btn btn-secondary btn-sm" style="text-align:center;text-decoration:none"><i class="fas fa-file-pdf"></i> PDF A4</a>
                <a id="btnInvWa" href="#" target="_blank" class="btn btn-success btn-sm" style="text-align:center;text-decoration:none;background:#16a34a;color:#fff"><i class="fab fa-whatsapp"></i> Kirim WA</a>
            </div>
            <button onclick="closeInvSuccess()" class="btn btn-primary" style="width:100%;margin-top:10px"><i class="fas fa-check"></i> Selesai — Buat Invoice Baru</button>
        </div>
    </div>
</div>

<div id="invToast" style="display:none;position:fixed;top:24px;right:24px;z-index:10001;min-width:300px;max-width:420px;padding:14px 20px;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.15);font-size:.88rem;font-weight:600;transition:all .3s;transform:translateX(120%);align-items:center;gap:10px">
    <i id="invToastIcon"></i><span id="invToastMsg"></span>
</div>

<script>
// ================= STATE =================
let cart = [];               // {id, nama, kode, qty, harga, jenis, manual, diskon, stok, tiers}
let selectedMetode = 'Tunai';
let khususMap = {};          // harga khusus per pelanggan {stokId: harga}
let pelangganTipe = '';      // tipe pelanggan aktif
const MAX_DISKON_PERSEN = {{ $maxDiskonPersen }};

// ================= UTIL =================
const rp = n => 'Rp ' + Math.round(Number(n || 0)).toLocaleString('id-ID');

function invToast(type, msg) {
    const t = document.getElementById('invToast');
    document.getElementById('invToastMsg').textContent = msg;
    const icon = document.getElementById('invToastIcon');
    if (type === 'success') { t.style.background = '#dcfce7'; t.style.color = '#166534'; t.style.border = '1px solid #bbf7d0'; icon.className = 'fas fa-check-circle'; }
    else if (type === 'warning') { t.style.background = '#fef3c7'; t.style.color = '#92400e'; t.style.border = '1px solid #fde68a'; icon.className = 'fas fa-exclamation-triangle'; }
    else { t.style.background = '#fef2f2'; t.style.color = '#991b1b'; t.style.border = '1px solid #fecaca'; icon.className = 'fas fa-times-circle'; }
    t.style.display = 'flex';
    setTimeout(() => t.style.transform = 'translateX(0)', 30);
    setTimeout(() => { t.style.transform = 'translateX(120%)'; setTimeout(() => t.style.display = 'none', 300); }, 4000);
}

// ================= HARGA OTOMATIS =================
// Prioritas: Khusus > Tipe (Reseller/Member) > Qty (Grosir 1-3) > Retail
function resolveHarga(item) {
    const khusus = Number(khususMap[item.id] || 0);
    if (khusus > 0) return { harga: khusus, jenis: 'khusus', label: 'Khusus' };
    if (pelangganTipe === 'Reseller' && item.tiers.reseller.harga > 0) return { harga: item.tiers.reseller.harga, jenis: 'reseller', label: 'Reseller' };
    if (pelangganTipe === 'Member' && item.tiers.member.harga > 0) return { harga: item.tiers.member.harga, jenis: 'member', label: 'Member' };
    if (pelangganTipe === 'Distributor' && item.tiers.reseller.harga > 0) return { harga: item.tiers.reseller.harga, jenis: 'reseller', label: 'Reseller' };
    const q = item.qty;
    if (q >= item.tiers.grosir3.min && item.tiers.grosir3.harga > 0) return { harga: item.tiers.grosir3.harga, jenis: 'grosir3', label: 'Grosir 3' };
    if (q >= item.tiers.grosir2.min && item.tiers.grosir2.harga > 0) return { harga: item.tiers.grosir2.harga, jenis: 'grosir2', label: 'Grosir 2' };
    if (q >= item.tiers.grosir1.min && item.tiers.grosir1.harga > 0) return { harga: item.tiers.grosir1.harga, jenis: 'grosir1', label: 'Grosir 1' };
    return { harga: item.retail, jenis: 'retail', label: 'Retail' };
}

// ================= PELANGGAN =================
function onPelangganChange() {
    const sel = document.getElementById('invPelanggan');
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('invLimitInfo');
    khususMap = {};

    if (!sel.value) {
        pelangganTipe = '';
        info.innerHTML = '';
        reRender(false);
        return;
    }

    pelangganTipe = opt.dataset.tipe || 'Umum';
    const limit = Number(opt.dataset.limit || 0);
    const outstanding = Number(opt.dataset.outstanding || 0);

    let html = `<div style="margin-top:6px;font-size:.68rem;color:#64748b;background:#f8fafc;border-radius:8px;padding:6px 8px">
        <b>${opt.dataset.nama}</b> — Tipe: <b style="color:var(--primary)">${pelangganTipe}</b>`;
    if (limit > 0) {
        const sisaLimit = limit - outstanding;
        const warna = sisaLimit <= 0 ? '#dc2626' : (outstanding / limit > 0.8 ? '#d97706' : '#16a34a');
        html += `<br>Limit piutang: <b>${rp(limit)}</b> · Terpakai: <b style="color:${warna}">${rp(outstanding)}</b> · Sisa limit: <b style="color:${warna}">${rp(Math.max(0, sisaLimit))}</b>`;
        if (sisaLimit <= 0) html += `<div class="alert-limit"><i class="fas fa-exclamation-triangle"></i> Limit piutang sudah habis — transaksi tempo akan ditolak!</div>`;
    }
    html += '</div>';
    info.innerHTML = html;

    // Ambil harga khusus pelanggan
    fetch('{{ route("invoice.api.khusus") }}?pelanggan_id=' + sel.value)
        .then(r => r.json())
        .then(d => { khususMap = d.khusus || {}; reRender(false); })
        .catch(() => reRender(false));
}

// ================= PRODUK =================
document.getElementById('invSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.inv-product').forEach(el => {
        el.style.display = (!q || el.dataset.search.includes(q)) ? '' : 'none';
    });
});

function addProduct(id) {
    const el = document.querySelector(`.inv-product[data-id="${id}"]`);
    if (!el) return;
    const tiers = JSON.parse(el.dataset.tiers);
    let item = cart.find(i => i.id === id);
    if (item) {
        if (item.qty >= Number(el.dataset.stok)) { invToast('warning', 'Stok maksimal: ' + el.dataset.stok); return; }
        item.qty++;
    } else {
        item = {
            id, nama: el.dataset.nama, kode: el.dataset.kode,
            qty: 1, retail: Number(el.dataset.retail), stok: Number(el.dataset.stok),
            tiers, manual: false, diskon: 0, harga: Number(el.dataset.retail), jenis: 'retail',
        };
        cart.push(item);
    }
    reRender(true);
}

function reRender(autoPrice) {
    cart.forEach(item => {
        if (autoPrice || !item.manual) {
            const r = resolveHarga(item);
            if (!item.manual) { item.harga = r.harga; item.jenis = r.jenis; item.label = r.label; }
        }
        if (item.manual) item.label = 'Manual';
        const rr = resolveHarga(item);
        item.label = item.manual ? 'Manual' : rr.label;
        item.jenis = item.manual ? 'manual' : rr.jenis;
    });
    renderCart();
}

function renderCart() {
    const box = document.getElementById('invItems');
    if (!cart.length) {
        box.innerHTML = '<div class="cart-empty"><i class="fas fa-shopping-basket" style="font-size:2rem;opacity:.3;margin-bottom:8px"></i><span>Keranjang kosong — klik produk di kiri</span></div>';
    } else {
        box.innerHTML = cart.map((it, idx) => {
            const sub = Math.max(0, it.qty * it.harga - (it.diskon || 0));
            return `<div class="ci">
                <div class="ci-top">
                    <span class="ci-name" title="${it.nama}">${it.nama}</span>
                    <span class="ci-tier ${it.manual ? 'manual' : it.jenis}">${it.label}</span>
                    <button onclick="removeItem(${idx})" style="background:#fef2f2;color:#dc2626;border:none;width:20px;height:20px;border-radius:5px;cursor:pointer;font-size:.6rem"><i class="fas fa-times"></i></button>
                </div>
                <div class="ci-mid">
                    <div class="ci-qty">
                        <button onclick="changeQty(${idx},-1)"><i class="fas fa-minus"></i></button>
                        <input type="number" value="${it.qty}" min="1" max="${it.stok}" style="width:42px;text-align:center" onchange="setQty(${idx}, this.value)">
                        <button onclick="changeQty(${idx},1)"><i class="fas fa-plus"></i></button>
                    </div>
                    <input type="number" value="${it.harga}" min="0" title="Harga satuan (boleh diubah manual)" style="width:95px;text-align:right" onchange="setHarga(${idx}, this.value)">
                    <input type="number" value="${it.diskon || 0}" min="0" title="Diskon item (Rp)" placeholder="Diskon" style="width:75px;text-align:right;color:#dc2626" onchange="setDiskonItem(${idx}, this.value)">
                    <span class="ci-sub">${rp(sub)}</span>
                </div>
            </div>`;
        }).join('');
    }
    document.getElementById('invCount').textContent = cart.reduce((s, i) => s + i.qty, 0) + ' item';
    renderSummary();
}

function removeItem(idx) { cart.splice(idx, 1); renderCart(); }
function changeQty(idx, d) {
    const it = cart[idx];
    let q = it.qty + d;
    if (q <= 0) { removeItem(idx); return; }
    if (q > it.stok) { q = it.stok; invToast('warning', 'Maksimal stok: ' + it.stok); }
    it.qty = q;
    reRender(true); // harga bisa naik tier saat qty bertambah
}
function setQty(idx, v) {
    const it = cart[idx];
    let q = parseInt(v) || 1;
    q = Math.min(Math.max(1, q), it.stok);
    it.qty = q;
    reRender(true);
}
function setHarga(idx, v) {
    const it = cart[idx];
    const val = Math.max(0, parseFloat(v) || 0);
    const auto = resolveHarga(it);
    it.manual = Math.abs(val - auto.harga) > 0.01;
    it.harga = val;
    reRender(false);
    if (it.manual) invToast('info', 'Harga manual — perubahan dicatat di riwayat harga');
}
function setDiskonItem(idx, v) { cart[idx].diskon = Math.max(0, parseFloat(v) || 0); renderCart(); }

// ================= RINGKASAN =================
function totals() {
    const subtotal = cart.reduce((s, i) => s + Math.max(0, i.qty * i.harga - (i.diskon || 0)), 0);
    const diskonItem = cart.reduce((s, i) => s + (i.diskon || 0), 0);
    const diskonTotal = Math.max(0, parseFloat(document.getElementById('invDiskonTotal').value) || 0);
    const total = Math.max(0, subtotal - diskonTotal);
    return { subtotal, diskonItem, diskonTotal, total, bruto: subtotal + diskonTotal + diskonItem };
}

function renderSummary() {
    const t = totals();
    document.getElementById('invSubtotal').textContent = rp(t.subtotal + t.diskonTotal);
    document.getElementById('invDiskonItem').textContent = '- ' + rp(t.diskonItem);
    document.getElementById('invTotal').textContent = rp(t.total);
    document.getElementById('invBtnText').textContent = cart.length ? 'Simpan Invoice · ' + rp(t.total) : 'Simpan Invoice';

    // Approval box otomatis muncul saat diskon > batas
    const persen = t.bruto > 0 ? ((t.diskonItem + t.diskonTotal) / t.bruto) * 100 : 0;
    document.getElementById('invApprovalBox').style.display = (t.diskonItem + t.diskonTotal) > 0 && persen > MAX_DISKON_PERSEN ? 'block' : 'none';

    // Sisa piutang utk DP/Tempo
    const bayarEl = document.getElementById('invBayar');
    const bayar = selectedMetode === 'DP' || selectedMetode === 'Tempo' ? Math.max(0, parseFloat(bayarEl?.value) || 0) : t.total;
    document.getElementById('invSisa').textContent = rp(Math.max(0, t.total - Math.min(bayar, t.total)));
}

// ================= METODE BAYAR =================
function selectMetode(m) {
    selectedMetode = m;
    document.querySelectorAll('.pm-opt').forEach(el => el.classList.toggle('active', el.dataset.metode === m));
    const show = m === 'DP' || m === 'Tempo';
    document.getElementById('invBayarBox').style.display = show ? 'block' : 'none';
    if (show) document.getElementById('invBayar').value = m === 'DP' ? Math.round(totals().total / 2) : 0;
    renderSummary();
}

// ================= SUBMIT =================
function submitInvoice() {
    if (!cart.length) { invToast('warning', 'Keranjang kosong'); return; }

    const t = totals();
    const sel = document.getElementById('invPelanggan');
    const opt = sel.options[sel.selectedIndex];

    let bayar = t.total;
    if (selectedMetode === 'DP' || selectedMetode === 'Tempo') {
        bayar = Math.max(0, parseFloat(document.getElementById('invBayar').value) || 0);
        if (bayar >= t.total && selectedMetode === 'DP') { invToast('warning', 'DP harus kurang dari total — gunakan metode Tunai jika lunas'); return; }
        if (!document.getElementById('invJatuhTempo').value) { invToast('warning', 'Jatuh tempo wajib diisi'); return; }
    }

    // Pre-check limit piutang (validasi final tetap di server)
    if (t.total - bayar > 0 && sel.value && opt.dataset.limit && Number(opt.dataset.limit) > 0) {
        const limit = Number(opt.dataset.limit), outstanding = Number(opt.dataset.outstanding || 0);
        if (outstanding + (t.total - bayar) > limit) {
            invToast('error', 'Limit piutang terlampaui: sisa limit ' + rp(limit - outstanding));
            return;
        }
    }

    const btn = document.getElementById('invBtnCheckout');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

    const payload = {
        items: cart.map(i => ({ stok_id: i.id, qty: i.qty, harga_satuan: i.harga, diskon: i.diskon || 0 })),
        pelanggan_grosir_id: sel.value || null,
        nama_pelanggan: document.getElementById('invNamaBaru').value || (sel.value ? opt.dataset.nama : null),
        no_wa: sel.value ? opt.dataset.wa : document.getElementById('invWaBaru').value || null,
        alamat: sel.value ? opt.dataset.alamat : document.getElementById('invAlamatBaru').value || null,
        sumber_cabang_id: document.getElementById('invSumber') ? document.getElementById('invSumber').value : null,
        metode_bayar: selectedMetode,
        bayar: bayar,
        metode_dp: document.getElementById('invMetodeDp') ? document.getElementById('invMetodeDp').value : 'Tunai',
        jatuh_tempo: (selectedMetode === 'DP' || selectedMetode === 'Tempo') ? document.getElementById('invJatuhTempo').value : null,
        diskon_total: t.diskonTotal,
        catatan: '',
    };

    // Sertakan approval bila box aktif
    const approvalBox = document.getElementById('invApprovalBox');
    if (approvalBox.style.display === 'block') {
        payload.approval_email = document.getElementById('invApprovalEmail').value;
        payload.approval_password = document.getElementById('invApprovalPassword').value;
    }

    fetch('{{ route("invoice.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
        body: JSON.stringify(payload),
    })
    .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
    .then(({ ok, data }) => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> <span id="invBtnText">Simpan Invoice</span>';
        if (data.success) {
            showSuccess(data.data);
            resetForm();
        } else {
            if (data.need_approval) document.getElementById('invApprovalBox').style.display = 'block';
            invToast('error', data.message || 'Gagal menyimpan invoice');
        }
    })
    .catch(e => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> <span id="invBtnText">Simpan Invoice</span>';
        invToast('error', 'Koneksi gagal: ' + e.message);
    });
}

function resetForm() {
    cart = [];
    khususMap = {};
    document.getElementById('invPelanggan').value = '';
    document.getElementById('invNamaBaru').value = '';
    document.getElementById('invWaBaru').value = '';
    document.getElementById('invAlamatBaru').value = '';
    document.getElementById('invDiskonTotal').value = 0;
    document.getElementById('invLimitInfo').innerHTML = '';
    document.getElementById('invApprovalEmail').value = '';
    document.getElementById('invApprovalPassword').value = '';
    pelangganTipe = '';
    selectMetode('Tunai');
    renderCart();
}

function showSuccess(d) {
    document.getElementById('invSuccessNo').textContent = d.no_invoice;
    document.getElementById('invSuccessStatus').textContent = d.status;
    document.getElementById('invSuccessTotal').textContent = rp(d.total);
    document.getElementById('invSuccessBayar').textContent = rp(d.bayar);
    document.getElementById('invSuccessSisa').textContent = rp(d.sisa);
    document.getElementById('btnInv58').href = '{{ url("invoice") }}/' + d.id + '/thermal/58';
    document.getElementById('btnInv80').href = '{{ url("invoice") }}/' + d.id + '/thermal/80';
    document.getElementById('btnInvPdf').href = '{{ url("invoice") }}/' + d.id + '/pdf';
    document.getElementById('invSuccessModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeInvSuccess() {
    document.getElementById('invSuccessModal').style.display = 'none';
    document.body.style.overflow = '';
}

renderCart();
</script>
@endsection
