<?php

namespace App\Http\Controllers;

use App\Models\DataArchive;
use App\Models\Daily52wHigh;
use App\Models\DailyVolumeGainer;
use App\Services\DataArchiveService;
use App\Services\DailyAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ArchiveController extends Controller
{
    public function __construct(
        protected DataArchiveService $archiveService,
        protected DailyAnalysisService $dailyService
    ) {}

    /**
     * Display the Archives Manager dashboard.
     */
    public function index(Request $request)
    {
        // 1. Ensure all historical dates from Daily52wHigh exist in DataArchive
        $distinctDates = Daily52wHigh::selectRaw('trade_date, count(*) as count_52wh')
            ->groupBy('trade_date')
            ->orderBy('trade_date', 'desc')
            ->get();

        foreach ($distinctDates as $d) {
            $formattedDate = date_create($d->trade_date)->format('Y-m-d');
            $vgCount = DailyVolumeGainer::where('trade_date', $formattedDate)->count();

            DataArchive::firstOrCreate(
                ['trade_date' => $formattedDate],
                [
                    'source' => 'HISTORICAL_IMPORT',
                    'total_52wh' => $d->count_52wh,
                    'total_vg' => $vgCount,
                    'status' => 'COMPLETE',
                    'notes' => 'Imported snapshot from historical trading dataset',
                ]
            );
        }

        $query = DataArchive::orderBy('trade_date', 'desc');

        if ($status = $request->get('status')) {
            if ($status !== 'ALL') {
                $query->where('status', $status);
            }
        }

        $archives = $query->paginate(25)->withQueryString();

        $totalArchives = DataArchive::count();
        $completeArchives = DataArchive::where('status', 'COMPLETE')->count();
        $totalArchived52wh = DataArchive::sum('total_52wh');
        $totalArchivedVg = DataArchive::sum('total_vg');

        return view('archives.index', compact(
            'archives',
            'totalArchives',
            'completeArchives',
            'totalArchived52wh',
            'totalArchivedVg'
        ));
    }

    /**
     * View or download raw JSON/CSV file for an archive date.
     */
    public function viewFile(string $tradeDate, string $type)
    {
        $raw = $this->archiveService->getRawContent($tradeDate, $type);

        if (!$raw) {
            // Synthesize JSON from database records if historical file isn't physically on disk
            if ($type === '52wh') {
                $rows = Daily52wHigh::with('stock')
                    ->where('trade_date', $tradeDate)
                    ->get()
                    ->map(function ($r) {
                        return [
                            'symbol' => $r->stock->symbol ?? '',
                            'company' => $r->stock->company_name ?? '',
                            'ltp' => (float)$r->ltp,
                            'new52WHL' => (float)$r->high_price,
                            'prevClose' => (float)$r->prev_close,
                            'pChange' => (float)$r->p_change,
                            'consecutiveDays' => (int)$r->consecutive_days,
                            'appearances30d' => (int)$r->total_appearances_30d,
                        ];
                    });
                $raw = json_encode(['trade_date' => $tradeDate, 'count' => count($rows), 'data' => $rows], JSON_PRETTY_PRINT);
            } elseif ($type === 'vg') {
                $rows = DailyVolumeGainer::with('stock')
                    ->where('trade_date', $tradeDate)
                    ->get()
                    ->map(function ($r) {
                        return [
                            'symbol' => $r->stock->symbol ?? '',
                            'volume' => (int)$r->volume,
                            'week1Change' => (float)$r->week1_change,
                            'week2Change' => (float)$r->week2_change,
                            'ltp' => (float)$r->ltp,
                            'turnoverLakhs' => (float)$r->turnover_lakhs,
                            'confidence' => $r->confidence,
                        ];
                    });
                $raw = json_encode(['trade_date' => $tradeDate, 'count' => count($rows), 'data' => $rows], JSON_PRETTY_PRINT);
            } else {
                $raw = "No raw Bhavcopy file cached for {$tradeDate}.";
            }
        }

        $contentType = ($type === 'bhavcopy') ? 'text/csv' : 'application/json';
        return response($raw, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "inline; filename=\"{$type}_{$tradeDate}." . ($type === 'bhavcopy' ? 'csv' : 'json') . "\"",
        ]);
    }

    /**
     * Trigger re-analysis for an archived date.
     */
    public function reAnalyze(string $tradeDate)
    {
        $res = $this->dailyService->runDailyAnalysis($tradeDate);
        return redirect()->route('archives.index')
            ->with('success', "Re-analyzed {$tradeDate}: 52WH: {$res['52wh_count']}, VG: {$res['vg_count']}, Status: {$res['status']}");
    }
}
