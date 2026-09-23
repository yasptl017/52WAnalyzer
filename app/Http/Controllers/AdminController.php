<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    /**
     * Admin Analytics & Subscription Overview Dashboard.
     */
    public function index(Request $request)
    {
        $search = trim($request->get('search', ''));
        $filterTier = $request->get('tier', 'ALL');
        $filterStatus = $request->get('status', 'ALL');

        // 1. Core KPIs
        $totalUsers = User::count();
        $paidRoles = ['starter', 'pro', 'elite', 'admin'];
        $activeSubscribers = User::whereIn('role', $paidRoles)
            ->where('subscription_status', 'ACTIVE')
            ->count();

        $trialUsers = User::where('role', 'free_trial')
            ->where('subscription_status', 'TRIALING')
            ->count();

        $expiredUsers = User::where('subscription_status', 'EXPIRED')->count();

        // Financial Metrics
        $totalGrossRevenue = (float)Payment::where('status', 'SUCCESS')->sum('amount');
        
        // Compute MRR & ARR from active paid subscribers
        $monthlySubscribers = User::whereIn('role', $paidRoles)
            ->where('subscription_status', 'ACTIVE')
            ->where('billing_cycle', 'MONTHLY')
            ->get();

        $annualSubscribers = User::whereIn('role', $paidRoles)
            ->where('subscription_status', 'ACTIVE')
            ->where('billing_cycle', 'ANNUAL')
            ->get();

        $mrr = 0.0;
        foreach ($monthlySubscribers as $u) {
            $mrr += match ($u->subscription_tier) {
                'ELITE' => 4999,
                'PRO' => 2499,
                'STARTER' => 999,
                default => 0,
            };
        }
        foreach ($annualSubscribers as $u) {
            $annualPrice = match ($u->subscription_tier) {
                'ELITE' => 39999,
                'PRO' => 19999,
                'STARTER' => 7999,
                default => 0,
            };
            $mrr += ($annualPrice / 12);
        }

        $arr = $mrr * 12;
        $arpu = $activeSubscribers > 0 ? round($mrr / $activeSubscribers, 2) : 0;

        $conversionBase = $activeSubscribers + $trialUsers + $expiredUsers;
        $trialConversionRate = $conversionBase > 0 ? round(($activeSubscribers / $conversionBase) * 100, 1) : 0;

        // Tier Breakdown
        $tierCounts = [
            'ELITE' => User::where('subscription_tier', 'ELITE')->count(),
            'PRO' => User::where('subscription_tier', 'PRO')->count(),
            'STARTER' => User::where('subscription_tier', 'STARTER')->count(),
            'FREE' => User::where('subscription_tier', 'FREE')->count(),
        ];

        $roleCounts = [
            'admin' => User::where('role', 'admin')->count(),
            'elite' => User::where('role', 'elite')->count(),
            'pro' => User::where('role', 'pro')->count(),
            'starter' => User::where('role', 'starter')->count(),
            'free_trial' => User::where('role', 'free_trial')->count(),
            'guest' => User::where('role', 'guest')->count(),
        ];

        // Expiring in next 7 days
        $expiringNext7Days = User::whereNotNull('subscription_ends_at')
            ->whereBetween('subscription_ends_at', [Carbon::now(), Carbon::now()->addDays(7)])
            ->orderBy('subscription_ends_at', 'asc')
            ->get();

        // Recent Payments
        $recentPayments = Payment::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        // Users Query with Filters
        $usersQuery = User::query()->orderBy('created_at', 'desc');

        if ($search !== '') {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($filterTier !== 'ALL') {
            $usersQuery->where('subscription_tier', $filterTier);
        }

        if ($filterStatus !== 'ALL') {
            $usersQuery->where('subscription_status', $filterStatus);
        }

        $users = $usersQuery->paginate(20)->withQueryString();

        return view('admin.dashboard', compact(
            'totalUsers',
            'activeSubscribers',
            'trialUsers',
            'expiredUsers',
            'totalGrossRevenue',
            'mrr',
            'arr',
            'arpu',
            'trialConversionRate',
            'tierCounts',
            'roleCounts',
            'expiringNext7Days',
            'recentPayments',
            'users',
            'search',
            'filterTier',
            'filterStatus'
        ));
    }

    /**
     * Update user subscription tier, status, or extend validity manually.
     */
    public function updateUserTier(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,elite,pro,starter,free_trial,guest',
            'subscription_tier' => 'required|in:ELITE,PRO,STARTER,FREE',
            'subscription_status' => 'required|in:ACTIVE,TRIALING,EXPIRED,CANCELLED',
            'billing_cycle' => 'required|in:MONTHLY,ANNUAL',
            'extend_days' => 'nullable|integer|min:0|max:3650',
        ]);

        $user->role = $validated['role'];
        $user->subscription_tier = $validated['subscription_tier'];
        $user->subscription_status = $validated['subscription_status'];
        $user->billing_cycle = $validated['billing_cycle'];

        if (!empty($validated['extend_days'])) {
            $baseDate = ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()) 
                ? $user->subscription_ends_at 
                : Carbon::now();
            $user->subscription_ends_at = $baseDate->copy()->addDays((int)$validated['extend_days']);
        }

        $user->save();

        return back()->with('success', "Updated user [{$user->name}] subscription to {$user->subscription_tier} ({$user->subscription_status}) successfully.");
    }

    /**
     * Export all transaction records to CSV.
     */
    public function exportTransactionsCsv(): StreamedResponse
    {
        $payments = Payment::with('user')->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="algodhara_transactions_' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Txn ID', 'User Name', 'Email', 'Plan Tier', 'Billing Cycle', 'Amount (INR)', 'Method', 'Status', 'Date']);

            foreach ($payments as $p) {
                fputcsv($handle, [
                    $p->gateway_payment_id,
                    $p->user?->name ?: 'N/A',
                    $p->user?->email ?: 'N/A',
                    $p->plan_tier,
                    $p->billing_cycle,
                    $p->amount,
                    $p->payment_method,
                    $p->status,
                    $p->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
