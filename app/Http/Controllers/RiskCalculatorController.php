<?php

namespace App\Http\Controllers;

use App\Models\TradeSetup;
use Illuminate\Http\Request;

class RiskCalculatorController extends Controller
{
    /**
     * Display the Live Interactive Risk Calculator.
     */
    public function index(Request $request)
    {
        $symbol = $request->get('symbol', '');
        $entry = (float)$request->get('entry', 500.0);
        $sl = (float)$request->get('sl', 480.0);
        $capital = (float)$request->get('capital', 500000.0);
        $riskPct = (float)$request->get('risk_pct', 1.0);

        // Fetch top 6 trade candidates to offer quick 1-click prefill buttons
        $topCandidates = TradeSetup::with('stock')
            ->where('status', 'CANDIDATE')
            ->orderBy('rr_ratio', 'desc')
            ->limit(6)
            ->get();

        return view('risk_calculator.index', compact(
            'symbol',
            'entry',
            'sl',
            'capital',
            'riskPct',
            'topCandidates'
        ));
    }
}
