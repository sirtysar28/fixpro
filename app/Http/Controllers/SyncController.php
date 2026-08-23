<?php

namespace App\Http\Controllers;

use App\Models\SyncQueue;
use Illuminate\Http\Request;

/**
 * Fitur #7 — Mode Offline (Offline Sync) — Riwayat Sinkronisasi (Web Admin)
 *
 * Menampilkan riwayat proses sinkronisasi transaksi offline dari mobile app,
 * termasuk status (processed / failed / conflict), error message, dan statistik.
 */
class SyncController extends Controller
{
    public function index(Request $request)
    {
        $query = SyncQueue::with(['user', 'cabang']);

        // Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('client_ref', 'like', "%$s%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%$s%"));
            });
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $items = $query->orderByDesc('id')->paginate(25)->withQueryString();

        // Statistik ringkas
        $statsBase = SyncQueue::query();
        if ($request->filled('from')) $statsBase->whereDate('created_at', '>=', $request->from);
        if ($request->filled('to')) $statsBase->whereDate('created_at', '<=', $request->to);

        $stats = [
            'total'     => (clone $statsBase)->count(),
            'processed' => (clone $statsBase)->where('status', SyncQueue::STATUS_PROCESSED)->count(),
            'failed'    => (clone $statsBase)->where('status', SyncQueue::STATUS_FAILED)->count(),
            'conflict'  => (clone $statsBase)->where('status', SyncQueue::STATUS_CONFLICT)->count(),
            'last_sync' => (clone $statsBase)->whereNotNull('synced_at')->latest('synced_at')->first()?->synced_at,
        ];

        // Breakdown by entity type
        $byEntity = (clone $statsBase)
            ->selectRaw('entity_type, COUNT(*) as cnt')
            ->groupBy('entity_type')
            ->pluck('cnt', 'entity_type')
            ->toArray();

        $entityTypes = SyncQueue::select('entity_type')->distinct()->pluck('entity_type')->filter()->values();

        return view('sync.index', compact('items', 'stats', 'byEntity', 'entityTypes'));
    }

    /** Detail 1 entri sync (untuk debugging konflik/error) */
    public function show(SyncQueue $sync)
    {
        $sync->load(['user', 'cabang']);
        return view('sync.show', compact('sync'));
    }
}
