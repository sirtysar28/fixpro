@extends('layouts.app')
@section('title', 'Generate Barcode')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<style>
    .bc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
    .bc-card {
        background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px;
        transition: transform .2s, box-shadow .2s; text-align: center;
    }
    .bc-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,.08); }
    .bc-card .bc-barcode-area {
        background: #fff; padding: 12px; border-radius: 8px; margin: 12px 0;
        border: 1px dashed #e2e8f0;
    }
    .bc-card .bc-barcode-area svg { max-width: 100%; }
    .bc-card .bc-product-name { font-size: .88rem; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
    .bc-card .bc-product-code { font-size: .72rem; color: #94a3b8; font-weight: 500; margin-bottom: 4px; }

    .bc-actions { display: flex; gap: 6px; justify-content: center; margin-top: 12px; }

    body.dark .bc-card { background: #1e293b; border-color: #334155; }
    body.dark .bc-card .bc-product-name { color: #e2e8f0; }
    body.dark .bc-card .bc-barcode-area { border-color: #334155; background: #0f172a; }

    @media print {
        .sidebar, .topbar, .no-print, .bc-actions { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .bc-card { break-inside: avoid; border: 1px solid #000 !important; }
        body.dark .bc-card { background: #fff !important; }
        body.dark .bc-card * { color: #000 !important; }
    }
</style>

<div class="flex-between mb-4 no-print">
    <h2 style="margin:0;display:flex;align-items:center;gap:8px">
        <i class="fas fa-barcode" style="color:var(--primary)"></i> Generate Barcode Produk
    </h2>
    <div style="display:flex;gap:8px">
        <a href="{{ route('barcode.print') }}" class="btn btn-success"><i class="fas fa-print"></i> Cetak Semua</a>
        <form method="POST" action="{{ route('barcode.generate-all') }}" style="display:inline" onsubmit="return confirm('Generate barcode untuk semua produk yang belum punya?')">
            @csrf @method('POST')
            <button class="btn btn-primary"><i class="fas fa-magic"></i> Generate Semua</button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success no-print"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif

{{-- Stats --}}
<div class="stats-grid mb-6 no-print">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-barcode"></i></div>
        <div class="stat-label">Total Produk</div>
        <div class="stat-value" style="color:var(--primary)">{{ $stoks->total() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-check"></i></div>
        <div class="stat-label">Sudah Ada Barcode</div>
        <div class="stat-value" style="color:#16a34a">{{ $stoks->total() - $noBarcodeCount }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-magic"></i></div>
        <div class="stat-label">Auto-Generated</div>
        <div class="stat-value" style="color:#d97706">{{ $noBarcodeCount }}</div>
        <div class="text-xs text-muted">Barcode dibuat otomatis</div>
    </div>
</div>

{{-- Filter --}}
<form method="GET" class="card mb-4 no-print">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
        <div style="flex:1"><label class="text-xs font-bold text-muted">Cari</label>
        <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="Cari produk..."></div>
        <div><label class="text-xs font-bold text-muted">Kategori</label>
        <select name="kategori" class="form-input" style="padding:8px 12px">
            <option value="">Semua</option>
            @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ request('kategori') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
        </select></div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
    </div>
</form>

{{-- Barcode Grid --}}
<div class="bc-grid">
    @foreach($stoks as $s)
    <div class="bc-card">
        <div class="bc-product-name">{{ $s->nama }}</div>
        <div class="bc-product-code">Kode: {{ $s->kode }}</div>

        {{-- Barcode --}}
        <div class="bc-barcode-area">
            <svg id="barcode-{{ $s->id }}"></svg>
        </div>

        <div class="bc-actions no-print">
            <button onclick="printSingleBarcode({{ $s->id }})" class="btn btn-sm btn-secondary"><i class="fas fa-print"></i></button>
        </div>
    </div>
    @endforeach
</div>

{{ $stoks->links() }}

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stoks = @json($stoks->items());
    stoks.forEach(s => {
        try {
            if (s.barcode) {
                JsBarcode("#barcode-" + s.id, s.barcode, {
                    format: "CODE128",
                    width: 1.5,
                    height: 50,
                    displayValue: true,
                    fontSize: 11,
                    margin: 4,
                    background: "#ffffff",
                    lineColor: "#000000"
                });
            }
        } catch(e) {
            console.warn('Barcode error for ' + s.id, e);
        }
    });
});

function printSingleBarcode(id) {
    const card = document.querySelector('#barcode-' + id)?.closest('.bc-card');
    if (!card) return;
    const sName = card.querySelector('.bc-product-name')?.textContent || '';
    const sCode = card.querySelector('.bc-product-code')?.textContent || '';
    const stoks = @json($stoks->items());
    const stokData = stoks.find(s => s.id === id);
    const bcVal = stokData?.barcode || '';
    const win = window.open('', '_blank');
    win.document.write(`
        <html><head><title>Barcode</title>
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>
        <style>
            body { display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .card { border: 1px solid #ccc; border-radius: 12px; padding: 24px; text-align: center; width: 300px; }
            .name { font-size: 14px; font-weight: 700; } .code { font-size: 10px; color: #666; }
        </style></head><body>
        <div class="card">
            <div class="name">${sName}</div>
            <div class="code">${sCode}</div>
            <svg id="print-bc"></svg>
        </div>
        <script>
            JsBarcode("#print-bc", "${bcVal}", {
                format: "CODE128", width: 1.5, height: 50, displayValue: true, fontSize: 11, margin: 4
            });
            setTimeout(() => window.print(), 500);
        <\/script></body></html>
    `);
    win.document.close();
}
</script>
@endsection
