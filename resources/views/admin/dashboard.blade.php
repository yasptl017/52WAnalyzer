@extends('layouts.app')

@section('title', 'Admin Subscription Analytics & User Management — 52WAnalyzer')

@section('content')
<div class="space-y-6">
    <!-- Top Header & Breadcrumbs -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span class="px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-bold uppercase tracking-wider text-[10px]">
                    🛡️ Admin Control Panel
                </span>
                <span>•</span>
                <span>Executive Analytics & Subscriptions</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                Subscription Analytics & User Manager
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                Real-time monetization metrics, MRR/ARR run rates, tier conversions, payment ledger, and user entitlement management.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.export-transactions') }}" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-400 text-slate-700 dark:text-slate-200 shadow-sm transition flex items-center gap-1.5">
                <span>📥</span> Export Transactions CSV
            </a>
            <button onclick="openSystemSyncModal()" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-500/20 transition flex items-center gap-1.5">
                <span>⚡</span> Central NSE Sync
            </button>
        </div>
    </div>

    <!-- 4 High-Level KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- MRR -->
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span class="font-bold uppercase tracking-wider text-[11px]">Monthly Recurring (MRR)</span>
                <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold">₹</span>
            </div>
            <div class="text-2xl sm:text-3xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                ₹{{ number_format($mrr, 0) }}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                <span>ARR Equivalent:</span>
                <span class="font-bold font-mono text-slate-800 dark:text-slate-200">₹{{ number_format($arr, 0) }}</span>
            </div>
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-emerald-500/5 rounded-full blur-xl pointer-events-none"></div>
        </div>

        <!-- Total Paid Active Subscribers -->
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span class="font-bold uppercase tracking-wider text-[11px]">Active Paid Traders</span>
                <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-bold">👥</span>
            </div>
            <div class="text-2xl sm:text-3xl font-black font-mono text-indigo-600 dark:text-indigo-400 mt-1">
                {{ $activeSubscribers }} <span class="text-xs font-normal text-slate-400">/ {{ $totalUsers }} total</span>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                <span>ARPU:</span>
                <span class="font-bold font-mono text-slate-800 dark:text-slate-200">₹{{ number_format($arpu, 0) }} / user</span>
            </div>
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-indigo-500/5 rounded-full blur-xl pointer-events-none"></div>
        </div>

        <!-- Trial Conversion Rate -->
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span class="font-bold uppercase tracking-wider text-[11px]">Trial Conversion Rate</span>
                <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold">📈</span>
            </div>
            <div class="text-2xl sm:text-3xl font-black font-mono text-amber-500 dark:text-amber-400 mt-1">
                {{ $trialConversionRate }}%
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                <span>Active Trials:</span>
                <span class="font-bold font-mono text-slate-800 dark:text-slate-200">{{ $trialUsers }} users</span>
            </div>
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-amber-500/5 rounded-full blur-xl pointer-events-none"></div>
        </div>

        <!-- Total All-Time Gross Collections -->
        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span class="font-bold uppercase tracking-wider text-[11px]">Gross Collections</span>
                <span class="p-1.5 rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 font-bold">💳</span>
            </div>
            <div class="text-2xl sm:text-3xl font-black font-mono text-cyan-600 dark:text-cyan-400 mt-1">
                ₹{{ number_format($totalGrossRevenue, 0) }}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                <span>Successful Transactions:</span>
                <span class="font-bold font-mono text-slate-800 dark:text-slate-200">{{ $recentPayments->count() }}+</span>
            </div>
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-cyan-500/5 rounded-full blur-xl pointer-events-none"></div>
        </div>
    </div>

    <!-- Tier Breakdown Cards & Expiring Subscriptions Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Subscription Tier Distribution -->
        <div class="p-6 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Active Subscription Breakdown by Tier</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">User distribution across monetization tiers</p>
                </div>
                <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">100% Monetization Coverage</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <!-- Elite Card -->
                <div class="p-4 rounded-xl border border-purple-500/20 bg-purple-500/5">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-bold text-purple-700 dark:text-purple-400">👑 Elite Algo</span>
                        <span class="text-[10px] font-mono text-slate-500">₹4,999/mo</span>
                    </div>
                    <div class="text-2xl font-black font-mono text-purple-900 dark:text-purple-300">
                        {{ $tierCounts['ELITE'] }}
                    </div>
                    <div class="text-[10px] text-purple-600 dark:text-purple-400 mt-1">Full Forward Simulator</div>
                </div>

                <!-- Pro Card -->
                <div class="p-4 rounded-xl border border-indigo-500/20 bg-indigo-500/5">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-bold text-indigo-700 dark:text-indigo-400">⭐ Pro Swing</span>
                        <span class="text-[10px] font-mono text-slate-500">₹2,499/mo</span>
                    </div>
                    <div class="text-2xl font-black font-mono text-indigo-900 dark:text-indigo-300">
                        {{ $tierCounts['PRO'] }}
                    </div>
                    <div class="text-[10px] text-indigo-600 dark:text-indigo-400 mt-1">Patterns & Multi-Tab Matrix</div>
                </div>

                <!-- Starter Card -->
                <div class="p-4 rounded-xl border border-teal-500/20 bg-teal-500/5">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-bold text-teal-700 dark:text-teal-400">🚀 Starter</span>
                        <span class="text-[10px] font-mono text-slate-500">₹999/mo</span>
                    </div>
                    <div class="text-2xl font-black font-mono text-teal-900 dark:text-teal-300">
                        {{ $tierCounts['STARTER'] }}
                    </div>
                    <div class="text-[10px] text-teal-600 dark:text-teal-400 mt-1">Daily 52WH & VG</div>
                </div>

                <!-- Free Trial Card -->
                <div class="p-4 rounded-xl border border-amber-500/20 bg-amber-500/5">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-bold text-amber-700 dark:text-amber-400">🎁 Free Trial</span>
                        <span class="text-[10px] font-mono text-slate-500">7 Days</span>
                    </div>
                    <div class="text-2xl font-black font-mono text-amber-900 dark:text-amber-300">
                        {{ $trialUsers }}
                    </div>
                    <div class="text-[10px] text-amber-600 dark:text-amber-400 mt-1">Pro Trial Active</div>
                </div>
            </div>
        </div>

        <!-- Subscriptions Expiring Soon -->
        <div class="p-6 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Expiring in Next 7 Days</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Renewal pipeline</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400">
                    {{ $expiringNext7Days->count() }} Traders
                </span>
            </div>

            <div class="space-y-2.5 max-h-52 overflow-y-auto custom-scrollbar">
                @forelse($expiringNext7Days as $exp)
                    <div class="p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-slate-800 dark:text-slate-200">{{ $exp->name }}</div>
                            <div class="text-[10px] text-slate-500">{{ $exp->email }}</div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $exp->getTierBadge()['bg'] }}">
                                {{ $exp->subscription_tier }}
                            </span>
                            <div class="text-[10px] font-mono text-amber-600 dark:text-amber-400 mt-0.5">
                                {{ $exp->subscription_ends_at ? $exp->subscription_ends_at->diffForHumans() : 'Soon' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-slate-400 text-xs">
                        🎉 No paid subscriptions expiring in the next 7 days!
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- User Management & Subscription Override Table -->
    <div class="p-6 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Registered Users & Subscription Manager</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">View, search, and manually update user subscription tiers</p>
            </div>

            <!-- Search and Filter Form -->
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-center gap-2 text-xs">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search name, email, phone..." class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                <select name="tier" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="ALL" {{ $filterTier === 'ALL' ? 'selected' : '' }}>All Tiers</option>
                    <option value="ELITE" {{ $filterTier === 'ELITE' ? 'selected' : '' }}>Elite</option>
                    <option value="PRO" {{ $filterTier === 'PRO' ? 'selected' : '' }}>Pro</option>
                    <option value="STARTER" {{ $filterTier === 'STARTER' ? 'selected' : '' }}>Starter</option>
                    <option value="FREE" {{ $filterTier === 'FREE' ? 'selected' : '' }}>Free / Trial</option>
                </select>
                <select name="status" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="ALL" {{ $filterStatus === 'ALL' ? 'selected' : '' }}>All Status</option>
                    <option value="ACTIVE" {{ $filterStatus === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                    <option value="TRIALING" {{ $filterStatus === 'TRIALING' ? 'selected' : '' }}>Trialing</option>
                    <option value="EXPIRED" {{ $filterStatus === 'EXPIRED' ? 'selected' : '' }}>Expired</option>
                </select>
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-4 py-3 text-left">Trader</th>
                        <th class="px-4 py-3 text-left">Role & Tier</th>
                        <th class="px-4 py-3 text-left">Billing Cycle</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Subscription Ends</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 font-medium">
                    @forelse($users as $u)
                        @php
                            $badge = $u->getTierBadge();
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $u->name }}</div>
                                <div class="text-[11px] text-slate-500">{{ $u->email }}</div>
                                @if($u->phone)
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $u->phone }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $badge['bg'] }}">
                                    {{ $badge['icon'] }} {{ $badge['name'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px]">
                                {{ $u->billing_cycle ?: 'MONTHLY' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($u->subscription_status === 'ACTIVE')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-500/20">
                                        ● ACTIVE
                                    </span>
                                @elseif($u->subscription_status === 'TRIALING')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-500/20">
                                        ⏳ TRIAL
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-500/20">
                                        EXPIRED
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px]">
                                @if($u->subscription_ends_at)
                                    <div>{{ $u->subscription_ends_at->format('d M Y') }}</div>
                                    <div class="text-[10px] text-slate-400">({{ $u->subscription_ends_at->diffForHumans() }})</div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button onclick='openEditUserModal(@json($u))' class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/80 font-bold text-[11px] transition">
                                    ⚙️ Edit Tier
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400 text-xs">
                                No users matched your search criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Financial Payments & Transaction Ledger -->
    <div class="p-6 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Recent Payment Transactions Ledger</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Live payment gateway stream and GST receipts</p>
            </div>
            <span class="text-xs font-mono text-slate-500">Showing last {{ $recentPayments->count() }} transactions</span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-4 py-3 text-left">Payment ID</th>
                        <th class="px-4 py-3 text-left">Trader Name</th>
                        <th class="px-4 py-3 text-left">Plan / Tier</th>
                        <th class="px-4 py-3 text-left">Method</th>
                        <th class="px-4 py-3 text-right">Amount (INR)</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 font-medium font-mono">
                    @forelse($recentPayments as $p)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 text-[11px] text-slate-500">
                                {{ $p->gateway_payment_id }}
                            </td>
                            <td class="px-4 py-3 font-sans font-bold text-slate-900 dark:text-white">
                                {{ $p->user?->name ?: 'N/A' }}
                                <div class="text-[10px] font-sans font-normal text-slate-500">{{ $p->user?->email }}</div>
                            </td>
                            <td class="px-4 py-3 font-sans">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400">
                                    {{ $p->plan_tier }} ({{ $p->billing_cycle }})
                                </span>
                            </td>
                            <td class="px-4 py-3 font-sans text-slate-600 dark:text-slate-300 text-[11px]">
                                {{ $p->payment_method }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">
                                ₹{{ number_format($p->amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center font-sans">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                    {{ $p->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-[11px] text-slate-500 font-sans">
                                {{ $p->created_at->format('d M Y, h:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-400 font-sans text-xs">
                                No payment records logged yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit User Tier Modal -->
<div id="edit-user-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <form id="edit-user-form" method="POST" action="">
            @csrf
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50">
                <div class="flex items-center gap-2">
                    <span class="text-lg">⚙️</span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Edit User Subscription Tier</h3>
                </div>
                <button type="button" onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">&times;</button>
            </div>

            <div class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Trader Name & Email</label>
                    <div id="modal-user-info" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-bold text-slate-900 dark:text-white"></div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">System Role</label>
                        <select name="role" id="modal-role" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="admin">Admin</option>
                            <option value="elite">Elite</option>
                            <option value="pro">Pro</option>
                            <option value="starter">Starter</option>
                            <option value="free_trial">Free Trial</option>
                            <option value="guest">Guest</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Subscription Tier</label>
                        <select name="subscription_tier" id="modal-tier" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="ELITE">ELITE (₹4,999)</option>
                            <option value="PRO">PRO (₹2,499)</option>
                            <option value="STARTER">STARTER (₹999)</option>
                            <option value="FREE">FREE / TRIAL</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select name="subscription_status" id="modal-status" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="ACTIVE">ACTIVE</option>
                            <option value="TRIALING">TRIALING</option>
                            <option value="EXPIRED">EXPIRED</option>
                            <option value="CANCELLED">CANCELLED</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Billing Cycle</label>
                        <select name="billing_cycle" id="modal-cycle" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="MONTHLY">Monthly</option>
                            <option value="ANNUAL">Annual</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Extend Validity (Days)</label>
                    <input type="number" name="extend_days" placeholder="e.g. 30, 90, 365" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500">
                    <p class="text-[10px] text-slate-400 mt-1">Leave blank to keep existing expiration date.</p>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <button type="button" onclick="closeEditUserModal()" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-500/20 transition">
                    💾 Save User Tier
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditUserModal(user) {
    const modal = document.getElementById('edit-user-modal');
    const form = document.getElementById('edit-user-form');
    const info = document.getElementById('modal-user-info');
    
    form.action = `/admin/users/${user.id}/update-tier`;
    info.innerText = `${user.name} (${user.email})`;
    
    document.getElementById('modal-role').value = user.role;
    document.getElementById('modal-tier').value = user.subscription_tier;
    document.getElementById('modal-status').value = user.subscription_status;
    document.getElementById('modal-cycle').value = user.billing_cycle || 'MONTHLY';
    
    modal.classList.remove('hidden');
}

function closeEditUserModal() {
    document.getElementById('edit-user-modal').classList.add('hidden');
}
</script>
@endsection
