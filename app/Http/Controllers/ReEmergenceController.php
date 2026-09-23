<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Daily52wHigh;
use App\Models\DailyVolumeGainer;
use App\Models\Stock;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReEmergenceController extends Controller
{
    /**
     * Display the Re-Emergence Breakouts dashboard with 3 separate tab facilities (>5, >10, >15 sessions).
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $tab = $request->get('tab', '5'); // '5', '10', '15'

        // Starter tier only includes 5-Session Re-Emergence. 10 & 15 require Pro or above.
        if ($user && !$user->isPro() && in_array($tab, ['10', '15'])) {
            return redirect()->route('pricing.index', ['required_tier' => 'PRO'])
                ->with('error', '⚡ Pro Feature Locked: 10+ and 15+ Session Re-Emergence filters require the PRO tier or higher.');
        }

        $minGap = (int) $tab;
        if (!in_array($minGap, [5, 10, 15])) {
            $minGap = 5;
            $tab = '5';
        }

        $sourceFilter = $request->get('source', 'ALL'); // 'ALL', '52WH', 'VG'
        $selectedDate = $request->get('date', 'LATEST'); // 'LATEST', 'ALL', or specific 'YYYY-MM-DD'
        $search = trim($request->get('search', ''));

        // 1. Fetch all distinct trading dates in chronological order
        $allDates52w = Daily52wHigh::distinct()->orderBy('trade_date', 'asc')->pluck('trade_date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();
        $allDatesVg = DailyVolumeGainer::distinct()->orderBy('trade_date', 'asc')->pluck('trade_date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();
        
        $allDates = array_values(array_unique(array_merge($allDates52w, $allDatesVg)));
        sort($allDates);
        $dateIndexMap = array_flip($allDates);

        $latestDate = count($allDates) > 0 ? end($allDates) : null;
        $activeDate = $selectedDate === 'LATEST' ? $latestDate : $selectedDate;

        // 2. Compute Re-Emergences across both datasets
        $emergences52w = $this->compute52wReEmergences($minGap, $dateIndexMap);
        $emergencesVg = $this->computeVgReEmergences($minGap, $dateIndexMap);

        // Merge and tag items
        $allEmergences = [];

        if ($sourceFilter === 'ALL' || $sourceFilter === '52WH') {
            foreach ($emergences52w as $item) {
                $allEmergences[] = $item;
            }
        }

        if ($sourceFilter === 'ALL' || $sourceFilter === 'VG') {
            foreach ($emergencesVg as $item) {
                $allEmergences[] = $item;
            }
        }

        // Filter by activeDate if not 'ALL'
        if ($activeDate !== 'ALL' && $activeDate !== null) {
            $allEmergences = array_values(array_filter($allEmergences, function ($item) use ($activeDate) {
                return $item['re_entry_date'] === $activeDate;
            }));
        }

        // Filter by search query if present
        if ($search !== '') {
            $searchUpper = strtoupper($search);
            $allEmergences = array_values(array_filter($allEmergences, function ($item) use ($searchUpper) {
                return str_contains(strtoupper($item['symbol']), $searchUpper) ||
                       str_contains(strtoupper($item['company_name']), $searchUpper) ||
                       str_contains(strtoupper($item['sector']), $searchUpper);
            }));
        }

        // Sort by gap_sessions desc, then p_change desc
        usort($allEmergences, function ($a, $b) {
            if ($a['gap_sessions'] !== $b['gap_sessions']) {
                return $b['gap_sessions'] <=> $a['gap_sessions'];
            }
            return $b['p_change'] <=> $a['p_change'];
        });

        // Summary counts for tabs (computed for the activeDate)
        $tabCounts = [
            '5' => $this->countEmergencesForDate(5, $activeDate, $emergences52w, $emergencesVg, $sourceFilter),
            '10' => $this->countEmergencesForDate(10, $activeDate, $emergences52w, $emergencesVg, $sourceFilter),
            '15' => $this->countEmergencesForDate(15, $activeDate, $emergences52w, $emergencesVg, $sourceFilter),
        ];

        // Stats for current tab
        $count52w = count(array_filter($allEmergences, fn($i) => $i['source'] === '52WH'));
        $countVg = count(array_filter($allEmergences, fn($i) => $i['source'] === 'VG'));
        $maxGap = count($allEmergences) > 0 ? max(array_column($allEmergences, 'gap_sessions')) : 0;

        return view('re_emergence.index', compact(
            'tab',
            'minGap',
            'sourceFilter',
            'selectedDate',
            'activeDate',
            'latestDate',
            'allDates',
            'allEmergences',
            'tabCounts',
            'count52w',
            'countVg',
            'maxGap',
            'search'
        ));
    }

    /**
     * Export re-emergence list to CSV.
     */
    public function exportCsv(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->isPro()) {
            return redirect()->route('pricing.index', ['required_tier' => 'PRO'])
                ->with('error', '⚡ Pro Feature Locked: Re-Emergence CSV export requires the PRO tier or higher.');
        }

        $tab = $request->get('tab', '5');
        $minGap = (int) $tab;
        $sourceFilter = $request->get('source', 'ALL');
        $selectedDate = $request->get('date', 'LATEST');

        $allDates52w = Daily52wHigh::distinct()->orderBy('trade_date', 'asc')->pluck('trade_date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();
        $allDatesVg = DailyVolumeGainer::distinct()->orderBy('trade_date', 'asc')->pluck('trade_date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();
        $allDates = array_values(array_unique(array_merge($allDates52w, $allDatesVg)));
        sort($allDates);
        $dateIndexMap = array_flip($allDates);

        $latestDate = count($allDates) > 0 ? end($allDates) : null;
        $activeDate = $selectedDate === 'LATEST' ? $latestDate : $selectedDate;

        $emergences52w = $this->compute52wReEmergences($minGap, $dateIndexMap);
        $emergencesVg = $this->computeVgReEmergences($minGap, $dateIndexMap);

        $list = [];
        if ($sourceFilter === 'ALL' || $sourceFilter === '52WH') {
            foreach ($emergences52w as $item) $list[] = $item;
        }
        if ($sourceFilter === 'ALL' || $sourceFilter === 'VG') {
            foreach ($emergencesVg as $item) $list[] = $item;
        }

        if ($activeDate !== 'ALL' && $activeDate !== null) {
            $list = array_values(array_filter($list, fn($i) => $i['re_entry_date'] === $activeDate));
        }

        usort($list, fn($a, $b) => $b['gap_sessions'] <=> $a['gap_sessions']);

        $filename = "52WAnalyzer_ReEmergence_GT{$minGap}Sessions_" . now()->format('Y-m-d') . ".csv";

        return new StreamedResponse(function () use ($list, $minGap) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Symbol',
                'Company Name',
                'Sector',
                'List Source',
                'Re-Entry Date',
                'Previous Appearance Date',
                'Gap (Trading Sessions)',
                'Last Traded Price (INR)',
                'Day Change (%)',
                'Metric Details'
            ]);

            foreach ($list as $row) {
                fputcsv($handle, [
                    $row['symbol'],
                    $row['company_name'],
                    $row['sector'],
                    $row['source'],
                    $row['re_entry_date'],
                    $row['prev_date'],
                    $row['gap_sessions'],
                    $row['ltp'],
                    $row['p_change'],
                    $row['metric_detail']
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Compute 52-Week High re-emergences.
     */
    private function compute52wReEmergences(int $minGap, array $dateIndexMap): array
    {
        $records = Daily52wHigh::with('stock')
            ->orderBy('trade_date', 'asc')
            ->get()
            ->groupBy('stock_id');

        $results = [];

        foreach ($records as $stockId => $stockRecords) {
            $prevDate = null;
            $prevIdx = null;

            foreach ($stockRecords as $r) {
                if (!$r->stock) continue;

                $curDate = $r->trade_date->format('Y-m-d');
                $curIdx = $dateIndexMap[$curDate] ?? null;

                if ($prevIdx !== null && $curIdx !== null) {
                    $gap = $curIdx - $prevIdx - 1;

                    if ($gap >= $minGap) {
                        $results[] = [
                            'symbol' => $r->stock->symbol,
                            'company_name' => $r->stock->company_name,
                            'sector' => $r->stock->sector ?? 'General',
                            'source' => '52WH',
                            're_entry_date' => $curDate,
                            'formatted_date' => Carbon::parse($curDate)->format('d-M-Y'),
                            'prev_date' => $prevDate,
                            'formatted_prev_date' => Carbon::parse($prevDate)->format('d-M-Y'),
                            'gap_sessions' => $gap,
                            'ltp' => (float) $r->ltp,
                            'p_change' => (float) $r->p_change,
                            'metric_detail' => ($r->consecutive_days > 1 ? "{$r->consecutive_days}d Streak" : "Fresh Breakout"),
                            'is_new' => (bool) $r->is_new,
                        ];
                    }
                } elseif ($prevIdx === null && !empty($r->prev_52w_date)) {
                    // Check historical prev_52w_date before dataset inception
                    $prev52Date = Carbon::parse($r->prev_52w_date);
                    $curDateObj = Carbon::parse($curDate);
                    
                    if ($prev52Date->lt($curDateObj)) {
                        $diffDays = $prev52Date->diffInDays($curDateObj);
                        $estSessions = (int) round($diffDays * (5 / 7));

                        if ($estSessions >= $minGap) {
                            $results[] = [
                                'symbol' => $r->stock->symbol,
                                'company_name' => $r->stock->company_name,
                                'sector' => $r->stock->sector ?? 'General',
                                'source' => '52WH',
                                're_entry_date' => $curDate,
                                'formatted_date' => $curDateObj->format('d-M-Y'),
                                'prev_date' => $prev52Date->format('Y-m-d'),
                                'formatted_prev_date' => $prev52Date->format('d-M-Y'),
                                'gap_sessions' => $estSessions,
                                'ltp' => (float) $r->ltp,
                                'p_change' => (float) $r->p_change,
                                'metric_detail' => "Multi-Month Breakout",
                                'is_new' => true,
                            ];
                        }
                    }
                }

                $prevDate = $curDate;
                $prevIdx = $curIdx;
            }
        }

        return $results;
    }

    /**
     * Compute Volume Gainer re-emergences.
     */
    private function computeVgReEmergences(int $minGap, array $dateIndexMap): array
    {
        $records = DailyVolumeGainer::with('stock')
            ->orderBy('trade_date', 'asc')
            ->get()
            ->groupBy('stock_id');

        $results = [];

        foreach ($records as $stockId => $stockRecords) {
            $prevDate = null;
            $prevIdx = null;

            foreach ($stockRecords as $r) {
                if (!$r->stock) continue;

                $curDate = $r->trade_date->format('Y-m-d');
                $curIdx = $dateIndexMap[$curDate] ?? null;

                if ($prevIdx !== null && $curIdx !== null) {
                    $gap = $curIdx - $prevIdx - 1;

                    if ($gap >= $minGap) {
                        $w1 = (float) $r->week1_change;
                        $w2 = (float) $r->week2_change;
                        $maxChg = max($w1, $w2);
                        $mult = round($maxChg / 100 + 1, 1);
                        if ($mult < 1) $mult = 1.0;

                        $results[] = [
                            'symbol' => $r->stock->symbol,
                            'company_name' => $r->stock->company_name,
                            'sector' => $r->stock->sector ?? 'General',
                            'source' => 'VG',
                            're_entry_date' => $curDate,
                            'formatted_date' => Carbon::parse($curDate)->format('d-M-Y'),
                            'prev_date' => $prevDate,
                            'formatted_prev_date' => Carbon::parse($prevDate)->format('d-M-Y'),
                            'gap_sessions' => $gap,
                            'ltp' => (float) $r->ltp,
                            'p_change' => (float) $r->p_change,
                            'metric_detail' => "{$mult}x Volume Surge",
                            'multiplier' => $mult,
                        ];
                    }
                }

                $prevDate = $curDate;
                $prevIdx = $curIdx;
            }
        }

        return $results;
    }

    /**
     * Helper to count emergences for a specific minimum gap and date.
     */
    private function countEmergencesForDate(int $gap, ?string $date, array $list52w, array $listVg, string $source): int
    {
        $filtered52w = array_filter($list52w, fn($i) => $i['gap_sessions'] >= $gap && ($date === 'ALL' || $i['re_entry_date'] === $date));
        $filteredVg = array_filter($listVg, fn($i) => $i['gap_sessions'] >= $gap && ($date === 'ALL' || $i['re_entry_date'] === $date));

        if ($source === '52WH') return count($filtered52w);
        if ($source === 'VG') return count($filteredVg);
        return count($filtered52w) + count($filteredVg);
    }
}
