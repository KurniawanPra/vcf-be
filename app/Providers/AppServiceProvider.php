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
                \App\Services\ActivityLogger::masterDeleted($config['type'], $model->$nameField ?? 'N/A');
            });
        }
    }
}
