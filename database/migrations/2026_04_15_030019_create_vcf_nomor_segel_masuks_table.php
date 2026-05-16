<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVcfNomorSegelMasuksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vcf_nomor_segel_masuks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segel_masuk_id')->constrained('vcf_segel_masuks')->cascadeOnDelete();
            $table->integer('urutan');
            $table->string('nomor_segel');
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
        Schema::dropIfExists('vcf_nomor_segel_masuks');
    }
}
