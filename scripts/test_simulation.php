<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

ini_set('memory_limit', '512M');
$startTime = microtime(true);

// 1. Fetch all distinct trading dates sorted as string YYYY-MM-DD
$tradingDates = DB::table('ohlcv_bars')
    ->distinct()
    ->orderBy('bar_date', 'asc')
    ->pluck('bar_date')
    ->map(fn($d) => substr((string)$d, 0, 10))
    ->values()
    ->toArray();

$dateIndexMap = array_flip($tradingDates);
$totalDates = count($tradingDates);

echo "Total trading dates in DB: {$totalDates} (" . reset($tradingDates) . " to " . end($tradingDates) . ")\n";

// Let's test simulation on last 40 trading dates
$recentDates = array_slice($tradingDates, -40);
$startDate = reset($recentDates);
$endDate = end($recentDates);

echo "Running test backtest from {$startDate} to {$endDate}...\n";

// 2. Load all 52W Highs in this period with stocks (using raw DB query for speed & low memory)
$highs = DB::table('daily_52w_highs')
    ->join('stocks', 'daily_52w_highs.stock_id', '=', 'stocks.id')
    ->whereBetween('daily_52w_highs.trade_date', [$startDate, $endDate])
    ->select('daily_52w_highs.stock_id', 'daily_52w_highs.trade_date', 'daily_52w_highs.ltp', 'daily_52w_highs.p_change', 'stocks.symbol', 'stocks.series')
    ->get();

echo "Total 52W High signals loaded: " . count($highs) . "\n";

// Filter out SME and circuit locked
$validSignals = [];
$stockIds = [];
foreach ($highs as $h) {
    if ($h->series !== 'EQ' || str_ends_with($h->symbol, '-SM') || str_ends_with($h->symbol, '-ST')) {
        continue;
    }
    // Upper circuit filter (p_change >= 19.5% or LTP <= 0)
    if ($h->p_change >= 19.5 || $h->ltp <= 0) {
        continue;
    }
    $h->trade_date_str = substr((string)$h->trade_date, 0, 10);
    $validSignals[] = $h;
    $stockIds[$h->stock_id] = true;
}
echo "Valid Deliverable Signals (Non-SME, Non-UC): " . count($validSignals) . "\n";

// Preload OHLCV with only essential columns
$bars = DB::table('ohlcv_bars')
    ->whereIn('stock_id', array_keys($stockIds))
    ->where('bar_date', '>=', $startDate)
    ->select('stock_id', 'bar_date', 'close', 'high', 'low')
    ->get();

// Index bars by "stock_id_date"
$priceMap = [];
$highMap = [];
$lowMap = [];
foreach ($bars as $b) {
    $d = substr((string)$b->bar_date, 0, 10);
    $key = "{$b->stock_id}_{$d}";
    $priceMap[$key] = (float)$b->close;
    $highMap[$key] = (float)$b->high;
    $lowMap[$key] = (float)$b->low;
}

echo "Indexed " . count($priceMap) . " price points in memory.\n";

$capitalPerStock = 10000;
$holdingStrategies = [
    'BTST (1-Day)' => 1,
    '5 Sessions' => 5,
    '10 Sessions' => 10,
    '15 Sessions' => 15,
    '30 Sessions' => 30,
];

$results = [];

foreach ($holdingStrategies as $stratName => $holdDays) {
    $trades = [];
    $totalPnl = 0;
    $totalInvested = 0;
    $wins = 0;
    $losses = 0;
    $grossProfit = 0;
    $grossLoss = 0;

    foreach ($validSignals as $sig) {
        $sigDate = $sig->trade_date_str;
        if (!isset($dateIndexMap[$sigDate])) continue;

        $entryIdx = $dateIndexMap[$sigDate];
        $exitIdx = $entryIdx + $holdDays;

        $entryKey = "{$sig->stock_id}_{$sigDate}";
        $entryPrice = $priceMap[$entryKey] ?? (float)$sig->ltp;
        if ($entryPrice <= 0) continue;

        $qty = (int)floor($capitalPerStock / $entryPrice);
        if ($qty < 1) continue;

        $invested = $qty * $entryPrice;

        if ($exitIdx < $totalDates) {
            $exitDate = $tradingDates[$exitIdx];
            $exitKey = "{$sig->stock_id}_{$exitDate}";
            if (isset($priceMap[$exitKey]) && $priceMap[$exitKey] > 0) {
                $exitPrice = $priceMap[$exitKey];
                $pnl = ($exitPrice - $entryPrice) * $qty;
                $pnlPct = (($exitPrice - $entryPrice) / $entryPrice) * 100;

                if ($pnl > 0) {
                    $wins++;
                    $grossProfit += $pnl;
                } elseif ($pnl < 0) {
                    $losses++;
                    $grossLoss += abs($pnl);
                }

                $totalPnl += $pnl;
                $totalInvested += $invested;
                $trades[] = [
                    'symbol' => $sig->symbol,
                    'entry_date' => $sigDate,
                    'entry_price' => $entryPrice,
                    'exit_date' => $exitDate,
                    'exit_price' => $exitPrice,
                    'qty' => $qty,
                    'pnl' => $pnl,
                    'pnl_pct' => $pnlPct,
                    'status' => $pnl >= 0 ? 'WON' : 'LOST'
                ];
            }
        }
    }

    $closedTrades = $wins + $losses;
    $winRate = $closedTrades > 0 ? round(($wins / $closedTrades) * 100, 1) : 0;
    $profitFactor = $grossLoss > 0 ? round($grossProfit / $grossLoss, 2) : ($grossProfit > 0 ? 99.9 : 0);
    $roiPct = $totalInvested > 0 ? round(($totalPnl / $totalInvested) * 100, 2) : 0;
    $avgTradePnl = $closedTrades > 0 ? round($totalPnl / $closedTrades, 2) : 0;

    $results[$stratName] = [
        'trades_count' => count($trades),
        'wins' => $wins,
        'losses' => $losses,
        'win_rate' => $winRate,
        'total_pnl' => round($totalPnl, 2),
        'profit_factor' => $profitFactor,
        'roi_pct' => $roiPct,
        'avg_trade_pnl' => $avgTradePnl,
    ];
}

$elapsed = round(microtime(true) - $startTime, 3);
echo "\n--- Simulation Results (Completed in {$elapsed}s) ---\n";
printf("%-18s | %-8s | %-8s | %-14s | %-12s | %-8s\n", "Strategy", "Trades", "Win Rate", "Net PnL (Rs)", "ROI %", "PF");
echo str_repeat('-', 75) . "\n";
foreach ($results as $s => $r) {
    printf("%-18s | %-8d | %-7s%% | Rs.%-11s | %-11s%% | %-8s\n",
        $s,
        $r['trades_count'],
        $r['win_rate'],
        number_format($r['total_pnl'], 2),
        $r['roi_pct'],
        $r['profit_factor']
    );
}
