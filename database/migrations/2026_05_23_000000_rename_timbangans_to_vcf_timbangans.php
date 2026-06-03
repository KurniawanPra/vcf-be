<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameTimbangansToVcfTimbangans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('timbangans') && !Schema::hasTable('vcf_timbangans')) {
            Schema::rename('timbangans', 'vcf_timbangans');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('vcf_timbangans') && !Schema::hasTable('timbangans')) {
            Schema::rename('vcf_timbangans', 'timbangans');
        }
    }
}
