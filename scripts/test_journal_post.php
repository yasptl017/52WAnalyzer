<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TradeJournal;
use App\Http\Controllers\StrategySimulationController;
use Illuminate\Http\Request;

$initialCount = TradeJournal::count();
echo "Initial Trade Journal Count: {$initialCount}\n";

$controller = new StrategySimulationController();
$req = new Request([
    'strategy_key' => '15d',
    'universe' => 'BOTH',
    'capital' => 10000,
    'from_date' => '2026-08-01',
    'to_date' => '2026-09-18',
    'mode' => 'ALL',
]);

$response = $controller->forwardTestJournal($req);
echo "Forward Test Response: " . get_class($response) . "\n";

$newCount = TradeJournal::count();
echo "New Trade Journal Count: {$newCount} (Added: " . ($newCount - $initialCount) . " trades)\n";

$recent = TradeJournal::with('stock')->orderBy('id', 'desc')->limit(3)->get();
foreach ($recent as $t) {
    echo "Logged Trade ID #{$t->id}: {$t->stock->symbol} | Entry: {$t->entry_date} @ Rs.{$t->entry_price} | Qty: {$t->quantity} | Exit: {$t->exit_date} @ Rs.{$t->exit_price} | PnL: Rs.{$t->pnl} | Status: {$t->status}\n";
}
