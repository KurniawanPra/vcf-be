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
            'vcf_timbangans',
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

        $vcf1Id = DB::table('vcfs')->insertGetId([
            'nomor_urut' => '00001',
            'tanggal' => $now->format('Y-m-d'),
            'produk' => 'CPO',
            'tipe_kegiatan' => 'unloading_lokal',
            'asal_tujuan' => 'Palembang',
            'jenis_kendaraan_id' => 2,
            'no_polisi' => 'B 1234 ABC',
            'tipe_kendaraan' => 'Tangki',
            'tahun_kendaraan' => 2019,
            'transporter_id' => 1,
            'driver_id' => 1,
            'jam_masuk' => $now->format('H:i:s'),
            'status' => 'bagian1_selesai',
            'keterangan' => null,
            'catatan' => null,
            'created_by' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->kelengkapanSupir($vcf1Id, [true, true, true, false], $now);
        $this->muatanDibawa($vcf1Id, [2, 3], $now);

        $vcf2Id = DB::table('vcfs')->insertGetId([
            'nomor_urut' => '00002',
            'tanggal' => $now->format('Y-m-d'),
            'produk' => 'PKO',
            'tipe_kegiatan' => 'loading_lokal',
            'asal_tujuan' => 'Jakarta',
            'jenis_kendaraan_id' => 1,
            'no_polisi' => 'D 5678 XYZ',
            'tipe_kendaraan' => 'Bak Terbuka',
            'tahun_kendaraan' => 2021,
            'transporter_id' => 2,
            'driver_id' => 2,
            'jam_masuk' => $now->subHours(3)->format('H:i:s'),
            'status' => 'bagian2_selesai',
            'keterangan' => 'Kendaraan layak.',
            'catatan' => null,
            'created_by' => 1,
            'created_at' => $now->subHours(3),
            'updated_at' => $now->subHours(1),
        ]);
        $this->kelengkapanSupir($vcf2Id, [true, true, true, true], $now->subHours(2));
        $this->muatanDibawa($vcf2Id, [2], $now->subHours(2));
        $this->pemeriksaanMasuk($vcf2Id, [[1, 'Bagus'], [2, 'Terpasang'], [3, 'Tidak Ada']], $now->subHours(1));
        $segelMasukId = $this->segelMasuk($vcf2Id, 2, 'Segel utuh dan original', 2, $now->subHours(1));
        $this->nomorSegel('vcf_nomor_segel_masuks', 'segel_masuk_id', $segelMasukId, ['SGL-001', 'SGL-002'], $now->subHours(1));
        DB::table('vcf_timbangans')->insert([
            'vcf_id' => $vcf2Id,
            'bruto_from' => 'WB-02',
            'bruto' => 25000.50,
            'tara_from' => null,
            'tara' => null,
            'netto_from' => null,
            'netto' => null,
            'created_at' => $now->subHours(1),
            'updated_at' => $now->subHours(1),
        ]);

        $vcf3Id = DB::table('vcfs')->insertGetId([
            'nomor_urut' => '00003',
            'tanggal' => $now->subDays(1)->format('Y-m-d'),
            'produk' => 'Stearin',
            'tipe_kegiatan' => 'loading_export',
            'asal_tujuan' => 'Tanjung Priok',
            'jenis_kendaraan_id' => 5,
            'no_polisi' => 'L 9999 AA',
            'tipe_kendaraan' => 'Container',
            'tahun_kendaraan' => 2022,
            'transporter_id' => 3,
            'driver_id' => 3,
            'jam_masuk' => '08:00:00',
            'status' => 'selesai',
            'keterangan' => 'Pengiriman ekspor selesai.',
            'catatan' => 'Truk diberikan prioritas.',
            'created_by' => 1,
            'created_at' => $now->subDays(1),
            'updated_at' => $now->subDays(1)->addHours(6),
        ]);
        $this->kelengkapanSupir($vcf3Id, [true, true, true, true], $now->subDays(1)->addHour());
        $this->muatanDibawa($vcf3Id, [1, 2], $now->subDays(1)->addHour());
        $this->muatanDiisi($vcf3Id, [4], $now->subDays(1)->addHours(2));
        $this->pemeriksaanMasuk($vcf3Id, [[1, 'Bagus'], [2, 'Terpasang'], [3, 'Tidak Ada']], $now->subDays(1)->addHours(2));
        $this->pemeriksaanKeluar($vcf3Id, [[1, 'Kosong'], [2, 'Tidak Ada']], $now->subDays(1)->addHours(5));
        DB::table('vcf_beban_tambahan_keluars')->insert([
            'vcf_id' => $vcf3Id,
            'jenis_beban' => 'Bandul 10kg',
            'created_at' => $now->subDays(1)->addHours(5),
            'updated_at' => $now->subDays(1)->addHours(5),
        ]);
        $segelKeluarId = $this->segelKeluar($vcf3Id, 1, 'Segel keluar terpasang.', 2, $now->subDays(1)->addHours(5));
        $this->nomorSegel('vcf_nomor_segel_keluars', 'segel_keluar_id', $segelKeluarId, ['SGL-101'], $now->subDays(1)->addHours(5));
        DB::table('vcf_timbangans')->insert([
            'vcf_id' => $vcf3Id,
            'bruto_from' => 'WB-03',
            'bruto' => 32800.75,
            'tara_from' => 'WB-03',
            'tara' => 8200.25,
            'netto_from' => 24600.50,
            'netto' => 24600.50,
            'created_at' => $now->subDays(1)->addHours(3),
            'updated_at' => $now->subDays(1)->addHours(4),
        ]);
        DB::table('vcf_keluars')->insert([
            'vcf_id' => $vcf3Id,
            'jam_keluar' => '14:00:00',
            'emergency_respon_kontak' => '0812-3456-7890',
            'petugas_id' => 2,
            'waktu_input' => $now->subDays(1)->addHours(6),
            'keterangan' => 'Keluar lancar.',
            'created_at' => $now->subDays(1)->addHours(6),
            'updated_at' => $now->subDays(1)->addHours(6),
        ]);
    }

    private function kelengkapanSupir(int $vcfId, array $values, Carbon $time)
    {
        $items = [];
        foreach ($values as $index => $nilai) {
            $items[] = [
                'vcf_id' => $vcfId,
                'item_id' => $index + 1,
                'nilai' => $nilai,
                'keterangan' => null,
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        DB::table('vcf_kelengkapan_supirs')->insert($items);
    }

    private function muatanDibawa(int $vcfId, array $itemIds, Carbon $time)
    {
        $items = [];
        foreach ($itemIds as $itemId) {
            $items[] = [
                'vcf_id' => $vcfId,
                'item_muatan_id' => $itemId,
                'nilai' => 'Ada',
                'keterangan' => null,
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        DB::table('vcf_muatan_dibawas')->insert($items);
    }

    private function muatanDiisi(int $vcfId, array $itemIds, Carbon $time)
    {
        $items = [];
        foreach ($itemIds as $itemId) {
            $items[] = [
                'vcf_id' => $vcfId,
                'item_muatan_id' => $itemId,
                'nilai' => 'Ada',
                'keterangan' => null,
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        DB::table('vcf_muatan_diisis')->insert($items);
    }

    private function pemeriksaanMasuk(int $vcfId, array $rows, Carbon $time)
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'vcf_id' => $vcfId,
                'item_id' => $row[0],
                'nilai' => $row[1],
                'keterangan' => null,
                'petugas_id' => 2,
                'waktu_input' => $time,
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        DB::table('vcf_pemeriksaan_masuks')->insert($items);
    }

    private function pemeriksaanKeluar(int $vcfId, array $rows, Carbon $time)
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'vcf_id' => $vcfId,
                'item_id' => $row[0],
                'nilai' => $row[1],
                'keterangan' => null,
                'petugas_id' => 2,
                'waktu_input' => $time,
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        DB::table('vcf_pemeriksaan_keluars')->insert($items);
    }

    private function segelMasuk(int $vcfId, int $jumlah, string $keterangan, int $petugasId, Carbon $time)
    {
        return DB::table('vcf_segel_masuks')->insertGetId([
            'vcf_id' => $vcfId,
            'jumlah_segel' => $jumlah,
            'keterangan' => $keterangan,
            'petugas_id' => $petugasId,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }

    private function segelKeluar(int $vcfId, int $jumlah, string $keterangan, int $petugasId, Carbon $time)
    {
        return DB::table('vcf_segel_keluars')->insertGetId([
            'vcf_id' => $vcfId,
            'jumlah_segel' => $jumlah,
            'keterangan' => $keterangan,
            'petugas_id' => $petugasId,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }

    private function nomorSegel(string $table, string $fk, int $parentId, array $nomorList, Carbon $time)
    {
        $items = [];
        foreach ($nomorList as $i => $nomor) {
            $items[] = [
                $fk => $parentId,
                'urutan' => $i + 1,
                'nomor_segel' => $nomor,
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        DB::table($table)->insert($items);
    }
}
