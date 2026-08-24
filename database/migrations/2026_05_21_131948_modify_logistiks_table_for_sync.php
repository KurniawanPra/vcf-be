<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyLogistiksTableForSync extends Migration
{
    public function up()
    {
        Schema::table('logistiks', function (Blueprint $table) {
            $table->string('nama_logistik')->nullable()->after('id');
        });

        $dbDriver = DB::getDriverName();
        if ($dbDriver === 'mysql') {
            DB::statement('ALTER TABLE logistiks MODIFY COLUMN nama VARCHAR(255) NULL');
            DB::statement('ALTER TABLE logistiks MODIFY COLUMN kode VARCHAR(255) NULL');
        } else {
            // PostgreSQL syntax
            DB::statement('ALTER TABLE logistiks ALTER COLUMN nama TYPE VARCHAR(255)');
            DB::statement('ALTER TABLE logistiks ALTER COLUMN nama DROP NOT NULL');
            DB::statement('ALTER TABLE logistiks ALTER COLUMN kode TYPE VARCHAR(255)');
            DB::statement('ALTER TABLE logistiks ALTER COLUMN kode DROP NOT NULL');
        }
    }

    public function down()
    {
        $dbDriver = DB::getDriverName();
        if ($dbDriver === 'mysql') {
            DB::statement('ALTER TABLE logistiks MODIFY COLUMN nama VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE logistiks MODIFY COLUMN kode VARCHAR(255) NOT NULL');
        } else {
            // PostgreSQL syntax
            DB::statement('ALTER TABLE logistiks ALTER COLUMN nama TYPE VARCHAR(255)');
            DB::statement('ALTER TABLE logistiks ALTER COLUMN nama SET NOT NULL');
            DB::statement('ALTER TABLE logistiks ALTER COLUMN kode TYPE VARCHAR(255)');
            DB::statement('ALTER TABLE logistiks ALTER COLUMN kode SET NOT NULL');
        }

        Schema::table('logistiks', function (Blueprint $table) {
            $table->dropColumn('nama_logistik');
        });
    }
}