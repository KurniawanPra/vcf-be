<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Ambil daftar activity log dengan filter dan pagination.
     * Hanya admin yang bisa mengakses semua log.
     * Petugas hanya bisa melihat log milik mereka sendiri.
     */
    public function index(Request $request)
    {
        $user   = $request->user();
        $isAdmin = $user && $user->role === 'admin';

        $query = ActivityLog::query()->orderByDesc('created_at');

        // Petugas hanya melihat log sendiri
        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        }

        // ── Filter ──────────────────────────────────
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('subject_label', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%");
            });
        }

        // Filter tanggal
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        } elseif ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                $request->date_from . ' 00:00:00',
                $request->date_to . ' 23:59:59',
            ]);
        } else {
            // Default: 7 hari terakhir
            $query->where('created_at', '>=', now()->subDays(7));
        }

        $perPage = min((int) $request->get('per_page', 50), 200);
        $data = $query->paginate($perPage);

        return response()->json($data);
    }

    /**
     * Detail satu log entry.
     */
    public function show(int $id)
    {
        $log = ActivityLog::findOrFail($id);
        return response()->json($log);
    }

    /**
     * Stats ringkasan untuk widget di dashboard.
     */
    public function stats(Request $request)
    {
        $today   = now()->toDateString();
        $week    = now()->subDays(7);

        $totalToday    = ActivityLog::whereDate('created_at', $today)->count();
        $totalWeek     = ActivityLog::where('created_at', '>=', $week)->count();
        $vcfEvents     = ActivityLog::where('module', 'vcf')->whereDate('created_at', $today)->count();
        $authEvents    = ActivityLog::where('module', 'auth')->whereDate('created_at', $today)->count();
        $masterEvents  = ActivityLog::where('module', 'master')->whereDate('created_at', $today)->count();
        $rejectEvents  = ActivityLog::where('action', 'rejected')->whereDate('created_at', $today)->count();

        // Aktivitas per user hari ini
        $byUser = ActivityLog::whereDate('created_at', $today)
            ->selectRaw('user_name, user_role, COUNT(*) as count')
            ->groupBy('user_name', 'user_role')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Event terbanyak hari ini
        $byAction = ActivityLog::whereDate('created_at', $today)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'total_today'   => $totalToday,
            'total_week'    => $totalWeek,
            'vcf_today'     => $vcfEvents,
            'auth_today'    => $authEvents,
            'master_today'  => $masterEvents,
            'reject_today'  => $rejectEvents,
            'by_user'       => $byUser,
            'by_action'     => $byAction,
        ]);
    }
}
