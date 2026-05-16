<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\ItemPemeriksaanMasuk;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// ─────────────────────────────────────────────────────────────────────────────
// ItemPemeriksaanMasukController
// ─────────────────────────────────────────────────────────────────────────────
class ItemPemeriksaanMasukController extends Controller
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
            $query = ItemPemeriksaanMasuk::query();

            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('nama_item', 'like', '%' . $request->search . '%')
                      ->orWhere('kode', 'like', '%' . $request->search . '%');
                });
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
                'nama_item'          => 'required|string|max:255',
                'kode'               => 'nullable|string|max:50|unique:item_pemeriksaan_masuks,kode',
                'tipe_jawaban'       => 'required|string',
                'has_detail'         => 'boolean',
                'keterangan_detail'  => 'nullable|string|max:255',
                'is_active'          => 'boolean',
            ]);

            // Auto-assign urutan
            $validated['urutan'] = ItemPemeriksaanMasuk::max('urutan') + 1;

            // Ensure keterangan_detail exists and is null when has_detail is false
            if (!($validated['has_detail'] ?? false)) {
                $validated['keterangan_detail'] = null;
            } else {
                $validated['keterangan_detail'] = $validated['keterangan_detail'] ?? null;
            }

            $kode = $validated['kode'];
            if (!$kode) {
                $kode = strtoupper(str_replace(' ', '_', $validated['nama_item'])) . '_' . time();
            }

            $item = ItemPemeriksaanMasuk::create([
                'nama_item'         => $validated['nama_item'],
                'kode'              => $kode,
                'tipe_jawaban'      => $validated['tipe_jawaban'],
                'has_detail'        => $validated['has_detail'] ?? false,
                'keterangan_detail' => $validated['keterangan_detail'] ?? null,
                'urutan'            => $validated['urutan'],
                'is_active'         => $validated['is_active'] ?? true,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Item berhasil ditambahkan.',
                'data'    => $item,
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }

            return response()->json([
                'message' => $this->messageFail,
                'errMsg'  => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function show(ItemPemeriksaanMasuk $itemPemeriksaanMasuk)
    {
        return response()->json($itemPemeriksaanMasuk);
    }

    public function update(Request $request, ItemPemeriksaanMasuk $itemPemeriksaanMasuk)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'nama_item'         => 'sometimes|required|string|max:255',
                'kode'              => 'nullable|string|max:50|unique:item_pemeriksaan_masuks,kode,' . $itemPemeriksaanMasuk->id,
                'tipe_jawaban'      => 'sometimes|required|string',
                'has_detail'        => 'boolean',
                'keterangan_detail' => 'nullable|string|max:255',
                'urutan'            => 'sometimes|required|integer',
                'is_active'         => 'boolean',
            ]);

            // jika has_detail false atau tidak ada, kosongkan keterangan_detail
            if (!($validated['has_detail'] ?? false)) {
                $validated['keterangan_detail'] = null;
            } else {
                $validated['keterangan_detail'] = $validated['keterangan_detail'] ?? null;
            }

            $itemPemeriksaanMasuk->update($validated);

            DB::commit();

            return response()->json([
                'message' => 'Item berhasil diperbarui.',
                'data'    => $itemPemeriksaanMasuk,
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }

            return response()->json([
                'message' => $this->messageFail,
                'errMsg'  => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function destroy(ItemPemeriksaanMasuk $itemPemeriksaanMasuk)
    {
        DB::beginTransaction();
        try {
            $itemPemeriksaanMasuk->delete();
            
            // Resequence remaining items
            $items = ItemPemeriksaanMasuk::orderBy('urutan', 'asc')->get();
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

