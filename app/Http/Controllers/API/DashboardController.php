<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Vcf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats()
    {
        try {
            $today = now()->toDateString();
            
            $stats = [
                'total'         => Vcf::count(),
                'today'         => Vcf::whereDate('tanggal', $today)->count(),
                'active'        => Vcf::whereNotIn('status', ['selesai', 'reject'])->count(),
                'system_speed'  => $this->getSystemSpeed(),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch dashboard stats'], 500);
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
