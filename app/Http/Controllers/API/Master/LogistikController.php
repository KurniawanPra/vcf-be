<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\Logistik;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LogistikController extends Controller
{
    private $messageFail = 'Something went wrong';
    private $messageAll = 'Success to Fetch All Datas';

    public function index(Request $request)
    {
        try {
            $query = Logistik::query();

            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('nama', 'like', '%' . $request->search . '%')
                      ->orWhere('nama_logistik', 'like', '%' . $request->search . '%')
                      ->orWhere('kode', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $data = $query->orderBy('nama_logistik', 'asc')
                          ->orderBy('nama', 'asc')
                          ->get();

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
                'nama_logistik' => 'required_without:nama|nullable|string|max:100',
                'nama'          => 'required_without:nama_logistik|nullable|string|max:100',
                'kode'          => 'nullable|string|max:20|unique:logistiks,kode',
                'is_active'     => 'boolean',
            ]);

            if (empty($validated['nama_logistik']) && !empty($validated['nama'])) {
                $validated['nama_logistik'] = $validated['nama'];
            }
            if (empty($validated['nama']) && !empty($validated['nama_logistik'])) {
                $validated['nama'] = $validated['nama_logistik'];
            }

            if (empty($validated['kode'])) {
                do {
                    $randomSuffix = strtoupper(bin2hex(random_bytes(3)));
                    $kode = 'LOG-' . $randomSuffix;
                } while (Logistik::where('kode', $kode)->exists());
                $validated['kode'] = $kode;
            }

            $item = Logistik::create($validated);
            DB::commit();
            return response()->json([
                'message' => 'Logistik berhasil ditambahkan.',
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

    public function show(Logistik $logistik)
    {
        return response()->json($logistik);
    }

    public function update(Request $request, Logistik $logistik)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'nama_logistik' => 'sometimes|required_without:nama|string|max:100',
                'nama'          => 'sometimes|required_without:nama_logistik|string|max:100',
                'kode'          => 'sometimes|nullable|string|max:20|unique:logistiks,kode,' . $logistik->id,
                'is_active'     => 'boolean',
            ]);

            if (isset($validated['nama_logistik']) && !isset($validated['nama'])) {
                $validated['nama'] = $validated['nama_logistik'];
            } elseif (isset($validated['nama']) && !isset($validated['nama_logistik'])) {
                $validated['nama_logistik'] = $validated['nama'];
            }

            $logistik->update($validated);
            DB::commit();
            return response()->json([
                'message' => 'Logistik berhasil diperbarui.',
                'data'    => $logistik,
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

    public function destroy(Logistik $logistik)
    {
        try {
            $logistik->delete();
            return response()->json(['message' => 'Logistik berhasil dihapus secara permanen.']);
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
