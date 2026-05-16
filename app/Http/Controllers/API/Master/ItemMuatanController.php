<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\ItemKelengkapanSupir;
use App\Models\ItemMuatan;
use App\Models\ItemPemeriksaanKeluar;
use App\Models\ItemPemeriksaanMasuk;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// ─────────────────────────────────────────────────────────────────────────────
// ItemMuatanController
// ─────────────────────────────────────────────────────────────────────────────
class ItemMuatanController extends Controller
{
    private $messageFail = 'Something went wrong';
    private $messageMissing = 'Data not found in record';
    private $messageAll = 'Success to Fetch All Datas';
    private $messageSuccess = 'Success to Fetch Data';
    private $messageCreate = 'Success to Create Data';
    private $messageUpdate = 'Success to Update Data';

    public function index(Request $request)
    {
        try {
            $query = ItemMuatan::query();

            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('nama_item', 'like', '%' . $request->search . '%')
                      ->orWhere('keterangan', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('jenis')) {
                if ($request->jenis === 'dibawa_all') {
                    $query->whereIn('jenis', ['dibawa', 'both']);
                } elseif ($request->jenis === 'diisi_all') {
                    $query->whereIn('jenis', ['diisi', 'both']);
                } else {
                    $query->where('jenis', $request->jenis);
                }
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            } 

            $data = $query->orderBy('urutan', 'asc')->get();

            return response()->json([
                'data' => $data,
                'message' => $this->messageAll
            ], 200);

        } catch (QueryException $e) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
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
                'nama_item'      => 'required|string|max:255',
                'jenis'          => 'nullable|string|max:100',
                'keterangan'     => 'nullable|string|max:255',
                'is_active'      => 'boolean',
            ]);

            // Auto-assign urutan
            $validated['urutan'] = ItemMuatan::max('urutan') + 1;
            // Default jenis if client doesn't send it (new UI hides it)
            $validated['jenis'] = $validated['jenis'] ?? 'dibawa';

            $driver = ItemMuatan::create($validated);
            DB::commit();
            return response()->json([
                'message' => 'Item berhasil ditambahkan.',
                'data'    => $driver,
            ], 201);
        } catch (\Exception $e) { if ($e instanceof \Illuminate\Validation\ValidationException) { DB::rollback(); throw $e; }
            DB::rollback();
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function show(ItemMuatan $itemMuatan)
    {
        return response()->json($itemMuatan);
    }

   public function update(Request $request, ItemMuatan $itemMuatan)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'nama_item'      => 'sometimes|required|string|max:255',
                'jenis'          => 'sometimes|nullable|string|max:100',
                'keterangan'     => 'nullable|string|max:255',
                'urutan'         => 'sometimes|required|integer',
                'is_active'      => 'boolean',
            ]);

            // If not provided, keep existing jenis (UI no longer edits this field)
            $itemMuatan->update($validated);
            DB::commit();
            return response()->json([
                'message' => 'Item berhasil diperbarui.',
                'data'    => $itemMuatan,
            ]);
        } catch (\Exception $e) { if ($e instanceof \Illuminate\Validation\ValidationException) { DB::rollback(); throw $e; }
            DB::rollback();
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function destroy(ItemMuatan $itemMuatan)
    {
        DB::beginTransaction();
        try {
            $itemMuatan->delete();
            
            // Resequence remaining items
            $items = ItemMuatan::orderBy('urutan', 'asc')->get();
            foreach ($items as $index => $item) {
                $item->update(['urutan' => $index + 1]);
            }

            DB::commit();
            return response()->json(['message' => 'Item berhasil dihapus dan urutan diperbarui.']);
        } catch (QueryException $e) {
            DB::rollback();
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Data tidak dapat dihapus karena memiliki keterkaitan dengan data VCF.',
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}

