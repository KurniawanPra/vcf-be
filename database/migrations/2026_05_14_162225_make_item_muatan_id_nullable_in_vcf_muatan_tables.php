<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeItemMuatanIdNullableInVcfMuatanTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE vcf_muatan_dibawas MODIFY item_muatan_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE vcf_muatan_diisis MODIFY item_muatan_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE vcf_muatan_dibawas MODIFY item_muatan_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE vcf_muatan_diisis MODIFY item_muatan_id BIGINT UNSIGNED NOT NULL');
    }
}
