<?php

namespace App\Http\Controllers;

use App\Models\TradeSetup;
use App\Models\PatternMatch;
use App\Models\MomentumScore;
use App\Models\Stock;
use Illuminate\Http\Request;

class TradingEngineController extends Controller
{
    /**
     * Display Trade Candidates (High-Conviction setups).
     */
    public function candidates(Request $request)
    {
        $query = TradeSetup::with(['stock', 'stock.momentumScores'])
            ->where('status', 'CANDIDATE');

        if ($search = trim($request->get('search', ''))) {
            $query->whereHas('stock', function ($q) use ($search) {
                $q->where('symbol', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%");
            });
        }

        if ($style = $request->get('entry_style')) {
            if ($style !== 'ALL') {
                $query->where('entry_style', $style);
            }
        }

        if ($minRr = (float)$request->get('min_rr', 0)) {
            $query->where('rr_ratio', '>=', $minRr);
        }

        $sort = $request->get('sort', 'rr_desc');
        match ($sort) {
            'rr_desc' => $query->orderBy('rr_ratio', 'desc'),
            'entry_asc' => $query->orderBy('entry_price', 'asc'),
            'entry_desc' => $query->orderBy('entry_price', 'desc'),
            default => $query->orderBy('rr_ratio', 'desc'),
        };

        $candidates = $query->paginate(30)->withQueryString();

        // High conviction counts
        $totalCandidates = TradeSetup::where('status', 'CANDIDATE')->count();
        $eliteCount = TradeSetup::where('status', 'CANDIDATE')->where('rr_ratio', '>=', 2.5)->count();
        $avgRr = TradeSetup::where('status', 'CANDIDATE')->avg('rr_ratio');

        return view('trading_engine.candidates', compact(
            'candidates',
            'totalCandidates',
            'eliteCount',
            'avgRr'
        ));
    }

    /**
     * Display the Avoid List with clear rejection diagnostic reasons.
     */
    public function avoidList(Request $request)
    {
        $query = TradeSetup::with('stock')
            ->where('status', 'REJECTED');

        if ($search = trim($request->get('search', ''))) {
            $query->whereHas('stock', function ($q) use ($search) {
                $q->where('symbol', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%");
            });
        }

        if ($reason = $request->get('reason')) {
            if ($reason !== 'ALL') {
                $query->where('rejection_reason', 'LIKE', "%{$reason}%");
            }
        }

        $rejected = $query->orderBy('created_at', 'desc')->paginate(40)->withQueryString();
        $totalRejected = TradeSetup::where('status', 'REJECTED')->count();

        return view('trading_engine.avoid', compact('rejected', 'totalRejected'));
    }

    /**
     * Display Technical Pattern Matches across all 23 patterns.
     */
    public function patterns(Request $request)
    {
        $availablePatterns = PatternMatch::distinct()
            ->orderBy('pattern_name', 'asc')
            ->pluck('pattern_name')
            ->toArray();

        $query = PatternMatch::with('stock');

        if ($search = trim($request->get('search', ''))) {
            $query->whereHas('stock', function ($q) use ($search) {
                $q->where('symbol', 'LIKE', "%{$search}%");
            });
        }

        if ($pattern = $request->get('pattern')) {
            if ($pattern !== 'ALL') {
                $query->where('pattern_name', $pattern);
            }
        }

        if ($conf = $request->get('confidence')) {
            if ($conf !== 'ALL') {
                $query->where('confidence', '>=', (float)$conf);
            }
        }

        $patterns = $query->orderBy('trade_date', 'desc')
            ->orderBy('confidence', 'desc')
            ->paginate(40)
            ->withQueryString();

        $totalPatterns = PatternMatch::count();

        return view('trading_engine.patterns', compact('patterns', 'availablePatterns', 'totalPatterns'));
    }
}
