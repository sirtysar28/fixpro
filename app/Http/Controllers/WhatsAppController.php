<?php

namespace App\Http\Controllers;

use App\Models\WaRoom;
use App\Models\WaMessage;
use App\Services\WhatsAppService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * Fitur #9 — WhatsApp Web di dashboard Laravel.
 *
 * Fitur:
 *  - Inbox (daftar percakapan) dengan polling realtime
 *  - Detail percakapan + kirim balasan
 *  - QR Code login device
 *  - Status device
 *  - Webhook publik untuk menerima pesan masuk dari Fonnte
 */
class WhatsAppController extends Controller
{
    public function __construct(protected WhatsAppService $wa)
    {
    }

    /** GET /whatsapp — halaman inbox WhatsApp Web */
    public function index(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();
        $enabled  = $this->wa->isEnabled($cabangId);

        $query = WaRoom::query();
        if ($cabangId !== null && !auth()->user()->isSuperAdmin()) {
            $query->where(fn($q) => $q->where('cabang_id', $cabangId)->orWhereNull('cabang_id'));
        }
        $query->where('is_archived', false);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")->orWhere('number', 'like', "%$s%")->orWhere('last_message', 'like', "%$s%"));
        }

        $rooms = $query->orderByDesc('last_message_at')->paginate(30)->withQueryString();
        $totalUnread = (clone $query)->sum('unread');

        $deviceStatus = $enabled ? $this->wa->deviceStatus($cabangId) : ['connected' => false, 'success' => false];

        return view('whatsapp.index', compact('rooms', 'totalUnread', 'enabled', 'deviceStatus'));
    }

    /** GET /whatsapp/room/{room} — detail percakapan (HTML) */
    public function show(WaRoom $room)
    {
        $this->checkRoomAccess($room);

        // Reset unread saat dibuka
        $room->update(['unread' => 0]);

        $messages = $room->messages()->orderBy('created_at', 'asc')->take(200)->get();

        if (request()->wantsJson()) {
            return response()->json([
                'room' => $room,
                'messages' => $messages,
                'device_status' => $this->wa->deviceStatus($room->cabang_id),
            ]);
        }

        return view('whatsapp.show', compact('room', 'messages'));
    }

    /** POST /whatsapp/room/{room}/send — kirim balasan */
    public function send(Request $request, WaRoom $room)
    {
        $this->checkRoomAccess($room);

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $result = $this->wa->sendText($room->number, $validated['message'], $room->cabang_id);

        if (!$result['success']) {
            return back()->with('error', 'Gagal kirim: ' . ($result['error'] ?? 'unknown error'));
        }

        AuditLogService::log('whatsapp', 'send', "Balas WA ke {$room->number} ({$room->name})");

        return back()->with('success', 'Pesan terkirim.');
    }

    /** Kirim otomatis (invoice/tagihan) — POST /whatsapp/send-auto */
    public function sendAuto(Request $request)
    {
        $validated = $request->validate([
            'target'  => 'required|string',
            'message' => 'required|string|max:5000',
        ]);
        $cabangId = auth()->user()->getActiveCabangId();
        $result = $this->wa->sendAuto($validated['target'], $validated['message'], $cabangId);

        return response()->json($result + ['success' => $result['success'] ?? false]);
    }

    /** GET /whatsapp/qr — ambil QR Code login (JSON untuk fetch di UI) */
    public function getQr()
    {
        $cabangId = auth()->user()->getActiveCabangId();
        $qr = $this->wa->getQrCode($cabangId);

        // Jika device sudah terhubung, beri sinyal ke UI untuk menyembunyikan kartu QR
        if (!empty($qr['connected'])) {
            return response()->json([
                'success'   => false,
                'connected' => true,
                'message'   => $qr['message'],
            ]);
        }

        return response()->json($qr);
    }

    /** GET /whatsapp/device-status — polling status device */
    public function deviceStatus()
    {
        $cabangId = auth()->user()->getActiveCabangId();
        return response()->json($this->wa->deviceStatus($cabangId));
    }

    /** GET /whatsapp/poll — polling pesan terbaru untuk dashboard realtime */
    public function poll(Request $request)
    {
        $cabangId = auth()->user()->getActiveCabangId();

        $sinceId = (int) $request->input('since_id', 0);
        $query = WaMessage::with('room')->where('id', '>', $sinceId);
        if ($cabangId !== null && !auth()->user()->isSuperAdmin()) {
            $query->whereHas('room', fn($q) => $q->where('cabang_id', $cabangId)->orWhereNull('cabang_id'));
        }
        $messages = $query->orderBy('id', 'asc')->take(50)->get();

        // Total unread
        $rooms = WaRoom::query();
        if ($cabangId !== null && !auth()->user()->isSuperAdmin()) {
            $rooms->where(fn($q) => $q->where('cabang_id', $cabangId)->orWhereNull('cabang_id'));
        }
        $totalUnread = (clone $rooms)->sum('unread');

        return response()->json([
            'messages'    => $messages,
            'total_unread'=> $totalUnread,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /** POST /whatsapp/webhook — endpoint publik untuk Fonnte push pesan masuk */
    public function webhook(Request $request)
    {
        // Verifikasi token
        $token = $request->query('token') ?: $request->header('X-Webhook-Token');
        if (!$this->wa->validateWebhookToken($token)) {
            return response()->json(['success' => false, 'message' => 'Token webhook tidak valid.'], 401);
        }

        $payload = $request->all();

        // Fonnte kadang kirim event {event:"incoming-message"} di field 'event'
        if (isset($payload['event']) && $payload['event'] !== 'incoming-message' && $payload['event'] !== 'new-message') {
            // Event lain (status delivered/read) — abaikan untuk sekarang
            return response()->json(['success' => true, 'ignored' => $payload['event']]);
        }

        $msg = $this->wa->handleWebhook($payload, null);

        return response()->json(['success' => (bool) $msg, 'message_id' => $msg?->id]);
    }

    /** POST /whatsapp/room/{room}/archive */
    public function archive(WaRoom $room)
    {
        $this->checkRoomAccess($room);
        $room->update(['is_archived' => true]);
        return back()->with('success', 'Percakapan diarsipkan.');
    }

    private function checkRoomAccess(WaRoom $room): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) return;
        $cabangId = $user->getActiveCabangId();
        if ($cabangId !== null && $room->cabang_id !== null && $room->cabang_id != $cabangId) {
            abort(403, 'Anda tidak punya akses ke percakapan ini.');
        }
    }
}
