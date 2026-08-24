<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNettoFromToVcfTimbangansTable extends Migration
{
    public function up()
    {
        Schema::table('vcf_timbangans', function (Blueprint $table) {
            $table->decimal('netto_from', 15, 2)->nullable()->after('tara_from');
        });
    }

    public function down()
    {
        Schema::table('vcf_timbangans', function (Blueprint $table) {
            $table->dropColumn('netto_from');
        });
    }
}
