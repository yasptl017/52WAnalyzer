<?php

namespace App\Services;

use App\Models\SystemSyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SystemSyncService
{
    public function __construct(
        protected DailyAnalysisService $analysisService,
        protected TradingCalendarService $calendar,
    ) {}

    /**
     * Check if the system needs to run a global daily sync for today.
     * Evaluates trading day calendar, market timing, and existing sync records.
     */
    public function needsDailySyncToday(): bool
    {
        $now = Carbon::now('Asia/Kolkata');
        $today = $now->format('Y-m-d');

        // 1. If today is not a trading day (weekend or NSE holiday), do not sync
        if (!$this->calendar->isTradingDay($today)) {
            return false;
        }

        // 2. If market has not opened yet (before 09:15 IST), wait until market opens
        if ($now->format('H:i') < '09:15') {
            return false;
        }

        // 3. Check if today's sync has already been executed successfully
        if (SystemSyncLog::hasSyncedFor($today)) {
            return false;
        }

        return true;
    }

    /**
     * Execute centralized system-wide NSE daily data synchronization.
     * Thread-safe with cache mutex locks.
     */
    public function executeGlobalSync(string $triggeredBy = 'AUTO_FIRST_LOGIN', ?int $userId = null, ?string $forceDate = null): array
    {
        $lock = Cache::lock('nse_global_daily_sync_lock', 180);

        if (!$lock->get()) {
            return [
                'success' => false,
                'status' => 'IN_PROGRESS',
                'message' => 'Another NSE Data Synchronization is currently in progress. Please wait a moment.',
                'records' => null,
            ];
        }

        try {
            $now = Carbon::now('Asia/Kolkata');
            $today = $now->format('Y-m-d');
            $targetDate = $forceDate ?: $today;

            // Check if target date is a weekend or holiday
            $isTradingDay = $this->calendar->isTradingDay($targetDate);
            $nonTradingReason = $this->calendar->getNonTradingReason($targetDate);

            if (!$isTradingDay && !$forceDate) {
                // Determine latest past trading day
                $latestTradingDay = $this->calendar->getLatestTradingDay($targetDate);

                // Check if the latest past trading day is already synced
                $hasLatestSynced = SystemSyncLog::hasSyncedFor($latestTradingDay);

                $log = SystemSyncLog::create([
                    'trade_date' => $targetDate,
                    'synced_at' => now(),
                    'triggered_by' => $triggeredBy,
                    'user_id' => $userId,
                    'status' => 'SKIPPED_HOLIDAY',
                    'records_52wh' => 0,
                    'records_vg' => 0,
                    'records_bhavcopy' => 0,
                    'notes' => "Market Closed on {$targetDate} ({$nonTradingReason}). Latest trading session is {$latestTradingDay}.",
                ]);

                return [
                    'success' => true,
                    'status' => 'SKIPPED_HOLIDAY',
                    'message' => "Market is closed today ({$nonTradingReason}). No new trading session created. Latest active session: {$latestTradingDay}.",
                    'trade_date' => $latestTradingDay,
                    'records' => null,
                ];
            }

            Log::info("Executing centralized NSE sync for date: {$targetDate} triggered by {$triggeredBy}");

            // Run analysis pipeline
            $analysisResult = $this->analysisService->runDailyAnalysis($targetDate);

            $status = ($analysisResult['52wh_count'] > 0 || $analysisResult['vg_count'] > 0) ? 'SUCCESS' : 'FAILED';
            $notes = implode(' | ', $analysisResult['messages'] ?? []);

            $syncLog = SystemSyncLog::create([
                'trade_date' => $targetDate,
                'synced_at' => now(),
                'triggered_by' => $triggeredBy,
                'user_id' => $userId,
                'status' => $status,
                'records_52wh' => $analysisResult['52wh_count'] ?? 0,
                'records_vg' => $analysisResult['vg_count'] ?? 0,
                'records_bhavcopy' => $analysisResult['bhavcopy_bars'] ?? 0,
                'notes' => $notes ?: 'System sync completed successfully.',
            ]);

            return [
                'success' => ($status === 'SUCCESS'),
                'status' => $status,
                'message' => ($status === 'SUCCESS') 
                    ? "Successfully synced {$syncLog->records_52wh} 52W Highs, {$syncLog->records_vg} Volume Gainers, and {$syncLog->records_bhavcopy} Bhavcopy bars."
                    : "NSE sync completed with warnings: {$notes}",
                'trade_date' => $targetDate,
                'sync_log' => $syncLog,
            ];
        } catch (\Throwable $e) {
            Log::error("System NSE sync failed: " . $e->getMessage(), ['exception' => $e]);

            SystemSyncLog::create([
                'trade_date' => $forceDate ?: Carbon::now('Asia/Kolkata')->format('Y-m-d'),
                'synced_at' => now(),
                'triggered_by' => $triggeredBy,
                'user_id' => $userId,
                'status' => 'FAILED',
                'records_52wh' => 0,
                'records_vg' => 0,
                'records_bhavcopy' => 0,
                'notes' => 'Execution error: ' . $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'FAILED',
                'message' => 'Synchronization failed: ' . $e->getMessage(),
                'records' => null,
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * Get real-time synchronization overview and status for UI indicators.
     */
    public function getSyncOverview(): array
    {
        $now = Carbon::now('Asia/Kolkata');
        $today = $now->format('Y-m-d');
        $marketStatus = $this->calendar->getMarketStatus();
        $latestSync = SystemSyncLog::latestSuccessful();
        $hasSyncedToday = SystemSyncLog::hasSyncedFor($today);
        $needsSync = $this->needsDailySyncToday();

        return [
            'today' => $today,
            'market' => $marketStatus,
            'latest_sync' => $latestSync ? [
                'date' => $latestSync->trade_date ? $latestSync->trade_date->format('d M Y') : '—',
                'time_ago' => $latestSync->synced_at ? $latestSync->synced_at->diffForHumans() : '—',
                'synced_at_formatted' => $latestSync->synced_at ? $latestSync->synced_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') : '—',
                'records_52wh' => $latestSync->records_52wh,
                'records_vg' => $latestSync->records_vg,
                'triggered_by' => $latestSync->triggered_by,
            ] : null,
            'synced_today' => $hasSyncedToday,
            'needs_sync' => $needsSync,
        ];
    }
}
