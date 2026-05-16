<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MasterDataSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('item_pemeriksaan_keluars')->truncate();
        DB::table('item_pemeriksaan_masuks')->truncate();
        DB::table('item_muatans')->truncate();
        DB::table('item_kelengkapan_supirs')->truncate();
        DB::table('drivers')->truncate();
        DB::table('transporters')->truncate();
        DB::table('jenis_kendaraans')->truncate();
        DB::table('produks')->truncate();
        DB::table('logistiks')->truncate();
        DB::table('users')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Logistik
        DB::table('logistiks')->insert([
            ['id' => 1, 'nama' => 'Internal Logistic',    'kode' => 'INT', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama' => 'External Logistic',    'kode' => 'EXT', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nama' => 'Third Party Logistic', 'kode' => '3PL', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Produk
        DB::table('produks')->insert([
            ['id' => 1, 'nama' => 'CPO (Crude Palm Oil)',  'keterangan' => 'Crude Palm Oil',  'kode' => 'CPO',    'nomor_urut' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama' => 'PKO (Palm Kernel Oil)', 'keterangan' => 'Palm Kernel Oil', 'kode' => 'PKO',    'nomor_urut' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nama' => 'Stearin',               'keterangan' => 'Palm Stearin',    'kode' => 'STR',    'nomor_urut' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nama' => 'PFAD',                  'keterangan' => 'PFAD',             'kode' => 'PFAD',   'nomor_urut' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Jenis Kendaraan (Sesuai dropdown di form registrasi)
        DB::table('jenis_kendaraans')->insert([
            ['id' => 1, 'nama' => 'Bak Terbuka', 'kode' => 'BAK',       'urutan' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama' => 'Tangki',      'kode' => 'TANGKI',    'urutan' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nama' => 'Umum',        'kode' => 'UMUM',      'urutan' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nama' => 'Box',         'kode' => 'BOX',       'urutan' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'nama' => 'Container',   'kode' => 'CONTAINER', 'urutan' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 4. Transporter
        DB::table('transporters')->insert([
            ['id' => 1, 'nama_transporter' => 'PT. Transport Maju Jaya', 'kode' => 'TMJ', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama_transporter' => 'PT. Logistik Nusantara',  'kode' => 'LNS', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nama_transporter' => 'CV. Sumber Rejeki',       'kode' => 'SRJ', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 5. Driver
        DB::table('drivers')->insert([
            [
                'id'              => 1,
                'nama_supir'      => 'Budi Santoso',
                'no_sim'          => '900100200',
                'jenis_sim'       => 'BII Umum',
                'tgl_berlaku_sim' => '2028-12-31',
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => 2,
                'nama_supir'      => 'Agus Wijaya',
                'no_sim'          => '900100201',
                'jenis_sim'       => 'BII Umum',
                'tgl_berlaku_sim' => '2028-12-31',
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => 3,
                'nama_supir'      => 'Arul Budianto',
                'no_sim'          => '1892939',
                'jenis_sim'       => 'C',
                'tgl_berlaku_sim' => '2026-05-28',
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        // 6. Users
        DB::table('users')->insert([
            [
                'id'            => 1,
                'nama'          => 'Administrator',
                'username'      => 'admin',
                'password_hash' => Hash::make('password'),
                'role'          => 'admin',
                'urutan'        => 1,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => 2,
                'nama'          => 'Petugas Security',
                'username'      => 'petugas',
                'password_hash' => Hash::make('password'),
                'role'          => 'petugas',
                'urutan'        => 2,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);

        // 7. Item Kelengkapan Supir
        DB::table('item_kelengkapan_supirs')->insert([
            ['id' => 1, 'nama_item' => 'SPB/DO',           'keterangan' => 'Surat Perintah Bongkar / Delivery Order', 'urutan' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama_item' => 'Seragam',          'keterangan' => 'Seragam kerja supir',                      'urutan' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nama_item' => 'Sepatu dan Helm',  'keterangan' => 'APD: Sepatu safety dan helm',               'urutan' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nama_item' => 'ID Card (Visitor)','keterangan' => 'Kartu identitas pengunjung / tamu',         'urutan' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 8. Item Muatan
        DB::table('item_muatans')->insert([
            ['id' => 1, 'nama_item' => 'Minyak',         'jenis' => 'both',   'keterangan' => null, 'urutan' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama_item' => 'Fuel/Gas/Chemical','jenis' => 'dibawa', 'keterangan' => null, 'urutan' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nama_item' => 'Sparepart',      'jenis' => 'dibawa', 'keterangan' => null, 'urutan' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nama_item' => 'Limbah',         'jenis' => 'diisi',  'keterangan' => null, 'urutan' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 9. Item Pemeriksaan Masuk (Sistem tipe_jawaban terbaru)
        DB::table('item_pemeriksaan_masuks')->insert([
            ['id' => 1, 'nama_item' => 'Kondisi Tangki',           'kode' => 'TKM', 'tipe_jawaban' => 'Bagus,Tidak Bagus',         'has_detail' => false, 'keterangan_detail' => null, 'urutan' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama_item' => 'Penutup Valve Keluar Minyak','kode' => 'PVM', 'tipe_jawaban' => 'Terpasang,Tidak Terpasang', 'has_detail' => false, 'keterangan_detail' => null, 'urutan' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nama_item' => 'Beban Tambahan',           'kode' => 'BTM', 'tipe_jawaban' => 'Ada,Tidak Ada',             'has_detail' => true,  'keterangan_detail' => 'Jenis Beban',   'urutan' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nama_item' => 'Segel',                    'kode' => 'SGM', 'tipe_jawaban' => 'Terpasang,Tidak Terpasang', 'has_detail' => true,  'keterangan_detail' => 'Nomor Segel',   'urutan' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 10. Item Pemeriksaan Keluar (Sistem tipe_jawaban terbaru)
        DB::table('item_pemeriksaan_keluars')->insert([
            ['id' => 1, 'nama_item' => 'Kondisi Tangki',  'kode' => 'TKK', 'tipe_jawaban' => 'Kosong,Sisa',               'has_detail' => false, 'keterangan_detail' => null, 'urutan' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nama_item' => 'Beban Tambahan',  'kode' => 'BTK', 'tipe_jawaban' => 'Ada,Tidak Ada',             'has_detail' => true,  'keterangan_detail' => 'Jenis Beban',  'urutan' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nama_item' => 'Segel',           'kode' => 'SGK', 'tipe_jawaban' => 'Terpasang,Tidak Terpasang', 'has_detail' => true,  'keterangan_detail' => 'Nomor Segel',  'urutan' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
