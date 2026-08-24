<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index untuk mempercepat listing VCF (Arsip & Operasional).
 *
 * Query utama pada VcfBagian1Controller::index() adalah:
 *   WHERE tanggal BETWEEN ? AND ? [AND status = ?] ORDER BY tanggal DESC, created_at DESC
 * Tanpa index, PostgreSQL melakukan sequential scan + sort pada seluruh tabel.
 */
class AddPerformanceIndexesToVcfsTable extends Migration
{
    public function up()
    {
        $vcfsIndexes = [
            // Composite: filter rentang tanggal + sort. Dipakai hampir semua listing.
            'vcfs_tanggal_created_at_index' => ['tanggal', 'created_at'],
            // Filter status (tab Aktif / WB Masuk / dst) yang dikombinasikan dengan tanggal.
            'vcfs_status_tanggal_index'     => ['status', 'tanggal'],
            // Pencarian & getNextNumber (CAST(nomor_urut) per bulan).
            'vcfs_nomor_urut_index'         => ['nomor_urut'],
            'vcfs_no_polisi_index'          => ['no_polisi'],
        ];

        foreach ($vcfsIndexes as $indexName => $columns) {
            if ($this->indexExists('vcfs', $indexName)) {
                continue;
            }
            Schema::table('vcfs', function (Blueprint $table) use ($indexName, $columns) {
                $table->index($columns, $indexName);
            });
        }

        // Index pada foreign key tabel anak (hasOne/hasMany) — dipakai eager loading
        // "WHERE vcf_id IN (...)". Laravel tidak otomatis membuat index untuk kolom ini
        // pada beberapa migrasi, jadi dibuat manual & idempotent.
        foreach ($this->childTables() as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'vcf_id')) {
                continue;
            }

            $indexName = $tableName . '_vcf_id_index';
            if ($this->indexExists($tableName, $indexName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->index('vcf_id', $indexName);
            });
        }
    }

    public function down()
    {
        $vcfsIndexNames = [
            'vcfs_tanggal_created_at_index',
            'vcfs_status_tanggal_index',
            'vcfs_nomor_urut_index',
            'vcfs_no_polisi_index',
        ];

        foreach ($vcfsIndexNames as $indexName) {
            if (! $this->indexExists('vcfs', $indexName)) {
                continue;
            }
            Schema::table('vcfs', function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }

        foreach ($this->childTables() as $tableName) {
            $indexName = $tableName . '_vcf_id_index';
            if (Schema::hasTable($tableName) && $this->indexExists($tableName, $indexName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        }
    }

    private function childTables(): array
    {
        return [
            'vcf_keluars',
            'vcf_timbangans',
            'vcf_segel_masuks',
            'vcf_segel_keluars',
            'vcf_beban_tambahan_masuks',
            'vcf_beban_tambahan_keluars',
            'vcf_kelengkapan_supirs',
            'vcf_muatan_dibawas',
            'vcf_muatan_diisis',
            'vcf_pemeriksaan_masuks',
            'vcf_pemeriksaan_keluars',
        ];
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            return count(
                \Illuminate\Support\Facades\DB::select(
                    "SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                    [$table, $index]
                )
            ) > 0;
        } catch (\Throwable $e) {
            // Non-pgsql atau driver tanpa pg_indexes: biarkan Schema::table gagal-aman.
            return false;
        }
    }
}
