<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Violation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

class ViolationController extends Controller
{
    /**
     * Cek riwayat pelanggaran berdasarkan driver_id dan/atau no_polisi.
     * Digunakan saat registrasi VCF untuk menampilkan warning.
     */
    public function check(Request $request)
    {
        $driverId  = $request->query('driver_id');
        $noPolisi  = $request->query('no_polisi');

        $result = [
            'driver'  => null,
            'vehicle' => null,
        ];

        if ($driverId) {
            $driver = Driver::select('id', 'nama_supir', 'no_sim', 'status')
                ->with(['violations' => function ($q) {
                    $q->select('id', 'driver_id', 'jenis_pelanggaran', 'keterangan', 'tanggal_pelanggaran')
                      ->orderByDesc('tanggal_pelanggaran')
                      ->limit(5);
                }])
                ->find($driverId);

            if ($driver) {
                $result['driver'] = [
                    'id'         => $driver->id,
                    'nama_supir' => $driver->nama_supir,
                    'no_sim'     => $driver->no_sim,
                    'status'     => $driver->status ?? 'normal',
                    'violations' => $driver->violations,
                ];
            }
        }

        if ($noPolisi) {
            $violations = Violation::select('id', 'no_polisi', 'jenis_pelanggaran', 'keterangan', 'tanggal_pelanggaran')
                ->where('no_polisi', strtoupper(trim($noPolisi)))
                ->whereNotNull('no_polisi')
                ->orderByDesc('tanggal_pelanggaran')
                ->limit(5)
                ->get();

            $result['vehicle'] = [
                'no_polisi'  => strtoupper(trim($noPolisi)),
                'violations' => $violations,
            ];
        }

        return response()->json(['data' => $result, 'success' => true]);
    }

    /**
     * List semua pelanggaran (admin).
     */
    public function index(Request $request)
    {
        try {
            $query = Violation::with(['driver:id,nama_supir,no_sim,status', 'createdBy:id,nama'])
                ->orderByDesc('tanggal_pelanggaran');

            if ($request->filled('driver_id')) {
                $query->where('driver_id', $request->driver_id);
            }
            if ($request->filled('no_polisi')) {
                $query->where('no_polisi', strtoupper($request->no_polisi));
            }
            if ($request->filled('search')) {
                $q2 = $request->search;
                $query->where(function ($q) use ($q2) {
                    $q->where('jenis_pelanggaran', 'like', '%' . $q2 . '%')
                      ->orWhere('keterangan', 'like', '%' . $q2 . '%')
                      ->orWhere('no_polisi', 'like', '%' . $q2 . '%')
                      ->orWhereHas('driver', fn($d) => $d->where('nama_supir', 'like', '%' . $q2 . '%'));
                });
            }
            if ($request->filled('date_from')) {
                $query->whereDate('tanggal_pelanggaran', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('tanggal_pelanggaran', '<=', $request->date_to);
            }
            if ($request->filled('driver_status')) {
                $query->whereHas('driver', fn($q) => $q->where('status', $request->driver_status));
            }

            $data = $query->paginate($request->get('per_page', 100));
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['errMsg' => $e->getMessage(), 'success' => false], 500);
        }
    }

    /**
     * Tambah pelanggaran baru (admin).
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'driver_id'           => 'nullable|exists:drivers,id',
                'no_polisi'           => 'nullable|string|max:20',
                'jenis_pelanggaran'   => 'required|string|max:255',
                'keterangan'          => 'nullable|string',
                'tanggal_pelanggaran' => 'required|date',
            ]);

            if (empty($validated['driver_id']) && empty($validated['no_polisi'])) {
                return response()->json([
                    'message' => 'Minimal driver_id atau no_polisi harus diisi.',
                    'success' => false,
                ], 422);
            }

            if (!empty($validated['no_polisi'])) {
                $validated['no_polisi'] = strtoupper(trim($validated['no_polisi']));
            }

            $violation = Violation::create(array_merge($validated, [
                'created_by' => $request->user()->id,
            ]));

            DB::commit();

            $targetLabel = $violation->driver?->nama_supir ?? $violation->no_polisi ?? 'N/A';
            ActivityLogger::violationCreated($violation, $targetLabel);

            return response()->json([
                'message' => 'Pelanggaran berhasil dicatat.',
                'data'    => $violation->load(['driver:id,nama_supir', 'createdBy:id,nama']),
                'success' => true,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Illuminate\Validation\ValidationException) throw $e;
            return response()->json(['errMsg' => $e->getMessage(), 'success' => false], 500);
        }
    }

    /**
     * Update pelanggaran (admin).
     */
    public function update(Request $request, Violation $violation)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'driver_id'           => 'sometimes|nullable|exists:drivers,id',
                'no_polisi'           => 'sometimes|nullable|string|max:20',
                'jenis_pelanggaran'   => 'sometimes|required|string|max:255',
                'keterangan'          => 'nullable|string',
                'tanggal_pelanggaran' => 'sometimes|required|date',
            ]);

            if (!empty($validated['no_polisi'])) {
                $validated['no_polisi'] = strtoupper(trim($validated['no_polisi']));
            }

            $violation->update($validated);
            DB::commit();
            return response()->json([
                'message' => 'Pelanggaran berhasil diperbarui.',
                'data'    => $violation->fresh(['driver:id,nama_supir', 'createdBy:id,nama']),
                'success' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Illuminate\Validation\ValidationException) throw $e;
            return response()->json(['errMsg' => $e->getMessage(), 'success' => false], 500);
        }
    }

    /**
     * Hapus pelanggaran (admin).
     */
    public function destroy(Violation $violation)
    {
        try {
            $targetLabel = $violation->driver?->nama_supir ?? $violation->no_polisi ?? 'N/A';
            $violation->delete();
            ActivityLogger::violationDeleted($targetLabel);
            return response()->json(['message' => 'Pelanggaran berhasil dihapus.', 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['errMsg' => $e->getMessage(), 'success' => false], 500);
        }
    }

    /**
     * Update status driver: normal / warning / blacklist (admin).
     */
    public function updateDriverStatus(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'status' => 'required|in:normal,warning,blacklist',
        ]);
        $driver->update(['status' => $validated['status']]);
        ActivityLogger::log(
            'master.driver.status_updated', 'master', 'updated', $driver,
            "Status driver diperbarui: {$driver->nama_supir} → {$validated['status']}",
            ['status' => $validated['status']],
            $driver->nama_supir
        );
        return response()->json([
            'message' => 'Status driver diperbarui.',
            'data'    => $driver->only(['id', 'nama_supir', 'status']),
            'success' => true,
        ]);
    }
}
