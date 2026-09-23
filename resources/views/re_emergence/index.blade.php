@extends('layouts.app')

@section('title', 'Re-Emergence Breakouts (>5, >10, >15 Sessions) — 52WAnalyzer')

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="glass-panel p-6 rounded-3xl bg-gradient-to-r from-blue-50/80 via-white to-white dark:from-blue-950/40 dark:via-slate-900 dark:to-slate-900 border border-blue-200 dark:border-blue-500/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wider mb-2">
                <span>🔄</span> Multi-Session Gap Scanner
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Re-Emergence & Base Breakout Scanner</h1>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-3xl">
                Identifies high-momentum stocks that were previously on the 52-Week High or Volume Gainers list, underwent quiet consolidation for multiple sessions, and have now <strong>re-appeared with fresh momentum</strong>.
            </p>
        </div>

        <!-- CSV Export & Quick Actions -->
        <div class="flex items-center gap-2">
            <a href="{{ route('re-emergence.export', ['tab' => $tab, 'source' => $sourceFilter, 'date' => $selectedDate]) }}" 
               class="inline-flex items-center px-3.5 py-2 text-xs font-bold rounded-xl bg-emerald-600/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 hover:bg-emerald-600/20 transition shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export List (.csv)
            </a>
        </div>
    </div>

    <!-- 3 SEPARATE TAB FACILITIES REQUESTED BY USER -->
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="flex flex-wrap space-x-2 sm:space-x-4 -mb-px">
            <!-- Facility 1: >5 Sessions Re-Emergence -->
            <a href="{{ route('re-emergence.index', ['tab' => '5', 'source' => $sourceFilter, 'date' => $selectedDate]) }}" 
               class="py-3 px-4 font-bold text-xs sm:text-sm border-b-2 transition flex items-center gap-2 {{ $tab === '5' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-indigo-50/30 dark:bg-indigo-950/20 rounded-t-xl' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                <span class="text-base">🕒</span>
                <span>1. &gt;5 Sessions Re-Emergence</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono {{ $tab === '5' ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                    {{ $tabCounts['5'] }}
                </span>
            </a>

            <!-- Facility 2: >10 Sessions Re-Emergence -->
            <a href="{{ route('re-emergence.index', ['tab' => '10', 'source' => $sourceFilter, 'date' => $selectedDate]) }}" 
               class="py-3 px-4 font-bold text-xs sm:text-sm border-b-2 transition flex items-center gap-2 {{ $tab === '10' ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50/30 dark:bg-amber-950/20 rounded-t-xl' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                <span class="text-base">📅</span>
                <span>2. &gt;10 Sessions Re-Emergence</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono {{ $tab === '10' ? 'bg-amber-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                    {{ $tabCounts['10'] }}
                </span>
            </a>

            <!-- Facility 3: >15 Sessions Re-Emergence -->
            <a href="{{ route('re-emergence.index', ['tab' => '15', 'source' => $sourceFilter, 'date' => $selectedDate]) }}" 
               class="py-3 px-4 font-bold text-xs sm:text-sm border-b-2 transition flex items-center gap-2 {{ $tab === '15' ? 'border-rose-500 text-rose-600 dark:text-rose-400 bg-rose-50/30 dark:bg-rose-950/20 rounded-t-xl' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                <span class="text-base">🚀</span>
                <span>3. &gt;15 Sessions Re-Emergence</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono {{ $tab === '15' ? 'bg-rose-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                    {{ $tabCounts['15'] }}
                </span>
            </a>
        </nav>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="glass-card p-4 rounded-2xl">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Re-Emergences</div>
            <div class="mt-1 text-2xl font-black text-slate-900 dark:text-white flex items-baseline gap-2">
                {{ count($allEmergences) }} <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">&gt;{{ $minGap }} Sessions</span>
            </div>
            <div class="text-[10px] text-slate-600 dark:text-slate-400 mt-1">Breakout after consolidation</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">52W High Re-Entries</div>
            <div class="mt-1 text-2xl font-black text-blue-600 dark:text-blue-400">
                {{ $count52w }}
            </div>
            <div class="text-[10px] text-slate-600 dark:text-slate-400 mt-1">New 52WH after gap</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Volume Spurt Revivals</div>
            <div class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400">
                {{ $countVg }}
            </div>
            <div class="text-[10px] text-slate-600 dark:text-slate-400 mt-1">Fresh institutional surge</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Maximum Gap Base</div>
            <div class="mt-1 text-2xl font-black text-rose-600 dark:text-rose-400">
                {{ $maxGap }} <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Sessions</span>
            </div>
            <div class="text-[10px] text-slate-600 dark:text-slate-400 mt-1">Longest quiet base period</div>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="glass-panel p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4">
        
        <!-- Filter Form -->
        <form method="GET" action="{{ route('re-emergence.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <!-- Source Filter (All / 52WH / VG) -->
            <div class="flex items-center space-x-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-1 shadow-sm">
                <button type="submit" name="source" value="ALL" 
                        class="px-3 py-1 text-xs font-bold rounded-lg transition {{ $sourceFilter === 'ALL' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    All Lists
                </button>
                <button type="submit" name="source" value="52WH" 
                        class="px-3 py-1 text-xs font-bold rounded-lg transition {{ $sourceFilter === '52WH' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    📈 52W High Only
                </button>
                <button type="submit" name="source" value="VG" 
                        class="px-3 py-1 text-xs font-bold rounded-lg transition {{ $sourceFilter === 'VG' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                    ⚡ Volume Gainers Only
                </button>
            </div>

            <!-- Date Selector -->
            <div class="flex items-center space-x-2">
                <label class="text-xs font-bold text-slate-600 dark:text-slate-400">Date:</label>
                <select name="date" onchange="this.form.submit()" 
                        class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                    <option value="LATEST" {{ $selectedDate === 'LATEST' ? 'selected' : '' }}>Latest Session ({{ $latestDate ? \Carbon\Carbon::parse($latestDate)->format('d-M-Y') : '-' }})</option>
                    <option value="ALL" {{ $selectedDate === 'ALL' ? 'selected' : '' }}>All Available Dates ({{ count($allDates) }} Sessions)</option>
                    @foreach(array_reverse($allDates) as $d)
                        <option value="{{ $d }}" {{ $selectedDate === $d ? 'selected' : '' }}>{{ \Carbon\Carbon::parse($d)->format('d-M-Y') }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <!-- Live In-Table Search Input -->
        <div class="relative w-full md:w-64">
            <input type="text" id="reemergence-search" placeholder="Search symbol or company..."
                   oninput="filterReemergenceTable(this.value)"
                   class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-1.5 pl-9 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
            <svg class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
    </div>

    <!-- Re-Emergence Stocks Table -->
    <div class="glass-panel rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-xl">
        <div class="overflow-x-auto custom-scrollbar max-h-[750px]">
            <table class="w-full text-left border-collapse text-xs" id="reemergence-table">
                <thead class="sticky top-0 z-20 shadow-md">
                    <tr class="bg-slate-900 text-white uppercase text-[11px] font-bold tracking-wider divide-x divide-slate-800">
                        <th class="py-3 px-3 text-center">#</th>
                        <th class="py-3 px-4">Symbol & Sector</th>
                        <th class="py-3 px-4">Company Name</th>
                        <th class="py-3 px-3 text-center">List Source</th>
                        <th class="py-3 px-3 text-center">Re-Entry Date</th>
                        <th class="py-3 px-3 text-center">Last Seen Date</th>
                        <th class="py-3 px-3 text-center">Gap Duration</th>
                        <th class="py-3 px-3 text-center">Momentum Details</th>
                        <th class="py-3 px-3 text-right">LTP (₹)</th>
                        <th class="py-3 px-3 text-right">Day %</th>
                        <th class="py-3 px-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-sans">
                    @if(count($allEmergences) === 0)
                        <tr>
                            <td colspan="11" class="py-16 text-center text-slate-400 text-sm">
                                <div class="text-3xl mb-2">🔍</div>
                                No stocks found re-emerging after &gt;{{ $minGap }} sessions for the selected filters.
                            </td>
                        </tr>
                    @else
                        @foreach($allEmergences as $index => $row)
                            @php
                                $gap = $row['gap_sessions'];
                                $is52w = $row['source'] === '52WH';
                                $pChg = $row['p_change'];

                                // Determine gap badge color
                                if ($gap >= 15) {
                                    $gapClass = 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 border-rose-200 dark:border-rose-700/60 font-black';
                                    $gapIcon = '🚀';
                                } elseif ($gap >= 10) {
                                    $gapClass = 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border-amber-200 dark:border-amber-700/60 font-bold';
                                    $gapIcon = '💥';
                                } else {
                                    $gapClass = 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700/60 font-semibold';
                                    $gapIcon = '🔥';
                                }
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors emergence-row"
                                data-symbol="{{ $row['symbol'] }}"
                                data-company="{{ $row['company_name'] }}"
                                data-sector="{{ $row['sector'] }}">
                                
                                <td class="py-3 px-3 text-center text-slate-400 font-mono">{{ $index + 1 }}</td>
                                
                                <td class="py-3 px-4">
                                    <div class="font-black text-slate-900 dark:text-white font-mono text-sm tracking-tight">
                                        {{ $row['symbol'] }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-0.5">
                                        {{ $row['sector'] }}
                                    </div>
                                </td>

                                <td class="py-3 px-4 font-medium text-slate-700 dark:text-slate-300 truncate max-w-[200px]" title="{{ $row['company_name'] }}">
                                    {{ $row['company_name'] }}
                                </td>

                                <td class="py-3 px-3 text-center">
                                    @if($is52w)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                                            📈 52W High
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200 dark:border-amber-700/50">
                                            ⚡ Vol Gainer
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3 px-3 text-center font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $row['formatted_date'] }}
                                </td>

                                <td class="py-3 px-3 text-center font-mono text-slate-500 dark:text-slate-400">
                                    {{ $row['formatted_prev_date'] }}
                                </td>

                                <td class="py-3 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs border shadow-sm {{ $gapClass }}">
                                        <span>{{ $gapIcon }}</span>
                                        <span>{{ $gap }} Sessions Gap</span>
                                    </span>
                                </td>

                                <td class="py-3 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ $row['metric_detail'] }}
                                    </span>
                                </td>

                                <td class="py-3 px-3 text-right font-mono font-black text-slate-900 dark:text-white">
                                    ₹{{ number_format($row['ltp'], 2) }}
                                </td>

                                <td class="py-3 px-3 text-right font-mono font-black {{ $pChg >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $pChg >= 0 ? '+' : '' }}{{ number_format($pChg, 2) }}%
                                </td>

                                <td class="py-3 px-3 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <a href="/date-matrix?tab={{ $is52w ? '52wh' : 'vg' }}" 
                                           class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-700/50 hover:bg-indigo-100 transition"
                                           title="Inspect in Date Matrix">
                                            📊 Matrix
                                        </a>
                                        <a href="/risk-calculator?symbol={{ $row['symbol'] }}&price={{ $row['ltp'] }}" 
                                           class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-700/50 hover:bg-emerald-100 transition"
                                           title="Calculate Position Sizing">
                                            🧮 Calc
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function filterReemergenceTable(query) {
        const q = (query || '').trim().toUpperCase();
        const rows = document.querySelectorAll('.emergence-row');
        
        rows.forEach(row => {
            const sym = row.getAttribute('data-symbol') || '';
            const comp = row.getAttribute('data-company') || '';
            const sector = row.getAttribute('data-sector') || '';
            
            if (!q || sym.toUpperCase().includes(q) || comp.toUpperCase().includes(q) || sector.toUpperCase().includes(q)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
@endpush
@endsection
