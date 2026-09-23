<?php

namespace App\Console\Commands;

use App\Services\DailyAnalysisService;
use App\Models\Daily52wHigh;
use App\Models\DailyVolumeGainer;
use App\Models\DailySummary;
use App\Models\DataArchive;
use Illuminate\Console\Command;

class DownloadDailyDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nse:download-daily 
                            {--date= : Specific trade date in YYYY-MM-DD format (default latest trading day)}
                            {--force : Force execution even on non-trading days/holidays}
                            {--no-vg : Skip Volume Gainers scraping}
                            {--no-bhav : Skip Bhavcopy download}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download daily 52-Week Highs, live & market-wide Volume Gainers, and Bhavcopy from NSE';

    /**
     * Execute the console command.
     */
    public function handle(DailyAnalysisService $dailyService): int
    {
        $tradeDate = $this->option('date');
        $force = (bool)$this->option('force');

        $this->info("===============================================================================");
        $this->info("              NSE Primary Daily Data Downloader & Analysis Pipeline           ");
        $this->info("===============================================================================");
        $this->line(" Target Date : <comment>" . ($tradeDate ?: 'Latest Active Trading Session') . "</comment>");
        $this->line(" Force Run   : " . ($force ? '<comment>YES</comment>' : '<info>NO (Adhering to NSE Calendar)</info>'));
        $this->line(" Mode        : <info>Live NSE Scraper + Market-Wide Volume Expansion</info>");
        $this->newLine();

        $this->output->write("Connecting to NSE India endpoints (Akamai cookie handshake)... ");
        $startTime = microtime(true);

        $res = $dailyService->runDailyAnalysis($tradeDate, ['force' => $force]);
        $duration = round(microtime(true) - $startTime, 2);

        $this->line("<info>Done ({$duration}s)</info>");
        $this->newLine();

        // Print Pipeline Status
        $this->table(
            ['Metric', 'Value'],
            [
                ['Trade Date', $res['trade_date']],
                ['Status', $res['status'] === 'COMPLETE' ? '<info>COMPLETE</info>' : '<comment>PARTIAL / NOTICE</comment>'],
                ['52-Week High Total', $res['52wh_count']],
                ['52-Week High New Entries', $res['52wh_new']],
                ['Total Volume Gainers (All Entries)', $res['vg_count']],
                ['Market-Wide Volume Breakouts (>=1.5x)', $res['market_wide_vg']],
                ['Bhavcopy OHLCV Bars Ingested', $res['bhavcopy_bars']],
                ['Date-Wise Disk Archive Saved', $res['archive_saved'] ? '<info>YES (storage/app/archives/' . $res['trade_date'] . ')</info>' : '<comment>NO</comment>'],
            ]
        );

        // Show messages
        if (!empty($res['messages'])) {
            $this->newLine();
            $this->line("<options=bold>Pipeline Activity Log:</options=bold>");
            foreach ($res['messages'] as $msg) {
                $this->line(" • {$msg}");
            }
        }

        // Show top 52-Week High Stocks with Highest Streaks
        $topStreaks = Daily52wHigh::with('stock')
            ->where('trade_date', $tradeDate)
            ->orderBy('consecutive_days', 'desc')
            ->limit(5)
            ->get();

        if ($topStreaks->isNotEmpty()) {
            $this->newLine();
            $this->info("Top 52-Week High Consecutive Momentum Streaks:");
            $streakRows = [];
            foreach ($topStreaks as $row) {
                $streakRows[] = [
                    $row->stock->symbol ?? 'N/A',
                    $row->consecutive_days . ' days',
                    $row->total_appearances_30d . '/30',
                    '₹' . number_format($row->ltp, 2),
                    ($row->p_change >= 0 ? '+' : '') . number_format($row->p_change, 2) . '%',
                    $row->is_new ? '<comment>NEW</comment>' : '<info>CONTINUING</info>',
                ];
            }
            $this->table(['Symbol', 'Streak', '30D App.', 'LTP', '% Change', 'Status'], $streakRows);
        }

        // Show top Volume Gainers
        $topVg = DailyVolumeGainer::with('stock')
            ->where('trade_date', $tradeDate)
            ->orderBy('week1_change', 'desc')
            ->limit(5)
            ->get();

        if ($topVg->isNotEmpty()) {
            $this->newLine();
            $this->info("Top Market-Wide Volume Gainers (Spurts):");
            $vgRows = [];
            foreach ($topVg as $row) {
                $vgRows[] = [
                    $row->stock->symbol ?? 'N/A',
                    $row->week1_change ? number_format($row->week1_change, 2) . 'x' : '-',
                    $row->week2_change ? number_format($row->week2_change, 2) . 'x' : '-',
                    number_format($row->volume),
                    '₹' . number_format($row->turnover_lakhs, 2) . ' L',
                    $row->confidence ?: '★',
                ];
            }
            $this->table(['Symbol', '1W Spurt', '2W Spurt', 'Volume', 'Turnover', 'Confidence'], $vgRows);
        }

        $this->newLine();
        $this->info("✅ Daily pipeline run completed successfully for {$tradeDate}.");
        return Command::SUCCESS;
    }
}
