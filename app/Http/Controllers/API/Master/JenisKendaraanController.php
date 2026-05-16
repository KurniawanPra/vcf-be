<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\JenisKendaraan;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class JenisKendaraanController extends Controller
{
    private $messageFail = 'Something went wrong';
    private $messageAll = 'Success to Fetch All Datas';

    public function index(Request $request)
    {
        try {
            $query = JenisKendaraan::query();

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('nama', 'like', '%' . $request->search . '%')
                      ->orWhere('kode', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $data = $query->orderBy('urutan', 'asc')->get();

            return response()->json(['data' => $data, 'message' => $this->messageAll], 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $this->messageFail,
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'nama'      => 'required|string|max:100',
                'kode'      => 'required|string|max:20|unique:jenis_kendaraans,kode',
                'is_active' => 'boolean',
            ]);

            // Auto-assign urutan
            $validated['urutan'] = JenisKendaraan::max('urutan') + 1;

            $item = JenisKendaraan::create($validated);
            DB::commit();
            return response()->json([
                'message' => 'Jenis kendaraan berhasil ditambahkan.',
                'data'    => $item,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            if ($e instanceof \Illuminate\Validation\ValidationException) throw $e;
            return response()->json([
                'message' => $this->messageFail,
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function show(JenisKendaraan $jenisKendaraan)
    {
        return response()->json($jenisKendaraan);
    }

    public function update(Request $request, JenisKendaraan $jenisKendaraan)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'nama'      => 'sometimes|required|string|max:100',
                'kode'      => 'sometimes|required|string|max:20|unique:jenis_kendaraans,kode,' . $jenisKendaraan->id,
                'urutan'    => 'sometimes|required|integer',
                'is_active' => 'boolean',
            ]);

            $jenisKendaraan->update($validated);
            DB::commit();
            return response()->json([
                'message' => 'Jenis kendaraan berhasil diperbarui.',
                'data'    => $jenisKendaraan,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            if ($e instanceof \Illuminate\Validation\ValidationException) throw $e;
            return response()->json([
                'message' => $this->messageFail,
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function destroy(JenisKendaraan $jenisKendaraan)
    {
        DB::beginTransaction();
        try {
            $jenisKendaraan->delete();

            // Resequence
            $items = JenisKendaraan::orderBy('urutan', 'asc')->get();
            foreach ($items as $index => $item) {
                $item->update(['urutan' => $index + 1]);
            }

            DB::commit();
            return response()->json(['message' => 'Jenis kendaraan berhasil dihapus dan urutan diperbarui.']);
        } catch (QueryException $e) {
            DB::rollback();
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Data tidak dapat dihapus karena sedang digunakan dalam transaksi VCF.',
                ], 422);
            }
            throw $e;
        }
    }
}
