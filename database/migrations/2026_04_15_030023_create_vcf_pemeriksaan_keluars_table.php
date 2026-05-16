<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVcfPemeriksaanKeluarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vcf_pemeriksaan_keluars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vcf_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('item_pemeriksaan_keluars')->cascadeOnDelete();
            $table->string('nilai')->nullable();
            $table->string('keterangan')->nullable();
            $table->foreignId('petugas_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('waktu_input');
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
        Schema::dropIfExists('vcf_pemeriksaan_keluars');
    }
}
