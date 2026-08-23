@if(!empty($brand) && $brand)
<div style="background:#0f766e;padding:18px 24px;text-align:center">
    <div style="font-size:1.4rem;font-weight:800;color:#fff">{{ $brand }}</div>
    <div style="font-size:.72rem;color:#ccfbf1">Sistem Manajemen Servis Profesional</div>
</div>
@endif

<div style="padding:24px;font-family:Arial,Helvetica,sans-serif;color:#1e293b;line-height:1.6">
    <h2 style="margin:0 0 14px;font-size:1.05rem">{{ $subject ?? '' }}</h2>

    @foreach($lines ?? [] as $line)
        @if(trim($line) === '')
            <div style="height:10px"></div>
        @elseif(str_starts_with($line, '---'))
            <hr style="border:none;border-top:1px dashed #cbd5e1;margin:14px 0">
        @elseif(str_starts_with($line, 'http'))
            <div><a href="{{ $line }}" style="color:#0d9488;font-weight:600;text-decoration:none">{{ $line }}</a></div>
        @else
            <div style="font-size:.88rem">{{ $line }}</div>
        @endif
    @endforeach
</div>

@if(!empty($brand) && $brand)
<div style="background:#f1f5f9;padding:14px 24px;text-align:center;font-size:.72rem;color:#64748b">
    © {{ date('Y') }} {{ $brand }} — Email ini dikirim otomatis, mohon tidak membalas.
</div>
@endif
