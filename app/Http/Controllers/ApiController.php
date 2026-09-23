<?php

namespace App\Http\Controllers;

use App\Services\DailyAnalysisService;
use App\Services\NseScraperService;
use App\Services\VolumeGainersService;
use App\Services\SystemSyncService;
use App\Services\TradingCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    public function __construct(
        protected DailyAnalysisService $dailyService,
        protected NseScraperService $scraper,
        protected VolumeGainersService $vgService,
        protected SystemSyncService $syncService,
        protected TradingCalendarService $calendar,
    ) {}

    /**
     * Get system-wide NSE synchronization status and market overview.
     */
    public function getSystemSyncStatus(): JsonResponse
    {
        $overview = $this->syncService->getSyncOverview();
        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

    /**
     * Trigger global centralized NSE sync (Admin / User Triggered).
     */
    public function triggerGlobalSync(Request $request): JsonResponse
    {
        $user = Auth::user();
        $triggeredBy = $user ? ($user->isAdmin() ? 'ADMIN' : 'USER_' . $user->id) : 'MANUAL_API';
        $forceDate = $request->input('date');

        $result = $this->syncService->executeGlobalSync($triggeredBy, $user?->id, $forceDate);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Trigger live daily download pipeline via AJAX.
     */
    public function triggerDownload(Request $request): JsonResponse
    {
        $date = $request->input('date', date('Y-m-d'));
        try {
            $res = $this->dailyService->runDailyAnalysis($date);
            return response()->json([
                'success' => true,
                'message' => "Pipeline executed successfully for {$date}.",
                'data' => $res,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Pipeline failed: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger live Volume Gainers snapshot via AJAX.
     */
    public function triggerVgSnapshot(Request $request): JsonResponse
    {
        $date = $request->input('date', date('Y-m-d'));
        try {
            $live = $this->scraper->fetchLiveVolumeGainers();
            $ingested = 0;
            if ($live['success'] && !empty($live['data'])) {
                $ingested = $this->vgService->ingestLiveSnapshot($live['data'], $date);
            }
            $mwCount = $this->vgService->computeMarketWideVolumeGainers($date);

            return response()->json([
                'success' => true,
                'message' => "Volume Gainers snapshot captured: {$ingested} live + {$mwCount} market-wide spurts.",
                'ingested_live' => $ingested,
                'market_wide_count' => $mwCount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Snapshot failed: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch complete lifetime appearance journey and timeline for a stock.
     */
    public function stockTimeline(string $symbol): JsonResponse
    {
        $symbol = strtoupper(trim($symbol));
        $stock = \App\Models\Stock::where('symbol', $symbol)->first();

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => "Stock symbol [{$symbol}] not found.",
            ], 404);
        }

        // Fetch all 52W High records
        $highs = \App\Models\Daily52wHigh::where('stock_id', $stock->id)
            ->orderBy('trade_date', 'asc')
            ->get();

        // Fetch all Volume Gainer records
        $vgs = \App\Models\DailyVolumeGainer::where('stock_id', $stock->id)
            ->orderBy('trade_date', 'asc')
            ->get();

        // Merge and organize by date
        $timelineByDate = [];
        $maxStreak = 0;
        $maxVolumeSpurt = 0;

        foreach ($highs as $h) {
            $d = substr((string)$h->trade_date, 0, 10);
            $streak = (int)$h->consecutive_days;
            if ($streak > $maxStreak) $maxStreak = $streak;

            $timelineByDate[$d] = [
                'date' => $d,
                'formatted_date' => date('d M Y', strtotime($d)),
                'ltp' => (float)$h->ltp,
                'p_change' => (float)$h->p_change,
                'is_52w' => true,
                'is_vg' => false,
                'streak' => $streak,
                'volume_surge' => null,
                'confidence' => null,
                'badge' => $streak >= 5 ? '🔥 Super Streak (5+ Days)' : ($streak >= 2 ? "★ Active Streak ({$streak}d)" : '🚀 Day 1 Breakout'),
                'badge_color' => $streak >= 5 ? 'amber' : ($streak >= 2 ? 'emerald' : 'blue'),
            ];
        }

        foreach ($vgs as $v) {
            $d = substr((string)$v->trade_date, 0, 10);
            $surge = (float)$v->week1_change;
            if ($surge > $maxVolumeSpurt) $maxVolumeSpurt = $surge;

            if (isset($timelineByDate[$d])) {
                $timelineByDate[$d]['is_vg'] = true;
                $timelineByDate[$d]['volume_surge'] = $surge;
                $timelineByDate[$d]['confidence'] = $v->confidence;
                $timelineByDate[$d]['badge'] = '⚡ 52W + Volume Gainer Confluence';
                $timelineByDate[$d]['badge_color'] = 'indigo';
            } else {
                $timelineByDate[$d] = [
                    'date' => $d,
                    'formatted_date' => date('d M Y', strtotime($d)),
                    'ltp' => (float)$v->ltp,
                    'p_change' => (float)$v->p_change,
                    'is_52w' => false,
                    'is_vg' => true,
                    'streak' => 1,
                    'volume_surge' => $surge,
                    'confidence' => $v->confidence,
                    'badge' => $surge >= 10 ? '💥 10x+ Mega Spurt' : ($surge >= 5 ? '🔥 5x-10x Surge' : '⚡ 2x-5x Surge'),
                    'badge_color' => $surge >= 10 ? 'rose' : ($surge >= 5 ? 'amber' : 'yellow'),
                ];
            }
        }

        // Sort timeline chronologically (latest first for timeline display)
        krsort($timelineByDate);
        $timelineList = array_values($timelineByDate);

        // Stats calculation
        $totalAppearances = count($timelineList);
        $total52w = count($highs);
        $totalVg = count($vgs);
        $confluenceCount = 0;
        foreach ($timelineList as $item) {
            if ($item['is_52w'] && $item['is_vg']) $confluenceCount++;
        }

        $chronological = array_reverse($timelineList);
        $firstEntry = reset($chronological);
        $latestEntry = end($chronological);

        $firstPrice = $firstEntry ? $firstEntry['ltp'] : 0;
        $latestPrice = $latestEntry ? $latestEntry['ltp'] : 0;
        $cumulativeGainPct = ($firstPrice > 0) ? round((($latestPrice - $firstPrice) / $firstPrice) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'stock' => [
                'symbol' => $stock->symbol,
                'company_name' => $stock->company_name ?: $stock->symbol,
                'series' => $stock->series ?? 'EQ',
                'sector' => $stock->sector ?: 'Diversified Equity',
                'market_cap_cr' => $stock->market_cap_cr,
                'current_ltp' => $latestPrice,
                'first_seen_date' => $firstEntry ? $firstEntry['formatted_date'] : '—',
                'latest_seen_date' => $latestEntry ? $latestEntry['formatted_date'] : '—',
                'total_appearances' => $totalAppearances,
                'total_52w' => $total52w,
                'total_vg' => $totalVg,
                'confluence_count' => $confluenceCount,
                'max_streak' => $maxStreak,
                'max_volume_spurt' => $maxVolumeSpurt,
                'cumulative_gain_pct' => $cumulativeGainPct,
            ],
            'timeline' => $timelineList,
        ]);
    }
}
