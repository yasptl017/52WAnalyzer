@extends('layouts.app')

@section('title', 'Subscription Plans & Pricing - 52WAnalyzer')

@section('content')
<div class="space-y-12 max-w-6xl mx-auto py-4">

    <!-- Hero Header -->
    <div class="text-center space-y-3 max-w-3xl mx-auto">
        <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 inline-flex items-center gap-1.5">
            💎 TRANSPARENT & PREDICTABLE PRICING
        </span>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight">
            Supercharge Your Trading with <span class="bg-gradient-to-r from-indigo-600 to-emerald-500 bg-clip-text text-transparent">Algorithmic Edge</span>
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400">
            Select the subscription plan tailored for your trading style. Start risk-free with our 7-Day Free Trial.
        </p>

        @if($requiredTier)
            <div class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-500/30 text-xs text-amber-800 dark:text-amber-300 font-bold inline-block mt-2">
                🔒 You tried accessing a facility that requires the <span class="text-indigo-600 dark:text-indigo-400 font-black">[{{ $requiredTier }}]</span> tier. Choose your plan below to unlock instant access.
            </div>
        @endif

        <!-- Monthly / Annual Toggle Switch -->
        <div class="flex items-center justify-center gap-3 pt-4">
            <span class="text-xs font-bold {{ $billingCycle === 'MONTHLY' ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">Monthly</span>
            
            <a href="{{ request()->fullUrlWithQuery(['billing' => $billingCycle === 'MONTHLY' ? 'ANNUAL' : 'MONTHLY']) }}"
               class="relative inline-flex h-7 w-14 items-center rounded-full transition-colors focus:outline-none {{ $billingCycle === 'ANNUAL' ? 'bg-indigo-600' : 'bg-slate-300 dark:bg-slate-700' }}">
                <span class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform shadow-md {{ $billingCycle === 'ANNUAL' ? 'translate-x-8' : 'translate-x-1' }}"></span>
            </a>

            <span class="text-xs font-bold flex items-center gap-1.5 {{ $billingCycle === 'ANNUAL' ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">
                Annual Billing
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500 text-white shadow-sm">
                    SAVE 33% (4 Mos Free)
                </span>
            </span>
        </div>
    </div>

    <!-- 3 Tier Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
        @foreach($plans as $planKey => $plan)
            @php
                $isPopular = $plan['popular'];
                $price = ($billingCycle === 'ANNUAL') ? $plan['annual_price'] : $plan['monthly_price'];
                $equivMonthly = ($billingCycle === 'ANNUAL') ? $plan['annual_monthly_equiv'] : $plan['monthly_price'];
                $isCurrent = ($user && $user->subscription_tier === $planKey && $user->hasActiveSubscription());
            @endphp

            <div class="glass-panel rounded-3xl p-8 border flex flex-col justify-between transition-all relative {{ $isPopular ? 'border-2 border-indigo-500 ring-4 ring-indigo-500/10 bg-indigo-50/20 dark:bg-indigo-950/20 shadow-2xl scale-105 z-10' : 'border-slate-200 dark:border-slate-800' }}">
                
                @if($isPopular)
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                        <span class="px-4 py-1 rounded-full text-xs font-black bg-gradient-to-r from-indigo-600 to-emerald-500 text-white shadow-md uppercase tracking-wider">
                            ⭐ {{ $plan['badge'] }}
                        </span>
                    </div>
                @endif

                <div class="space-y-6">
                    <!-- Plan Title & Badge -->
                    <div>
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $plan['name'] }}</h3>
                            @if(!$isPopular)
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">{{ $plan['badge'] }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $plan['tagline'] }}</p>
                    </div>

                    <!-- Price Block -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white">₹{{ number_format($equivMonthly) }}</span>
                            <span class="text-xs text-slate-500 font-semibold">/ month</span>
                        </div>
                        @if($billingCycle === 'ANNUAL')
                            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">
                                Billed annually at ₹{{ number_format($price) }}/year
                            </div>
                        @else
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                Billed monthly. Cancel anytime.
                            </div>
                        @endif
                    </div>

                    <!-- CTA Button -->
                    <div>
                        @if($isCurrent)
                            <button disabled class="w-full py-3 px-4 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 font-bold text-xs cursor-default">
                                ✓ Current Active Plan
                            </button>
                        @else
                            <a href="{{ route('pricing.checkout', ['tier' => $planKey, 'billing' => $billingCycle]) }}"
                               class="w-full py-3 px-4 rounded-xl text-center font-bold text-xs transition shadow-lg block {{ $isPopular ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-500/25' : 'bg-slate-900 dark:bg-white hover:bg-slate-800 dark:hover:bg-slate-100 text-white dark:text-slate-900' }}">
                                Subscribe to {{ $plan['name'] }} →
                            </a>
                        @endif
                    </div>

                    <!-- Feature Checklist -->
                    <div class="pt-6 border-t border-slate-100 dark:border-slate-800 space-y-3">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Included Facilities:</span>
                        <ul class="space-y-2.5 text-xs font-medium">
                            @foreach($plan['features'] as $feat => $included)
                                <li class="flex items-center gap-2.5 {{ $included ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-600 line-through' }}">
                                    @if($included)
                                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <span class="w-4 h-4 flex items-center justify-center text-slate-400 flex-shrink-0">✕</span>
                                    @endif
                                    <span>{{ $feat }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="mt-8 pt-4 border-t border-slate-100 dark:border-slate-800/60 text-[11px] text-center text-slate-400">
                    Auto-recurring via UPI, Cards, NetBanking
                </div>
            </div>
        @endforeach
    </div>

    <!-- Free Trial Banner -->
    <div class="glass-panel p-6 rounded-3xl border border-emerald-500/30 bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-indigo-500/10 text-center space-y-3">
        <h3 class="text-lg font-black text-slate-900 dark:text-white">
            Not ready to subscribe? Test everything with our 7-Day Free Trial.
        </h3>
        <p class="text-xs text-slate-600 dark:text-slate-300 max-w-xl mx-auto">
            Get complete access to 52W High breakouts, Volume Gainers, and Pro analytics for 7 full trading sessions. No credit card required.
        </p>
        <a href="{{ route('register') }}"
           class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-500/25 transition">
            <span>🎁</span> Start 7-Day Free Trial Now
        </a>
    </div>

    <!-- Frequently Asked Questions -->
    <div class="space-y-6 pt-6">
        <div class="text-center">
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">Frequently Asked Questions</h2>
            <p class="text-xs text-slate-500 mt-1">Everything you need to know about billing and platform access</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="glass-panel p-5 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-1.5">
                <h4 class="font-bold text-slate-900 dark:text-white">What payment methods are supported?</h4>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    We support all Indian payment methods including UPI (Google Pay, PhonePe, Paytm, BHIM), Credit/Debit Cards (Visa, MasterCard, RuPay), NetBanking across 50+ banks, and Wallets.
                </p>
            </div>

            <div class="glass-panel p-5 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-1.5">
                <h4 class="font-bold text-slate-900 dark:text-white">Can I change or cancel my subscription anytime?</h4>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    Yes! You can upgrade, downgrade, or cancel your auto-renewal at any time directly from your Profile settings with zero cancellation penalties.
                </p>
            </div>

            <div class="glass-panel p-5 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-1.5">
                <h4 class="font-bold text-slate-900 dark:text-white">What is the difference between Pro and Elite?</h4>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    Pro is ideal for active swing traders who need pattern scanners and candidate setups. Elite is designed for quantitative hedge traders who need the complete 6-Model Strategy Simulator, 1-click batch trade journal logging, and live scraper triggers.
                </p>
            </div>

            <div class="glass-panel p-5 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-1.5">
                <h4 class="font-bold text-slate-900 dark:text-white">Do you offer GST Invoices for business expense?</h4>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    Yes, all invoices contain full GST breakdown and can be claimed for business expenditure deductions under capital market research.
                </p>
            </div>
        </div>
    </div>

</div>
@endsection
