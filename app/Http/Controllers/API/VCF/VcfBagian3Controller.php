<?php

namespace App\Http\Controllers\API\VCF;

use App\Http\Controllers\Controller;
use App\Models\Vcf;
use App\Models\VcfPemeriksaanKeluar;
use App\Models\VcfBebanTambahanKeluar;
use App\Models\VcfSegelKeluar;
use App\Models\VcfNomorSegelKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

class VcfBagian3Controller extends Controller
{
    /**
     * Status VCF yang masih boleh diedit oleh petugas (sebelum finalisasi).
     */
    private const EDITABLE_STATUSES = [
        'bagian3_selesai',
        'weighbridge_keluar',
    ];

    /**
     * Simpan Bagian 3 — Pemeriksaan Weighbridge Keluar.
     * VCF harus berstatus 'bagian2_selesai'.
     */
    public function store(Request $request, int $vcfId)
    {
        $vcf = Vcf::findOrFail($vcfId);

        if (!in_array($vcf->status, ['bagian2_selesai', 'loading_unloading_selesai'])) {
            return response()->json([
                'message' => 'Bagian 3 hanya dapat diisi setelah proses Loading/Unloading atau Timbangan Masuk selesai.',
                'status_saat_ini' => $vcf->status,
            ], 422);
        }

        $validated = $request->validate([
            'pemeriksaan' => 'required|array',
            'pemeriksaan.*.item_id' => 'required|exists:item_pemeriksaan_keluars,id',
            'pemeriksaan.*.nilai' => 'required|string|max:100',
            'pemeriksaan.*.keterangan' => 'nullable|string',

            'beban_tambahan_ada' => 'required|boolean',
            'jenis_beban' => 'required_if:beban_tambahan_ada,true|nullable|string|max:255',

            'segel_terpasang' => 'required|boolean',
            'jumlah_segel' => 'nullable|integer|min:0|max:100',
            'nomor_segel' => 'nullable|array',
            'nomor_segel.*' => 'nullable|string|max:100',

            'keterangan' => 'nullable|string|max:1000',
        ]);

        if ($validated['segel_terpasang'] && !empty($validated['nomor_segel'])) {
            $filteredNomorSegel = array_filter($validated['nomor_segel']);
            if (count($filteredNomorSegel) > 0 && count($filteredNomorSegel) !== (int) $validated['jumlah_segel']) {
                return response()->json([
                    'message' => 'Jumlah nomor segel harus sama dengan jumlah segel yang diinput.',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($validated['pemeriksaan'] as $item) {
                VcfPemeriksaanKeluar::create([
                    'vcf_id' => $vcf->id,
                    'item_id' => $item['item_id'],
                    'nilai' => $item['nilai'],
                    'keterangan' => $item['keterangan'] ?? null,
                    'petugas_id' => $request->user()->id,
                    'waktu_input' => now(),
                ]);
            }

            if ($validated['beban_tambahan_ada']) {
                VcfBebanTambahanKeluar::create([
                    'vcf_id' => $vcf->id,
                    'jenis_beban' => $validated['jenis_beban'],
                ]);
            }

            // Hapus segel lama jika ada
            $vcf->segelKeluar()->each(fn($s) => $s->nomorSegel()->delete());
            $vcf->segelKeluar()->delete();

            // Simpan segel (selalu buat record untuk menyimpan keterangan umum)
            if ($validated['segel_terpasang']) {
                $segel = VcfSegelKeluar::create([
                    'vcf_id' => $vcf->id,
                    'jumlah_segel' => $validated['jumlah_segel'] ?? count($validated['nomor_segel'] ?? []),
                    'petugas_id' => $request->user()->id,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);

                if (!empty($validated['nomor_segel'])) {
                    foreach ($validated['nomor_segel'] as $urutan => $nomor) {
                        if (!empty($nomor)) {
                            VcfNomorSegelKeluar::create([
                                'segel_keluar_id' => $segel->id,
                                'urutan' => $urutan + 1,
                                'nomor_segel' => $nomor,
                            ]);
                        }
                    }
                }
            } else {
                // Jika segel tidak terpasang, tetap buat record untuk menyimpan keterangan umum
                $segel = VcfSegelKeluar::create([
                    'vcf_id' => $vcf->id,
                    'jumlah_segel' => 0,
                    'petugas_id' => $request->user()->id,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);
            }

            $vcf->update(['status' => 'bagian3_selesai']);

            ActivityLogger::vcfStageDone($vcf, 'Weighbridge Keluar (Bagian 3)');

            DB::commit();

            return response()->json([
                'message' => 'VCF Bagian 3 berhasil disimpan.',
                'data' => $this->loadBagian3($vcf->id),
            ]);
        } catch (\Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                DB::rollBack();
                throw $e;
            }
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan Bagian 3.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject VCF at Bagian 3.
     */
    public function reject(Request $request, int $vcfId)
    {
        $vcf = Vcf::findOrFail($vcfId);

        if (!in_array($vcf->status, ['bagian2_selesai', 'loading_unloading_selesai'])) {
            return response()->json([
                'message' => 'Hanya VCF yang sudah melewati tahap 2 atau Loading/Unloading yang dapat di-reject di tahap ini.',
            ], 422);
        }

        $validated = $request->validate([
            'catatan_reject' => 'required|string|max:500',
        ]);

        $vcf->update([
            'status' => 'reject',
            'keterangan' => trim(($vcf->keterangan ?? '') . "\n[REJECTED AT WB KELUAR]: " . $validated['catatan_reject'])
        ]);

        ActivityLogger::vcfRejected($vcf, $validated['catatan_reject'], 'Weighbridge Keluar');

        return response()->json([
            'message' => 'VCF berhasil di-reject.',
            'data' => [
                'vcf_id' => $vcf->id,
                'status' => $vcf->status,
            ],
        ]);
    }

    /**
     * Update Bagian 3 — hanya jika status 'bagian3_selesai' atau user adalah admin.
     */
    public function update(Request $request, int $vcfId)
    {
        $vcf = Vcf::findOrFail($vcfId);

        // Only admin can edit VCF at any stage. Petugas cannot edit if status is selesai/reject.
        if (in_array($vcf->status, ['selesai', 'reject']) && !$this->isAdmin()) {
            return response()->json([
                'message' => 'Bagian 3 tidak dapat diedit karena VCF sudah final/ditolak. Hanya admin yang dapat mengedit.',
            ], 422);
        }

        // Non-admin users can only edit if status is in editable statuses
        if (!$this->isAdmin() && !in_array($vcf->status, self::EDITABLE_STATUSES)) {
            return response()->json([
                'message' => 'Bagian 3 tidak dapat diedit. Status VCF: ' . $vcf->status,
            ], 422);
        }

        $validated = $request->validate([
            'pemeriksaan' => 'sometimes|array',
            'pemeriksaan.*.item_id' => 'required|exists:item_pemeriksaan_keluars,id',
            'pemeriksaan.*.nilai' => 'required|string|max:100',
            'pemeriksaan.*.keterangan' => 'nullable|string',

            'beban_tambahan_ada' => 'sometimes|boolean',
            'jenis_beban' => 'nullable|string|max:255',

            'segel_terpasang' => 'sometimes|boolean',
            'jumlah_segel' => 'nullable|integer|min:0|max:100',
            'nomor_segel' => 'nullable|array',
            'nomor_segel.*' => 'nullable|string|max:100',

            'keterangan' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['pemeriksaan'])) {
                $vcf->pemeriksaanKeluar()->delete();
                foreach ($validated['pemeriksaan'] as $item) {
                    VcfPemeriksaanKeluar::create([
                        'vcf_id' => $vcf->id,
                        'item_id' => $item['item_id'],
                        'nilai' => $item['nilai'],
                        'keterangan' => $item['keterangan'] ?? null,
                        'petugas_id' => $request->user()->id,
                        'waktu_input' => now(),
                    ]);
                }
            }

            if (isset($validated['beban_tambahan_ada'])) {
                $vcf->bebanTambahanKeluar()->delete();
                if ($validated['beban_tambahan_ada']) {
                    VcfBebanTambahanKeluar::create([
                        'vcf_id' => $vcf->id,
                        'jenis_beban' => $validated['jenis_beban'],
                    ]);
                }
            }

            if (isset($validated['segel_terpasang'])) {
                $vcf->segelKeluar()->each(fn($s) => $s->nomorSegel()->delete());
                $vcf->segelKeluar()->delete();

                $segelTerpasang = $validated['segel_terpasang'] ?? false;
                if ($segelTerpasang) {
                    $filteredNomorSegel = array_filter($validated['nomor_segel'] ?? []);
                    if (count($filteredNomorSegel) > 0 && count($filteredNomorSegel) !== (int) ($validated['jumlah_segel'] ?? 0)) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Jumlah nomor segel tidak sesuai.',
                        ], 422);
                    }

                    $segel = VcfSegelKeluar::create([
                        'vcf_id' => $vcf->id,
                        'jumlah_segel' => $validated['jumlah_segel'] ?? count($validated['nomor_segel'] ?? []),
                        'petugas_id' => $request->user()->id,
                        'keterangan' => $validated['keterangan'] ?? null,
                    ]);

                    if (!empty($validated['nomor_segel'])) {
                        foreach ($validated['nomor_segel'] as $urutan => $nomor) {
                            if (!empty($nomor)) {
                                VcfNomorSegelKeluar::create([
                                    'segel_keluar_id' => $segel->id,
                                    'urutan' => $urutan + 1,
                                    'nomor_segel' => $nomor,
                                ]);
                            }
                        }
                    }
                } else {
                    // Jika segel tidak terpasang, tetap buat record untuk menyimpan keterangan umum
                    $segel = VcfSegelKeluar::create([
                        'vcf_id' => $vcf->id,
                        'jumlah_segel' => 0,
                        'petugas_id' => $request->user()->id,
                        'keterangan' => $validated['keterangan'] ?? null,
                    ]);
                }
            }

            DB::commit();

            ActivityLogger::vcfUpdated($vcf, []);

            return response()->json([
                'message' => 'VCF Bagian 3 berhasil diperbarui.',
                'data' => $this->loadBagian3($vcf->id),
            ]);
        } catch (\Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                DB::rollBack();
                throw $e;
            }
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui Bagian 3.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $vcfId)
    {
        Vcf::findOrFail($vcfId);
        return response()->json($this->loadBagian3($vcfId));
    }

    private function loadBagian3(int $vcfId): array
    {
        $vcf = Vcf::with([
            'pemeriksaanKeluar.item',
            'pemeriksaanKeluar.petugas:id,nama',
            'bebanTambahanKeluar',
            'segelKeluar.nomorSegel',
            'segelKeluar.petugas:id,nama',
        ])->findOrFail($vcfId);

        return [
            'vcf_id' => $vcf->id,
            'status' => $vcf->status,
            'pemeriksaan' => $vcf->pemeriksaanKeluar,
            'beban_tambahan' => $vcf->bebanTambahanKeluar,
            'segel' => $vcf->segelKeluar,
        ];
    }
}
