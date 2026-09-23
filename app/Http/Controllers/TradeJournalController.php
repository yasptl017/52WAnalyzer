<?php

namespace App\Http\Controllers;

use App\Models\TradeJournal;
use App\Models\Stock;
use Illuminate\Http\Request;

class TradeJournalController extends Controller
{
    /**
     * Display the Trade Journal dashboard.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->get('status', 'ALL');

        $query = TradeJournal::with('stock');

        if ($statusFilter !== 'ALL') {
            $query->where('status', $statusFilter);
        }

        $trades = $query->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Performance Statistics
        $totalTrades = TradeJournal::count();
        $openTrades = TradeJournal::where('status', 'OPEN')->count();
        $wonTrades = TradeJournal::where('status', 'WON')->count();
        $lostTrades = TradeJournal::where('status', 'LOST')->count();
        $closedTrades = $wonTrades + $lostTrades;

        $winRate = $closedTrades > 0 ? round(($wonTrades / $closedTrades) * 100, 1) : 0;
        $totalPnl = TradeJournal::whereIn('status', ['WON', 'LOST', 'CLOSED'])->sum('pnl');

        $grossProfit = TradeJournal::where('pnl', '>', 0)->sum('pnl');
        $grossLoss = abs(TradeJournal::where('pnl', '<', 0)->sum('pnl'));
        $profitFactor = $grossLoss > 0 ? round($grossProfit / $grossLoss, 2) : ($grossProfit > 0 ? 99.9 : 0.0);

        return view('trade_journal.index', compact(
            'trades',
            'totalTrades',
            'openTrades',
            'wonTrades',
            'lostTrades',
            'winRate',
            'totalPnl',
            'profitFactor',
            'statusFilter'
        ));
    }

    /**
     * Store a new trade entry.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->isPro()) {
            $existingCount = TradeJournal::count();
            if ($existingCount >= 50) {
                return redirect()->route('pricing.index', ['required_tier' => 'PRO'])
                    ->with('error', '⚡ Starter Journal Limit Reached: Starter tier includes up to 50 trade logs. Upgrade to PRO for unlimited journal logging and CSV analytics.');
            }
        }

        $validated = validator($request->all(), [
            'symbol' => 'required|string|max:20',
            'entry_date' => 'required|date',
            'entry_price' => 'required|numeric|min:0.01',
            'stop_loss' => 'required|numeric|min:0.01',
            'target_price' => 'nullable|numeric|min:0.01',
            'quantity' => 'required|integer|min:1',
            'status' => 'required|in:OPEN,WON,LOST,CLOSED',
            'exit_date' => 'nullable|date',
            'exit_price' => 'nullable|numeric|min:0.01',
            'notes' => 'nullable|string',
        ])->validate();

        $stock = Stock::firstOrCreate(
            ['symbol' => strtoupper(trim($validated['symbol']))],
            ['asset_class' => 'EQUITY', 'series' => 'EQ']
        );

        $realizedPnl = null;
        $exitPrice = $validated['exit_price'] ?? null;
        if (!empty($exitPrice)) {
            $realizedPnl = ($exitPrice - $validated['entry_price']) * $validated['quantity'];
        }

        TradeJournal::create([
            'stock_id' => $stock->id,
            'entry_date' => $validated['entry_date'],
            'entry_price' => $validated['entry_price'],
            'stop_loss' => $validated['stop_loss'] ?? null,
            'target_price' => $validated['target_price'] ?? null,
            'quantity' => $validated['quantity'],
            'status' => $validated['status'],
            'exit_date' => $validated['exit_date'] ?? null,
            'exit_price' => $exitPrice,
            'pnl' => $realizedPnl,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('journal.index')->with('success', "Trade logged for {$stock->symbol} successfully!");
    }

    /**
     * Update/Close an existing trade entry.
     */
    public function update(Request $request, $id)
    {
        $trade = TradeJournal::findOrFail($id);

        $validated = validator($request->all(), [
            'status' => 'required|in:OPEN,WON,LOST,CLOSED',
            'exit_date' => 'nullable|date',
            'exit_price' => 'nullable|numeric|min:0.01',
            'notes' => 'nullable|string',
        ])->validate();

        $exitPrice = $validated['exit_price'] ?? null;
        $realizedPnl = $trade->pnl;
        if (!empty($exitPrice)) {
            $realizedPnl = ($exitPrice - $trade->entry_price) * $trade->quantity;
        }

        $trade->update([
            'status' => $validated['status'],
            'exit_date' => $validated['exit_date'] ?? null,
            'exit_price' => $exitPrice,
            'pnl' => $realizedPnl,
            'notes' => $validated['notes'] ?? $trade->notes,
        ]);

        return redirect()->route('journal.index')->with('success', "Trade updated successfully!");
    }

    /**
     * Delete a trade entry.
     */
    public function destroy($id)
    {
        $trade = TradeJournal::findOrFail($id);
        $symbol = $trade->stock->symbol ?? 'Trade';
        $trade->delete();

        return redirect()->route('journal.index')->with('success', "Trade for {$symbol} removed.");
    }
}
