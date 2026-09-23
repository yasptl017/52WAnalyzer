<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Stock;
use App\Models\Daily52wHigh;
use App\Models\DailyVolumeGainer;
use App\Models\OhlcvBar;

$symbol = 'QUINT';
$stock = Stock::where('symbol', $symbol)->first();

if (!$stock) {
    echo "Stock {$symbol} not found\n";
    exit;
}

echo "Found stock: {$stock->symbol} ({$stock->company_name}) ID={$stock->id}\n";

$highs = Daily52wHigh::where('stock_id', $stock->id)->orderBy('trade_date', 'asc')->get();
$vgs = DailyVolumeGainer::where('stock_id', $stock->id)->orderBy('trade_date', 'asc')->get();

echo "52W High Appearances: " . count($highs) . "\n";
echo "Volume Gainer Appearances: " . count($vgs) . "\n";

foreach ($highs as $h) {
    echo "  52WH: {$h->trade_date} | LTP: Rs.{$h->ltp} | %Chg: {$h->p_change}% | Streak: {$h->consecutive_days}d\n";
}

foreach ($vgs as $v) {
    echo "  VG: {$v->trade_date} | LTP: Rs.{$v->ltp} | %Chg: {$v->p_change}% | Vol Surge: {$v->week1_change}x\n";
}
