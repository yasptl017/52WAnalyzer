<?php

namespace App\Console\Commands;

use App\Services\SystemSyncService;
use Illuminate\Console\Command;

class SyncDailyNseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nse:daily-sync 
                            {--force : Force sync even on non-trading days/holidays}
                            {--date= : Custom trade date to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Centralized system-wide NSE daily data synchronization (Holiday-aware)';

    /**
     * Execute the console command.
     */
    public function handle(SystemSyncService $syncService): int
    {
        $force = (bool)$this->option('force');
        $date = $this->option('date');

        $this->info("===============================================================================");
        $this->info("              NSE Centralized Daily System Synchronization                     ");
        $this->info("===============================================================================");

        $res = $syncService->executeGlobalSync('SCHEDULED_CRON', null, $date);

        if ($res['status'] === 'SKIPPED_HOLIDAY') {
            $this->warn("⚠️  " . $res['message']);
            return Command::SUCCESS;
        }

        if ($res['success']) {
            $this->info("✅  " . $res['message']);
            return Command::SUCCESS;
        }

        $this->error("❌  " . $res['message']);
        return Command::FAILURE;
    }
}
