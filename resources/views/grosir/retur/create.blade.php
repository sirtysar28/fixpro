@extends('layouts.app')
@section('title', 'Retur Grosir Baru')

@section('content')
<div class="page-header" style="margin-bottom:16px;">
    <h1 style="font-size:1.5rem;margin:0;">↩️ Retur Grosir Baru</h1>
    <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;"><a href="{{ route('grosir.retur.index') }}">← Daftar retur</a> · Pilih nota grosir lalu isi qty barang yang diretur</p>
</div>

@if($errors->any())
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i>
    <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
</div>
@endif

<form method="GET" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <div style="flex:1;min-width:250px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">1. Pilih Nota Grosir</label>
        <select name="nota" class="form-input" onchange="this.form.submit()">
            <option value="">— pilih nota penjualan —</option>
            @foreach($notas as $n)
            <option value="{{ $n->id }}" {{ $selected && $selected->id === $n->id ? 'selected' : '' }}>
                {{ $n->no_nota }} · {{ $n->nama_pelanggan }} · {{ formatRp($n->total) }} ({{ $n->tanggal->format('d/m/Y') }})
            </option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary"><i class="fas fa-arrow-right"></i> Tampilkan</button>
</form>

@if($selected)
<form method="POST" action="{{ route('grosir.retur.store') }}">
    @csrf
    <input type="hidden" name="penjualan_grosir_id" value="{{ $selected->id }}">

    <div class="card">
        <div class="card-header">
            <h3>2. Barang yang Diretur — Nota {{ $selected->no_nota }}</h3>
            <span class="badge badge-proses">{{ $selected->nama_pelanggan }} · {{ $selected->labelLevelHarga() }}</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:60px;text-align:center;">Retur?</th>
                        <th>Produk</th>
                        <th style="text-align:center;">Qty Beli</th>
                        <th style="text-align:center;">Sudah Diretur</th>
                        <th style="width:110px;text-align:center;">Qty Retur</th>
                        <th style="text-align:right;">Harga Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sudahRetur = [];
                        foreach($selected->returs as $rr) {
                            foreach($rr->items as $ri) {
                                $k = $ri->stok_id . '|' . $ri->nama;
                                $sudahRetur[$k] = ($sudahRetur[$k] ?? 0) + $ri->qty;
                            }
                        }
                    @endphp
                    @foreach($items as $i => $item)
                    @php $k = $item->stok_id . '|' . $item->nama; $sudah = $sudahRetur[$k] ?? 0; $maks = $item->qty - $sudah; @endphp
                    <tr {{ $maks <= 0 ? 'style="opacity:.45;"' : '' }}>
                        <td style="text-align:center;">
                            <input type="checkbox" class="cek-retur" {{ $maks <= 0 ? 'disabled' : '' }} onchange="toggleRetur({{ $i }}, this.checked)">
                            <input type="hidden" name="items[{{ $i }}][item_id]" value="{{ $item->id }}" class="hid-item-{{ $i }}" {{ $maks <= 0 ? 'disabled' : 'disabled' }}>
                        </td>
                        <td>
                            <b>{{ $item->nama }}</b>
                            <div style="font-size:.7rem;color:#94a3b8;font-family:monospace;">{{ $item->kode }}</div>
                        </td>
                        <td style="text-align:center;">{{ $item->qty }}</td>
                        <td style="text-align:center;">{{ $sudah }}</td>
                        <td style="text-align:center;">
                            <input type="number" name="items[{{ $i }}][qty]" min="1" max="{{ $maks }}" value="{{ max(1, min(1, $maks)) }}" class="form-input qty-retur-{{ $i }}" style="width:80px;text-align:center;" disabled {{ $maks <= 0 ? 'disabled' : '' }}>
                        </td>
                        <td style="text-align:right;">{{ formatRp($item->harga_satuan) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>3. Detail Retur</h3><span id="estTotal" style="font-weight:700;color:var(--primary);"></span></div>
        <div class="form-row">
            <div class="form-group">
                <label>Metode Retur</label>
                <select name="metode" class="form-input">
                    <option value="Uang Kembali">Uang Kembali (kas keluar)</option>
                    <option value="Tukar Barang">Tukar Barang (stok masuk saja)</option>
                    <option value="Potong Piutang">Potong Piutang (jika nota masih ada piutang)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Alasan Retur *</label>
                <input type="text" name="alasan" class="form-input" required minlength="3" placeholder="Contoh: LCD cacat pabrik">
            </div>
        </div>
        <button class="btn btn-primary"><i class="fas fa-save"></i> Simpan Retur</button>
    </div>
</form>

<script>
    // Harga per item untuk estimasi total retur
    const hargaItem = @json($items->mapWithKeys(fn($it) => [$it->id => (float) $it->harga_satuan])->all());

    function toggleRetur(i, on) {
        const qty = document.querySelector(`.qty-retur-${i}`);
        const hid = document.querySelector(`.hid-item-${i}`);
        qty.disabled = !on;
        hid.disabled = !on;
        hitungEstimasi();
    }

    function hitungEstimasi() {
        let total = 0;
        document.querySelectorAll('tbody tr').forEach((row, idx) => {
            const cb = row.querySelector('.cek-retur');
            if (!cb || !cb.checked) return;
            const itemId = parseInt(row.querySelector(`[class*="hid-item-"]`).value);
            const qty = parseInt(row.querySelector(`[class*="qty-retur-"]`).value) || 0;
            total += (hargaItem[itemId] || 0) * qty;
        });
        document.getElementById('estTotal').textContent = total > 0 ? 'Estimasi: ' + formatRp(total) : '';
    }

    document.addEventListener('input', e => {
        if (e.target.className && String(e.target.className).includes('qty-retur-')) hitungEstimasi();
    });
</script>
@else
<div class="card"><p style="text-align:center;color:#94a3b8;padding:20px;">Pilih nota grosir di atas untuk mulai membuat retur.</p></div>
@endif
@endsection
