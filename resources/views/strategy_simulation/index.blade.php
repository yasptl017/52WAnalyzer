@extends('layouts.app')

@section('title', 'Strategy Simulator & Forward-Testing Engine - 52WAnalyzer')

@section('content')
<div class="space-y-6">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                    🔬 Quantitative Forward-Test Lab
                </span>
                <span class="text-xs text-slate-500 dark:text-slate-400">• Multi-Strategy Evaluator</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                Forward-Testing & Strategy Simulator
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-0.5">
                Evaluate daily stock buying models with custom capital, SME/Upper Circuit exclusions, and head-to-head holding period comparisons.
            </p>
        </div>

        <!-- Quick Top Actions -->
        <div class="flex items-center gap-3">
            <button type="button" onclick="document.getElementById('batchLogModal').classList.remove('hidden')"
                    class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-500/25 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span>🚀 Batch Log to Trade Journal</span>
            </button>
            <a href="{{ route('journal.index') }}"
               class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs rounded-xl transition flex items-center gap-1.5">
                <span>📖 View Journal</span>
            </a>
        </div>
    </div>

    <!-- Parameter Tuning & Filter Control Panel -->
    <div class="glass-panel p-5 rounded-2xl border border-slate-200 dark:border-slate-800">
        <form method="GET" action="{{ route('strategy-simulation.index') }}" id="simulationForm" class="space-y-4">
            
            <!-- Top Controls Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Universe Selector -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                        <span>🎯 Trading Universe</span>
                    </label>
                    <select name="universe" onchange="this.form.submit()"
                            class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                        <option value="52WH" {{ $universe === '52WH' ? 'selected' : '' }}>🔥 52-Week High Stocks (Momentum Highs)</option>
                        <option value="VG" {{ $universe === 'VG' ? 'selected' : '' }}>⚡ Volume Gainers (Spurt Breakouts)</option>
                        <option value="BOTH" {{ $universe === 'BOTH' ? 'selected' : '' }}>👑 Both Confluence (52WH + Volume Gainer)</option>
                    </select>
                </div>

                <!-- Capital Per Stock -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 flex items-center justify-between">
                        <span>💰 Capital / Stock (₹)</span>
                        <span class="text-[10px] text-slate-400">Qty = ⌊₹ / Entry⌋</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-slate-400">₹</span>
                        <input type="number" name="capital" id="capitalInput" value="{{ $capitalPerStock }}" step="500" min="500" max="1000000"
                               class="w-full pl-7 pr-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                    </div>
                </div>

                <!-- From Date -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        📅 Start Date (Lookback)
                    </label>
                    <input type="date" name="from_date" value="{{ $fromDate }}" min="{{ $minDbDate }}" max="{{ $maxDbDate }}"
                           class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                </div>

                <!-- To Date -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        📅 End Date (Forward Limit)
                    </label>
                    <input type="date" name="to_date" value="{{ $toDate }}" min="{{ $minDbDate }}" max="{{ $maxDbDate }}"
                           class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                </div>
            </div>

            <!-- Bottom Row: Presets, Exclusion Toggles, & Submit -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                
                <!-- Quick Capital Preset Buttons -->
                <div class="flex items-center gap-1.5 overflow-x-auto w-full sm:w-auto">
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mr-1">Quick Budget:</span>
                    <button type="button" onclick="setCapital(5000)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-indigo-50 dark:bg-slate-800 dark:hover:bg-indigo-950/40 text-slate-700 dark:text-slate-300 transition">₹5,000</button>
                    <button type="button" onclick="setCapital(10000)" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30 transition">₹10,000 (Default)</button>
                    <button type="button" onclick="setCapital(25000)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-indigo-50 dark:bg-slate-800 dark:hover:bg-indigo-950/40 text-slate-700 dark:text-slate-300 transition">₹25,000</button>
                    <button type="button" onclick="setCapital(50000)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-indigo-50 dark:bg-slate-800 dark:hover:bg-indigo-950/40 text-slate-700 dark:text-slate-300 transition">₹50,000</button>
                    <button type="button" onclick="setCapital(100000)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-indigo-50 dark:bg-slate-800 dark:hover:bg-indigo-950/40 text-slate-700 dark:text-slate-300 transition">₹1,00,000</button>
                </div>

                <!-- Exclusion Checkboxes & Run Button -->
                <div class="flex items-center gap-4 w-full sm:w-auto justify-end">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="exclude_sme" value="1" {{ $excludeSme ? 'checked' : '' }}
                               class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                        <span>🛡️ Avoid SME Stocks</span>
                    </label>

                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="exclude_uc" value="1" {{ $excludeUc ? 'checked' : '' }}
                               class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                        <span>⚡ Avoid Upper Circuit Locks</span>
                    </label>

                    <input type="hidden" name="active_strat" value="{{ $activeStratKey }}">

                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-500/20 transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Run Simulation</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    @if($champion)
    <!-- 🏆 Strategy Champion & Empirical Verdict Banner -->
    <div class="glass-panel p-6 rounded-3xl border-2 border-emerald-500/40 bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-indigo-500/10 dark:from-emerald-950/40 dark:via-teal-950/20 dark:to-indigo-950/30 relative overflow-hidden">
        <div class="absolute -right-8 -bottom-8 opacity-10 dark:opacity-5 pointer-events-none">
            <span class="text-9xl">🏆</span>
        </div>
        
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
            <div class="space-y-2 max-w-2xl">
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-500 text-white shadow-md shadow-emerald-500/30 flex items-center gap-1.5">
                        <span>🏆</span> MOST FAVORABLE STRATEGY FOR REWARD
                    </span>
                    <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300">
                        {{ $champion['name'] }}
                    </span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    {{ $champion['badge'] }} delivers the highest risk-adjusted reward
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    Based on quantitative forward simulation across <strong>{{ number_format($champion['total_trades']) }} deliverable signals</strong>, holding for <strong>{{ $champion['name'] }}</strong> generates a superior <strong>+{{ $champion['roi_pct'] }}% Total Return</strong> with a <strong>{{ $champion['win_rate'] }}% Win Rate</strong> and <strong>{{ $champion['profit_factor'] }}x Profit Factor</strong>.
                </p>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 italic">
                    💡 <strong>Quantitative Insight:</strong> BTST exits frequently experience friction from morning whipsaws. Extending holding to 15–30 sessions allows institutional momentum markup cycles to mature and compound gains.
                </div>
            </div>

            <!-- Key Champion Metrics -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 w-full md:w-auto flex-shrink-0">
                <div class="bg-white/80 dark:bg-slate-900/80 p-3.5 rounded-2xl border border-emerald-200 dark:border-emerald-500/30 text-center shadow-sm">
                    <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Net P&L</div>
                    <div class="text-lg sm:text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">
                        ₹{{ number_format($champion['total_pnl'], 0) }}
                    </div>
                    <div class="text-[10px] text-slate-500 mt-0.5">+{{ $champion['roi_pct'] }}% ROI</div>
                </div>

                <div class="bg-white/80 dark:bg-slate-900/80 p-3.5 rounded-2xl border border-emerald-200 dark:border-emerald-500/30 text-center shadow-sm">
                    <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Win Rate</div>
                    <div class="text-lg sm:text-xl font-black text-indigo-600 dark:text-indigo-400 mt-0.5">
                        {{ $champion['win_rate'] }}%
                    </div>
                    <div class="text-[10px] text-slate-500 mt-0.5">{{ number_format($champion['wins']) }}W / {{ number_format($champion['losses']) }}L</div>
                </div>

                <div class="bg-white/80 dark:bg-slate-900/80 p-3.5 rounded-2xl border border-emerald-200 dark:border-emerald-500/30 text-center shadow-sm col-span-2 sm:col-span-1">
                    <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Profit Factor</div>
                    <div class="text-lg sm:text-xl font-black text-amber-600 dark:text-amber-400 mt-0.5">
                        {{ $champion['profit_factor'] }}x
                    </div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Score: {{ $champion['reward_score'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Comparative Leaderboard: 6 Strategies Head-to-Head Cards -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <span>📊</span> Head-to-Head Strategy Leaderboard
            </h3>
            <span class="text-xs text-slate-500 dark:text-slate-400">Click any card to inspect simulated trade log</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($strategyResults as $key => $strat)
                @php
                    $isActive = ($key === $activeStratKey);
                    $isWinner = ($champion && $champion['key'] === $key);
                @endphp
                <a href="{{ request()->fullUrlWithQuery(['active_strat' => $key, 'page' => 1]) }}"
                   class="glass-card p-5 rounded-2xl border transition-all relative group cursor-pointer block {{ $isActive ? 'ring-2 ring-indigo-500 border-indigo-400 bg-indigo-50/40 dark:bg-indigo-950/20' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
                    
                    <!-- Top Badge & Rank -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 transition">
                            {{ $strat['badge'] }}
                        </span>
                        @if($isWinner)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500 text-white flex items-center gap-1 shadow-sm">
                                <span>🏆</span> BEST REWARD
                            </span>
                        @elseif($isActive)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500 text-white">
                                Active Tab
                            </span>
                        @endif
                    </div>

                    <!-- Strategy Name & Brief -->
                    <h4 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                        {{ $strat['name'] }}
                    </h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 h-8">
                        {{ $strat['description'] }}
                    </p>

                    <!-- Key Metrics Grid -->
                    <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80">
                        <div>
                            <span class="text-[10px] font-semibold text-slate-400 uppercase">Net Realized P&L</span>
                            <div class="text-sm font-extrabold {{ $strat['total_pnl'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                ₹{{ number_format($strat['total_pnl'], 0) }}
                            </div>
                            <span class="text-[10px] font-medium text-slate-500">ROI: {{ $strat['roi_pct'] }}%</span>
                        </div>

                        <div>
                            <span class="text-[10px] font-semibold text-slate-400 uppercase">Win Rate %</span>
                            <div class="text-sm font-extrabold text-indigo-600 dark:text-indigo-400">
                                {{ $strat['win_rate'] }}%
                            </div>
                            <span class="text-[10px] font-medium text-slate-500">PF: {{ $strat['profit_factor'] }}x</span>
                        </div>

                        <div>
                            <span class="text-[10px] font-semibold text-slate-400 uppercase">Avg Gain / Trade</span>
                            <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                +{{ $strat['avg_gain_pct'] }}%
                            </div>
                        </div>

                        <div>
                            <span class="text-[10px] font-semibold text-slate-400 uppercase">Total Trades</span>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                {{ number_format($strat['closed_trades']) }} closed
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar for Win Rate -->
                    <div class="mt-3">
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-500 to-emerald-400 h-1.5 rounded-full" style="width: {{ min(100, $strat['win_rate']) }}%"></div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Active Strategy Trade Drill-Down Table & Search -->
    <div class="glass-panel p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4">
        
        <!-- Table Header & Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">
                        Trade Log: {{ $strategyResults[$activeStratKey]['name'] ?? 'Strategy' }}
                    </h3>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30">
                        {{ number_format($totalFiltered) }} Trades
                    </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Individual simulated executions with dynamic sizing based on ₹{{ number_format($capitalPerStock) }} per stock.
                </p>
            </div>

            <!-- Search and Status Filter Controls -->
            <form method="GET" action="{{ route('strategy-simulation.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="universe" value="{{ $universe }}">
                <input type="hidden" name="capital" value="{{ $capitalPerStock }}">
                <input type="hidden" name="from_date" value="{{ $fromDate }}">
                <input type="hidden" name="to_date" value="{{ $toDate }}">
                <input type="hidden" name="exclude_sme" value="{{ $excludeSme ? '1' : '0' }}">
                <input type="hidden" name="exclude_uc" value="{{ $excludeUc ? '1' : '0' }}">
                <input type="hidden" name="active_strat" value="{{ $activeStratKey }}">

                <!-- Status Filter Pills -->
                <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1">
                    <button type="submit" name="status" value="ALL" class="px-2.5 py-1 text-xs font-bold rounded-lg transition {{ $statusFilter === 'ALL' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400' }}">All</button>
                    <button type="submit" name="status" value="WON" class="px-2.5 py-1 text-xs font-bold rounded-lg transition {{ $statusFilter === 'WON' ? 'bg-emerald-500 text-white shadow-sm' : 'text-emerald-600 dark:text-emerald-400' }}">Won</button>
                    <button type="submit" name="status" value="LOST" class="px-2.5 py-1 text-xs font-bold rounded-lg transition {{ $statusFilter === 'LOST' ? 'bg-rose-500 text-white shadow-sm' : 'text-rose-600 dark:text-rose-400' }}">Lost</button>
                    <button type="submit" name="status" value="OPEN" class="px-2.5 py-1 text-xs font-bold rounded-lg transition {{ $statusFilter === 'OPEN' ? 'bg-indigo-500 text-white shadow-sm' : 'text-indigo-600 dark:text-indigo-400' }}">Open</button>
                </div>

                <!-- Symbol Search Input -->
                <input type="text" name="search" value="{{ $search }}" placeholder="Search symbol..."
                       class="w-36 sm:w-44 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">

                <button type="submit" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-sm transition">
                    Search
                </button>
                @if($search || $statusFilter !== 'ALL')
                    <a href="{{ request()->fullUrlWithQuery(['search' => '', 'status' => 'ALL', 'page' => 1]) }}"
                       class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs rounded-xl hover:bg-slate-200 transition">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Trade Table -->
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-850 text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[10px] font-bold">
                    <tr>
                        <th class="px-4 py-3">Symbol</th>
                        <th class="px-3 py-3">Entry Date</th>
                        <th class="px-3 py-3 text-right">Entry Price</th>
                        <th class="px-3 py-3 text-right">Sizing (Qty / Inv)</th>
                        <th class="px-3 py-3">Exit Date</th>
                        <th class="px-3 py-3 text-right">Exit Price</th>
                        <th class="px-3 py-3 text-right">P&L (₹)</th>
                        <th class="px-3 py-3 text-right">P&L (%)</th>
                        <th class="px-3 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900 font-medium">
                    @forelse($paginatedTrades as $trade)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <a href="https://www.tradingview.com/chart/?symbol=NSE%3A{{ $trade['symbol'] }}" target="_blank"
                                   class="hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-1">
                                    {{ $trade['symbol'] }}
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                                <span class="text-[9px] px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 font-mono">{{ $trade['series'] }}</span>
                            </td>
                            <td class="px-3 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $trade['entry_date'] }}
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-slate-900 dark:text-white">
                                ₹{{ number_format($trade['entry_price'], 2) }}
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $trade['quantity'] }} shs</span>
                                <span class="text-[10px] text-slate-400 block">₹{{ number_format($trade['invested'], 0) }}</span>
                            </td>
                            <td class="px-3 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $trade['exit_date'] ?? '—' }}
                                @if(isset($trade['exit_reason']) && $trade['exit_reason'] !== 'Time Horizon')
                                    <span class="block text-[9px] text-indigo-500 font-semibold">{{ $trade['exit_reason'] }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-slate-900 dark:text-white">
                                {{ $trade['exit_price'] ? '₹' . number_format($trade['exit_price'], 2) : '—' }}
                            </td>
                            <td class="px-3 py-3 text-right font-bold whitespace-nowrap {{ ($trade['pnl'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $trade['pnl'] !== null ? ($trade['pnl'] >= 0 ? '+' : '') . '₹' . number_format($trade['pnl'], 2) : '—' }}
                            </td>
                            <td class="px-3 py-3 text-right font-bold whitespace-nowrap {{ ($trade['pnl_pct'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $trade['pnl_pct'] !== null ? ($trade['pnl_pct'] >= 0 ? '+' : '') . number_format($trade['pnl_pct'], 2) . '%' : '—' }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($trade['status'] === 'WON')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                        WON
                                    </span>
                                @elseif($trade['status'] === 'LOST')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20">
                                        LOST
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-500/20">
                                        OPEN
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <form method="POST" action="{{ route('journal.store') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="symbol" value="{{ $trade['symbol'] }}">
                                    <input type="hidden" name="entry_date" value="{{ $trade['entry_date'] }}">
                                    <input type="hidden" name="entry_price" value="{{ $trade['entry_price'] }}">
                                    <input type="hidden" name="stop_loss" value="{{ round($trade['entry_price'] * 0.95, 2) }}">
                                    <input type="hidden" name="target_price" value="{{ round($trade['entry_price'] * 1.15, 2) }}">
                                    <input type="hidden" name="quantity" value="{{ $trade['quantity'] }}">
                                    <input type="hidden" name="status" value="{{ $trade['status'] }}">
                                    <input type="hidden" name="exit_date" value="{{ $trade['exit_date'] ?? '' }}">
                                    <input type="hidden" name="exit_price" value="{{ $trade['exit_price'] ?? '' }}">
                                    <input type="hidden" name="notes" value="Forward-Test [{{ $universe }}] {{ $strategyResults[$activeStratKey]['badge'] ?? '' }} (Budget ₹{{ $capitalPerStock }})">
                                    
                                    <button type="submit" title="Log this individual trade to your Trade Journal"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30 transition flex items-center gap-1 mx-auto">
                                        <span>➕</span> Log
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="text-3xl mb-2">🔍</div>
                                <div class="font-bold text-slate-700 dark:text-slate-300">No simulated trades found matching the current criteria.</div>
                                <div class="text-xs text-slate-500 mt-1">Try broadening the date range, changing universe, or adjusting capital sizing.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        @if($totalPages > 1)
            <div class="flex items-center justify-between pt-2">
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    Showing Page <strong>{{ $currentPage }}</strong> of <strong>{{ $totalPages }}</strong> ({{ number_format($totalFiltered) }} total trades)
                </span>
                <div class="flex items-center gap-1.5">
                    @if($currentPage > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                            ← Previous
                        </a>
                    @endif

                    @if($currentPage < $totalPages)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-sm">
                            Next →
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>

</div>

<!-- Batch Log Modal -->
<div id="batchLogModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="glass-panel w-full max-w-lg p-6 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl relative">
        <button type="button" onclick="document.getElementById('batchLogModal').classList.add('hidden')"
                class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl font-bold">
                🚀
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Batch Log Forward-Test Trades</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Import simulated trades into your live Trade Journal.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('strategy-simulation.forward-test-journal') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="universe" value="{{ $universe }}">
            <input type="hidden" name="capital" value="{{ $capitalPerStock }}">
            <input type="hidden" name="from_date" value="{{ $fromDate }}">
            <input type="hidden" name="to_date" value="{{ $toDate }}">

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Select Strategy to Log</label>
                <select name="strategy_key" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 dark:text-white">
                    @foreach($strategyResults as $k => $s)
                        <option value="{{ $k }}" {{ $k === $activeStratKey ? 'selected' : '' }}>
                            {{ $s['name'] }} (ROI: +{{ $s['roi_pct'] }}%, Win Rate: {{ $s['win_rate'] }}%)
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Trade Filter Mode</label>
                <select name="mode" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 dark:text-white">
                    <option value="ALL">All Trades (Open + Closed, Up to 500 safety cap)</option>
                    <option value="OPEN_ONLY">Open Trades Only (Currently active live holdings)</option>
                    <option value="CLOSED_ONLY">Closed Historical Trades Only (For statistical benchmarking)</option>
                </select>
            </div>

            <div class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-500/30 text-xs text-amber-800 dark:text-amber-300">
                ⚠️ <strong>Note:</strong> Logged trades will appear in your Trade Journal dashboard where you can edit stop-losses, targets, or track forward performance.
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('batchLogModal').classList.add('hidden')"
                        class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs rounded-xl transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-500/25 transition">
                    🚀 Confirm & Log Trades
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function setCapital(amount) {
        document.getElementById('capitalInput').value = amount;
        document.getElementById('simulationForm').submit();
    }
</script>
@endsection
