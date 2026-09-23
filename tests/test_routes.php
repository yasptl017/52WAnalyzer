<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Create or find a test user
$testUser = User::firstOrCreate(
    ['email' => 'testtrader@algodhara.com'],
    [
        'name' => 'Test Trader',
        'password' => 'secret123',
        'role' => 'elite',
        'subscription_tier' => 'ELITE',
        'subscription_status' => 'ACTIVE',
    ]
);

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$urls = [
    '/',
    '/login',
    '/register',
    '/pricing',
    '/pricing?billing=ANNUAL',
    '/pricing?required_tier=ELITE',
    '/pricing/checkout?tier=PRO&billing=MONTHLY',
    '/pricing/checkout?tier=ELITE&billing=ANNUAL',
    '/volume-gainers',
    '/date-matrix',
    '/date-matrix?tab=52wh',
    '/date-matrix?tab=vg',
    '/date-matrix?tab=combined',
    '/date-matrix/export?tab=52wh',
    '/re-emergence',
    '/re-emergence?tab=5',
    '/re-emergence?tab=10',
    '/re-emergence?tab=15',
    '/re-emergence/export?tab=5',
    '/trading-engine/candidates',
    '/trading-engine/avoid',
    '/trading-engine/patterns',
    '/risk-calculator',
    '/trade-journal',
    '/strategy-simulation',
    '/strategy-simulation?universe=52WH&capital=10000',
    '/strategy-simulation?universe=VG&capital=25000',
    '/strategy-simulation?universe=BOTH&capital=50000',
    '/archives',
    '/archives/2026-09-18/raw/52wh',
    '/archives/2026-09-18/raw/vg',
    '/profile',
    '/api/stock-timeline/QUINT',
    '/api/system/sync-status',
    '/admin',
    '/admin/subscriptions',
    '/admin/export-transactions',
];

$allPassed = true;
Auth::login($testUser); // Authenticate test user

foreach ($urls as $url) {
    try {
        $request = Illuminate\Http\Request::create($url, 'GET');
        $response = $httpKernel->handle($request);
        $status = $response->getStatusCode();
        echo "{$url} -> Status: {$status}" . PHP_EOL;

        if ($status !== 200) {
            $allPassed = false;
            $content = $response->getContent();
            echo "Body: " . substr($content, 0, 500) . PHP_EOL;
        }
    } catch (\Throwable $e) {
        $allPassed = false;
        echo "Exception in {$url}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    }
}

if ($allPassed) {
    echo "\nALL " . count($urls) . " ROUTES RENDERED WITH HTTP 200 OK!\n";
}
