@extends('layouts.app')
@section('title', 'Harga Grosir')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:1.5rem;margin:0;">💰 Harga Grosir</h1>
        <p style="color:#64748b;font-size:.85rem;margin:4px 0 0;">Harga penjualan berbeda per level: Eceran, Grosir 1-3, Reseller, Distributor — khusus toko aktif Anda</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('grosir.harga.khusus') }}" class="btn btn-secondary"><i class="fas fa-user-tag"></i> Harga Khusus Pelanggan</a>
        <button onclick="document.getElementById('massalModal').style.display='flex'" class="btn btn-primary"><i class="fas fa-magic"></i> Buat Harga Massal</button>
    </div>
</div>

@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

<form method="GET" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <div style="flex:1;min-width:180px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Cari produk</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / kode / barcode..." class="form-input">
    </div>
    <div style="min-width:140px;">
        <label style="font-size:.75rem;font-weight:600;color:#374151;">Status</label>
        <select name="status" class="form-input">
            <option value="">Semua</option>
            <option value="sudah" {{ request('status') === 'sudah' ? 'selected' : '' }}>Sudah ada harga grosir</option>
            <option value="belum" {{ request('status') === 'belum' ? 'selected' : '' }}>Belum ada</option>
        </select>
    </div>
    <button class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
</form>

<div class="card">
    <div class="card-header"><h3>Tabel Harga Penjualan ({{ $stoks->total() }} produk)</h3></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th style="text-align:right;">Eceran</th>
                    <th style="text-align:right;">Grosir 1</th>
                    <th style="text-align:right;">Grosir 2</th>
                    <th style="text-align:right;">Grosir 3</th>
                    <th style="text-align:right;">Reseller</th>
                    <th style="text-align:right;">Distributor</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($stoks as $s)
                @php $hg = $s->hargaGrosir; @endphp
                <tr>
                    <td>
                        <div style="font-weight:600;">{{ $s->nama }}</div>
                        <div style="font-size:.72rem;color:#94a3b8;font-family:monospace;">{{ $s->kode }} · Stok {{ $s->stok }}</div>
                    </td>
                    <td style="text-align:right;">{{ formatRp($s->jual) }}</td>
                    <td style="text-align:right;">{{ $hg?->harga_grosir1 ? formatRp($hg->harga_grosir1) : '—' }}</td>
                    <td style="text-align:right;">{{ $hg?->harga_grosir2 ? formatRp($hg->harga_grosir2) : '—' }}</td>
                    <td style="text-align:right;">{{ $hg?->harga_grosir3 ? formatRp($hg->harga_grosir3) : '—' }}</td>
                    <td style="text-align:right;">{{ $hg?->harga_reseller ? formatRp($hg->harga_reseller) : '—' }}</td>
                    <td style="text-align:right;">{{ $hg?->harga_distributor ? formatRp($hg->harga_distributor) : '—' }}</td>
                    <td>
                        <button onclick="bukaFormHarga({{ $s->id }})" class="btn btn-sm {{ $hg ? 'btn-secondary' : 'btn-primary' }}">
                            <i class="fas {{ $hg ? 'fa-edit' : 'fa-plus' }}"></i> {{ $hg ? 'Edit' : 'Atur' }}
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:28px;">Belum ada produk. Tambahkan stok barang dulu.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $stoks->links() }}
</div>

{{-- Form harga per produk --}}
<form method="POST" id="formHarga" style="display:none;" action="{{ route('grosir.harga.store') }}">
    @csrf
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;display:flex;align-items:center;justify-content:center;" onclick="if(event.target===this)document.getElementById('formHarga').style.display='none'">
        <div style="background:#fff;border-radius:16px;padding:24px;width:92%;max-width:560px;max-height:90vh;overflow-y:auto;">
            <h3 style="margin:0 0 4px;" id="hgNama">Atur Harga Grosir</h3>
            <p style="color:#64748b;font-size:.78rem;margin:0 0 16px;" id="hgSub"></p>
            <input type="hidden" name="stok_id" id="hgStokId">

            <div class="form-row">
                <div class="form-group"><label>Grosir 1 (Rp)</label><input type="number" step="any" min="0" name="harga_grosir1" id="hgG1" class="form-input"></div>
                <div class="form-group"><label>Min. Qty Grosir 1</label><input type="number" min="1" name="min_qty_grosir1" id="hgMin1" class="form-input" value="3"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Grosir 2 (Rp)</label><input type="number" step="any" min="0" name="harga_grosir2" id="hgG2" class="form-input"></div>
                <div class="form-group"><label>Min. Qty Grosir 2</label><input type="number" min="1" name="min_qty_grosir2" id="hgMin2" class="form-input" value="6"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Grosir 3 (Rp)</label><input type="number" step="any" min="0" name="harga_grosir3" id="hgG3" class="form-input"></div>
                <div class="form-group"><label>Min. Qty Grosir 3</label><input type="number" min="1" name="min_qty_grosir3" id="hgMin3" class="form-input" value="12"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Reseller (Rp)</label><input type="number" step="any" min="0" name="harga_reseller" id="hgRes" class="form-input"></div>
                <div class="form-group"><label>Distributor (Rp)</label><input type="number" step="any" min="0" name="harga_distributor" id="hgDis" class="form-input"></div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="aktif" value="1" id="hgAktif" checked> Aktifkan harga grosir produk ini
                </label>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('formHarga').style.display='none'">Batal</button>
                <button class="btn btn-primary"><i class="fas fa-save"></i> Simpan Harga</button>
            </div>
        </div>
    </div>
