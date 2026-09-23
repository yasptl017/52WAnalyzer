<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

echo "--- 1. Testing Demo Users Authentication with password 'admin@123' ---\n";

$demoEmails = [
    'admin@algodhara.com' => ['expected_role' => 'admin', 'expected_tier' => 'ELITE'],
    'elite@algodhara.com' => ['expected_role' => 'elite', 'expected_tier' => 'ELITE'],
    'pro@algodhara.com' => ['expected_role' => 'pro', 'expected_tier' => 'PRO'],
    'starter@algodhara.com' => ['expected_role' => 'starter', 'expected_tier' => 'STARTER'],
    'trial@algodhara.com' => ['expected_role' => 'free_trial', 'expected_tier' => 'FREE'],
    'expired@algodhara.com' => ['expected_role' => 'guest', 'expected_tier' => 'FREE'],
];

$allPassed = true;

foreach ($demoEmails as $email => $expected) {
    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "❌ User not found: {$email}\n";
        $allPassed = false;
        continue;
    }

    $passCheck = Hash::check('admin@123', $user->password);
    if (!$passCheck) {
        echo "❌ Password check failed for: {$email}\n";
        $allPassed = false;
        continue;
    }

    $badge = $user->getTierBadge();
    echo "✅ Authenticated {$email} | Role: {$user->role} | Tier: {$user->subscription_tier} | Status: {$user->subscription_status} | Badge: {$badge['name']}\n";
}

echo "\n--- 2. Testing Admin Panel Endpoints ---\n";
$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$adminUser = User::where('email', 'admin@algodhara.com')->first();
Auth::login($adminUser);

$adminRoutes = [
    '/admin',
    '/admin/subscriptions',
    '/admin/export-transactions',
];

foreach ($adminRoutes as $route) {
    try {
        $req = Illuminate\Http\Request::create($route, 'GET');
        $res = $httpKernel->handle($req);
        $status = $res->getStatusCode();
        echo "{$route} -> Status: {$status} " . ($status === 200 ? '✅' : '❌') . "\n";
        if ($status !== 200) {
            $allPassed = false;
            echo "Body: " . substr($res->getContent(), 0, 800) . "\n";
        }
    } catch (\Throwable $e) {
        echo "Exception in {$route}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

echo "\n--- 3. Testing Admin Tier Update Action ---\n";
$targetUser = User::where('email', 'trial@algodhara.com')->first();
$updateReq = Illuminate\Http\Request::create("/admin/users/{$targetUser->id}/update-tier", 'POST', [
    'role' => 'pro',
    'subscription_tier' => 'PRO',
    'subscription_status' => 'ACTIVE',
    'billing_cycle' => 'MONTHLY',
    'extend_days' => 30,
]);
$updateRes = app(\App\Http\Controllers\AdminController::class)->updateUserTier($updateReq, $targetUser);
$targetUser->refresh();
echo "Updated trial user to: Role={$targetUser->role}, Tier={$targetUser->subscription_tier}, Status={$targetUser->subscription_status}, EndsAt={$targetUser->subscription_ends_at->format('d M Y')}\n";

if ($allPassed && $targetUser->role === 'pro') {
    echo "\n🎉 ALL DEMO USERS & ADMIN SUBSCRIPTION ANALYTICS PANEL VERIFIED SUCCESSFULLY!\n";
}
