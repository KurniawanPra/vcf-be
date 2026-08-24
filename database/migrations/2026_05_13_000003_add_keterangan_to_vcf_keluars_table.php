<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKeteranganToVcfKeluarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vcf_keluars', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->after('emergency_respon_kontak');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vcf_keluars', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
}
