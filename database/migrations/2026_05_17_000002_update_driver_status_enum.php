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
        } elseif ($dbDriver === 'pgsql' || $dbDriver === 'postgresql') {
            // PostgreSQL: drop constraint, update values, set new default, add new constraint
            DB::statement("ALTER TABLE drivers DROP CONSTRAINT IF EXISTS drivers_status_check");
            DB::statement("UPDATE drivers SET status = 'normal' WHERE status = 'ok' OR status NOT IN ('normal','warning','blacklist')");
            DB::statement("ALTER TABLE drivers ALTER COLUMN status SET DEFAULT 'normal'");
            DB::statement("ALTER TABLE drivers ADD CONSTRAINT drivers_status_check CHECK (status IN ('normal', 'warning', 'blacklist'))");
        }
        // SQLite tidak support MODIFY, tapi tabel baru saja dibuat jadi kolom belum ada nilai lama
    }

    public function down()
    {
        $dbDriver = DB::connection()->getDriverName();

        if ($dbDriver === 'mysql' || $dbDriver === 'mariadb') {
            DB::statement("ALTER TABLE drivers MODIFY status ENUM('ok','blacklist') NOT NULL DEFAULT 'ok'");
            DB::statement("UPDATE drivers SET status = 'ok' WHERE status NOT IN ('ok','blacklist')");
        } elseif ($dbDriver === 'pgsql' || $dbDriver === 'postgresql') {
            DB::statement("ALTER TABLE drivers DROP CONSTRAINT IF EXISTS drivers_status_check");
            DB::statement("UPDATE drivers SET status = 'ok' WHERE status NOT IN ('ok','blacklist')");
            DB::statement("ALTER TABLE drivers ALTER COLUMN status SET DEFAULT 'ok'");
            DB::statement("ALTER TABLE drivers ADD CONSTRAINT drivers_status_check CHECK (status IN ('ok', 'blacklist'))");
        }
    }
}
