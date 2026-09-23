<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Daily52wHigh;
use App\Models\DailyVolumeGainer;
use App\Models\OhlcvBar;
use App\Models\Stock;

echo "Stocks: " . Stock::count() . "\n";
echo "52W Highs: " . Daily52wHigh::count() . "\n";
echo "Volume Gainers: " . DailyVolumeGainer::count() . "\n";
echo "OHLCV Bars: " . OhlcvBar::count() . "\n";
echo "52WH Range: " . Daily52wHigh::min('trade_date') . " to " . Daily52wHigh::max('trade_date') . "\n";
echo "VG Range: " . DailyVolumeGainer::min('trade_date') . " to " . DailyVolumeGainer::max('trade_date') . "\n";
echo "OHLCV Range: " . OhlcvBar::min('bar_date') . " to " . OhlcvBar::max('bar_date') . "\n";
