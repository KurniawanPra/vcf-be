<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNettoToTimbangansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vcf_timbangans', function (Blueprint $table) {
            $table->decimal('netto', 10, 2)->nullable()->after('tara');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vcf_timbangans', function (Blueprint $table) {
            $table->dropColumn('netto');
        });
    }
}

