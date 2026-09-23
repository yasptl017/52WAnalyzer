<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Daily52wHigh dates: \n";
$d52 = \App\Models\Daily52wHigh::distinct()->orderBy('trade_date', 'desc')->pluck('trade_date')->map(fn($d) => substr((string)$d, 0, 10))->unique()->values()->toArray();
print_r(array_slice($d52, 0, 10));

echo "\nDailyVolumeGainer dates: \n";
$dvg = \App\Models\DailyVolumeGainer::distinct()->orderBy('trade_date', 'desc')->pluck('trade_date')->map(fn($d) => substr((string)$d, 0, 10))->unique()->values()->toArray();
print_r(array_slice($dvg, 0, 10));

echo "\nDataArchive dates: \n";
$dar = \App\Models\DataArchive::distinct()->orderBy('trade_date', 'desc')->pluck('trade_date')->map(fn($d) => substr((string)$d, 0, 10))->unique()->values()->toArray();
print_r(array_slice($dar, 0, 10));

