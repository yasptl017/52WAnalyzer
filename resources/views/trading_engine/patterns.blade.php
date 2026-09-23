@extends('layouts.app')

@section('title', 'Technical Pattern Scanner - 52WAnalyzer')

@section('content')
<div class="space-y-6">

    <!-- Navigation Subtabs -->
    <div class="flex items-center space-x-3 border-b border-slate-200 dark:border-slate-800 pb-3">
        <a href="{{ route('trading-engine.candidates') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 transition flex items-center gap-2">
            <span>🎯</span> Trade Candidates
        </a>
        <a href="{{ route('trading-engine.avoid') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 transition flex items-center gap-2">
            <span>🚫</span> Avoid List
        </a>
        <a href="{{ route('trading-engine.patterns') }}" class="px-4 py-2 rounded-xl text-sm font-bold bg-indigo-50 dark:bg-indigo-600/20 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30 flex items-center gap-2">
            <span>📈</span> Pattern Scanner
            <span class="px-2 py-0.5 text-xs rounded-full bg-indigo-100 dark:bg-indigo-500/20 text-indigo-800 dark:text-indigo-200 font-bold">{{ $totalPatterns }}</span>
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="glass-panel p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('trading-engine.patterns') }}" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search symbol..."
                   class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-1.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
            
            <select name="pattern" class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs text-slate-800 dark:text-white focus:outline-none max-w-xs shadow-sm">
                <option value="ALL">All 23 Technical Patterns</option>
                @foreach($availablePatterns as $pat)
                    <option value="{{ $pat }}" {{ request('pattern') == $pat ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', strtolower($pat))) }}
                    </option>
                @endforeach
            </select>

            <select name="confidence" class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs text-slate-800 dark:text-white focus:outline-none shadow-sm">
                <option value="ALL">All Confidence Levels</option>
                <option value="HIGH" {{ request('confidence') == 'HIGH' ? 'selected' : '' }}>High Confidence</option>
                <option value="MEDIUM" {{ request('confidence') == 'MEDIUM' ? 'selected' : '' }}>Medium Confidence</option>
                <option value="LOW" {{ request('confidence') == 'LOW' ? 'selected' : '' }}>Low Confidence</option>
            </select>

            <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-sm transition">
                Filter
            </button>
            @if(request('search') || request('pattern') || request('confidence'))
                <a href="{{ route('trading-engine.patterns') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-xl transition">
                    Clear
                </a>
            @endif
        </form>

        <div class="text-xs text-slate-600 dark:text-slate-400">
            Total Matches: <span class="font-bold text-slate-900 dark:text-white">{{ $patterns->total() }}</span>
        </div>
    </div>

    <!-- Table -->
    <div class="glass-panel rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/90 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3.5 px-4">Symbol</th>
                        <th class="py-3.5 px-4">Pattern Name</th>
                        <th class="py-3.5 px-4 text-center">Confidence</th>
                        <th class="py-3.5 px-4 text-center">Detected Date</th>
                        <th class="py-3.5 px-4">Technical Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($patterns as $p)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4 font-mono font-extrabold text-slate-900 dark:text-white text-base">
                                {{ $p->stock->symbol }}
                            </td>

                            <td class="py-3 px-4">
                                <span class="font-bold text-indigo-700 dark:text-indigo-300 block">
                                    {{ ucwords(str_replace('_', ' ', strtolower($p->pattern_name))) }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-center">
                                @if(strtoupper($p->confidence) === 'HIGH')
                                    <span class="px-2 py-0.5 rounded text-xs font-extrabold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">HIGH</span>
                                @elseif(strtoupper($p->confidence) === 'MEDIUM')
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">MEDIUM</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">LOW</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-center font-mono text-xs text-slate-600 dark:text-slate-400">
                                {{ date('d M Y', strtotime($p->match_date)) }}
                            </td>

                            <td class="py-3 px-4 text-xs font-mono text-slate-700 dark:text-slate-300 max-w-md truncate">
                                @if(is_array($p->details))
                                    {{ json_encode($p->details) }}
                                @else
                                    {{ $p->details ?: 'Technical breakout confirmed' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                No technical pattern matches found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($patterns->hasPages())
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800">
                {{ $patterns->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
