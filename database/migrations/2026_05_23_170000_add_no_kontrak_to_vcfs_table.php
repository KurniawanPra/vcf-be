<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoKontrakToVcfsTable extends Migration
{
    public function up()
    {
        Schema::table('vcfs', function (Blueprint $table) {
            $table->string('no_kontrak')->nullable()->after('keterangan');
        });
    }

    public function down()
    {
        Schema::table('vcfs', function (Blueprint $table) {
            $table->dropColumn('no_kontrak');
        });
    }
}
