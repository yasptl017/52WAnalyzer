@extends('layouts.app')

@section('title', 'Volume Gainers Hub - 52WAnalyzer')

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="glass-panel p-6 rounded-3xl bg-gradient-to-r from-amber-50/80 via-white to-white dark:from-amber-950/40 dark:via-slate-900 dark:to-slate-900 border border-amber-200 dark:border-amber-500/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-400 text-xs font-bold uppercase tracking-wider mb-2">
                <span>⚡</span> Full Market-Wide Discovery Engine
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">NSE Volume Gainers & Spurts</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-2xl">
                Breaks the 25-stock limitation of NSE's default web widget. Evaluates rolling 5-day (1W) and 10-day (2W) average volumes across all listed equities to detect genuine institutional volume surges.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <span class="text-xs uppercase font-bold tracking-wider text-slate-600 dark:text-slate-400">Date:</span>
            <form method="GET" action="{{ route('volume-gainers.index') }}" id="date-form">
                <select name="date" onchange="document.getElementById('date-form').submit()" 
                        class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-white text-sm font-semibold rounded-xl px-3.5 py-2 focus:ring-2 focus:ring-amber-500 focus:outline-none cursor-pointer shadow-sm">
                    @foreach($availableDates as $d)
                        <option value="{{ $d }}" {{ $d === $selectedDate ? 'selected' : '' }}>
                            📅 {{ date('D, d M Y', strtotime($d)) }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Gainers -->
        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Volume Gainers</span>
            <div class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white font-mono">{{ $totalGainers }}</div>
            <div class="text-[11px] text-amber-600 dark:text-amber-400 mt-1 font-semibold">★ Market-Wide Tracked</div>
        </div>

        <!-- Top Spurt -->
        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Top 1W Volume Spurt</span>
            <div class="mt-2 text-3xl font-extrabold text-amber-600 dark:text-amber-400 font-mono">
                {{ $topGainer->week1_change ? number_format($topGainer->week1_change, 1) . 'x' : '-' }}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate">{{ $topGainer->stock->symbol ?? 'N/A' }}</div>
        </div>

        <!-- Persistent 3/3 -->
        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Persistent (3/3)</span>
            <div class="mt-2 text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">{{ $persistentCount }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Highest confidence (★★★)</div>
        </div>

        <!-- Repeating 2/3 -->
        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Repeating (2/3)</span>
            <div class="mt-2 text-3xl font-extrabold text-indigo-600 dark:text-indigo-400 font-mono">{{ $repeatingCount }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Multi-session (★★)</div>
        </div>

        <!-- Turnover -->
        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Traded Turnover</span>
            <div class="mt-2 text-3xl font-extrabold text-purple-600 dark:text-purple-400 font-mono">
                ₹{{ number_format($totalTurnoverLakhs / 100, 1) }} <span class="text-sm font-normal text-slate-500 dark:text-slate-400">Cr</span>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Combined volume turnover</div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 glass-panel p-4 rounded-2xl">
        <!-- Confidence Pills -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-600 dark:text-slate-400 mr-1">Confidence:</span>
            <a href="{{ route('volume-gainers.index', ['date' => $selectedDate]) }}" 
               class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ !request('confidence') ? 'bg-amber-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                All ({{ $totalGainers }})
            </a>
            <a href="{{ route('volume-gainers.index', ['date' => $selectedDate, 'confidence' => '★★★']) }}" 
               class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ request('confidence') === '★★★' ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                ★★★ Persistent ({{ $persistentCount }})
            </a>
            <a href="{{ route('volume-gainers.index', ['date' => $selectedDate, 'confidence' => '★★']) }}" 
               class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ request('confidence') === '★★' ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                ★★ Repeating ({{ $repeatingCount }})
            </a>
            <a href="{{ route('volume-gainers.index', ['date' => $selectedDate, 'min_multiplier' => 5]) }}" 
               class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ request('min_multiplier') == 5 ? 'bg-purple-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                🔥 Spurt ≥ 5x
            </a>
            <a href="{{ route('volume-gainers.index', ['date' => $selectedDate, 'min_multiplier' => 10]) }}" 
               class="px-3 py-1 rounded-lg text-xs font-semibold transition {{ request('min_multiplier') == 10 ? 'bg-rose-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                🚀 Spurt ≥ 10x
            </a>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('volume-gainers.index') }}" class="flex items-center space-x-2">
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search symbol or company..."
                   class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-1.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-amber-500 focus:outline-none shadow-sm">
            <button type="submit" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-xl shadow-sm transition">
                Search
            </button>
            @if(request('search') || request('confidence') || request('min_multiplier'))
                <a href="{{ route('volume-gainers.index', ['date' => $selectedDate]) }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-xl transition">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Volume Gainers Data Table -->
    <div class="glass-panel rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/90 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3.5 px-4">Symbol & Company</th>
                        <th class="py-3.5 px-4 text-center">1W Volume Spurt</th>
                        <th class="py-3.5 px-4 text-center">2W Volume Spurt</th>
                        <th class="py-3.5 px-4 text-right">Volume</th>
                        <th class="py-3.5 px-4 text-right">LTP (₹)</th>
                        <th class="py-3.5 px-4 text-right">% Chg</th>
                        <th class="py-3.5 px-4 text-right">Turnover</th>
                        <th class="py-3.5 px-4 text-center">Confidence</th>
                        <th class="py-3.5 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($gainers as $g)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors group">
                            <!-- Symbol -->
                            <td class="py-3 px-4">
                                <span class="font-extrabold text-slate-900 dark:text-white font-mono text-base group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors block">
                                    {{ $g->stock->symbol }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-xs block">
                                    {{ $g->stock->company_name ?? 'Equity' }}
                                </span>
                            </td>

                            <!-- 1W Spurt Multiplier -->
                            <td class="py-3 px-4 text-center">
                                @if($g->week1_change >= 20)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-black bg-gradient-to-r from-rose-500/15 to-amber-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30">
                                        🚀 {{ number_format($g->week1_change, 2) }}x
                                    </span>
                                @elseif($g->week1_change >= 5)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30">
                                        ⚡ {{ number_format($g->week1_change, 2) }}x
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ number_format($g->week1_change, 2) }}x
                                    </span>
                                @endif
                            </td>

                            <!-- 2W Spurt Multiplier -->
                            <td class="py-3 px-4 text-center font-mono text-xs font-medium text-slate-600 dark:text-slate-300">
                                {{ $g->week2_change ? number_format($g->week2_change, 2) . 'x' : '-' }}
                            </td>

                            <!-- Volume -->
                            <td class="py-3 px-4 text-right font-mono text-slate-800 dark:text-slate-300 font-semibold">
                                {{ number_format($g->volume) }}
                            </td>

                            <!-- LTP -->
                            <td class="py-3 px-4 text-right font-mono font-semibold text-slate-900 dark:text-white">
                                ₹{{ number_format($g->ltp, 2) }}
                            </td>

                            <!-- % Change -->
                            <td class="py-3 px-4 text-right font-mono font-bold">
                                @if($g->p_change >= 0)
                                    <span class="text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded text-xs">
                                        +{{ number_format($g->p_change, 2) }}%
                                    </span>
                                @else
                                    <span class="text-rose-700 dark:text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded text-xs">
                                        {{ number_format($g->p_change, 2) }}%
                                    </span>
                                @endif
                            </td>

                            <!-- Turnover -->
                            <td class="py-3 px-4 text-right font-mono text-slate-600 dark:text-slate-300 text-xs">
                                ₹{{ number_format($g->turnover_lakhs, 2) }} L
                            </td>

                            <!-- Confidence Stars -->
                            <td class="py-3 px-4 text-center">
                                @if($g->confidence === '★★★')
                                    <span class="text-emerald-600 dark:text-emerald-400 text-sm font-black tracking-widest" title="Persistent: Seen in 3/3 sessions">★★★</span>
                                @elseif($g->confidence === '★★')
                                    <span class="text-indigo-600 dark:text-indigo-400 text-sm font-black tracking-widest" title="Repeating: Seen in 2/3 sessions">★★</span>
                                @else
                                    <span class="text-amber-600 dark:text-amber-400 text-sm font-bold tracking-widest" title="Spurt: Seen in 1 session">★</span>
                                @endif
                            </td>

                            <!-- Quick Action -->
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('risk-calculator.index', ['symbol' => $g->stock->symbol, 'entry' => $g->ltp, 'sl' => round($g->ltp * 0.95, 2)]) }}"
                                   title="Load in Risk Calculator"
                                   class="inline-flex items-center p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 dark:bg-amber-500/10 dark:hover:bg-amber-500/30 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                No Volume Gainer records match your criteria for this date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($gainers->hasPages())
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800">
                {{ $gainers->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
