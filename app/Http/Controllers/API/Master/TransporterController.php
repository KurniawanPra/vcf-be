<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\Transporter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class TransporterController extends Controller
{
    private $messageFail = 'Something went wrong';
    private $messageAll = 'Success to Fetch All Datas';

    public function index(Request $request)
    {
        try {
            $query = Transporter::query();

            if ($request->filled('search')) {
                $like = $this->likeOperator();
                $query->where(function ($q) use ($request, $like) {
                    $q->where('nama_transporter', $like, '%' . $request->search . '%')
                      ->orWhere('kode', $like, '%' . $request->search . '%');
                });
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $data = $query->orderBy('nama_transporter', 'asc')->get();

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
                'nama_transporter' => 'required|string|max:100',
                'kode' => 'required|string|max:20|unique:transporters,kode',
                'is_active' => 'boolean',
            ]);

            $item = Transporter::create($validated);
            DB::commit();
            return response()->json([
                'message' => 'Transporter berhasil ditambahkan.',
                'data' => $item,
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

    public function show(Transporter $transporter)
    {
        return response()->json($transporter->load('drivers'));
    }

    public function update(Request $request, Transporter $transporter)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'nama_transporter' => 'sometimes|required|string|max:100',
                'kode' => 'sometimes|required|string|max:20|unique:transporters,kode,' . $transporter->id,
                'is_active' => 'boolean',
            ]);

            $transporter->update($validated);
            DB::commit();
            return response()->json([
                'message' => 'Transporter berhasil diperbarui.',
                'data' => $transporter,
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

    public function destroy(Transporter $transporter)
    {
        try {
            $transporter->delete();
            return response()->json(['message' => 'Transporter berhasil dihapus secara permanen.']);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Data tidak dapat dihapus karena sedang digunakan dalam transaksi VCF.',
                ], 422);
            }
            throw $e;
        }
    }
}
