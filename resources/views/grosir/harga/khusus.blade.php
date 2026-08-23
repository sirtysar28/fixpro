@extends('layouts.app')
@section('title', 'Harga Khusus Pelanggan')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">🏷️ Harga Khusus Pelanggan</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">Harga khusus menimpa level harga — dipakai otomatis saat pelanggan bertransaksi grosir</p>
    </div>
    <a href="{{ route('grosir.harga.index') }}" class="btn btn-secondary"><i class="fas fa-tags"></i> Tabel Harga Grosir</a>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

<form method="GET" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <div style="flex:1;min-width:200px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Pilih Pelanggan</label>
        <select name="pelanggan" class="form-input" onchange="this.form.submit()">
            <option value="">— pilih pelanggan —</option>
            @foreach($pelanggans as $p)
            <option value="{{ $p->id }}" {{ $selected && $selected->id === $p->id ? 'selected' : '' }}>{{ $p->nama }} ({{ $p->kode }}) · {{ $p->labelLevelHarga() }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary"><i class="fas fa-filter"></i> Tampilkan</button>
</form>

@if($selected)
<div class="card">
    <div class="card-header">
        <h3>Harga Khusus: {{ $selected->nama }} <span class="badge badge-normal">{{ $selected->kode }}</span></h3>
        <span class="badge badge-proses">Level: {{ $selected->labelLevelHarga() }}</span>
    </div>

    <form method="POST" action="{{ route('grosir.harga.khusus.store') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;">
        @csrf
        <input type="hidden" name="pelanggan_grosir_id" value="{{ $selected->id }}">
        <div style="flex:2;min-width:200px;">
            <label style="font-size:.75rem;font-weight:600;color:#374151;">Produk</label>
            <select name="stok_id" class="form-input" required>
                <option value="">— pilih produk —</option>
            </select>
        </div>
        <div style="flex:1;min-width:120px;">
            <label style="font-size:.75rem;font-weight:600;color:#374151;">Harga Khusus (Rp)</label>
            <input type="number" step="any" min="0" name="harga" class="form-input" required>
        </div>
        <button class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Produk</th><th>Kode</th><th style="text-align:right;">Harga Eceran</th><th style="text-align:right;">Harga Khusus</th><th></th></tr></thead>
            <tbody>
                @forelse($hargaKhusus as $hk)
                <tr>
                    <td style="font-weight:600;">{{ $hk->stok?->nama ?? $hk->stok_id }}</td>
                    <td style="font-family:monospace;">{{ $hk->stok?->kode ?? '-' }}</td>
                    <td style="text-align:right;">{{ formatRp($hk->stok?->jual ?? 0) }}</td>
                    <td style="text-align:right;font-weight:700;color:var(--primary);">{{ formatRp($hk->harga) }}</td>
                    <td>
                        <form method="POST" action="{{ route('grosir.harga.khusus.destroy', $hk) }}" onsubmit="return confirm('Hapus harga khusus ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:24px;">Belum ada harga khusus untuk pelanggan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<script>
    // Dropdown produk via API grosir (stok toko aktif saja)
    document.addEventListener('DOMContentLoaded', async () => {
        const sel = document.querySelector('select[name="stok_id"]');
        if (!sel) return;
        try {
            const res = await fetch('{{ route('grosir.penjualan.api.produk') }}?q=');
            const data = await res.json();
            (data.products || []).forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = `${p.nama} (${p.kode}) — eceran ${p.harga}`;
                sel.appendChild(opt);
            });
        } catch (e) { console.error(e); }
    });
</script>
@endsection
