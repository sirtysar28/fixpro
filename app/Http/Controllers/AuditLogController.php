<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    private function checkSuperAdmin()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang bisa mengakses halaman ini.');
        }
    }

    public function index(Request $request)
    {
        $this->checkSuperAdmin();

        $query = AuditLog::with('user');

        // Filter: module
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        // Filter: action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter: user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter: date from
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filter: date to
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter: search description
        if ($request->filled('search')) {
            $query->where('description', 'like', "%{$request->search}%");
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        // Stats for summary cards
        $today = now()->format('Y-m-d');
        $statsToday = AuditLog::whereDate('created_at', $today)->count();
        $statsWeek = AuditLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $statsMonth = AuditLog::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $statsTotal = AuditLog::count();

        // Activity by module (top modules)
        $moduleStats = AuditLog::selectRaw('module, COUNT(*) as total')
            ->whereDate('created_at', $today)
            ->groupBy('module')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        // Top active users today
        $userStats = AuditLog::selectRaw('user_id, COUNT(*) as total')
            ->whereDate('created_at', $today)
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $users = User::orderBy('name')->get();

        return view('audit-log.index', compact(
            'logs', 'users', 'statsToday', 'statsWeek', 'statsMonth', 'statsTotal',
            'moduleStats', 'userStats'
        ));
    }

    /**
     * Detail single log
     */
    public function show(AuditLog $audit_log)
    {
        $this->checkSuperAdmin();

        $audit_log->load('user');

        return response()->json([
            'id' => $audit_log->id,
            'user' => $audit_log->user?->name ?? 'System',
            'email' => $audit_log->user?->email ?? '-',
            'module' => $audit_log->getModuleLabel(),
            'action' => $audit_log->getActionLabel(),
            'description' => $audit_log->description,
            'model_type' => $audit_log->model_type,
            'model_id' => $audit_log->model_id,
            'old_values' => $audit_log->old_values,
            'new_values' => $audit_log->new_values,
            'ip_address' => $audit_log->ip_address,
            'user_agent' => $audit_log->user_agent,
            'cabang_id' => $audit_log->cabang_id,
            'created_at' => $audit_log->created_at->format('d/m/Y H:i:s'),
        ]);
    }

    /**
     * Clear old logs (older than X days)
     */
    public function clear(Request $request)
    {
        $this->checkSuperAdmin();

        $days = $request->input('days', 90);
        $days = max(30, (int) $days); // Minimum 30 hari

        $count = AuditLog::where('created_at', '<', now()->subDays($days))->count();
        AuditLog::where('created_at', '<', now()->subDays($days))->delete();

        return redirect()->route('audit-log.index')
            ->with('success', "Berhasil menghapus {$count} log aktivitas yang lebih dari {$days} hari.");
    }

    /**
     * Export audit log ke CSV
     */
    public function exportCsv(Request $request)
    {
        $this->checkSuperAdmin();

        $query = AuditLog::with('user');

        if ($request->filled('module')) $query->where('module', $request->module);
        if ($request->filled('action')) $query->where('action', $request->action);
        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('search')) $query->where('description', 'like', "%{$request->search}%");

        $logs = $query->orderBy('created_at', 'desc')->get();

        $filename = 'audit_log_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['No', 'Waktu', 'User', 'Email', 'Modul', 'Aksi', 'Deskripsi', 'IP Address']);

            foreach ($logs as $i => $log) {
                fputcsv($file, [
                    $i + 1,
                    $log->created_at->format('d/m/Y H:i:s'),
                    $log->user?->name ?? 'System',
                    $log->user?->email ?? '-',
                    $log->getModuleLabel(),
                    $log->getActionLabel(),
                    $log->description,
                    $log->ip_address,
                ]);
            }
            fclose($file);
        };

        AuditLogService::custom('audit_log', 'export', 'Export Audit Log ke CSV (' . $logs->count() . ' records)');

        return response()->stream($callback, 200, $headers);
    }
}
