<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Timbangan;
use App\Models\Vcf;
use Illuminate\Http\Request;

class TimbanganController extends Controller
{
    /**
     * Store timbangan data during registration (bruto_from, tara_from)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vcf_id' => 'required|exists:vcfs,id',
            'bruto_from' => 'nullable|numeric|min:0',
            'tara_from' => 'nullable|numeric|min:0',
        ]);

        // If a timbangan already exists for this vcf_id, update it instead of creating a new one
        $timbangan = Timbangan::where('vcf_id', $validated['vcf_id'])->first();
        if ($timbangan) {
            $timbangan->update($validated);
        } else {
            $timbangan = Timbangan::create($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data timbangan berhasil disimpan',
            'data' => $timbangan,
        ], 201);
    }

    /**
     * Get timbangan by VCF ID
     *
     * @param  int  $vcfId
     * @return \Illuminate\Http\Response
     */
    public function show($vcfId)
    {
        $timbangan = Timbangan::where('vcf_id', $vcfId)->first();

        if (!$timbangan) {
            return response()->json([
                'success' => false,
                'message' => 'Data timbangan tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $timbangan,
        ]);
    }

    /**
     * Update bruto (WB Masuk / WB Keluar depending on flow)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $vcfId
     * @return \Illuminate\Http\Response
     */
    public function updateBruto(Request $request, $vcfId)
    {
        $validated = $request->validate([
            'bruto' => 'required|numeric|min:0',
        ]);

        $vcf = Vcf::find($vcfId);
        if (!$vcf) {
            return response()->json([
                'success' => false,
                'message' => 'Data VCF tidak ditemukan',
            ], 404);
        }

        if (in_array($vcf->status, ['selesai', 'reject'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat melakukan timbang karena status VCF sudah ' . $vcf->status,
            ], 422);
        }

        $timbangan = Timbangan::where('vcf_id', $vcfId)->first();
        if (!$timbangan) {
            $timbangan = Timbangan::create(['vcf_id' => $vcfId]);
        }

        $isLoading = strpos($vcf->tipe_kegiatan, 'loading') === 0;

        // Validation based on Loading/Unloading flow
        if ($isLoading) {
            // Loading: bruto is input at WB Keluar
            // WB Masuk (tara) must be completed first
            if ($timbangan->tara === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'WB Keluar (input bruto) tidak boleh dilakukan sebelum WB Masuk (input tara) selesai.',
                ], 422);
            }
            if (in_array($vcf->status, ['bagian1_selesai'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'WB Keluar tidak boleh dilakukan sebelum WB Masuk selesai.',
                ], 422);
            }
        } else {
            // Unloading: bruto is input at WB Masuk
            if (!in_array($vcf->status, ['bagian1_selesai', 'bagian2_selesai'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Input bruto hanya diperbolehkan saat WB Masuk untuk proses unloading.',
                ], 422);
            }
        }

        // Field already filled check
        if ($timbangan->bruto !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Kolom bruto sudah terisi dan tidak boleh diinput ulang.',
            ], 422);
        }

        $bruto = $validated['bruto'];
        $tara = $timbangan->tara;
        $netto = null;

        if ($tara !== null) {
            if ($bruto <= $tara) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bruto wajib lebih besar dari tara (tara saat ini: ' . $tara . ').',
                ], 422);
            }
            $netto = $bruto - $tara;
        }

        $timbangan->update([
            'bruto' => $bruto,
            'netto' => $netto,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bruto berhasil diperbarui',
            'data' => $timbangan,
        ]);
    }

    /**
     * Update tara (WB Masuk / WB Keluar depending on flow)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $vcfId
     * @return \Illuminate\Http\Response
     */
    public function updateTara(Request $request, $vcfId)
    {
        $validated = $request->validate([
            'tara' => 'required|numeric|min:0',
        ]);

        $vcf = Vcf::find($vcfId);
        if (!$vcf) {
            return response()->json([
                'success' => false,
                'message' => 'Data VCF tidak ditemukan',
            ], 404);
        }

        if (in_array($vcf->status, ['selesai', 'reject'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat melakukan timbang karena status VCF sudah ' . $vcf->status,
            ], 422);
        }

        $timbangan = Timbangan::where('vcf_id', $vcfId)->first();
        if (!$timbangan) {
            $timbangan = Timbangan::create(['vcf_id' => $vcfId]);
        }

        $isLoading = strpos($vcf->tipe_kegiatan, 'loading') === 0;

        // Validation based on Loading/Unloading flow
        if ($isLoading) {
            // Loading: tara is input at WB Masuk
            if (!in_array($vcf->status, ['bagian1_selesai', 'bagian2_selesai'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Input tara hanya diperbolehkan saat WB Masuk untuk proses loading.',
                ], 422);
            }
        } else {
            // Unloading: tara is input at WB Keluar
            // WB Masuk (bruto) must be completed first
            if ($timbangan->bruto === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'WB Keluar (input tara) tidak boleh dilakukan sebelum WB Masuk (input bruto) selesai.',
                ], 422);
            }
            if (in_array($vcf->status, ['bagian1_selesai'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'WB Keluar tidak boleh dilakukan sebelum WB Masuk selesai.',
                ], 422);
            }
        }

        // Field already filled check
        if ($timbangan->tara !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Kolom tara sudah terisi dan tidak boleh diinput ulang.',
            ], 422);
        }

        $tara = $validated['tara'];
        $bruto = $timbangan->bruto;
        $netto = null;

        if ($bruto !== null) {
            if ($bruto <= $tara) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bruto wajib lebih besar dari tara (bruto saat ini: ' . $bruto . ').',
                ], 422);
            }
            $netto = $bruto - $tara;
        }

        $timbangan->update([
            'tara' => $tara,
            'netto' => $netto,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tara berhasil diperbarui',
            'data' => $timbangan,
        ]);
    }
    /**
     * Override all timbangan fields by Admin
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $vcfId
     * @return \Illuminate\Http\Response
     */
    public function updateAdmin(Request $request, $vcfId)
    {
        $validated = $request->validate([
            'bruto_from' => 'nullable|numeric|min:0',
            'tara_from' => 'nullable|numeric|min:0',
            'bruto' => 'nullable|numeric|min:0',
            'tara' => 'nullable|numeric|min:0',
        ]);

        $vcf = Vcf::find($vcfId);
        if (!$vcf) {
            return response()->json([
                'success' => false,
                'message' => 'Data VCF tidak ditemukan',
            ], 404);
        }

        $timbangan = Timbangan::where('vcf_id', $vcfId)->first();
        if (!$timbangan) {
            $timbangan = Timbangan::create(['vcf_id' => $vcfId]);
        }

        $bruto = $validated['bruto'] ?? null;
        $tara = $validated['tara'] ?? null;
        $netto = null;

        if ($bruto !== null && $tara !== null) {
            if ($bruto < $tara) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bruto tidak boleh lebih kecil dari tara.',
                ], 422);
            }
            $netto = $bruto - $tara;
        }

        $timbangan->update([
            'bruto_from' => $validated['bruto_from'] ?? null,
            'tara_from' => $validated['tara_from'] ?? null,
            'bruto' => $bruto,
            'tara' => $tara,
            'netto' => $netto,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data timbangan berhasil diubah oleh Admin',
            'data' => $timbangan,
        ]);
    }
}
