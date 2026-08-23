@extends('layouts.app')
@section('title', 'Percakapan WhatsApp')

@section('content')
<a href="{{ route('whatsapp.index') }}" class="btn btn-secondary btn-sm mb-3"><i class="fas fa-arrow-left"></i> Kembali ke Inbox</a>

<div class="card" style="max-width:780px;margin:0 auto">
    <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid #e2e8f0;background:#f8fafc">
        <div style="width:44px;height:44px;border-radius:50%;background:#25D366;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">
            {{ strtoupper(substr($room->name ?: $room->number, 0, 2)) }}
        </div>
        <div style="flex:1">
            <strong>{{ $room->name ?: $room->number }}</strong>
            @if($room->name)<div style="font-size:.74rem;color:#64748b">{{ $room->number }}</div>@endif
        </div>
        <span class="wa-badge online" style="display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:14px;font-size:.7rem;font-weight:700;background:#dcfce7;color:#15803d">
            <span style="width:8px;height:8px;border-radius:50%;background:#16a34a;display:inline-block"></span> WhatsApp
        </span>
    </div>

    <div id="chatBox" style="height:480px;overflow-y:auto;padding:16px;background:#efeae2;background-image:linear-gradient(rgba(255,255,255,.4),rgba(255,255,255,.4));">
        @foreach($messages as $msg)
        <div class="msg {{ $msg->direction === 'out' ? 'out' : 'in' }}">
            @if($msg->type !== 'text' && $msg->media_url)
            <div style="margin-bottom:4px">
                @if($msg->type === 'image')
                <img src="{{ $msg->media_url }}" style="max-width:240px;border-radius:8px;display:block">
                @elseif($msg->type === 'document')
                <a href="{{ $msg->media_url }}" target="_blank" style="color:#fff;text-decoration:underline"><i class="fas fa-file"></i> {{ $msg->filename ?: 'Dokumen' }}</a>
                @endif
            </div>
            @endif
            @if($msg->message)
            <div class="bubble">{{ nl2br(e($msg->message)) }}</div>
            @endif
            <div class="meta">
                @if($msg->is_auto) <span title="Dikirim otomatis">🤖</span> @endif
                {{ $msg->created_at->format('H:i') }}
                @if($msg->direction === 'out') <i class="fas fa-check-double" style="font-size:.7rem"></i> @endif
            </div>
        </div>
        @endforeach
        @if($messages->count() === 0)
        <div style="text-align:center;color:#64748b;padding:40px">Belum ada pesan. Mulai percakapan dengan mengirim pesan di bawah.</div>
        @endif
    </div>

    <form method="POST" action="{{ route('whatsapp.send', $room) }}" style="display:flex;gap:8px;padding:12px 18px;border-top:1px solid #e2e8f0;background:#fff">
        @csrf
        <input type="text" name="message" class="form-input" placeholder="Ketik pesan..." required style="flex:1" autofocus>
        <button type="submit" class="btn btn-success" style="background:#25D366;color:#fff"><i class="fas fa-paper-plane"></i> Kirim</button>
    </form>
</div>

@if(session('success') || session('error'))
<script>
@if(session('success')) alert('{{ session('success') }}'); @endif
@if(session('error')) alert('{{ session('error') }}'); @endif
</script>
@endif

<style>
.msg { margin-bottom:10px; max-width:75%; display:flex; flex-direction:column; }
.msg.in { align-self:flex-start; }
.msg.out { align-self:flex-end; align-items:flex-end; }
.msg .bubble { padding:8px 12px; border-radius:10px; font-size:.86rem; line-height:1.4; word-wrap:break-word; word-break:break-word; }
.msg.in .bubble { background:#fff; color:#111; border-top-left-radius:2px; }
.msg.out .bubble { background:#d9fdd3; color:#111; border-top-right-radius:2px; }
.msg .meta { font-size:.66rem; color:#64748b; margin-top:3px; display:flex; align-items:center; gap:4px; }
#chatBox { display:flex; flex-direction:column; }
</style>

<script>
const box = document.getElementById('chatBox');
box.scrollTop = box.scrollHeight;

// Polling realtime untuk pesan baru masuk
async function pollMessages() {
    try {
        const last = document.querySelectorAll('#chatBox .msg').length;
        const r = await fetch('{{ route("whatsapp.poll") }}?since_id={{ $messages->last()?->id ?? 0 }}');
        const d = await r.json();
        if (d.messages && d.messages.length > 0) {
            const newIn = d.messages.filter(m => m.direction === 'in' && m.room_id === {{ $room->id }});
            if (newIn.length > 0) location.reload(); // ada pesan masuk baru → reload
        }
    } catch (e) {}
}
setInterval(pollMessages, 6000);
</script>
@endsection
