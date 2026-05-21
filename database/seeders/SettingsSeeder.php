<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        // Check if table exists
        if (!Schema::hasTable('settings')) {
            return;
        }

        $defaultSettings = [
            // Print Settings
            [
                'key' => 'print.company_name',
                'value' => 'PT. Industri Nabati Lestari',
                'type' => 'string',
                'group' => 'print',
                'label' => 'Nama Perusahaan',
                'description' => 'Nama perusahaan yang ditampilkan di dokumen print',
                'is_active' => true,
            ],
            [
                'key' => 'print.company_address',
                'value' => 'Jl. Industri No. 123, Indonesia',
                'type' => 'string',
                'group' => 'print',
                'label' => 'Alamat Perusahaan',
                'description' => 'Alamat perusahaan di dokumen print',
                'is_active' => true,
            ],
            [
                'key' => 'print.footer_text',
                'value' => 'Dokumen ini dihasilkan secara otomatis oleh sistem VCF',
                'type' => 'string',
                'group' => 'print',
                'label' => 'Teks Footer',
                'description' => 'Teks footer di dokumen print',
                'is_active' => true,
            ],
            [
                'key' => 'print.font_family',
                'value' => 'Arial, sans-serif',
                'type' => 'string',
                'group' => 'print',
                'label' => 'Font Utama',
                'description' => 'Font utama untuk dokumen print',
                'is_active' => true,
            ],
            
            // General Settings
            [
                'key' => 'general.app_name',
                'value' => 'VCF System',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Nama Aplikasi',
                'description' => 'Nama aplikasi yang ditampilkan di header',
                'is_active' => true,
            ],
            [
                'key' => 'general.timezone',
                'value' => 'Asia/Jakarta',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Zona Waktu',
                'description' => 'Zona waktu sistem untuk pencatatan waktu',
                'is_active' => true,
            ],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
