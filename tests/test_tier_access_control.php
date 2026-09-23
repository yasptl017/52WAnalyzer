<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "\n=================================================================\n";
echo "  52WAnalyzer: Comprehensive Tier & Package RBAC Access Audit\n";
echo "=================================================================\n\n";

$users = [
    'Guest (Unauthenticated)' => null,
    'Starter (starter@algodhara.com)' => User::where('email', 'starter@algodhara.com')->first(),
    'Free Trial 7D (trial@algodhara.com)' => User::where('email', 'trial@algodhara.com')->first(),
    'Pro Trader (pro@algodhara.com)' => User::where('email', 'pro@algodhara.com')->first(),
    'Elite Trader (elite@algodhara.com)' => User::where('email', 'elite@algodhara.com')->first(),
    'Super Admin (admin@algodhara.com)' => User::where('email', 'admin@algodhara.com')->first(),
];

$testMatrix = [
    // [Route Path, Expected Status for: [Guest, Starter, FreeTrial, Pro, Elite, Admin]]
    ['/', [302, 200, 200, 200, 200, 200]],
    ['/risk-calculator', [200, 200, 200, 200, 200, 200]],
    ['/pricing', [200, 200, 200, 200, 200, 200]],
    ['/volume-gainers', [302, 200, 200, 200, 200, 200]],
    ['/date-matrix', [302, 200, 200, 200, 200, 200]],
    ['/trade-journal', [302, 200, 200, 200, 200, 200]],
    ['/re-emergence?tab=5', [302, 200, 200, 200, 200, 200]],
    ['/re-emergence?tab=10', [302, 302, 200, 200, 200, 200]],
    ['/re-emergence?tab=15', [302, 302, 200, 200, 200, 200]],
    ['/trading-engine/candidates', [302, 302, 200, 200, 200, 200]],
    ['/trading-engine/avoid', [302, 302, 200, 200, 200, 200]],
    ['/trading-engine/patterns', [302, 302, 200, 200, 200, 200]],
    ['/archives', [302, 302, 200, 200, 200, 200]],
    ['/strategy-simulation', [302, 302, 302, 302, 200, 200]],
    ['/admin', [302, 302, 302, 302, 302, 200]],
];

$userKeys = array_keys($users);
$totalChecks = 0;
$passedChecks = 0;

foreach ($testMatrix as $row) {
    $uri = $row[0];
    $expectedStatuses = $row[1];

    echo "Testing Route: " . str_pad($uri, 30) . "\n";

    foreach ($userKeys as $idx => $userLabel) {
        $userObj = $users[$userLabel];
        $expected = $expectedStatuses[$idx];

        // Create Request
        $parsedUrl = parse_url($uri);
        $path = $parsedUrl['path'];
        $queryStr = $parsedUrl['query'] ?? '';
        parse_str($queryStr, $queryParams);

        $request = Request::create($path, 'GET', $queryParams);
        $app->instance('request', $request);

        // Set Auth Context
        if ($userObj) {
            Auth::guard('web')->setUser($userObj);
        } else {
            Auth::guard('web')->forgetUser();
        }

        $response = $kernel->handle($request);
        $status = $response->getStatusCode();

        $totalChecks++;
        $redirectTarget = ($status === 302) ? $response->headers->get('Location') : '';

        if ($status === $expected) {
            $passedChecks++;
            $icon = "  ✓ PASS";
            $color = "\033[32m";
            $reset = "\033[0m";
            $detail = ($status === 302) ? " (302 Redirect -> " . substr($redirectTarget, strrpos($redirectTarget, '/') ?: 0) . ")" : " (200 OK)";
            echo "   {$color}{$icon}{$reset} [{$userLabel}]: {$status}{$detail}\n";
        } else {
            $icon = "  ✗ FAIL";
            $color = "\033[31m";
            $reset = "\033[0m";
            echo "   {$color}{$icon}{$reset} [{$userLabel}]: Got {$status}, Expected {$expected} (Location: {$redirectTarget})\n";
        }

        $kernel->terminate($request, $response);
    }
    echo "\n";
}

echo "=================================================================\n";
echo "  Audit Results: {$passedChecks} / {$totalChecks} checks passed.\n";
if ($passedChecks === $totalChecks) {
    echo "  🎉 ALL PACKAGE ACCESS RESTRICTIONS PERFECTLY ENFORCED!\n";
} else {
    echo "  ⚠️ SOME TESTS FAILED - PLEASE REVIEW ABOVE.\n";
}
echo "=================================================================\n";
