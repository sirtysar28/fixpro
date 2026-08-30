@extends('layouts.app')
@section('title', 'Invoice ' . $invoice->no_invoice)

@section('content')
<div class="flex-between mb-4" style="flex-wrap:wrap;gap:10px">
    <div>
        <a href="{{ route('invoice.riwayat') }}" class="btn btn-secondary btn-sm" style="margin-bottom:8px"><i class="fas fa-arrow-left"></i> Kembali ke Riwayat</a>
        <h2 style="margin:0">Invoice <span style="color:var(--primary)">{{ $invoice->no_invoice }}</span></h2>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('invoice.pdf', $invoice) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-file-pdf"></i> PDF A4</a>
        <a href="{{ route('invoice.thermal', ['invoice' => $invoice, 'size' => 58]) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print"></i> 58mm</a>
        <a href="{{ route('invoice.thermal', ['invoice' => $invoice, 'size' => 80]) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print"></i> 80mm</a>
        @if($waPhone)
        <button onclick="kirimWaInvoice()" class="btn btn-success btn-sm" style="background:#16a34a;color:#fff"><i class="fab fa-whatsapp"></i> Kirim WA</button>
        <a href="https://wa.me/{{ $waPhone }}?text={{ rawurlencode($waMessage) }}" target="_blank" class="btn btn-secondary btn-sm" title="Buka WhatsApp manual"><i class="fab fa-whatsapp"></i> WA Manual</a>
        @endif
        @if(!$invoice->isVoid())
            @if($invoice->hasPiutang())
            <button onclick="openBayarModal()" class="btn btn-primary btn-sm"><i class="fas fa-money-bill-wave"></i> Bayar / Pelunasan</button>
            @endif
            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
            <button onclick="openDiskonModal()" class="btn btn-warning btn-sm" style="background:#d97706;color:#fff"><i class="fas fa-tags"></i> Ubah Diskon</button>
            <button onclick="openReturModal()" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i> Retur</button>
            <button onclick="openVoidModal()" class="btn btn-danger btn-sm"><i class="fas fa-ban"></i> Void Invoice</button>
            @endif
        @endif
    </div>
</div>

@if($invoice->isVoid())
<div class="card mb-4" style="background:#fef2f2;border:1px solid #fecaca">
    <div style="color:#991b1b;font-size:.86rem">
        <b><i class="fas fa-ban"></i> Invoice dibatalkan (VOID)</b> pada {{ $invoice->void_pada?->format('d/m/Y H:i') }} oleh {{ \App\Models\User::find($invoice->void_oleh)?->name ?? '-' }}.
        Alasan: {{ $invoice->alasan_void }}
    </div>
