<?php

namespace App\Http\Controllers\API\VCF;

use App\Http\Controllers\Controller;
use App\Models\Vcf;
use App\Models\VcfSegelMasuk;
use App\Models\VcfNomorSegelMasuk;
use App\Models\VcfSegelKeluar;
use App\Models\VcfNomorSegelKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

class VcfSegelController extends Controller
{
    /**
     * Simpan Segel Masuk — untuk Unloading, diinput saat WB Masuk (Bagian 2).
     * Bisa dipanggil bersamaan dengan Bagian 2, atau secara terpisah via endpoint ini.
     */
    public function storeMasuk(Request $request, $vcfId)
    {
        $vcfId = (int) $vcfId;
        $vcf = Vcf::findOrFail($vcfId);

        // Boleh diupdate selama VCF belum selesai/ditolak
        if (in_array($vcf->status, ['selesai', 'reject'])) {
            return response()->json([
                'message' => 'Segel masuk tidak dapat diubah karena VCF sudah ' . $vcf->status,
            ], 422);
        }

        $validated = $request->validate([
            'jumlah_segel' => 'required|integer|min:1|max:100',
            'nomor_segel'  => 'required|array|min:1',
            'nomor_segel.*' => 'required|string|max:100',
            'keterangan'   => 'nullable|string|max:1000',
        ]);

        if (count($validated['nomor_segel']) !== (int) $validated['jumlah_segel']) {
            return response()->json([
                'message' => 'Jumlah nomor segel harus sama dengan jumlah_segel.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Hapus segel lama
            $vcf->segelMasuk()->each(fn($s) => $s->nomorSegel()->delete());
            $vcf->segelMasuk()->delete();

            $segel = VcfSegelMasuk::create([
                'vcf_id'      => $vcf->id,
                'jumlah_segel' => $validated['jumlah_segel'],
                'petugas_id'  => $request->user()->id,
                'keterangan'  => $validated['keterangan'] ?? null,
            ]);

            foreach ($validated['nomor_segel'] as $urutan => $nomor) {
                VcfNomorSegelMasuk::create([
                    'segel_masuk_id' => $segel->id,
                    'urutan'         => $urutan + 1,
                    'nomor_segel'    => $nomor,
                ]);
            }

            ActivityLogger::vcfStageDone($vcf, 'Segel Masuk (Unloading)');
            DB::commit();

            return response()->json([
                'message' => 'Segel masuk berhasil disimpan.',
                'data'    => $segel->load('nomorSegel'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan segel masuk.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Simpan Segel Keluar — untuk Loading, diinput saat MG Keluar (Bagian 4).
     * Bisa dipanggil bersamaan dengan Bagian 4, atau secara terpisah via endpoint ini.
     */
    public function storeKeluar(Request $request, $vcfId)
    {
        $vcfId = (int) $vcfId;
        $vcf = Vcf::findOrFail($vcfId);

        // Boleh diupdate selama VCF belum selesai/ditolak
        if (in_array($vcf->status, ['reject'])) {
            return response()->json([
                'message' => 'Segel keluar tidak dapat diubah karena VCF sudah ditolak.',
            ], 422);
        }

        $validated = $request->validate([
            'jumlah_segel' => 'required|integer|min:1|max:100',
            'nomor_segel'  => 'required|array|min:1',
            'nomor_segel.*' => 'required|string|max:100',
            'keterangan'   => 'nullable|string|max:1000',
        ]);

        if (count($validated['nomor_segel']) !== (int) $validated['jumlah_segel']) {
            return response()->json([
                'message' => 'Jumlah nomor segel harus sama dengan jumlah_segel.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Hapus segel lama
            $vcf->segelKeluar()->each(fn($s) => $s->nomorSegel()->delete());
            $vcf->segelKeluar()->delete();

            $segel = VcfSegelKeluar::create([
                'vcf_id'      => $vcf->id,
                'jumlah_segel' => $validated['jumlah_segel'],
                'petugas_id'  => $request->user()->id,
                'keterangan'  => $validated['keterangan'] ?? null,
            ]);

            foreach ($validated['nomor_segel'] as $urutan => $nomor) {
                VcfNomorSegelKeluar::create([
                    'segel_keluar_id' => $segel->id,
                    'urutan'          => $urutan + 1,
                    'nomor_segel'     => $nomor,
                ]);
            }

            ActivityLogger::vcfStageDone($vcf, 'Segel Keluar (Loading)');
            DB::commit();

            return response()->json([
                'message' => 'Segel keluar berhasil disimpan.',
                'data'    => $segel->load('nomorSegel'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan segel keluar.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
