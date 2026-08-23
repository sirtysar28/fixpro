@extends('layouts.app')
@section('title', 'Pilih Toko / Cabang')

@section('content')
<div style="max-width:720px;margin:40px auto;padding:0 16px">

    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.05)">
        <div style="background:#fffbeb;padding:22px 26px;border-bottom:1px solid #fde68a;text-align:center">
            <div style="font-size:2.4rem;margin-bottom:6px">🏪</div>
            <h2 style="font-size:1.15rem;font-weight:800;color:#92400e;margin:0 0 6px">Pilih Toko / Cabang Dulu</h2>
            <p style="font-size:.85rem;color:#b45309;margin:0;line-height:1.6">
                Stok sparepart <b>tidak ditampilkan campur antar toko</b>.<br>
                Silakan pilih toko/cabang yang stok-nya ingin Anda lihat:
            </p>
        </div>

        <div style="padding:20px 26px">
            @php
                $roots = \App\Models\Cabang::whereNull('parent_cabang_id')->orderBy('nama')->get();
            @endphp

            @foreach($roots as $root)
                <form method="POST" action="{{ route('cabang.set') }}" style="margin:0">
                    @csrf
                    <input type="hidden" name="cabang_id" value="{{ $root->id }}">
                    <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                    <button type="submit" style="display:flex;align-items:center;justify-content:space-between;width:100%;text-align:left;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;margin-bottom:8px;cursor:pointer;transition:all .15s"
                            onmouseover="this.style.borderColor='var(--primary)';this.style.background='#f0fdf4'"
                            onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'">
                        <span style="font-weight:700;font-size:.9rem;color:#0f172a">
                            {{ !$root->aktif ? '⏸️' : '🏢' }} {{ $root->nama }}
                            <span style="font-size:.68rem;color:#94a3b8;font-weight:600">(Pusat)</span>
                        </span>
                        <span style="font-size:.72rem;color:var(--primary);font-weight:700">Lihat Stok &rarr;</span>
                    </button>
                </form>

                @php $children = \App\Models\Cabang::where('parent_cabang_id', $root->id)->orderBy('nama')->get(); @endphp
                @foreach($children as $child)
                <form method="POST" action="{{ route('cabang.set') }}" style="margin:0">
                    @csrf
                    <input type="hidden" name="cabang_id" value="{{ $child->id }}">
                    <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                    <button type="submit" style="display:flex;align-items:center;justify-content:space-between;width:100%;text-align:left;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:10px 16px;margin:0 0 8px 24px;cursor:pointer;transition:all .15s"
                            onmouseover="this.style.borderColor='var(--primary)';this.style.background='#f0fdf4'"
                            onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#fff'">
                        <span style="font-weight:600;font-size:.85rem;color:#334155">
                            {{ !$child->aktif ? '⏸️' : '📍' }} {{ $child->nama }}
                            <span style="font-size:.66rem;color:#94a3b8">— cabang {{ $root->nama }}</span>
                        </span>
                        <span style="font-size:.7rem;color:var(--primary);font-weight:700">Lihat Stok &rarr;</span>
                    </button>
                </form>
                @endforeach
            @endforeach

            @if($roots->isEmpty())
            <p style="text-align:center;color:#94a3b8;font-size:.85rem;padding:20px 0">Belum ada data toko/cabang.</p>
            @endif

            <div style="margin-top:16px;padding-top:14px;border-top:1px dashed #e2e8f0;text-align:center">
                <a href="{{ route('dashboard') }}" style="font-size:.78rem;color:#94a3b8;text-decoration:none">
                    <i class="fas fa-arrow-left"></i>&nbsp; Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
