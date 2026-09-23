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

$recentDates = array_slice($tradingDates, -40);
$startDate = reset($recentDates);
$endDate = end($recentDates);

$highs = DB::table('daily_52w_highs')
    ->join('stocks', 'daily_52w_highs.stock_id', '=', 'stocks.id')
    ->whereBetween('daily_52w_highs.trade_date', [$startDate, $endDate])
    ->select('daily_52w_highs.stock_id', 'daily_52w_highs.trade_date', 'daily_52w_highs.ltp', 'daily_52w_highs.p_change', 'stocks.symbol', 'stocks.series')
    ->get();

$validSignals = [];
$stockIds = [];
foreach ($highs as $h) {
    if ($h->series !== 'EQ' || str_ends_with($h->symbol, '-SM') || str_ends_with($h->symbol, '-ST')) {
        continue;
    }
    if ($h->p_change >= 19.5 || $h->ltp <= 0) {
        continue;
    }
    $h->trade_date_str = substr((string)$h->trade_date, 0, 10);
    $validSignals[] = $h;
    $stockIds[$h->stock_id] = true;
}

$bars = DB::table('ohlcv_bars')
    ->whereIn('stock_id', array_keys($stockIds))
    ->where('bar_date', '>=', $startDate)
    ->select('stock_id', 'bar_date', 'open', 'high', 'low', 'close')
    ->get();

$priceMap = [];
$highMap = [];
$lowMap = [];
$openMap = [];
foreach ($bars as $b) {
    $d = substr((string)$b->bar_date, 0, 10);
    $key = "{$b->stock_id}_{$d}";
    $priceMap[$key] = (float)$b->close;
    $highMap[$key] = (float)$b->high;
    $lowMap[$key] = (float)$b->low;
    $openMap[$key] = (float)$b->open;
}

$capitalPerStock = 10000;

// Smart Trailing Stop: 5% trailing stop from highest high, +15% profit target, max 20 sessions
$tradesSmart = [];
$totalPnlSmart = 0;
$totalInvestedSmart = 0;
$winsSmart = 0;
$lossesSmart = 0;
$grossProfitSmart = 0;
$grossLossSmart = 0;

foreach ($validSignals as $sig) {
    $sigDate = $sig->trade_date_str;
    if (!isset($dateIndexMap[$sigDate])) continue;

    $entryIdx = $dateIndexMap[$sigDate];
    $entryKey = "{$sig->stock_id}_{$sigDate}";
    $entryPrice = $priceMap[$entryKey] ?? (float)$sig->ltp;
    if ($entryPrice <= 0) continue;

    $qty = (int)floor($capitalPerStock / $entryPrice);
    if ($qty < 1) continue;
    $invested = $qty * $entryPrice;

    $highestPrice = $entryPrice;
    $trailingStopPct = 0.05; // 5% trailing SL
    $targetPct = 0.15; // 15% target
    $maxHold = 20;

    $exitDate = null;
    $exitPrice = null;
    $exitReason = null;

    for ($i = 1; $i <= $maxHold; $i++) {
        $currIdx = $entryIdx + $i;
        if ($currIdx >= $totalDates) break;

        $currDate = $tradingDates[$currIdx];
        $currKey = "{$sig->stock_id}_{$currDate}";
        if (!isset($priceMap[$currKey])) continue;

        $currHigh = $highMap[$currKey] ?? $priceMap[$currKey];
        $currLow = $lowMap[$currKey] ?? $priceMap[$currKey];
        $currClose = $priceMap[$currKey];

        if ($currHigh > $highestPrice) {
            $highestPrice = $currHigh;
        }

        $trailingStopPrice = $highestPrice * (1 - $trailingStopPct);
        $targetPrice = $entryPrice * (1 + $targetPct);

        // Check if target hit
        if ($currHigh >= $targetPrice) {
            $exitDate = $currDate;
            $exitPrice = $targetPrice;
            $exitReason = 'Target (+15%)';
            break;
        }

        // Check if trailing SL hit
        if ($currLow <= $trailingStopPrice) {
            $exitDate = $currDate;
            $exitPrice = $trailingStopPrice;
            $exitReason = 'Trailing SL (-5% from peak)';
            break;
        }

        // If at max hold
        if ($i === $maxHold) {
            $exitDate = $currDate;
            $exitPrice = $currClose;
            $exitReason = 'Max Hold (20D)';
            break;
        }
    }

    if ($exitPrice !== null && $exitDate !== null) {
        $pnl = ($exitPrice - $entryPrice) * $qty;
        $pnlPct = (($exitPrice - $entryPrice) / $entryPrice) * 100;

        if ($pnl > 0) {
            $winsSmart++;
            $grossProfitSmart += $pnl;
        } elseif ($pnl < 0) {
            $lossesSmart++;
            $grossLossSmart += abs($pnl);
        }

        $totalPnlSmart += $pnl;
        $totalInvestedSmart += $invested;
    }
}

$closedSmart = $winsSmart + $lossesSmart;
$winRateSmart = $closedSmart > 0 ? round(($winsSmart / $closedSmart) * 100, 1) : 0;
$pfSmart = $grossLossSmart > 0 ? round($grossProfitSmart / $grossLossSmart, 2) : 99.9;
$roiSmart = $totalInvestedSmart > 0 ? round(($totalPnlSmart / $totalInvestedSmart) * 100, 2) : 0;

echo "Smart Trailing Confluence: Trades: {$closedSmart}, Win Rate: {$winRateSmart}%, Net PnL: Rs." . number_format($totalPnlSmart, 2) . ", ROI: {$roiSmart}%, Profit Factor: {$pfSmart}\n";
