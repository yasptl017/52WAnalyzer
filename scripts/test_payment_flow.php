<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Payment;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Create clean test subscriber
$user = User::updateOrCreate(
    ['email' => 'subtest@algodhara.com'],
    [
        'name' => 'Subscription Test Trader',
        'password' => 'secret123',
        'role' => 'free_trial',
        'subscription_tier' => 'FREE',
        'subscription_status' => 'TRIALING',
        'trial_ends_at' => now()->addDays(7),
    ]
);

Auth::login($user);

echo "Initial User State: Role={$user->role}, Tier={$user->subscription_tier}, Status={$user->subscription_status}\n";

$controller = new PaymentController();
$req = new Request([
    'tier' => 'PRO',
    'billing_cycle' => 'ANNUAL',
    'amount' => 23598.82, // 19999 + 18% GST
    'payment_method' => 'UPI',
    'gateway_payment_id' => 'pay_test_upi_12345678',
    'gateway_order_id' => 'order_test_987654',
]);

$response = $controller->verifyPayment($req);
$user->refresh();

echo "Post-Payment User State: Role={$user->role}, Tier={$user->subscription_tier}, Status={$user->subscription_status}, Expires=" . ($user->subscription_ends_at ? $user->subscription_ends_at->format('Y-m-d') : 'None') . "\n";
echo "Is Pro? " . ($user->isPro() ? 'YES' : 'NO') . "\n";
echo "Is Elite? " . ($user->isElite() ? 'YES' : 'NO') . "\n";
echo "Can access Pattern Scanner? " . ($user->canAccess('pattern_scanner') ? 'YES' : 'NO') . "\n";
echo "Can access Strategy Simulation? " . ($user->canAccess('strategy_simulation') ? 'YES' : 'NO') . "\n";

$lastPayment = Payment::where('user_id', $user->id)->latest()->first();
echo "Recorded Payment ID #{$lastPayment->id}: Rs.{$lastPayment->amount} via {$lastPayment->payment_method} ({$lastPayment->status})\n";
