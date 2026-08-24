<?php

namespace App\Http\Controllers\API\VCF;

use App\Http\Controllers\Controller;
use App\Models\Vcf;
use App\Models\VcfPemeriksaanMasuk;
use App\Models\VcfBebanTambahanMasuk;
use App\Models\VcfSegelMasuk;
use App\Models\VcfNomorSegelMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

class VcfBagian2Controller extends Controller
{
    /**
     * Status VCF yang masih boleh diedit oleh petugas (sebelum finalisasi).
     */
    private const EDITABLE_STATUSES = [
        'bagian2_selesai',
        'loading_unloading_proses',
        'loading_unloading_selesai',
        'bagian3_selesai',
        'weighbridge_keluar',
    ];

    /**
     * Simpan Bagian 2 — Pemeriksaan Weighbridge Masuk.
     * VCF harus berstatus 'bagian1_selesai'.
     */
    public function store(Request $request, $vcfId)
    {
        $vcfId = (int) $vcfId;
        $vcf = Vcf::findOrFail($vcfId);

        if ($vcf->driver && $vcf->driver->status === 'blacklist') {
            return response()->json([
                'message' => 'Driver ini dalam status BLACKLIST. Transaksi VCF tidak dapat dilanjutkan.',
            ], 422);
        }

        if ($vcf->status !== 'bagian1_selesai') {
            return response()->json([
                'message' => 'Bagian 2 hanya dapat diisi setelah Bagian 1 selesai.',
                'status_saat_ini' => $vcf->status,
            ], 422);
        }

        $validated = $request->validate([
            // Item pemeriksaan (from master)
            'pemeriksaan' => 'required|array',
            'pemeriksaan.*.item_id' => 'required|exists:item_pemeriksaan_masuks,id',
            'pemeriksaan.*.nilai' => 'required|string|max:100',
            'pemeriksaan.*.keterangan' => 'nullable|string',

            // Beban tambahan (jika ada)
            'beban_tambahan_ada' => 'required|boolean',
            'jenis_beban' => 'required_if:beban_tambahan_ada,true|nullable|string|max:255',

            // Keterangan umum (opsional)
            'keterangan' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Simpan hasil pemeriksaan
            foreach ($validated['pemeriksaan'] as $item) {
                VcfPemeriksaanMasuk::create([
                    'vcf_id' => $vcf->id,
                    'item_id' => $item['item_id'],
                    'nilai' => $item['nilai'],
                    'keterangan' => $item['keterangan'] ?? null,
                    'petugas_id' => $request->user()->id,
                    'waktu_input' => now(),
                ]);
            }


            // Simpan beban tambahan jika ada
            if ($validated['beban_tambahan_ada']) {
                VcfBebanTambahanMasuk::create([
                    'vcf_id' => $vcf->id,
                    'jenis_beban' => $validated['jenis_beban'],
                ]);
            }

            // Update or create segel_masuk for saving the keterangan from Weighbridge Masuk,
            // but DO NOT touch the jumlah_segel or nomor_segel (unless creating for the first time).
            $segelMasuk = VcfSegelMasuk::where('vcf_id', $vcf->id)->first();
            if ($segelMasuk) {
                $segelMasuk->update([
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);
            } else {
                VcfSegelMasuk::create([
                    'vcf_id' => $vcf->id,
                    'jumlah_segel' => 0,
                    'petugas_id' => $request->user()->id,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);
            }

            // Update status VCF
            $vcf->update(['status' => 'bagian2_selesai']);

            ActivityLogger::vcfStageDone($vcf, 'Weighbridge Masuk (Bagian 2)');

            DB::commit();

            return response()->json([
                'message' => 'VCF Bagian 2 berhasil disimpan.',
                'data' => $this->loadBagian2($vcf->id),
            ]);
        } catch (\Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                DB::rollBack();
                throw $e;
            }
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan Bagian 2.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject VCF at Bagian 2.
     */
    public function reject(Request $request, $vcfId)
    {
        $vcfId = (int) $vcfId;
        $vcf = Vcf::findOrFail($vcfId);

        if ($vcf->status !== 'bagian1_selesai') {
            return response()->json([
                'message' => 'Hanya VCF yang baru selesai Bagian 1 yang dapat di-reject di tahap ini.',
            ], 422);
        }

        $validated = $request->validate([
            'catatan_reject' => 'required|string|max:500',
        ]);

        $vcf->update([
            'status' => 'reject',
            'catatan' => trim(($vcf->catatan ?? '') . "\n[REJECTED AT WB MASUK]: " . $validated['catatan_reject']),
            'keterangan' => trim(($vcf->keterangan ?? '') . "\n[REJECTED AT WB MASUK]: " . $validated['catatan_reject'])
        ]);

        ActivityLogger::vcfRejected($vcf, $validated['catatan_reject'], 'Weighbridge Masuk');

        return response()->json([
            'message' => 'VCF berhasil di-reject.',
            'data' => [
                'vcf_id' => $vcf->id,
                'status' => $vcf->status,
            ],
        ]);
    }

    /**
     * Update Bagian 2 — hanya jika status 'bagian2_selesai' atau user adalah admin.
     */
    public function update(Request $request, $vcfId)
    {
        $vcfId = (int) $vcfId;
        $vcf = Vcf::findOrFail($vcfId);

        if ($vcf->driver && $vcf->driver->status === 'blacklist') {
            return response()->json([
                'message' => 'Driver ini dalam status BLACKLIST. Transaksi VCF tidak dapat dilanjutkan.',
            ], 422);
        }

        // Only admin can edit VCF at any stage. Petugas cannot edit if status is selesai/reject.
        if (in_array($vcf->status, ['selesai', 'reject']) && !$this->isAdmin()) {
            return response()->json([
                'message' => 'Bagian 2 tidak dapat diedit karena VCF sudah final/ditolak. Hanya admin yang dapat mengedit.',
            ], 422);
        }

        // Non-admin users can only edit if status is in editable statuses
        if (!$this->isAdmin() && !in_array($vcf->status, self::EDITABLE_STATUSES)) {
            return response()->json([
                'message' => 'Bagian 2 tidak dapat diedit. Status VCF: ' . $vcf->status,
            ], 422);
        }

        $validated = $request->validate([
            'pemeriksaan' => 'sometimes|array',
            'pemeriksaan.*.item_id' => 'required|exists:item_pemeriksaan_masuks,id',
            'pemeriksaan.*.nilai' => 'required|string|max:100',
            'pemeriksaan.*.keterangan' => 'nullable|string',

            'beban_tambahan_ada' => 'sometimes|boolean',
            'jenis_beban' => 'nullable|string|max:255',

            'keterangan' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['pemeriksaan'])) {
                $vcf->pemeriksaanMasuk()->delete();
                foreach ($validated['pemeriksaan'] as $item) {
                    VcfPemeriksaanMasuk::create([
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
                $vcf->bebanTambahanMasuk()->delete();
                if ($validated['beban_tambahan_ada']) {
                    VcfBebanTambahanMasuk::create([
                        'vcf_id' => $vcf->id,
                        'jenis_beban' => $validated['jenis_beban'],
                    ]);
                }
            }

            if (array_key_exists('keterangan', $validated)) {
                $segelMasuk = VcfSegelMasuk::where('vcf_id', $vcf->id)->first();
                if ($segelMasuk) {
                    $segelMasuk->update([
                        'keterangan' => $validated['keterangan'] ?? null,
                    ]);
                } else {
                    VcfSegelMasuk::create([
                        'vcf_id' => $vcf->id,
                        'jumlah_segel' => 0,
                        'petugas_id' => $request->user()->id,
                        'keterangan' => $validated['keterangan'] ?? null,
                    ]);
                }
            }

            DB::commit();

            $changes = [];
            if (isset($validated['pemeriksaan'])) $changes['pemeriksaan_masuk'] = 'Pemeriksaan checklist diperbarui';
            if (isset($validated['beban_tambahan_ada'])) $changes['beban_tambahan_masuk'] = $validated['beban_tambahan_ada'] ? 'Ada (' . ($validated['jenis_beban'] ?? '') . ')' : 'Tidak ada';
            if (isset($validated['keterangan'])) $changes['keterangan_segel_masuk'] = $validated['keterangan'] ?? 'Kosong';

            ActivityLogger::vcfUpdated($vcf, $changes);

            return response()->json([
                'message' => 'VCF Bagian 2 berhasil diperbarui.',
                'data' => $this->loadBagian2($vcf->id),
            ]);
        } catch (\Throwable $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                DB::rollBack();
                throw $e;
            }
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui Bagian 2.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tampilkan data Bagian 2 saja.
     */
    public function show($vcfId)
    {
        $vcfId = (int) $vcfId;
        Vcf::findOrFail($vcfId);
        return response()->json($this->loadBagian2($vcfId));
    }

    private function loadBagian2($vcfId): array
    {
        $vcfId = (int) $vcfId;
        $vcf = Vcf::with([
            'pemeriksaanMasuk.item',
            'pemeriksaanMasuk.petugas:id,nama',
            'bebanTambahanMasuk',
            'segelMasuk.nomorSegel',
            'segelMasuk.petugas:id,nama',
        ])->findOrFail($vcfId);

        return [
            'vcf_id' => $vcf->id,
            'status' => $vcf->status,
            'pemeriksaan' => $vcf->pemeriksaanMasuk,
            'beban_tambahan' => $vcf->bebanTambahanMasuk,
            'segel' => $vcf->segelMasuk,
        ];
    }
}
