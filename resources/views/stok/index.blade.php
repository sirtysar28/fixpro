@extends('layouts.app')
@section('title', 'Stok Barang')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<style>
    .bc-popup-overlay {
        display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;
        align-items:center;justify-content:center;
    }
    .bc-popup-overlay.show { display:flex; }
    .bc-popup {
        background:#fff;border-radius:16px;padding:28px 32px;text-align:center;
        max-width:380px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);
        animation:popIn .25s ease;position:relative;
    }
    @keyframes popIn { from{transform:scale(.9);opacity:0} to{transform:scale(1);opacity:1} }
    .bc-popup .bp-close {
        position:absolute;top:12px;right:16px;background:none;border:none;
        font-size:1.2rem;cursor:pointer;color:#94a3b8;transition:color .2s;
    }
    .bc-popup .bp-close:hover { color:#dc2626; }
    .bc-popup .bp-name { font-size:1.1rem;font-weight:800;color:#0f172a;margin-bottom:2px; }
    .bc-popup .bp-code { font-size:.78rem;color:#94a3b8;font-weight:500;margin-bottom:16px; }
    .bc-popup .bp-barcode-box {
        background:#fff;padding:16px;border-radius:10px;margin:12px auto 20px;
        border:2px dashed #e2e8f0;width:fit-content;
    }
    .bc-popup .bp-barcode-box svg { max-width:100%; }
    .bc-popup .bp-price { font-size:1.5rem;font-weight:800;color:var(--primary);margin-bottom:6px; }
    .bc-popup .bp-info { font-size:.78rem;color:#64748b;line-height:1.8; }
    .bc-popup .bp-row { display:flex;justify-content:space-between;gap:20px;padding:4px 0; }
    .bc-popup .bp-label { color:#94a3b8; }
    .bc-popup .bp-value { font-weight:700;color:#0f172a; }
    .bc-popup .bp-actions { display:flex;gap:8px;justify-content:center;margin-top:16px; }

    .bc-trigger {
        display:inline-flex;align-items:center;gap:4px;
        font-family:monospace;font-size:.7rem;background:var(--primary-bg);
        color:var(--primary);padding:3px 10px;border-radius:6px;
        border:1px solid rgba(13,148,136,.2);cursor:pointer;
        font-weight:600;transition:all .2s;
    }
    .bc-trigger:hover { background:var(--primary);color:#fff;border-color:var(--primary); }
    .bc-trigger i { font-size:.72rem; }

    body.dark .bc-popup { background:#1e293b; border:1px solid #334155; }
    body.dark .bc-popup .bp-name { color:#e2e8f0; }
    body.dark .bc-popup .bp-code { color:#64748b; }
    body.dark .bc-popup .bp-barcode-box { background:#0f172a; border-color:#334155; }
    body.dark .bc-popup .bp-info { color:#94a3b8; }
    body.dark .bc-popup .bp-label { color:#64748b; }
    body.dark .bc-popup .bp-value { color:#e2e8f0; }
    body.dark .bc-trigger { background:rgba(13,148,136,.15);border-color:rgba(13,148,136,.3);color:#2dd4bf; }
    body.dark .bc-trigger:hover { background:var(--primary);color:#fff; }
</style>

{{-- Barcode Popup --}}
<div class="bc-popup-overlay" id="bcOverlay" onclick="if(event.target===this)closeBarcodePopup()">
    <div class="bc-popup">
        <button class="bp-close" onclick="closeBarcodePopup()"><i class="fas fa-times"></i></button>
        <div class="bp-name" id="bpName">-</div>
        <div class="bp-code" id="bpCode">-</div>
        <div class="bp-barcode-box">
            <svg id="bpBarcode"></svg>
        </div>
        <div class="bp-price" id="bpPrice">-</div>
        <div class="bp-info">
            <div class="bp-row"><span class="bp-label">Kategori</span><span class="bp-value" id="bpKategori">-</span></div>
            <div class="bp-row"><span class="bp-label">Merk HP</span><span class="bp-value" id="bpMerk">-</span></div>
            <div class="bp-row"><span class="bp-label">Stok</span><span class="bp-value" id="bpStok">-</span></div>
            <div class="bp-row"><span class="bp-label">Barcode</span><span class="bp-value" id="bpBarcodeText">-</span></div>
        </div>
        <div class="bp-actions">
            <button onclick="copyBarcodeText()" class="btn btn-sm btn-primary"><i class="fas fa-copy"></i> Copy Barcode</button>
            <button onclick="printSingleBarcodeFromPopup()" class="btn btn-sm btn-secondary"><i class="fas fa-print"></i> Cetak</button>
        </div>
    </div>
</div>

<div class="flex-between mb-4" id="import-stok">
    <h2 style="margin:0">Daftar Stok Sparepart</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('stok.template-excel') }}" class="btn btn-secondary btn-sm"><i class="fas fa-download"></i> Template</a>
        <a href="{{ route('stok.export-excel') }}" class="btn btn-success btn-sm" style="background:#16a34a;color:#fff"><i class="fas fa-file-excel"></i> Export Excel</a>
        <form method="POST" action="{{ route('stok.import-excel') }}" enctype="multipart/form-data" style="display:inline">
            @csrf
            <label class="btn btn-warning btn-sm" style="background:#d97706;color:#fff;cursor:pointer;margin:0">
                <i class="fas fa-file-import"></i> Import Excel
                <input type="file" name="file" accept=".xls,.xlsx,.csv" required onchange="this.form.submit()" style="display:none">
            </label>
        </form>
        <a href="{{ route('stok.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Barang</a>
    </div>
</div>

<div class="stats-grid mb-6">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-boxes"></i></div>
        <div class="stat-label">Total Jenis</div>
        <div class="stat-value" style="color:var(--primary)">{{ $totalJenis }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:var(--warning)"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-label">Stok Menipis</div>
        <div class="stat-value" style="color:var(--warning)">{{ $stokLow }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-times-circle"></i></div>
        <div class="stat-label">Stok Habis</div>
        <div class="stat-value" style="color:var(--danger)">{{ $stokHabis }}</div>
    </div>
</div>

<form method="GET" class="card mb-4">
    {{-- Pertahankan sorting saat filter berubah --}}
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">
    <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:160px"><label class="text-xs font-bold text-muted">Cari</label>
        <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="Nama / kode / barcode / merk..."></div>
        <div style="min-width:130px"><label class="text-xs font-bold text-muted">Kategori</label>
        <select name="kategori" class="form-input">
            <option value="">Semua</option>
            @foreach($kategoriList as $k)
            <option value="{{ $k }}" {{ request('kategori') == $k ? 'selected' : '' }}>{{ $k }}</option>
            @endforeach
        </select></div>
        <div style="min-width:120px"><label class="text-xs font-bold text-muted">Merk HP</label>
        <select name="merk" class="form-input">
            <option value="">Semua</option>
            @foreach($merkList as $m)
            <option value="{{ $m }}" {{ request('merk') == $m ? 'selected' : '' }}>{{ $m }}</option>
            @endforeach
        </select></div>
        @if($allowedCabangs->count() > 1)
        <div style="min-width:150px"><label class="text-xs font-bold text-muted">Cabang / Gudang</label>
        <select name="cabang" class="form-input">
            @foreach($allowedCabangs as $c)
            <option value="{{ $c->id }}" {{ (int) $filterCabang === (int) $c->id ? 'selected' : '' }}>{{ $c->nama }} ({{ $c->tipe ?? 'toko' }})</option>
            @endforeach
        </select></div>
        @endif
        <div style="min-width:110px"><label class="text-xs font-bold text-muted">Per Halaman</label>
        <select name="per_page" class="form-input">
            @foreach([10, 20, 50, 100] as $pp)
            <option value="{{ $pp }}" {{ $perPage === $pp ? 'selected' : '' }}>{{ $pp }} data</option>
            @endforeach
        </select></div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        <a href="{{ route('stok.index') }}" class="btn btn-secondary btn-sm" title="Reset filter"><i class="fas fa-redo"></i></a>
    </div>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th><a href="{{ route('stok.index', array_merge(\Illuminate\Support\Arr::except(request()->query(), ['page']), ['sort' => 'kode', 'dir' => $sort === 'kode' && $dir === 'asc' ? 'desc' : 'asc'])) }}" style="text-decoration:none;color:inherit">Kode @if($sort === 'kode')<i class="fas fa-caret-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>@endif</a></th>
                <th><a href="{{ route('stok.index', array_merge(\Illuminate\Support\Arr::except(request()->query(), ['page']), ['sort' => 'nama', 'dir' => $sort === 'nama' && $dir === 'asc' ? 'desc' : 'asc'])) }}" style="text-decoration:none;color:inherit">Nama @if($sort === 'nama')<i class="fas fa-caret-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>@endif</a></th>
                <th><a href="{{ route('stok.index', array_merge(\Illuminate\Support\Arr::except(request()->query(), ['page']), ['sort' => 'kategori', 'dir' => $sort === 'kategori' && $dir === 'asc' ? 'desc' : 'asc'])) }}" style="text-decoration:none;color:inherit">Kategori @if($sort === 'kategori')<i class="fas fa-caret-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>@endif</a></th>
                <th><a href="{{ route('stok.index', array_merge(\Illuminate\Support\Arr::except(request()->query(), ['page']), ['sort' => 'merk_hp', 'dir' => $sort === 'merk_hp' && $dir === 'asc' ? 'desc' : 'asc'])) }}" style="text-decoration:none;color:inherit">Merk HP @if($sort === 'merk_hp')<i class="fas fa-caret-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>@endif</a></th>
                <th><a href="{{ route('stok.index', array_merge(\Illuminate\Support\Arr::except(request()->query(), ['page']), ['sort' => 'stok', 'dir' => $sort === 'stok' && $dir === 'asc' ? 'desc' : 'asc'])) }}" style="text-decoration:none;color:inherit">Stok @if($sort === 'stok')<i class="fas fa-caret-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>@endif</a></th>
                <th><a href="{{ route('stok.index', array_merge(\Illuminate\Support\Arr::except(request()->query(), ['page']), ['sort' => 'modal', 'dir' => $sort === 'modal' && $dir === 'asc' ? 'desc' : 'asc'])) }}" style="text-decoration:none;color:inherit">Modal @if($sort === 'modal')<i class="fas fa-caret-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>@endif</a></th>
                <th><a href="{{ route('stok.index', array_merge(\Illuminate\Support\Arr::except(request()->query(), ['page']), ['sort' => 'jual', 'dir' => $sort === 'jual' && $dir === 'asc' ? 'desc' : 'asc'])) }}" style="text-decoration:none;color:inherit">Jual @if($sort === 'jual')<i class="fas fa-caret-{{ $dir === 'asc' ? 'up' : 'down' }}"></i>@endif</a></th>
                <th>Min Alert</th><th>Barcode</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach($stoks as $s)
                <tr>
                    <td><strong>{{ $s->kode }}</strong></td>
                    <td>{{ $s->nama }}</td>
                    <td><span class="badge badge-masuk">{{ $s->kategori }}</span></td>
                    <td>{{ $s->merk_hp ?? '-' }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px">
                            <button onclick="quickStok({{ $s->id }}, -1)" class="btn btn-xs" style="background:#fee2e2;color:#dc2626;padding:2px 8px;min-width:28px" title="Kurangi 1"><i class="fas fa-minus"></i></button>
                            @if($s->stok == 0) <span class="badge badge-pending">Habis</span>
                            @elseif($s->stok <= $s->min_alert) <span class="badge badge-proses">{{ $s->stok }}</span>
                            @else <span class="badge badge-selesai">{{ $s->stok }}</span>
                            @endif
                            <button onclick="quickStok({{ $s->id }}, 1)" class="btn btn-xs" style="background:#dcfce7;color:#16a34a;padding:2px 8px;min-width:28px" title="Tambah 1"><i class="fas fa-plus"></i></button>
                        </div>
                    </td>
                    <td>{{ formatRp($s->modal) }}</td>
                    <td>{{ formatRp($s->jual) }}</td>
                    <td>{{ $s->min_alert }}</td>
                    <td>
                        @if($s->barcode)
                        <span class="bc-trigger" onclick="showBarcodePopup({{ $s->id }}, '{{ $s->barcode }}', '{{ addslashes($s->nama) }}', '{{ $s->kode }}', {{ (int) $s->jual }}, {{ $s->stok }}, '{{ $s->satuan ?? 'pcs' }}', '{{ $s->kategori }}', '{{ $s->merk_hp ?? '' }}')">
                            <i class="fas fa-barcode"></i> {{ $s->barcode }}
                        </span>
                        @else
                        <span style="font-size:.68rem;color:#94a3b8">-</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('aktivitas-sparepart.show', $s) }}" class="btn btn-xs" style="background:#fef3c7;color:#b45309" title="Kartu Stok / Aktivitas"><i class="fas fa-clipboard-list"></i></a>
                        <a href="{{ route('stok.edit', $s) }}" class="btn btn-primary btn-xs"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('stok.destroy', $s) }}" style="display:inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $stoks->links() }}
</div>

<script>
let currentBarcode = '';

function showBarcodePopup(id, barcode, nama, kode, jual, stok, satuan, kategori, merk) {
    currentBarcode = barcode;
    document.getElementById('bpName').textContent = nama;
    document.getElementById('bpCode').textContent = 'Kode: ' + kode;
    document.getElementById('bpPrice').textContent = 'Rp ' + parseInt(jual).toLocaleString('id-ID');
    document.getElementById('bpKategori').textContent = kategori;
    document.getElementById('bpMerk').textContent = merk || '-';
    document.getElementById('bpStok').textContent = stok + ' ' + satuan;
    document.getElementById('bpBarcodeText').textContent = barcode;

    // Render barcode
    try {
        JsBarcode('#bpBarcode', barcode, {
            format: 'CODE128', width: 2, height: 60,
            displayValue: true, fontSize: 13, margin: 6,
            background: '#ffffff', lineColor: '#000000'
        });
    } catch(e) {
        document.getElementById('bpBarcode').parentElement.innerHTML = '<span style="color:#dc2626;font-size:.8rem">Gagal render barcode</span>';
    }

    document.getElementById('bcOverlay').classList.add('show');
}

function closeBarcodePopup() {
    document.getElementById('bcOverlay').classList.remove('show');
}

function copyBarcodeText() {
    navigator.clipboard.writeText(currentBarcode).then(() => {
        const btn = document.querySelector('.bp-actions .btn-primary');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Tersalin!';
        btn.style.background = '#16a34a';
        setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; }, 1500);
    });
}

function printSingleBarcodeFromPopup() {
    const nama = document.getElementById('bpName').textContent;
    const kode = document.getElementById('bpCode').textContent;
    const harga = document.getElementById('bpPrice').textContent;
    const kategori = document.getElementById('bpKategori').textContent;
    const merk = document.getElementById('bpMerk').textContent;
    const stok = document.getElementById('bpStok').textContent;
    const bcText = currentBarcode;

    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html><html><head><title>Barcode</title>
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>
        <style>
            body{display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;font-family:Inter,sans-serif}
            .card{border:1px solid #ccc;border-radius:12px;padding:24px;text-align:center;width:320px}
            .name{font-size:16px;font-weight:800} .code{font-size:11px;color:#666}
            .price{font-size:22px;font-weight:800;margin:10px 0} .info{font-size:11px;color:#888;line-height:1.6}
        </style></head><body>
        <div class="card">
            <div class="name">${nama}</div>
            <div class="code">${kode}</div>
            <svg id="print-bc"></svg>
            <div class="price">${harga}</div>
            <div class="info">${kategori} ${merk !== '-' ? '• ' + merk : ''} • Stok: ${stok}</div>
        </div>
        <script>
            JsBarcode('#print-bc','${bcText}',{format:'CODE128',width:2,height:60,displayValue:true,fontSize:13,margin:6});
            setTimeout(()=>window.print(),400);
        <\/script></body></html>`);
    win.document.close();
}

// Close on ESC
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeBarcodePopup(); });

function quickStok(id, delta) {
    fetch('{{ route('stok.quick-update') }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
        body: JSON.stringify({id: id, delta: delta})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Gagal update stok');
        }
    })
    .catch(err => alert('Error: ' + err.message));
}
</script>
@endsection
