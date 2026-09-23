@extends('layouts.app')

@section('title', 'Live Interactive Risk Calculator - 52WAnalyzer')

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="glass-panel p-6 rounded-3xl bg-gradient-to-r from-emerald-50/80 via-white to-white dark:from-emerald-950/40 dark:via-slate-900 dark:to-slate-900 border border-emerald-200 dark:border-emerald-500/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider mb-2">
                <span>🧮</span> Capital Sizing & Risk Management
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Interactive Position Size & Risk Calculator</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-2xl">
                Replaces the static ₹5L single-row template from Excel. Dynamically computes the exact number of shares to buy so that you never risk more than your predefined loss percentage.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">Rules:</span>
            <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 text-xs font-bold border border-emerald-500/20">Max 2% Risk</span>
            <span class="px-2.5 py-1 rounded-lg bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 text-xs font-bold border border-indigo-500/20">Min 2:1 R:R</span>
        </div>
    </div>

    <!-- Quick Load Candidate Setups -->
    @if($topCandidates->isNotEmpty())
        <div class="glass-panel p-4 rounded-2xl">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 block mb-2.5">
                ⚡ Quick Load from Top Trade Candidates:
            </span>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5">
                @foreach($topCandidates as $tc)
                    <button type="button" 
                            onclick="loadCandidate('{{ $tc->stock->symbol }}', {{ $tc->entry_price }}, {{ $tc->stop_loss }}, {{ $tc->target_price_2r }})"
                            class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 hover:bg-indigo-50 dark:hover:bg-indigo-600/20 border border-slate-200 dark:border-slate-700/60 hover:border-indigo-300 dark:hover:border-indigo-500/40 text-left transition group shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900 dark:text-white font-mono text-xs group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ $tc->stock->symbol }}</span>
                            <span class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">{{ number_format($tc->setup_score, 0) }}pts</span>
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">₹{{ number_format($tc->entry_price, 1) }} / SL ₹{{ number_format($tc->stop_loss, 1) }}</div>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Calculator Grid: Left Inputs & Right Live Results -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left Column: Inputs (5 Cols) -->
        <div class="lg:col-span-5 glass-panel p-6 rounded-3xl space-y-5">
            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">⚙️</span>
                Account & Trade Parameters
            </h2>

            <!-- Capital Input -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Total Trading Capital (₹)</label>
                    <span id="label-capital" class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">₹5,00,000</span>
                </div>
                <input type="number" id="input-capital" value="{{ $capital }}" step="10000" min="10000"
                       class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white font-mono text-base font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                
                <!-- Quick Capital Presets -->
                <div class="flex flex-wrap gap-1.5 pt-1">
                    @foreach([100000 => '1L', 200000 => '2L', 500000 => '5L', 1000000 => '10L', 2500000 => '25L', 5000000 => '50L', 10000000 => '1Cr'] as $capVal => $capLabel)
                        <button type="button" onclick="setCapital({{ $capVal }})" 
                                class="px-2 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition">
                            ₹{{ $capLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Risk % Slider & Input -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Risk Per Trade (%)</label>
                    <span id="label-risk-pct" class="text-xs font-mono font-extrabold text-amber-600 dark:text-amber-400">1.0%</span>
                </div>
                <input type="number" id="input-risk-pct" value="{{ $riskPct }}" step="0.1" min="0.1" max="10"
                       class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-2 text-slate-900 dark:text-white font-mono text-base font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                
                <!-- Quick Risk Presets -->
                <div class="flex gap-2 pt-1">
                    @foreach([0.5, 1.0, 1.5, 2.0, 3.0] as $r)
                        <button type="button" onclick="setRiskPct({{ $r }})" 
                                class="flex-1 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition">
                            {{ $r }}%
                        </button>
                    @endforeach
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-800">

            <!-- Stock Symbol -->
            <div class="space-y-1">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Stock Symbol</label>
                <input type="text" id="input-symbol" value="{{ $symbol ?: 'RELIANCE' }}" placeholder="e.g. TRENT, CDSL"
                       class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white font-mono text-base font-extrabold uppercase focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
            </div>

            <!-- Entry Price -->
            <div class="space-y-1">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Planned Entry Price (₹)</label>
                <input type="number" id="input-entry" value="{{ $entry }}" step="0.05" min="0.1"
                       class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white font-mono text-base font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
            </div>

            <!-- Stop Loss Price -->
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Stop Loss Price (₹)</label>
                    <span id="label-sl-pct" class="text-xs font-mono text-rose-600 dark:text-rose-400"></span>
                </div>
                <input type="number" id="input-sl" value="{{ $sl }}" step="0.05" min="0.1"
                       class="w-full bg-white dark:bg-slate-900 border border-rose-300 dark:border-rose-900/60 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white font-mono text-base font-bold focus:ring-2 focus:ring-rose-500 focus:outline-none shadow-sm">
            </div>

            <!-- Profit Target Price -->
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Target Price (₹)</label>
                    <span id="label-rr-badge" class="text-xs font-mono font-bold text-amber-600 dark:text-amber-400"></span>
                </div>
                <input type="number" id="input-target" value="{{ round($entry + (($entry - $sl) * 2), 2) }}" step="0.05" min="0.1"
                       class="w-full bg-white dark:bg-slate-900 border border-emerald-300 dark:border-emerald-900/60 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white font-mono text-base font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none shadow-sm">
            </div>
        </div>

        <!-- Right Column: Live Computation Cards (7 Cols) -->
        <div class="lg:col-span-7 space-y-5">
            
            <!-- Hero Card: Recommended Position Size -->
            <div class="glass-panel p-6 rounded-3xl border border-indigo-200 dark:border-indigo-500/40 bg-gradient-to-br from-indigo-50/70 via-white to-white dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/40 relative overflow-hidden shadow-xl">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Recommended Execution Size</span>
                    <span id="badge-capital-pct" class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-500/30">
                        15% Capital
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400 block">Buy Quantity:</span>
                        <div id="res-shares" class="text-4xl font-black text-slate-900 dark:text-white font-mono tracking-tight">0</div>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Shares / Units</span>
                    </div>

                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400 block">Total Order Value:</span>
                        <div id="res-total-value" class="text-4xl font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight">₹0</div>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Invested Capital</span>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Max Permissible Loss:</span>
                        <span id="res-max-loss" class="text-sm font-black text-rose-600 dark:text-rose-400 font-mono">₹0</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Risk per Share:</span>
                        <span id="res-risk-per-share" class="text-sm font-bold text-slate-800 dark:text-slate-200 font-mono">₹0</span>
                    </div>
                </div>
            </div>

            <!-- Profit Targets & Multipliers (1R, 2R, 3R) -->
            <div class="glass-panel p-6 rounded-3xl space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center justify-between">
                    <span>Target Scale-Out Scenarios</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-normal">Based on 1R = Risk Amount</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- 1R Target -->
                    <div class="glass-card p-3.5 rounded-2xl">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">1R Target</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">Break-Even</span>
                        </div>
                        <div id="target-1r-price" class="text-xl font-black text-slate-900 dark:text-white font-mono mt-1">₹0</div>
                        <div id="target-1r-profit" class="text-xs text-emerald-600 dark:text-emerald-400 font-mono mt-1">+₹0 Profit</div>
                    </div>

                    <!-- 2R Target -->
                    <div class="glass-card p-3.5 rounded-2xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/10">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400">2R Target</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 font-bold">Optimal</span>
                        </div>
                        <div id="target-2r-price" class="text-xl font-black text-emerald-600 dark:text-emerald-300 font-mono mt-1">₹0</div>
                        <div id="target-2r-profit" class="text-xs text-emerald-700 dark:text-emerald-400 font-mono mt-1 font-bold">+₹0 Profit</div>
                    </div>

                    <!-- 3R Target -->
                    <div class="glass-card p-3.5 rounded-2xl border border-purple-200 dark:border-purple-500/30 bg-purple-50/50 dark:bg-purple-950/10">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-purple-700 dark:text-purple-400">3R Target</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-500/20 text-purple-800 dark:text-purple-300 font-bold">Runner</span>
                        </div>
                        <div id="target-3r-price" class="text-xl font-black text-purple-600 dark:text-purple-300 font-mono mt-1">₹0</div>
                        <div id="target-3r-profit" class="text-xs text-purple-700 dark:text-purple-400 font-mono mt-1 font-bold">+₹0 Profit</div>
                    </div>
                </div>
            </div>

            <!-- Quick Action: Direct Log to Trade Journal -->
            <div class="glass-panel p-5 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Execute and Journal this Setup</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Send these exact calculated parameters straight to your Trade Journal.</p>
                </div>

                <form method="POST" action="{{ route('journal.store') }}" id="form-journal">
                    @csrf
                    <input type="hidden" name="symbol" id="form-symbol" value="">
                    <input type="hidden" name="entry_date" value="{{ date('Y-m-d') }}">
                    <input type="hidden" name="entry_price" id="form-entry" value="">
                    <input type="hidden" name="stop_loss" id="form-sl" value="">
                    <input type="hidden" name="target_price" id="form-target" value="">
                    <input type="hidden" name="quantity" id="form-qty" value="">
                    <input type="hidden" name="status" value="OPEN">
                    <input type="hidden" name="notes" value="Calculated via Risk Calculator">

                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Log to Trade Journal
                    </button>
                </form>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
    function calculateRisk() {
        const capital = parseFloat(document.getElementById('input-capital').value) || 0;
        const riskPct = parseFloat(document.getElementById('input-risk-pct').value) || 0;
        const entry = parseFloat(document.getElementById('input-entry').value) || 0;
        const sl = parseFloat(document.getElementById('input-sl').value) || 0;
        const target = parseFloat(document.getElementById('input-target').value) || 0;
        const symbol = document.getElementById('input-symbol').value.toUpperCase();

        // Display Labels
        document.getElementById('label-capital').innerText = '₹' + capital.toLocaleString('en-IN');
        document.getElementById('label-risk-pct').innerText = riskPct.toFixed(1) + '%';

        // Risk math
        const maxLoss = (capital * (riskPct / 100));
        const riskPerShare = Math.max(0.01, (entry - sl));
        const slPct = entry > 0 ? (((entry - sl) / entry) * 100).toFixed(1) : 0;
        document.getElementById('label-sl-pct').innerText = `(-${slPct}%)`;

        let shares = 0;
        if (riskPerShare > 0) {
            shares = Math.floor(maxLoss / riskPerShare);
        }

        const totalValue = shares * entry;
        const capitalPct = capital > 0 ? ((totalValue / capital) * 100).toFixed(1) : 0;

        // Targets Math
        const target1r = entry + riskPerShare;
        const target2r = entry + (riskPerShare * 2);
        const target3r = entry + (riskPerShare * 3);

        const profit1r = shares * riskPerShare;
        const profit2r = shares * (riskPerShare * 2);
        const profit3r = shares * (riskPerShare * 3);

        const currentRr = riskPerShare > 0 && target > entry ? ((target - entry) / riskPerShare).toFixed(1) : 0;
        document.getElementById('label-rr-badge').innerText = `(R:R 1 : ${currentRr})`;

        // Update Outputs
        document.getElementById('res-shares').innerText = shares.toLocaleString('en-IN');
        document.getElementById('res-total-value').innerText = '₹' + Math.round(totalValue).toLocaleString('en-IN');
        document.getElementById('res-max-loss').innerText = '₹' + Math.round(maxLoss).toLocaleString('en-IN');
        document.getElementById('res-risk-per-share').innerText = '₹' + riskPerShare.toFixed(2);
        document.getElementById('badge-capital-pct').innerText = `${capitalPct}% Capital`;

        document.getElementById('target-1r-price').innerText = '₹' + target1r.toFixed(2);
        document.getElementById('target-1r-profit').innerText = '+₹' + Math.round(profit1r).toLocaleString('en-IN');

        document.getElementById('target-2r-price').innerText = '₹' + target2r.toFixed(2);
        document.getElementById('target-2r-profit').innerText = '+₹' + Math.round(profit2r).toLocaleString('en-IN');

        document.getElementById('target-3r-price').innerText = '₹' + target3r.toFixed(2);
        document.getElementById('target-3r-profit').innerText = '+₹' + Math.round(profit3r).toLocaleString('en-IN');

        // Form bindings
        document.getElementById('form-symbol').value = symbol;
        document.getElementById('form-entry').value = entry;
        document.getElementById('form-sl').value = sl;
        document.getElementById('form-target').value = target;
        document.getElementById('form-qty').value = shares;
    }

    function setCapital(val) {
        document.getElementById('input-capital').value = val;
        calculateRisk();
    }

    function setRiskPct(val) {
        document.getElementById('input-risk-pct').value = val;
        calculateRisk();
    }

    function loadCandidate(sym, entry, sl, target) {
        document.getElementById('input-symbol').value = sym;
        document.getElementById('input-entry').value = entry;
        document.getElementById('input-sl').value = sl;
        document.getElementById('input-target').value = target;
        calculateRisk();
        showToast(`Loaded ${sym} setup parameters!`, 'success');
    }

    // Attach real-time listeners
    ['input-capital', 'input-risk-pct', 'input-entry', 'input-sl', 'input-target', 'input-symbol'].forEach(id => {
        document.getElementById(id).addEventListener('input', calculateRisk);
    });

    document.addEventListener('DOMContentLoaded', calculateRisk);
</script>
@endpush
@endsection
