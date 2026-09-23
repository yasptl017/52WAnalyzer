<?php

namespace App\Http\Controllers;

use App\Models\DailyVolumeGainer;
use App\Models\Daily52wHigh;
use App\Models\DailySummary;
use Illuminate\Http\Request;

class VolumeGainersController extends Controller
{
    /**
     * Display the Volume Gainers Hub.
     */
    public function index(Request $request)
    {
        $availableDates = DailyVolumeGainer::distinct()
            ->orderBy('trade_date', 'desc')
            ->pluck('trade_date')
            ->map(fn($d) => date_create($d)->format('Y-m-d'))
            ->toArray();

        $selectedDate = $request->get('date', $availableDates[0] ?? date('Y-m-d'));

        $query = DailyVolumeGainer::with('stock')
            ->where('trade_date', $selectedDate);

        // Filter: Search Symbol or Company
        if ($search = trim($request->get('search', ''))) {
            $query->whereHas('stock', function ($q) use ($search) {
                $q->where('symbol', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%");
            });
        }

        // Filter: Confidence
        if ($conf = $request->get('confidence')) {
            if ($conf !== 'ALL') {
                $query->where('confidence', $conf);
            }
        }

        // Filter: Min 1W Volume Multiplier
        if ($minMult = (float)$request->get('min_multiplier', 0)) {
            $query->where('week1_change', '>=', $minMult);
        }

        // Filter: Min Turnover in Lakhs
        if ($minTurnover = (float)$request->get('min_turnover', 0)) {
            $query->where('turnover_lakhs', '>=', $minTurnover);
        }

        // Sorting
        $sort = $request->get('sort', 'spurt_desc');
        match ($sort) {
            'spurt_desc' => $query->orderBy('week1_change', 'desc'),
            'spurt2_desc' => $query->orderBy('week2_change', 'desc'),
            'turnover_desc' => $query->orderBy('turnover_lakhs', 'desc'),
            'pchange_desc' => $query->orderBy('p_change', 'desc'),
            'volume_desc' => $query->orderBy('volume', 'desc'),
            default => $query->orderBy('week1_change', 'desc'),
        };

        $gainers = $query->paginate(40)->withQueryString();

        // Summary Aggregates for Selected Date
        $totalGainers = DailyVolumeGainer::where('trade_date', $selectedDate)->count();
        $persistentCount = DailyVolumeGainer::where('trade_date', $selectedDate)->where('confidence', '★★★')->count();
        $repeatingCount = DailyVolumeGainer::where('trade_date', $selectedDate)->where('confidence', '★★')->count();
        $topGainer = DailyVolumeGainer::with('stock')
            ->where('trade_date', $selectedDate)
            ->orderBy('week1_change', 'desc')
            ->first();
        $totalTurnoverLakhs = DailyVolumeGainer::where('trade_date', $selectedDate)->sum('turnover_lakhs');

        // VG Daily Summary
        $summaryVg = DailySummary::where('trade_date', $selectedDate)
            ->where('category', 'VG')
            ->first();

        return view('volume_gainers.index', compact(
            'availableDates',
            'selectedDate',
            'gainers',
            'totalGainers',
            'persistentCount',
            'repeatingCount',
            'topGainer',
            'totalTurnoverLakhs',
            'summaryVg'
        ));
    }
}
