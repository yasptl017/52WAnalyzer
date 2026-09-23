@extends('layouts.app')

@section('title', 'Avoid List & Diagnostics - 52WAnalyzer')

@section('content')
<div class="space-y-6">

    <!-- Navigation Subtabs -->
    <div class="flex items-center space-x-3 border-b border-slate-200 dark:border-slate-800 pb-3">
        <a href="{{ route('trading-engine.candidates') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 transition flex items-center gap-2">
            <span>🎯</span> Trade Candidates
        </a>
        <a href="{{ route('trading-engine.avoid') }}" class="px-4 py-2 rounded-xl text-sm font-bold bg-rose-50 dark:bg-rose-600/20 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-500/30 flex items-center gap-2">
            <span>🚫</span> Avoid List
            <span class="px-2 py-0.5 text-xs rounded-full bg-rose-100 dark:bg-rose-500/20 text-rose-800 dark:text-rose-200 font-bold">{{ $totalRejected }}</span>
        </a>
        <a href="{{ route('trading-engine.patterns') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 transition flex items-center gap-2">
            <span>📈</span> Pattern Scanner
        </a>
    </div>

    <!-- Diagnostic Banner -->
    <div class="glass-panel p-5 rounded-2xl border border-rose-200 dark:border-rose-500/30 bg-gradient-to-r from-rose-50/80 via-white to-white dark:from-rose-950/20 dark:to-slate-900 flex items-start space-x-4">
        <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 flex-shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Algorithmic Capital Preservation Diagnostics</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                This diagnostic replaces the confusing Avoid List sheet from Excel. The engine actively rejects stocks that exhibit high risk characteristics (extended prices, thin liquidity, unfavorable risk-to-reward ratios, or extreme ATR volatility) to protect your trading capital.
            </p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="glass-panel p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('trading-engine.avoid') }}" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search symbol or company..."
                   class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-1.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-rose-500 focus:outline-none shadow-sm">
            
            <select name="reason" class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs text-slate-800 dark:text-white focus:outline-none shadow-sm">
                <option value="ALL">All Rejection Reasons</option>
                <option value="extended" {{ request('reason') == 'extended' ? 'selected' : '' }}>Extended / Climax</option>
                <option value="volume" {{ request('reason') == 'volume' ? 'selected' : '' }}>Low Liquidity / Volume</option>
                <option value="reward" {{ request('reason') == 'reward' ? 'selected' : '' }}>Poor R:R Ratio (&lt; 2.0)</option>
                <option value="volatility" {{ request('reason') == 'volatility' ? 'selected' : '' }}>Extreme Volatility</option>
            </select>

            <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow-sm transition">
                Filter
            </button>
            @if(request('search') || request('reason'))
                <a href="{{ route('trading-engine.avoid') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-xl transition">
                    Clear
                </a>
            @endif
        </form>

        <div class="text-xs text-slate-600 dark:text-slate-400">
            Total Rejections: <span class="font-bold text-rose-600 dark:text-rose-400">{{ $totalRejected }}</span>
        </div>
    </div>

    <!-- Table -->
    <div class="glass-panel rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/90 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3.5 px-4">Symbol & Company</th>
                        <th class="py-3.5 px-4 text-right">Tested Entry</th>
                        <th class="py-3.5 px-4 text-right">Stop Loss</th>
                        <th class="py-3.5 px-4 text-center">Score</th>
                        <th class="py-3.5 px-4">Diagnostic Rejection Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($rejected as $r)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4">
                                <span class="font-extrabold text-slate-900 dark:text-white font-mono text-base block">
                                    {{ $r->stock->symbol }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-xs block">
                                    {{ $r->stock->company_name ?? 'Equity' }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-right font-mono font-semibold text-slate-800 dark:text-slate-300">
                                ₹{{ number_format($r->entry_price, 2) }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-rose-600 dark:text-rose-400">
                                ₹{{ number_format($r->stop_loss, 2) }}
                            </td>

                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-mono">
                                    {{ number_format($r->setup_score, 0) }}/100
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-500/10 text-rose-700 dark:text-rose-300 border border-rose-500/20">
                                    🚫 {{ $r->reject_reason ?: 'Failed Risk/Reward threshold (< 2.0)' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                No rejected stocks matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rejected->hasPages())
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800">
                {{ $rejected->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
