<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateDriverStatusEnum extends Migration
{
    public function up()
    {
        $dbDriver = DB::connection()->getDriverName();

        if ($dbDriver === 'mysql' || $dbDriver === 'mariadb') {
            // Update lama nilai 'ok' -> 'normal' SEBELUM modifikasi enum
            DB::statement("ALTER TABLE drivers MODIFY status VARCHAR(20) NOT NULL DEFAULT 'normal'");
            DB::statement("UPDATE drivers SET status = 'normal' WHERE status = 'ok' OR status NOT IN ('normal','warning','blacklist')");
            DB::statement("ALTER TABLE drivers MODIFY status ENUM('normal','warning','blacklist') NOT NULL DEFAULT 'normal'");
        }
        // SQLite tidak support MODIFY, tapi tabel baru saja dibuat jadi kolom belum ada nilai lama
    }

    public function down()
    {
        $dbDriver = DB::connection()->getDriverName();

        if ($dbDriver === 'mysql' || $dbDriver === 'mariadb') {
            DB::statement("ALTER TABLE drivers MODIFY status ENUM('ok','blacklist') NOT NULL DEFAULT 'ok'");
            DB::statement("UPDATE drivers SET status = 'ok' WHERE status NOT IN ('ok','blacklist')");
        }
    }
}
