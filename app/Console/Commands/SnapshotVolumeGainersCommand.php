<?php

namespace App\Console\Commands;

use App\Services\NseScraperService;
use App\Services\VolumeGainersService;
use App\Models\DailyVolumeGainer;
use Illuminate\Console\Command;

class SnapshotVolumeGainersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nse:snapshot-vg 
                            {--date= : Specific trade date in YYYY-MM-DD format (default today)}
                            {--market-wide : Execute market-wide volume spurt scan from OHLCV bars}
                            {--min-mult=1.50 : Minimum volume multiplier threshold for market-wide scan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take a live snapshot of NSE Volume Gainers and/or compute market-wide volume spurts across all stocks';

    /**
     * Execute the console command.
     */
    public function handle(NseScraperService $scraper, VolumeGainersService $vgService): int
    {
        $tradeDate = $this->option('date') ?: date('Y-m-d');
        $runMarketWide = $this->option('market-wide');
        $minMult = (float)$this->option('min-mult');

        $this->info("===============================================================================");
        $this->info("                   NSE Volume Gainers Pipeline & Snapshot                      ");
        $this->info("===============================================================================");
        $this->line(" Target Date : <comment>{$tradeDate}</comment>");
        $this->line(" Live Scrape : <info>Fetching live snapshot from NSE</info>");
        $this->newLine();

        // 1. Live Snapshot
        $this->output->write("Fetching live Volume Gainers from NSE... ");
        $res = $scraper->fetchLiveVolumeGainers();

        if ($res['success'] && !empty($res['data'])) {
            $ingested = $vgService->ingestLiveSnapshot($res['data'], $tradeDate);
            $this->line("<info>OK ({$ingested} stocks ingested / session presence updated)</info>");
        } else {
            $this->line("<comment>Notice: " . ($res['error'] ?: 'No live data returned') . "</comment>");
        }

        // 2. Market-wide computation if requested or by default
        if ($runMarketWide) {
            $this->newLine();
            $this->output->write("Calculating market-wide volume spurts from OHLCV bars (>= {$minMult}x)... ");
            $mwCount = $vgService->computeMarketWideVolumeGainers($tradeDate, $minMult);
            $this->line("<info>OK ({$mwCount} stocks identified across the entire market)</info>");
        }

        // 3. Display summary
        $totalVg = DailyVolumeGainer::where('trade_date', $tradeDate)->count();
        $this->newLine();
        $this->info("Total Volume Gainers tracked for {$tradeDate}: <comment>{$totalVg}</comment>");

        $topGainers = DailyVolumeGainer::with('stock')
            ->where('trade_date', $tradeDate)
            ->orderBy('week1_change', 'desc')
            ->limit(10)
            ->get();

        if ($topGainers->isNotEmpty()) {
            $this->newLine();
            $this->info("Top Volume Gainers on {$tradeDate}:");
            $rows = [];
            foreach ($topGainers as $g) {
                $rows[] = [
                    $g->stock->symbol ?? 'N/A',
                    $g->week1_change ? number_format($g->week1_change, 2) . 'x' : '-',
                    $g->week2_change ? number_format($g->week2_change, 2) . 'x' : '-',
                    number_format($g->volume),
                    '₹' . number_format($g->ltp, 2),
                    ($g->p_change >= 0 ? '+' : '') . number_format($g->p_change, 2) . '%',
                    '₹' . number_format($g->turnover_lakhs, 2) . ' L',
                    $g->sessions_seen,
                    $g->confidence,
                ];
            }
            $this->table(
                ['Symbol', '1W Spurt', '2W Spurt', 'Volume', 'LTP', '% Change', 'Turnover', 'Sessions', 'Confidence'],
                $rows
            );
        }

        return Command::SUCCESS;
    }
}
