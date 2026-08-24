<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Siapa yang melakukan aksi
            $table->unsignedBigInteger('user_id')->nullable(); // null jika sistem
            $table->string('user_name')->nullable();           // snapshot nama user saat log dibuat
            $table->string('user_role')->nullable();           // snapshot role user

            // Jenis event
            // Contoh: vcf.created, vcf.updated, vcf.rejected, vcf.finalized,
            //         vcf.bagian2.created, vcf.bagian3.created, vcf.bagian4.created,
            //         master.driver.created, master.transporter.deleted,
            //         auth.login, auth.logout, timbangan.updated, dll.
            $table->string('event');

            // Modul sumber (vcf, master, auth, settings, timbangan, violation, dll)
            $table->string('module')->nullable();

            // Aksi (created, updated, deleted, rejected, finalized, login, logout, dll)
            $table->string('action');

            // Subject / target — entitas mana yang kena dampak
            $table->string('subject_type')->nullable();   // Contoh: App\Models\Vcf
            $table->unsignedBigInteger('subject_id')->nullable(); // ID entitas target

            // Deskripsi manusiawi
            $table->text('description');

            // Label pendek untuk UI (misal: "VCF #00001", "Driver: Budi")
            $table->string('subject_label')->nullable();

            // Data perubahan (before/after) — JSON
            $table->json('properties')->nullable();

            // IP Address dan User Agent (opsional)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            // Indexes untuk performa query dashboard
            $table->index(['event', 'created_at']);
            $table->index(['module', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
}
