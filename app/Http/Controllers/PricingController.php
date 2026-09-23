<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PricingController extends Controller
{
    /**
     * Display the Subscription Plans & Interactive Comparison Landing Page.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $billingCycle = $request->get('billing', 'MONTHLY'); // MONTHLY or ANNUAL
        $requiredTier = strtoupper($request->get('required_tier', ''));

        $plans = [
            'STARTER' => [
                'name' => 'Starter Trader',
                'tagline' => 'Essential momentum discovery for novice traders',
                'monthly_price' => 999,
                'annual_price' => 7999,
                'annual_monthly_equiv' => 666,
                'color' => 'blue',
                'badge' => '🚀 Novice Momentum',
                'popular' => false,
                'features' => [
                    'Daily 52-Week High Stocks List' => true,
                    'Volume Gainers Spurt Hub' => true,
                    '7-Day Date Matrix Lookback' => true,
                    '5-Session Re-Emergence Breakouts' => true,
                    'Live Risk & Position Size Calculator' => true,
                    'Standard Trade Journal (Up to 50 logs)' => true,
                    '10+ & 15+ Session Re-Emergence' => false,
                    'All 23 Pattern Scanners & Candidates' => false,
                    'Avoid List Capital Preservation' => false,
                    'Multi-Strategy Forward Simulator' => false,
                    '1-Click Batch Journal Ingestion' => false,
                    'Real-Time NSE Scraper Triggers' => false,
                ],
            ],
            'PRO' => [
                'name' => 'Pro Swing Trader',
                'tagline' => 'Full algorithmic suite for active swing traders & investors',
                'monthly_price' => 2499,
                'annual_price' => 19999,
                'annual_monthly_equiv' => 1666,
                'color' => 'indigo',
                'badge' => '⭐ MOST POPULAR',
                'popular' => true,
                'features' => [
                    'Daily 52-Week High Stocks List' => true,
                    'Volume Gainers Spurt Hub' => true,
                    'Full Date Matrix (52WH, VG & Confluence)' => true,
                    'Re-Emergence Breakouts (>5, >10, >15 Sessions)' => true,
                    'Live Risk & Position Size Calculator' => true,
                    'Full Trade Journal with CSV Exports' => true,
                    'All 23 Technical Pattern Scanners' => true,
                    'Trade Candidates Engine (High R:R setups)' => true,
                    'Avoid List Capital Diagnostics' => true,
                    'Basic Strategy Backtest (15 Sessions)' => true,
                    'Full Multi-Strategy Forward Simulator' => false,
                    '1-Click Batch Journal Ingestion' => false,
                    'Real-Time NSE Scraper Triggers' => false,
                ],
            ],
            'ELITE' => [
                'name' => 'Elite Algo Trader',
                'tagline' => 'Uncapped quantitative power, custom simulations & instant triggers',
                'monthly_price' => 4999,
                'annual_price' => 39999,
                'annual_monthly_equiv' => 3333,
                'color' => 'purple',
                'badge' => '👑 QUANTITATIVE VIP',
                'popular' => false,
                'features' => [
                    'Daily 52-Week High Stocks List' => true,
                    'Volume Gainers Spurt Hub' => true,
                    'Full Date Matrix (52WH, VG & Confluence)' => true,
                    'Re-Emergence Breakouts (>5, >10, >15 Sessions)' => true,
                    'Live Risk & Position Size Calculator' => true,
                    'Full Trade Journal with CSV Exports' => true,
                    'All 23 Technical Pattern Scanners' => true,
                    'Trade Candidates Engine (High R:R setups)' => true,
                    'Avoid List Capital Diagnostics' => true,
                    'Full Multi-Strategy Forward Simulator (All 6 Models)' => true,
                    '1-Click Batch Journal Ingestion (500+ trades)' => true,
                    'Real-Time NSE Live Scraper Triggers' => true,
                    'Dual Confluence Overlap Engine' => true,
                    'Priority WhatsApp Alerts & VIP Support' => true,
                ],
            ],
        ];

        return view('pricing.index', compact('plans', 'billingCycle', 'user', 'requiredTier'));
    }

    /**
     * Show Checkout page for a selected plan.
     */
    public function checkout(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('register')->with('info', 'Please create your free account before completing checkout.');
        }

        $tier = strtoupper($request->get('tier', 'PRO'));
        $billingCycle = strtoupper($request->get('billing', 'MONTHLY'));

        $priceMatrix = [
            'STARTER' => ['MONTHLY' => 999, 'ANNUAL' => 7999, 'name' => 'Starter Trader'],
            'PRO' => ['MONTHLY' => 2499, 'ANNUAL' => 19999, 'name' => 'Pro Swing Trader'],
            'ELITE' => ['MONTHLY' => 4999, 'ANNUAL' => 39999, 'name' => 'Elite Algo Trader'],
        ];

        $planData = $priceMatrix[$tier] ?? $priceMatrix['PRO'];
        $amount = $planData[$billingCycle] ?? $planData['MONTHLY'];
        $gstAmount = round($amount * 0.18, 2); // 18% GST in India
        $totalAmount = round($amount + $gstAmount, 2);

        return view('pricing.checkout', compact('user', 'tier', 'billingCycle', 'planData', 'amount', 'gstAmount', 'totalAmount'));
    }
}
