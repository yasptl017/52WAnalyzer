<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Stock;
use App\Models\Daily52wHigh;
use Illuminate\Support\Facades\DB;

$series = Stock::select('series', DB::raw('count(*) as count'))->groupBy('series')->get();
echo "Stock Series breakdown:\n";
foreach ($series as $s) {
    echo "Series: {$s->series} -> Count: {$s->count}\n";
}

$sampleHighs = Daily52wHigh::join('stocks', 'daily_52w_highs.stock_id', '=', 'stocks.id')
    ->select('stocks.symbol', 'stocks.series', 'daily_52w_highs.trade_date', 'daily_52w_highs.ltp', 'daily_52w_highs.p_change')
    ->limit(10)
    ->get();

echo "\nSample 52W Highs:\n";
foreach ($sampleHighs as $h) {
    echo "{$h->trade_date} | {$h->symbol} ({$h->series}) | LTP: {$h->ltp} | %Chg: {$h->p_change}%\n";
}
