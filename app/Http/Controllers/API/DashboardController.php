<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Vcf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function getStats()
    {
        try {
            $stats = Cache::remember('dashboard_stats', 2, function () {
                $todayLocal = now()->timezone('Asia/Jakarta')->toDateString();
                $currentYearLocal = now()->timezone('Asia/Jakarta')->year;
                $currentMonthLocal = now()->timezone('Asia/Jakarta')->month;

                // Count overall totals directly from the vcfs table
                $dbStats = DB::selectOne("
                    SELECT
                        COUNT(*) as total_overall,
                        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as completed_overall,
                        SUM(CASE WHEN status = 'reject' THEN 1 ELSE 0 END) as reject_overall,
                        SUM(CASE WHEN status NOT IN ('selesai', 'reject') THEN 1 ELSE 0 END) as active_in_area,
                        SUM(CASE WHEN status IN ('bagian1_selesai', 'bagian2_selesai', 'bagian3_selesai', 'weighbridge_keluar') THEN 1 ELSE 0 END) as pending
                    FROM vcfs
                ");

                // Count daily events directly from vcfs table for exact sync with the chart
                $totalToday = DB::table('vcfs')
                    ->where('tanggal', $todayLocal)
                    ->count();

                $completedToday = DB::table('vcfs')
                    ->where('tanggal', $todayLocal)
                    ->where('status', 'selesai')
                    ->count();

                $rejectToday = DB::table('vcfs')
                    ->where('tanggal', $todayLocal)
                    ->where('status', 'reject')
                    ->count();

                // Count monthly events directly from vcfs table
                $totalMonth = DB::table('vcfs')
                    ->whereRaw("YEAR(tanggal) = ? AND MONTH(tanggal) = ?", [$currentYearLocal, $currentMonthLocal])
                    ->count();

                $completedMonth = DB::table('vcfs')
                    ->whereRaw("YEAR(tanggal) = ? AND MONTH(tanggal) = ? AND status = 'selesai'", [$currentYearLocal, $currentMonthLocal])
                    ->count();

                $rejectMonth = DB::table('vcfs')
                    ->whereRaw("YEAR(tanggal) = ? AND MONTH(tanggal) = ? AND status = 'reject'", [$currentYearLocal, $currentMonthLocal])
                    ->count();

                // Daily counts for the last 7 days (including today)
                $dailyCounts = DB::select("
                    SELECT tanggal as date, COUNT(*) as count
                    FROM vcfs
                    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                    GROUP BY tanggal
                    ORDER BY tanggal ASC
                ");

                // Establish baseline: Average daily VCF count over the last 30 days
                $avgDb = DB::selectOne("
                    SELECT AVG(daily_count) as avg_count
                    FROM (
                        SELECT tanggal, COUNT(*) as daily_count
                        FROM vcfs
                        WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
                        GROUP BY tanggal
                    ) as daily_totals
                ");
                
                $avgCount = (float) ($avgDb->avg_count ?? 10);
                if ($avgCount < 5) {
                    $avgCount = 10; // Default fallback for clean visualization
                }

                // Normal range limits (0.5x to 1.5x of the average count)
                $lowerBound = (int) max(1, round($avgCount * 0.4));
                $upperBound = (int) max(5, round($avgCount * 1.6));

                // Populate continuous last 7 days to handle potential missing days
                $weeklyStats = [];
                $dayLabels = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

                for ($i = 6; $i >= 0; $i--) {
                    $carbonDate = now()->subDays($i);
                    $dateStr = $carbonDate->toDateString();
                    $dayName = $dayLabels[$carbonDate->dayOfWeek];

                    // Find match in queried daily counts
                    $count = 0;
                    foreach ($dailyCounts as $row) {
                        if ($row->date === $dateStr) {
                            $count = (int) $row->count;
                            break;
                        }
                    }

                    // An anomaly occurs if transaction volume falls outside the normal corridor bounds
                    $isAnomaly = ($count > $upperBound || $count < $lowerBound);

                    $weeklyStats[] = [
                        'date'        => $dateStr,
                        'day'         => $dayName,
                        'count'       => $count,
                        'baseline'    => $avgCount,
                        'lower_bound' => $lowerBound,
                        'upper_bound' => $upperBound,
                        'is_anomaly'  => $isAnomaly
                    ];
                }

                $blacklistDrivers = \App\Models\Driver::where('status', 'blacklist')->count();

                return [
                    'total_overall'      => (int) ($dbStats->total_overall ?? 0),
                    'total_today'        => $totalToday,
                    'total_month'        => $totalMonth,
                    
                    'completed_overall'  => (int) ($dbStats->completed_overall ?? 0),
                    'completed_today'    => $completedToday,
                    'completed_month'    => $completedMonth,
                    
                    'reject_overall'     => (int) ($dbStats->reject_overall ?? 0),
                    'reject_today'       => $rejectToday,
                    'reject_month'       => $rejectMonth,
                    
                    'active_in_area'     => (int) ($dbStats->active_in_area ?? 0),
                    'pending'            => (int) ($dbStats->pending ?? 0),
                    'blacklist_drivers'  => (int) $blacklistDrivers,
                    'weekly_anomaly_stats'=> $weeklyStats,
                ];
            });

            // Dynamically calculate system speed on every request
            $stats['system_speed'] = $this->getSystemSpeed();

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch dashboard stats',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getMonthlyChartData(Request $request)
    {
        try {
            $year = (int) $request->get('year', now()->timezone('Asia/Jakarta')->year);
            $month = (int) $request->get('month', now()->timezone('Asia/Jakarta')->month);

            $data = DB::table('vcfs')
                ->selectRaw("
                    tanggal as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'reject' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status NOT IN ('selesai', 'reject') THEN 1 ELSE 0 END) as pending
                ")
                ->whereRaw("YEAR(tanggal) = ? AND MONTH(tanggal) = ?", [$year, $month])
                ->groupBy('tanggal')
                ->orderBy('date', 'asc')
                ->get();

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch monthly chart data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    private function getSystemSpeed()
    {
        try {
            $startTime = microtime(true);
            
            // Simple query to measure response time
            DB::select('SELECT 1');
            
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);
            
            return $responseTime;
        } catch (\Exception $e) {
            return 0;
        }
    }
}

