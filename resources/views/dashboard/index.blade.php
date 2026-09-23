@extends('layouts.app')

@section('title', 'Momentum Dashboard - 52WAnalyzer')

@section('content')
<div class="space-y-6">

    <!-- Top Controls: Date Switcher & Search -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 glass-panel p-4 rounded-2xl">
        <div class="flex items-center space-x-3">
            <span class="text-xs uppercase font-bold tracking-wider text-slate-600 dark:text-slate-400">Trade Date:</span>
            <form method="GET" action="{{ route('dashboard') }}" id="date-form" class="inline-flex items-center">
                <select name="date" onchange="document.getElementById('date-form').submit()" 
                        class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-white text-sm font-semibold rounded-xl px-3.5 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none cursor-pointer shadow-sm">
                    @foreach($availableDates as $d)
                        <option value="{{ $d }}" {{ $d === $selectedDate ? 'selected' : '' }}>
                            📅 {{ date('D, d M Y', strtotime($d)) }} {{ $loop->first ? '(Latest)' : '' }}
                        </option>
                    @endforeach
                </select>
                @if(request('min_streak')) <input type="hidden" name="min_streak" value="{{ request('min_streak') }}"> @endif
                @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
            </form>
        </div>

        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center space-x-2">
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search symbol or company..."
                       class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 pl-9 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl shadow-sm transition">
                Filter
            </button>
            @if(request('search') || request('min_streak') || request('status'))
                <a href="{{ route('dashboard', ['date' => $selectedDate]) }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm rounded-xl transition">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- 5 Daily Summary Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total 52WH -->
        <div class="glass-card p-4 rounded-2xl relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total 52W Highs</span>
                <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white font-mono">{{ $total52whCount }}</span>
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">equities</span>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">Recorded on {{ date('d M', strtotime($selectedDate)) }}</div>
        </div>

        <!-- New Entries -->
        <div class="glass-card p-4 rounded-2xl relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">New Breakouts</span>
                <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">{{ $summary52wh->new_count ?? $new52whCount }}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">New (Day 1)</span>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">Fresh 52W high entries</div>
        </div>

        <!-- Repeated / Continuing -->
        <div class="glass-card p-4 rounded-2xl relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Continuing Streaks</span>
                <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 font-mono">{{ $summary52wh->repeated_count ?? $continuingCount }}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">Streak ≥ 2d</span>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">Maintained high from prior day</div>
        </div>

        <!-- Dropped -->
        <div class="glass-card p-4 rounded-2xl relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dropped</span>
                <span class="p-1.5 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-rose-600 dark:text-rose-400 font-mono">{{ $summary52wh->dropped_count ?? 0 }}</span>
                <span class="text-xs text-slate-500 dark:text-slate-400">fell off list</span>
            </div>
            <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">Failed to refresh 52W high</div>
        </div>

        <!-- Top Streak Performer -->
        <div class="glass-card p-4 rounded-2xl relative overflow-hidden col-span-2 lg:col-span-1 bg-gradient-to-br from-indigo-50/80 to-white dark:from-slate-900 dark:to-indigo-950/40 border-indigo-200 dark:border-indigo-500/30">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider">Streak Leader</span>
                <span class="text-xs">👑</span>
            </div>
            <div class="mt-2">
                <div class="text-xl font-black text-slate-900 dark:text-white truncate font-mono">
                    {{ $summary52wh->top_stock ?? ($topStreaks->first()?->stock->symbol ?? 'N/A') }}
                </div>
                <div class="text-xs text-indigo-700 dark:text-indigo-300 mt-1 font-semibold flex items-center gap-1">
                    <span>Consecutive:</span>
                    <span class="text-amber-600 dark:text-amber-400 font-bold">{{ $topStreaks->first()?->consecutive_days ?? 1 }} Days 🔥</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area: Left Table & Right Highlight Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Left Table: 52-Week High Breakouts & Streaks (3 Columns) -->
        <div class="lg:col-span-3 space-y-4">
            
            <!-- Filter Pills Bar -->
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('dashboard', ['date' => $selectedDate]) }}" 
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ !request('min_streak') && !request('status') ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        All ({{ $total52whCount }})
                    </a>
                    <a href="{{ route('dashboard', ['date' => $selectedDate, 'status' => 'new']) }}" 
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('status') === 'new' ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        ✨ New Breakouts ({{ $new52whCount }})
                    </a>
                    <a href="{{ route('dashboard', ['date' => $selectedDate, 'status' => 'continuing']) }}" 
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('status') === 'continuing' ? 'bg-amber-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        🔥 Continuing Streaks ({{ $continuingCount }})
                    </a>
                    <a href="{{ route('dashboard', ['date' => $selectedDate, 'min_streak' => 3]) }}" 
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('min_streak') == 3 ? 'bg-purple-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        Streaks ≥ 3d
                    </a>
                    <a href="{{ route('dashboard', ['date' => $selectedDate, 'min_streak' => 5]) }}" 
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('min_streak') == 5 ? 'bg-indigo-700 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        Elite ≥ 5d
                    </a>
                </div>

                <!-- Sort dropdown -->
                <div class="text-xs text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                    <span>Sort by:</span>
                    <form method="GET" action="{{ route('dashboard') }}" id="sort-form">
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                        @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
                        @if(request('min_streak')) <input type="hidden" name="min_streak" value="{{ request('min_streak') }}"> @endif
                        <select name="sort" onchange="document.getElementById('sort-form').submit()"
                                class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1 text-xs text-slate-800 dark:text-white focus:outline-none shadow-sm">
                            <option value="streak_desc" {{ request('sort') == 'streak_desc' ? 'selected' : '' }}>Highest Streak</option>
                            <option value="p_change_desc" {{ request('sort') == 'p_change_desc' ? 'selected' : '' }}>Top % Gainers</option>
                            <option value="p_change_asc" {{ request('sort') == 'p_change_asc' ? 'selected' : '' }}>Top % Losers</option>
                            <option value="ltp_desc" {{ request('sort') == 'ltp_desc' ? 'selected' : '' }}>Highest Price</option>
                            <option value="symbol_asc" {{ request('sort') == 'symbol_asc' ? 'selected' : '' }}>Symbol (A-Z)</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Table Container -->
            <div class="glass-panel rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/90 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <th class="py-3.5 px-4">Symbol & Company</th>
                                <th class="py-3.5 px-4 text-right">LTP (₹)</th>
                                <th class="py-3.5 px-4 text-right">% Chg</th>
                                <th class="py-3.5 px-4 text-right">New 52WH</th>
                                <th class="py-3.5 px-4 text-center">Consecutive Streak</th>
                                <th class="py-3.5 px-4 text-center">30-Day Activity</th>
                                <th class="py-3.5 px-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                            @forelse($records as $row)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors group">
                                    <!-- Symbol & Company -->
                                    <td class="py-3 px-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-extrabold text-slate-900 dark:text-white font-mono text-base group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                                {{ $row->stock->symbol }}
                                            </span>
                                            @if($row->is_new)
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">NEW</span>
                                            @else
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">DAY {{ $row->consecutive_days }}</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-xs mt-0.5">
                                            {{ $row->stock->company_name ?? 'Equity' }}
                                        </div>
                                    </td>

                                    <!-- LTP -->
                                    <td class="py-3 px-4 text-right font-mono font-semibold text-slate-800 dark:text-slate-200">
                                        ₹{{ number_format($row->ltp, 2) }}
                                    </td>

                                    <!-- % Change -->
                                    <td class="py-3 px-4 text-right font-mono font-bold">
                                        @if($row->p_change >= 0)
                                            <span class="text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded">
                                                +{{ number_format($row->p_change, 2) }}%
                                            </span>
                                        @else
                                            <span class="text-rose-700 dark:text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded">
                                                {{ number_format($row->p_change, 2) }}%
                                            </span>
                                        @endif
                                    </td>

                                    <!-- High Price -->
                                    <td class="py-3 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                        ₹{{ number_format($row->high_price, 2) }}
                                    </td>

                                    <!-- Streak Badge -->
                                    <td class="py-3 px-4 text-center">
                                        @if($row->consecutive_days >= 5)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold bg-gradient-to-r from-purple-500/15 to-indigo-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30">
                                                🔥 {{ $row->consecutive_days }} Days
                                            </span>
                                        @elseif($row->consecutive_days >= 2)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                                                ⚡ {{ $row->consecutive_days }} Days
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800">
                                                1 Day
                                            </span>
                                        @endif
                                    </td>

                                    <!-- 30-Day Activity Bar -->
                                    <td class="py-3 px-4">
                                        <div class="flex items-center justify-center space-x-2">
                                            <div class="w-16 bg-slate-200 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                                                <div class="h-2 rounded-full {{ $row->total_appearances_30d >= 15 ? 'bg-indigo-500' : ($row->total_appearances_30d >= 7 ? 'bg-emerald-500' : 'bg-slate-400 dark:bg-slate-600') }}"
                                                     style="width: {{ min(100, round(($row->total_appearances_30d / 30) * 100)) }}%"></div>
                                            </div>
                                            <span class="text-xs font-mono text-slate-600 dark:text-slate-400 font-semibold">{{ $row->total_appearances_30d }}/30</span>
                                        </div>
                                    </td>

                                    <!-- Action: Quick Calculator -->
                                    <td class="py-3 px-4 text-center">
                                        <a href="{{ route('risk-calculator.index', ['symbol' => $row->stock->symbol, 'entry' => $row->ltp, 'sl' => round($row->ltp * 0.96, 2)]) }}"
                                           title="Load in Risk Calculator"
                                           class="inline-flex items-center p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-600/10 dark:hover:bg-indigo-600/30 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                        No 52-Week High records match the active filter for this date.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($records->hasPages())
                    <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800">
                        {{ $records->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Highlight Sidebar (1 Column) -->
        <div class="space-y-6">
            
            <!-- Top Consecutive Momentum Streaks Card -->
            <div class="glass-panel p-4 rounded-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                        <span>🔥</span> Top Streaks Today
                    </h3>
                    <span class="text-[11px] text-slate-500">Days</span>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800/60 mt-2">
                    @foreach($topStreaks as $s)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-900 dark:text-white font-mono text-sm block">{{ $s->stock->symbol }}</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">₹{{ number_format($s->ltp, 2) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-extrabold text-amber-600 dark:text-amber-400 font-mono">{{ $s->consecutive_days }}d</span>
                                <span class="text-[10px] text-slate-500 block">{{ $s->total_appearances_30d }}/30 in 30d</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Top Gainers Card -->
            <div class="glass-panel p-4 rounded-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                        <span>🚀</span> Top % Gainers
                    </h3>
                    <span class="text-[11px] text-slate-500">% Chg</span>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-800/60 mt-2">
                    @foreach($topGainers as $g)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-900 dark:text-white font-mono text-sm block">{{ $g->stock->symbol }}</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">₹{{ number_format($g->ltp, 2) }}</span>
                            </div>
                            <div class="text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                                +{{ number_format($g->p_change, 2) }}%
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Volume Gainers Quick Banner -->
            <div class="glass-card p-4 rounded-2xl border-amber-200 dark:border-amber-500/30 bg-gradient-to-br from-amber-50/80 to-white dark:from-slate-900 dark:to-amber-950/20">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Volume Gainers</span>
                    <span class="text-xs">⚡</span>
                </div>
                <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white font-mono">
                    {{ $totalVgCount }}
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                    Market-wide volume breakouts recorded today with 1W & 2W surge multipliers.
                </p>
                <div class="mt-3">
                    <a href="{{ route('volume-gainers.index', ['date' => $selectedDate]) }}" 
                       class="inline-flex items-center text-xs font-semibold text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300">
                        View Volume Gainers Hub &rarr;
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
