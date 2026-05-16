<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Driver::query();

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('nama_supir', 'like', '%' . $request->search . '%')
                      ->orWhere('no_sim', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $data = $query->orderBy('nama_supir', 'asc')->get();

            return response()->json(['data' => $data, 'message' => 'Success to Fetch All Datas'], 200);

        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'err'     => $e->getTrace()[0],
                'errMsg'  => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'nama_supir'      => 'required|string|max:255',
                'no_sim'          => 'required|string|max:50|unique:drivers,no_sim',
                'jenis_sim'       => 'required|string|max:20',
                'tgl_berlaku_sim' => 'required|date',
                'is_active'       => 'boolean',
            ]);

            $driver = Driver::create($validated);
            DB::commit();
            return response()->json([
                'message' => 'Supir berhasil ditambahkan.',
                'data'    => $driver,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            if ($e instanceof \Illuminate\Validation\ValidationException) throw $e;
            return response()->json([
                'message' => 'Something went wrong',
                'errMsg'  => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function show(Driver $driver)
    {
        return response()->json($driver);
    }

    public function update(Request $request, Driver $driver)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'nama_supir'      => 'sometimes|required|string|max:255',
                'no_sim'          => 'sometimes|required|string|max:50|unique:drivers,no_sim,' . $driver->id,
                'jenis_sim'       => 'sometimes|required|string|max:20',
                'tgl_berlaku_sim' => 'sometimes|required|date',
                'is_active'       => 'boolean',
            ]);

            $driver->update($validated);
            DB::commit();
            return response()->json([
                'message' => 'Supir berhasil diperbarui.',
                'data'    => $driver,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            if ($e instanceof \Illuminate\Validation\ValidationException) throw $e;
            return response()->json([
                'message' => 'Something went wrong',
                'errMsg'  => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function destroy(Driver $driver)
    {
        try {
            $driver->delete();
            return response()->json(['message' => 'Supir berhasil dihapus secara permanen.']);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Data tidak dapat dihapus karena sudah digunakan dalam transaksi VCF.',
                ], 422);
            }
            throw $e;
        }
    }
}
