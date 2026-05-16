<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVcfMuatanDibawasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vcf_muatan_dibawas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vcf_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_muatan_id')->constrained()->cascadeOnDelete();
            $table->string('nilai')->nullable();
            $table->string('keterangan')->nullable();
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
        Schema::dropIfExists('vcf_muatan_dibawas');
    }
}
