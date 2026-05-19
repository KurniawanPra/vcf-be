<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VcfTransactionSeeder extends Seeder
{
    public function run()
    {
        $connection = DB::connection();
        $isPgsql = $connection->getDriverName() === 'pgsql';

        $tables = [
            'vcf_kelengkapan_supirs', 'vcf_muatan_dibawas', 'vcf_muatan_diisis',
            'vcf_pemeriksaan_masuks', 'vcf_beban_tambahan_masuks', 'vcf_segel_masuks', 'vcf_nomor_segel_masuks',
            'vcf_pemeriksaan_keluars', 'vcf_beban_tambahan_keluars', 'vcf_segel_keluars', 'vcf_nomor_segel_keluars',
            'vcf_keluars', 'vcfs',
        ];

        if ($isPgsql) {
            foreach ($tables as $table) {
                DB::statement("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE");
            }
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $now = Carbon::now();

        // 1. VCF Tahap 1 Selesai (Menunggu Timbangan Masuk)
        $vcf1Id = DB::table('vcfs')->insertGetId([
            'nomor_urut' => '00001',
            'tanggal' => $now->format('Y-m-d'),
            'status' => 'bagian1_selesai',
            'tipe_kegiatan' => 'loading_lokal',
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => 1,
        ]);

        DB::table('vcf_bagian1s')->insert([
            'vcf_id' => $vcf1Id,
            'tgl_masuk' => $now->format('Y-m-d'),
            'jam_masuk' => $now->format('H:i:s'),
            'id_logistik' => 1,
            'id_produk' => 1,
            'id_jenis_kendaraan' => 2,
            'id_transporter' => 1,
            'id_supir' => 1,
            'no_polisi' => 'B 1234 ABC',
            'no_polisi_asli' => 'B 1234 ABC',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. VCF Tahap 2 Selesai (Menunggu Timbangan Keluar)
        $vcf2Id = DB::table('vcfs')->insertGetId([
            'nomor_urut' => '00002',
            'tanggal' => $now->format('Y-m-d'),
            'status' => 'bagian2_selesai',
            'tipe_kegiatan' => 'unloading_lokal',
            'created_at' => $now->subHours(2),
            'updated_at' => $now->subHours(1),
            'created_by' => 1,
        ]);

        DB::table('vcf_bagian1s')->insert([
            'vcf_id' => $vcf2Id,
            'tgl_masuk' => $now->format('Y-m-d'),
            'jam_masuk' => $now->subHours(2)->format('H:i:s'),
            'id_logistik' => 2,
            'id_produk' => 2,
            'id_jenis_kendaraan' => 2,
            'id_transporter' => 2,
            'id_supir' => 2,
            'no_polisi' => 'D 5678 XYZ',
            'no_polisi_asli' => 'D 5678 XYZ',
            'created_at' => $now->subHours(2),
            'updated_at' => $now->subHours(2),
        ]);

        DB::table('vcf_bagian2s')->insert([
            'vcf_id' => $vcf2Id,
            'user_id' => 2,
            'waktu_periksa' => $now->subHours(1),
            'keterangan' => 'Kondisi kendaraan layak.',
            'created_at' => $now->subHours(1),
            'updated_at' => $now->subHours(1),
        ]);

        // Seed Pemeriksaan Masuk for VCF 2
        DB::table('pemeriksaan_masuks')->insert([
            ['vcf_id' => $vcf2Id, 'item_id' => 1, 'nilai' => 'Bagus', 'created_at' => $now],
            ['vcf_id' => $vcf2Id, 'item_id' => 2, 'nilai' => 'Terpasang', 'created_at' => $now],
            ['vcf_id' => $vcf2Id, 'item_id' => 3, 'nilai' => 'Tidak Ada', 'created_at' => $now],
            ['vcf_id' => $vcf2Id, 'item_id' => 4, 'nilai' => 'Terpasang', 'created_at' => $now],
        ]);

        DB::table('segel_masuks')->insert([
            'vcf_id' => $vcf2Id,
            'jumlah_segel' => 2,
            'nomor_segel' => 'SGL-001, SGL-002',
            'keterangan' => 'Segel utuh dan original',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 3. VCF Tahap Selesai
        $vcf3Id = DB::table('vcfs')->insertGetId([
            'nomor_urut' => '00003',
            'tanggal' => $now->subDays(1)->format('Y-m-d'),
            'status' => 'selesai',
            'tipe_kegiatan' => 'loading_export',
            'created_at' => $now->subDays(1),
            'updated_at' => $now->subDays(1)->addHours(4),
            'created_by' => 1,
        ]);

        DB::table('vcf_bagian1s')->insert([
            'vcf_id' => $vcf3Id,
            'tgl_masuk' => $now->subDays(1)->format('Y-m-d'),
            'jam_masuk' => '08:00:00',
            'id_logistik' => 1,
            'id_produk' => 3,
            'id_jenis_kendaraan' => 1,
            'id_transporter' => 3,
            'id_supir' => 3,
            'no_polisi' => 'L 9999 AA',
            'no_polisi_asli' => 'L 9999 AA',
            'created_at' => $now->subDays(1),
            'updated_at' => $now->subDays(1),
        ]);
        
        // VCF 3 completes all stages... (omitted for brevity but status is 'selesai')
    }
}
