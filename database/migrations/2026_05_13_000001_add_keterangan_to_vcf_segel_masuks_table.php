<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKeteranganToVcfSegelMasuksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vcf_segel_masuks', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->after('jumlah_segel');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vcf_segel_masuks', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
}
