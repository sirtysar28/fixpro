@extends('layouts.app')
@section('title', 'Status Pembayaran')

@section('content')
<div style="max-width:560px;margin:0 auto;text-align:center;padding-top:30px">
    @if($payment->isPaid())
    <i class="fas fa-check-circle" style="font-size:3.6rem;color:#16a34a"></i>
    <h2 style="margin:12px 0 6px">Pembayaran Berhasil</h2>
    <p style="color:#64748b;font-size:.86rem">Terima kasih! Pembayaran <strong>{{ $payment->kode }}</strong> sebesar <strong>{{ formatRp($payment->total_bayar) }}</strong> telah kami terima.</p>
    @elseif($payment->isPending())
    <i class="fas fa-clock" style="font-size:3.6rem;color:#d97706"></i>
    <h2 style="margin:12px 0 6px">Menunggu Pembayaran</h2>
    <p style="color:#64748b;font-size:.86rem">Pembayaran <strong>{{ $payment->kode }}</strong> belum kami terima. Selesaikan pembayaran sebelum kedaluwarsa.</p>
    <a href="{{ route('payment.show', $payment->kode) }}" class="btn btn-primary" style="margin-top:12px;text-decoration:none"><i class="fas fa-eye"></i> Lihat Instruksi</a>
    @else
    <i class="fas fa-times-circle" style="font-size:3.6rem;color:#dc2626"></i>
    <h2 style="margin:12px 0 6px">Pembayaran {{ ucfirst($payment->status) }}</h2>
    <p style="color:#64748b;font-size:.86rem">Transaksi <strong>{{ $payment->kode }}</strong> berstatus: <strong>{{ $payment->status }}</strong>.</p>
    @endif

    <div style="margin-top:18px">
        <a href="{{ route('payment.select') }}" class="btn btn-secondary btn-sm" style="text-decoration:none"><i class="fas fa-home"></i> Halaman Pembayaran</a>
    </div>
</div>

@if($payment->isPending())
<script>
setInterval(() => location.reload(), 15000);
</script>
@endif
@endsection
