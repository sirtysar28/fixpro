@extends('layouts.app')
@section('title', 'Detail Pembelian')

@section('content')
<a href="{{ route('pembelian.index') }}" class="btn btn-secondary btn-sm mb-3"><i class="fas fa-arrow-left"></i> Kembali</a>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px" class="grid-responsive">
    <div class="card mb-4">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:16px">
            <div>
                <h2 style="margin:0;font-size:1.3rem"><strong style="color:var(--primary)">{{ $pembelian->kode }}</strong></h2>
                <div style="font-size:.82rem;color:#64748b;margin-top:2px">
                    {{ $pembelian->tanggal?->format('d/m/Y') }} • dibuat oleh {{ $pembelian->user?->name ?? '-' }}
                    @if($pembelian->diedit_pada)
                    • <span title="{{ $pembelian->diedit_pada->format('d/m/Y H:i') }}"><i class="fas fa-user-edit"></i> diedit oleh {{ $pembelian->editor?->name ?? '-' }} ({{ $pembelian->diedit_pada->diffForHumans() }})</span>
                    @endif
                </div>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                @php $sb = $pembelian->statusBadge(); $st = $pembelian->statusTransaksiBadge(); @endphp
                <span style="background:{{ $sb['bg'] }};color:{{ $sb['color'] }};padding:4px 12px;border-radius:12px;font-size:.78rem;font-weight:700">{{ $sb['label'] }}</span>
                <span style="background:{{ $st['bg'] }};color:{{ $st['color'] }};padding:4px 12px;border-radius:12px;font-size:.78rem;font-weight:700">{{ $pembelian->status_transaksi }}</span>
                <a href="{{ route('pembelian.nota', $pembelian) }}" target="_blank" class="btn btn-xs" style="background:#e0e7ff;color:#4338ca"><i class="fas fa-print"></i> Cetak Nota</a>
            </div>
        </div>

        {{-- Kontrol transaksi --}}
        @if(!$pembelian->isDibatalkan())
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;padding:10px;background:#f8fafc;border-radius:10px">
            @if($pembelian->isDraft())
            <form method="POST" action="{{ route('pembelian.proses', $pembelian) }}" style="display:flex;gap:6px;align-items:center" onsubmit="return confirm('Proses pembelian ini? Stok akan bertambah otomatis.')">
                @csrf
                <input type="number" name="dibayar" min="0" step="1000" value="0" placeholder="Dibayar" style="width:110px;padding:5px 8px;font-size:.78rem;border:1px solid #e2e8f0;border-radius:6px">
                <button type="submit" class="btn btn-sm" style="background:#2563eb;color:#fff"><i class="fas fa-play"></i> Proses (Stok Masuk)</button>
            </form>
            <a href="{{ route('pembelian.edit', $pembelian) }}" class="btn btn-sm" style="background:#fef3c7;color:#b45309"><i class="fas fa-edit"></i> Edit Item</a>
            @else
            <a href="{{ route('pembelian.edit', $pembelian) }}" class="btn btn-sm" style="background:#fef3c7;color:#b45309"><i class="fas fa-edit"></i> Edit Data Header</a>
            @if($pembelian->status_transaksi === 'Diproses' && $pembelian->sisaHutang() <= 0)
            <form method="POST" action="{{ route('pembelian.selesaikan', $pembelian) }}">
                @csrf
                <button type="submit" class="btn btn-sm" style="background:#16a34a;color:#fff"><i class="fas fa-check-double"></i> Tandai Selesai</button>
            </form>
            @endif
            @endif
        </div>
        @endif

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;font-size:.84rem">
            <div>
                <div style="color:#94a3b8;font-size:.72rem;font-weight:600;text-transform:uppercase;margin-bottom:2px">Supplier</div>
                <div style="font-weight:600">{{ $pembelian->supplier_nama }}</div>
                @if($pembelian->supplier_kontak)<div style="color:#64748b"><i class="fas fa-phone" style="font-size:.7rem"></i> {{ $pembelian->supplier_kontak }}</div>@endif
                @if($pembelian->supplier_alamat)<div style="color:#64748b;font-size:.78rem">{{ $pembelian->supplier_alamat }}</div>@endif
            </div>
            <div>
                @if($pembelian->tanggal_jatuh_tempo)
                <div style="color:#94a3b8;font-size:.72rem;font-weight:600;text-transform:uppercase;margin-bottom:2px">Jatuh Tempo</div>
                <div style="font-weight:600;color:{{ $pembelian->sisaHutang() > 0 && $pembelian->tanggal_jatuh_tempo->isPast() ? '#dc2626' : '#0f172a' }}">{{ $pembelian->tanggal_jatuh_tempo->format('d/m/Y') }}
                    @if($pembelian->sisaHutang() > 0 && $pembelian->tanggal_jatuh_tempo->isPast())<span style="font-size:.7rem">(TERLAMBAT!)</span>@endif
                </div>
                @endif
                <div style="color:#94a3b8;font-size:.72rem;font-weight:600;text-transform:uppercase;margin-top:6px;margin-bottom:2px">Metode</div>
                <div>{{ $pembelian->metode_bayar }}</div>
            </div>
        </div>

        @if($pembelian->catatan)
        <div style="background:#f8fafc;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:.8rem;color:#475569;border-left:3px solid var(--primary);white-space:pre-wrap">
            <i class="fas fa-sticky-note"></i> {{ $pembelian->catatan }}
        </div>
        @endif

        <div class="table-wrap">
            <table>
                <thead><tr><th>Barang</th><th>Kode</th><th>Qty</th><th>Harga Beli</th><th>Diskon Item</th><th>Harga Jual</th><th>Subtotal</th></tr></thead>
                <tbody>
                    @foreach(($pembelian->items ?? []) as $it)
                    @php $sudahRetur = !empty($it['stok_id']) ? $pembelian->qtyRetur($it['stok_id']) : 0; @endphp
                    <tr>
                        <td>
                            {{ $it['nama'] }}
                            @if($sudahRetur > 0)<span style="background:#ffedd5;color:#c2410c;font-size:.66rem;font-weight:700;padding:1px 6px;border-radius:8px;margin-left:4px">retur {{ $sudahRetur }}</span>@endif
                        </td>
                        <td style="font-size:.78rem;color:#64748b">{{ $it['kode'] ?? '-' }}</td>
                        <td style="text-align:center">{{ $it['qty'] }}</td>
                        <td>{{ formatRp($it['harga_beli']) }}</td>
                        <td style="color:#dc2626">{{ (float)($it['diskon_item'] ?? 0) > 0 ? '- ' . formatRp($it['diskon_item']) : '-' }}</td>
                        <td>{{ formatRp($it['harga_jual'] ?? 0) }}</td>
                        <td style="font-weight:600">{{ formatRp($it['subtotal']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:16px">
            <div style="width:300px;font-size:.86rem">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span>Subtotal</span><strong>{{ formatRp($pembelian->subtotal) }}</strong></div>
                @if((float)$pembelian->subtotal - (float)$pembelian->total + (float)$pembelian->biaya_tambahan + (float)$pembelian->ongkir != 0)
                <div style="display:flex;justify-content:space-between;color:#dc2626;margin-bottom:6px"><span>Diskon ({{ $pembelian->diskon_persen }}% + {{ formatRp($pembelian->diskon_nominal) }})</span><strong>- {{ formatRp((float)$pembelian->subtotal * (float)$pembelian->diskon_persen / 100 + (float)$pembelian->diskon_nominal) }}</strong></div>
                @endif
                @if((float)$pembelian->biaya_tambahan > 0)
                <div style="display:flex;justify-content:space-between;color:#2563eb;margin-bottom:6px"><span>Biaya Tambahan</span><strong>+ {{ formatRp($pembelian->biaya_tambahan) }}</strong></div>
                @endif
                @if((float)$pembelian->ongkir > 0)
                <div style="display:flex;justify-content:space-between;color:#2563eb;margin-bottom:6px"><span>Ongkir</span><strong>+ {{ formatRp($pembelian->ongkir) }}</strong></div>
                @endif
                <div style="display:flex;justify-content:space-between;padding-top:8px;border-top:2px solid #e2e8f0;margin-bottom:6px;font-size:1rem"><strong>Total Pembelian</strong><strong style="color:var(--primary)">{{ formatRp($pembelian->total) }}</strong></div>
                @if((float)$pembelian->total_retur > 0)
                <div style="display:flex;justify-content:space-between;color:#c2410c;margin-bottom:6px"><span>Retur</span><strong>- {{ formatRp($pembelian->total_retur) }}</strong></div>
                <div style="display:flex;justify-content:space-between;padding-top:6px;border-top:1px dashed #e2e8f0;margin-bottom:6px"><strong>Total Akhir</strong><strong>{{ formatRp($pembelian->totalAkhir()) }}</strong></div>
                @endif
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;color:#16a34a"><span>Dibayar</span><strong>{{ formatRp($pembelian->dibayar) }}</strong></div>
                <div style="display:flex;justify-content:space-between;color:#dc2626;font-weight:700"><span>Sisa Hutang</span><strong>{{ formatRp($pembelian->sisaHutang()) }}</strong></div>
            </div>
        </div>
    </div>

    <div>
        {{-- ===== BAYAR HUTANG ===== --}}
        @if(!$pembelian->isDibatalkan() && !$pembelian->isDraft() && $pembelian->sisaHutang() > 0)
        <div class="card mb-4" id="bayar">
            <h3 style="font-size:.92rem;margin-bottom:14px"><i class="fas fa-hand-holding-usd" style="color:#dc2626"></i> Bayar Hutang</h3>
            <form method="POST" action="{{ route('pembelian.bayar-hutang', $pembelian) }}">
                @csrf
                <div class="form-group">
                    <label>Jumlah Bayar (maks {{ formatRp($pembelian->sisaHutang()) }})</label>
                    <input type="number" name="jumlah" class="form-input" min="1" max="{{ $pembelian->sisaHutang() }}" step="1000" value="{{ $pembelian->sisaHutang() }}" required>
                    <div style="display:flex;gap:6px;margin-top:6px">
                        <button type="button" class="btn btn-xs" style="background:#dcfce7;color:#16a34a" onclick="this.form.jumlah.value={{ $pembelian->sisaHutang() }}">Lunas Semua</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="metode" class="form-input">
                        @foreach(['Cash','Transfer','QRIS'] as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Pembayaran</label>
                    <input type="date" name="tanggal_bayar" class="form-input" value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <input type="text" name="catatan" class="form-input" placeholder="opsional">
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Bayar</button>
            </form>
        </div>
        @endif

        {{-- ===== RETUR ===== --}}
        @if(!$pembelian->isDibatalkan() && !$pembelian->isDraft() && count($returable) > 0)
        <div class="card mb-4" id="retur">
            <h3 style="font-size:.92rem;margin-bottom:14px"><i class="fas fa-undo" style="color:#d97706"></i> Retur Barang</h3>
            <div style="font-size:.74rem;color:#64748b;margin-bottom:10px">Kembalikan barang ke supplier. Stok berkurang, nilai pembelian berkurang, hutang disesuaikan.</div>
            <form method="POST" action="{{ route('pembelian.retur', $pembelian) }}">
                @csrf
                <div class="form-group">
                    <label>Pilih Barang</label>
                    <select name="stok_id" class="form-input" required id="returStok">
                        <option value="">-- Pilih --</option>
                        @foreach($returable as $r)
                        <option value="{{ $r['stok_id'] }}" data-harga="{{ $r['harga_beli'] }}">{{ $r['nama'] }} — beli {{ formatRp($r['harga_beli']) }} (sisa bisa retur: {{ $r['sisa_qty'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Qty Retur</label>
                    <input type="number" name="qty" class="form-input" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label>Harga Retur / pcs (default harga beli)</label>
                    <input type="number" name="harga_retur" class="form-input" min="0" step="100" id="returHarga" placeholder="otomatis dari harga beli">
                </div>
                <div class="form-group">
                    <label>Alasan Retur</label>
                    <input type="text" name="alasan" class="form-input" placeholder="Rusak / salah item / dll">
                </div>
                <button type="submit" class="btn btn-warning btn-sm" style="background:#d97706;color:#fff"><i class="fas fa-undo"></i> Retur</button>
            </form>
        </div>
        @endif

        {{-- ===== RIWAYAT PEMBAYARAN ===== --}}
        <div class="card mb-4">
            <h3 style="font-size:.92rem;margin-bottom:12px"><i class="fas fa-history" style="color:#16a34a"></i> Riwayat Pembayaran</h3>
            @if($pembelian->payments->count() > 0)
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Tgl</th><th>Jumlah</th><th>Metode</th><th>Oleh</th><th>Ref</th></tr></thead>
                    <tbody>
                        @foreach($pembelian->payments as $pay)
                        <tr>
                            <td style="font-size:.76rem">{{ $pay->tanggal?->format('d/m/y') }}</td>
                            <td style="font-weight:600;color:#16a34a">{{ formatRp($pay->jumlah) }}</td>
                            <td style="font-size:.76rem">{{ $pay->metode }}</td>
                            <td style="font-size:.76rem">{{ $pay->user?->name ?? '-' }}</td>
                            <td style="font-size:.68rem;color:#94a3b8">{{ $pay->ref_kode }}</td>
                        </tr>
                        @endforeach
                        <tr style="background:#f0fdf4"><td colspan="2" style="font-weight:700">Total Dibayar</td><td colspan="3" style="font-weight:700;color:#16a34a">{{ formatRp($pembelian->payments->sum('jumlah')) }}</td></tr>
                    </tbody>
                </table>
            </div>
            @else
            <div style="font-size:.78rem;color:#94a3b8">Belum ada pembayaran.</div>
            @endif
        </div>

        {{-- ===== RIWAYAT RETUR ===== --}}
        <div class="card mb-4">
            <h3 style="font-size:.92rem;margin-bottom:12px"><i class="fas fa-undo-alt" style="color:#c2410c"></i> Riwayat Retur</h3>
            @if($pembelian->returns->count() > 0)
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Tgl</th><th>Barang</th><th>Qty</th><th>Nilai</th><th>Alasan</th></tr></thead>
                    <tbody>
                        @foreach($pembelian->returns as $ret)
                        <tr>
                            <td style="font-size:.76rem">{{ $ret->tanggal?->format('d/m/y') }}</td>
                            <td style="font-size:.78rem">{{ $ret->nama_barang }}<br><span style="font-size:.66rem;color:#94a3b8">{{ $ret->kode }}</span></td>
                            <td style="text-align:center">{{ $ret->qty }}</td>
                            <td style="font-weight:600;color:#c2410c">{{ formatRp($ret->nilai) }}</td>
                            <td style="font-size:.72rem;color:#64748b">{{ $ret->alasan ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="font-size:.78rem;color:#94a3b8">Belum ada retur.</div>
            @endif
        </div>

        {{-- ===== BATALKAN ===== --}}
        @if(!$pembelian->isDibatalkan())
        <div class="card mb-4" style="border-color:#fecaca">
            <h3 style="font-size:.92rem;margin-bottom:14px;color:#dc2626"><i class="fas fa-ban"></i> Batalkan Pembelian</h3>
            <div style="font-size:.74rem;color:#64748b;margin-bottom:10px">
                Seluruh stok dikembalikan & semua pembayaran dikembalikan ke kas. Transaksi dengan pembayaran TIDAK bisa dihapus permanen tanpa pembatalan yang benar.
                @if($pembelian->payments->count() > 0)<br><strong style="color:#b45309">⚠ Transaksi ini sudah memiliki {{ $pembelian->payments->count() }} pembayaran.</strong>@endif
            </div>
            <details>
                <summary style="cursor:pointer;font-size:.8rem;color:#dc2626;font-weight:600">Buka form pembatalan</summary>
                <form method="POST" action="{{ route('pembelian.batal', $pembelian) }}" style="margin-top:10px">
                    @csrf
                    <div class="form-group">
                        <label>Alasan *</label>
                        <input type="text" name="alasan" class="form-input" required minlength="3">
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm" style="background:#dc2626;color:#fff" onclick="return confirm('Yakin batalkan? Stok & seluruh pembayaran akan dikembalikan.')"><i class="fas fa-ban"></i> Batalkan</button>
                </form>
            </details>
        </div>
        @elseif($pembelian->payments()->doesntExist())
        <div class="card mb-4" style="border-color:#fecaca">
            <h3 style="font-size:.92rem;margin-bottom:10px;color:#dc2626"><i class="fas fa-trash"></i> Hapus Permanen</h3>
            <form method="POST" action="{{ route('pembelian.destroy', $pembelian) }}" onsubmit="return confirm('Hapus permanen {{ $pembelian->kode }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" style="background:#dc2626;color:#fff"><i class="fas fa-trash"></i> Hapus</button>
            </form>
        </div>
        @endif
    </div>
</div>

<script>
// Isi otomatis harga retur dari harga beli item terpilih
document.getElementById('returStok')?.addEventListener('change', function () {
    const opt = this.selectedOptions[0];
    const harga = document.getElementById('returHarga');
    if (opt && opt.value && harga && !harga.value) {
        harga.value = opt.dataset.harga || '';
        harga.placeholder = 'Rp ' + Number(opt.dataset.harga || 0).toLocaleString('id-ID');
    }
});
</script>

<style>
@media (max-width: 768px) { .grid-responsive { grid-template-columns: 1fr !important; } }
</style>
@endsection
