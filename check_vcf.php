<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$latestVcfs = \App\Models\Vcf::orderBy('id', 'desc')->limit(10)->get();
foreach ($latestVcfs as $v) {
    echo "VCF ID: {$v->id}, Urut: {$v->nomor_urut}, Tanggal: {$v->tanggal}, Status: {$v->status}\n";
    $logs = \App\Models\ActivityLog::where('module', 'vcf')
        ->where('subject_id', $v->id)
        ->get();
    foreach ($logs as $l) {
        echo "  Log ID: {$l->id}, Action: {$l->action}, Created At: {$l->created_at}\n";
    }
}