</form>

{{-- Data harga untuk prefill form --}}
<script>
    window.__hargaData = @json($stoks->getCollection()->mapWithKeys(fn($s) => [$s->id => [
        'nama' => $s->nama,
        'kode' => $s->kode,
        'eceran' => (float) $s->jual,
        'hg' => $s->hargaGrosir ? [
            'g1' => $s->hargaGrosir->harga_grosir1,
            'g2' => $s->hargaGrosir->harga_grosir2,
            'g3' => $s->hargaGrosir->harga_grosir3,
            'res' => $s->hargaGrosir->harga_reseller,
            'dis' => $s->hargaGrosir->harga_distributor,
            'min1' => $s->hargaGrosir->min_qty_grosir1,
            'min2' => $s->hargaGrosir->min_qty_grosir2,
            'min3' => $s->hargaGrosir->min_qty_grosir3,
            'aktif' => $s->hargaGrosir->aktif,
        ] : null,
    ]])->all());

    function bukaFormHarga(id) {
        const d = window.__hargaData[id];
        if (!d) return;
        document.getElementById('hgNama').textContent = d.nama;
        document.getElementById('hgSub').textContent = d.kode + ' · Harga eceran: ' + formatRp(d.eceran);
        document.getElementById('hgStokId').value = id;
        const hg = d.hg || {};
        document.getElementById('hgG1').value = hg.g1 ?? '';
        document.getElementById('hgG2').value = hg.g2 ?? '';
        document.getElementById('hgG3').value = hg.g3 ?? '';
        document.getElementById('hgRes').value = hg.res ?? '';
        document.getElementById('hgDis').value = hg.dis ?? '';
        document.getElementById('hgMin1').value = hg.min1 ?? 3;
        document.getElementById('hgMin2').value = hg.min2 ?? 6;
        document.getElementById('hgMin3').value = hg.min3 ?? 12;
        document.getElementById('hgAktif').checked = hg.aktif ?? true;
        document.getElementById('formHarga').style.display = 'block';
    }
</script>

{{-- Modal harga massal --}}
<form method="POST" id="massalModal" style="display:none;" action="{{ route('grosir.harga.massal') }}">
    @csrf
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;display:flex;align-items:center;justify-content:center;" onclick="if(event.target===this)document.getElementById('massalModal').style.display='none'">
        <div style="background:#fff;border-radius:16px;padding:24px;width:92%;max-width:420px;">
            <h3 style="margin:0 0 6px;">Buat Harga Massal</h3>
            <p style="color:#64748b;font-size:.78rem;margin:0 0 16px;">Turunkan harga eceran dengan persentase tertentu untuk semua produk di toko ini (harga yang sudah terisi akan ditimpa).</p>
            <div class="form-group">
                <label>Level Harga</label>
                <select name="level" class="form-input">
                    <option value="grosir1">Grosir 1</option>
                    <option value="grosir2">Grosir 2</option>
                    <option value="grosir3">Grosir 3</option>
                    <option value="reseller">Reseller</option>
                    <option value="distributor">Distributor</option>
                </select>
            </div>
            <div class="form-group">
                <label>Diskon dari harga eceran (%)</label>
                <input type="number" name="persen" min="0" max="90" step="any" value="10" class="form-input" required>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('massalModal').style.display='none'">Batal</button>
                <button class="btn btn-primary"><i class="fas fa-magic"></i> Terapkan</button>
            </div>
        </div>
    </div>
</form>
@endsection