</div>
@endif

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px" class="mb-4">
    {{-- ===== DETAIL ===== --}}
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-box-open"></i> Detail Sparepart</h3></div>
        <div style="padding:0 16px 8px;font-size:.8rem;color:#64748b">
            Cabang: <b>{{ $invoice->cabang?->nama ?? '-' }}</b> ·
            Gudang/Stok: <b>{{ $invoice->sumberCabang?->nama ?? '-' }}</b> ·
            Kasir: <b>{{ $invoice->kasir?->name ?? '-' }}</b> ·
            Tipe: <b>{{ $invoice->tipe_pelanggan }}</b>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Kode</th><th>Nama</th><th>Tipe HP</th><th>Tipe LCD</th><th>Qty</th><th>Harga</th><th>Jenis</th><th>Diskon</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $it)
                    <tr>
                        <td>{{ $it->kode }}</td>
                        <td>{{ $it->nama }}</td>
                        <td>{{ $it->merk_hp ?? '-' }}</td>
                        <td>{{ $it->tipe_lcd ?? '-' }}</td>
                        <td>{{ $it->qty }}</td>
                        <td>{{ formatRp($it->harga_satuan) }}</td>
                        <td><span class="badge {{ $it->jenis_harga === 'khusus' ? 'badge-proses' : ($it->jenis_harga === 'manual' ? 'badge-pending' : 'badge-masuk') }}">{{ $it->labelJenisHarga() }}</span></td>
                        <td style="color:#dc2626">{{ (float) $it->diskon > 0 ? '-' . formatRp($it->diskon) : '-' }}</td>
                        <td><strong>{{ formatRp($it->subtotal) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:14px 16px;border-top:1px solid #f1f5f9;display:flex;flex-direction:column;gap:4px;font-size:.86rem;max-width:340px;margin-left:auto">
            <div style="display:flex;justify-content:space-between"><span style="color:#64748b">Subtotal</span><span>{{ formatRp($invoice->subtotal + $invoice->diskon_total) }}</span></div>
            @if((float) $invoice->diskon_total > 0)
            <div style="display:flex;justify-content:space-between"><span style="color:#64748b">Diskon Nota</span><span style="color:#dc2626">- {{ formatRp($invoice->diskon_total) }}</span></div>
            @endif
            @if((float) $invoice->total_retur > 0)
            <div style="display:flex;justify-content:space-between"><span style="color:#64748b">Retur</span><span style="color:#dc2626">- {{ formatRp($invoice->total_retur) }}</span></div>
            @endif
            <div style="display:flex;justify-content:space-between;font-size:1.05rem;font-weight:800;color:var(--primary);border-top:2px dashed #e2e8f0;padding-top:8px;margin-top:4px">
                <span>TOTAL</span><span>{{ formatRp($invoice->total) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between"><span style="color:#64748b">Dibayar</span><span>{{ formatRp($invoice->dibayar) }}</span></div>
            <div style="display:flex;justify-content:space-between"><span style="color:#64748b">Sisa</span><span style="color:{{ (float) $invoice->sisa > 0 ? '#dc2626' : 'var(--success)' }};font-weight:700">{{ formatRp($invoice->sisa) }}</span></div>
        </div>
    </div>

    {{-- ===== INFO PELANGGAN & STATUS ===== --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-user"></i> Pelanggan</h3></div>
            <div style="padding:12px 16px;font-size:.84rem;line-height:1.9">
                <b>{{ $invoice->nama_pelanggan ?? 'Umum' }}</b><br>
                <span class="badge badge-masuk">{{ $invoice->tipe_pelanggan }}</span><br>
                @if($invoice->no_wa)<span style="color:#64748b"><i class="fab fa-whatsapp"></i> {{ $invoice->no_wa }}</span><br>@endif
                @if($invoice->alamat)<span style="color:#64748b"><i class="fas fa-map-marker-alt"></i> {{ $invoice->alamat }}</span><br>@endif
                @if($invoice->pelanggan && (float) $invoice->pelanggan->limit_piutang > 0)
                <span style="color:#64748b">Limit piutang: <b>{{ formatRp($invoice->pelanggan->limit_piutang) }}</b></span>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3><i class="fas fa-info-circle"></i> Status</h3></div>
            <div style="padding:12px 16px;font-size:.84rem;line-height:2">
                Status: <span class="badge {{ $invoice->badgeStatus() }}">{{ $invoice->status }}</span><br>
                Metode: <b>{{ $invoice->metode_bayar }}</b><br>
                Tanggal: <b>{{ $invoice->tanggal?->format('d/m/Y H:i') }}</b><br>
                Jatuh Tempo: <b style="color:{{ $invoice->isJatuhTempo() ? '#dc2626' : 'inherit' }}">{{ $invoice->jatuh_tempo?->format('d/m/Y') ?? '-' }}</b><br>
                @if($invoice->approvalDiskonOleh)
                Diskon di-approve oleh: <b>{{ $invoice->approvalDiskonOleh?->name ?? '-' }}</b><br>
                @endif
                @if($invoice->catatan)<span style="color:#64748b">Catatan: {{ $invoice->catatan }}</span>@endif
            </div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    {{-- ===== RIWAYAT PEMBAYARAN ===== --}}
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-money-bill-wave"></i> Riwayat Pembayaran</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tanggal</th><th>Jumlah</th><th>Metode</th><th>Oleh</th><th>Catatan</th></tr></thead>
                <tbody>
                    @forelse($invoice->payments as $pay)
                    <tr>
                        <td>{{ $pay->tanggal?->format('d/m/Y H:i') }}</td>
                        <td><strong style="color:var(--success)">{{ formatRp($pay->jumlah) }}</strong></td>
                        <td><span class="badge badge-proses">{{ $pay->metode }}</span></td>
                        <td>{{ $pay->user?->name ?? '-' }}</td>
                        <td>{{ $pay->catatan ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:16px">Belum ada pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== LOG / RIWAYAT PERUBAHAN ===== --}}
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-history"></i> Riwayat Perubahan Invoice</h3></div>
        <div style="padding:8px 16px 16px">
            @forelse($invoice->logs as $log)
            <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid #f8fafc">
                <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;background:var(--primary-bg);color:var(--primary)">
                    @if($log->aksi === 'create')<i class="fas fa-plus"></i>
                    @elseif($log->aksi === 'diskon')<i class="fas fa-tags"></i>
                    @elseif($log->aksi === 'harga')<i class="fas fa-dollar-sign"></i>
                    @elseif($log->aksi === 'void')<i class="fas fa-ban"></i>
                    @elseif($log->aksi === 'bayar')<i class="fas fa-money-bill-wave"></i>
                    @elseif($log->aksi === 'retur')<i class="fas fa-undo"></i>
                    @else<i class="fas fa-edit"></i>@endif
                </div>
                <div>
                    <div style="font-size:.8rem;color:#1e293b">{{ $log->deskripsi }}</div>
                    <div style="font-size:.68rem;color:#94a3b8">{{ $log->user?->name ?? 'Sistem' }} · {{ $log->created_at?->format('d/m/Y H:i') }}</div>
                </div>
            </div>
            @empty
            <div style="color:#94a3b8;font-size:.82rem;text-align:center;padding:16px">Belum ada riwayat.</div>
            @endforelse
        </div>
    </div>
</div>

@if($invoice->returs->count())
<div class="card mt-4">
    <div class="card-header"><h3><i class="fas fa-undo"></i> Retur</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Retur</th><th>Tanggal</th><th>Item</th><th>Total</th><th>Alasan</th><th>Oleh</th></tr></thead>
            <tbody>
                @foreach($invoice->returs as $r)
                <tr>
                    <td><strong>{{ $r->no_retur }}</strong></td>
                    <td>{{ $r->tanggal?->format('d/m/Y H:i') }}</td>
                    <td>@foreach($r->items as $ri)<div style="font-size:.76rem">{{ $ri->nama }} × {{ $ri->qty }}</div>@endforeach</td>
                    <td style="color:#dc2626;font-weight:700">{{ formatRp($r->total) }}</td>
                    <td>{{ $r->alasan }}</td>
                    <td>{{ $r->user?->name ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ===== MODAL BAYAR ===== --}}
<div id="bayarModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:24px;max-width:420px;width:92%">
        <h3 style="font-size:1rem;margin-bottom:4px"><i class="fas fa-money-bill-wave" style="color:var(--primary)"></i> Pembayaran / Pelunasan</h3>
        <p style="font-size:.78rem;color:#64748b;margin-bottom:14px">Sisa tagihan: <b style="color:#dc2626">{{ formatRp($invoice->sisa) }}</b></p>
        <label class="text-xs font-bold text-muted">Jumlah Bayar</label>
        <input type="number" id="bayarJumlah" class="form-input" value="{{ (float) $invoice->sisa }}" min="1" max="{{ (float) $invoice->sisa }}">
        <div style="display:flex;gap:8px;margin-top:8px">
            <div style="flex:1"><label class="text-xs font-bold text-muted">Metode</label>
            <select id="bayarMetode" class="form-input"><option>Tunai</option><option>Transfer</option><option>QRIS</option></select></div>
        </div>
        <label class="text-xs font-bold text-muted" style="margin-top:8px;display:block">Catatan</label>
        <input type="text" id="bayarCatatan" class="form-input" placeholder="Pelunasan / sebagian...">
        <div style="display:flex;gap:8px;margin-top:16px">
            <button onclick="closeModal('bayarModal')" class="btn btn-secondary" style="flex:1">Batal</button>
            <button onclick="submitBayar()" class="btn btn-primary" style="flex:1"><i class="fas fa-check"></i> Bayar</button>
        </div>
    </div>
</div>

{{-- ===== MODAL DISKON ===== --}}
<div id="diskonModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:24px;max-width:420px;width:92%">
        <h3 style="font-size:1rem;margin-bottom:4px"><i class="fas fa-tags" style="color:#d97706"></i> Ubah Diskon Nota</h3>
        <p style="font-size:.78rem;color:#64748b;margin-bottom:14px">Diskon saat ini: <b>{{ formatRp($invoice->diskon_total) }}</b> · Subtotal: <b>{{ formatRp($invoice->subtotal + $invoice->diskon_total) }}</b></p>
        <label class="text-xs font-bold text-muted">Diskon Baru (Rp)</label>
        <input type="number" id="diskonBaru" class="form-input" value="{{ (float) $invoice->diskon_total }}" min="0">
        <div id="diskonApprovalWrap" style="margin-top:10px;display:none;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px">
            <div style="font-size:.72rem;color:#92400e;font-weight:700"><i class="fas fa-user-shield"></i> Approval Admin (diskon besar)</div>
            <input type="email" id="diskonApprovalEmail" class="form-input" placeholder="Email Admin" style="margin-top:6px">
            <input type="password" id="diskonApprovalPassword" class="form-input" placeholder="Password Admin">
        </div>
        <div style="display:flex;gap:8px;margin-top:16px">
            <button onclick="closeModal('diskonModal')" class="btn btn-secondary" style="flex:1">Batal</button>
            <button onclick="submitDiskon()" class="btn btn-warning" style="flex:1;background:#d97706;color:#fff"><i class="fas fa-check"></i> Simpan</button>
        </div>
    </div>
</div>

{{-- ===== MODAL RETUR ===== --}}
<div id="returModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:24px;max-width:520px;width:92%;max-height:85vh;overflow-y:auto">
        <h3 style="font-size:1rem;margin-bottom:14px"><i class="fas fa-undo" style="color:var(--primary)"></i> Retur Barang</h3>
        <div id="returItems">
            @foreach($invoice->items as $it)
            <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f8fafc;font-size:.82rem">
                <span style="flex:1">{{ $it->nama }} <span style="color:#94a3b8">(beli {{ $it->qty }})</span></span>
                <input type="number" class="form-input retur-qty" data-item="{{ $it->id }}" min="0" max="{{ $it->qty }}" value="0" style="width:70px;text-align:center">
            </div>
            @endforeach
        </div>
        <label class="text-xs font-bold text-muted" style="margin-top:12px;display:block">Alasan Retur *</label>
        <textarea id="returAlasan" class="form-input" rows="2" placeholder="Barang rusak / salah pesan..."></textarea>
        <div style="display:flex;gap:8px;margin-top:14px">
            <button onclick="closeModal('returModal')" class="btn btn-secondary" style="flex:1">Batal</button>
            <button onclick="submitRetur()" class="btn btn-primary" style="flex:1"><i class="fas fa-undo"></i> Proses Retur</button>
        </div>
    </div>
</div>

{{-- ===== MODAL VOID ===== --}}
<div id="voidModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:24px;max-width:440px;width:92%">
        <h3 style="font-size:1rem;margin-bottom:4px;color:#dc2626"><i class="fas fa-ban"></i> Void Invoice</h3>
        <p style="font-size:.78rem;color:#64748b;margin-bottom:12px">Semua stok akan dikembalikan & semua pembayaran dibalik dari kas. Tindakan ini tidak dapat dibatalkan.</p>
        <label class="text-xs font-bold text-muted">Alasan Void *</label>
        <textarea id="voidAlasan" class="form-input" rows="3" placeholder="Salah input / pembatalan pelanggan..."></textarea>
        <div style="display:flex;gap:8px;margin-top:14px">
            <button onclick="closeModal('voidModal')" class="btn btn-secondary" style="flex:1">Batal</button>
            <button onclick="submitVoid()" class="btn btn-danger" style="flex:1"><i class="fas fa-ban"></i> Void Sekarang</button>
        </div>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function openBayarModal() { openModal('bayarModal'); }
function openVoidModal() { openModal('voidModal'); }
function openDiskonModal() { openModal('diskonModal'); }
function openReturModal() { openModal('returModal'); }

function postJson(url, body) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(body),
    }).then(r => r.json());
}

function submitBayar() {
    const jumlah = parseFloat(document.getElementById('bayarJumlah').value);
    const metode = document.getElementById('bayarMetode').value;
    const catatan = document.getElementById('bayarCatatan').value;
    if (!jumlah || jumlah <= 0) { alert('Jumlah bayar tidak valid'); return; }
    postJson('{{ route("invoice.bayar", $invoice) }}', { jumlah, metode, catatan })
        .then(d => { alert(d.message); if (d.success) location.reload(); });
}

function submitDiskon() {
    const diskon = parseFloat(document.getElementById('diskonBaru').value) || 0;
    const body = { diskon_total: diskon };
    const wrap = document.getElementById('diskonApprovalWrap');
    if (wrap.style.display === 'block') {
        body.approval_email = document.getElementById('diskonApprovalEmail').value;
        body.approval_password = document.getElementById('diskonApprovalPassword').value;
    }
    postJson('{{ route("invoice.diskon", $invoice) }}', body)
        .then(d => {
            if (d.success) { alert(d.message); location.reload(); }
            else {
                alert(d.message);
                if (d.need_approval) document.getElementById('diskonApprovalWrap').style.display = 'block';
            }
        });
}
// Tampilkan approval box otomatis jika diskon > 5% dari subtotal
document.getElementById('diskonBaru').addEventListener('input', function () {
    const subtotal = {{ (float) $invoice->subtotal + (float) $invoice->diskon_total }};
    const v = parseFloat(this.value) || 0;
    document.getElementById('diskonApprovalWrap').style.display = (subtotal > 0 && (v / subtotal) * 100 > {{ (float) ($maxDiskonPersen ?? 5) }}) ? 'block' : 'none';
});

function submitRetur() {
    const items = [];
    document.querySelectorAll('.retur-qty').forEach(el => {
        const q = parseInt(el.value) || 0;
        if (q > 0) items.push({ item_id: el.dataset.item, qty: q });
    });
    const alasan = document.getElementById('returAlasan').value.trim();
    if (!items.length) { alert('Pilih minimal 1 item untuk diretur'); return; }
    if (alasan.length < 3) { alert('Alasan retur wajib diisi'); return; }
    postJson('{{ route("invoice.retur.store", $invoice) }}', { items, alasan })
        .then(d => { alert(d.message); if (d.success) location.reload(); });
}

function submitVoid() {
    const alasan = document.getElementById('voidAlasan').value.trim();
    if (alasan.length < 3) { alert('Alasan void wajib diisi'); return; }
    if (!confirm('Yakin void invoice ini? Stok & kas akan dikembalikan.')) return;
    postJson('{{ route("invoice.void", $invoice) }}', { alasan })
        .then(d => { alert(d.message); if (d.success) location.reload(); });
}

function kirimWaInvoice() {
    if (!confirm('Kirim invoice {{ $invoice->no_invoice }} + PDF ke WhatsApp pelanggan?')) return;
    postJson('{{ route("invoice.wa", $invoice) }}', {})
        .then(d => {
            alert(d.message);
            if (!d.success && d.manual_mode) {
                window.open('https://wa.me/{{ $waPhone }}?text={{ rawurlencode($waMessage) }}', '_blank');
            }
        });
}
</script>
@endsection
