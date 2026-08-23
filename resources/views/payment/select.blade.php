@extends('layouts.app')
@section('title', 'Pembayaran Online')

@section('content')
<h2 class="mb-4" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <i class="fas fa-credit-card" style="color:var(--primary)"></i> Pembayaran Online
    <span style="font-size:.7rem;color:#94a3b8;font-weight:400">— Virtual Account, QRIS, E-Wallet, Transfer Bank</span>
</h2>

@if(!$enabled)
<div class="card mb-4" style="background:#fef3c7;border:1px solid #fcd34d;color:#78350f;padding:14px 18px">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Payment Gateway belum dikonfigurasi.</strong>
    Mode <strong>manual</strong> aktif — pelanggan akan mendapat instruksi manual, admin yang verifikasi.
    Super Admin bisa mengaktifkan gateway otomatis (Tripay/Midtrans) di halaman Pengaturan.
</div>
@endif

@if(session('error'))
<div class="card mb-4" style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:12px 16px">{{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('payment.create') }}">
    @csrf
    <div class="card mb-4">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-file-invoice"></i> Detail Pembayaran</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
            <div class="form-group">
                <label>Tagihan Untuk (opsional)</label>
                <div style="display:flex;gap:6px">
                    <select name="payable_type" class="form-input" style="flex:1" onchange="updatePayable(this.value)">
                        <option value="">— Bayar bebas —</option>
                        <option value="servis" {{ request('type')==='servis' ? 'selected' : '' }}>Servis</option>
                        <option value="tagihan" {{ request('type')==='tagihan' ? 'selected' : '' }}>Tagihan Sparepart</option>
                        <option value="penjualan" {{ request('type')==='penjualan' ? 'selected' : '' }}>Penjualan Sparepart</option>
                    </select>
                    <input type="number" name="payable_id" class="form-input" style="width:100px" placeholder="ID" value="{{ request('id') }}" oninput="fetchAmount()">
                </div>
            </div>
            <div class="form-group">
                <label>Nominal (Rp) *</label>
                <input type="number" name="amount" id="amountInput" class="form-input" min="1" step="1000" required value="{{ old('amount', $amount > 0 ? $amount : '') }}" oninput="updateFeePreview()">
                <input type="hidden" name="reference" value="{{ old('reference', $reference) }}">
            </div>
            <div class="form-group">
                <label>Nama Pelanggan</label>
                <input type="text" name="customer_name" class="form-input" value="{{ old('customer_name', $customer['name'] ?? '') }}">
            </div>
            <div class="form-group">
                <label>No. HP</label>
                <input type="text" name="customer_phone" class="form-input" value="{{ old('customer_phone', $customer['phone'] ?? '') }}">
            </div>
            <div class="form-group">
                <label>Email (opsional)</label>
                <input type="email" name="customer_email" class="form-input" value="{{ old('customer_email', $customer['email'] ?? '') }}">
            </div>
            <div class="form-group">
                <label>Catatan</label>
                <input type="text" name="catatan" class="form-input" value="{{ old('catatan') }}">
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <h3 style="font-size:.95rem;margin-bottom:14px"><i class="fas fa-wallet"></i> Pilih Metode Pembayaran</h3>

        @php
            $icons = [
                'va'      => ['icon'=>'fa-university', 'label'=>'Virtual Account', 'color'=>'#2563eb', 'bg'=>'#dbeafe'],
                'qris'    => ['icon'=>'fa-qrcode',     'label'=>'QRIS',            'color'=>'#7c3aed', 'bg'=>'#ede9fe'],
                'ewallet' => ['icon'=>'fa-wallet',     'label'=>'E-Wallet',        'color'=>'#db2777', 'bg'=>'#fce7f3'],
                'bank'    => ['icon'=>'fa-money-bill-wave', 'label'=>'Transfer Bank', 'color'=>'#059669', 'bg'=>'#d1fae5'],
                'retail'  => ['icon'=>'fa-store',      'label'=>'Gerai Retail',    'color'=>'#d97706', 'bg'=>'#fef3c7'],
            ];
        @endphp

        @foreach($methods as $group => $codes)
        @if(!isset($icons[$group])) @continue @endif
        <div style="margin-bottom:18px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-size:.82rem;font-weight:700;color:#475569">
                <span style="background:{{ $icons[$group]['bg'] }};color:{{ $icons[$group]['color'] }};width:24px;height:24px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center"><i class="fas {{ $icons[$group]['icon'] }}"></i></span>
                {{ $icons[$group]['label'] }}
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px">
                @foreach($codes as $code)
                <label style="border:2px solid #e2e8f0;border-radius:10px;padding:10px 12px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:.78rem;transition:all .15s" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='#e2e8f0'">
                    <input type="radio" name="method_code" value="{{ $code }}" required style="accent-color:var(--primary)" {{ old('method_code') === $code ? 'checked' : '' }} onchange="updateFeePreview()">
                    <span>{{ \App\Models\Payment::methodLabel($code) }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @endforeach

        <div id="feePreview" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px;font-size:.82rem;color:#166534;margin-top:12px"></div>

        <div style="margin-top:16px;display:flex;gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Buat Transaksi</button>
            <a href="javascript:history.back()" class="btn btn-secondary">Batal</a>
        </div>
    </div>
</form>

<script>
function updatePayable(type) { /* triggered by select change; nothing else needed here */ }
function fetchAmount() { /* server has the logic — submit form if user wants to autofill */ }

function updateFeePreview() {
    const amount = parseFloat(document.getElementById('amountInput')?.value || 0);
    const method = document.querySelector('input[name="method_code"]:checked')?.value || '';
    if (!amount || !method) {
        document.getElementById('feePreview').innerHTML = '<i class="fas fa-info-circle"></i> Pilih metode & isi nominal untuk melihat estimasi biaya admin.';
        return;
    }
    const grp = method.split('_')[0];
    let fee = 2000;
    if (grp === 'VA') fee = 4000;
    else if (method === 'QRIS') fee = Math.max(500, amount * 0.007);
    else if (grp === 'EWALLET') fee = amount * 0.02;
    else if (grp === 'BANK') fee = 5000;
    else if (grp === 'RETAIL') fee = 7500;

    const total = amount + fee;
    document.getElementById('feePreview').innerHTML =
        '<i class="fas fa-receipt"></i> Nominal <strong>Rp ' + amount.toLocaleString('id-ID') + '</strong> + ' +
        'Biaya admin <strong>Rp ' + Math.round(fee).toLocaleString('id-ID') + '</strong> = ' +
        'Total bayar <strong style="font-size:.92rem">Rp ' + Math.round(total).toLocaleString('id-ID') + '</strong>';
}

async function autofillFromPayable() {
    const type = document.querySelector('select[name=payable_type]')?.value;
    const id = document.querySelector('input[name=payable_id]')?.value;
    if (!type || !id) return;
    // tidak ada endpoint publik untuk ini — user isi manual saja, atau gunakan dari query string
}

updateFeePreview();
</script>
@endsection
