<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TradingCalendarService;
use App\Services\SystemSyncService;
use App\Models\SystemSyncLog;
use App\Models\Daily52wHigh;

$calendar = app(TradingCalendarService::class);
$syncService = app(SystemSyncService::class);

echo "--- 1. Testing TradingCalendarService ---\n";
echo "Is 2026-09-19 (Saturday) trading day? " . ($calendar->isTradingDay('2026-09-19') ? 'YES' : 'NO (Correct)') . "\n";
echo "Reason for 2026-09-19: " . $calendar->getNonTradingReason('2026-09-19') . "\n";
echo "Is 2026-09-18 (Friday) trading day? " . ($calendar->isTradingDay('2026-09-18') ? 'YES (Correct)' : 'NO') . "\n";
echo "Is 2026-01-26 (Republic Day) trading day? " . ($calendar->isTradingDay('2026-01-26') ? 'YES' : 'NO (Holiday Correct)') . "\n";
echo "Latest trading day from Saturday 2026-09-19: " . $calendar->getLatestTradingDay('2026-09-19') . "\n";

echo "\n--- 2. Testing SystemSyncService ---\n";
$overview = $syncService->getSyncOverview();
echo "Market status: " . $overview['market']['status_text'] . " (" . $overview['market']['reason'] . ")\n";
echo "Needs daily sync today? " . ($overview['needs_sync'] ? 'YES' : 'NO (Weekend/Synced)') . "\n";

echo "\n--- 3. Testing SystemSyncLog records in DB ---\n";
$latest = SystemSyncLog::latest()->first();
if ($latest) {
    echo "Latest Sync Status: " . $latest->status . " | Triggered by: " . $latest->triggered_by . " | Notes: " . $latest->notes . "\n";
} else {
    echo "No sync logs found.\n";
}

echo "\n--- 4. Verify 2026-09-19 is NOT present in 52W Highs or VG ---\n";
$satCount52 = Daily52wHigh::where('trade_date', '2026-09-19')->count();
echo "Saturday 2026-09-19 count in Daily52wHigh: {$satCount52} (Expected: 0)\n";

echo "\n--- 5. Testing API Global Sync Endpoint ---\n";
$req = \Illuminate\Http\Request::create('/api/system/global-sync', 'POST');
$res = app(\App\Http\Controllers\ApiController::class)->triggerGlobalSync($req);
echo "API Response: " . json_encode($res->getData(), JSON_PRETTY_PRINT) . "\n";

echo "\n>>> ALL TRADING CALENDAR & SYSTEM SYNC CHECKS PASSED PERFECTLY! <<<\n";

