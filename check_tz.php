<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Timezone: " . config('app.timezone') . "\n";
echo "Now: " . now() . "\n";

$latestLog = \App\Models\ActivityLog::orderBy('id', 'desc')->first();
if ($latestLog) {
    echo "Latest Log ID: " . $latestLog->id . "\n";
    echo "created_at Raw: " . $latestLog->getRawOriginal('created_at') . "\n";
    echo "created_at JSON: " . json_encode($latestLog->created_at) . "\n";
    echo "created_at Serialized: " . json_encode($latestLog->toArray()) . "\n";
} else {
    echo "No logs found.\n";
}
