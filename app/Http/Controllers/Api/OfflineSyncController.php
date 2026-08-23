<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncQueue;
use App\Services\OfflineSyncService;
use Illuminate\Http\Request;

/**
 * Fitur #11 — Mode Offline (Offline Sync)
 * Endpoint untuk mobile app:
 *  - POST /api/offline/sync        → batch push transaksi offline
 *  - GET  /api/offline/sync/status → cek status by client_ref
 *  - GET  /api/offline/last-sync   → timestamp sync terakhir
 *
 * Setiap entri WAJIB punya `client_ref` (UUID) yang di-generate client saat offline.
 * Idempotent: sync ulang dengan client_ref sama TIDAK duplikat.
 */
class OfflineSyncController extends Controller
{
    public function __construct(protected OfflineSyncService $sync)
    {
    }

    /** POST /api/offline/sync — batch push */
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'entries'             => 'required|array|max:100',
            'entries.*.client_ref'=> 'required|string|max:80',
            'entries.*.entity_type' => 'required|string|max:50',
            'entries.*.action'    => 'nullable|string|in:create,update,delete',
            'entries.*.payload'   => 'required|array',
            'entries.*.client_id' => 'nullable|string|max:80',
            'entries.*.client_created_at' => 'nullable|string',
            'entries.*.device_id' => 'nullable|string|max:80',
        ]);

        $user = $request->user();
        $results = [];
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($validated['entries'] as $entry) {
            try {
                $record = $this->sync->process($entry, $user);
                $results[] = [
                    'client_ref'  => $record->client_ref,
                    'client_id'   => $record->client_id,
                    'server_id'   => $record->server_id,
                    'status'      => $record->status,
                    'error'       => $record->error_message,
                    'idempotent'  => $record->synced_at?->diffInSeconds(now()) > 2, // sudah ada sebelumnya
                ];
                if ($record->status === SyncQueue::STATUS_PROCESSED) {
                    if ($record->synced_at && $record->updated_at && $record->synced_at->lt($record->updated_at->subSeconds(2))) {
                        $skipped++;
                    } else {
                        $processed++;
                    }
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $results[] = [
                    'client_ref' => $entry['client_ref'],
                    'status'     => SyncQueue::STATUS_FAILED,
                    'error'      => $e->getMessage(),
                ];
                $failed++;
            }
        }

        return response()->json([
            'success'   => true,
            'processed' => $processed, // transaksi baru diproses
            'skipped'   => $skipped,   // sudah ada (idempotent, return existing)
            'failed'    => $failed,    // gagal proses (stok konflik, dll)
            'total'     => count($validated['entries']),
            'results'   => $results,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    /** GET /api/offline/sync/status?client_ref=... — cek status 1 entri */
    public function status(Request $request)
    {
        $request->validate(['client_ref' => 'required|string|max:80']);
        $record = SyncQueue::where('client_ref', $request->client_ref)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$record) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'      => true,
            'client_ref' => $record->client_ref,
            'server_id'  => $record->server_id,
            'status'     => $record->status,
            'error'      => $record->error_message,
            'synced_at'  => $record->synced_at?->toIso8601String(),
        ]);
    }

    /** GET /api/offline/last-sync — sync terakhir user */
    public function lastSync(Request $request)
    {
        $last = SyncQueue::where('user_id', $request->user()->id)
            ->latest('synced_at')->first();

        return response()->json([
            'last_sync' => $last?->synced_at?->toIso8601String(),
            'total_synced' => SyncQueue::where('user_id', $request->user()->id)->count(),
        ]);
    }

    /** GET /api/offline/conflicts — daftar entri yang gagal/konflik (perlu diresolve user) */
    public function conflicts(Request $request)
    {
        $conflicts = SyncQueue::where('user_id', $request->user()->id)
            ->whereIn('status', [SyncQueue::STATUS_FAILED, SyncQueue::STATUS_CONFLICT])
            ->latest()->take(100)->get();

        return response()->json(['data' => $conflicts]);
    }
}
