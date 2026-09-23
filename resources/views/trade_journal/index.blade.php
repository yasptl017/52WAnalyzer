@extends('layouts.app')

@section('title', 'Trade Journal - 52WAnalyzer')

@section('content')
<div class="space-y-6">

    <!-- Header Banner with "Log New Trade" button -->
    <div class="glass-panel p-6 rounded-3xl bg-gradient-to-r from-blue-50/80 via-white to-white dark:from-blue-950/40 dark:via-slate-900 dark:to-slate-900 border border-blue-200 dark:border-blue-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wider mb-2">
                <span>📖</span> Performance Tracking
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Interactive Trade Journal</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-2xl">
                Replaces the empty 1-row template from Excel. Track real-time open positions, target scale-outs, stop loss discipline, and aggregate portfolio win rate.
            </p>
        </div>

        <button onclick="document.getElementById('modal-new-trade').classList.remove('hidden')" 
                class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center gap-1.5 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Log New Trade
        </button>
    </div>

    <!-- Performance Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Trades</span>
            <div class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white font-mono">{{ $totalTrades }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Logged in journal</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Open Positions</span>
            <div class="mt-2 text-3xl font-extrabold text-indigo-600 dark:text-indigo-400 font-mono">{{ $openTrades }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Currently active</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Win Rate</span>
            <div class="mt-2 text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">{{ $winRate }}%</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">{{ $wonTrades }} Won / {{ $lostTrades }} Lost</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Realized P&L</span>
            <div class="mt-2 text-3xl font-extrabold {{ $totalPnl >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} font-mono">
                {{ $totalPnl >= 0 ? '+' : '' }}₹{{ number_format($totalPnl, 2) }}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Cumulative closed profit</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Profit Factor</span>
            <div class="mt-2 text-3xl font-extrabold text-amber-600 dark:text-amber-400 font-mono">{{ $profitFactor }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Gross Wins / Losses</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="flex items-center space-x-2">
        <a href="{{ route('journal.index') }}" 
           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $statusFilter === 'ALL' ? 'bg-blue-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
            All ({{ $totalTrades }})
        </a>
        <a href="{{ route('journal.index', ['status' => 'OPEN']) }}" 
           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $statusFilter === 'OPEN' ? 'bg-indigo-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
            Active Open ({{ $openTrades }})
        </a>
        <a href="{{ route('journal.index', ['status' => 'WON']) }}" 
           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $statusFilter === 'WON' ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
            Won ({{ $wonTrades }})
        </a>
        <a href="{{ route('journal.index', ['status' => 'LOST']) }}" 
           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $statusFilter === 'LOST' ? 'bg-rose-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
            Lost ({{ $lostTrades }})
        </a>
    </div>

    <!-- Trades Table -->
    <div class="glass-panel rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/90 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3.5 px-4">Symbol</th>
                        <th class="py-3.5 px-4">Entry Date</th>
                        <th class="py-3.5 px-4 text-right">Entry (₹)</th>
                        <th class="py-3.5 px-4 text-right">Stop Loss (₹)</th>
                        <th class="py-3.5 px-4 text-right">Target (₹)</th>
                        <th class="py-3.5 px-4 text-right">Quantity</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Realized P&L</th>
                        <th class="py-3.5 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($trades as $t)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4 font-mono font-extrabold text-slate-900 dark:text-white text-base">
                                {{ $t->stock->symbol }}
                            </td>

                            <td class="py-3 px-4 text-xs font-mono text-slate-600 dark:text-slate-400">
                                {{ date('d M Y', strtotime($t->entry_date)) }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-slate-900 dark:text-white font-bold">
                                ₹{{ number_format($t->entry_price, 2) }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-rose-600 dark:text-rose-400 font-semibold">
                                ₹{{ number_format($t->stop_loss, 2) }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
                                {{ $t->target_price ? '₹' . number_format($t->target_price, 2) : '-' }}
                            </td>

                            <td class="py-3 px-4 text-right font-mono text-slate-700 dark:text-slate-300 font-bold">
                                {{ number_format($t->quantity) }}
                            </td>

                            <td class="py-3 px-4 text-center">
                                @if($t->status === 'OPEN')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-500/30">OPEN</span>
                                @elseif($t->status === 'WON')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">WON</span>
                                @elseif($t->status === 'LOST')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/30">LOST</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">CLOSED</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-right font-mono font-black">
                                @if($t->pnl !== null)
                                    <span class="{{ $t->pnl >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $t->pnl >= 0 ? '+' : '' }}₹{{ number_format($t->pnl, 2) }}
                                    </span>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 text-xs">-</span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('journal.destroy', $t->id) }}" onsubmit="return confirm('Remove this trade entry?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                No trades logged yet. Click "Log New Trade" or use the Risk Calculator to record your first trade!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($trades->hasPages())
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800">
                {{ $trades->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal: Log New Trade -->
<div id="modal-new-trade" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 hidden">
    <div class="glass-panel p-6 rounded-3xl max-w-md w-full border border-blue-200 dark:border-blue-500/30 shadow-2xl relative bg-white dark:bg-slate-900">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Log New Trade</h3>
            <button onclick="document.getElementById('modal-new-trade').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 dark:hover:text-white font-bold text-lg">&times;</button>
        </div>

        <form method="POST" action="{{ route('journal.store') }}" class="space-y-3.5 mt-4">
            @csrf
            <div>
                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Symbol</label>
                <input type="text" name="symbol" required placeholder="e.g. TRENT" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white uppercase font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Entry Date</label>
                    <input type="date" name="entry_date" value="{{ date('Y-m-d') }}" required class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none shadow-sm">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Quantity</label>
                    <input type="number" name="quantity" min="1" value="100" required class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white font-mono font-bold focus:outline-none shadow-sm">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Entry (₹)</label>
                    <input type="number" name="entry_price" step="0.05" required class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-2 text-xs text-slate-900 dark:text-white font-mono focus:outline-none shadow-sm">
                </div>
                <div>
                    <label class="text-xs font-bold text-rose-600 dark:text-rose-400 block mb-1">Stop Loss (₹)</label>
                    <input type="number" name="stop_loss" step="0.05" required class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-2 text-xs text-slate-900 dark:text-white font-mono focus:outline-none shadow-sm">
                </div>
                <div>
                    <label class="text-xs font-bold text-emerald-600 dark:text-emerald-400 block mb-1">Target (₹)</label>
                    <input type="number" name="target_price" step="0.05" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-2 text-xs text-slate-900 dark:text-white font-mono focus:outline-none shadow-sm">
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Status</label>
                <select name="status" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none shadow-sm">
                    <option value="OPEN">OPEN (Active Position)</option>
                    <option value="WON">WON (Closed at Profit)</option>
                    <option value="LOST">LOST (Closed at Loss)</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Notes / Strategy</label>
                <textarea name="notes" rows="2" placeholder="52W breakout pullback setup..." class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none shadow-sm"></textarea>
            </div>

            <div class="pt-2 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('modal-new-trade').classList.add('hidden')" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl shadow-md">Save Trade</button>
            </div>
        </form>
    </div>
</div>
@endsection
