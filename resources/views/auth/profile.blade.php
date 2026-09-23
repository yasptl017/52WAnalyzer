@extends('layouts.app')

@section('title', 'Trader Profile & Subscription - 52WAnalyzer')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                Account & Subscription
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Manage your membership tier, payment history, and trading entitlements.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('pricing.index') }}"
               class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-emerald-600 hover:from-indigo-500 hover:to-emerald-500 text-white text-xs font-bold rounded-xl shadow-md transition flex items-center gap-1.5">
                <span>⚡</span> Upgrade / Change Plan
            </a>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="px-3.5 py-2 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 dark:bg-slate-800 dark:hover:bg-rose-950/40 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition">
                    Sign Out
                </button>
            </form>
        </div>
    </div>

    <!-- User & Subscription Overview Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- User Details Card -->
        <div class="glass-panel p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-black text-lg flex items-center justify-center border border-indigo-500/20">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $user->name }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                </div>
            </div>

            <div class="space-y-2 pt-3 border-t border-slate-100 dark:border-slate-800/80 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Phone:</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $user->phone ?: 'Not provided' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Member Since:</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $user->created_at->format('M d, Y') }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">System Role:</span>
                    <span class="font-mono uppercase font-bold text-indigo-600 dark:text-indigo-400">{{ $user->role }}</span>
                </div>
            </div>
        </div>

        <!-- Active Subscription Card -->
        <div class="glass-panel p-6 rounded-2xl border-2 border-indigo-500/30 bg-gradient-to-br from-indigo-500/5 to-emerald-500/5 dark:from-indigo-950/20 dark:to-emerald-950/10 md:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-black border {{ $tierBadge['bg'] }} flex items-center gap-1.5 shadow-sm">
                        <span>{{ $tierBadge['icon'] }}</span> {{ $tierBadge['name'] }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $user->hasActiveSubscription() ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20' }}">
                        {{ $user->subscription_status }}
                    </span>
                </div>
                <span class="text-xs text-slate-500 font-mono">{{ $user->billing_cycle }} BILLING</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                <div class="bg-white/80 dark:bg-slate-900/80 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Plan Tier</span>
                    <div class="text-base font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $user->subscription_tier }}</div>
                </div>

                <div class="bg-white/80 dark:bg-slate-900/80 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Valid Until</span>
                    <div class="text-sm font-extrabold text-slate-900 dark:text-white mt-0.5">
                        @if($user->isTrialActive())
                            {{ $user->trial_ends_at ? $user->trial_ends_at->format('M d, Y') : 'Active' }}
                        @elseif($user->subscription_ends_at)
                            {{ $user->subscription_ends_at->format('M d, Y') }}
                        @else
                            Lifetime / Open
                        @endif
                    </div>
                </div>

                <div class="bg-white/80 dark:bg-slate-900/80 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Days Remaining</span>
                    <div class="text-base font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">
                        @if($user->isTrialActive() && $user->trial_ends_at)
                            {{ (int)ceil(now()->diffInDays($user->trial_ends_at, false)) }} Days (Trial)
                        @elseif($user->subscription_ends_at)
                            {{ (int)ceil(now()->diffInDays($user->subscription_ends_at, false)) }} Days
                        @else
                            Active
                        @endif
                    </div>
                </div>
            </div>

            <!-- Feature Entitlements Breakdown -->
            <div class="pt-3 border-t border-slate-200/80 dark:border-slate-800 text-xs">
                <span class="font-bold text-slate-700 dark:text-slate-300 block mb-1.5">Enabled Facilities:</span>
                <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-[10px] font-semibold">✓ 52W Highs Hub</span>
                    <span class="px-2 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-[10px] font-semibold">✓ Volume Gainers</span>
                    <span class="px-2 py-0.5 rounded-lg {{ $user->isStarter() ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} text-[10px] font-semibold">✓ Date Matrix (7D+)</span>
                    <span class="px-2 py-0.5 rounded-lg {{ $user->isPro() ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} text-[10px] font-semibold">✓ Re-Emergence (>5, >10, >15)</span>
                    <span class="px-2 py-0.5 rounded-lg {{ $user->isPro() ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} text-[10px] font-semibold">✓ Pattern Scanner & Avoid List</span>
                    <span class="px-2 py-0.5 rounded-lg {{ $user->isElite() ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} text-[10px] font-semibold">✓ Strategy Forward-Simulator</span>
                    <span class="px-2 py-0.5 rounded-lg {{ $user->isElite() ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} text-[10px] font-semibold">✓ Batch Journal Ingestion</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Developer Instant Role / Tier Switcher Bar (Demo & Testing Helper) -->
    <div class="glass-panel p-4 rounded-2xl border border-amber-200 dark:border-amber-500/30 bg-amber-50/50 dark:bg-amber-950/20 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-base">🧪</span>
            <div>
                <span class="text-xs font-bold text-amber-900 dark:text-amber-300">Instant Demo Role Switcher</span>
                <p class="text-[11px] text-amber-700 dark:text-amber-400">Switch user role instantly for testing feature gates without card payments.</p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            <a href="{{ route('profile.switch-tier', ['tier' => 'ADMIN']) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-600 text-white hover:bg-rose-500 transition shadow-sm">Super Admin</a>
            <a href="{{ route('profile.switch-tier', ['tier' => 'ELITE']) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-600 text-white hover:bg-purple-500 transition shadow-sm">👑 Elite Plan</a>
            <a href="{{ route('profile.switch-tier', ['tier' => 'PRO']) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-500 transition shadow-sm">⭐ Pro Plan</a>
            <a href="{{ route('profile.switch-tier', ['tier' => 'STARTER']) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-600 text-white hover:bg-blue-500 transition shadow-sm">🚀 Starter Plan</a>
            <a href="{{ route('profile.switch-tier', ['tier' => 'TRIAL']) }}" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-500 transition shadow-sm">🎁 7D Trial</a>
            <a href="{{ route('profile.switch-tier', ['tier' => 'GUEST']) }}" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-600 text-white hover:bg-slate-500 transition shadow-sm">👤 Expired/Guest</a>
        </div>
    </div>

    <!-- Payment & Invoice History -->
    <div class="glass-panel p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
            <span>🧾</span> Payment & Invoice History
        </h3>

        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-850 text-slate-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="px-4 py-3">Invoice / Ref ID</th>
                        <th class="px-3 py-3">Plan Tier</th>
                        <th class="px-3 py-3">Billing</th>
                        <th class="px-3 py-3">Amount (INR)</th>
                        <th class="px-3 py-3">Method</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-4 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900 font-medium">
                    @forelse($payments as $pay)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $pay->gateway_payment_id ?: 'INV-' . $pay->id }}
                            </td>
                            <td class="px-3 py-3 font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $pay->plan_tier }}
                            </td>
                            <td class="px-3 py-3 text-slate-600 dark:text-slate-300">
                                {{ $pay->billing_cycle }}
                            </td>
                            <td class="px-3 py-3 font-bold text-slate-900 dark:text-white">
                                ₹{{ number_format($pay->amount, 2) }}
                            </td>
                            <td class="px-3 py-3 text-slate-600 dark:text-slate-300">
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10px]">{{ $pay->payment_method }}</span>
                            </td>
                            <td class="px-3 py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                    {{ $pay->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">
                                {{ $pay->created_at->format('M d, Y h:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                No payment transactions recorded yet. Active on Free Trial.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
