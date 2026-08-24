<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTampilPadaToItemPemeriksaanTables extends Migration
{
    public function up()
    {
        Schema::table('item_pemeriksaan_masuks', function (Blueprint $table) {
            // 'semua' = tampil untuk semua tipe kegiatan
            // 'loading' = hanya untuk loading
            // 'unloading' = hanya untuk unloading
            $table->string('tampil_pada')->default('semua')->after('keterangan_detail');
        });

        Schema::table('item_pemeriksaan_keluars', function (Blueprint $table) {
            $table->string('tampil_pada')->default('semua')->after('keterangan_detail');
        });
    }

    public function down()
    {
        Schema::table('item_pemeriksaan_masuks', function (Blueprint $table) {
            $table->dropColumn('tampil_pada');
        });

        Schema::table('item_pemeriksaan_keluars', function (Blueprint $table) {
            $table->dropColumn('tampil_pada');
        });
    }
}
