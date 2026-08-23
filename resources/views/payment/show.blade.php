@extends('layouts.app')
@section('title', 'Instruksi Pembayaran ' . $payment->kode)

@section('content')
<?php
    $grp = \App\Models\Payment::methodGroup($payment->method_code);
    $groupMeta = [
        'va'      => ['icon' => 'fa-university',       'color' => '#2563eb', 'bg' => '#dbeafe'],
        'qris'    => ['icon' => 'fa-qrcode',           'color' => '#7c3aed', 'bg' => '#ede9fe'],
        'ewallet' => ['icon' => 'fa-wallet',           'color' => '#db2777', 'bg' => '#fce7f3'],
        'bank'    => ['icon' => 'fa-money-bill-wave',  'color' => '#059669', 'bg' => '#d1fae5'],
        'retail'  => ['icon' => 'fa-store',            'color' => '#d97706', 'bg' => '#fef3c7'],
    ][$grp] ?? ['icon' => 'fa-credit-card', 'color' => '#0d9488', 'bg' => '#ccfbf1'];

    $statusMeta = [
        'pending'  => ['label' => 'Menunggu Pembayaran', 'color' => '#b45309', 'bg' => '#fef3c7', 'icon' => 'fa-clock'],
        'paid'     => ['label' => 'LUNAS',                'color' => '#15803d', 'bg' => '#dcfce7', 'icon' => 'fa-check-circle'],
        'expired'  => ['label' => 'Kedaluwarsa',          'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'fa-times-circle'],
        'failed'   => ['label' => 'Gagal',                'color' => '#dc2626', 'bg' => '#fee2e2', 'icon' => 'fa-times-circle'],
        'refunded' => ['label' => 'Dana Kembali',         'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'fa-undo'],
    ][$payment->status] ?? ['label' => $payment->status, 'color' => '#475569', 'bg' => '#f1f5f9', 'icon' => 'fa-circle'];
?>

<div style="max-width:680px;margin:0 auto">
    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:.84rem"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif

    <div class="card mb-4" style="text-align:center;padding:24px">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:16px;background:{{ $groupMeta['bg'] }};color:{{ $groupMeta['color'] }};margin-bottom:12px">
            <i class="fas {{ $groupMeta['icon'] }}" style="font-size:1.6rem"></i>
        </div>
        <div style="font-size:.78rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;font-weight:700">{{ \App\Models\Payment::methodLabel($payment->method_code) }}</div>
        <h2 style="margin:6px 0;font-size:1.5rem">{{ formatRp($payment->total_bayar) }}</h2>

        <span style="display:inline-block;background:{{ $statusMeta['bg'] }};color:{{ $statusMeta['color'] }};padding:4px 14px;border-radius:14px;font-size:.78rem;font-weight:700;margin-top:4px">
            <i class="fas {{ $statusMeta['icon'] }}"></i> {{ $statusMeta['label'] }}
        </span>

        <div style="margin-top:14px;font-size:.74rem;color:#94a3b8">Kode Transaksi: <strong style="color:#475569">{{ $payment->kode }}</strong></div>
        @if($payment->expired_at && $payment->isPending())
        <div style="margin-top:6px;font-size:.74rem;color:#dc2626"><i class="fas fa-clock"></i> Bayar sebelum: <strong>{{ $payment->expired_at->format('d/m/Y H:i') }}</strong></div>
        @endif
    </div>

    @if($payment->isPending())
    <div class="card mb-4">
        <h3 style="font-size:.92rem;margin-bottom:14px"><i class="fas fa-list-ol" style="color:var(--primary)"></i> Instruksi Pembayaran</h3>

        {{-- VA Number --}}
        @if($grp === 'va' && $payment->va_number)
        <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:12px">
            <div style="font-size:.74rem;color:#64748b;margin-bottom:4px">Nomor Virtual Account</div>
            <div style="display:flex;align-items:center;gap:8px">
                <code style="font-size:1.4rem;font-weight:800;color:var(--primary);letter-spacing:1px">{{ $payment->va_number }}</code>
                <button onclick="copyText('{{ $payment->va_number }}')" class="btn btn-secondary btn-xs"><i class="fas fa-copy"></i></button>
            </div>
        </div>
        @endif

        {{-- QRIS --}}
        @if($grp === 'qris' && $payment->qr_string)
        <div style="text-align:center;background:#f8fafc;border-radius:10px;padding:18px;margin-bottom:12px">
            <div id="qrisBox" style="display:inline-block;padding:14px;background:#fff;border:1px solid #e2e8f0;border-radius:12px"></div>
            <div style="font-size:.78rem;color:#64748b;margin-top:10px">Scan QR di atas dengan aplikasi e-wallet/bank apa pun (GoPay, OVO, DANA, ShopeePay, dll).</div>
        </div>
        @endif

        {{-- Generic instructions --}}
        @if($payment->instructions)
        <ol style="font-size:.82rem;line-height:1.7;padding-left:20px;color:#475569">
            @foreach((array) $payment->instructions as $step)
            <li>{{ is_array($step) ? ($step['instruction'] ?? json_encode($step)) : $step }}</li>
            @endforeach
        </ol>
        @endif

        {{-- Pay URL (checkout) --}}
        @if($payment->pay_url)
        <a href="{{ $payment->pay_url }}" target="_blank" class="btn btn-primary" style="width:100%;text-align:center;text-decoration:none;display:block;margin-top:12px">
            <i class="fas fa-external-link-alt"></i> Buka Halaman Pembayaran
        </a>
        @endif

        <form method="POST" action="{{ route('payment.refresh', $payment->kode) }}" style="margin-top:12px">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm" style="width:100%"><i class="fas fa-sync"></i> Cek Status Pembayaran</button>
        </form>
    </div>
    @endif

    @if($payment->isPaid())
    <div class="card mb-4" style="text-align:center;background:#f0fdf4;border-color:#bbf7d0">
        <i class="fas fa-check-circle" style="font-size:2.6rem;color:#16a34a"></i>
        <h3 style="margin:8px 0 4px">Pembayaran Berhasil!</h3>
        <p style="font-size:.82rem;color:#475569;margin-bottom:14px">
            Pembayaran sebesar <strong>{{ formatRp($payment->amount) }}</strong> telah kami terima @if($payment->paid_at) pada {{ $payment->paid_at->format('d/m/Y H:i') }} @endif.
            @if($payment->reference) Referensi: <strong>{{ $payment->reference }}</strong> @endif
        </p>
        @if($payment->pay_url)
        <a href="{{ $payment->pay_url }}" class="btn btn-primary btn-sm"><i class="fas fa-home"></i> Kembali</a>
        @endif
    </div>
    @endif

    {{-- Detail --}}
    <div class="card">
        <h3 style="font-size:.88rem;margin-bottom:10px"><i class="fas fa-receipt"></i> Rincian Transaksi</h3>
        <table style="width:100%;font-size:.82rem">
            <tr><td style="color:#94a3b8;padding:6px 0">Nominal</td><td style="text-align:right">{{ formatRp($payment->amount) }}</td></tr>
            <tr><td style="color:#94a3b8;padding:6px 0">Biaya Admin</td><td style="text-align:right">{{ formatRp($payment->fee_customer) }}</td></tr>
            <tr style="border-top:1px solid #e2e8f0"><td style="font-weight:700;padding:8px 0">Total Bayar</td><td style="text-align:right;font-weight:700;color:var(--primary)">{{ formatRp($payment->total_bayar) }}</td></tr>
            @if($payment->customer_name)<tr><td style="color:#94a3b8;padding:6px 0">Pelanggan</td><td style="text-align:right">{{ $payment->customer_name }}</td></tr>@endif
            @if($payment->customer_phone)<tr><td style="color:#94a3b8;padding:6px 0">No. HP</td><td style="text-align:right">{{ $payment->customer_phone }}</td></tr>@endif
        </table>
    </div>

    <div style="text-align:center;margin-top:16px">
        <a href="{{ route('payment.select') }}" class="btn btn-secondary btn-sm"><i class="fas fa-plus"></i> Pembayaran Baru</a>
        <a href="{{ route('payment.riwayat') }}" class="btn btn-secondary btn-sm"><i class="fas fa-history"></i> Riwayat</a>
    </div>
</div>

@if($grp === 'qris' && $payment->qr_string && $payment->isPending())
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('qrisBox'), { text: {{ json_encode($payment->qr_string) }}, width: 220, height: 220 });
</script>
@endif

<script>
function copyText(t) {
    navigator.clipboard.writeText(t);
    alert('Disalin: ' + t);
}

// Auto-polling status saat pending
@if($payment->isPending())
const kode = {{ json_encode($payment->kode) }};
setInterval(() => {
    fetch('/payment/status/' + encodeURIComponent(kode))
        .then(r => r.json())
        .then(d => { if (d.paid) location.reload(); })
        .catch(() => {});
}, 8000);
@endif
</script>
@endsection
