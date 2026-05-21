<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimbangansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timbangans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vcf_id')->constrained()->cascadeOnDelete();
            $table->string('bruto_from')->nullable();
            $table->decimal('bruto', 10, 2)->nullable();
            $table->string('tara_from')->nullable();
            $table->decimal('tara', 10, 2)->nullable();
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
        Schema::dropIfExists('timbangans');
    }
}
