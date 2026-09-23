<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

ini_set('memory_limit', '512M');

$tradingDates = DB::table('ohlcv_bars')
    ->distinct()
    ->orderBy('bar_date', 'asc')
    ->pluck('bar_date')
    ->map(fn($d) => substr((string)$d, 0, 10))
    ->values()
    ->toArray();

$dateIndexMap = array_flip($tradingDates);
$totalDates = count($tradingDates);

$recentDates = array_slice($tradingDates, -50);
$startDate = reset($recentDates);
$endDate = end($recentDates);

echo "Universe Test: {$startDate} to {$endDate}\n";

$universes = ['52WH', 'VG', 'BOTH'];

foreach ($universes as $univ) {
    if ($univ === '52WH') {
        $raw = DB::table('daily_52w_highs')
            ->join('stocks', 'daily_52w_highs.stock_id', '=', 'stocks.id')
            ->whereBetween('daily_52w_highs.trade_date', [$startDate, $endDate])
            ->select('daily_52w_highs.stock_id', 'daily_52w_highs.trade_date', 'daily_52w_highs.ltp', 'daily_52w_highs.p_change', 'stocks.symbol', 'stocks.series')
            ->get();
    } elseif ($univ === 'VG') {
        $raw = DB::table('daily_volume_gainers')
            ->join('stocks', 'daily_volume_gainers.stock_id', '=', 'stocks.id')
            ->whereBetween('daily_volume_gainers.trade_date', [$startDate, $endDate])
            ->select('daily_volume_gainers.stock_id', 'daily_volume_gainers.trade_date', 'daily_volume_gainers.ltp', 'daily_volume_gainers.p_change', 'stocks.symbol', 'stocks.series')
            ->get();
    } else { // BOTH
        $raw = DB::table('daily_52w_highs')
            ->join('daily_volume_gainers', function ($join) {
                $join->on('daily_52w_highs.stock_id', '=', 'daily_volume_gainers.stock_id')
                     ->on('daily_52w_highs.trade_date', '=', 'daily_volume_gainers.trade_date');
            })
            ->join('stocks', 'daily_52w_highs.stock_id', '=', 'stocks.id')
            ->whereBetween('daily_52w_highs.trade_date', [$startDate, $endDate])
            ->select('daily_52w_highs.stock_id', 'daily_52w_highs.trade_date', 'daily_52w_highs.ltp', 'daily_52w_highs.p_change', 'stocks.symbol', 'stocks.series')
            ->get();
    }

    $valid = [];
    $stockIds = [];
    foreach ($raw as $r) {
        if ($r->series !== 'EQ' || str_ends_with($r->symbol, '-SM') || str_ends_with($r->symbol, '-ST')) continue;
        if ($r->p_change >= 19.5 || $r->ltp <= 0) continue;
        $r->trade_date_str = substr((string)$r->trade_date, 0, 10);
        $valid[] = $r;
        $stockIds[$r->stock_id] = true;
    }

    $bars = DB::table('ohlcv_bars')
        ->whereIn('stock_id', array_keys($stockIds))
        ->where('bar_date', '>=', $startDate)
        ->select('stock_id', 'bar_date', 'close')
        ->get();

    $priceMap = [];
    foreach ($bars as $b) {
        $d = substr((string)$b->bar_date, 0, 10);
        $priceMap["{$b->stock_id}_{$d}"] = (float)$b->close;
    }

    echo "\n=== Universe: {$univ} (Signals: " . count($valid) . ") ===\n";
    foreach ([1 => 'BTST (1D)', 5 => '5 Sessions', 10 => '10 Sessions', 15 => '15 Sessions', 30 => '30 Sessions'] as $hold => $label) {
        $wins = 0; $losses = 0; $pnlTotal = 0; $invTotal = 0; $gp = 0; $gl = 0;
        foreach ($valid as $s) {
            $sigDate = $s->trade_date_str;
            if (!isset($dateIndexMap[$sigDate])) continue;
            $entryIdx = $dateIndexMap[$sigDate];
            $exitIdx = $entryIdx + $hold;
            $entryPrice = $priceMap["{$s->stock_id}_{$sigDate}"] ?? (float)$s->ltp;
            if ($entryPrice <= 0) continue;
            $qty = (int)floor(10000 / $entryPrice);
            if ($qty < 1) continue;
            if ($exitIdx < $totalDates) {
                $exitDate = $tradingDates[$exitIdx];
                $exitPrice = $priceMap["{$s->stock_id}_{$exitDate}"] ?? 0;
                if ($exitPrice > 0) {
                    $pnl = ($exitPrice - $entryPrice) * $qty;
                    if ($pnl > 0) { $wins++; $gp += $pnl; }
                    elseif ($pnl < 0) { $losses++; $gl += abs($pnl); }
                    $pnlTotal += $pnl;
                    $invTotal += ($qty * $entryPrice);
                }
            }
        }
        $closed = $wins + $losses;
        $winRate = $closed > 0 ? round(($wins / $closed) * 100, 1) : 0;
        $roi = $invTotal > 0 ? round(($pnlTotal / $invTotal) * 100, 2) : 0;
        $pf = $gl > 0 ? round($gp / $gl, 2) : 99.9;
        printf("%-15s | Trades: %-6d | Win Rate: %-5s%% | Net PnL: Rs.%-10s | ROI: %-6s%% | PF: %-5s\n",
            $label, $closed, $winRate, number_format($pnlTotal, 0), $roi, $pf);
    }
}
