<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVcfBebanTambahanMasuksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vcf_beban_tambahan_masuks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vcf_id')->constrained()->cascadeOnDelete();
            $table->string('jenis_beban')->nullable();
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
        Schema::dropIfExists('vcf_beban_tambahan_masuks');
    }
}
