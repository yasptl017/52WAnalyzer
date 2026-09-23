<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Daily52wHigh;
use App\Models\DailyVolumeGainer;
use App\Models\DailySummary;
use App\Models\DataArchive;
use App\Models\OhlcvBar;
use App\Services\TradingCalendarService;

$calendar = app(TradingCalendarService::class);

echo "Starting weekend and holiday data cleanup...\n";

$tables = [
    'Daily52wHigh' => [\App\Models\Daily52wHigh::class, 'trade_date'],
    'DailyVolumeGainer' => [\App\Models\DailyVolumeGainer::class, 'trade_date'],
    'DailySummary' => [\App\Models\DailySummary::class, 'trade_date'],
    'DataArchive' => [\App\Models\DataArchive::class, 'trade_date'],
    'OhlcvBar' => [\App\Models\OhlcvBar::class, 'bar_date'],
];

$allDates = [];
foreach ($tables as [$model, $col]) {
    $dates = $model::distinct()->pluck($col)->map(fn($d) => substr((string)$d, 0, 10))->unique()->toArray();
    $allDates = array_unique(array_merge($allDates, $dates));
}

$purged = 0;
foreach ($allDates as $d) {
    if (!$calendar->isTradingDay($d)) {
        $reason = $calendar->getNonTradingReason($d);
        echo "Found non-trading date in DB: {$d} ({$reason}). Purging across all tables...\n";

        foreach ($tables as $name => [$model, $col]) {
            $del = $model::where($col, $d)->delete();
            if ($del > 0) {
                echo "  - Deleted {$del} from {$name}\n";
            }
        }
        $purged++;
    }
}

if ($purged === 0) {
    echo "No non-trading dates found in database. All clean!\n";
} else {
    echo "Cleanup complete! All non-trading/weekend entries successfully removed.\n";
}
