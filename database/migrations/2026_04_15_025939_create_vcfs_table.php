<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVcfsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vcfs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_urut');
            $table->date('tanggal');
            // Disimpan sebagai string (sesuai form fisik & mendukung "Others")
            // Contoh: "CPO", "RBDPO", "PFAD", "OTHERS: <input>"
            $table->string('produk');
            $table->string('tipe_kegiatan');
            $table->string('asal_tujuan')->nullable();
            $table->foreignId('jenis_kendaraan_id')->constrained()->cascadeOnDelete();
            $table->string('no_polisi');
            // Bak terbuka / Tangki / Umum / Box / Container
            $table->string('tipe_kendaraan')->nullable();
            $table->unsignedSmallInteger('tahun_kendaraan')->nullable();
            $table->foreignId('transporter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->time('jam_masuk');
            $table->text('keterangan')->nullable();
            $table->string('status')->default('masuk'); // misal: 'masuk', 'keluar', 'selesai', 'reject'
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
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
        Schema::dropIfExists('vcfs');
    }
}
