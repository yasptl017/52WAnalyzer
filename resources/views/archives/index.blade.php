@extends('layouts.app')

@section('title', 'Data Archives Manager - 52WAnalyzer')

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="glass-panel p-6 rounded-3xl bg-gradient-to-r from-cyan-50/80 via-white to-white dark:from-cyan-950/40 dark:via-slate-900 dark:to-slate-900 border border-cyan-200 dark:border-cyan-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-700 dark:text-cyan-400 text-xs font-bold uppercase tracking-wider mb-2">
                <span>🗂️</span> Raw Snapshot Vault
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Date-Wise Data Archives</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-2xl">
                Inspect date-wise downloaded snapshots, view raw JSON feeds from NSE, check historical data integrity, or trigger on-demand algorithmic re-analysis for any trading day.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-700 dark:text-cyan-300 text-xs font-bold font-mono">
                📁 storage/app/archives/
            </span>
        </div>
    </div>

    <!-- Summary Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Archived Days</span>
            <div class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white font-mono">{{ $totalArchives }}</div>
            <div class="text-[11px] text-cyan-600 dark:text-cyan-400 mt-1">Trading session snapshots</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Verified Complete</span>
            <div class="mt-2 text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">{{ $completeArchives }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">All datasets intact</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total 52WH Entries</span>
            <div class="mt-2 text-3xl font-extrabold text-indigo-600 dark:text-indigo-400 font-mono">{{ number_format($totalArchived52wh) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Historical appearance rows</div>
        </div>

        <div class="glass-card p-4 rounded-2xl">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total VG Entries</span>
            <div class="mt-2 text-3xl font-extrabold text-amber-600 dark:text-amber-400 font-mono">{{ number_format($totalArchivedVg) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Volume breakout records</div>
        </div>
    </div>

    <!-- Archives Table -->
    <div class="glass-panel rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/90 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3.5 px-4">Trade Date</th>
                        <th class="py-3.5 px-4">Source</th>
                        <th class="py-3.5 px-4 text-center">52W Highs</th>
                        <th class="py-3.5 px-4 text-center">Volume Gainers</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4">Notes</th>
                        <th class="py-3.5 px-4 text-center">Raw Data Inspector</th>
                        <th class="py-3.5 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                    @forelse($archives as $a)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4 font-mono font-extrabold text-slate-900 dark:text-white text-base">
                                {{ date('D, d M Y', strtotime($a->trade_date)) }}
                            </td>

                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $a->source === 'NSE' ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                                    {{ $a->source }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-center font-mono font-bold text-slate-900 dark:text-white">
                                {{ number_format($a->total_52wh) }}
                            </td>

                            <td class="py-3 px-4 text-center font-mono font-bold text-amber-600 dark:text-amber-400">
                                {{ number_format($a->total_vg) }}
                            </td>

                            <td class="py-3 px-4 text-center">
                                @if($a->status === 'COMPLETE')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                        COMPLETE
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                                        {{ $a->status }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-xs text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                {{ $a->notes ?: 'Archived dataset.' }}
                            </td>

                            <!-- Raw Inspector Buttons -->
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center space-x-1.5">
                                    <button onclick="inspectArchive('{{ date('Y-m-d', strtotime($a->trade_date)) }}', '52wh')" 
                                            class="px-2.5 py-1 rounded text-xs font-bold bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-600/10 dark:hover:bg-indigo-600/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/20 transition shadow-sm">
                                        52WH JSON
                                    </button>
                                    <button onclick="inspectArchive('{{ date('Y-m-d', strtotime($a->trade_date)) }}', 'vg')" 
                                            class="px-2.5 py-1 rounded text-xs font-bold bg-amber-50 hover:bg-amber-100 dark:bg-amber-600/10 dark:hover:bg-amber-600/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/20 transition shadow-sm">
                                        VG JSON
                                    </button>
                                </div>
                            </td>

                            <!-- Action: Re-Analyze -->
                            <td class="py-3 px-4 text-center">
                                <form method="POST" action="{{ route('archives.reanalyze', date('Y-m-d', strtotime($a->trade_date))) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition" title="Re-run analysis pipeline">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                No archives recorded yet. Click "Download NSE" in the top bar to archive today's session!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($archives->hasPages())
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800">
                {{ $archives->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal: Raw JSON / CSV Inspector -->
<div id="modal-inspector" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 hidden">
    <div class="glass-panel p-6 rounded-3xl max-w-3xl w-full border border-cyan-200 dark:border-cyan-500/30 shadow-2xl flex flex-col max-h-[85vh] bg-white dark:bg-slate-900">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h3 id="inspector-title" class="text-base font-bold text-slate-900 dark:text-white font-mono">Raw File Inspector</h3>
                <span id="inspector-subtitle" class="text-xs text-slate-500 dark:text-slate-400">Viewing archived snapshot payload</span>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="copyInspectorContent()" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg transition">
                    Copy
                </button>
                <button onclick="document.getElementById('modal-inspector').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 dark:hover:text-white font-bold text-xl px-2">&times;</button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto mt-4 custom-scrollbar bg-slate-900 dark:bg-slate-950 p-4 rounded-xl border border-slate-700 dark:border-slate-800">
            <pre id="inspector-content" class="text-xs font-mono text-emerald-400 whitespace-pre-wrap">Loading...</pre>
        </div>
    </div>
</div>

@push('scripts')
<script>
    async function inspectArchive(date, type) {
        const modal = document.getElementById('modal-inspector');
        const title = document.getElementById('inspector-title');
        const content = document.getElementById('inspector-content');

        title.innerText = `${type.toUpperCase()} Payload — ${date}`;
        content.innerText = 'Fetching raw archive from disk / DB...';
        modal.classList.remove('hidden');

        try {
            const res = await fetch(`/archives/${date}/raw/${type}`);
            const text = await res.text();
            content.innerText = text;
        } catch (e) {
            content.innerText = 'Error loading file: ' + e.message;
        }
    }

    function copyInspectorContent() {
        const content = document.getElementById('inspector-content').innerText;
        navigator.clipboard.writeText(content);
        showToast('Raw file content copied to clipboard!', 'success');
    }
</script>
@endpush
@endsection
