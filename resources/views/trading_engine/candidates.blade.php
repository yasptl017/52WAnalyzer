@extends('layouts.app')

@section('title', 'Trade Candidates - 52WAnalyzer')

@section('content')
<div class="space-y-6">

    <!-- Navigation Subtabs -->
    <div class="flex items-center space-x-3 border-b border-slate-200 dark:border-slate-800 pb-3">
        <a href="{{ route('trading-engine.candidates') }}" class="px-4 py-2 rounded-xl text-sm font-bold bg-violet-50 dark:bg-violet-600/20 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-500/30 flex items-center gap-2">
            <span>🎯</span> Trade Candidates
            <span class="px-2 py-0.5 text-xs rounded-full bg-violet-100 dark:bg-violet-500/20 text-violet-800 dark:text-violet-200 font-bold">{{ $totalCandidates }}</span>
        </a>
        <a href="{{ route('trading-engine.avoid') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 transition flex items-center gap-2">
            <span>🚫</span> Avoid List
        </a>
        <a href="{{ route('trading-engine.patterns') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 transition flex items-center gap-2">
            <span>📈</span> Pattern Scanner
        </a>
    </div>

    <!-- Header Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">High Conviction Candidates</span>
            <div class="mt-2 text-3xl font-black text-slate-900 dark:text-white font-mono">{{ $totalCandidates }}</div>
            <div class="text-[11px] text-violet-600 dark:text-violet-400 mt-1 font-semibold">Passed all strict liquidity & technical filters</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Elite Setups (Score ≥ 80)</span>
            <div class="mt-2 text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ $eliteCount }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Top tier institutional momentum</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Average Risk:Reward</span>
            <div class="mt-2 text-3xl font-black text-amber-600 dark:text-amber-400 font-mono">1 : {{ number_format($avgRr, 1) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Calculated against technical ATR stop loss</div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="glass-panel p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('trading-engine.candidates') }}" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search symbol or company..."
                   class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-1.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-violet-500 focus:outline-none shadow-sm">
            
            <select name="setup_type" class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs text-slate-800 dark:text-white focus:outline-none shadow-sm">
                <option value="ALL">All Setup Types</option>
                <option value="BREAKOUT" {{ request('setup_type') == 'BREAKOUT' ? 'selected' : '' }}>Breakout</option>
                <option value="RETEST" {{ request('setup_type') == 'RETEST' ? 'selected' : '' }}>52W Retest</option>
                <option value="CONSOLIDATION" {{ request('setup_type') == 'CONSOLIDATION' ? 'selected' : '' }}>Consolidation</option>
            </select>

            <select name="min_rr" class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs text-slate-800 dark:text-white focus:outline-none shadow-sm">
                <option value="0">All R:R Ratios</option>
                <option value="2.0" {{ request('min_rr') == '2.0' ? 'selected' : '' }}>R:R ≥ 2.0:1</option>
                <option value="2.5" {{ request('min_rr') == '2.5' ? 'selected' : '' }}>R:R ≥ 2.5:1</option>
                <option value="3.0" {{ request('min_rr') == '3.0' ? 'selected' : '' }}>R:R ≥ 3.0:1</option>
            </select>

            <button type="submit" class="px-4 py-1.5 bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold rounded-xl shadow-sm transition">
                Filter
            </button>
            @if(request('search') || request('setup_type') || request('min_rr'))
                <a href="{{ route('trading-engine.candidates') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-xl transition">
                    Clear
                </a>
            @endif
        </form>

        <div class="text-xs text-slate-600 dark:text-slate-400">
            Showing <span class="font-bold text-slate-900 dark:text-white">{{ $candidates->total() }}</span> curated candidates
        </div>
    </div>

    <!-- Candidates Table -->
    <div class="glass-panel rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/90 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3.5 px-4">Symbol</th>
                        <th class="py-3.5 px-4 text-center">Score</th>
                        <th class="py-3.5 px-4 text-center">Rank Tier</th>
                        <th class="py-3.5 px-4 text-right">Entry (₹)</th>
                        <th class="py-3.5 px-4 text-right">Stop Loss (₹)</th>
                        <th class="py-3.5 px-4 text-right">Target 1 (1R)</th>
                        <th class="py-3.5 px-4 text-right">Target 2 (2R)</th>
                        <th class="py-3.5 px-4 text-center">R:R Ratio</th>
                        <th class="py-3.5 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($candidates as $c)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors group">
                            <!-- Symbol -->
                            <td class="py-3 px-4">
                                <span class="font-extrabold text-slate-900 dark:text-white font-mono text-base group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors block">
                                    {{ $c->stock->symbol }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-xs block">
                                    {{ $c->stock->company_name ?? 'Equity' }}
                                </span>
                            </td>

                            <!-- Score -->
                            <td class="py-3 px-4 text-center">
                                @php
                                    $score = $c->stock->momentumScores->first()?->momentum_score ?? min(95, round($c->rr_ratio * 32));
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black {{ $score >= 80 ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30' : 'bg-violet-500/15 text-violet-700 dark:text-violet-300 border border-violet-500/30' }}">
                                    {{ number_format($score, 0) }} / 100
                                </span>
                            </td>

                            <!-- Rank Tier -->
                            <td class="py-3 px-4 text-center">
                                @if($c->rr_ratio >= 2.5)
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">👑 ELITE</span>
                                @elseif($c->rr_ratio >= 2.0)
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-500/20">⚡ STRONG</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">MODERATE</span>
                                @endif
                            </td>

                            <!-- Entry -->
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                ₹{{ number_format($c->entry_price, 2) }}
                            </td>

                            <!-- Stop Loss -->
                            <td class="py-3 px-4 text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                                ₹{{ number_format($c->stop_loss, 2) }}
                            </td>

                            <!-- Target 1 -->
                            <td class="py-3 px-4 text-right font-mono text-emerald-600 dark:text-emerald-400">
                                ₹{{ number_format($c->target_1r, 2) }}
                            </td>

                            <!-- Target 2 -->
                            <td class="py-3 px-4 text-right font-mono text-emerald-700 dark:text-emerald-300 font-semibold">
                                ₹{{ number_format($c->target_2r, 2) }}
                            </td>

                            <!-- R:R Ratio -->
                            <td class="py-3 px-4 text-center font-mono font-extrabold text-amber-600 dark:text-amber-400">
                                1 : {{ number_format($c->rr_ratio, 1) }}
                            </td>

                            <!-- Action -->
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('risk-calculator.index', ['symbol' => $c->stock->symbol, 'entry' => $c->entry_price, 'sl' => $c->stop_loss, 'target' => $c->target_2r]) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-violet-50 hover:bg-violet-100 dark:bg-violet-600/20 dark:hover:bg-violet-600/40 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-500/30 transition gap-1 shadow-sm">
                                    <span>🧮</span> Calculate Size
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                No candidate setups found matching your active filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($candidates->hasPages())
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800">
                {{ $candidates->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
