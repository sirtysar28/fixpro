<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatRoom;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Get or create chat room for current user
     */
    public function getRoom()
    {
        $user = auth()->user();
        $cabangId = $user->getActiveCabangId();

        $room = ChatRoom::where('user_id', $user->id)
            ->where('cabang_id', $cabangId)
            ->first();

        if (!$room) {
            $admin = User::where('cabang_id', $cabangId)
                ->whereHas('role', fn($q) => $q->where('name', 'Admin'))
                ->first();
            if (!$admin) $admin = User::where('is_super_admin', true)->first();
            if (!$admin) $admin = User::first();

            $room = ChatRoom::create([
                'user_id' => $user->id,
                'admin_id' => $admin?->id ?? 1,
                'cabang_id' => $cabangId,
                'last_message_at' => now(),
            ]);
        }

        return $room;
    }

    public function getMessages(Request $request)
    {
        try {
            $room = $this->getRoom();

            Chat::where('room_id', $room->id)
                ->where('sender_id', '!=', auth()->id())
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $messages = Chat::where('room_id', $room->id)
                ->with('sender')
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json(['room' => $room, 'messages' => $messages]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Chat getMessages error: ' . $e->getMessage());
            return response()->json(['room' => null, 'messages' => []]);
        }
    }

    public function sendMessage(Request $request)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        try {
            $user = auth()->user();
            $room = $this->getRoom();

            $chat = Chat::create([
                'room_id' => $room->id,
                'sender_id' => $user->id,
                'receiver_id' => $room->admin_id,
                'cabang_id' => $room->cabang_id,
                'message' => $request->message,
                'is_read' => false,
                'is_bot' => false,
            ]);

            $room->update(['last_message_at' => now()]);

            // Bot reply dibungkus try-catch sendiri — kalau gagal, pesan user tetap tersimpan
            try {
                $this->sendBotReply($room, $request->message);
            } catch (\Exception $botError) {
                \Illuminate\Support\Facades\Log::error('ChatBot: Bot reply gagal - ' . $botError->getMessage());
                // Kirim default reply sebagai fallback
                $botSenderId = $room->admin_id ?? 1;
                Chat::create([
                    'room_id' => $room->id,
                    'sender_id' => $botSenderId,
                    'receiver_id' => $room->user_id,
                    'cabang_id' => $room->cabang_id,
                    'message' => $this->getDefaultReply($request->message),
                    'is_read' => false,
                    'is_bot' => true,
                ]);
            }

            return response()->json(['success' => true, 'chat' => $chat->load('sender')]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Chat sendMessage error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function adminSendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'room_id' => 'required|exists:chat_rooms,id',
        ]);

        $room = ChatRoom::findOrFail($request->room_id);

        $chat = Chat::create([
            'room_id' => $room->id,
            'sender_id' => auth()->id(),
            'receiver_id' => $room->user_id,
            'cabang_id' => $room->cabang_id,
            'message' => $request->message,
            'is_read' => false,
            'is_bot' => false,
        ]);

        $room->update(['last_message_at' => now()]);
        return response()->json(['success' => true, 'chat' => $chat->load('sender')]);
    }

    public function adminRooms()
    {
        $cabangId = auth()->user()->getActiveCabangId();

        $rooms = ChatRoom::with(['user', 'admin', 'cabang'])
            ->where('cabang_id', $cabangId)
            ->orderBy('last_message_at', 'desc')
            ->get();

        $rooms->each(function ($room) {
            $room->unread = $room->unreadCount(auth()->id());
            $room->last_message = Chat::where('room_id', $room->id)
                ->orderBy('created_at', 'desc')->first();
        });

        return response()->json($rooms);
    }

    public function adminGetMessages($roomId)
    {
        $room = ChatRoom::findOrFail($roomId);

        Chat::where('room_id', $roomId)
            ->where('sender_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Chat::where('room_id', $roomId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['room' => $room->load(['user', 'cabang']), 'messages' => $messages]);
    }

    public function adminUnreadCount()
    {
        $cabangId = auth()->user()->getActiveCabangId();
        $count = Chat::where('cabang_id', $cabangId)
            ->where('sender_id', '!=', auth()->id())
            ->where('is_read', false)
            ->count();
        return response()->json(['count' => $count]);
    }

    // ==================== BOT ENGINE ====================

    /**
     * Main bot reply — route ke provider yang dipilih
     */
    private function sendBotReply(ChatRoom $room, string $userMessage): void
    {
        $provider = Setting::get('bot_provider', 'default');
        $systemPrompt = Setting::get('bot_system_prompt', $this->defaultSystemPrompt());

        // Ambil chat history
        $chatHistory = Chat::where('room_id', $room->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->reverse()
            ->values();

        $reply = null;

        switch ($provider) {
            case 'gemini':
                $reply = $this->askGemini($userMessage, $systemPrompt, $chatHistory, $room);
                break;
            case 'groq':
                $reply = $this->askGroq($userMessage, $systemPrompt, $chatHistory, $room);
                break;
            case 'openai':
                $reply = $this->askOpenAI($userMessage, $systemPrompt, $chatHistory, $room);
                break;
            default:
                $reply = $this->getDefaultReply($userMessage);
                break;
        }

        // Fallback kalau semua gagal
        if (empty($reply)) {
            $reply = $this->getDefaultReply($userMessage);
        }

        // Pastikan bot sender valid
        $botSenderId = $room->admin_id
            ?? User::where('is_super_admin', true)->value('id')
            ?? User::whereHas('role', fn($q) => $q->where('name', 'Admin'))->value('id')
            ?? User::first()?->id
            ?? 1;

        Chat::create([
            'room_id' => $room->id,
            'sender_id' => $botSenderId,
            'receiver_id' => $room->user_id,
            'cabang_id' => $room->cabang_id,
            'message' => $reply,
            'is_read' => false,
            'is_bot' => true,
        ]);
    }

    // ==================== GOOGLE GEMINI (GRATIS) ====================

    private function askGemini(string $userMessage, string $systemPrompt, $chatHistory, $room): ?string
    {
        $apiKey = Setting::get('gemini_api_key');

        if (empty($apiKey) || strlen(trim($apiKey)) < 10) {
            Log::warning('ChatBot Gemini: API Key kosong');
            return $this->getDefaultReply($userMessage);
        }

        try {
            // Build contents untuk Gemini format
            $contents = [];

            // System instruction dikirim sebagai user message pertama
            $systemContext = $systemPrompt . "\n\nCATATAN: Kamu adalah bot customer service. Jawab SINGKAT, maksimal 3-4 kalimat.";

            // Tambah history
            foreach ($chatHistory as $msg) {
                $role = $msg->sender_id === $room->user_id ? 'user' : 'model';
                $contents[] = ['role' => $role, 'parts' => [['text' => $msg->message]]];
            }

            // Tambah pesan baru dengan system context
            $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

            $models = ['gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-1.5-flash-8b'];
            $reply = null;

            foreach ($models as $model) {
                try {
                    Log::info("ChatBot Gemini: Mencoba model {$model}");

                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                    ])->timeout(30)->connectTimeout(10)->post(
                        "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                        [
                            'system_instruction' => ['parts' => [['text' => $systemContext]]],
                            'contents' => $contents,
                            'generationConfig' => [
                                'maxOutputTokens' => 500,
                                'temperature' => 0.7,
                            ],
                        ]
                    );

                    if ($response->successful()) {
                        $data = $response->json();
                        $candidates = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                        if ($candidates) {
                            Log::info("ChatBot Gemini: Berhasil model {$model}");
                            return $candidates;
                        }
                    } else {
                        $status = $response->status();
                        $error = $response->body();
                        Log::warning("ChatBot Gemini: Gagal model {$model} - HTTP {$status} - {$error}");

                        if ($status === 400 || $status === 403) {
                            // Key salah atau quota exceeded
                            break;
                        }
                        continue;
                    }
                } catch (\Exception $e) {
                    Log::warning("ChatBot Gemini: Error model {$model} - " . $e->getMessage());
                    continue;
                }
            }

            return $reply;
        } catch (\Exception $e) {
            Log::error('ChatBot Gemini: Exception - ' . $e->getMessage());
            return null;
        }
    }

    // ==================== GROQ (GRATIS - SUPER CEPAT) ====================

    private function askGroq(string $userMessage, string $systemPrompt, $chatHistory, $room): ?string
    {
        $apiKey = Setting::get('groq_api_key');

        if (empty($apiKey) || strlen(trim($apiKey)) < 10) {
            Log::warning('ChatBot Groq: API Key kosong');
            return $this->getDefaultReply($userMessage);
        }

        try {
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt . "\n\nCATATAN: Jawab SINGKAT, maksimal 3-4 kalimat dalam bahasa Indonesia."],
            ];

            foreach ($chatHistory as $msg) {
                $role = $msg->sender_id === $room->user_id ? 'user' : 'assistant';
                $messages[] = ['role' => $role, 'content' => $msg->message];
            }

            $messages[] = ['role' => 'user', 'content' => $userMessage];

            $models = ['llama-3.3-70b-versatile', 'llama-3.1-8b-instant', 'mixtral-8x7b-32768'];
            $reply = null;

            foreach ($models as $model) {
                try {
                    Log::info("ChatBot Groq: Mencoba model {$model}");

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])->timeout(20)->connectTimeout(8)->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $model,
                        'messages' => $messages,
                        'max_tokens' => 500,
                        'temperature' => 0.7,
                    ]);

                    if ($response->successful()) {
                        $reply = $response->json('choices.0.message.content');
                        if ($reply) {
                            Log::info("ChatBot Groq: Berhasil model {$model}");
                            return $reply;
                        }
                    } else {
                        $status = $response->status();
                        Log::warning("ChatBot Groq: Gagal model {$model} - HTTP {$status}");

                        if ($status === 401) break; // Key salah
                        continue;
                    }
                } catch (\Exception $e) {
                    Log::warning("ChatBot Groq: Error model {$model} - " . $e->getMessage());
                    continue;
                }
            }

            return $reply;
        } catch (\Exception $e) {
            Log::error('ChatBot Groq: Exception - ' . $e->getMessage());
            return null;
        }
    }

    // ==================== OPENAI (BERBAYAR) ====================

    private function askOpenAI(string $userMessage, string $systemPrompt, $chatHistory, $room): ?string
    {
        $apiKey = Setting::get('openai_api_key');

        if (empty($apiKey) || strlen(trim($apiKey)) < 10) {
            Log::warning('ChatBot OpenAI: API Key kosong');
            return $this->getDefaultReply($userMessage);
        }

        try {
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt . "\n\nCATATAN: Jawab SINGKAT, maksimal 3-4 kalimat."],
            ];

            foreach ($chatHistory as $msg) {
                $role = $msg->sender_id === $room->user_id ? 'user' : 'assistant';
                $messages[] = ['role' => $role, 'content' => $msg->message];
            }

            $messages[] = ['role' => 'user', 'content' => $userMessage];

            $models = ['gpt-4o-mini', 'gpt-3.5-turbo', 'gpt-4o'];
            $reply = null;

            foreach ($models as $model) {
                try {
                    Log::info("ChatBot OpenAI: Mencoba model {$model}");

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])->timeout(30)->connectTimeout(10)->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $model,
                        'messages' => $messages,
                        'max_tokens' => 500,
                        'temperature' => 0.7,
                    ]);

                    if ($response->successful()) {
                        $reply = $response->json('choices.0.message.content');
                        if ($reply) {
                            Log::info("ChatBot OpenAI: Berhasil model {$model}");
                            return $reply;
                        }
                    } else {
                        $status = $response->status();
                        Log::warning("ChatBot OpenAI: Gagal model {$model} - HTTP {$status}");
                        if ($status === 401) break;
                        continue;
                    }
                } catch (\Exception $e) {
                    Log::warning("ChatBot OpenAI: Error model {$model} - " . $e->getMessage());
                    continue;
                }
            }

            return $reply;
        } catch (\Exception $e) {
            Log::error('ChatBot OpenAI: Exception - ' . $e->getMessage());
            return null;
        }
    }

    // ==================== DEFAULT KEYWORD BOT ====================

    private function getDefaultReply(string $message): string
    {
        $msg = strtolower($message);

        if (str_contains($msg, 'harga') || str_contains($msg, 'biaya') || str_contains($msg, 'berapa')) {
            return "Halo! Terima kasih sudah menghubungi FIXPRO 🙏\n\nUntuk estimasi biaya servis, silakan sebutkan:\n• Merk & tipe HP Anda\n• Keluhan/kerusakan yang dialami\n\nNanti kami akan berikan estimasi biayanya ya! 😊";
        }
        if (str_contains($msg, 'lcd') || str_contains($msg, 'layar') || str_contains($msg, 'retak') || str_contains($msg, 'pecah')) {
            return "Untuk servis LCD/layar, estimasi biaya tergantung merk dan tipe HP:\n• LCD iPhone: Rp 350.000 - Rp 1.500.000\n• LCD Android: Rp 150.000 - Rp 800.000\n\nSilakan datang ke toko kami untuk pengecekan gratis! 🔧";
        }
        if (str_contains($msg, 'baterai') || str_contains($msg, 'charge') || str_contains($msg, 'daya')) {
            return "Untuk ganti baterai HP:\n• Baterai iPhone: Rp 250.000 - Rp 600.000\n• Baterai Android: Rp 100.000 - Rp 400.000\n\nGaransi baterai 30 hari. Silakan kunjungi toko kami! 🔋";
        }
        if (str_contains($msg, 'charging') || str_contains($msg, 'cas') || str_contains($msg, 'port')) {
            return "Untuk servis port charging:\n• Port iPhone: Rp 200.000 - Rp 500.000\n• Port Android: Rp 100.000 - Rp 350.000\n\nBisa langsung datang ke toko, proses 30-60 menit! ⚡";
        }
        if (str_contains($msg, 'jam') || str_contains($msg, 'buka') || str_contains($msg, 'operasional') || str_contains($msg, 'tutup')) {
            return "Jam operasional FIXPRO:\n• Senin - Sabtu: 09:00 - 21:00\n• Minggu: 10:00 - 18:00\n\nSilakan datang sesuai jam operasional ya! 😊";
        }
        if (str_contains($msg, 'lokasi') || str_contains($msg, 'alamat') || str_contains($msg, 'dimana') || str_contains($msg, 'maps')) {
            return "Silakan kunjungi toko FIXPRO terdekat! Untuk info alamat lengkap, cek menu Dashboard atau langsung datang ke toko kami.\n\nAda yang bisa kami bantu lagi? 😊";
        }
        if (str_contains($msg, 'garansi') || str_contains($msg, 'jaminan')) {
            return "Semua servis di FIXPRO mendapat garansi:\n• Garansi servis: 30 hari\n• Garansi sparepart: 30-90 hari (tergantung jenis)\n\nKalau ada masalah dalam masa garansi, gratis! 🛡️";
        }
        if (str_contains($msg, 'lama') || str_contains($msg, 'proses') || str_contains($msg, 'selesai') || str_contains($msg, 'ambil')) {
            return "Estimasi waktu servis:\n• Ganti LCD/Baterai: 30-60 menit\n• Servis motherboard: 1-3 hari\n• Ganti konektor: 1-2 jam\n\nTergantung tingkat kerusakan dan ketersediaan sparepart. ⏱️";
        }
        if (str_contains($msg, 'mati') || str_contains($msg, 'hidup') || str_contains($msg, 'nyala') || str_contains($msg, 'dead')) {
            return "HP mati total? Bisa disebabkan oleh:\n• Baterai rusak/habis\n• IC power rusak\n• Masalah motherboard\n\nSilakan bawa ke toko untuk diagnosa GRATIS! 🔍";
        }
        if (str_contains($msg, 'camera') || str_contains($msg, 'kamera') || str_contains($msg, 'foto')) {
            return "Untuk servis kamera:\n• Kamera belakang: Rp 150.000 - Rp 800.000\n• Kamera depan: Rp 100.000 - Rp 400.000\n\nTergantung merk dan tipe HP. Diagnosa gratis di toko! 📸";
        }
        if (str_contains($msg, 'speaker') || str_contains($msg, 'suara') || str_contains($msg, 'mic') || str_contains($msg, 'mikrofon')) {
            return "Untuk servis speaker/mic:\n• Speaker: Rp 100.000 - Rp 400.000\n• Microphone: Rp 100.000 - Rp 350.000\n\nProses cepat, biasanya 30-60 menit! 🎵";
        }
        if (str_contains($msg, 'software') || str_contains($msg, 'reset') || str_contains($msg, 'install') || str_contains($msg, 'flash') || str_contains($msg, 'hp jelek')) {
            return "Untuk servis software:\n• Reset/flash HP: Rp 50.000 - Rp 150.000\n• Install ulang: Rp 100.000 - Rp 200.000\n• Bypass akun: tergantung tipe\n\nPastikan backup data dulu ya! 💾";
        }
        if (str_contains($msg, 'terima kasih') || str_contains($msg, 'makasih') || str_contains($msg, 'thanks')) {
            return "Sama-sama! 😊 Terima kasih sudah menghubungi FIXPRO. Kalau ada pertanyaan lagi, jangan ragu untuk chat kami ya! 🙏";
        }

        return "Halo! Terima kasih sudah menghubungi FIXPRO 🔧\n\nSaya asisten virtual FIXPRO. Silakan ceritakan keluhan HP Anda!\n\nKamu bisa tanya:\n• 💰 Harga servis\n• 📱 LCD, baterai, charging\n• ⏱️ Lama proses\n• 📍 Lokasi toko\n• 🛡️ Garansi\n\nAtau langsung daftar servis di menu \"Daftar Servis HP\" 😊";
    }

    // ==================== TEST BOT ====================

    /**
     * Test koneksi bot dari halaman settings
     */
    public function testBot()
    {
        $provider = Setting::get('bot_provider', 'default');

        if ($provider === 'default') {
            return response()->json([
                'success' => true,
                'message' => '✅ Mode Default aktif — Bot keyword-based (tanpa API, gratis selamanya)',
                'reply' => $this->getDefaultReply('halo'),
            ]);
        }

        if ($provider === 'gemini') {
            return $this->testGemini();
        }

        if ($provider === 'groq') {
            return $this->testGroq();
        }

        if ($provider === 'openai') {
            return $this->testOpenAI();
        }

        return response()->json(['success' => false, 'message' => 'Provider tidak dikenal: ' . $provider]);
    }

    private function testGemini()
    {
        $apiKey = Setting::get('gemini_api_key');

        if (empty($apiKey) || strlen(trim($apiKey)) < 10) {
            return response()->json([
                'success' => false,
                'message' => '❌ Gemini API Key kosong. Isi dulu key-nya, lalu klik Simpan, baru Test lagi.',
            ]);
        }

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(15)->connectTimeout(5)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => 'Halo, tes koneksi. Balas dengan singkat dalam bahasa Indonesia.']]]],
                    'generationConfig' => ['maxOutputTokens' => 50],
                ]);

            if ($response->successful()) {
                $reply = $response->json('candidates.0.content.parts.0.text') ?? 'No reply';
                return response()->json([
                    'success' => true,
                    'message' => '✅ Google Gemini berhasil! (Gratis)',
                    'reply' => $reply,
                ]);
            } else {
                $status = $response->status();
                $body = $response->body();
                $errorMsg = $response->json('error.message') ?? $body;
                return response()->json([
                    'success' => false,
                    'message' => "❌ Gemini gagal (HTTP {$status}): {$errorMsg}",
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ Gagal konek ke Gemini: ' . $e->getMessage(),
            ]);
        }
    }

    private function testGroq()
    {
        $apiKey = Setting::get('groq_api_key');

        if (empty($apiKey) || strlen(trim($apiKey)) < 10) {
            return response()->json([
                'success' => false,
                'message' => '❌ Groq API Key kosong. Isi dulu key-nya, lalu klik Simpan, baru Test lagi.',
            ]);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->connectTimeout(5)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.3-70b-versatile',
                'messages' => [['role' => 'user', 'content' => 'Halo, tes koneksi. Balas singkat.']],
                'max_tokens' => 50,
            ]);

            if ($response->successful()) {
                $reply = $response->json('choices.0.message.content') ?? 'No reply';
                return response()->json([
                    'success' => true,
                    'message' => '✅ Groq berhasil! (Gratis, super cepat)',
                    'reply' => $reply,
                ]);
            } else {
                $status = $response->status();
                $errorMsg = $response->json('error.message') ?? $response->body();
                return response()->json([
                    'success' => false,
                    'message' => "❌ Groq gagal (HTTP {$status}): {$errorMsg}",
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ Gagal konek ke Groq: ' . $e->getMessage(),
            ]);
        }
    }

    private function testOpenAI()
    {
        $apiKey = Setting::get('openai_api_key');

        if (empty($apiKey) || strlen(trim($apiKey)) < 10) {
            return response()->json([
                'success' => false,
                'message' => '❌ OpenAI API Key kosong. Isi dulu key-nya.',
            ]);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->connectTimeout(5)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => 'Halo, tes koneksi. Balas singkat.']],
                'max_tokens' => 50,
            ]);

            if ($response->successful()) {
                $reply = $response->json('choices.0.message.content') ?? 'No reply';
                return response()->json([
                    'success' => true,
                    'message' => '✅ OpenAI berhasil! (Berbayar)',
                    'reply' => $reply,
                ]);
            } else {
                $status = $response->status();
                $errorMsg = $response->json('error.message') ?? $response->body();
                return response()->json([
                    'success' => false,
                    'message' => "❌ OpenAI gagal (HTTP {$status}): {$errorMsg}",
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ Gagal konek ke OpenAI: ' . $e->getMessage(),
            ]);
        }
    }

    private function defaultSystemPrompt(): string
    {
        return 'Kamu adalah asisten AI customer service dari FIXPRO, layanan service HP profesional. Jawab dengan ramah dan singkat dalam bahasa Indonesia. Tanyakan keluhan HP pelanggan, berikan estimasi biaya kasar, dan sarankan untuk datang ke toko.';
    }
}
