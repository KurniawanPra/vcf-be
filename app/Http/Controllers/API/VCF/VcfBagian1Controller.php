<?php

namespace App\Http\Controllers\API\VCF;

use App\Http\Controllers\Controller;

use App\Models\Driver;
use App\Models\JenisKendaraan;
use App\Models\Transporter;
use App\Models\Vcf;
use App\Models\VcfKelengkapanSupir;
use App\Models\VcfMuatanDibawa;
use App\Models\VcfMuatanDiisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogger;

class VcfBagian1Controller extends Controller
{
    /**
     * Ambil nomor urut berikutnya.
     */
    public function getNextNumber(Request $request)
    {
        // Nomor urut reset bulanan (berdasarkan bulan dan tahun tanggal VCF).
        $dateInput = $request->filled('tanggal') ? $request->get('tanggal') : date('Y-m-d');
        try {
            $date = \Carbon\Carbon::parse($dateInput);
        } catch (\Exception $e) {
            $date = \Carbon\Carbon::now();
        }

        $startOfMonth = $date->copy()->startOfMonth()->toDateString();
        $endOfMonth = $date->copy()->endOfMonth()->toDateString();

        /** @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();
        $castType = $connection->getDriverName() === 'pgsql' ? 'INTEGER' : 'UNSIGNED';

        $maxNum = Vcf::whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->max(DB::raw("CAST(nomor_urut AS {$castType})"));

        $nextNumber = (int) $maxNum + 1;
        $next = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        return response()->json(['next_number' => $next]);
    }

    /**
     * List semua VCF dengan filter dan pagination.
     */
    public function index(Request $request)
    {
        // Relasi "ringan" untuk listing tabel (Arsip / Daftar / Operasional).
        // Relasi berat (segel + nomor segel, beban tambahan, timbangan) hanya
        // dimuat bila klien memintanya lewat ?with_detail=1 — dipakai oleh
        // export Excel / print yang memang butuh kolom keterangan & segel.
        $lightRelations = [
            'transporter:id,nama_transporter',
            'driver:id,nama_supir,no_sim,status',
        ];

        $detailRelations = [
            'jenisKendaraan:id,nama,kode',
            'timbangan',
            'createdBy:id,nama',
            'vcfKeluar',
            'segelMasuk.nomorSegel',
            'segelKeluar.nomorSegel',
            'bebanTambahanMasuk',
            'bebanTambahanKeluar',
        ];

        $query = Vcf::query()->with(
            $request->boolean('with_detail')
                ? array_merge($lightRelations, $detailRelations)
                : $lightRelations
        );

        // Listing tabel tidak butuh kolom teks panjang (keterangan/catatan).
        if (! $request->boolean('with_detail')) {
            $query->select([
                'id',
                'nomor_urut',
                'tanggal',
                'produk',
                'tipe_kegiatan',
                'no_polisi',
                'jenis_kendaraan_id',
                'transporter_id',
                'driver_id',
                'jam_masuk',
                'status',
                'created_at',
            ]);
        }

        if ($request->filled('search')) {
            $like = $this->likeOperator();
            $query->where(function ($q) use ($request, $like) {
                $q->where('nomor_urut', $like, '%' . $request->search . '%')
                    ->orWhere('no_polisi', $like, '%' . $request->search . '%')
                    ->orWhere('produk', $like, '%' . $request->search . '%')
                    ->orWhere('tipe_kegiatan', $like, '%' . $request->search . '%')
                    ->orWhere('status', $like, '%' . $request->search . '%')
                    ->orWhereHas('driver', function ($q) use ($request, $like) {
                        $q->where('nama_supir', $like, '%' . $request->search . '%')
                            ->orWhere('no_sim', $like, '%' . $request->search . '%');
                    })
                    ->orWhereHas('transporter', function ($q) use ($request, $like) {
                        $q->where('nama_transporter', $like, '%' . $request->search . '%');
                    });
            });
        }

        // Catatan: gunakan filled() dan bukan has(). has('status') tetap true untuk
        // "?status=" (string kosong) sehingga query jadi status = '' dan hasilnya kosong.
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        } elseif ($request->filled('tanggal_dari') && $request->filled('tanggal_sampai')) {
            $query->whereBetween('tanggal', [$request->tanggal_dari, $request->tanggal_sampai]);
        } elseif ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_dari);
        } elseif ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_sampai);
        } else {
            // Default: Tampilkan VCF aktif dari 7 hari terakhir
            // Truck yang registrasi hari sebelumnya tapi belum selesai tetap muncul untuk tracking
            $query->whereDate('tanggal', '>=', \Carbon\Carbon::now()->subDays(7)->toDateString());
        }

        if ($request->boolean('only_blacklist')) {
            $query->where(function ($q) {
                $q->where('status', 'reject')
                  ->orWhereHas('driver', function ($dq) {
                      $dq->where('status', 'blacklist');
                  })
                  ->orWhere('keterangan', 'LIKE', '%[BLACKLIST]%')
                  ->orWhere('catatan', 'LIKE', '%[BLACKLIST]%');
            });
        } else {
            if ($request->filled('status')) {
                if ($request->status === 'aktif') {
                    $query->whereNotIn('status', ['selesai', 'reject']);
                } elseif ($request->status === 'reject') {
                    $query->where('status', 'reject');
                } else {
                    $query->where('status', $request->status);
                }
            }
        }

        // Filter multi-status: ?status_in=bagian1_selesai,bagian2_selesai,bagian3_selesai
        // Dipakai halaman Operasional VCF supaya filtering tahap dilakukan di DB,
        // bukan mengunduh seluruh baris lalu difilter di browser.
        if ($request->filled('status_in')) {
            $statuses = collect(explode(',', (string) $request->status_in))
                ->map(fn ($s) => trim($s))
                ->filter()
                ->values()
                ->all();

            if (! empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
        }

        if ($request->filled('tipe_kegiatan')) {
            $query->where('tipe_kegiatan', $request->tipe_kegiatan);
        }

        if ($request->filled('transporter_id')) {
            $query->where('transporter_id', $request->transporter_id);
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        // Batasi per_page supaya satu request tidak pernah menarik puluhan ribu baris.
        $perPage = (int) $request->get('per_page', 15);
        $perPage = max(1, min($perPage, 500));

        $data = $query->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($data);
    }

    /**
     * Rekap jumlah VCF per bulan untuk satu tahun (halaman Arsip VCF).
     *
     * Menggantikan pola lama di frontend yang mengunduh SELURUH VCF setahun
     * (per_page=10000, lengkap dengan semua relasi) hanya untuk menghitung
     * angka pada 12 kartu bulan. Sekarang cukup satu query agregat.
     */
    public function monthlyStats(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));

        $rows = Vcf::query()
            ->selectRaw($this->monthExpression() . ' AS bulan')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai")
            ->selectRaw("SUM(CASE WHEN status = 'reject' THEN 1 ELSE 0 END) AS reject")
            ->whereYear('tanggal', $year)
            ->groupByRaw($this->monthExpression())
            ->get();

        // Selalu kembalikan 12 bulan agar frontend tidak perlu mengisi celah.
        $months = collect(range(1, 12))->mapWithKeys(fn ($m) => [
            $m => ['bulan' => $m, 'total' => 0, 'selesai' => 0, 'reject' => 0],
        ])->all();

        foreach ($rows as $row) {
            $m = (int) $row->bulan;
            if (! isset($months[$m])) {
                continue;
            }
            $months[$m] = [
                'bulan'   => $m,
                'total'   => (int) $row->total,
                'selesai' => (int) $row->selesai,
                'reject'  => (int) $row->reject,
            ];
        }

        return response()->json([
            'year' => $year,
            'data' => array_values($months),
        ]);
    }

    /**
     * Rekap jumlah VCF per status untuk badge halaman Operasional VCF.
     *
     * Menghormati filter rentang tanggal + search yang sama dengan index(),
     * sehingga angka badge konsisten dengan tabel di bawahnya.
     */
    public function operationalStats(Request $request)
    {
        $query = Vcf::query();

        if ($request->filled('search')) {
            $like = $this->likeOperator();
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search, $like) {
                $q->where('nomor_urut', $like, $search)
                    ->orWhere('no_polisi', $like, $search)
                    ->orWhere('produk', $like, $search)
                    ->orWhere('tipe_kegiatan', $like, $search)
                    ->orWhere('status', $like, $search)
                    ->orWhereHas('driver', function ($q) use ($search, $like) {
                        $q->where('nama_supir', $like, $search)
                            ->orWhere('no_sim', $like, $search);
                    })
                    ->orWhereHas('transporter', function ($q) use ($search, $like) {
                        $q->where('nama_transporter', $like, $search);
                    });
            });
        }

        if ($request->filled('tanggal_dari') && $request->filled('tanggal_sampai')) {
            $query->whereBetween('tanggal', [$request->tanggal_dari, $request->tanggal_sampai]);
        }

        $today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();

        $aktif = "status IN ('bagian1_selesai','bagian2_selesai','bagian3_selesai')";

        $row = $query
            ->selectRaw("SUM(CASE WHEN status = 'bagian1_selesai' THEN 1 ELSE 0 END) AS wb_masuk")
            ->selectRaw("SUM(CASE WHEN status = 'bagian2_selesai' THEN 1 ELSE 0 END) AS wb_keluar")
            ->selectRaw("SUM(CASE WHEN status = 'bagian3_selesai' THEN 1 ELSE 0 END) AS mg_keluar")
            ->selectRaw("SUM(CASE WHEN {$aktif} AND tanggal = ? THEN 1 ELSE 0 END) AS hari_ini", [$today])
            ->selectRaw("SUM(CASE WHEN {$aktif} AND tanggal < ? THEN 1 ELSE 0 END) AS ditunda", [$today])
            ->first();

        return response()->json([
            'data' => [
                'hari_ini'  => (int) ($row->hari_ini ?? 0),
                'ditunda'   => (int) ($row->ditunda ?? 0),
                'wb_masuk'  => (int) ($row->wb_masuk ?? 0),
                'wb_keluar' => (int) ($row->wb_keluar ?? 0),
                'mg_keluar' => (int) ($row->mg_keluar ?? 0),
            ],
        ]);
    }

    /**
     * Tolak VCF jika terjadi ketidaksesuaian pemeriksaan.
     */
    public function reject(Request $request, $id)
    {
        $id = (int) $id;

        // Hanya VCF yang belum selesai dan bukan sudah ditolak yang bisa ditolak
        if (in_array($vcf->status, ['selesai', 'reject'])) {
            return response()->json([
                'message' => 'VCF sudah selesai atau sudah ditolak sebelumnya.',
            ], 422);
        }

        $validated = $request->validate([
            'catatan_reject' => 'required|string|max:500',
        ]);

        $vcf->update([
            'status' => 'reject',
            'catatan' => trim(($vcf->catatan ?? '') . "\n[REJECTED AT MAIN GATE MASUK]: " . $validated['catatan_reject']),
            'keterangan' => trim(($vcf->keterangan ?? '') . "\n[REJECTED AT MAIN GATE MASUK]: " . $validated['catatan_reject'])
        ]);

        ActivityLogger::vcfRejected($vcf, $validated['catatan_reject'], 'Main Gate');

        return response()->json([
            'message' => 'VCF telah ditolak.',
            'data' => $vcf
        ]);
    }

    /**
     * Buat VCF baru — Bagian 1 (Security Main Gate).
     * Status awal: 'draft_bagian1_selesai'
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'tipe_kegiatan' => 'required|in:loading_lokal,loading_export,unloading_lokal,unloading_import',
            'produk' => 'required|string|max:120',
            'asal_tujuan' => 'required|string|max:255',
            'jenis_kendaraan_id' => [
                'required',
                Rule::exists('jenis_kendaraans', 'id')->where('is_active', true),
            ],
            'no_polisi' => 'required|string|max:20',
            'tipe_kendaraan' => 'required|in:bak_terbuka,tangki,umum,box,container',
            'tahun_kendaraan' => 'required|integer|min:1950|max:2100',
            'transporter_id' => [
                'required',
                Rule::exists('transporters', 'id')->where('is_active', true),
            ],
            'driver_id' => [
                'required',
                Rule::exists('drivers', 'id')->where('is_active', true),
            ],
            'jam_masuk' => 'required|date_format:H:i',
            'beban_tambahan_ada' => 'boolean',
            'jenis_beban' => 'nullable|required_if:beban_tambahan_ada,true|string|max:255',

            // Segel
            'segel_terpasang' => [
                Rule::requiredIf(str_starts_with($request->input('tipe_kegiatan', ''), 'unloading')),
                'nullable',
                'boolean',
                function ($attribute, $value, $fail) use ($request) {
                    if (str_starts_with($request->input('tipe_kegiatan', ''), 'unloading') && !$value) {
                        $fail('Segel wajib terpasang (disetujui/aktif) untuk unloading.');
                    }
                },
            ],
            'jumlah_segel' => [
                Rule::requiredIf(fn() => $request->boolean('segel_terpasang') && str_starts_with($request->input('tipe_kegiatan', ''), 'unloading')),
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'nomor_segel' => [
                Rule::requiredIf(fn() => $request->boolean('segel_terpasang')
                    && str_starts_with($request->input('tipe_kegiatan', ''), 'unloading')),
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    $isUnloading = str_starts_with($request->input('tipe_kegiatan', ''), 'unloading');
                    $segelTerpasang = $request->boolean('segel_terpasang');
                    if ($isUnloading && $segelTerpasang && (empty($value) || count($value) < 1)) {
                        $fail('Minimal harus ada 1 nomor segel.');
                    }
                },
            ],
            'nomor_segel.*' => 'nullable|string|max:100',

            // Kelengkapan supir (optional in Stage 1)
            'kelengkapan_supir' => 'nullable|array',
            'kelengkapan_supir.*.item_id' => 'required_with:kelengkapan_supir|exists:item_kelengkapan_supirs,id',
            'kelengkapan_supir.*.nilai' => 'required_with:kelengkapan_supir|boolean',
            'kelengkapan_supir.*.keterangan' => 'nullable|string',

            'muatan_dibawa' => [
                Rule::requiredIf(fn() => str_starts_with($request->input('tipe_kegiatan', ''), 'unloading')),
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    if (str_starts_with($request->input('tipe_kegiatan', ''), 'unloading') && empty($value)) {
                        $fail('Detail muatan yang dibawa wajib diisi untuk unloading.');
                    }
                },
            ],

            'muatan_dibawa.*.item_muatan_id' => 'nullable|exists:item_muatans,id',
            'muatan_dibawa.*.nilai' => 'nullable|string|max:255',
            'muatan_dibawa.*.keterangan' => 'nullable|string',

            'muatan_diisi' => [
                Rule::requiredIf(fn() => str_starts_with($request->input('tipe_kegiatan', ''), 'loading')),
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    if (str_starts_with($request->input('tipe_kegiatan', ''), 'loading') && empty($value)) {
                        $fail('Detail muatan yang akan diisi wajib diisi untuk loading.');
                    }
                },
            ],

            'muatan_diisi.*.item_muatan_id' => 'nullable|exists:item_muatans,id',
            'muatan_diisi.*.nilai' => 'nullable|string|max:255',
            'muatan_diisi.*.keterangan' => 'nullable|string',

            // Keterangan umum (opsional)
            'keterangan' => 'nullable|string|max:1000',
            'bruto_from' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('tara_from') && $value < $request->input('tara_from')) {
                        $fail('Bruto tidak boleh lebih kecil dari Tara (Netto negatif).');
                    }
                },
            ],
            'tara_from' => 'nullable|numeric|min:0',
            'netto_from' => 'nullable|numeric|min:0',
        ], [
            'tahun_kendaraan.integer' => 'Tahun kendaraan harus berupa angka.',
            'tahun_kendaraan.max' => 'Tahun kendaraan tidak boleh lebih dari 2100.',
            'segel_terpasang.required_if' => 'Status segel terpasang wajib diisi untuk unloading.',
            'segel_terpasang.accepted' => 'Segel wajib terpasang (disetujui/aktif) untuk unloading.',
            'jumlah_segel.required_if' => 'Jumlah segel wajib diisi untuk unloading.',
            'jumlah_segel.integer' => 'Jumlah segel harus berupa angka.',
            'jumlah_segel.min' => 'Jumlah segel minimal 1.',
            'nomor_segel.required_if' => 'Nomor segel wajib diisi untuk unloading.',
            'nomor_segel.array' => 'Nomor segel harus berupa array/list.',
            'nomor_segel.min' => 'Minimal harus ada 1 nomor segel.',
            'nomor_segel.*.required_if' => 'Nomor segel tidak boleh kosong.',
        ]);

        DB::beginTransaction();
        try {

            // Cek status blacklist driver
            $driver = Driver::find($validated['driver_id']);
            if ($driver && ($driver->status ?? 'normal') === 'blacklist') {
                return response()->json([
                    'message' => 'Driver masuk daftar blacklist dan tidak dapat membuat VCF baru.',
                    'error_code' => 'DRIVER_BLACKLISTED',
                ], 422);
            }

            $validated['no_polisi'] = strtoupper(trim($validated['no_polisi']));

            $existingRecord = Vcf::whereRaw('UPPER(TRIM(no_polisi)) = ?', [$validated['no_polisi']])
                ->whereNotIn('status', ['selesai', 'reject'])
                ->first();

            if ($existingRecord) {
                return response()->json([
                    'message' => 'No polisi sudah terdaftar dan belum selesai atau reject.',
                ], 422);
            }

            $tanggalVcf = $validated['tanggal'];
            try {
                $date = \Carbon\Carbon::parse($tanggalVcf);
            } catch (\Exception $e) {
                $date = \Carbon\Carbon::now();
            }

            $startOfMonth = $date->copy()->startOfMonth()->toDateString();
            $endOfMonth = $date->copy()->endOfMonth()->toDateString();

            // Nomor urut reset bulanan.
            /** @var \Illuminate\Database\Connection $connection */
            $connection = DB::connection();
            $castType = $connection->getDriverName() === 'pgsql' ? 'INTEGER' : 'UNSIGNED';

            $maxNum = Vcf::whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                ->max(DB::raw("CAST(nomor_urut AS {$castType})"));

            $newNomorUrut = str_pad((int) $maxNum + 1, 5, '0', STR_PAD_LEFT);

            $vcf = Vcf::create([
                'nomor_urut' => $newNomorUrut,
                'tanggal' => $validated['tanggal'],
                'produk' => $validated['produk'],
                'tipe_kegiatan' => $validated['tipe_kegiatan'],
                'asal_tujuan' => $validated['asal_tujuan'] ?? null,
                'jenis_kendaraan_id' => $validated['jenis_kendaraan_id'],
                'no_polisi' => $validated['no_polisi'],
                'tipe_kendaraan' => $validated['tipe_kendaraan'] ?? null,
                'tahun_kendaraan' => $validated['tahun_kendaraan'] ?? null,
                'transporter_id' => $validated['transporter_id'],
                'driver_id' => $validated['driver_id'],
                'jam_masuk' => $validated['jam_masuk'],
                'keterangan' => $validated['keterangan'] ?? null,
                'created_by' => $request->user()->id,
                'status' => 'bagian1_selesai',
            ]);

            // Simpan kelengkapan supir
            if (!empty($validated['kelengkapan_supir'])) {
                foreach ($validated['kelengkapan_supir'] as $item) {
                    VcfKelengkapanSupir::create([
                        'vcf_id' => $vcf->id,
                        'item_id' => $item['item_id'],
                        'nilai' => $item['nilai'],
                        'keterangan' => $item['keterangan'] ?? null,
                    ]);
                }
            }

            // Simpan muatan dibawa
            if (!empty($validated['muatan_dibawa'])) {
                foreach ($validated['muatan_dibawa'] as $item) {
                    VcfMuatanDibawa::create([
                        'vcf_id' => $vcf->id,
                        'item_muatan_id' => $item['item_muatan_id'] ?? null,
                        'nilai' => $item['nilai'] ?? null,
                        'keterangan' => $item['keterangan'] ?? null,
                    ]);
                }
            }

            // Simpan muatan diisi (loading) jika ada
            if (!empty($validated['muatan_diisi'])) {
                foreach ($validated['muatan_diisi'] as $item) {
                    VcfMuatanDiisi::create([
                        'vcf_id' => $vcf->id,
                        'item_muatan_id' => $item['item_muatan_id'] ?? null,
                        'nilai' => $item['nilai'] ?? null,
                        'keterangan' => $item['keterangan'] ?? null,
                    ]);
                }
            }

            // Simpan Beban Tambahan jika ada
            if (!empty($validated['beban_tambahan_ada'])) {
                $vcf->bebanTambahanMasuk()->create([
                    'jenis_beban' => $validated['jenis_beban'] ?? null,
                ]);
            }

            // Simpan segel masuk jika unloading
            if (str_starts_with($validated['tipe_kegiatan'], 'unloading')) {
                $segelTerpasang = $request->input('segel_terpasang', false);
                $segel = \App\Models\VcfSegelMasuk::create([
                    'vcf_id' => $vcf->id,
                    'jumlah_segel' => $segelTerpasang ? ($request->input('jumlah_segel') ?? count($request->input('nomor_segel') ?? [])) : 0,
                    'petugas_id' => $request->user()->id,
                    'keterangan' => null, // Segel/WB Masuk keterangan is separate from registration keterangan
                ]);

                if ($segelTerpasang && $request->has('nomor_segel') && is_array($request->input('nomor_segel'))) {
                    foreach ($request->input('nomor_segel') as $urutan => $nomor) {
                        if (!empty($nomor)) {
                            \App\Models\VcfNomorSegelMasuk::create([
                                'segel_masuk_id' => $segel->id,
                                'urutan' => $urutan + 1,
                                'nomor_segel' => $nomor,
                            ]);
                        }
                    }
                }
            }

            // Create scale record
            \App\Models\Timbangan::create([
                'vcf_id' => $vcf->id,
                'bruto_from' => $validated['bruto_from'] ?? null,
                'tara_from' => $validated['tara_from'] ?? null,
                'netto_from' => $validated['netto_from'] ?? null,
            ]);

            DB::commit();

            ActivityLogger::vcfCreated($vcf);

            return response()->json([
                'message' => 'VCF Bagian 1 berhasil disimpan.',
                'data' => $this->loadVcfFull($vcf->id),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            return response()->json([
                'message' => 'Gagal menyimpan VCF.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail lengkap satu VCF.
     */
    public function show($id)
    {
        $id = (int) $id;
        return response()->json($this->loadVcfFull($id));
    }

    /**
     * Update Bagian 1 — hanya jika status masih 'bagian1_selesai' atau user adalah admin.
     */
    public function update(Request $request, $id)
    {
        $id = (int) $id;
        $vcf = Vcf::findOrFail($id);

        // Only admin can edit VCF at any stage. Petugas cannot edit if status is selesai/reject.
        if (in_array($vcf->status, ['selesai', 'reject']) && !$this->isAdmin()) {
            return response()->json([
                'message' => 'VCF tidak dapat diedit karena sudah final/ditolak. Hanya admin yang dapat mengedit VCF pada status ini.',
            ], 422);
        }

        // Resolve tipe_kegiatan: pakai dari request jika ada, fallback ke data VCF existing
        $tipeKegiatan = $request->input('tipe_kegiatan', $vcf->tipe_kegiatan);
        $isUnloading = str_starts_with($tipeKegiatan, 'unloading');
        $isLoading = str_starts_with($tipeKegiatan, 'loading');

        $validated = $request->validate([
            'nomor_urut' => 'sometimes|required|string|max:50|unique:vcfs,nomor_urut,' . $vcf->id,
            'tanggal' => 'sometimes|required|date',
            'tipe_kegiatan' => 'sometimes|required|in:loading_lokal,loading_export,unloading_lokal,unloading_import',
            'produk' => 'sometimes|required|string|max:120',
            'asal_tujuan' => 'nullable|string|max:255',
            'jenis_kendaraan_id' => [
                'sometimes',
                'required',
                Rule::exists('jenis_kendaraans', 'id')->where('is_active', true),
            ],
            'no_polisi' => 'sometimes|required|string|max:20',
            'tipe_kendaraan' => 'nullable|in:bak_terbuka,tangki,umum,box,container',
            'tahun_kendaraan' => 'nullable|integer|min:1950|max:2100',
            'transporter_id' => [
                'sometimes',
                'required',
                Rule::exists('transporters', 'id')->where('is_active', true),
            ],
            'driver_id' => [
                'sometimes',
                'required',
                Rule::exists('drivers', 'id')->where('is_active', true),
            ],
            'jam_masuk' => 'sometimes|required|date_format:H:i',

            // ── Segel (hanya wajib saat unloading) ──────────────────────────
            'segel_terpasang' => [
                Rule::requiredIf($isUnloading),
                'nullable',
                'boolean',
                function ($attribute, $value, $fail) use ($isUnloading) {
                    if ($isUnloading && !$value) {
                        $fail('Segel wajib terpasang (disetujui/aktif) untuk unloading.');
                    }
                },
            ],
            'jumlah_segel' => [
                Rule::requiredIf($isUnloading && $request->boolean('segel_terpasang')),
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'nomor_segel' => [
                Rule::requiredIf($isUnloading && $request->boolean('segel_terpasang')),
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($isUnloading, $request) {
                    if ($isUnloading && $request->boolean('segel_terpasang') && empty($value)) {
                        $fail('Minimal harus ada 1 nomor segel.');
                    }
                },
            ],
            'nomor_segel.*' => 'nullable|string|max:100',

            // ── Kelengkapan supir ────────────────────────────────────────────
            'kelengkapan_supir' => 'sometimes|array',
            'kelengkapan_supir.*.item_id' => 'required|exists:item_kelengkapan_supirs,id',
            'kelengkapan_supir.*.nilai' => 'required|boolean',
            'kelengkapan_supir.*.keterangan' => 'nullable|string',

            // ── Muatan dibawa (wajib saat unloading) ────────────────────────
            'muatan_dibawa' => [
                Rule::requiredIf($isUnloading),
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($isUnloading) {
                    if ($isUnloading && empty($value)) {
                        $fail('Detail muatan yang dibawa wajib diisi untuk unloading.');
                    }
                },
            ],
            'muatan_dibawa.*.item_muatan_id' => 'nullable|exists:item_muatans,id',
            'muatan_dibawa.*.nilai' => 'nullable|string|max:255',
            'muatan_dibawa.*.keterangan' => 'nullable|string',

            // ── Muatan diisi (wajib saat loading) ───────────────────────────
            'muatan_diisi' => [
                Rule::requiredIf($isLoading),
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($isLoading) {
                    if ($isLoading && empty($value)) {
                        $fail('Detail muatan yang akan diisi wajib diisi untuk loading.');
                    }
                },
            ],
            'muatan_diisi.*.item_muatan_id' => 'nullable|exists:item_muatans,id',
            'muatan_diisi.*.nilai' => 'nullable|string|max:255',
            'muatan_diisi.*.keterangan' => 'nullable|string',

            // ── Timbangan & keterangan ───────────────────────────────────────
            'keterangan' => 'nullable|string|max:1000',
            'bruto_from' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('tara_from') && $value < $request->input('tara_from')) {
                        $fail('Bruto tidak boleh lebih kecil dari Tara (Netto negatif).');
                    }
                },
            ],
            'tara_from' => 'nullable|numeric|min:0',
            'netto_from' => 'nullable|numeric|min:0',
        ], [
            'tahun_kendaraan.integer' => 'Tahun kendaraan harus berupa angka.',
            'tahun_kendaraan.max' => 'Tahun kendaraan tidak boleh lebih dari 2100.',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['no_polisi'])) {
                $validated['no_polisi'] = strtoupper(trim($validated['no_polisi']));
            }

            // Update field utama VCF
            $fillable = array_intersect_key($validated, array_flip([
                'nomor_urut',
                'tanggal',
                'produk',
                'tipe_kegiatan',
                'asal_tujuan',
                'jenis_kendaraan_id',
                'no_polisi',
                'transporter_id',
                'driver_id',
                'jam_masuk',
                'tipe_kendaraan',
                'tahun_kendaraan',
                'keterangan',
            ]));
            if (!empty($fillable)) {
                $vcf->update($fillable);
            }

            // ── Kelengkapan supir ────────────────────────────────────────────
            if (isset($validated['kelengkapan_supir'])) {
                $vcf->kelengkapanSupir()->delete();
                foreach ($validated['kelengkapan_supir'] as $item) {
                    VcfKelengkapanSupir::create([
                        'vcf_id' => $vcf->id,
                        'item_id' => $item['item_id'],
                        'nilai' => $item['nilai'],
                        'keterangan' => $item['keterangan'] ?? null,
                    ]);
                }
            }

            // ── Muatan dibawa (unloading) ────────────────────────────────────
            if (isset($validated['muatan_dibawa'])) {
                $vcf->muatanDibawa()->delete();
                if ($isUnloading) {
                    foreach ($validated['muatan_dibawa'] as $item) {
                        VcfMuatanDibawa::create([
                            'vcf_id' => $vcf->id,
                            'item_muatan_id' => $item['item_muatan_id'] ?? null,
                            'nilai' => $item['nilai'] ?? null,
                            'keterangan' => $item['keterangan'] ?? null,
                        ]);
                    }
                }
            }

            // ── Muatan diisi (loading) ───────────────────────────────────────
            if (isset($validated['muatan_diisi'])) {
                $vcf->muatanDiisi()->delete();
                if ($isLoading) {
                    foreach ($validated['muatan_diisi'] as $item) {
                        VcfMuatanDiisi::create([
                            'vcf_id' => $vcf->id,
                            'item_muatan_id' => $item['item_muatan_id'] ?? null,
                            'nilai' => $item['nilai'] ?? null,
                            'keterangan' => $item['keterangan'] ?? null,
                        ]);
                    }
                }
            }

            // ── Bersihkan segel lama jika tipe kegiatan berubah ──────────────
            $oldTipeKegiatan = $vcf->getOriginal('tipe_kegiatan');
            $newTipeKegiatan = $validated['tipe_kegiatan'] ?? $vcf->tipe_kegiatan;
            if ($oldTipeKegiatan && $newTipeKegiatan && $oldTipeKegiatan !== $newTipeKegiatan) {
                if (str_starts_with($newTipeKegiatan, 'loading')) {
                    $vcf->segelMasuk()->each(fn($s) => $s->nomorSegel()->delete());
                    $vcf->segelMasuk()->delete();
                } elseif (str_starts_with($newTipeKegiatan, 'unloading')) {
                    $vcf->segelKeluar()->each(fn($s) => $s->nomorSegel()->delete());
                    $vcf->segelKeluar()->delete();
                }
            }

            // ── Simpan/update segel masuk (hanya unloading, hanya jika dikirim) ──
            if ($isUnloading && $request->has('segel_terpasang')) {
                $vcf->segelMasuk()->each(fn($s) => $s->nomorSegel()->delete());
                $vcf->segelMasuk()->delete();

                $segelTerpasang = $request->boolean('segel_terpasang');
                $segel = \App\Models\VcfSegelMasuk::create([
                    'vcf_id' => $vcf->id,
                    'jumlah_segel' => $segelTerpasang
                        ? ($request->input('jumlah_segel') ?? count($request->input('nomor_segel') ?? []))
                        : 0,
                    'petugas_id' => $request->user()->id,
                    'keterangan' => null,
                ]);

                if ($segelTerpasang && $request->has('nomor_segel') && is_array($request->input('nomor_segel'))) {
                    foreach ($request->input('nomor_segel') as $urutan => $nomor) {
                        if (!empty($nomor)) {
                            \App\Models\VcfNomorSegelMasuk::create([
                                'segel_masuk_id' => $segel->id,
                                'urutan' => $urutan + 1,
                                'nomor_segel' => $nomor,
                            ]);
                        }
                    }
                }
            }

            // ── Timbangan ────────────────────────────────────────────────────
            $timbanganKeys = ['bruto_from', 'tara_from', 'netto_from'];
            $timbanganUpdate = array_intersect_key($validated, array_flip($timbanganKeys));
            // Hanya update jika setidaknya satu key dikirim dalam request
            $timbanganSent = array_filter($timbanganKeys, fn($k) => array_key_exists($k, $validated));
            if (!empty($timbanganSent)) {
                $timbangan = \App\Models\Timbangan::firstOrCreate(['vcf_id' => $vcf->id]);
                $timbangan->update($timbanganUpdate);
            }

            DB::commit();

            ActivityLogger::vcfUpdated($vcf, $fillable);

            return response()->json([
                'message' => 'VCF Bagian 1 berhasil diperbarui.',
                'data' => $this->loadVcfFull($vcf->id),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            return response()->json([
                'message' => 'Gagal memperbarui VCF.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: load VCF dengan semua relasi.
     */
    private function loadVcfFull($id): Vcf
    {
        $id = (int) $id;
        return Vcf::with([
            'jenisKendaraan',
            'transporter',
            'driver',
            'createdBy:id,nama',
            'kelengkapanSupir.item',
            'muatanDibawa.itemMuatan',
            'muatanDiisi.itemMuatan',
            'pemeriksaanMasuk.item',
            'pemeriksaanMasuk.petugas:id,nama',
            'bebanTambahanMasuk',
            'segelMasuk.nomorSegel',
            'segelMasuk.petugas:id,nama',
            'pemeriksaanKeluar.item',
            'pemeriksaanKeluar.petugas:id,nama',
            'bebanTambahanKeluar',
            'segelKeluar.nomorSegel',
            'segelKeluar.petugas:id,nama',
            'vcfKeluar.petugas:id,nama',
            'timbangan',
        ])->findOrFail($id);
    }
}
