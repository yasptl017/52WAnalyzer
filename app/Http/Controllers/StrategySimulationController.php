<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\TradeJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StrategySimulationController extends Controller
{
    /**
     * Run multi-strategy forward simulation and render comparative leaderboard.
     */
    public function index(Request $request)
    {
        ini_set('memory_limit', '512M');

        $capitalPerStock = max(100, (float)$request->get('capital', 10000));
        $universe = strtoupper($request->get('universe', '52WH'));
        if (!in_array($universe, ['52WH', 'VG', 'BOTH'])) {
            $universe = '52WH';
        }

        $excludeSme = $request->boolean('exclude_sme', true);
        $excludeUc = $request->boolean('exclude_uc', true);

        // Fetch sorted trading dates from OHLCV bars
        $tradingDates = DB::table('ohlcv_bars')
            ->distinct()
            ->orderBy('bar_date', 'asc')
            ->pluck('bar_date')
            ->map(fn($d) => substr((string)$d, 0, 10))
            ->values()
            ->toArray();

        $totalDates = count($tradingDates);
        $dateIndexMap = array_flip($tradingDates);

        $minDbDate = reset($tradingDates) ?: '2025-06-27';
        $maxDbDate = end($tradingDates) ?: '2026-09-18';

        // Default lookback: last 50 trading dates if not specified
        $recentSlice = array_slice($tradingDates, -50);
        $defaultFromDate = reset($recentSlice) ?: $minDbDate;
        
        $fromDate = $request->get('from_date', $defaultFromDate);
        $toDate = $request->get('to_date', $maxDbDate);

        // Fetch candidate signals based on Universe
        $rawSignals = $this->fetchUniverseSignals($universe, $fromDate, $toDate);

        // Filter SME and Upper Circuit
        $validSignals = [];
        $stockIds = [];
        foreach ($rawSignals as $sig) {
            if ($excludeSme) {
                if ($sig->series !== 'EQ' || str_ends_with($sig->symbol, '-SM') || str_ends_with($sig->symbol, '-ST')) {
                    continue;
                }
            }
            if ($excludeUc) {
                if ($sig->p_change >= 19.5 || $sig->ltp <= 0) {
                    continue;
                }
            }
            $sig->trade_date_str = substr((string)$sig->trade_date, 0, 10);
            $validSignals[] = $sig;
            $stockIds[$sig->stock_id] = true;
        }

        // Preload OHLCV bars for memory-mapped instant execution
        $bars = DB::table('ohlcv_bars')
            ->whereIn('stock_id', array_keys($stockIds))
            ->where('bar_date', '>=', $fromDate)
            ->select('stock_id', 'bar_date', 'open', 'high', 'low', 'close')
            ->get();

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

        // Define the 6 comparison strategies
        $strategyDefs = [
            'btst' => [
                'name' => 'BTST (1-Session Hold)',
                'hold_days' => 1,
                'type' => 'fixed',
                'description' => 'Buy Today at Close, Sell Tomorrow at Close (1 trading session hold). Quick intraday/overnight momentum capture.',
                'color' => 'amber',
                'badge' => '⚡ 1-Day BTST',
            ],
            '5d' => [
                'name' => '5 Sessions Hold (~1 Week Swing)',
                'hold_days' => 5,
                'type' => 'fixed',
                'description' => 'Exit after 5 consecutive trading sessions. Captures weekly breakout continuation waves.',
                'color' => 'blue',
                'badge' => '📅 5 Sessions (~1W)',
            ],
            '10d' => [
                'name' => '10 Sessions Hold (~2 Weeks Base)',
                'hold_days' => 10,
                'type' => 'fixed',
                'description' => 'Exit after 10 consecutive trading sessions. Allows standard swing continuation and base consolidation.',
                'color' => 'indigo',
                'badge' => '📈 10 Sessions (~2W)',
            ],
            '15d' => [
                'name' => '15 Sessions Hold (~3 Weeks Trend)',
                'hold_days' => 15,
                'type' => 'fixed',
                'description' => 'Exit after 15 consecutive trading sessions. Exploits sustained medium-term momentum trends.',
                'color' => 'emerald',
                'badge' => '🚀 15 Sessions (~3W)',
            ],
            '30d' => [
                'name' => '30 Sessions Hold (~6 Weeks Wave)',
                'hold_days' => 30,
                'type' => 'fixed',
                'description' => 'Exit after 30 consecutive trading sessions (~1.5 months). Captures major institutional markup cycles.',
                'color' => 'purple',
                'badge' => '🌊 30 Sessions (~6W)',
            ],
            'trailing' => [
                'name' => 'Smart Trailing Confluence (Pro Logic)',
                'hold_days' => 20,
                'type' => 'smart',
                'description' => '5% Trailing Stop Loss from peak high, +15% profit target take-profit, or max 20 sessions hold.',
                'color' => 'rose',
                'badge' => '🛡️ Trailing Stop (5% Peak)',
            ],
        ];

        // Execute all 6 simulations in parallel
        $strategyResults = [];
        $strategyTrades = [];

        foreach ($strategyDefs as $stratKey => $strat) {
            $trades = [];
            $totalPnl = 0;
            $totalInvested = 0;
            $wins = 0;
            $losses = 0;
            $grossProfit = 0;
            $grossLoss = 0;
            $maxWinPct = 0;
            $maxLossPct = 0;
            $totalGainPct = 0;
            $totalLossPct = 0;

            // Running equity curve for max drawdown
            $runningPnl = 0;
            $peakPnl = 0;
            $maxDd = 0;

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

                $exitDate = null;
                $exitPrice = null;
                $exitReason = 'Time Horizon';

                if ($strat['type'] === 'fixed') {
                    $exitIdx = $entryIdx + $strat['hold_days'];
                    if ($exitIdx < $totalDates) {
                        $exitDate = $tradingDates[$exitIdx];
                        $exitKey = "{$sig->stock_id}_{$exitDate}";
                        if (isset($priceMap[$exitKey]) && $priceMap[$exitKey] > 0) {
                            $exitPrice = $priceMap[$exitKey];
                        }
                    }
                } else { // smart trailing
                    $highestPrice = $entryPrice;
                    $trailingStopPct = 0.05; // 5% SL from peak
                    $targetPct = 0.15; // 15% target
                    $maxHold = 20;

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

                        if ($currHigh >= $targetPrice) {
                            $exitDate = $currDate;
                            $exitPrice = $targetPrice;
                            $exitReason = 'Target (+15%)';
                            break;
                        }

                        if ($currLow <= $trailingStopPrice) {
                            $exitDate = $currDate;
                            $exitPrice = $trailingStopPrice;
                            $exitReason = 'Trailing SL (-5%)';
                            break;
                        }

                        if ($i === $maxHold) {
                            $exitDate = $currDate;
                            $exitPrice = $currClose;
                            $exitReason = 'Max Hold (20D)';
                            break;
                        }
                    }
                }

                $status = 'OPEN';
                $pnl = null;
                $pnlPct = null;

                if ($exitPrice !== null && $exitDate !== null) {
                    $pnl = ($exitPrice - $entryPrice) * $qty;
                    $pnlPct = (($exitPrice - $entryPrice) / $entryPrice) * 100;
                    $status = $pnl >= 0 ? 'WON' : 'LOST';

                    if ($pnl > 0) {
                        $wins++;
                        $grossProfit += $pnl;
                        $totalGainPct += $pnlPct;
                        if ($pnlPct > $maxWinPct) $maxWinPct = $pnlPct;
                    } elseif ($pnl < 0) {
                        $losses++;
                        $grossLoss += abs($pnl);
                        $totalLossPct += abs($pnlPct);
                        if (abs($pnlPct) > $maxLossPct) $maxLossPct = abs($pnlPct);
                    }

                    $totalPnl += $pnl;
                    $totalInvested += $invested;

                    // Update Drawdown calculation
                    $runningPnl += $pnl;
                    if ($runningPnl > $peakPnl) {
                        $peakPnl = $runningPnl;
                    }
                    $dd = $peakPnl - $runningPnl;
                    if ($dd > $maxDd) {
                        $maxDd = $dd;
                    }
                }

                $trades[] = [
                    'stock_id' => $sig->stock_id,
                    'symbol' => $sig->symbol,
                    'series' => $sig->series,
                    'entry_date' => $sigDate,
                    'entry_price' => round($entryPrice, 2),
                    'exit_date' => $exitDate,
                    'exit_price' => $exitPrice ? round($exitPrice, 2) : null,
                    'exit_reason' => $exitReason,
                    'quantity' => $qty,
                    'invested' => round($invested, 2),
                    'pnl' => $pnl !== null ? round($pnl, 2) : null,
                    'pnl_pct' => $pnlPct !== null ? round($pnlPct, 2) : null,
                    'status' => $status,
                ];
            }

            $closedTrades = $wins + $losses;
            $winRate = $closedTrades > 0 ? round(($wins / $closedTrades) * 100, 1) : 0;
            $profitFactor = $grossLoss > 0 ? round($grossProfit / $grossLoss, 2) : ($grossProfit > 0 ? 99.9 : 0);
            $roiPct = $totalInvested > 0 ? round(($totalPnl / $totalInvested) * 100, 2) : 0;
            $avgTradePnl = $closedTrades > 0 ? round($totalPnl / $closedTrades, 2) : 0;
            $avgGainPct = $wins > 0 ? round($totalGainPct / $wins, 2) : 0;
            $avgLossPct = $losses > 0 ? round($totalLossPct / $losses, 2) : 0;
            $rrRatio = $avgLossPct > 0 ? round($avgGainPct / $avgLossPct, 2) : 0;

            // Composite Reward Score = ROI% * Profit Factor * (Win Rate / 50)
            $rewardScore = round($roiPct * max(0.1, $profitFactor) * ($winRate / 50), 2);

            $strategyResults[$stratKey] = [
                'key' => $stratKey,
                'name' => $strat['name'],
                'description' => $strat['description'],
                'color' => $strat['color'],
                'badge' => $strat['badge'],
                'total_trades' => count($trades),
                'closed_trades' => $closedTrades,
                'open_trades' => count($trades) - $closedTrades,
                'wins' => $wins,
                'losses' => $losses,
                'win_rate' => $winRate,
                'total_pnl' => round($totalPnl, 2),
                'total_invested' => round($totalInvested, 2),
                'gross_profit' => round($grossProfit, 2),
                'gross_loss' => round($grossLoss, 2),
                'profit_factor' => $profitFactor,
                'roi_pct' => $roiPct,
                'avg_trade_pnl' => $avgTradePnl,
                'avg_gain_pct' => $avgGainPct,
                'avg_loss_pct' => $avgLossPct,
                'rr_ratio' => $rrRatio,
                'max_win_pct' => round($maxWinPct, 2),
                'max_loss_pct' => round($maxLossPct, 2),
                'max_drawdown_amount' => round($maxDd, 2),
                'reward_score' => $rewardScore,
            ];

            $strategyTrades[$stratKey] = $trades;
        }

        // Determine Champion (Most Favorable Strategy for Reward)
        $rankedStrategies = collect($strategyResults)->sortByDesc('reward_score')->values()->all();
        $champion = $rankedStrategies[0] ?? null;

        // Active Strategy Tab for Drill-down
        $activeStratKey = $request->get('active_strat', $champion['key'] ?? '15d');
        if (!isset($strategyResults[$activeStratKey])) {
            $activeStratKey = '15d';
        }

        // Filter and Paginate Trades for Active Strategy
        $filteredTrades = collect($strategyTrades[$activeStratKey] ?? []);

        // Filter by Search Symbol
        if ($search = strtoupper(trim($request->get('search', '')))) {
            $filteredTrades = $filteredTrades->filter(fn($t) => str_contains($t['symbol'], $search));
        }

        // Filter by Status
        $statusFilter = strtoupper($request->get('status', 'ALL'));
        if ($statusFilter !== 'ALL') {
            $filteredTrades = $filteredTrades->filter(fn($t) => $t['status'] === $statusFilter);
        }

        // Slicing for Pagination
        $perPage = 25;
        $currentPage = max(1, (int)$request->get('page', 1));
        $totalFiltered = $filteredTrades->count();
        $paginatedTrades = $filteredTrades->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $totalPages = (int)ceil($totalFiltered / $perPage);

        return view('strategy_simulation.index', compact(
            'capitalPerStock',
            'universe',
            'excludeSme',
            'excludeUc',
            'fromDate',
            'toDate',
            'minDbDate',
            'maxDbDate',
            'strategyResults',
            'rankedStrategies',
            'champion',
            'activeStratKey',
            'paginatedTrades',
            'totalFiltered',
            'currentPage',
            'totalPages',
            'perPage',
            'search',
            'statusFilter'
        ));
    }

    /**
     * Batch log simulated strategy trades directly into the user's active Trade Journal.
     */
    public function forwardTestJournal(Request $request)
    {
        $validated = validator($request->all(), [
            'strategy_key' => 'required|string',
            'universe' => 'required|string',
            'capital' => 'required|numeric|min:100',
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'mode' => 'required|in:ALL,OPEN_ONLY,CLOSED_ONLY',
        ])->validate();

        // Re-run the simulation logic for the requested strategy
        $simRequest = new Request([
            'capital' => $validated['capital'],
            'universe' => $validated['universe'],
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
            'active_strat' => $validated['strategy_key'],
            'exclude_sme' => true,
            'exclude_uc' => true,
        ]);

        // Fetch trading dates & price bars
        $tradingDates = DB::table('ohlcv_bars')
            ->distinct()
            ->orderBy('bar_date', 'asc')
            ->pluck('bar_date')
            ->map(fn($d) => substr((string)$d, 0, 10))
            ->values()
            ->toArray();

        $totalDates = count($tradingDates);
        $dateIndexMap = array_flip($tradingDates);

        $rawSignals = $this->fetchUniverseSignals($validated['universe'], $validated['from_date'], $validated['to_date']);

        $validSignals = [];
        $stockIds = [];
        foreach ($rawSignals as $sig) {
            if ($sig->series !== 'EQ' || str_ends_with($sig->symbol, '-SM') || str_ends_with($sig->symbol, '-ST')) {
                continue;
            }
            if ($sig->p_change >= 19.5 || $sig->ltp <= 0) {
                continue;
            }
            $sig->trade_date_str = substr((string)$sig->trade_date, 0, 10);
            $validSignals[] = $sig;
            $stockIds[$sig->stock_id] = true;
        }

        $bars = DB::table('ohlcv_bars')
            ->whereIn('stock_id', array_keys($stockIds))
            ->where('bar_date', '>=', $validated['from_date'])
            ->select('stock_id', 'bar_date', 'open', 'high', 'low', 'close')
            ->get();

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

        $stratKey = $validated['strategy_key'];
        $holdDaysMap = ['btst' => 1, '5d' => 5, '10d' => 10, '15d' => 15, '30d' => 30];
        $holdDays = $holdDaysMap[$stratKey] ?? 15;

        $insertedCount = 0;

        foreach ($validSignals as $sig) {
            $sigDate = $sig->trade_date_str;
            if (!isset($dateIndexMap[$sigDate])) continue;

            $entryIdx = $dateIndexMap[$sigDate];
            $entryKey = "{$sig->stock_id}_{$sigDate}";
            $entryPrice = $priceMap[$entryKey] ?? (float)$sig->ltp;
            if ($entryPrice <= 0) continue;

            $qty = (int)floor($validated['capital'] / $entryPrice);
            if ($qty < 1) continue;

            $exitDate = null;
            $exitPrice = null;
            $status = 'OPEN';
            $pnl = null;

            if ($stratKey === 'trailing') {
                $highestPrice = $entryPrice;
                for ($i = 1; $i <= 20; $i++) {
                    $currIdx = $entryIdx + $i;
                    if ($currIdx >= $totalDates) break;
                    $currDate = $tradingDates[$currIdx];
                    $currKey = "{$sig->stock_id}_{$currDate}";
                    if (!isset($priceMap[$currKey])) continue;

                    $currHigh = $highMap[$currKey] ?? $priceMap[$currKey];
                    $currLow = $lowMap[$currKey] ?? $priceMap[$currKey];
                    $currClose = $priceMap[$currKey];

                    if ($currHigh > $highestPrice) $highestPrice = $currHigh;

                    if ($currHigh >= $entryPrice * 1.15) {
                        $exitDate = $currDate;
                        $exitPrice = $entryPrice * 1.15;
                        break;
                    }
                    if ($currLow <= $highestPrice * 0.95) {
                        $exitDate = $currDate;
                        $exitPrice = $highestPrice * 0.95;
                        break;
                    }
                    if ($i === 20) {
                        $exitDate = $currDate;
                        $exitPrice = $currClose;
                        break;
                    }
                }
            } else {
                $exitIdx = $entryIdx + $holdDays;
                if ($exitIdx < $totalDates) {
                    $exitDate = $tradingDates[$exitIdx];
                    $exitKey = "{$sig->stock_id}_{$exitDate}";
                    if (isset($priceMap[$exitKey]) && $priceMap[$exitKey] > 0) {
                        $exitPrice = $priceMap[$exitKey];
                    }
                }
            }

            if ($exitPrice !== null && $exitDate !== null) {
                $pnl = ($exitPrice - $entryPrice) * $qty;
                $status = $pnl >= 0 ? 'WON' : 'LOST';
            }

            // Mode filter
            if ($validated['mode'] === 'OPEN_ONLY' && $status !== 'OPEN') continue;
            if ($validated['mode'] === 'CLOSED_ONLY' && $status === 'OPEN') continue;

            // Ensure stock exists
            $stock = Stock::firstOrCreate(
                ['symbol' => $sig->symbol],
                ['series' => $sig->series ?? 'EQ', 'asset_class' => 'EQUITY']
            );

            // Calculate Stop Loss & Target
            $stopLoss = round($entryPrice * 0.95, 2);
            $targetPrice = round($entryPrice * 1.15, 2);

            TradeJournal::create([
                'stock_id' => $stock->id,
                'entry_date' => $sigDate,
                'entry_price' => round($entryPrice, 2),
                'stop_loss' => $stopLoss,
                'target_price' => $targetPrice,
                'quantity' => $qty,
                'status' => $status,
                'exit_date' => $exitDate,
                'exit_price' => $exitPrice ? round($exitPrice, 2) : null,
                'pnl' => $pnl !== null ? round($pnl, 2) : null,
                'notes' => "Auto Forward-Test [{$validated['universe']}] Strategy: {$stratKey} (Budget: Rs.{$validated['capital']})",
            ]);

            $insertedCount++;
            if ($insertedCount >= 500) break; // Safety cap
        }

        return redirect()->route('journal.index')->with('success', "🚀 Successfully logged {$insertedCount} forward-test trades from Strategy [{$stratKey}] into your Trade Journal!");
    }

    /**
     * Export simulated trades to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $capital = (float)$request->get('capital', 10000);
        $universe = $request->get('universe', '52WH');
        $stratKey = $request->get('active_strat', '15d');

        $filename = "forward_test_simulation_{$universe}_{$stratKey}_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Symbol', 'Series', 'Entry Date', 'Entry Price (Rs)', 'Quantity', 'Invested (Rs)', 'Exit Date', 'Exit Price (Rs)', 'Hold Days', 'Realized PnL (Rs)', 'ROI %', 'Status']);

            // Simple demonstration stream
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Helper to fetch signals based on Universe selection.
     */
    private function fetchUniverseSignals(string $universe, string $fromDate, string $toDate)
    {
        if ($universe === '52WH') {
            return DB::table('daily_52w_highs')
                ->join('stocks', 'daily_52w_highs.stock_id', '=', 'stocks.id')
                ->whereBetween('daily_52w_highs.trade_date', [$fromDate, $toDate])
                ->select(
                    'daily_52w_highs.stock_id',
                    'daily_52w_highs.trade_date',
                    'daily_52w_highs.ltp',
                    'daily_52w_highs.p_change',
                    'stocks.symbol',
                    'stocks.series'
                )
                ->get();
        } elseif ($universe === 'VG') {
            return DB::table('daily_volume_gainers')
                ->join('stocks', 'daily_volume_gainers.stock_id', '=', 'stocks.id')
                ->whereBetween('daily_volume_gainers.trade_date', [$fromDate, $toDate])
                ->select(
                    'daily_volume_gainers.stock_id',
                    'daily_volume_gainers.trade_date',
                    'daily_volume_gainers.ltp',
                    'daily_volume_gainers.p_change',
                    'stocks.symbol',
                    'stocks.series'
                )
                ->get();
        } else { // BOTH (Confluence)
            return DB::table('daily_52w_highs')
                ->join('daily_volume_gainers', function ($join) {
                    $join->on('daily_52w_highs.stock_id', '=', 'daily_volume_gainers.stock_id')
                         ->on('daily_52w_highs.trade_date', '=', 'daily_volume_gainers.trade_date');
                })
                ->join('stocks', 'daily_52w_highs.stock_id', '=', 'stocks.id')
                ->whereBetween('daily_52w_highs.trade_date', [$fromDate, $toDate])
                ->select(
                    'daily_52w_highs.stock_id',
                    'daily_52w_highs.trade_date',
                    'daily_52w_highs.ltp',
                    'daily_52w_highs.p_change',
                    'stocks.symbol',
                    'stocks.series'
                )
                ->get();
        }
    }
}
