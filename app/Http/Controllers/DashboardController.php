<?php

namespace App\Http\Controllers;

use App\Models\Daily52wHigh;
use App\Models\DailySummary;
use App\Models\DailyVolumeGainer;
use App\Models\Stock;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the Momentum Dashboard.
     */
    public function index(Request $request)
    {
        // 1. Available dates
        $availableDates = Daily52wHigh::distinct()
            ->orderBy('trade_date', 'desc')
            ->pluck('trade_date')
            ->map(fn($d) => date_create($d)->format('Y-m-d'))
            ->toArray();

        $selectedDate = $request->get('date', $availableDates[0] ?? date('Y-m-d'));

        // 2. Daily Summary metrics
        $summary52wh = DailySummary::where('trade_date', $selectedDate)
            ->where('category', '52WH')
            ->first();

        $summaryVg = DailySummary::where('trade_date', $selectedDate)
            ->where('category', 'VG')
            ->first();

        // 3. Filtered 52W High records
        $query = Daily52wHigh::with('stock')
            ->where('trade_date', $selectedDate);

        // Filter: Search Symbol or Company
        if ($search = trim($request->get('search', ''))) {
            $query->whereHas('stock', function ($q) use ($search) {
                $q->where('symbol', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%");
            });
        }

        // Filter: Minimum Streak
        if ($minStreak = (int)$request->get('min_streak', 0)) {
            $query->where('consecutive_days', '>=', $minStreak);
        }

        // Filter: Status (new vs continuing)
        if ($status = $request->get('status')) {
            if ($status === 'new') {
                $query->where('is_new', true);
            } elseif ($status === 'continuing') {
                $query->where('is_new', false);
            }
        }

        // Sorting
        $sort = $request->get('sort', 'streak_desc');
        match ($sort) {
            'streak_desc' => $query->orderBy('consecutive_days', 'desc')->orderBy('p_change', 'desc'),
            'p_change_desc' => $query->orderBy('p_change', 'desc'),
            'p_change_asc' => $query->orderBy('p_change', 'asc'),
            'ltp_desc' => $query->orderBy('ltp', 'desc'),
            'symbol_asc' => $query->join('stocks', 'stocks.id', '=', 'daily_52w_highs.stock_id')->orderBy('stocks.symbol', 'asc')->select('daily_52w_highs.*'),
            default => $query->orderBy('consecutive_days', 'desc'),
        };

        $records = $query->paginate(40)->withQueryString();

        // 4. Quick Highlights for Widget Cards
        $topStreaks = Daily52wHigh::with('stock')
            ->where('trade_date', $selectedDate)
            ->orderBy('consecutive_days', 'desc')
            ->limit(5)
            ->get();

        $topGainers = Daily52wHigh::with('stock')
            ->where('trade_date', $selectedDate)
            ->orderBy('p_change', 'desc')
            ->limit(5)
            ->get();

        // Total count across all records for this date
        $total52whCount = Daily52wHigh::where('trade_date', $selectedDate)->count();
        $new52whCount = Daily52wHigh::where('trade_date', $selectedDate)->where('is_new', true)->count();
        $continuingCount = $total52whCount - $new52whCount;
        $totalVgCount = DailyVolumeGainer::where('trade_date', $selectedDate)->count();

        return view('dashboard.index', compact(
            'availableDates',
            'selectedDate',
            'summary52wh',
            'summaryVg',
            'records',
            'topStreaks',
            'topGainers',
            'total52whCount',
            'new52whCount',
            'continuingCount',
            'totalVgCount'
        ));
    }
}
