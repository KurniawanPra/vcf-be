<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViolationsTable extends Migration
{
    public function up()
    {
        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('no_polisi', 20)->nullable();
            $table->string('jenis_pelanggaran', 255);
            $table->text('keterangan')->nullable();
            $table->date('tanggal_pelanggaran');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index('driver_id');
            $table->index('no_polisi');
        });
    }

    public function down()
    {
        Schema::dropIfExists('violations');
    }
}
