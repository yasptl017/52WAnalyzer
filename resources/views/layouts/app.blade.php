<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NSE 52W High & Volume Gainers Analyzer')</title>
    
    <!-- Theme Initializer (Default: Light Mode) -->
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Google Fonts: Plus Jakarta Sans (UI), Outfit (Headings), JetBrains Mono (Numbers/Symbols) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (via official CDN for seamless zero-build instant rendering) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', '-apple-system', 'sans-serif'],
                        heading: ['"Outfit"', '"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
                    },
                    colors: {
                        slate: {
                            850: '#131c2e',
                            900: '#0f172a',
                            950: '#080d1a',
                        },
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Custom Light/Dark Mode Styles & Typography Enhancements -->
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            transition: background-color 0.2s ease, color 0.2s ease;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            font-feature-settings: "cv02", "cv03", "cv04", "cv11", "tnum";
            font-variant-numeric: tabular-nums;
        }

        h1, h2, h3, h4, .font-heading {
            font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.02em;
        }

        .font-mono, [data-mono="true"] {
            font-family: 'JetBrains Mono', monospace;
            font-feature-settings: "zero", "tnum";
        }

        /* Light Mode (Default) */
        html:not(.dark) body {
            background-color: #f8fafc;
            color: #0f172a;
        }
        html:not(.dark) .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -2px rgba(0, 0, 0, 0.03);
        }
        html:not(.dark) .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        }
        html:not(.dark) .glass-card:hover {
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.1);
        }
        html:not(.dark) table thead tr {
            background-color: #f8fafc !important;
            color: #475569 !important;
            border-color: #e2e8f0 !important;
        }
        html:not(.dark) table tbody tr:hover {
            background-color: #f1f5f9 !important;
        }
        html:not(.dark) table tbody tr {
            border-color: #f1f5f9 !important;
        }
        html:not(.dark) .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        html:not(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
        }

        /* Dark Mode */
        html.dark body {
            background-color: #080d1a;
            color: #f1f5f9;
        }
        html.dark .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(51, 65, 85, 0.4);
        }
        html.dark .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(71, 85, 105, 0.3);
        }
        html.dark .glass-card:hover {
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 8px 24px -4px rgba(99, 102, 241, 0.15);
        }
        html.dark .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
        }
        html.dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(71, 85, 105, 0.6);
        }

        .glow-emerald {
            box-shadow: 0 0 20px -3px rgba(16, 185, 129, 0.3);
        }
        .glow-indigo {
            box-shadow: 0 0 20px -3px rgba(99, 102, 241, 0.3);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            border-radius: 9999px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Top Navigation & Header Bar -->
    <header class="sticky top-0 z-50 glass-panel border-b border-slate-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Brand & Logo -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-emerald-400 flex items-center justify-center shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition-transform duration-200">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-lg font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                                52W<span class="text-indigo-600 dark:text-indigo-400">Analyzer</span>
                                <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">NSE Real-Time</span>
                            </span>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 block -mt-1">Algorithmic Momentum & Volume Engine</span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Tabs -->
                <nav class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ request()->routeIs('dashboard') ? 'bg-indigo-50 dark:bg-indigo-600/20 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        🔥 Momentum Dashboard
                    </a>
                    <a href="{{ route('volume-gainers.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ request()->routeIs('volume-gainers.*') ? 'bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        ⚡ Volume Gainers
                    </a>
                    <a href="{{ route('date-matrix.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ request()->routeIs('date-matrix.*') ? 'bg-indigo-50 dark:bg-indigo-600/20 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        📊 Date Matrix
                    </a>
                    <a href="{{ route('re-emergence.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ request()->routeIs('re-emergence.*') ? 'bg-blue-50 dark:bg-blue-600/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        🔄 Re-Emergence
                    </a>
                    <a href="{{ route('trading-engine.candidates') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ request()->routeIs('trading-engine.*') ? 'bg-violet-50 dark:bg-violet-500/20 text-violet-700 dark:text-violet-400 border border-violet-200 dark:border-violet-500/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        🎯 Trading Engine
                    </a>
                    <a href="{{ route('risk-calculator.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ request()->routeIs('risk-calculator.*') ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        🧮 Risk Calculator
                    </a>
                    <a href="{{ route('journal.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ request()->routeIs('journal.*') ? 'bg-blue-50 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        📖 Trade Journal
                    </a>
                    <a href="{{ route('strategy-simulation.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ request()->routeIs('strategy-simulation.*') ? 'bg-teal-50 dark:bg-teal-500/20 text-teal-700 dark:text-teal-400 border border-teal-200 dark:border-teal-500/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        🔬 Strategy Simulator
                    </a>
                    <a href="{{ route('pricing.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors {{ request()->routeIs('pricing.*') ? 'bg-indigo-50 dark:bg-indigo-600/20 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30' : 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40' }}">
                        💎 Pricing & Plans
                    </a>
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors {{ request()->routeIs('admin.*') ? 'bg-purple-50 dark:bg-purple-600/20 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-500/30' : 'text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-950/40' }}">
                            🛡️ Admin Panel
                        </a>
                    @endif
                    <a href="{{ route('archives.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ request()->routeIs('archives.*') ? 'bg-cyan-50 dark:bg-cyan-500/20 text-cyan-700 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-500/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        🗂️ Archives
                    </a>
                </nav>

                <!-- Quick Action Buttons, User Profile & Theme Switcher -->
                <div class="flex items-center space-x-2">
                    <!-- Light / Dark Mode Toggle Switch -->
                    <button id="theme-toggle" onclick="toggleTheme()" 
                            class="p-2 rounded-xl border border-slate-200 dark:border-slate-700/60 bg-white/80 dark:bg-slate-800/80 text-slate-600 dark:text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors shadow-sm" 
                            title="Toggle Light / Dark Mode">
                        <!-- Sun Icon (visible in Dark Mode) -->
                        <svg id="theme-toggle-light-icon" class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <!-- Moon Icon (visible in Light Mode) -->
                        <svg id="theme-toggle-dark-icon" class="w-4 h-4 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    <!-- User Account / Auth Dropdown or CTAs -->
                    @auth
                        @php
                            $userBadge = auth()->user()->getTierBadge();
                        @endphp
                        <a href="{{ route('profile') }}" class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800 hover:border-indigo-400 transition shadow-sm">
                            <span class="text-[10px] font-black px-1.5 py-0.5 rounded-lg {{ $userBadge['bg'] }}">
                                {{ $userBadge['icon'] }} {{ $userBadge['name'] }}
                            </span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 max-w-[100px] truncate hidden sm:inline">
                                {{ auth()->user()->name }}
                            </span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-3 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-lg transition">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" class="px-3.5 py-1.5 text-xs font-bold rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-md shadow-emerald-500/20 transition flex items-center gap-1">
                            <span>🎁</span> Free Trial
                        </a>
                    @endauth

                    <!-- Centralized NSE Sync & Market Status Pill -->
                    @php
                        $sysCalendar = app(\App\Services\TradingCalendarService::class);
                        $sysMarket = $sysCalendar->getMarketStatus();
                        $sysLatestSync = \App\Models\SystemSyncLog::latestSuccessful();
                    @endphp
                    <button onclick="openSystemSyncModal()" 
                            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white/90 dark:bg-slate-800/90 hover:border-indigo-400 dark:hover:border-indigo-500/50 shadow-sm transition text-xs font-semibold text-slate-700 dark:text-slate-200"
                            title="View Centralized NSE Data Sync Status">
                        @if($sysMarket['is_trading_day'])
                            <span class="w-2 h-2 rounded-full {{ $sysMarket['is_open'] ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        @endif
                        <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">NSE:</span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            {{ $sysLatestSync ? $sysLatestSync->trade_date->format('d M') : 'Pending' }}
                        </span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-md {{ $sysMarket['is_trading_day'] ? ($sysMarket['is_open'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300') : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400' }}">
                            {{ $sysMarket['is_trading_day'] ? ($sysMarket['is_open'] ? 'Live' : 'Closed') : 'Weekend' }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu Subbar -->
        <div class="md:hidden flex overflow-x-auto space-x-2 px-4 py-2 border-t border-slate-200 dark:border-slate-800 custom-scrollbar text-xs font-medium">
            <a href="{{ route('dashboard') }}" class="whitespace-nowrap px-3 py-1 rounded {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-500 dark:text-slate-400' }}">Dashboard</a>
            <a href="{{ route('volume-gainers.index') }}" class="whitespace-nowrap px-3 py-1 rounded {{ request()->routeIs('volume-gainers.*') ? 'bg-amber-600 text-white' : 'text-slate-500 dark:text-slate-400' }}">Volume Gainers</a>
            <a href="{{ route('date-matrix.index') }}" class="whitespace-nowrap px-3 py-1 rounded {{ request()->routeIs('date-matrix.*') ? 'bg-indigo-600 text-white' : 'text-slate-500 dark:text-slate-400' }}">Date Matrix</a>
            <a href="{{ route('re-emergence.index') }}" class="whitespace-nowrap px-3 py-1 rounded {{ request()->routeIs('re-emergence.*') ? 'bg-blue-600 text-white' : 'text-slate-500 dark:text-slate-400' }}">Re-Emergence</a>
            <a href="{{ route('trading-engine.candidates') }}" class="whitespace-nowrap px-3 py-1 rounded {{ request()->routeIs('trading-engine.*') ? 'bg-violet-600 text-white' : 'text-slate-500 dark:text-slate-400' }}">Trading Engine</a>
            <a href="{{ route('risk-calculator.index') }}" class="whitespace-nowrap px-3 py-1 rounded {{ request()->routeIs('risk-calculator.*') ? 'bg-emerald-600 text-white' : 'text-slate-500 dark:text-slate-400' }}">Risk Calculator</a>
            <a href="{{ route('journal.index') }}" class="whitespace-nowrap px-3 py-1 rounded {{ request()->routeIs('journal.*') ? 'bg-blue-600 text-white' : 'text-slate-500 dark:text-slate-400' }}">Journal</a>
            <a href="{{ route('strategy-simulation.index') }}" class="whitespace-nowrap px-3 py-1 rounded {{ request()->routeIs('strategy-simulation.*') ? 'bg-teal-600 text-white' : 'text-slate-500 dark:text-slate-400' }}">Simulator</a>
            <a href="{{ route('pricing.index') }}" class="whitespace-nowrap px-3 py-1 rounded font-bold text-indigo-600 dark:text-indigo-400">💎 Pricing</a>
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="whitespace-nowrap px-3 py-1 rounded font-bold text-purple-600 dark:text-purple-400">🛡️ Admin</a>
                @endif
                <a href="{{ route('profile') }}" class="whitespace-nowrap px-3 py-1 rounded text-slate-700 dark:text-slate-300">👤 Profile</a>
            @else
                <a href="{{ route('login') }}" class="whitespace-nowrap px-3 py-1 rounded text-slate-700 dark:text-slate-300">🔑 Sign In</a>
            @endauth
        </div>
    </header>

    <!-- Global Toast Alert Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none"></div>

    <!-- Main Content Body -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Flash session message -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm font-bold">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300 text-xs sm:text-sm font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-5 h-5 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 text-sm font-bold">&times;</button>
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-6 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-800 dark:text-amber-300 text-xs sm:text-sm font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('warning') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-700 text-sm font-bold">&times;</button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 dark:border-slate-800/80 bg-white dark:bg-slate-950 py-6 text-center text-xs text-slate-600 dark:text-slate-500 transition-colors">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div>
                <span class="font-bold text-slate-800 dark:text-slate-400">52WAnalyzer</span> — Algorithmic 52-Week High & Volume Spurt Analysis
            </div>
            <div class="flex items-center space-x-4 text-slate-600 dark:text-slate-400">
                <span>Database: <strong class="text-emerald-600 dark:text-emerald-400 font-mono">MySQL (Local)</strong></span>
                <span>•</span>
                <span>Source: <strong class="text-indigo-600 dark:text-indigo-400 font-mono">NSE India Real-Time</strong></span>
            </div>
        </div>
    </footer>

    <!-- Global JavaScript for Live Download Triggers & Theme Toggling -->
    <script>
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            showToast(`Switched to ${isDark ? 'Dark' : 'Light'} Mode`, 'success');
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const bgClass = type === 'success' 
                ? 'bg-white dark:bg-slate-900 border-emerald-500/50 text-emerald-800 dark:text-emerald-300' 
                : 'bg-white dark:bg-slate-900 border-rose-500/50 text-rose-800 dark:text-rose-300';
            toast.className = `pointer-events-auto p-4 rounded-xl border shadow-2xl flex items-center space-x-3 transition-all transform translate-y-2 opacity-0 duration-300 ${bgClass}`;
            toast.innerHTML = `
                <div class="text-sm font-medium">${message}</div>
            `;
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            }, 10);
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // System Centralized Sync Modal Functions
        function openSystemSyncModal() {
            const modal = document.getElementById('system-sync-modal');
            if (modal) {
                modal.classList.remove('hidden');
                refreshSyncStatusModal();
            }
        }

        function closeSystemSyncModal() {
            const modal = document.getElementById('system-sync-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        async function refreshSyncStatusModal() {
            try {
                const res = await fetch('/api/system/sync-status');
                const json = await res.json();
                if (json.success && json.data) {
                    const d = json.data;
                    const statusText = document.getElementById('sync-modal-market-status');
                    if (statusText) {
                        statusText.innerText = d.market.status_text + (d.market.reason ? ' • ' + d.market.reason : '');
                    }
                    if (d.latest_sync) {
                        const dateEl = document.getElementById('sync-modal-last-date');
                        const timeEl = document.getElementById('sync-modal-last-time');
                        const countEl = document.getElementById('sync-modal-last-counts');
                        const byEl = document.getElementById('sync-modal-last-by');
                        if (dateEl) dateEl.innerText = d.latest_sync.date;
                        if (timeEl) timeEl.innerText = d.latest_sync.synced_at_formatted + ' (' + d.latest_sync.time_ago + ')';
                        if (countEl) countEl.innerText = `${d.latest_sync.records_52wh} 52W Highs | ${d.latest_sync.records_vg} Volume Gainers`;
                        if (byEl) byEl.innerText = d.latest_sync.triggered_by;
                    }
                }
            } catch (e) {
                console.error('Failed to refresh sync status modal:', e);
            }
        }

        async function triggerSystemGlobalSync() {
            const btn = document.getElementById('btn-sync-modal-trigger');
            const icon = document.getElementById('icon-sync-modal-spinner');
            const text = document.getElementById('text-sync-modal-btn');

            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-70', 'cursor-not-allowed');
            }
            if (icon) icon.classList.remove('hidden');
            if (text) text.innerText = 'Synchronizing NSE Central Data...';

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('/api/system/global-sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({})
                });

                const data = await response.json();
                if (data.success || data.status === 'SKIPPED_HOLIDAY') {
                    showToast(data.message, data.status === 'SKIPPED_HOLIDAY' ? 'info' : 'success');
                    await refreshSyncStatusModal();
                    if (data.status !== 'SKIPPED_HOLIDAY') {
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } else {
                    showToast(data.message || 'Sync failed', 'error');
                }
            } catch (err) {
                showToast('Failed to trigger synchronization: ' + err.message, 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-70', 'cursor-not-allowed');
                }
                if (icon) icon.classList.add('hidden');
                if (text) text.innerText = '⚡ Trigger Central System Sync';
            }
        }

        async function triggerDailyDownload() {
            const btn = document.getElementById('btn-trigger-daily');
            const icon = document.getElementById('icon-download');
            const text = document.getElementById('text-download');
            
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-75', 'cursor-not-allowed');
            }
            if (text) text.innerText = 'Scraping NSE...';
            if (icon) icon.classList.add('animate-spin');

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('/api/system/global-sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({})
                });

                const data = await response.json();
                if (data.success) {
                    showToast(data.message + ' Reloading...', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else if (data.status === 'SKIPPED_HOLIDAY') {
                    showToast(data.message, 'info');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Failed to connect: ' + err.message, 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-75', 'cursor-not-allowed');
                }
                if (text) text.innerText = 'NSE Sync';
                if (icon) icon.classList.remove('animate-spin');
            }
        }

        async function triggerVgSnapshot() {
            const btn = document.getElementById('btn-trigger-vg');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-75');
            }

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('/api/trigger-vg-snapshot', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({})
                });

                const data = await response.json();
                if (data.success) {
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Failed to snapshot VG: ' + err.message, 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-75');
                }
            }
        }
    </script>

    <!-- Centralized System NSE Sync Status & Control Modal -->
    <div id="system-sync-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-base">
                        ⚡
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">NSE Centralized Data Synchronization</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Automated Daily EOD Sync & Holiday Calendar Engine</p>
                    </div>
                </div>
                <button onclick="closeSystemSyncModal()" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200/50 dark:hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 space-y-4 text-xs">
                <!-- Market Status Card -->
                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Indian Market Session</span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $sysMarket['is_trading_day'] ? ($sysMarket['is_open'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-400 border border-emerald-500/30' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300') : 'bg-rose-50 text-rose-700 dark:bg-rose-950/80 dark:text-rose-400 border border-rose-500/30' }}">
                            {{ $sysMarket['is_trading_day'] ? ($sysMarket['is_open'] ? '● MARKET LIVE' : '● MARKET CLOSED') : '● NON-TRADING DAY' }}
                        </span>
                    </div>
                    <div id="sync-modal-market-status" class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                        {{ $sysMarket['status_text'] }} • {{ $sysMarket['reason'] }}
                    </div>
                </div>

                <!-- Centralized Architecture Highlight -->
                <div class="p-3.5 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200/60 dark:border-indigo-500/20 flex items-start gap-2.5">
                    <span class="text-base flex-shrink-0">💡</span>
                    <p class="text-[11px] leading-relaxed text-indigo-900 dark:text-indigo-200">
                        <strong>Centralized Once-a-Day Execution:</strong> NSE market data is automatically synced once per trading day upon the <em>first user login</em> or evening batch run. The synced dataset is immediately available across all user accounts with zero redundant scraping.
                    </p>
                </div>

                <!-- Last System Sync Stats -->
                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/60 space-y-2.5">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-700/60">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Latest Active Trading Day:</span>
                        <span id="sync-modal-last-date" class="font-bold text-slate-900 dark:text-white font-mono">
                            {{ $sysLatestSync ? $sysLatestSync->trade_date->format('d M Y') : '—' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-700/60">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Last Synchronized:</span>
                        <span id="sync-modal-last-time" class="font-bold text-slate-800 dark:text-slate-200">
                            {{ $sysLatestSync ? $sysLatestSync->synced_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') . ' (' . $sysLatestSync->synced_at->diffForHumans() . ')' : '—' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-700/60">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Records Ingested:</span>
                        <span id="sync-modal-last-counts" class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                            {{ $sysLatestSync ? "{$sysLatestSync->records_52wh} 52W Highs | {$sysLatestSync->records_vg} Volume Gainers" : '—' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Trigger Source:</span>
                        <span id="sync-modal-last-by" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono">
                            {{ $sysLatestSync ? $sysLatestSync->triggered_by : 'NONE' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <button onclick="closeSystemSyncModal()" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                    Close
                </button>
                <button id="btn-sync-modal-trigger" onclick="triggerSystemGlobalSync()" 
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white shadow-md shadow-indigo-500/20 transition flex items-center gap-1.5">
                    <svg id="icon-sync-modal-spinner" class="hidden w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span id="text-sync-modal-btn">⚡ Trigger Central System Sync</span>
                </button>
            </div>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
