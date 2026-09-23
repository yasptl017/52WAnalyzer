<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Stock;
use App\Models\Daily52wHigh;
use App\Models\DailyVolumeGainer;
use App\Models\DailySummary;
use App\Models\OhlcvBar;
use App\Models\PatternMatch;
use App\Models\MomentumScore;
use App\Models\TradeSetup;
use App\Models\Alert;
use App\Models\AppSetting;
use OpenSpout\Reader\XLSX\Reader;
use PDO;

class ImportHistoricalDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nse:import-historical-data
                            {--excel= : Path to 52WeekHighAnalysis.xlsx}
                            {--sqlite= : Path to trading_engine.sqlite}
                            {--skip-ohlcv : Skip importing historical OHLCV bars}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import preloaded historical data from 52WeekHighAnalysis.xlsx and SQLite into MySQL';

    /**
     * Cache for symbol -> stock_id mapping.
     */
    protected array $stockMap = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("=================================================");
        $this->info("  NSE 52W & Volume Gainers Historical Data Import ");
        $this->info("=================================================");

        $excelPath = $this->option('excel') ?: 'd:/AlgoDhara/52WAnalysis/Reference/52WeekHighAnalysis.xlsx';
        $sqlitePath = $this->option('sqlite') ?: 'd:/AlgoDhara/52WAnalysis/Reference/data/trading_engine.sqlite';

        // Preload existing stocks into memory
        $this->loadStockMap();

        if (file_exists($excelPath)) {
            $this->info("Reading Excel: {$excelPath}");
            $this->importExcel($excelPath);
        } else {
            $this->warn("Excel file not found at: {$excelPath}");
        }

        if (!$this->option('skip-ohlcv') && file_exists($sqlitePath)) {
            $this->info("Reading SQLite: {$sqlitePath}");
            $this->importSqlite($sqlitePath);
        } elseif ($this->option('skip-ohlcv')) {
            $this->comment("Skipping OHLCV bars as --skip-ohlcv was requested.");
        } else {
            $this->warn("SQLite file not found at: {$sqlitePath}");
        }

        AppSetting::set('last_imported_at', now()->toDateTimeString(), 'Timestamp of last historical data import');
        AppSetting::set('max_history_days', 30, 'Rolling window days for momentum analysis');

        $this->info("-------------------------------------------------");
        $this->info("Summary of Database Records in MySQL:");
        $this->info("  Stocks: " . Stock::count());
        $this->info("  52-Week High Hits: " . Daily52wHigh::count());
        $this->info("  Volume Gainers Records: " . DailyVolumeGainer::count());
        $this->info("  Daily Summaries: " . DailySummary::count());
        $this->info("  Pattern Matches: " . PatternMatch::count());
        $this->info("  Momentum Scores: " . MomentumScore::count());
        $this->info("  Trade Setups: " . TradeSetup::count());
        $this->info("  Alerts: " . Alert::count());
        $this->info("  OHLCV Bars: " . OhlcvBar::count());
        $this->info("=================================================");
        $this->info("Historical Data Migration Completed Successfully!");

        return Command::SUCCESS;
    }

    protected function loadStockMap(): void
    {
        $this->stockMap = Stock::pluck('id', 'symbol')->toArray();
    }

    protected function getOrCreateStock(string $symbol, ?string $company = null, ?string $sector = null): int
    {
        $symbol = strtoupper(trim($symbol));
        if (empty($symbol)) {
            return 0;
        }

        if (isset($this->stockMap[$symbol])) {
            // Update company name if available and not set
            if ($company && $company !== 'None') {
                Stock::where('id', $this->stockMap[$symbol])
                    ->where(function ($q) {
                        $q->whereNull('company_name')->orWhere('company_name', '');
                    })
                    ->update(['company_name' => trim($company)]);
            }
            return $this->stockMap[$symbol];
        }

        $stock = Stock::create([
            'symbol' => $symbol,
            'company_name' => ($company && $company !== 'None') ? trim($company) : null,
            'series' => 'EQ',
            'asset_class' => 'EQUITY',
            'sector' => $sector ? trim($sector) : null,
            'is_active' => true,
        ]);

        $this->stockMap[$symbol] = $stock->id;
        return $stock->id;
    }

    protected function importExcel(string $path): void
    {
        $reader = new Reader();
        $reader->open($path);

        $latestDate = '2026-09-18'; // Fallback latest trade date

        foreach ($reader->getSheetIterator() as $sheet) {
            $name = $sheet->getName();
            $this->comment("Processing sheet: {$name}...");

            if ($name === 'History') {
                $latestDate = $this->import52wHistory($sheet) ?: $latestDate;
            } elseif ($name === 'Volume Gainers History') {
                $this->importVolumeGainersHistory($sheet);
            } elseif ($name === 'Daily Summary') {
                $this->importDailySummary($sheet, '52WH');
            } elseif ($name === 'Volume Gainers Daily Summary') {
                $this->importDailySummary($sheet, 'VG');
            } elseif ($name === 'Pattern Ranking') {
                $this->importPatternRanking($sheet);
            } elseif ($name === 'Momentum Ranking') {
                $this->importMomentumRanking($sheet, $latestDate);
            } elseif ($name === 'Trade Candidates') {
                $this->importTradeCandidates($sheet, $latestDate);
            } elseif ($name === 'Avoid List') {
                $this->importAvoidList($sheet, $latestDate);
            }
        }

        $reader->close();
    }

    protected function parseDateLabel(string $label): ?string
    {
        $label = trim($label);
        if (empty($label) || $label === 'None') {
            return null;
        }

        $d = date_create($label);
        return $d ? $d->format('Y-m-d') : null;
    }

    protected function import52wHistory($sheet): ?string
    {
        $headerRow = null;
        $dateCols = [];
        $batch = [];
        $count = 0;
        $latestDate = null;

        foreach ($sheet->getRowIterator() as $row) {
            $arr = $row->toArray();
            if (!$headerRow) {
                $headerRow = $arr;
                foreach ($headerRow as $idx => $colName) {
                    if ($idx >= 2) {
                        $parsedDate = $this->parseDateLabel((string)$colName);
                        if ($parsedDate) {
                            $dateCols[$idx] = $parsedDate;
                            if (!$latestDate || $parsedDate > $latestDate) {
                                $latestDate = $parsedDate;
                            }
                        }
                    }
                }
                continue;
            }

            $symbol = trim((string)($arr[0] ?? ''));
            $company = trim((string)($arr[1] ?? ''));

            if (empty($symbol) || $symbol === 'Symbol' || $symbol === 'None') {
                continue;
            }

            $stockId = $this->getOrCreateStock($symbol, $company);
            if (!$stockId) {
                continue;
            }

            foreach ($dateCols as $idx => $tradeDate) {
                $val = $arr[$idx] ?? null;
                if ($val !== null && $val !== '' && $val !== 'None') {
                    $valStr = trim((string)$val);
                    $price = is_numeric($valStr) ? (float)$valStr : (float)str_replace(',', '', $valStr);
                    $batch[] = [
                        'stock_id' => $stockId,
                        'trade_date' => $tradeDate,
                        'ltp' => $price > 0 ? $price : null,
                        'consecutive_days' => 1,
                        'total_appearances_30d' => 1,
                        'is_new' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($batch) >= 1000) {
                        DB::table('daily_52w_highs')->insertOrIgnore($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                }
            }
        }

        if (!empty($batch)) {
            DB::table('daily_52w_highs')->insertOrIgnore($batch);
            $count += count($batch);
        }

        $this->info("  Imported {$count} daily 52W-High records.");
        return $latestDate;
    }

    protected function importVolumeGainersHistory($sheet): void
    {
        $headerRow = null;
        $dateCols = [];
        $batch = [];
        $count = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $arr = $row->toArray();
            if (!$headerRow) {
                $headerRow = $arr;
                foreach ($headerRow as $idx => $colName) {
                    if ($idx >= 4) { // 0=Symbol, 1=Company, 2=Sessions Seen, 3=Confidence
                        $parsedDate = $this->parseDateLabel((string)$colName);
                        if ($parsedDate) {
                            $dateCols[$idx] = $parsedDate;
                        }
                    }
                }
                continue;
            }

            $symbol = trim((string)($arr[0] ?? ''));
            $company = trim((string)($arr[1] ?? ''));
            $sessions = is_numeric($arr[2] ?? null) ? (int)$arr[2] : 1;
            $confidence = trim((string)($arr[3] ?? '★'));

            if (empty($symbol) || $symbol === 'Symbol' || $symbol === 'None') {
                continue;
            }

            $stockId = $this->getOrCreateStock($symbol, $company);
            if (!$stockId) {
                continue;
            }

            foreach ($dateCols as $idx => $tradeDate) {
                $val = $arr[$idx] ?? null;
                if ($val !== null && $val !== '' && $val !== 'None') {
                    $valStr = trim((string)$val);
                    $price = is_numeric($valStr) ? (float)$valStr : (float)str_replace(',', '', $valStr);
                    $batch[] = [
                        'stock_id' => $stockId,
                        'trade_date' => $tradeDate,
                        'ltp' => $price > 0 ? $price : null,
                        'sessions_seen' => $sessions,
                        'confidence' => $confidence ?: '★',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($batch) >= 1000) {
                        DB::table('daily_volume_gainers')->insertOrIgnore($batch);
                        $count += count($batch);
                        $batch = [];
                    }
                }
            }
        }

        if (!empty($batch)) {
            DB::table('daily_volume_gainers')->insertOrIgnore($batch);
            $count += count($batch);
        }

        $this->info("  Imported {$count} daily Volume Gainer records.");
    }

    protected function importDailySummary($sheet, string $category): void
    {
        $headerRow = null;
        $batch = [];
        $count = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $arr = $row->toArray();
            if (!$headerRow) {
                $headerRow = $arr;
                continue;
            }

            $dateRaw = $arr[0] ?? null;
            if (!$dateRaw || $dateRaw === 'Date') {
                continue;
            }

            $tradeDate = $this->parseDateLabel((string)$dateRaw);
            if (!$tradeDate) {
                continue;
            }

            $total = (int)($arr[1] ?? 0);
            $new = (int)($arr[2] ?? 0);
            $repeated = (int)($arr[3] ?? 0);
            $dropped = (int)($arr[4] ?? 0);
            $topStock = trim((string)($arr[5] ?? ''));

            $batch[] = [
                'trade_date' => $tradeDate,
                'category' => $category,
                'total_count' => $total,
                'new_count' => $new,
                'repeated_count' => $repeated,
                'dropped_count' => $dropped,
                'top_stock' => $topStock ?: null,
                'top_score' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($batch)) {
            DB::table('daily_summaries')->upsert(
                $batch,
                ['trade_date', 'category'],
                ['total_count', 'new_count', 'repeated_count', 'dropped_count', 'top_stock', 'updated_at']
            );
            $count = count($batch);
        }

        $this->info("  Imported {$count} {$category} daily summaries.");
    }

    protected function importPatternRanking($sheet): void
    {
        $headerRow = null;
        $batch = [];
        $count = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $arr = $row->toArray();
            if (!$headerRow) {
                $headerRow = $arr;
                continue;
            }

            $symbol = trim((string)($arr[0] ?? ''));
            $code = trim((string)($arr[1] ?? ''));
            $name = trim((string)($arr[2] ?? ''));
            $confidence = (float)($arr[3] ?? 0);
            $detectedOn = $this->parseDateLabel((string)($arr[4] ?? ''));
            $evidence = trim((string)($arr[5] ?? ''));

            if (empty($symbol) || !$detectedOn || empty($code)) {
                continue;
            }

            $stockId = $this->getOrCreateStock($symbol);
            if (!$stockId) {
                continue;
            }

            $batch[] = [
                'stock_id' => $stockId,
                'trade_date' => $detectedOn,
                'pattern_code' => $code,
                'pattern_name' => $name,
                'confidence' => $confidence,
                'evidence' => $evidence ?: null,
                'details' => json_encode(['evidence' => $evidence]),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                DB::table('pattern_matches')->insert($batch);
                $count += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('pattern_matches')->insert($batch);
            $count += count($batch);
        }

        $this->info("  Imported {$count} pattern matches.");
    }

    protected function importMomentumRanking($sheet, string $defaultDate): void
    {
        $headerRow = null;
        $batch = [];
        $count = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $arr = $row->toArray();
            if (!$headerRow) {
                $headerRow = $arr;
                continue;
            }

            // [Rank, Symbol, Company, Momentum, Institutional, Trade Quality, Rank Tier, Confidence %]
            $symbol = trim((string)($arr[1] ?? ''));
            $company = trim((string)($arr[2] ?? ''));
            $momentum = (float)($arr[3] ?? 0);
            $institutional = (float)($arr[4] ?? 0);
            $quality = (float)($arr[5] ?? 0);
            $tier = trim((string)($arr[6] ?? 'B'));
            $confidence = (float)($arr[7] ?? 0);

            if (empty($symbol) || $symbol === 'Symbol') {
                continue;
            }

            $stockId = $this->getOrCreateStock($symbol, $company);
            if (!$stockId) {
                continue;
            }

            $batch[] = [
                'stock_id' => $stockId,
                'trade_date' => $defaultDate,
                'momentum_score' => $momentum,
                'institutional_score' => $institutional,
                'trade_quality_score' => $quality,
                'rank_tier' => $tier ?: 'B',
                'confidence_pct' => $confidence,
                'reasons' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 500) {
                DB::table('momentum_scores')->upsert(
                    $batch,
                    ['stock_id', 'trade_date'],
                    ['momentum_score', 'institutional_score', 'trade_quality_score', 'rank_tier', 'confidence_pct', 'updated_at']
                );
                $count += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('momentum_scores')->upsert(
                $batch,
                ['stock_id', 'trade_date'],
                ['momentum_score', 'institutional_score', 'trade_quality_score', 'rank_tier', 'confidence_pct', 'updated_at']
            );
            $count += count($batch);
        }

        $this->info("  Imported {$count} momentum scores.");
    }

    protected function importTradeCandidates($sheet, string $defaultDate): void
    {
        $headerRow = null;
        $batch = [];
        $count = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $arr = $row->toArray();
            if (!$headerRow) {
                $headerRow = $arr;
                continue;
            }

            // [Rank, Symbol, Company, Momentum, Institutional, Trade Quality, Rank Tier, Confidence %, Entry Style, Entry Price, Stop Loss, SL Method, Target 1R, Target 2R, Target 3R, R:R, Qty, Capital Used, Patterns, Reasons]
            $symbol = trim((string)($arr[1] ?? ''));
            $company = trim((string)($arr[2] ?? ''));
            $style = trim((string)($arr[8] ?? 'Moderate'));
            $entry = (float)($arr[9] ?? 0);
            $sl = (float)($arr[10] ?? 0);
            $slMethod = trim((string)($arr[11] ?? 'ATR_1.5x'));
            $t1 = (float)($arr[12] ?? 0);
            $t2 = (float)($arr[13] ?? 0);
            $t3 = (float)($arr[14] ?? 0);
            $rr = (float)($arr[15] ?? 0);
            $qty = (int)($arr[16] ?? 0);
            $capUsed = (float)($arr[17] ?? 0);
            $reasons = trim((string)($arr[19] ?? ''));

            if (empty($symbol) || $symbol === 'Symbol' || $entry <= 0) {
                continue;
            }

            $stockId = $this->getOrCreateStock($symbol, $company);
            if (!$stockId) {
                continue;
            }

            $batch[] = [
                'stock_id' => $stockId,
                'trade_date' => $defaultDate,
                'entry_style' => $style ?: 'Moderate',
                'entry_price' => $entry,
                'stop_loss' => $sl,
                'stop_method' => $slMethod ?: 'ATR_1.5x',
                'target_1r' => $t1 ?: null,
                'target_2r' => $t2 ?: null,
                'target_3r' => $t3 ?: null,
                'rr_ratio' => $rr,
                'suggested_qty' => $qty,
                'capital_used' => $capUsed,
                'status' => 'CANDIDATE',
                'rejection_reason' => null,
                'notes' => $reasons ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($batch)) {
            DB::table('trade_setups')->insert($batch);
            $count = count($batch);
        }

        $this->info("  Imported {$count} trade candidates.");
    }

    protected function importAvoidList($sheet, string $defaultDate): void
    {
        $headerRow = null;
        $batch = [];
        $count = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $arr = $row->toArray();
            if (!$headerRow) {
                $headerRow = $arr;
                continue;
            }

            // [Symbol, Company, Rejection Reason, Momentum, Entry, SL, R:R]
            $symbol = trim((string)($arr[0] ?? ''));
            $company = trim((string)($arr[1] ?? ''));
            $reason = trim((string)($arr[2] ?? ''));
            $entry = (float)($arr[4] ?? 0);
            $sl = (float)($arr[5] ?? 0);
            $rr = (float)($arr[6] ?? 0);

            if (empty($symbol) || $symbol === 'Symbol') {
                continue;
            }

            $stockId = $this->getOrCreateStock($symbol, $company);
            if (!$stockId) {
                continue;
            }

            $batch[] = [
                'stock_id' => $stockId,
                'trade_date' => $defaultDate,
                'entry_style' => 'Moderate',
                'entry_price' => $entry > 0 ? $entry : 0,
                'stop_loss' => $sl > 0 ? $sl : 0,
                'stop_method' => 'Filter_Rejected',
                'target_1r' => null,
                'target_2r' => null,
                'target_3r' => null,
                'rr_ratio' => $rr,
                'suggested_qty' => 0,
                'capital_used' => 0,
                'status' => 'REJECTED',
                'rejection_reason' => $reason ?: 'Rejected by strategy filter',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 500) {
                DB::table('trade_setups')->insert($batch);
                $count += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('trade_setups')->insert($batch);
            $count += count($batch);
        }

        $this->info("  Imported {$count} rejected setups into Avoid List.");
    }

    protected function importSqlite(string $path): void
    {
        $pdo = new PDO("sqlite:{$path}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. Import Alerts
        $this->comment("Importing Alerts from SQLite...");
        $stmt = $pdo->query("SELECT * FROM alerts");
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $alertBatch = [];
        $alertCount = 0;

        foreach ($alerts as $a) {
            $sym = trim($a['symbol'] ?? '');
            if (empty($sym)) continue;
            $stockId = $this->getOrCreateStock($sym);

            $raisedAt = $a['raised_at'] ?? now()->toDateTimeString();
            $tradeDate = substr($raisedAt, 0, 10);

            $alertBatch[] = [
                'stock_id' => $stockId,
                'trade_date' => $tradeDate,
                'rule_code' => $a['rule_code'] ?? 'GENERAL',
                'message' => $a['message'] ?? '',
                'is_acknowledged' => (bool)($a['acknowledged'] ?? false),
                'raised_at' => $raisedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($alertBatch) >= 500) {
                DB::table('alerts')->insert($alertBatch);
                $alertCount += count($alertBatch);
                $alertBatch = [];
            }
        }

        if (!empty($alertBatch)) {
            DB::table('alerts')->insert($alertBatch);
            $alertCount += count($alertBatch);
        }
        $this->info("  Imported {$alertCount} alerts from SQLite.");

        // 2. Import OHLCV bars (370k rows) in chunks of 5000
        $this->comment("Importing ~370,000 OHLCV bars from SQLite (this will take a few seconds)...");
        $stmt = $pdo->query("SELECT symbol, bar_date, open, high, low, close, volume FROM ohlcv_bars ORDER BY rowid ASC");

        $barBatch = [];
        $barCount = 0;
        $barProgressBar = $this->output->createProgressBar(371000);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sym = trim($row['symbol'] ?? '');
            $bDate = trim($row['bar_date'] ?? '');

            if (empty($sym) || empty($bDate)) {
                continue;
            }

            $stockId = $this->getOrCreateStock($sym);
            if (!$stockId) {
                continue;
            }

            $barBatch[] = [
                'stock_id' => $stockId,
                'bar_date' => $bDate,
                'open' => is_numeric($row['open']) ? (float)$row['open'] : null,
                'high' => is_numeric($row['high']) ? (float)$row['high'] : null,
                'low' => is_numeric($row['low']) ? (float)$row['low'] : null,
                'close' => is_numeric($row['close']) ? (float)$row['close'] : null,
                'volume' => (int)($row['volume'] ?? 0),
                'source' => 'SQLITE_MIGRATION',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($barBatch) >= 2000) {
                DB::table('ohlcv_bars')->insertOrIgnore($barBatch);
                $barCount += count($barBatch);
                $barProgressBar->advance(count($barBatch));
                $barBatch = [];
            }
        }

        if (!empty($barBatch)) {
            DB::table('ohlcv_bars')->insertOrIgnore($barBatch);
            $barCount += count($barBatch);
            $barProgressBar->advance(count($barBatch));
        }

        $barProgressBar->finish();
        $this->newLine();
        $this->info("  Imported {$barCount} OHLCV bars from SQLite.");
    }
}
