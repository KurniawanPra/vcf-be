<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$vcfs = DB::table('vcfs')
    ->select('id', 'nomor_urut', 'tanggal', 'status')
    ->whereNotIn('status', ['selesai', 'reject'])
    ->get();

echo "Active VCFs in database (status NOT IN 'selesai', 'reject'):\n";
foreach ($vcfs as $v) {
    echo "ID: {$v->id}, Urut: {$v->nomor_urut}, Tanggal: {$v->tanggal}, Status: {$v->status}\n";
}
echo "Total count: " . $vcfs->count() . "\n";
