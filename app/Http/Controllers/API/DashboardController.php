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
            $stats = [
                'total'   => Vcf::count(),
                'selesai' => Vcf::where('status', 'selesai')->count(),
                'proses'  => Vcf::whereNotIn('status', ['selesai', 'reject'])->count(),
                'reject'  => Vcf::where('status', 'reject')->count(),
            ];

            $recentActivity = Vcf::with(['driver', 'transporter', 'logistik', 'produk'])
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($vcf) {
                    return [
                        'id' => $vcf->id,
                        'nomor_urut' => $vcf->nomor_urut,
                        'status' => $vcf->status,
                        'driver' => $vcf->driver->nama_supir ?? 'Unknown',
                        'updated_at' => $vcf->updated_at->diffForHumans(),
                    ];
                });

            return response()->json([
                'stats' => $stats,
                'recent_activity' => $recentActivity
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch dashboard stats'], 500);
        }
    }
}
