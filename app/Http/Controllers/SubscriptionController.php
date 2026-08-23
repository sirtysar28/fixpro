<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Fitur Paket Berlangganan (3 bulan).
 *
 * Super Admin mengaktifkan paket untuk akun admin cabang. Aktivasi akan
 * mengisi / memperpanjang tanggal login_expires_at user dan mencatatnya di
 * tabel subscriptions. Sisa hari ditampilkan di pojok kanan atas + profil.
 */
class SubscriptionController extends Controller
{
    /** Daftar langganan (Super Admin: semua; lainnya: milik sendiri) */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Subscription::with(['user', 'cabang', 'activator']);

        if (!$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"))
                  ->orWhere('kode', 'like', "%$s%");
        }

        // Tandai langganan yang sudah lewat sebagai expired (lazy)
        Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->update(['status' => Subscription::STATUS_EXPIRED]);

        $subs = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $packages = Subscription::packages();

        // Target users (admin cabang) untuk dropdown aktivasi (Super Admin)
        $targetUsers = collect();
        if ($user->isSuperAdmin()) {
            $targetUsers = User::where('is_super_admin', false)
                ->whereHas('role', fn ($q) => $q->where('name', 'Admin'))
                ->orderBy('name')->get();
        }

        return view('subscription.index', compact('subs', 'packages', 'targetUsers'));
    }

    /** Form/Aktivasi paket berlangganan (Super Admin) */
    public function activate(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang bisa mengaktifkan paket berlangganan.');
        }

        $validated = $request->validate([
            'user_id'         => 'required|exists:users,id',
            'package'         => 'required|in:standar,enterprise',
            'duration_months' => 'required|integer|min:1|max:36',
            'amount'          => 'nullable|numeric|min:0',
            'extend'          => 'nullable',
            'note'            => 'nullable|string|max:500',
        ]);

        $target = User::findOrFail($validated['user_id']);
        $duration = (int) $validated['duration_months'];
        $packages = Subscription::packages();

        DB::beginTransaction();
        try {
            // Tentukan tanggal mulai & berakhir
            // extend = perpanjang dari sisa langganan/expire yang masih ada
            $base = now();
            if ($request->has('extend') && $target->login_expires_at && $target->login_expires_at->isFuture()) {
                $base = $target->login_expires_at;
            }

            $startedAt = now();
            $endsAt = $base->copy()->addMonths($duration);

            $sub = Subscription::create([
                'user_id'         => $target->id,
                'cabang_id'       => $target->cabang_id,
                'package'         => $validated['package'],
                'kode'            => Subscription::generateKode(),
                'duration_months' => $duration,
                'amount'          => $validated['amount'] ?? ($packages[$validated['package']]['price'] ?? 0),
                'started_at'      => $startedAt,
                'ends_at'         => $endsAt,
                'status'          => Subscription::STATUS_ACTIVE,
                'note'            => $validated['note'] ?? null,
                'activated_by'    => auth()->id(),
            ]);

            // Update user: perpanjang expiry, non-permanen, set paket
            $target->update([
                'login_expires_at' => $endsAt,
                'is_permanent'     => false,
                'is_active'        => true,
                'paket'            => $validated['package'],
            ]);

            AuditLogService::log('subscription', 'activate', "Aktivasi paket {$validated['package']} ({$duration} bln) untuk {$target->name} — berakhir {$endsAt->format('d/m/Y')}");

            DB::commit();

            return back()->with('success', "Paket " . ucfirst($validated['package']) . " ({$duration} bulan) diaktifkan untuk {$target->name}. Berakhir {$endsAt->translatedFormat('d F Y')}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal aktivasi: ' . $e->getMessage());
        }
    }

    /** Batalkan langganan */
    public function cancel(Subscription $subscription)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }
        $subscription->update(['status' => Subscription::STATUS_CANCELLED]);
        AuditLogService::log('subscription', 'cancel', "Batalkan langganan {$subscription->kode}");
        return back()->with('success', 'Langganan dibatalkan.');
    }
}
