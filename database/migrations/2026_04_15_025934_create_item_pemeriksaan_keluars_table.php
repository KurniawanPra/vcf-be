<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemPemeriksaanKeluarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_pemeriksaan_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('nama_item');
            $table->string('kode');
            $table->string('tipe_jawaban');
            $table->text('pilihan_jawaban')->nullable();
            $table->boolean('has_detail');
            $table->string('keterangan_detail')->nullable();
            $table->integer('urutan');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('item_pemeriksaan_keluars');
    }
}
