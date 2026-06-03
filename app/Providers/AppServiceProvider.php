<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $models = [
            \App\Models\Driver::class => ['nama' => 'nama_supir', 'type' => 'driver'],
            \App\Models\Transporter::class => ['nama' => 'nama_transporter', 'type' => 'transporter'],
            \App\Models\JenisKendaraan::class => ['nama' => 'nama', 'type' => 'jenis_kendaraan'],
            \App\Models\Produk::class => ['nama' => 'nama', 'type' => 'produk'],
            \App\Models\User::class => ['nama' => 'nama', 'type' => 'user'],
            \App\Models\ItemKelengkapanSupir::class => ['nama' => 'deskripsi', 'type' => 'item_kelengkapan_supir'],
            \App\Models\ItemMuatan::class => ['nama' => 'nama_item', 'type' => 'item_muatan'],
            \App\Models\ItemPemeriksaanMasuk::class => ['nama' => 'item_pemeriksaan', 'type' => 'item_pemeriksaan_masuk'],
            \App\Models\ItemPemeriksaanKeluar::class => ['nama' => 'item_pemeriksaan', 'type' => 'item_pemeriksaan_keluar'],
            \App\Models\Logistik::class => ['nama' => 'nama_logistik', 'type' => 'logistik'],
        ];

        foreach ($models as $modelClass => $config) {
            $modelClass::created(function ($model) use ($config) {
                $nameField = $config['nama'];
                \App\Services\ActivityLogger::masterCreated($config['type'], $model, $model->$nameField ?? 'N/A');
            });

            $modelClass::updated(function ($model) use ($config) {
                $nameField = $config['nama'];
                \App\Services\ActivityLogger::masterUpdated($config['type'], $model, $model->$nameField ?? 'N/A');
            });

            $modelClass::deleted(function ($model) use ($config) {
                $nameField = $config['nama'];
                \App\Services\ActivityLogger::masterDeleted($config['type'], $model, $model->$nameField ?? 'N/A');
            });
        }

        // Automatic logging for Vcf model updates
        \App\Models\Vcf::updated(function ($vcf) {
            $changes = $vcf->getChanges();
            if (empty($changes)) {
                return;
            }

            // Exclude status and updated_at changes (they are handled by specific logs or ignore)
            $ignoreKeys = ['updated_at', 'status'];
            $actualChanges = array_diff(array_keys($changes), $ignoreKeys);
            if (empty($actualChanges)) {
                return;
            }

            $details = [];
            foreach ($changes as $key => $newVal) {
                if (in_array($key, $ignoreKeys)) {
                    continue;
                }
                $oldVal = $vcf->getOriginal($key);
                if ($oldVal != $newVal) {
                    $details[$key] = [
                        'old' => $oldVal !== null ? (string)$oldVal : 'kosong',
                        'new' => $newVal !== null ? (string)$newVal : 'kosong'
                    ];
                }
            }

            if (!empty($details)) {
                \App\Services\ActivityLogger::vcfUpdatedDirect($vcf, $details);
            }
        });

        // Automatic logging for Timbangan (scale) updates
        \App\Models\Timbangan::updated(function ($timbangan) {
            $changes = $timbangan->getChanges();
            if (empty($changes)) {
                return;
            }

            $ignoreKeys = ['updated_at'];
            $actualChanges = array_diff(array_keys($changes), $ignoreKeys);
            if (empty($actualChanges)) {
                return;
            }

            $details = [];
            foreach ($changes as $key => $newVal) {
                if (in_array($key, $ignoreKeys)) {
                    continue;
                }
                $oldVal = $timbangan->getOriginal($key);
                if ($oldVal != $newVal) {
                    $details[$key] = [
                        'old' => $oldVal !== null ? (string)$oldVal : 'kosong',
                        'new' => $newVal !== null ? (string)$newVal : 'kosong'
                    ];
                }
            }

            if (!empty($details)) {
                $vcf = $timbangan->vcf;
                if ($vcf) {
                    $label = "VCF #{$vcf->nomor_urut} ({$vcf->no_polisi})";
                    $changedKeys = array_keys($details);
                    $desc = "Data timbangan diperbarui pada {$label} (Kolom diubah: " . implode(', ', $changedKeys) . ")";
                    
                    \App\Services\ActivityLogger::log(
                        'timbangan.updated', 'timbangan', 'updated', $vcf,
                        $desc,
                        ['changes' => $details],
                        $label
                    );
                }
            }
        });
    }
}
