<?php

namespace App\Http\Controllers;

use App\Models\WaRoom;
use App\Models\WaMessage;
use App\Services\WhatsAppService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Fitur #9 — WhatsApp Web di dashboard Laravel.
 *
 * Fitur:
 *  - Inbox (daftar percakapan) dengan polling realtime
 *  - Detail percakapan + kirim balasan
 *  - QR Code login device
 *  - Status device
 *  - Webhook publik untuk menerima pesan masuk dari Fonnte
 *  - Statistics dashboard
 *  - Chat preview sidebar (messages API)
 *  - Quick reply dari sidebar
 *  - Bulk actions (mark read, delete, send)
 */
class WhatsAppController extends Controller
{
    public function __construct(protected WhatsAppService $wa)
    {
    }

    // =========================================================================
    // INBOX & LISTING
    // =========================================================================

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

        // --- Search ---
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%$s%")
                ->orWhere('number', 'like', "%$s%")
                ->orWhere('last_message', 'like', "%$s%")
            );
        }

        // --- Filter ---
        $filter = $request->input('filter', 'all');
        if ($filter === 'unread') {
            $query->where('unread', '>', 0);
        } elseif ($filter === 'read') {
            $query->where('unread', 0);
        } elseif ($filter === 'outgoing') {
            $query->where('last_direction', 'out');
        } elseif ($filter === 'incoming') {
            $query->where('last_direction', 'in');
        }

        // --- Sort ---
        $sort = $request->input('sort', 'latest');
        if ($sort === 'oldest') {
            $query->orderBy('last_message_at', 'asc');
        } elseif ($sort === 'name_asc') {
            $query->orderBy('name', 'asc');
        } elseif ($sort === 'name_desc') {
            $query->orderBy('name', 'desc');
        } elseif ($sort === 'unread_first') {
            $query->orderByDesc('unread')->orderByDesc('last_message_at');
        } else {
            $query->orderByDesc('last_message_at');
        }

        // --- Date Range ---
        if ($request->filled('date_from')) {
            $query->whereDate('last_message_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('last_message_at', '<=', $request->date_to);
        }

        $rooms = $query->paginate(30)->withQueryString();

        // Total unread (tanpa filter/sort agar angka real)
        $unreadQuery = WaRoom::query();
        if ($cabangId !== null && !auth()->user()->isSuperAdmin()) {
            $unreadQuery->where(fn($q) => $q->where('cabang_id', $cabangId)->orWhereNull('cabang_id'));
        }
        $unreadQuery->where('is_archived', false);
        $totalUnread = $unreadQuery->sum('unread');

        // Stats untuk dashboard card
        $stats = $this->getStatsData($cabangId);

        $deviceStatus = $enabled ? $this->wa->deviceStatus($cabangId) : ['connected' => false, 'success' => false];

        return view('whatsapp.index', compact('rooms', 'totalUnread', 'enabled', 'deviceStatus', 'stats'));
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    /** GET /whatsapp/stats — JSON stats untuk refresh via AJAX */
    public function stats()
    {
        $cabangId = auth()->user()->getActiveCabangId();
        return response()->json($this->getStatsData($cabangId));
    }

    /**
     * Internal: hitung semua statistik
     */
    private function getStatsData(?int $cabangId): array
    {
        $msgQuery = WaMessage::query();
        $roomQuery = WaRoom::query();

        if ($cabangId !== null && !auth()->user()->isSuperAdmin()) {
            $msgQuery->where(fn($q) => $q
                ->whereHas('room', fn($r) => $r->where('cabang_id', $cabangId)->orWhereNull('cabang_id'))
            );
            $roomQuery->where(fn($q) => $q->where('cabang_id', $cabangId)->orWhereNull('cabang_id'));
        }

        $today = (clone $msgQuery)->whereDate('created_at', today())
            ->where('direction', 'in')
            ->count();

        $week = (clone $msgQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('direction', 'in')
            ->count();

        $unread = (clone $roomQuery)->where('is_archived', false)->sum('unread');
        $total  = (clone $roomQuery)->count();

        $outgoing = (clone $msgQuery)->where('direction', 'out')
            ->whereDate('created_at', today())
            ->count();

        // Rata-rata waktu respons (dalam menit)
        $avgResponse = $this->calculateAvgResponse($roomQuery);

        return [
            'today'       => $today,
            'week'        => $week,
            'unread'      => $unread,
            'total'       => $total,
            'outgoing'    => $outgoing,
            'avg_response'=> $avgResponse,
        ];
    }

    /**
     * Internal: hitung rata-rata waktu respons
     */
    private function calculateAvgResponse($roomQuery): string
    {
        $rooms = (clone $roomQuery)
            ->whereNotNull('last_message_at')
            ->limit(100)
            ->get();

        if ($rooms->isEmpty()) {
            return '-';
        }

        $totalMinutes = 0;
        $count = 0;

        foreach ($rooms as $room) {
            $lastIn = WaMessage::where('room_id', $room->id)
                ->where('direction', 'in')
                ->orderByDesc('created_at')
                ->first();

            if (!$lastIn) {
                continue;
            }

            $firstOut = WaMessage::where('room_id', $room->id)
                ->where('direction', 'out')
                ->where('created_at', '>', $lastIn->created_at)
                ->orderBy('created_at')
                ->first();

            if ($firstOut) {
                $diff = $lastIn->created_at->diffInMinutes($firstOut->created_at);
                if ($diff < 1440) { // Hanya dalam 24 jam
                    $totalMinutes += $diff;
                    $count++;
                }
            }
        }

        if ($count === 0) {
            return '-';
        }

        $avg = round($totalMinutes / $count);
        return $avg < 60 ? $avg . ' mnt' : round($avg / 60) . ' jam';
    }

    // =========================================================================
    // QR CODE & DEVICE
    // =========================================================================

    /** GET /whatsapp/qr — ambil QR Code login (JSON untuk fetch di UI) */
    public function getQr()
    {
        $cabangId = auth()->user()->getActiveCabangId();
        $qr = $this->wa->getQrCode($cabangId);

        // Jika device sudah terhubung
        if (!empty($qr['connected'])) {
            return response()->json([
                'success'   => false,
                'connected' => true,
                'message'   => $qr['message'] ?? 'Device sudah terhubung.',
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

    // =========================================================================
    // POLLING REALTIME
    // =========================================================================

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
        $totalUnread = (clone $rooms)->where('is_archived', false)->sum('unread');

        // Deteksi apakah ada update (untuk trigger refresh list)
        $hasUpdates = $messages->contains(fn($m) => $m->direction === 'in');

        return response()->json([
            'messages'     => $messages,
            'total_unread' => $totalUnread,
            'has_updates'  => $hasUpdates,
            'server_time'  => now()->toIso8601String(),
        ]);
    }

    // =========================================================================
    // ROOM DETAIL
    // =========================================================================

    /** GET /whatsapp/room/{room} — detail percakapan (HTML) */
    public function show(WaRoom $room)
    {
        $this->checkRoomAccess($room);

        // Reset unread saat dibuka
        $room->update(['unread' => 0]);

        $messages = $room->messages()->orderBy('created_at', 'asc')->take(200)->get();

        if (request()->wantsJson()) {
            return response()->json([
                'room'          => $room,
                'messages'      => $messages,
                'device_status' => $this->wa->deviceStatus($room->cabang_id),
            ]);
        }

        return view('whatsapp.show', compact('room', 'messages'));
    }

    /** GET /whatsapp/room/{room}/messages — pesan untuk sidebar preview (JSON) */
    public function getMessages(WaRoom $room)
    {
        $this->checkRoomAccess($room);

        $messages = $room->messages()
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get()
            ->map(fn($msg) => [
                'id'         => $msg->id,
                'message'    => $msg->message,
                'direction'  => $msg->direction,
                'created_at' => $msg->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'room'     => [
                'name'   => $room->name,
                'number' => $room->number,
            ],
            'messages' => $messages,
        ]);
    }

    // =========================================================================
    // KIRIM PESAN
    // =========================================================================

    /** POST /whatsapp/room/{room}/send — kirim balasan dari halaman detail */
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

    /** POST /whatsapp/room/{room}/reply — quick reply dari sidebar (JSON) */
    public function sendReply(Request $request, WaRoom $room)
    {
        $this->checkRoomAccess($room);

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $result = $this->wa->sendText($room->number, $request->message, $room->cabang_id);

        if ($result['success']) {
            AuditLogService::log('whatsapp', 'send', "Quick reply WA ke {$room->number} ({$room->name})");
            return response()->json(['success' => true]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim: ' . ($result['error'] ?? 'unknown error'),
        ], 500);
    }

    /** POST /whatsapp/send-auto — kirim otomatis (invoice/tagihan) */
    public function sendAuto(Request $request)
    {
        $validated = $request->validate([
            'target'  => 'required|string',
            'message' => 'required|string|max:5000',
        ]);
        $cabangId = auth()->user()->getActiveCabangId();
        $result   = $this->wa->sendAuto($validated['target'], $validated['message'], $cabangId);

        return response()->json($result + ['success' => $result['success'] ?? false]);
    }

    // =========================================================================
    // MARK AS READ
    // =========================================================================

    /** POST /whatsapp/room/{room}/mark-read — tandai 1 room dibaca (JSON) */
    public function markRead(WaRoom $room)
    {
        $this->checkRoomAccess($room);
        $room->update(['unread' => 0]);

        return response()->json(['success' => true]);
    }

    /** POST /whatsapp/mark-read-bulk — tandai banyak room dibaca (JSON) */
    public function markReadBulk(Request $request)
    {
        $request->validate([
            'room_ids'   => 'required|array',
            'room_ids.*' => 'exists:wa_rooms,id',
        ]);

        $query = WaRoom::query();
        $this->applyCabangFilter($query);

        $updated = $query->whereIn('id', $request->room_ids)
            ->update(['unread' => 0]);

        return response()->json([
            'success' => true,
            'updated' => $updated,
        ]);
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /** POST /whatsapp/delete-bulk — hapus banyak room + pesannya (JSON) */
    public function deleteBulk(Request $request)
    {
        $request->validate([
            'room_ids'   => 'required|array',
            'room_ids.*' => 'exists:wa_rooms,id',
        ]);

        $query = WaRoom::query();
        $this->applyCabangFilter($query);

        $rooms = (clone $query)->whereIn('id', $request->room_ids);
        $deleted = $rooms->count();

        // Hapus pesan terlebih dahulu
        $roomIds = $rooms->pluck('id');
        WaMessage::whereIn('room_id', $roomIds)->delete();

        // Hapus rooms
        $rooms->delete();

        AuditLogService::log('whatsapp', 'delete-bulk', "Hapus {$deleted} percakapan WA");

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
        ]);
    }

    // =========================================================================
    // BULK SEND
    // =========================================================================

    /** POST /whatsapp/send-bulk — kirim pesan ke banyak room (JSON) */
    public function sendBulk(Request $request)
    {
        $request->validate([
            'room_ids'   => 'required|array',
            'room_ids.*' => 'exists:wa_rooms,id',
            'message'    => 'required|string|max:5000',
        ]);

        $query = WaRoom::query();
        $this->applyCabangFilter($query);

        $rooms = $query->whereIn('id', $request->room_ids)->get();

        if ($rooms->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Tidak ada room yang valid.'], 400);
        }

        $sent   = 0;
        $failed = 0;
        $errors = [];

        foreach ($rooms as $room) {
            // Replace variabel {nama} dan {nomor}
            $message = str_replace(
                ['{nama}', '{nomor}'],
                [$room->name ?? '', $room->number],
                $request->message
            );

            $result = $this->wa->sendText($room->number, $message, $room->cabang_id);

            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
                $errors[] = "{$room->number}: " . ($result['error'] ?? 'unknown');
            }
        }

        AuditLogService::log('whatsapp', 'send-bulk', "Bulk send WA ke {$sent} nomor ({$failed} gagal)");

        return response()->json([
            'success' => true,
            'sent'    => $sent,
            'failed'  => $failed,
            'total'   => $rooms->count(),
            'errors'  => $errors,
        ]);
    }

    // =========================================================================
    // ARCHIVE
    // =========================================================================

    /** POST /whatsapp/room/{room}/archive */
    public function archive(WaRoom $room)
    {
        $this->checkRoomAccess($room);
        $room->update(['is_archived' => true]);

        AuditLogService::log('whatsapp', 'archive', "Arsipkan WA {$room->number} ({$room->name})");

        return back()->with('success', 'Percakapan diarsipkan.');
    }

    // =========================================================================
    // WEBHOOK (PUBLIC)
    // =========================================================================

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

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Cek akses room berdasarkan cabang user
     */
    private function checkRoomAccess(WaRoom $room): void
    {
        $user     = auth()->user();
        $cabangId = $user->getActiveCabangId();

        // Super Admin bebas akses
        if ($user->isSuperAdmin()) {
            return;
        }

        // Jika room punya cabang_id dan tidak cocok → deny
        if ($cabangId !== null && $room->cabang_id !== null && $room->cabang_id != $cabangId) {
            abort(403, 'Anda tidak punya akses ke percakapan ini.');
        }
    }

    /**
     * Terapkan filter cabang ke query WaRoom
     */
    private function applyCabangFilter($query): void
    {
        $cabangId = auth()->user()->getActiveCabangId();
        if ($cabangId !== null && !auth()->user()->isSuperAdmin()) {
            $query->where(fn($q) => $q->where('cabang_id', $cabangId)->orWhereNull('cabang_id'));
        }
    }
}