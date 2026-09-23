<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('admin@123');

        $demoUsers = [
            [
                'email' => 'admin@algodhara.com',
                'name' => 'AlgoDhara Admin',
                'phone' => '+91 98765 00001',
                'password' => $password,
                'role' => 'admin',
                'subscription_tier' => 'ELITE',
                'subscription_status' => 'ACTIVE',
                'billing_cycle' => 'ANNUAL',
                'subscription_ends_at' => Carbon::now()->addYear(),
                'payment' => [
                    'amount' => 39999,
                    'plan_tier' => 'ELITE',
                    'billing_cycle' => 'ANNUAL',
                    'method' => 'UPI (Google Pay)',
                ],
            ],
            [
                'email' => 'elite@algodhara.com',
                'name' => 'Rajesh Sharma (Elite)',
                'phone' => '+91 98765 00002',
                'password' => $password,
                'role' => 'elite',
                'subscription_tier' => 'ELITE',
                'subscription_status' => 'ACTIVE',
                'billing_cycle' => 'ANNUAL',
                'subscription_ends_at' => Carbon::now()->addMonths(11),
                'payment' => [
                    'amount' => 39999,
                    'plan_tier' => 'ELITE',
                    'billing_cycle' => 'ANNUAL',
                    'method' => 'HDFC NetBanking',
                ],
            ],
            [
                'email' => 'pro@algodhara.com',
                'name' => 'Pooja Patel (Pro)',
                'phone' => '+91 98765 00003',
                'password' => $password,
                'role' => 'pro',
                'subscription_tier' => 'PRO',
                'subscription_status' => 'ACTIVE',
                'billing_cycle' => 'MONTHLY',
                'subscription_ends_at' => Carbon::now()->addDays(18),
                'payment' => [
                    'amount' => 2499,
                    'plan_tier' => 'PRO',
                    'billing_cycle' => 'MONTHLY',
                    'method' => 'Credit Card (Visa)',
                ],
            ],
            [
                'email' => 'starter@algodhara.com',
                'name' => 'Vikram Mehta (Starter)',
                'phone' => '+91 98765 00004',
                'password' => $password,
                'role' => 'starter',
                'subscription_tier' => 'STARTER',
                'subscription_status' => 'ACTIVE',
                'billing_cycle' => 'MONTHLY',
                'subscription_ends_at' => Carbon::now()->addDays(25),
                'payment' => [
                    'amount' => 999,
                    'plan_tier' => 'STARTER',
                    'billing_cycle' => 'MONTHLY',
                    'method' => 'UPI (PhonePe)',
                ],
            ],
            [
                'email' => 'trial@algodhara.com',
                'name' => 'Amit Verma (Trial)',
                'phone' => '+91 98765 00005',
                'password' => $password,
                'role' => 'free_trial',
                'subscription_tier' => 'FREE',
                'subscription_status' => 'TRIALING',
                'billing_cycle' => 'MONTHLY',
                'trial_ends_at' => Carbon::now()->addDays(5),
                'subscription_ends_at' => Carbon::now()->addDays(5),
                'payment' => null,
            ],
            [
                'email' => 'expired@algodhara.com',
                'name' => 'Sanjay Shah (Expired)',
                'phone' => '+91 98765 00006',
                'password' => $password,
                'role' => 'guest',
                'subscription_tier' => 'FREE',
                'subscription_status' => 'EXPIRED',
                'billing_cycle' => 'MONTHLY',
                'trial_ends_at' => Carbon::now()->subDays(8),
                'subscription_ends_at' => Carbon::now()->subDays(8),
                'payment' => null,
            ],
        ];

        // Additional sample users for rich analytics
        $extraUsers = [
            ['email' => 'kavita.deshmukh@gmail.com', 'name' => 'Kavita Deshmukh', 'tier' => 'ELITE', 'role' => 'elite', 'cycle' => 'ANNUAL', 'amount' => 39999, 'method' => 'Credit Card (MasterCard)'],
            ['email' => 'anil.kapoor.trader@yahoo.com', 'name' => 'Anil Kapoor', 'tier' => 'PRO', 'role' => 'pro', 'cycle' => 'ANNUAL', 'amount' => 19999, 'method' => 'UPI (Paytm)'],
            ['email' => 'sneha.reddy@outlook.com', 'name' => 'Sneha Reddy', 'tier' => 'PRO', 'role' => 'pro', 'cycle' => 'MONTHLY', 'amount' => 2499, 'method' => 'ICICI NetBanking'],
            ['email' => 'rohit.gupta@quantindia.com', 'name' => 'Rohit Gupta', 'tier' => 'ELITE', 'role' => 'elite', 'cycle' => 'MONTHLY', 'amount' => 4999, 'method' => 'UPI (BHIM)'],
            ['email' => 'manoj.singh@gmail.com', 'name' => 'Manoj Singh', 'tier' => 'STARTER', 'role' => 'starter', 'cycle' => 'ANNUAL', 'amount' => 7999, 'method' => 'Debit Card (RuPay)'],
            ['email' => 'deepa.nair@financials.in', 'name' => 'Deepa Nair', 'tier' => 'STARTER', 'role' => 'starter', 'cycle' => 'MONTHLY', 'amount' => 999, 'method' => 'UPI (CRED)'],
            ['email' => 'tarun.bhatia@algohedge.com', 'name' => 'Tarun Bhatia', 'tier' => 'FREE', 'role' => 'free_trial', 'cycle' => 'TRIAL', 'amount' => 0, 'method' => 'Free Trial'],
            ['email' => 'gaurav.joshi@gmail.com', 'name' => 'Gaurav Joshi', 'tier' => 'FREE', 'role' => 'guest', 'cycle' => 'EXPIRED', 'amount' => 0, 'method' => 'Expired'],
        ];

        foreach ($demoUsers as $uData) {
            $paymentInfo = $uData['payment'];
            unset($uData['payment']);

            $user = User::updateOrCreate(
                ['email' => $uData['email']],
                $uData
            );

            if ($paymentInfo) {
                Payment::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'gateway_payment_id' => 'pay_demo_' . substr(md5($user->email), 0, 10),
                    ],
                    [
                        'gateway' => 'RAZORPAY_DEMO',
                        'gateway_order_id' => 'order_demo_' . substr(md5($user->email), 0, 8),
                        'plan_tier' => $paymentInfo['plan_tier'],
                        'billing_cycle' => $paymentInfo['billing_cycle'],
                        'amount' => $paymentInfo['amount'],
                        'currency' => 'INR',
                        'payment_method' => $paymentInfo['method'],
                        'status' => 'SUCCESS',
                        'created_at' => Carbon::now()->subDays(rand(1, 20)),
                    ]
                );
            }
        }

        foreach ($extraUsers as $idx => $extra) {
            $isPaid = in_array($extra['tier'], ['STARTER', 'PRO', 'ELITE']);
            $user = User::updateOrCreate(
                ['email' => $extra['email']],
                [
                    'name' => $extra['name'],
                    'phone' => '+91 98000 ' . str_pad((string)($idx + 10), 5, '0', STR_PAD_LEFT),
                    'password' => $password,
                    'role' => $extra['role'],
                    'subscription_tier' => $extra['tier'],
                    'subscription_status' => $isPaid ? 'ACTIVE' : ($extra['role'] === 'free_trial' ? 'TRIALING' : 'EXPIRED'),
                    'billing_cycle' => $extra['cycle'] === 'ANNUAL' ? 'ANNUAL' : 'MONTHLY',
                    'trial_ends_at' => ($extra['role'] === 'free_trial') ? Carbon::now()->addDays(4) : null,
                    'subscription_ends_at' => $isPaid ? Carbon::now()->addMonths(rand(1, 10)) : ($extra['role'] === 'free_trial' ? Carbon::now()->addDays(4) : Carbon::now()->subDays(3)),
                ]
            );

            if ($extra['amount'] > 0) {
                Payment::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'gateway_payment_id' => 'pay_sample_' . substr(md5($user->email), 0, 10),
                    ],
                    [
                        'gateway' => 'RAZORPAY',
                        'gateway_order_id' => 'order_sample_' . substr(md5($user->email), 0, 8),
                        'plan_tier' => $extra['tier'],
                        'billing_cycle' => $extra['cycle'],
                        'amount' => $extra['amount'],
                        'currency' => 'INR',
                        'payment_method' => $extra['method'],
                        'status' => 'SUCCESS',
                        'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    ]
                );
            }
        }
    }
}
