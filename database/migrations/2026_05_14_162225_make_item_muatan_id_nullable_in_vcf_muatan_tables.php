<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeItemMuatanIdNullableInVcfMuatanTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Handle MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE vcf_muatan_dibawas MODIFY item_muatan_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE vcf_muatan_diisis MODIFY item_muatan_id BIGINT UNSIGNED NULL');
        } 
        // Handle PostgreSQL
        elseif (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE vcf_muatan_dibawas ALTER COLUMN item_muatan_id DROP NOT NULL');
            DB::statement('ALTER TABLE vcf_muatan_diisis ALTER COLUMN item_muatan_id DROP NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Handle MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE vcf_muatan_dibawas MODIFY item_muatan_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE vcf_muatan_diisis MODIFY item_muatan_id BIGINT UNSIGNED NOT NULL');
        }
        // Handle PostgreSQL
        elseif (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE vcf_muatan_dibawas ALTER COLUMN item_muatan_id SET NOT NULL');
            DB::statement('ALTER TABLE vcf_muatan_diisis ALTER COLUMN item_muatan_id SET NOT NULL');
        }
    }
}
