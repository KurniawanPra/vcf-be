<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, json, integer
            $table->string('group')->default('general'); // general, vcf, print, etc
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default settings
        $this->seedDefaultSettings();
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }

    private function seedDefaultSettings()
    {
        $settings = [
            // VCF Settings
            [
                'key' => 'vcf.show_produk_lainnya',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'vcf',
                'label' => 'Tampilkan Produk Lainnya',
                'description' => 'Menampilkan opsi "Produk Lainnya" di form registrasi VCF',
                'is_active' => true,
            ],
            [
                'key' => 'vcf.required_all_fields',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'vcf',
                'label' => 'Semua Field Wajib Diisi',
                'description' => 'Membuat semua field transaksi VCF wajib diisi',
                'is_active' => true,
            ],
            [
                'key' => 'vcf.enable_qr_signature',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'vcf',
                'label' => 'Aktifkan QR Code Tanda Tangan',
                'description' => 'Generate QR code otomatis saat petugas submit form',
                'is_active' => true,
            ],
            
            // Print Settings
            [
                'key' => 'print.company_name',
                'value' => 'PT. INDUSTRI NABATI LESTARI',
                'type' => 'string',
                'group' => 'print',
                'label' => 'Nama Perusahaan',
                'description' => 'Nama perusahaan yang tampil di print VCF',
                'is_active' => true,
            ],
            [
                'key' => 'print.company_address',
                'value' => '',
                'type' => 'string',
                'group' => 'print',
                'label' => 'Alamat Perusahaan',
                'description' => 'Alamat perusahaan yang tampil di print VCF',
                'is_active' => true,
            ],
            [
                'key' => 'print.primary_font',
                'value' => 'Arial, sans-serif',
                'type' => 'string',
                'group' => 'print',
                'label' => 'Font Utama',
                'description' => 'Font utama untuk dokumen print',
                'is_active' => true,
            ],
            [
                'key' => 'print.show_qr_signature',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'print',
                'label' => 'Tampilkan QR Signature di Print',
                'description' => 'Menampilkan QR code tanda tangan di dokumen print',
                'is_active' => true,
            ],
            [
                'key' => 'print.footer_text',
                'value' => 'Dokumen ini digenerate otomatis oleh sistem VCF',
                'type' => 'string',
                'group' => 'print',
                'label' => 'Teks Footer Print',
                'description' => 'Teks footer yang muncul di dokumen print',
                'is_active' => true,
            ],

            // General Settings
            [
                'key' => 'general.app_name',
                'value' => 'VCF System',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Nama Aplikasi',
                'description' => 'Nama aplikasi yang ditampilkan di seluruh sistem',
                'is_active' => true,
            ],
            [
                'key' => 'general.timezone',
                'value' => 'Asia/Jakarta',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Zona Waktu',
                'description' => 'Zona waktu default sistem',
                'is_active' => true,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
