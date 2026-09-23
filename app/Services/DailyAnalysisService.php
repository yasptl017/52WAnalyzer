<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Daily52wHigh;
use App\Models\DailyVolumeGainer;
use App\Models\DailySummary;
use App\Models\OhlcvBar;
use App\Models\DataArchive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyAnalysisService
{
    public function __construct(
        protected NseScraperService $scraper,
        protected VolumeGainersService $vgService,
        protected DataArchiveService $archiveService,
        protected TradingCalendarService $calendar,
    ) {}

    /**
     * Run the complete end-to-end daily data pipeline:
     * 1. Scrape 52-Week High list from NSE
     * 2. Ingest stocks, calculate streaks & 30-day appearances
     * 3. Scrape live Volume Gainers
     * 4. Scrape Bhavcopy, ingest OHLCV bars, and calculate market-wide volume gainers
     * 5. Compute Daily Summary deltas (New, Repeated, Dropped, Top Stock)
     * 6. Save raw files & metadata to DataArchive
     */
    public function runDailyAnalysis(?string $tradeDate = null, array $options = []): array
    {
        $force = !empty($options['force']);

        if (!$tradeDate) {
            $tradeDate = $this->calendar->getLatestTradingDay();
        } elseif (!$force && !$this->calendar->isTradingDay($tradeDate)) {
            $reason = $this->calendar->getNonTradingReason($tradeDate);
            $latestTradingDay = $this->calendar->getLatestTradingDay($tradeDate);

            // If the latest actual trading day is already processed, do not duplicate
            if (Daily52wHigh::where('trade_date', $latestTradingDay)->exists()) {
                Log::info("Daily analysis skipped for non-trading date {$tradeDate} ({$reason}). Latest session {$latestTradingDay} is already in DB.");
                return [
                    'trade_date' => $latestTradingDay,
                    '52wh_count' => Daily52wHigh::where('trade_date', $latestTradingDay)->count(),
                    '52wh_new' => 0,
                    'vg_count' => DailyVolumeGainer::where('trade_date', $latestTradingDay)->count(),
                    'bhavcopy_bars' => OhlcvBar::where('bar_date', $latestTradingDay)->count(),
                    'market_wide_vg' => 0,
                    'archive_saved' => true,
                    'status' => 'SKIPPED_HOLIDAY',
                    'messages' => [
                        "Market is closed on {$tradeDate} ({$reason}).",
                        "Latest trading session is {$latestTradingDay} (already synced).",
                    ],
                ];
            } else {
                Log::info("Date {$tradeDate} is a non-trading day ({$reason}). Redirecting sync to latest valid trading day: {$latestTradingDay}.");
                $tradeDate = $latestTradingDay;
            }
        }

        Log::info("Starting daily analysis pipeline for date: {$tradeDate}");

        $result = [
            'trade_date' => $tradeDate,
            '52wh_count' => 0,
            '52wh_new' => 0,
            'vg_count' => 0,
            'bhavcopy_bars' => 0,
            'market_wide_vg' => 0,
            'archive_saved' => false,
            'status' => 'PENDING',
            'messages' => [],
        ];

        // -------------------------------------------------------------
        // Step 1: 52-Week High Ingestion
        // -------------------------------------------------------------
        $res52wh = $this->scraper->fetch52WeekHighs();
        $raw52whRows = $res52wh['data'];
        $raw52whJson = $res52wh['raw'];

        if (!empty($raw52whRows)) {
            $count52wh = $this->process52WeekHighs($raw52whRows, $tradeDate);
            $result['52wh_count'] = $count52wh;
            $result['messages'][] = "Ingested {$count52wh} 52-Week High stocks.";
        } else {
            $msg = $res52wh['error'] ?: 'NSE returned empty 52WH data (possible holiday or off-market hours).';
            Log::warning($msg);
            $result['messages'][] = $msg;
        }

        // -------------------------------------------------------------
        // Step 2: Live Volume Gainers Ingestion
        // -------------------------------------------------------------
        $resVg = $this->scraper->fetchLiveVolumeGainers();
        $rawVgRows = $resVg['data'];
        $rawVgJson = $resVg['raw'];

        if (!empty($rawVgRows)) {
            $countVg = $this->vgService->ingestLiveSnapshot($rawVgRows, $tradeDate);
            $result['vg_count'] = $countVg;
            $result['messages'][] = "Ingested {$countVg} live Volume Gainer stocks from NSE.";
        } else {
            $msg = $resVg['error'] ?: 'NSE returned empty Volume Gainers data.';
            Log::warning($msg);
            $result['messages'][] = $msg;
        }

        // -------------------------------------------------------------
        // Step 3: Bhavcopy & Market-Wide Volume Gainers
        // -------------------------------------------------------------
        $resBhav = $this->scraper->fetchBhavcopy($tradeDate);
        $rawBhavCsv = $resBhav['csv'];

        if (!empty($rawBhavCsv)) {
            $parsedBars = $this->scraper->parseBhavcopyCsv($rawBhavCsv);
            $ingestedBars = $this->ingestBhavcopyBars($parsedBars, $tradeDate);
            $result['bhavcopy_bars'] = $ingestedBars;
            $result['messages'][] = "Ingested {$ingestedBars} OHLCV bars from Bhavcopy.";

            // Compute market-wide volume gainers from OHLCV bars (breaks the 25 limit!)
            $mwCount = $this->vgService->computeMarketWideVolumeGainers($tradeDate);
            $result['market_wide_vg'] = $mwCount;
            $result['messages'][] = "Market-wide scan identified {$mwCount} volume breakouts (>= 1.5x rolling avg).";
        } else {
            // Check if ohlcv_bars already exist for today (e.g. from prior runs or preloaded data)
            $existingBars = OhlcvBar::where('bar_date', $tradeDate)->count();
            if ($existingBars > 0) {
                $mwCount = $this->vgService->computeMarketWideVolumeGainers($tradeDate);
                $result['market_wide_vg'] = $mwCount;
                $result['messages'][] = "Market-wide scan from existing bars identified {$mwCount} volume breakouts.";
            } else {
                $result['messages'][] = "Bhavcopy not available yet for {$tradeDate}; market-wide scan skipped.";
            }
        }

        // Total volume gainers count for today after live + market-wide
        $result['vg_count'] = DailyVolumeGainer::where('trade_date', $tradeDate)->count();

        // -------------------------------------------------------------
        // Step 4: Compute Daily Summaries (52WH & VG)
        // -------------------------------------------------------------
        $summary52wh = $this->deriveDailySummary($tradeDate, '52WH');
        $summaryVg = $this->deriveDailySummary($tradeDate, 'VG');

        $result['52wh_new'] = $summary52wh->new_count ?? 0;

        // -------------------------------------------------------------
        // Step 5: Save Archive
        // -------------------------------------------------------------
        $archiveStatus = ($result['52wh_count'] > 0 || $result['vg_count'] > 0) ? 'COMPLETE' : 'PARTIAL';
        $archive = $this->archiveService->archiveDay(
            tradeDate: $tradeDate,
            raw52whJson: $raw52whJson ?: null,
            rawVgJson: $rawVgJson ?: null,
            rawBhavcopyCsv: $rawBhavCsv ?: null,
            total52wh: $result['52wh_count'],
            totalVg: $result['vg_count'],
            status: $archiveStatus,
            notes: implode(' | ', $result['messages'])
        );

        $result['archive_saved'] = (bool)$archive;
        $result['status'] = $archiveStatus;

        Log::info("Completed daily analysis pipeline for {$tradeDate}. 52WH: {$result['52wh_count']}, VG: {$result['vg_count']}");
        return $result;
    }

    /**
     * Process and insert 52-Week High records with streak and 30-day appearance tracking.
     */
    protected function process52WeekHighs(array $rows, string $tradeDate): int
    {
        // Find previous distinct trading date in daily_52w_highs before today
        $prevDate = Daily52wHigh::where('trade_date', '<', $tradeDate)
            ->max('trade_date');

        // Fetch previous day streaks in memory for fast lookup
        $prevStreaks = [];
        if ($prevDate) {
            $prevRecords = Daily52wHigh::where('trade_date', $prevDate)
                ->pluck('consecutive_days', 'stock_id')
                ->toArray();
            $prevStreaks = $prevRecords;
        }

        // Last 30 distinct trading dates up to today
        $last30Dates = Daily52wHigh::where('trade_date', '<=', $tradeDate)
            ->distinct()
            ->orderBy('trade_date', 'desc')
            ->limit(30)
            ->pluck('trade_date')
            ->toArray();

        $count = 0;
        foreach ($rows as $row) {
            $symbol = strtoupper(trim($row['symbol'] ?? ''));
            if (empty($symbol)) continue;

            $company = trim($row['comapnyName'] ?? $row['companyName'] ?? $row['company'] ?? '');
            $series = strtoupper(trim($row['series'] ?? 'EQ'));
            $assetClass = $this->mapSeriesToAssetClass($series);

            $stock = Stock::firstOrCreate(
                ['symbol' => $symbol],
                [
                    'company_name' => $company ?: null,
                    'series' => $series,
                    'asset_class' => $assetClass,
                ]
            );

            // Calculate streak
            $consecutive = isset($prevStreaks[$stock->id]) ? ($prevStreaks[$stock->id] + 1) : 1;
            $isNew = ($consecutive === 1);

            // Calculate 30-day appearances
            $appearances30d = Daily52wHigh::where('stock_id', $stock->id)
                ->whereIn('trade_date', $last30Dates)
                ->where('trade_date', '!=', $tradeDate)
                ->count() + 1;

            $ltp = isset($row['ltp']) ? (float)$row['ltp'] : null;
            $high = isset($row['new52WHL']) ? (float)$row['new52WHL'] : (isset($row['high']) ? (float)$row['high'] : null);
            $prevClose = isset($row['prevClose']) ? (float)$row['prevClose'] : null;
            $change = isset($row['change']) ? (float)$row['change'] : null;
            $pChange = isset($row['pChange']) ? (float)$row['pChange'] : null;
            $prev52WHL = isset($row['prev52WHL']) ? (float)$row['prev52WHL'] : null;
            $prev52WDate = null;
            if (!empty($row['prevHLDate'])) {
                try {
                    $d = date_create((string)$row['prevHLDate']);
                    if ($d instanceof \DateTimeInterface) {
                        $prev52WDate = $d->format('Y-m-d');
                    }
                } catch (\Throwable) {}
            }

            Daily52wHigh::updateOrCreate(
                [
                    'stock_id' => $stock->id,
                    'trade_date' => $tradeDate,
                ],
                [
                    'ltp' => $ltp,
                    'high_price' => $high ?: $ltp,
                    'prev_close' => $prevClose,
                    'change_val' => $change,
                    'p_change' => $pChange,
                    'prev_52w_high' => $prev52WHL,
                    'prev_52w_date' => $prev52WDate,
                    'consecutive_days' => $consecutive,
                    'total_appearances_30d' => $appearances30d,
                    'is_new' => $isNew,
                ]
            );

            $count++;
        }

        return $count;
    }

    /**
     * Bulk insert/update OHLCV bars from Bhavcopy.
     */
    protected function ingestBhavcopyBars(array $bars, string $tradeDate): int
    {
        $count = 0;
        $chunks = array_chunk($bars, 500);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $b) {
                $symbol = $b['symbol'];
                $stock = Stock::firstOrCreate(
                    ['symbol' => $symbol],
                    ['series' => $b['series'], 'asset_class' => 'EQUITY']
                );

                OhlcvBar::updateOrCreate(
                    [
                        'stock_id' => $stock->id,
                        'bar_date' => $tradeDate,
                    ],
                    [
                        'open' => $b['open'],
                        'high' => $b['high'],
                        'low' => $b['low'],
                        'close' => $b['close'],
                        'volume' => $b['volume'],
                        'source' => 'NSE',
                    ]
                );

                $count++;
            }
        }

        return $count;
    }

    /**
     * Compute and persist Daily Summary for 52WH or VG.
     */
    public function deriveDailySummary(string $tradeDate, string $category): ?DailySummary
    {
        if ($category === '52WH') {
            $todayStockIds = Daily52wHigh::where('trade_date', $tradeDate)
                ->pluck('stock_id')
                ->toArray();

            $prevDate = Daily52wHigh::where('trade_date', '<', $tradeDate)->max('trade_date');
            $prevStockIds = $prevDate
                ? Daily52wHigh::where('trade_date', $prevDate)->pluck('stock_id')->toArray()
                : [];

            // Top stock by consecutive days
            $topRecord = Daily52wHigh::with('stock')
                ->where('trade_date', $tradeDate)
                ->orderBy('consecutive_days', 'desc')
                ->first();

            $topStock = $topRecord ? "{$topRecord->stock->symbol} ({$topRecord->consecutive_days})" : null;
            $topScore = $topRecord ? (float)$topRecord->consecutive_days : null;
        } else {
            $todayStockIds = DailyVolumeGainer::where('trade_date', $tradeDate)
                ->pluck('stock_id')
                ->toArray();

            $prevDate = DailyVolumeGainer::where('trade_date', '<', $tradeDate)->max('trade_date');
            $prevStockIds = $prevDate
                ? DailyVolumeGainer::where('trade_date', $prevDate)->pluck('stock_id')->toArray()
                : [];

            // Top stock by volume multiplier (week1_change)
            $topRecord = DailyVolumeGainer::with('stock')
                ->where('trade_date', $tradeDate)
                ->orderBy('week1_change', 'desc')
                ->first();

            $topStock = $topRecord ? "{$topRecord->stock->symbol} ({$topRecord->week1_change}x)" : null;
            $topScore = $topRecord ? (float)$topRecord->week1_change : null;
        }

        if (empty($todayStockIds) && empty($prevStockIds)) {
            return null;
        }

        $todaySet = array_flip($todayStockIds);
        $prevSet = array_flip($prevStockIds);

        $newCount = 0;
        $repeatedCount = 0;
        foreach ($todayStockIds as $id) {
            if (isset($prevSet[$id])) {
                $repeatedCount++;
            } else {
                $newCount++;
            }
        }

        $droppedCount = 0;
        foreach ($prevStockIds as $id) {
            if (!isset($todaySet[$id])) {
                $droppedCount++;
            }
        }

        return DailySummary::updateOrCreate(
            [
                'trade_date' => $tradeDate,
                'category' => $category,
            ],
            [
                'total_count' => count($todayStockIds),
                'new_count' => $newCount,
                'repeated_count' => $repeatedCount,
                'dropped_count' => $droppedCount,
                'top_stock' => $topStock,
                'top_score' => $topScore,
            ]
        );
    }

    /**
     * Map NSE Series code to Asset Class.
     */
    protected function mapSeriesToAssetClass(string $series): string
    {
        return match (strtoupper($series)) {
            'EQ', 'BE', 'SM', 'BZ' => 'EQUITY',
            'ETF', 'GS' => 'ETF',
            'SGB' => 'SGB',
            'MF' => 'MF',
            'IV' => 'INVIT',
            'RE' => 'REIT',
            default => 'EQUITY',
        };
    }
}
