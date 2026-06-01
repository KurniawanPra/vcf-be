<?php

namespace App\Http\Controllers\API\VCF;

use App\Http\Controllers\Controller;
use App\Models\Vcf;
use App\Models\VcfKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

class VcfBagian4Controller extends Controller
{
    /**
     * Simpan Bagian 4 — Jam Keluar & Emergency Respon Kontak.
     * VCF harus berstatus 'bagian3_selesai'.
     */
    public function store(Request $request, int $vcfId)
    {
        $vcf = Vcf::findOrFail($vcfId);

        if ($vcf->driver && $vcf->driver->status === 'blacklist') {
            return response()->json([
                'message' => 'Driver ini dalam status BLACKLIST. Transaksi VCF tidak dapat dilanjutkan.',
            ], 422);
        }

        if (!in_array($vcf->status, ['bagian3_selesai', 'weighbridge_keluar'])) {
            return response()->json([
                'message' => 'Bagian 4 hanya dapat diisi setelah Bagian 3 selesai.',
                'status_saat_ini' => $vcf->status,
            ], 422);
        }

        if (str_starts_with($vcf->tipe_kegiatan, 'loading')) {
            $segel = $vcf->segelKeluar;
            if (!$segel || $segel->jumlah_segel === 0 || $segel->nomorSegel()->count() === 0) {
                return response()->json([
                    'message' => 'Segel keluar wajib diisi untuk kegiatan loading sebelum menyelesaikan Bagian 4.',
                ], 422);
            }
        }

        $validated = $request->validate([
            'jam_keluar'               => 'required|date_format:H:i',
            'emergency_respon_kontak'  => 'nullable|string|max:500',
            'keterangan'               => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $keluar = VcfKeluar::updateOrCreate(
                ['vcf_id' => $vcf->id],
                [
                    'jam_keluar'              => $validated['jam_keluar'],
                    'emergency_respon_kontak' => $validated['emergency_respon_kontak'] ?? null,
                    'keterangan'              => $validated['keterangan'] ?? null,
                    'petugas_id'              => $request->user()->id,
                    'waktu_input'             => now(),
                ]
            );

            $vcf->update(['status' => 'weighbridge_keluar']);

            ActivityLogger::vcfStageDone($vcf, 'Main Gate Keluar (Bagian 4)');

            DB::commit();

            return response()->json([
                'message' => 'VCF Bagian 4 berhasil disimpan. Menunggu keluar Main Gate.',
                'data'    => $keluar->load('petugas:id,nama'),
            ]);
        } catch (\Throwable $e) { if ($e instanceof \Illuminate\Validation\ValidationException) { DB::rollBack(); throw $e; }
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan Bagian 4.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalisasi VCF — Keluar Main Gate.
     */
    public function finalize(Request $request, int $vcfId)
    {
        $vcf = Vcf::findOrFail($vcfId);

        if ($vcf->driver && $vcf->driver->status === 'blacklist') {
            return response()->json([
                'message' => 'Driver ini dalam status BLACKLIST. Transaksi VCF tidak dapat dilanjutkan.',
            ], 422);
        }

        if ($vcf->status !== 'weighbridge_keluar') {
            return response()->json([
                'message' => 'VCF belum menyelesaikan timbangan keluar.',
            ], 422);
        }

        if (str_starts_with($vcf->tipe_kegiatan, 'loading')) {
            $segel = $vcf->segelKeluar;
            if (!$segel || $segel->jumlah_segel === 0 || $segel->nomorSegel()->count() === 0) {
                return response()->json([
                    'message' => 'Segel keluar wajib diisi untuk kegiatan loading sebelum konfirmasi keluar Main Gate.',
                ], 422);
            }
        }

        $vcf->update(['status' => 'selesai']);

        ActivityLogger::vcfFinalized($vcf);

        return response()->json([
            'message' => 'VCF telah selesai. Kendaraan keluar Main Gate.',
            'data'    => $vcf
        ]);
    }

    /**
     * Update Bagian 4 — hanya jika status 'selesai' dan ada kebutuhan koreksi.
     * Hanya admin yang bisa (dikontrol di route level).
     */
    public function update(Request $request, int $vcfId)
    {
        $vcf = Vcf::findOrFail($vcfId);

        if ($vcf->driver && $vcf->driver->status === 'blacklist') {
            return response()->json([
                'message' => 'Driver ini dalam status BLACKLIST. Transaksi VCF tidak dapat dilanjutkan.',
            ], 422);
        }

        // Petugas hanya boleh edit sebelum finalisasi (saat status masih weighbridge_keluar).
        // Admin tetap boleh koreksi sesuai kebutuhan.
        if (! $this->isAdmin()) {
            if (in_array($vcf->status, ['selesai', 'reject'])) {
                return response()->json([
                    'message' => 'Bagian 4 tidak dapat diedit karena VCF sudah final/ditolak. Status VCF: ' . $vcf->status,
                ], 422);
            }

            if ($vcf->status !== 'weighbridge_keluar') {
                return response()->json([
                    'message' => 'Bagian 4 hanya dapat diedit setelah disimpan dan sebelum konfirmasi keluar Main Gate. Status VCF: ' . $vcf->status,
                ], 422);
            }
        }

        $keluar = VcfKeluar::where('vcf_id', $vcfId)->firstOrFail();

        $validated = $request->validate([
            'jam_keluar'              => 'sometimes|required|date_format:H:i',
            'emergency_respon_kontak' => 'nullable|string|max:500',
            'keterangan'              => 'nullable|string',
        ]);

        $keluar->update($validated);

        return response()->json([
            'message' => 'VCF Bagian 4 berhasil diperbarui.',
            'data'    => $keluar->load('petugas:id,nama'),
        ]);
    }

    public function show(int $vcfId)
    {
        Vcf::findOrFail($vcfId);

        $keluar = VcfKeluar::with('petugas:id,nama')
            ->where('vcf_id', $vcfId)
            ->first();

        return response()->json([
            'vcf_id' => $vcfId,
            'data'   => $keluar,
        ]);
    }
}
