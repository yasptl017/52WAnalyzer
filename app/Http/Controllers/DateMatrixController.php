<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Daily52wHigh;
use App\Models\DailyVolumeGainer;
use App\Models\Stock;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DateMatrixController extends Controller
{
    /**
     * Display the date-wise matrix view.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $tab = $request->get('tab', '52wh'); // '52wh', 'vg', 'combined'
        $days = (int) $request->get('days', 10);
        
        $maxAllowedDays = ($user && $user->isPro()) ? 60 : 7;
        
        // Starter tier has 7-Day Date Matrix Lookback limit
        if ($days > $maxAllowedDays) {
            $days = $maxAllowedDays;
        }

        if ($days < 3) $days = 3;

        $sort = $request->get('sort', 'streak_surge'); // 'streak_surge', 'p_change', 'symbol', 'turnover'
        $search = trim($request->get('search', ''));

        // 1. Get the list of distinct available trading dates based on the active tab
        $availableDates = $this->getAvailableDates($tab, $days);

        // 2. Build the matrix data for the selected dates
        $matrixData = [];
        $uniqueSymbols = [];
        $symbolAppearanceCounts = [];
        $maxRows = 0;

        foreach ($availableDates as $dateStr) {
            $columnItems = $this->getColumnItemsForDate($tab, $dateStr, $sort, $search);
            $matrixData[$dateStr] = $columnItems;
            
            if (count($columnItems) > $maxRows) {
                $maxRows = count($columnItems);
            }

            foreach ($columnItems as $item) {
                $sym = $item['symbol'];
                $uniqueSymbols[$sym] = true;
                $symbolAppearanceCounts[$sym] = ($symbolAppearanceCounts[$sym] ?? 0) + 1;
            }
        }

        // Sort symbols by appearance frequency for top runners leaderboard
        arsort($symbolAppearanceCounts);
        $topRepeatedStocks = array_slice($symbolAppearanceCounts, 0, 8, true);

        // 3. Group and aggregate stocks by category for interactive pop-up lists
        $categorizedStocks = [];
        foreach ($matrixData as $dateStr => $items) {
            foreach ($items as $item) {
                $cat = $item['category'];
                $sym = $item['symbol'];
                if (!isset($categorizedStocks[$cat])) {
                    $categorizedStocks[$cat] = [
                        'stocks' => [],
                        'total_appearances' => 0,
                    ];
                }
                $categorizedStocks[$cat]['total_appearances']++;
                if (!isset($categorizedStocks[$cat]['stocks'][$sym])) {
                    $categorizedStocks[$cat]['stocks'][$sym] = [
                        'symbol' => $sym,
                        'company_name' => $item['company_name'],
                        'sector' => $item['sector'] ?? 'General',
                        'ltp' => $item['ltp'],
                        'p_change' => $item['p_change'],
                        'badge_text' => $item['badge_text'],
                        'consecutive_days' => $item['consecutive_days'] ?? null,
                        'multiplier' => $item['multiplier'] ?? null,
                        'dates' => [],
                    ];
                }
                $categorizedStocks[$cat]['stocks'][$sym]['dates'][] = Carbon::parse($dateStr)->format('d-M-Y');
            }
        }

        // Convert stocks mapping to indexed arrays sorted by appearance count / momentum
        foreach ($categorizedStocks as $cat => &$catData) {
            $stockList = array_values($catData['stocks']);
            usort($stockList, function ($a, $b) {
                $cntA = count($a['dates']);
                $cntB = count($b['dates']);
                if ($cntA !== $cntB) return $cntB <=> $cntA;
                return $b['p_change'] <=> $a['p_change'];
            });
            $catData['unique_count'] = count($stockList);
            $catData['stocks'] = $stockList;
        }
        unset($catData);

        // Calculate summary stats
        $totalAppearances = array_sum(array_map('count', $matrixData));
        $avgStocksPerDay = count($availableDates) > 0 ? round($totalAppearances / count($availableDates), 1) : 0;
        $uniqueCount = count($uniqueSymbols);

        return view('date_matrix.index', compact(
            'tab',
            'days',
            'sort',
            'search',
            'availableDates',
            'matrixData',
            'maxRows',
            'uniqueCount',
            'totalAppearances',
            'avgStocksPerDay',
            'topRepeatedStocks',
            'categorizedStocks'
        ));
    }

    /**
     * Export the current matrix data as a CSV matching the spreadsheet layout.
     */
    public function exportCsv(Request $request)
    {
        $tab = $request->get('tab', '52wh');
        $days = (int) $request->get('days', 15);
        $sort = $request->get('sort', 'streak_surge');

        $availableDates = $this->getAvailableDates($tab, $days);
        $matrixData = [];
        $maxRows = 0;

        foreach ($availableDates as $dateStr) {
            $columnItems = $this->getColumnItemsForDate($tab, $dateStr, $sort);
            $matrixData[$dateStr] = $columnItems;
            if (count($columnItems) > $maxRows) {
                $maxRows = count($columnItems);
            }
        }

        $filename = "52WAnalyzer_{$tab}_matrix_" . now()->format('Y-m-d') . ".csv";

        return new StreamedResponse(function () use ($availableDates, $matrixData, $maxRows) {
            $handle = fopen('php://output', 'w');
            
            // Header Row: Formatted Dates (e.g. 18-Sep-2026)
            $headerRow = array_map(function ($d) {
                return Carbon::parse($d)->format('d-M-Y');
            }, $availableDates);
            fputcsv($handle, $headerRow);

            // Data Rows: Stock Symbols under each date column
            for ($i = 0; $i < $maxRows; $i++) {
                $row = [];
                foreach ($availableDates as $dateStr) {
                    $item = $matrixData[$dateStr][$i] ?? null;
                    $row[] = $item ? $item['symbol'] : '';
                }
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Get distinct dates for the selected tab.
     */
    private function getAvailableDates(string $tab, int $limit): array
    {
        if ($tab === 'vg') {
            return DailyVolumeGainer::distinct()
                ->orderBy('trade_date', 'desc')
                ->take($limit)
                ->pluck('trade_date')
                ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
                ->toArray();
        }

        return Daily52wHigh::distinct()
            ->orderBy('trade_date', 'desc')
            ->take($limit)
            ->pluck('trade_date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();
    }

    /**
     * Retrieve and format items for a specific date column.
     */
    private function getColumnItemsForDate(string $tab, string $dateStr, string $sort, string $search = ''): array
    {
        if ($tab === '52wh') {
            return $this->get52whColumn($dateStr, $sort, $search);
        } elseif ($tab === 'vg') {
            return $this->getVgColumn($dateStr, $sort, $search);
        } else {
            return $this->getCombinedColumn($dateStr, $sort, $search);
        }
    }

    /**
     * 52-Week High column items.
     */
    private function get52whColumn(string $dateStr, string $sort, string $search): array
    {
        // Get all VG stock IDs on this date for confluence check
        $vgStockIds = DailyVolumeGainer::where('trade_date', $dateStr)
            ->pluck('stock_id')
            ->flip()
            ->toArray();

        $query = Daily52wHigh::with('stock')
            ->where('trade_date', $dateStr);

        if ($search !== '') {
            $query->whereHas('stock', function ($q) use ($search) {
                $q->where('symbol', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        $records = $query->get();

        $items = [];
        foreach ($records as $r) {
            if (!$r->stock) continue;

            $isDual = isset($vgStockIds[$r->stock_id]);
            $streak = (int) $r->consecutive_days;
            $isNew = (bool) $r->is_new || $streak <= 1;

            // Determine category / color scheme
            if ($isDual) {
                $category = 'dual'; // Violet / Purple (Confluence)
                $badgeText = '⚡ 52W + VG';
            } elseif ($streak >= 5) {
                $category = 'super_streak'; // Gold / Amber
                $badgeText = "🔥 {$streak}d Streak";
            } elseif ($streak >= 2) {
                $category = 'active_streak'; // Emerald / Green
                $badgeText = "★ {$streak}d Streak";
            } elseif ($isNew) {
                $category = 'new_breakout'; // Sky / Blue
                $badgeText = '🚀 New Breakout';
            } else {
                $category = 'standard';
                $badgeText = "{$streak}d";
            }

            $items[] = [
                'symbol' => $r->stock->symbol,
                'company_name' => $r->stock->company_name,
                'sector' => $r->stock->sector,
                'ltp' => (float) $r->ltp,
                'p_change' => (float) $r->p_change,
                'consecutive_days' => $streak,
                'is_new' => $isNew,
                'is_dual' => $isDual,
                'category' => $category,
                'badge_text' => $badgeText,
                'prev_52w_high' => (float) $r->prev_52w_high,
                'stock_id' => $r->stock_id,
            ];
        }

        // Sort items
        return $this->sortItems($items, $sort, '52wh');
    }

    /**
     * Volume Gainers column items.
     */
    private function getVgColumn(string $dateStr, string $sort, string $search): array
    {
        // Get all 52W stock IDs on this date for confluence check
        $high52StockIds = Daily52wHigh::where('trade_date', $dateStr)
            ->pluck('stock_id')
            ->flip()
            ->toArray();

        $query = DailyVolumeGainer::with('stock')
            ->where('trade_date', $dateStr);

        if ($search !== '') {
            $query->whereHas('stock', function ($q) use ($search) {
                $q->where('symbol', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        $records = $query->get();

        $items = [];
        foreach ($records as $r) {
            if (!$r->stock) continue;

            $isDual = isset($high52StockIds[$r->stock_id]);
            $w1 = (float) $r->week1_change;
            $w2 = (float) $r->week2_change;
            $maxChg = max($w1, $w2);
            $multiplier = round($maxChg / 100 + 1, 1);
            if ($multiplier < 1) $multiplier = 1.0;

            // Determine category / color scheme
            if ($isDual) {
                $category = 'dual'; // Violet / Purple (Confluence)
                $badgeText = "⚡ {$multiplier}x + 52WH";
            } elseif ($multiplier >= 10.0 || $maxChg >= 900) {
                $category = 'mega_surge'; // Flame Red / Rose
                $badgeText = "💥 {$multiplier}x Spurt";
            } elseif ($multiplier >= 5.0 || $maxChg >= 400) {
                $category = 'high_surge'; // Amber / Orange
                $badgeText = "🔥 {$multiplier}x Surge";
            } elseif ($multiplier >= 2.0 || $maxChg >= 100) {
                $category = 'mod_surge'; // Emerald / Green
                $badgeText = "⚡ {$multiplier}x Volume";
            } else {
                $category = 'standard';
                $badgeText = "{$multiplier}x";
            }

            $items[] = [
                'symbol' => $r->stock->symbol,
                'company_name' => $r->stock->company_name,
                'sector' => $r->stock->sector,
                'ltp' => (float) $r->ltp,
                'p_change' => (float) $r->p_change,
                'multiplier' => $multiplier,
                'week1_change' => $w1,
                'week2_change' => $w2,
                'sessions_seen' => (int) $r->sessions_seen,
                'confidence' => $r->confidence,
                'turnover_lakhs' => (float) $r->turnover_lakhs,
                'is_dual' => $isDual,
                'category' => $category,
                'badge_text' => $badgeText,
                'stock_id' => $r->stock_id,
            ];
        }

        // Sort items
        return $this->sortItems($items, $sort, 'vg');
    }

    /**
     * Combined Confluence column items (Stocks present on BOTH lists).
     */
    private function getCombinedColumn(string $dateStr, string $sort, string $search): array
    {
        $high52Records = Daily52wHigh::with('stock')
            ->where('trade_date', $dateStr)
            ->get()
            ->keyBy('stock_id');

        $vgRecords = DailyVolumeGainer::with('stock')
            ->where('trade_date', $dateStr)
            ->get()
            ->keyBy('stock_id');

        // Find intersecting stock IDs
        $intersectingStockIds = $high52Records->keys()->intersect($vgRecords->keys());

        $items = [];
        foreach ($intersectingStockIds as $stockId) {
            $h = $high52Records[$stockId];
            $v = $vgRecords[$stockId];
            if (!$h->stock) continue;

            if ($search !== '') {
                if (stripos($h->stock->symbol, $search) === false && stripos($h->stock->company_name, $search) === false) {
                    continue;
                }
            }

            $streak = (int) $h->consecutive_days;
            $w1 = (float) $v->week1_change;
            $w2 = (float) $v->week2_change;
            $maxChg = max($w1, $w2);
            $multiplier = round($maxChg / 100 + 1, 1);
            if ($multiplier < 1) $multiplier = 1.0;

            // Category for combined confluence
            if ($streak >= 3 && $multiplier >= 3.0) {
                $category = 'elite_dual'; // Gold / Crown
                $badgeText = "👑 {$streak}d Streak • {$multiplier}x VG";
            } elseif ($h->is_new && $multiplier >= 2.0) {
                $category = 'fresh_dual'; // Cyan / Indigo
                $badgeText = "🚀 Day 1 • {$multiplier}x VG";
            } else {
                $category = 'standard_dual'; // Violet / Purple
                $badgeText = "⚡ {$streak}d • {$multiplier}x";
            }

            $items[] = [
                'symbol' => $h->stock->symbol,
                'company_name' => $h->stock->company_name,
                'sector' => $h->stock->sector,
                'ltp' => (float) $h->ltp,
                'p_change' => (float) $h->p_change,
                'consecutive_days' => $streak,
                'multiplier' => $multiplier,
                'week1_change' => $w1,
                'turnover_lakhs' => (float) $v->turnover_lakhs,
                'is_dual' => true,
                'category' => $category,
                'badge_text' => $badgeText,
                'stock_id' => $h->stock_id,
            ];
        }

        return $this->sortItems($items, $sort, 'combined');
    }

    /**
     * Sort items array according to chosen criteria.
     */
    private function sortItems(array $items, string $sort, string $type): array
    {
        usort($items, function ($a, $b) use ($sort, $type) {
            if ($sort === 'p_change') {
                return $b['p_change'] <=> $a['p_change'];
            } elseif ($sort === 'symbol') {
                return strcmp($a['symbol'], $b['symbol']);
            } elseif ($sort === 'turnover') {
                $toA = $a['turnover_lakhs'] ?? ($a['ltp'] * 100);
                $toB = $b['turnover_lakhs'] ?? ($b['ltp'] * 100);
                return $toB <=> $toA;
            } else {
                // 'streak_surge' default
                if ($type === '52wh') {
                    if ($a['is_dual'] !== $b['is_dual']) {
                        return $b['is_dual'] ? 1 : -1;
                    }
                    if ($a['consecutive_days'] !== $b['consecutive_days']) {
                        return $b['consecutive_days'] <=> $a['consecutive_days'];
                    }
                    return $b['p_change'] <=> $a['p_change'];
                } elseif ($type === 'vg') {
                    if ($a['is_dual'] !== $b['is_dual']) {
                        return $b['is_dual'] ? 1 : -1;
                    }
                    if ($a['multiplier'] != $b['multiplier']) {
                        return $b['multiplier'] <=> $a['multiplier'];
                    }
                    return $b['p_change'] <=> $a['p_change'];
                } else {
                    // combined
                    $scoreA = $a['consecutive_days'] * 2 + $a['multiplier'];
                    $scoreB = $b['consecutive_days'] * 2 + $b['multiplier'];
                    return $scoreB <=> $scoreA;
                }
            }
        });

        return $items;
    }
}
