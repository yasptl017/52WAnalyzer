@extends('layouts.app')

@section('title', 'Date-Wise Stock Matrix Analysis — 52W & Volume Gainers')

@section('content')
<div class="space-y-6">

    <!-- Top Header & Banner -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-3">
                <span class="p-2 rounded-xl bg-gradient-to-tr from-indigo-600 via-blue-600 to-emerald-400 text-white shadow-lg shadow-indigo-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                    </svg>
                </span>
                Date-Wise Stock Matrix Analysis
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Multi-session calendar matrix tracking stock appearances across trading dates with algorithmic momentum highlights.
            </p>
        </div>

        <!-- Controls: Days selector, Sort & Export CSV -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Days Window Form -->
            <form method="GET" action="{{ route('date-matrix.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                
                <div class="flex items-center space-x-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-1 shadow-sm">
                    <label class="text-[11px] font-bold text-slate-500 dark:text-slate-400 px-2">Window:</label>
                    @php
                        $userIsPro = auth()->check() && auth()->user()->isPro();
                        $windowOptions = [3, 5, 7, 10, 15, 20, 30, 45];
                    @endphp
                    @foreach($windowOptions as $d)
                        @php
                            $isLocked = !$userIsPro && $d > 7;
                        @endphp
                        <button type="submit" name="days" value="{{ $d }}" 
                                class="px-2 py-1 text-xs font-semibold rounded-lg transition {{ $days == $d ? 'bg-indigo-600 text-white shadow-sm' : ($isLocked ? 'text-slate-400 dark:text-slate-600 opacity-60 hover:opacity-100' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800') }}"
                                title="{{ $isLocked ? "{$d}D requires Pro Tier (Starter is 7D max)" : "Show {$d} trading sessions" }}">
                            {{ $d }}D{{ $isLocked ? '🔒' : '' }}
                        </button>
                    @endforeach
                </div>

                <!-- Sort Dropdown -->
                <select name="sort" onchange="this.form.submit()" 
                        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                    <option value="streak_surge" {{ $sort === 'streak_surge' ? 'selected' : '' }}>Sort: Momentum / Streak</option>
                    <option value="p_change" {{ $sort === 'p_change' ? 'selected' : '' }}>Sort: % Change (High to Low)</option>
                    <option value="symbol" {{ $sort === 'symbol' ? 'selected' : '' }}>Sort: Alphabetical (A-Z)</option>
                    <option value="turnover" {{ $sort === 'turnover' ? 'selected' : '' }}>Sort: Turnover / Volume</option>
                </select>
            </form>

            <!-- Export CSV -->
            <a href="{{ route('date-matrix.export', ['tab' => $tab, 'days' => $days, 'sort' => $sort]) }}" 
               class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-xl bg-emerald-600/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 hover:bg-emerald-600/20 transition shadow-sm"
               title="Download Matrix Spreadsheet matching the Excel grid">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="glass-card p-4 rounded-2xl">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Trading Days Window</div>
            <div class="mt-1 text-2xl font-black text-slate-900 dark:text-white flex items-baseline gap-2">
                {{ count($availableDates) }} <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">Sessions</span>
            </div>
            <div class="text-[10px] text-slate-600 dark:text-slate-400 mt-1">
                From {{ count($availableDates) > 0 ? \Carbon\Carbon::parse(end($availableDates))->format('d M') : '-' }} to {{ count($availableDates) > 0 ? \Carbon\Carbon::parse($availableDates[0])->format('d M Y') : '-' }}
            </div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Unique Stocks</div>
            <div class="mt-1 text-2xl font-black text-indigo-600 dark:text-indigo-400">
                {{ $uniqueCount }}
            </div>
            <div class="text-[10px] text-slate-600 dark:text-slate-400 mt-1">Distinct symbols identified</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Appearances</div>
            <div class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">
                {{ $totalAppearances }}
            </div>
            <div class="text-[10px] text-slate-600 dark:text-slate-400 mt-1">Cumulative date entries</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Daily Average</div>
            <div class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400">
                {{ $avgStocksPerDay }}
            </div>
            <div class="text-[10px] text-slate-600 dark:text-slate-400 mt-1">Stocks per trading session</div>
        </div>
    </div>

    <!-- Frequent Repeaters Leaderboard Strip -->
    @if(count($topRepeatedStocks) > 0)
    <div class="glass-panel p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-wrap items-center gap-2">
        <div class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mr-2">
            <span class="text-amber-500">🏆</span> Top Persistent Runners:
        </div>
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach($topRepeatedStocks as $sym => $cnt)
                <button type="button" onclick="filterSymbol('{{ $sym }}')" 
                        class="symbol-chip px-2.5 py-1 rounded-lg text-xs font-bold font-mono transition bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-500 text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 shadow-sm flex items-center gap-1.5"
                        data-symbol="{{ $sym }}">
                    <span>{{ $sym }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-sans">{{ $cnt }}d</span>
                </button>
            @endforeach
            <button type="button" id="btn-clear-filter" onclick="clearFilter()" class="hidden px-2 py-1 text-xs text-rose-500 hover:text-rose-700 dark:hover:text-rose-300 font-bold ml-1">
                &times; Clear Filter
            </button>
        </div>
    </div>
    @endif

    <!-- 3 Facility Sub-Tabs Navigation -->
    <div class="border-b border-slate-200 dark:border-slate-800">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <nav class="flex space-x-2 -mb-px">
                <!-- Facility 1: 52-Week High Matrix -->
                <a href="{{ route('date-matrix.index', ['tab' => '52wh', 'days' => $days, 'sort' => $sort]) }}" 
                   class="py-3 px-4 font-bold text-xs sm:text-sm border-b-2 transition flex items-center gap-2 {{ $tab === '52wh' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                    <span class="text-base">📈</span>
                    <span>1. 52-Week High Matrix</span>
                    @if($tab === '52wh')
                        <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 font-semibold">{{ $totalAppearances }}</span>
                    @endif
                </a>

                <!-- Facility 2: Volume Gainers Matrix -->
                <a href="{{ route('date-matrix.index', ['tab' => 'vg', 'days' => $days, 'sort' => $sort]) }}" 
                   class="py-3 px-4 font-bold text-xs sm:text-sm border-b-2 transition flex items-center gap-2 {{ $tab === 'vg' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                    <span class="text-base">⚡</span>
                    <span>2. Volume Gainers Matrix</span>
                    @if($tab === 'vg')
                        <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 font-semibold">{{ $totalAppearances }}</span>
                    @endif
                </a>

                <!-- Facility 3: Combined Confluence Matrix -->
                <a href="{{ route('date-matrix.index', ['tab' => 'combined', 'days' => $days, 'sort' => $sort]) }}" 
                   class="py-3 px-4 font-bold text-xs sm:text-sm border-b-2 transition flex items-center gap-2 {{ $tab === 'combined' ? 'border-violet-500 text-violet-600 dark:text-violet-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                    <span class="text-base">🚀</span>
                    <span>3. Combined 52WH + VG Confluence</span>
                    @if($tab === 'combined')
                        <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-300 font-semibold">{{ $totalAppearances }}</span>
                    @endif
                </a>
            </nav>

            <!-- Instant In-Page Search Box -->
            <div class="relative w-full sm:w-64 pb-2 sm:pb-0">
                <input type="text" id="matrix-search" placeholder="Type stock symbol..." 
                       class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-1.5 pl-9 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                <svg class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Color Scheme Legend Bar (Click any category to open Pop-Up Stock List) -->
    <div class="glass-panel p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs shadow-sm">
        <div class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
            <span class="text-base">🎨</span>
            <span>Click to View Pop-Up Lists:</span>
        </div>

        @if($tab === '52wh')
            <div class="flex flex-wrap items-center gap-2">
                @php
                    $cntDual = $categorizedStocks['dual']['unique_count'] ?? 0;
                    $cntSuper = $categorizedStocks['super_streak']['unique_count'] ?? 0;
                    $cntActive = $categorizedStocks['active_streak']['unique_count'] ?? 0;
                    $cntNew = $categorizedStocks['new_breakout']['unique_count'] ?? 0;
                @endphp
                <button type="button" onclick="openCategoryModal('dual', '⚡ 52W + Volume Gainer Confluence', 'violet')"
                        class="legend-btn group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-violet-100/80 hover:bg-violet-200 text-violet-900 dark:bg-violet-900/40 dark:hover:bg-violet-900/60 dark:text-violet-200 border border-violet-200 dark:border-violet-700/60 hover:scale-105 shadow-sm">
                    <span>⚡ 52W + Volume Gainer</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-violet-600 text-white font-mono">{{ $cntDual }}</span>
                </button>

                <button type="button" onclick="openCategoryModal('super_streak', '🔥 Super Streak (5+ Days)', 'amber')"
                        class="legend-btn group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-amber-100/80 hover:bg-amber-200 text-amber-900 dark:bg-amber-900/40 dark:hover:bg-amber-900/60 dark:text-amber-200 border border-amber-200 dark:border-amber-700/60 hover:scale-105 shadow-sm">
                    <span>🔥 Super Streak (5+ Days)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-600 text-white font-mono">{{ $cntSuper }}</span>
                </button>

                <button type="button" onclick="openCategoryModal('active_streak', '★ Active Streak (2-4 Days)', 'emerald')"
                        class="legend-btn group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-emerald-100/80 hover:bg-emerald-200 text-emerald-900 dark:bg-emerald-900/40 dark:hover:bg-emerald-900/60 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-700/60 hover:scale-105 shadow-sm">
                    <span>★ Active Streak (2-4 Days)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-600 text-white font-mono">{{ $cntActive }}</span>
                </button>

                <button type="button" onclick="openCategoryModal('new_breakout', '🚀 Day 1 Breakout', 'sky')"
                        class="legend-btn group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-sky-100/80 hover:bg-sky-200 text-sky-900 dark:bg-sky-900/40 dark:hover:bg-sky-900/60 dark:text-sky-200 border border-sky-200 dark:border-sky-700/60 hover:scale-105 shadow-sm">
                    <span>🚀 Day 1 Breakout</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-sky-600 text-white font-mono">{{ $cntNew }}</span>
                </button>
            </div>
        @elseif($tab === 'vg')
            <div class="flex flex-wrap items-center gap-2">
                @php
                    $cntDual = $categorizedStocks['dual']['unique_count'] ?? 0;
                    $cntMega = $categorizedStocks['mega_surge']['unique_count'] ?? 0;
                    $cntHigh = $categorizedStocks['high_surge']['unique_count'] ?? 0;
                    $cntMod = $categorizedStocks['mod_surge']['unique_count'] ?? 0;
                @endphp
                <button type="button" onclick="openCategoryModal('dual', '⚡ VG + 52-Week High Confluence', 'violet')"
                        class="legend-btn group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-violet-100/80 hover:bg-violet-200 text-violet-900 dark:bg-violet-900/40 dark:hover:bg-violet-900/60 dark:text-violet-200 border border-violet-200 dark:border-violet-700/60 hover:scale-105 shadow-sm">
                    <span>⚡ VG + 52-Week High</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-violet-600 text-white font-mono">{{ $cntDual }}</span>
                </button>

                <button type="button" onclick="openCategoryModal('mega_surge', '💥 10x+ Mega Spurt', 'rose')"
                        class="legend-btn group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-rose-100/80 hover:bg-rose-200 text-rose-900 dark:bg-rose-900/40 dark:hover:bg-rose-900/60 dark:text-rose-200 border border-rose-200 dark:border-rose-700/60 hover:scale-105 shadow-sm">
                    <span>💥 10x+ Mega Spurt</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-600 text-white font-mono">{{ $cntMega }}</span>
                </button>

                <button type="button" onclick="openCategoryModal('high_surge', '🔥 5x-10x High Surge', 'amber')"
                        class="legend-btn group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-amber-100/80 hover:bg-amber-200 text-amber-900 dark:bg-amber-900/40 dark:hover:bg-amber-900/60 dark:text-amber-200 border border-amber-200 dark:border-amber-700/60 hover:scale-105 shadow-sm">
                    <span>🔥 5x-10x High Surge</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-600 text-white font-mono">{{ $cntHigh }}</span>
                </button>

                <button type="button" onclick="openCategoryModal('mod_surge', '⚡ 2x-5x Volume Surge', 'emerald')"
                        class="legend-btn group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-emerald-100/80 hover:bg-emerald-200 text-emerald-900 dark:bg-emerald-900/40 dark:hover:bg-emerald-900/60 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-700/60 hover:scale-105 shadow-sm">
                    <span>⚡ 2x-5x Volume Surge</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-600 text-white font-mono">{{ $cntMod }}</span>
                </button>
            </div>
        @else
            <div class="flex flex-wrap items-center gap-2">
                @php
                    $cntElite = $categorizedStocks['elite_dual']['unique_count'] ?? 0;
                    $cntFresh = $categorizedStocks['fresh_dual']['unique_count'] ?? 0;
                    $cntStd = $categorizedStocks['standard_dual']['unique_count'] ?? 0;
                @endphp
                <button type="button" onclick="openCategoryModal('elite_dual', '👑 Elite Confluence (Streak 3d+ & Surge 3x+)', 'amber')"
                        class="legend-btn group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-amber-100/80 hover:bg-amber-200 text-amber-900 dark:bg-amber-900/40 dark:hover:bg-amber-900/60 dark:text-amber-200 border border-amber-200 dark:border-amber-700/60 hover:scale-105 shadow-sm">
                    <span>👑 Elite Confluence (Streak 3d+ & Surge 3x+)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-600 text-white font-mono">{{ $cntElite }}</span>
                </button>

                <button type="button" onclick="openCategoryModal('fresh_dual', '🚀 Day 1 Breakout + High Volume', 'sky')"
                        class="legend-btn group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-sky-100/80 hover:bg-sky-200 text-sky-900 dark:bg-sky-900/40 dark:hover:bg-sky-900/60 dark:text-sky-200 border border-sky-200 dark:border-sky-700/60 hover:scale-105 shadow-sm">
                    <span>🚀 Day 1 Breakout + High Volume</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-sky-600 text-white font-mono">{{ $cntFresh }}</span>
                </button>

                <button type="button" onclick="openCategoryModal('standard_dual', '⚡ Dual Overlap', 'violet')"
                        class="legend-btn group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-violet-100/80 hover:bg-violet-200 text-violet-900 dark:bg-violet-900/40 dark:hover:bg-violet-900/60 dark:text-violet-200 border border-violet-200 dark:border-violet-700/60 hover:scale-105 shadow-sm">
                    <span>⚡ Dual Overlap</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-violet-600 text-white font-mono">{{ $cntStd }}</span>
                </button>
            </div>
        @endif

        <div class="text-[11px] text-slate-500 dark:text-slate-400 italic">
            * Click any badge to pop up matching stock list
        </div>
    </div>

    <!-- View & Display Layout Controls Bar (Screen Fit vs Horizontal Scroll Mode) -->
    <div class="glass-panel p-3 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs shadow-sm bg-slate-50/50 dark:bg-slate-900/50">
        
        <!-- Left: Display Mode & Columns per Screen Selector -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Mode Toggle: Single Screen Fit vs Scroll Mode -->
            <div class="inline-flex rounded-xl p-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm" role="group">
                <button type="button" id="btn-mode-fit" onclick="setMatrixViewMode('fit')"
                        class="px-3 py-1.5 rounded-lg font-bold text-xs transition flex items-center gap-1.5 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white"
                        title="Fit all date columns onto a single screen with zero horizontal scroll">
                    <span>🖥️</span>
                    <span>Fit to Screen</span>
                </button>
                <button type="button" id="btn-mode-scroll" onclick="setMatrixViewMode('scroll')"
                        class="px-3 py-1.5 rounded-lg font-bold text-xs transition flex items-center gap-1.5 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white"
                        title="Use spacious fixed-width columns with smooth horizontal scrolling">
                    <span>📜</span>
                    <span>Horizontal Scroll</span>
                </button>
            </div>

            <!-- Columns on Screen / Column Width Presets -->
            <div class="flex items-center gap-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-2.5 py-1 shadow-sm">
                <label for="matrix-col-preset" class="text-[11px] font-bold text-slate-500 dark:text-slate-400 whitespace-nowrap">Columns on Screen:</label>
                <select id="matrix-col-preset" onchange="applyColumnPreset(this.value)" 
                        class="bg-transparent border-0 text-xs font-bold text-slate-800 dark:text-slate-200 focus:ring-0 focus:outline-none cursor-pointer">
                    <option value="fit">🖥️ Auto-Fit (All {{ count($availableDates) }} Dates)</option>
                    <option value="3">3 Dates per screen</option>
                    <option value="5">5 Dates per screen</option>
                    <option value="7" selected>7 Dates per screen</option>
                    <option value="10">10 Dates per screen</option>
                    <option value="12">12 Dates per screen</option>
                    <option value="compact">Compact Width (145px/col)</option>
                    <option value="standard">Standard Width (190px/col)</option>
                    <option value="spacious">Spacious Width (245px/col)</option>
                </select>
            </div>

            <!-- Density Toggle (Compact vs Comfortable) -->
            <button type="button" id="btn-density-toggle" onclick="toggleDensity()"
                    class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:border-indigo-400 font-semibold text-xs shadow-sm flex items-center gap-1.5"
                    title="Toggle card height and padding density">
                <span id="density-icon">📏</span>
                <span id="density-text">Compact View</span>
            </button>
        </div>

        <!-- Right: Horizontal Scroll Navigator Controls (Active in Scroll Mode) -->
        <div id="matrix-scroll-nav" class="flex items-center gap-1.5">
            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 hidden sm:inline mr-1">Scroll Dates:</span>
            
            <button type="button" onclick="scrollMatrixTo('start')"
                    class="p-1.5 px-2.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-400 text-slate-700 dark:text-slate-300 font-bold transition shadow-sm hover:scale-105"
                    title="Jump to Oldest Available Date (Leftmost)">
                ⏪ Oldest
            </button>
            
            <button type="button" onclick="scrollMatrixBy(-1)"
                    class="p-1.5 px-2.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-400 text-slate-700 dark:text-slate-300 font-bold transition shadow-sm hover:scale-105"
                    title="Scroll Left (Previous Date Column)">
                ◀ Prev
            </button>

            <!-- Active Visible Columns Tracker -->
            <div id="scroll-status-badge" class="px-2.5 py-1 rounded-lg bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-500/20 font-mono font-bold text-[11px]">
                {{ count($availableDates) }} Dates
            </div>

            <button type="button" onclick="scrollMatrixBy(1)"
                    class="p-1.5 px-2.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-400 text-slate-700 dark:text-slate-300 font-bold transition shadow-sm hover:scale-105"
                    title="Scroll Right (Next Date Column)">
                Next ▶
            </button>
            
            <button type="button" onclick="scrollMatrixTo('end')"
                    class="p-1.5 px-2.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-400 text-slate-700 dark:text-slate-300 font-bold transition shadow-sm hover:scale-105"
                    title="Jump to Latest Trading Date (Rightmost / Today)">
                Latest ⏩
            </button>
        </div>
    </div>

    <!-- Calendar Column Matrix Table Container with Interactive Screen-Fit & Horizontal Scroll -->
    <div id="matrix-container-panel" class="glass-panel rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-xl transition-all">
        <div id="matrix-scroll-wrapper" class="overflow-x-auto custom-scrollbar max-h-[760px] relative scroll-smooth">
            <table id="matrix-table" class="w-full text-left border-collapse transition-all">
                <!-- Table Headers: Trading Dates in Header with Blue Theme -->
                <thead class="sticky top-0 z-20 shadow-md">
                    <tr id="matrix-header-row" class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white text-xs font-bold divide-x divide-slate-700/80">
                        @foreach($availableDates as $dateIndex => $dateStr)
                            @php
                                $dObj = \Carbon\Carbon::parse($dateStr);
                                $colCount = count($matrixData[$dateStr] ?? []);
                            @endphp
                            <th class="matrix-th py-2.5 px-2.5 text-center tracking-wider bg-[#102a4e] text-white border-b-2 border-indigo-400 transition-all" data-date-idx="{{ $dateIndex }}">
                                <div class="font-extrabold text-xs sm:text-sm text-cyan-300 matrix-th-date">
                                    {{ $dObj->format('d-M-Y') }}
                                </div>
                                <div class="text-[10px] text-slate-300 font-normal flex items-center justify-center gap-1 mt-0.5 matrix-th-sub">
                                    <span>{{ $dObj->format('l') }}</span>
                                    <span>•</span>
                                    <span class="font-bold text-amber-300">{{ $colCount }} Stocks</span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <!-- Table Rows: Stock symbols under each column -->
                <tbody id="matrix-tbody" class="divide-y divide-slate-200 dark:divide-slate-800/60 bg-white dark:bg-slate-950/40 font-mono text-xs">
                    @if($maxRows === 0)
                        <tr>
                            <td colspan="{{ max(1, count($availableDates)) }}" class="py-12 text-center text-slate-400 text-sm">
                                No stocks found matching your criteria. Try adjusting your window or filters.
                            </td>
                        </tr>
                    @else
                        @for($rowIndex = 0; $rowIndex < $maxRows; $rowIndex++)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-900/40 transition-colors divide-x divide-slate-100 dark:divide-slate-800/40">
                                @foreach($availableDates as $dateStr)
                                    @php
                                        $item = $matrixData[$dateStr][$rowIndex] ?? null;
                                    @endphp
                                    <td class="p-1.5 align-top text-center matrix-cell transition-all" data-col="{{ $dateStr }}">
                                        @if($item)
                                            @php
                                                $sym = $item['symbol'];
                                                $cat = $item['category'];
                                                $pChg = $item['p_change'];
                                                $ltp = $item['ltp'];
                                                $badgeText = $item['badge_text'];

                                                // Determine CSS classes for styling
                                                $bgClass = 'bg-slate-50 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200';
                                                if ($cat === 'dual' || $cat === 'standard_dual') {
                                                    $bgClass = 'bg-violet-50 dark:bg-violet-950/40 border-violet-200 dark:border-violet-700/60 text-violet-900 dark:text-violet-200';
                                                } elseif ($cat === 'super_streak' || $cat === 'elite_dual') {
                                                    $bgClass = 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-700/60 text-amber-900 dark:text-amber-200';
                                                } elseif ($cat === 'active_streak' || $cat === 'mod_surge') {
                                                    $bgClass = 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-700/60 text-emerald-900 dark:text-emerald-200';
                                                } elseif ($cat === 'new_breakout' || $cat === 'fresh_dual') {
                                                    $bgClass = 'bg-sky-50 dark:bg-sky-950/40 border-sky-200 dark:border-sky-700/60 text-sky-900 dark:text-sky-200';
                                                } elseif ($cat === 'mega_surge') {
                                                    $bgClass = 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-700/60 text-rose-900 dark:text-rose-200';
                                                } elseif ($cat === 'high_surge') {
                                                    $bgClass = 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-700/60 text-amber-900 dark:text-amber-200';
                                                }
                                            @endphp

                                            <!-- Interactive Stock Card in Cell -->
                                            <div class="stock-item group relative rounded-xl p-2 border transition-all duration-150 cursor-pointer shadow-sm hover:shadow-md {{ $bgClass }}"
                                                 data-symbol="{{ $sym }}"
                                                 onmouseenter="highlightSymbol('{{ $sym }}')"
                                                 onmouseleave="unhighlightSymbol()"
                                                 onclick="openStockTimelineModal('{{ $sym }}')">
                                                
                                                <!-- Top Row: Stock Symbol & Confluence Icon -->
                                                <div class="flex items-center justify-between gap-1">
                                                    <span class="stock-item-symbol font-black text-xs tracking-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                                                        {{ $sym }}
                                                    </span>
                                                    @if($item['is_dual'])
                                                        <span class="text-[10px] text-violet-600 dark:text-violet-400 font-bold" title="Volume Gainer + 52W High Confluence">⚡</span>
                                                    @endif
                                                </div>

                                                <!-- Middle Row: Badge Label (Streak / Spurt) -->
                                                <div class="stock-item-badge mt-1 text-[10px] font-sans font-bold truncate text-left opacity-90">
                                                    {{ $badgeText }}
                                                </div>

                                                <!-- Bottom Row: LTP & % Change -->
                                                <div class="stock-item-footer mt-1 flex items-center justify-between text-[10px] font-sans pt-1 border-t border-black/5 dark:border-white/5">
                                                    <span class="font-mono text-slate-500 dark:text-slate-400">₹{{ number_format($ltp, 1) }}</span>
                                                    <span class="font-bold {{ $pChg >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                        {{ $pChg >= 0 ? '+' : '' }}{{ number_format($pChg, 1) }}%
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="h-10 text-slate-300 dark:text-slate-800 text-center flex items-center justify-center text-xs">
                                                -
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    @endif
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Stock Lifetime Journey & Appearance Timeline Modal -->
<div id="stock-timeline-modal" class="fixed inset-0 z-50 hidden bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
    <div class="glass-panel w-full max-w-4xl max-h-[92vh] rounded-3xl border border-slate-200 dark:border-slate-700/80 shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Modal Top Header -->
        <div class="p-5 border-b border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70 flex items-center justify-between gap-4">
            <div class="flex items-center space-x-3.5">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-emerald-400 text-white flex items-center justify-center font-black text-lg shadow-lg shadow-indigo-500/25">
                    <span id="stl-icon">STK</span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 id="stl-symbol" class="text-xl font-black text-slate-900 dark:text-white tracking-tight"></h3>
                        <span id="stl-series" class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">EQ</span>
                        <span id="stl-sector" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30">Sector</span>
                    </div>
                    <p id="stl-company" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium"></p>
                </div>
            </div>

            <button onclick="closeStockTimelineModal()" 
                    class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-900 dark:hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Scrollable Content -->
        <div class="p-6 overflow-y-auto space-y-6 custom-scrollbar flex-1 bg-white/40 dark:bg-slate-950/40">
            
            <!-- Executive Metrics Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white/80 dark:bg-slate-900/80 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Total Appearances</div>
                    <div id="stl-total-count" class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-0.5">—</div>
                    <div id="stl-sub-appearances" class="text-[10px] text-slate-500 mt-0.5">52WH & VG</div>
                </div>

                <div class="bg-white/80 dark:bg-slate-900/80 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Cumulative Gain</div>
                    <div id="stl-cum-gain" class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">—</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Since 1st Breakout</div>
                </div>

                <div class="bg-white/80 dark:bg-slate-900/80 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Max Streak / Spurt</div>
                    <div id="stl-max-streak" class="text-xl font-black text-amber-600 dark:text-amber-400 mt-0.5">—</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Consecutive Days</div>
                </div>

                <div class="bg-white/80 dark:bg-slate-900/80 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm text-center">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Latest LTP</div>
                    <div id="stl-latest-ltp" class="text-xl font-black text-slate-900 dark:text-white mt-0.5">—</div>
                    <div id="stl-date-span" class="text-[10px] text-slate-500 mt-0.5">—</div>
                </div>
            </div>

            <!-- Complete Lifetime Appearance Journey Timeline Table -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                        <span>🗓️</span> Complete Lifetime Appearance Timeline (All DB Dates)
                    </h4>
                    <span class="text-[11px] text-slate-500 italic">Includes dates outside current matrix window</span>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-850 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold">
                            <tr>
                                <th class="px-4 py-3">Trading Date</th>
                                <th class="px-3 py-3 text-right">LTP (₹)</th>
                                <th class="px-3 py-3 text-right">Day % Change</th>
                                <th class="px-3 py-3">Classification & Trigger</th>
                                <th class="px-3 py-3 text-center">Streak</th>
                                <th class="px-3 py-3 text-center">Volume Surge</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="stl-timeline-rows" class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900 font-medium">
                            <!-- Populated dynamically via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Bottom Footer Actions -->
        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a id="stl-tv-link" href="#" target="_blank"
                   class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs transition flex items-center gap-1.5">
                    <span>📈</span> TradingView
                </a>
                <a id="stl-risk-link" href="#"
                   class="px-3.5 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 hover:bg-emerald-100 font-bold text-xs transition flex items-center gap-1.5">
                    <span>🧮</span> Calculate Position Size
                </a>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" onclick="filterSymbol(currentTimelineSymbol); closeStockTimelineModal();"
                        class="px-3.5 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30 hover:bg-indigo-100 font-bold text-xs transition flex items-center gap-1.5">
                    <span>🔍</span> Focus in Matrix
                </button>
                <button type="button" onclick="closeStockTimelineModal()"
                        class="px-4 py-1.5 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 text-slate-800 dark:text-slate-200 font-semibold text-xs transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Floating Cross-Column Occurrence Tracker Bar -->
<div id="floating-occurrence-bar" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-40 glass-panel px-4 py-2 rounded-2xl border-2 border-indigo-500/40 shadow-2xl bg-white/95 dark:bg-slate-900/95 flex items-center gap-3 animate-in fade-in slide-in-from-bottom-2 duration-150">
    <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-ping"></span>
        <span id="float-sym" class="font-black text-xs text-slate-900 dark:text-white"></span>
        <span id="float-count" class="text-xs text-slate-600 dark:text-slate-400"></span>
    </div>
    <div class="flex items-center gap-1.5">
        <button type="button" onclick="jumpToNextOccurrence()"
                class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center gap-1 shadow-sm">
            <span>⬇ Jump to Next</span>
        </button>
        <button type="button" onclick="openStockTimelineModal(activeHighlightSymbol)"
                class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 text-xs font-bold border border-indigo-200 dark:border-indigo-500/30 transition flex items-center gap-1">
            <span>🗓️ Full Journey</span>
        </button>
    </div>
</div>

<!-- Dedicated Category Pop-Up List Modal (Triggered by clicking any Legend Badge) -->
<div id="category-list-modal" class="fixed inset-0 z-50 hidden bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
    <div class="glass-panel w-full max-w-4xl max-h-[90vh] rounded-3xl border border-slate-200 dark:border-slate-700/80 shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Modal Top Header -->
        <div class="p-5 border-b border-slate-200 dark:border-slate-800 bg-white/60 dark:bg-slate-900/60 flex items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div id="cat-modal-icon-container" class="w-10 h-10 rounded-2xl flex items-center justify-center text-lg font-black shadow-md">
                    <span id="cat-modal-icon">⚡</span>
                </div>
                <div>
                    <h3 id="cat-modal-title" class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                        Category Stock List
                    </h3>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span id="cat-modal-count-badge" class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                            0 Stocks Found
                        </span>
                        <span id="cat-modal-window-badge" class="text-xs text-slate-500 dark:text-slate-400">
                            Across {{ count($availableDates) }} Trading Sessions
                        </span>
                    </div>
                </div>
            </div>

            <button onclick="closeCategoryModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="Close Modal (Esc)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Toolbar: Search & CSV Export -->
        <div class="p-3.5 bg-slate-50/70 dark:bg-slate-900/40 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="relative w-full sm:w-72">
                <input type="text" id="cat-modal-search" placeholder="Search by symbol or company..."
                       oninput="filterCategoryModalTable(this.value)"
                       class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-1.5 pl-9 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                <svg class="w-3.5 h-3.5 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="flex items-center space-x-2 w-full sm:w-auto justify-end">
                <button type="button" onclick="exportCategoryListCsv()" 
                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-xl bg-emerald-600/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 hover:bg-emerald-600/20 transition shadow-sm">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Category List (.csv)
                </button>
            </div>
        </div>

        <!-- Modal Table Body (Scrollable) -->
        <div class="flex-1 overflow-y-auto custom-scrollbar max-h-[60vh] p-2 sm:p-4">
            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-slate-850 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200 dark:border-slate-800">
                            <th class="py-2.5 px-3">#</th>
                            <th class="py-2.5 px-3">Symbol & Sector</th>
                            <th class="py-2.5 px-3">Company Name</th>
                            <th class="py-2.5 px-3">Dates Seen in Matrix</th>
                            <th class="py-2.5 px-3">Classification</th>
                            <th class="py-2.5 px-3 text-right">LTP (₹)</th>
                            <th class="py-2.5 px-3 text-right">Day %</th>
                            <th class="py-2.5 px-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cat-modal-tbody" class="divide-y divide-slate-100 dark:divide-slate-800 font-sans">
                        <!-- Dynamic rows inserted by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div id="cat-modal-empty" class="hidden py-12 text-center text-slate-400 text-xs">
                No stocks match your search query.
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/40 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <span>💡 Tip: Click <strong>"Track in Matrix"</strong> to highlight all occurrences of any stock across calendar columns.</span>
            <button onclick="closeCategoryModal()" class="px-4 py-1.5 rounded-xl font-bold bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                Close
            </button>
        </div>
    </div>
</div>

@push('scripts')
<style>
    /* Cross-column highlight glow effect */
    .highlight-active {
        box-shadow: 0 0 0 2px #6366f1, 0 4px 14px 0 rgba(99, 102, 241, 0.45) !important;
        transform: scale(1.04);
        z-index: 10;
        background-color: #f5f3ff !important;
        border-color: #6366f1 !important;
    }
    html.dark .highlight-active {
        box-shadow: 0 0 0 2px #818cf8, 0 4px 14px 0 rgba(129, 140, 248, 0.45) !important;
        background-color: #1e1b4b !important;
        border-color: #818cf8 !important;
    }
    .dimmed {
        opacity: 0.28 !important;
        filter: grayscale(60%);
    }
    .legend-btn:active {
        transform: scale(0.97);
    }
</style>

<script>
    window.categoryData = @json($categorizedStocks);
    let activeHighlightSymbol = null;
    let currentModalSymbol = '';
    let currentCategoryKey = '';
    let currentCategoryTitle = '';
    let currentCategoryStockList = [];

    // Open Category Pop-Up List Modal
    function openCategoryModal(catKey, catTitle, colorTheme) {
        currentCategoryKey = catKey;
        currentCategoryTitle = catTitle;
        
        const catObj = window.categoryData[catKey] || { stocks: [], total_appearances: 0, unique_count: 0 };
        currentCategoryStockList = catObj.stocks || [];

        // Set titles and badge counts
        document.getElementById('cat-modal-title').innerText = catTitle;
        document.getElementById('cat-modal-count-badge').innerText = `${catObj.unique_count || currentCategoryStockList.length} Unique Stocks (${catObj.total_appearances || 0} Entries)`;
        
        // Theme color styling for icon container
        const iconContainer = document.getElementById('cat-modal-icon-container');
        const iconElem = document.getElementById('cat-modal-icon');
        
        if (colorTheme === 'violet') {
            iconContainer.className = 'w-10 h-10 rounded-2xl flex items-center justify-center text-lg font-black shadow-md bg-violet-600 text-white shadow-violet-600/30';
            iconElem.innerText = '⚡';
        } else if (colorTheme === 'amber') {
            iconContainer.className = 'w-10 h-10 rounded-2xl flex items-center justify-center text-lg font-black shadow-md bg-amber-500 text-white shadow-amber-500/30';
            iconElem.innerText = catKey.includes('elite') ? '👑' : '🔥';
        } else if (colorTheme === 'rose') {
            iconContainer.className = 'w-10 h-10 rounded-2xl flex items-center justify-center text-lg font-black shadow-md bg-rose-600 text-white shadow-rose-600/30';
            iconElem.innerText = '💥';
        } else if (colorTheme === 'sky') {
            iconContainer.className = 'w-10 h-10 rounded-2xl flex items-center justify-center text-lg font-black shadow-md bg-sky-500 text-white shadow-sky-500/30';
            iconElem.innerText = '🚀';
        } else {
            iconContainer.className = 'w-10 h-10 rounded-2xl flex items-center justify-center text-lg font-black shadow-md bg-emerald-600 text-white shadow-emerald-600/30';
            iconElem.innerText = '★';
        }

        // Reset search input
        const searchInput = document.getElementById('cat-modal-search');
        if (searchInput) searchInput.value = '';

        // Render Table Rows
        renderCategoryModalRows(currentCategoryStockList);

        // Show Modal
        document.getElementById('category-list-modal').classList.remove('hidden');
    }

    function closeCategoryModal() {
        document.getElementById('category-list-modal').classList.add('hidden');
    }

    function renderCategoryModalRows(stockList) {
        const tbody = document.getElementById('cat-modal-tbody');
        const emptyMsg = document.getElementById('cat-modal-empty');
        tbody.innerHTML = '';

        if (!stockList || stockList.length === 0) {
            emptyMsg.classList.remove('hidden');
            return;
        }
        emptyMsg.classList.add('hidden');

        stockList.forEach((stk, index) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors';
            
            const numP = parseFloat(stk.p_change || 0);
            const pClass = numP >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400';
            const pSign = numP >= 0 ? '+' : '';

            // Generate date badges
            const dateBadges = (stk.dates || []).map(d => {
                return `<span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono font-bold">${d}</span>`;
            }).join(' ');

            tr.innerHTML = `
                <td class="py-2.5 px-3 text-slate-400 font-mono">${index + 1}</td>
                <td class="py-2.5 px-3">
                    <div class="font-black text-slate-900 dark:text-white font-mono text-xs flex items-center gap-1.5">
                        <span>${stk.symbol}</span>
                    </div>
                    <div class="text-[10px] text-slate-400 uppercase tracking-wider">${stk.sector || 'General'}</div>
                </td>
                <td class="py-2.5 px-3 font-medium text-slate-700 dark:text-slate-300 truncate max-w-[180px]" title="${stk.company_name}">
                    ${stk.company_name}
                </td>
                <td class="py-2.5 px-3">
                    <div class="flex flex-wrap items-center gap-1 max-w-[220px]">
                        ${dateBadges}
                    </div>
                </td>
                <td class="py-2.5 px-3">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700/50">
                        ${stk.badge_text || 'Active'}
                    </span>
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                    ₹${parseFloat(stk.ltp).toFixed(2)}
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold ${pClass}">
                    ${pSign}${numP.toFixed(2)}%
                </td>
                <td class="py-2.5 px-3 text-center">
                    <div class="flex items-center justify-center space-x-1.5">
                        <button type="button" onclick="trackFromCategoryModal('${stk.symbol}')" 
                                class="px-2 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 dark:bg-indigo-900/50 hover:bg-indigo-100 text-indigo-600 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700/50 transition"
                                title="Highlight across calendar columns">
                            🔍 Track
                        </button>
                        <a href="/risk-calculator?symbol=${stk.symbol}&price=${stk.ltp}" 
                           class="px-2 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 dark:bg-emerald-900/50 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700/50 transition"
                           title="Calculate position size in Risk Calculator">
                            🧮 Calc
                        </a>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function filterCategoryModalTable(query) {
        const q = (query || '').trim().toUpperCase();
        if (!q) {
            renderCategoryModalRows(currentCategoryStockList);
            return;
        }

        const filtered = currentCategoryStockList.filter(s => {
            return (s.symbol && s.symbol.toUpperCase().includes(q)) || 
                   (s.company_name && s.company_name.toUpperCase().includes(q)) ||
                   (s.sector && s.sector.toUpperCase().includes(q));
        });

        renderCategoryModalRows(filtered);
    }

    function trackFromCategoryModal(symbol) {
        closeCategoryModal();
        filterSymbol(symbol);
        showToast(`Tracking ${symbol} across all calendar columns!`, 'success');
    }

    function exportCategoryListCsv() {
        if (!currentCategoryStockList || currentCategoryStockList.length === 0) {
            showToast('No stocks available to export', 'error');
            return;
        }

        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Symbol,Company Name,Sector,LTP,Day Pct Change,Classification,Dates Appeared\r\n";

        currentCategoryStockList.forEach(stk => {
            const datesStr = `"${(stk.dates || []).join(', ')}"`;
            const compName = `"${(stk.company_name || '').replace(/"/g, '""')}"`;
            const sector = `"${(stk.sector || '').replace(/"/g, '""')}"`;
            const badge = `"${(stk.badge_text || '').replace(/"/g, '""')}"`;
            
            csvContent += `${stk.symbol},${compName},${sector},${stk.ltp},${stk.p_change},${badge},${datesStr}\r\n`;
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `52WAnalyzer_${currentCategoryKey}_stocks_${new Date().toISOString().slice(0,10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showToast('Exported category stock list as CSV!', 'success');
    }

    let currentTimelineSymbol = null;
    let currentOccurrenceIndex = 0;
    let floatingBarTimeout = null;

    // Cross-column hover synchronization with floating occurrence counter
    function highlightSymbol(symbol) {
        if (!symbol) return;
        activeHighlightSymbol = symbol;
        clearTimeout(floatingBarTimeout);
        
        let matchCount = 0;
        const allCards = document.querySelectorAll('.stock-item');
        allCards.forEach(card => {
            if (card.getAttribute('data-symbol') === symbol) {
                card.classList.add('highlight-active');
                card.classList.remove('dimmed');
                matchCount++;
            } else {
                card.classList.remove('highlight-active');
                card.classList.add('dimmed');
            }
        });

        if (matchCount > 0) {
            const floatBar = document.getElementById('floating-occurrence-bar');
            if (floatBar) {
                document.getElementById('float-sym').innerText = symbol;
                document.getElementById('float-count').innerText = `${matchCount} visible in current view`;
                floatBar.classList.remove('hidden');
            }
        }
    }

    function unhighlightSymbol() {
        floatingBarTimeout = setTimeout(() => {
            activeHighlightSymbol = null;
            const allCards = document.querySelectorAll('.stock-item');
            allCards.forEach(card => {
                card.classList.remove('highlight-active', 'dimmed');
            });
            document.getElementById('floating-occurrence-bar')?.classList.add('hidden');
        }, 150);
    }

    // Auto-scroll matrix directly to next occurrence of the hovered stock
    function jumpToNextOccurrence() {
        if (!activeHighlightSymbol) return;
        const matchingCards = Array.from(document.querySelectorAll(`.stock-item[data-symbol="${activeHighlightSymbol}"]`));
        if (matchingCards.length === 0) return;

        currentOccurrenceIndex = (currentOccurrenceIndex + 1) % matchingCards.length;
        const targetCard = matchingCards[currentOccurrenceIndex];

        targetCard.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });

        // Pulse animation
        targetCard.classList.add('ring-4', 'ring-indigo-500');
        setTimeout(() => targetCard.classList.remove('ring-4', 'ring-indigo-500'), 1000);
    }

    // In-page search input
    document.getElementById('matrix-search')?.addEventListener('input', function(e) {
        const query = e.target.value.trim().toUpperCase();
        filterSymbol(query);
    });

    function filterSymbol(symbol) {
        const searchInput = document.getElementById('matrix-search');
        if (searchInput && symbol) searchInput.value = symbol;

        const clearBtn = document.getElementById('btn-clear-filter');
        if (symbol) {
            clearBtn?.classList.remove('hidden');
        } else {
            clearBtn?.classList.add('hidden');
        }

        const allCards = document.querySelectorAll('.stock-item');
        if (!symbol) {
            allCards.forEach(c => {
                c.classList.remove('highlight-active', 'dimmed');
                c.parentElement.style.opacity = '1';
            });
            return;
        }

        allCards.forEach(card => {
            const sym = card.getAttribute('data-symbol');
            if (sym && sym.toUpperCase().includes(symbol.toUpperCase())) {
                card.classList.add('highlight-active');
                card.classList.remove('dimmed');
                card.parentElement.style.opacity = '1';
            } else {
                card.classList.remove('highlight-active');
                card.classList.add('dimmed');
                card.parentElement.style.opacity = '0.35';
            }
        });
    }

    function clearFilter() {
        const searchInput = document.getElementById('matrix-search');
        if (searchInput) searchInput.value = '';
        document.getElementById('btn-clear-filter')?.classList.add('hidden');
        filterSymbol('');
    }

    // Open Complete Stock Lifetime Journey & Timeline Modal
    async function openStockTimelineModal(symbol) {
        if (!symbol) return;
        currentTimelineSymbol = symbol;

        // Set initial skeleton / loading state
        document.getElementById('stl-icon').innerText = symbol.substring(0, 3);
        document.getElementById('stl-symbol').innerText = symbol;
        document.getElementById('stl-company').innerText = 'Loading appearance journey...';
        document.getElementById('stl-series').innerText = 'EQ';
        document.getElementById('stl-sector').innerText = '...';
        document.getElementById('stl-total-count').innerText = '...';
        document.getElementById('stl-cum-gain').innerText = '...';
        document.getElementById('stl-max-streak').innerText = '...';
        document.getElementById('stl-latest-ltp').innerText = '...';
        document.getElementById('stl-date-span').innerText = 'Querying database...';

        document.getElementById('stl-timeline-rows').innerHTML = `
            <tr>
                <td colspan="7" class="py-12 text-center text-slate-500">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-500 border-t-transparent mb-2"></div>
                    <div class="font-bold text-xs">Querying all lifetime appearances for ${symbol}...</div>
                </td>
            </tr>
        `;

        document.getElementById('stl-tv-link').href = `https://www.tradingview.com/chart/?symbol=NSE%3A${symbol}`;
        document.getElementById('stl-risk-link').href = `/risk-calculator?symbol=${symbol}`;

        document.getElementById('stock-timeline-modal').classList.remove('hidden');

        try {
            const response = await fetch(`/api/stock-timeline/${symbol}`);
            const data = await response.json();

            if (!data.success) {
                showToast(data.message || 'Error loading timeline', 'error');
                return;
            }

            const stk = data.stock;
            const timeline = data.timeline || [];

            document.getElementById('stl-symbol').innerText = stk.symbol;
            document.getElementById('stl-company').innerText = stk.company_name;
            document.getElementById('stl-series').innerText = stk.series;
            document.getElementById('stl-sector').innerText = stk.sector;

            document.getElementById('stl-total-count').innerText = stk.total_appearances;
            document.getElementById('stl-sub-appearances').innerText = `${stk.total_52w}x 52WH • ${stk.total_vg}x VG • ${stk.confluence_count}x Confluence`;

            const gainElem = document.getElementById('stl-cum-gain');
            gainElem.innerText = (stk.cumulative_gain_pct >= 0 ? '+' : '') + stk.cumulative_gain_pct + '%';
            gainElem.className = 'text-xl font-black mt-0.5 ' + (stk.cumulative_gain_pct >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400');

            document.getElementById('stl-max-streak').innerText = (stk.max_streak > 1 ? `${stk.max_streak} Days 🔥` : (stk.max_volume_spurt > 0 ? `${stk.max_volume_spurt}x 💥` : '1 Day'));
            document.getElementById('stl-latest-ltp').innerText = '₹' + parseFloat(stk.current_ltp).toFixed(2);
            document.getElementById('stl-date-span').innerText = `From ${stk.first_seen_date} to ${stk.latest_seen_date}`;

            document.getElementById('stl-risk-link').href = `/risk-calculator?symbol=${stk.symbol}&price=${stk.current_ltp}`;

            // Render Timeline Rows
            let rowsHtml = '';
            if (timeline.length === 0) {
                rowsHtml = `<tr><td colspan="7" class="py-8 text-center text-slate-500 font-bold">No historical appearances found in DB.</td></tr>`;
            } else {
                timeline.forEach((item, idx) => {
                    const isDual = item.is_52w && item.is_vg;
                    const pChg = parseFloat(item.p_change);
                    const pChgColor = pChg >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400';
                    const pChgFormatted = (pChg >= 0 ? '+' : '') + pChg.toFixed(2) + '%';

                    rowsHtml += `
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-bold text-slate-900 dark:text-white font-mono">${item.formatted_date}</span>
                                ${idx === 0 ? '<span class="ml-1.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">LATEST</span>' : ''}
                                ${idx === timeline.length - 1 ? '<span class="ml-1.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">FIRST BREAKOUT</span>' : ''}
                            </td>
                            <td class="px-3 py-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                ₹${parseFloat(item.ltp).toFixed(2)}
                            </td>
                            <td class="px-3 py-3 text-right font-mono font-bold ${pChgColor}">
                                ${pChgFormatted}
                            </td>
                            <td class="px-3 py-3">
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold ${isDual ? 'bg-indigo-100 text-indigo-900 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700/60' : (item.is_52w ? 'bg-amber-50 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-700/60' : 'bg-emerald-50 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700/60')}">
                                    ${item.badge}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center font-mono text-slate-700 dark:text-slate-300">
                                ${item.streak > 1 ? `${item.streak}d` : '1d'}
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-bold ${item.volume_surge >= 5 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500'}">
                                ${item.volume_surge ? `${item.volume_surge}x` : '—'}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <form method="POST" action="/trade-journal" class="inline">
                                    <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}">
                                    <input type="hidden" name="symbol" value="${stk.symbol}">
                                    <input type="hidden" name="entry_date" value="${item.date}">
                                    <input type="hidden" name="entry_price" value="${item.ltp}">
                                    <input type="hidden" name="stop_loss" value="${(item.ltp * 0.95).toFixed(2)}">
                                    <input type="hidden" name="target_price" value="${(item.ltp * 1.15).toFixed(2)}">
                                    <input type="hidden" name="quantity" value="${Math.max(1, Math.floor(10000 / item.ltp))}">
                                    <input type="hidden" name="status" value="OPEN">
                                    <input type="hidden" name="notes" value="Timeline Log: ${item.badge} on ${item.date}">
                                    <button type="submit" class="px-2 py-1 text-[10px] font-bold rounded bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-700/50 transition">
                                        ➕ Journal
                                    </button>
                                </form>
                            </td>
                        </tr>
                    `;
                });
            }

            document.getElementById('stl-timeline-rows').innerHTML = rowsHtml;

        } catch (err) {
            console.error(err);
            document.getElementById('stl-timeline-rows').innerHTML = `
                <tr><td colspan="7" class="py-6 text-center text-rose-500 font-bold">Failed to load appearance timeline. Please try again.</td></tr>
            `;
        }
    }

    function closeStockTimelineModal() {
        document.getElementById('stock-timeline-modal').classList.add('hidden');
    }

    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeStockTimelineModal();
            closeCategoryModal();
        }
    });

    /* ==========================================================================
       DATE MATRIX: SCREEN-FIT & HORIZONTAL SCROLL CONTROLLER
       ========================================================================== */
    const totalDatesCount = {{ count($availableDates) }};
    let currentMatrixViewMode = 'fit'; // 'fit' or 'scroll'
    let currentColumnPreset = '7'; // 'fit', '3', '5', '7', '10', '12', 'compact', 'standard', 'spacious'
    let isCompactDensity = false;

    // Apply View Mode: 'fit' (Single Screen Width) or 'scroll' (Horizontal Scrollable)
    function setMatrixViewMode(mode) {
        currentMatrixViewMode = mode;
        localStorage.setItem('date_matrix_view_mode', mode);

        const btnFit = document.getElementById('btn-mode-fit');
        const btnScroll = document.getElementById('btn-mode-scroll');
        const presetSelect = document.getElementById('matrix-col-preset');
        const table = document.getElementById('matrix-table');
        const wrapper = document.getElementById('matrix-scroll-wrapper');
        const scrollNav = document.getElementById('matrix-scroll-nav');

        if (!table || !wrapper) return;

        if (mode === 'fit') {
            // Highlight Fit button
            btnFit.className = 'px-3 py-1.5 rounded-lg font-bold text-xs transition flex items-center gap-1.5 bg-indigo-600 text-white shadow-sm';
            btnScroll.className = 'px-3 py-1.5 rounded-lg font-bold text-xs transition flex items-center gap-1.5 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white';
            
            // Single screen fit: 100% width, fixed equal column distribution
            table.style.minWidth = '100%';
            table.style.width = '100%';
            table.style.tableLayout = 'fixed';
            wrapper.style.overflowX = 'hidden';

            const thElements = table.querySelectorAll('.matrix-th');
            thElements.forEach(th => {
                th.style.width = `${100 / Math.max(1, totalDatesCount)}%`;
                th.style.minWidth = '0px';
            });

            if (presetSelect) presetSelect.value = 'fit';
            if (scrollNav) scrollNav.classList.add('opacity-40', 'pointer-events-none');
            updateScrollStatusBadge('Fit on 1 Screen');
        } else {
            // Highlight Scroll button
            btnScroll.className = 'px-3 py-1.5 rounded-lg font-bold text-xs transition flex items-center gap-1.5 bg-indigo-600 text-white shadow-sm';
            btnFit.className = 'px-3 py-1.5 rounded-lg font-bold text-xs transition flex items-center gap-1.5 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white';
            
            wrapper.style.overflowX = 'auto';
            table.style.tableLayout = 'auto';

            if (scrollNav) scrollNav.classList.remove('opacity-40', 'pointer-events-none');
            
            // Apply column width according to preset
            applyColumnPreset(currentColumnPreset === 'fit' ? '7' : currentColumnPreset, false);
        }
    }

    // Apply specific Column Width preset or columns per view
    function applyColumnPreset(preset, switchMode = true) {
        currentColumnPreset = preset;
        localStorage.setItem('date_matrix_col_preset', preset);

        const table = document.getElementById('matrix-table');
        const wrapper = document.getElementById('matrix-scroll-wrapper');
        const presetSelect = document.getElementById('matrix-col-preset');
        if (presetSelect && presetSelect.value !== preset) presetSelect.value = preset;

        if (preset === 'fit') {
            setMatrixViewMode('fit');
            return;
        }

        if (switchMode && currentMatrixViewMode !== 'scroll') {
            setMatrixViewMode('scroll');
            return;
        }

        let colWidthPx = 185; // default standard
        const containerWidth = wrapper ? wrapper.clientWidth || 1100 : 1100;

        if (preset === '3') {
            colWidthPx = Math.max(260, Math.floor((containerWidth - 40) / 3));
        } else if (preset === '5') {
            colWidthPx = Math.max(200, Math.floor((containerWidth - 40) / 5));
        } else if (preset === '7') {
            colWidthPx = Math.max(160, Math.floor((containerWidth - 40) / 7));
        } else if (preset === '10') {
            colWidthPx = Math.max(130, Math.floor((containerWidth - 40) / 10));
        } else if (preset === '12') {
            colWidthPx = Math.max(115, Math.floor((containerWidth - 40) / 12));
        } else if (preset === 'compact') {
            colWidthPx = 145;
        } else if (preset === 'standard') {
            colWidthPx = 190;
        } else if (preset === 'spacious') {
            colWidthPx = 250;
        }

        const totalTableMinWidth = Math.max(containerWidth, totalDatesCount * colWidthPx);
        if (table) {
            table.style.minWidth = `${totalTableMinWidth}px`;
            table.style.width = `${totalTableMinWidth}px`;
            table.style.tableLayout = 'fixed';

            const thElements = table.querySelectorAll('.matrix-th');
            thElements.forEach(th => {
                th.style.width = `${colWidthPx}px`;
                th.style.minWidth = `${colWidthPx}px`;
            });
        }

        updateScrollTracker();
    }

    // Toggle Card Density (Compact vs Comfortable)
    function toggleDensity() {
        isCompactDensity = !isCompactDensity;
        localStorage.setItem('date_matrix_density', isCompactDensity ? 'compact' : 'standard');
        applyDensityStyles(isCompactDensity);
    }

    function applyDensityStyles(isCompact) {
        const container = document.getElementById('matrix-container-panel');
        const icon = document.getElementById('density-icon');
        const text = document.getElementById('density-text');

        if (isCompact) {
            container?.classList.add('matrix-compact');
            if (icon) icon.innerText = '📐';
            if (text) text.innerText = 'Spacious View';
        } else {
            container?.classList.remove('matrix-compact');
            if (icon) icon.innerText = '📏';
            if (text) text.innerText = 'Compact View';
        }
    }

    // Horizontal Scroll Navigator: Scroll by N columns
    function scrollMatrixBy(direction) {
        const wrapper = document.getElementById('matrix-scroll-wrapper');
        if (!wrapper) return;
        const scrollAmount = wrapper.clientWidth * 0.45 * direction;
        wrapper.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }

    // Jump to extreme start (oldest date) or extreme end (latest date)
    function scrollMatrixTo(pos) {
        const wrapper = document.getElementById('matrix-scroll-wrapper');
        if (!wrapper) return;
        if (pos === 'start') {
            wrapper.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            wrapper.scrollTo({ left: wrapper.scrollWidth, behavior: 'smooth' });
        }
    }

    // Calculate currently visible column range on screen
    function updateScrollTracker() {
        const wrapper = document.getElementById('matrix-scroll-wrapper');
        if (!wrapper || currentMatrixViewMode === 'fit') {
            updateScrollStatusBadge('Fit on 1 Screen');
            return;
        }

        const scrollLeft = wrapper.scrollLeft;
        const viewWidth = wrapper.clientWidth;
        const totalWidth = wrapper.scrollWidth;

        if (totalWidth <= viewWidth + 10) {
            updateScrollStatusBadge(`All ${totalDatesCount} Dates Visible`);
            return;
        }

        const thElements = Array.from(document.querySelectorAll('.matrix-th'));
        if (thElements.length === 0) return;

        const colWidth = thElements[0].offsetWidth || 185;
        const firstVisibleIdx = Math.max(1, Math.floor(scrollLeft / colWidth) + 1);
        const visibleColsCount = Math.ceil(viewWidth / colWidth);
        const lastVisibleIdx = Math.min(totalDatesCount, firstVisibleIdx + visibleColsCount - 1);

        updateScrollStatusBadge(`Cols ${firstVisibleIdx}–${lastVisibleIdx} of ${totalDatesCount}`);
    }

    function updateScrollStatusBadge(text) {
        const badge = document.getElementById('scroll-status-badge');
        if (badge) badge.innerText = text;
    }

    // Initialize layout preferences on load
    document.addEventListener('DOMContentLoaded', function() {
        const savedMode = localStorage.getItem('date_matrix_view_mode') || (totalDatesCount <= 7 ? 'fit' : 'scroll');
        const savedPreset = localStorage.getItem('date_matrix_col_preset') || (totalDatesCount <= 7 ? 'fit' : '7');
        const savedDensity = localStorage.getItem('date_matrix_density') === 'compact';

        isCompactDensity = savedDensity;
        applyDensityStyles(savedDensity);

        if (savedMode === 'fit') {
            setMatrixViewMode('fit');
        } else {
            setMatrixViewMode('scroll');
            applyColumnPreset(savedPreset, false);
        }

        const wrapper = document.getElementById('matrix-scroll-wrapper');
        if (wrapper) {
            wrapper.addEventListener('scroll', updateScrollTracker, { passive: true });
            
            // Allow horizontal scrolling via Shift + MouseWheel
            wrapper.addEventListener('wheel', function(e) {
                if (currentMatrixViewMode === 'scroll' && Math.abs(e.deltaX) < Math.abs(e.deltaY) && e.shiftKey) {
                    wrapper.scrollLeft += e.deltaY;
                    e.preventDefault();
                }
            }, { passive: false });
        }

        // Window resize listener to recompute screen fit
        window.addEventListener('resize', function() {
            if (currentMatrixViewMode === 'fit') {
                setMatrixViewMode('fit');
            } else {
                applyColumnPreset(currentColumnPreset, false);
            }
        });
    });
</script>
@endpush

<style>
    /* Compact Card Density Mode CSS */
    .matrix-compact .matrix-th {
        padding: 6px 4px !important;
    }
    .matrix-compact .matrix-th-date {
        font-size: 11px !important;
    }
    .matrix-compact .matrix-th-sub {
        font-size: 9px !important;
        margin-top: 1px !important;
    }
    .matrix-compact .matrix-cell {
        padding: 3px !important;
    }
    .matrix-compact .stock-item {
        padding: 4px 6px !important;
        border-radius: 8px !important;
    }
    .matrix-compact .stock-item-symbol {
        font-size: 11px !important;
    }
    .matrix-compact .stock-item-badge {
        font-size: 9px !important;
        margin-top: 1px !important;
    }
    .matrix-compact .stock-item-footer {
        font-size: 9px !important;
        margin-top: 1px !important;
        padding-top: 1px !important;
    }
</style>
@endsection
