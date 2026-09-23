<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\DailyVolumeGainer;
use App\Models\OhlcvBar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VolumeGainersService
{
    /**
     * Process live Volume Gainers snapshot from NSE.
     * Overcomes the single snapshot limitation by accumulating and tracking session presence.
     */
    public function ingestLiveSnapshot(array $rawRows, string $tradeDate): int
    {
        $count = 0;

        foreach ($rawRows as $row) {
            $symbol = strtoupper(trim($row['symbol'] ?? ''));
            if (empty($symbol)) continue;

            $company = trim($row['companyName'] ?? $row['company'] ?? '');
            $stock = Stock::firstOrCreate(
                ['symbol' => $symbol],
                ['company_name' => $company ?: null, 'series' => 'EQ', 'asset_class' => 'EQUITY']
            );

            $existing = DailyVolumeGainer::where('stock_id', $stock->id)
                ->where('trade_date', $tradeDate)
                ->first();

            $sessions = $existing ? ($existing->sessions_seen + 1) : 1;
            $confidence = match (true) {
                $sessions >= 3 => '★★★',
                $sessions == 2 => '★★',
                default => '★',
            };

            $vol = isset($row['volume']) ? (int)$row['volume'] : null;
            $w1Avg = isset($row['week1AvgVolume']) ? (int)$row['week1AvgVolume'] : null;
            $w1Chg = isset($row['week1volChange']) ? (float)$row['week1volChange'] : null;
            $w2Avg = isset($row['week2AvgVolume']) ? (int)$row['week2AvgVolume'] : null;
            $w2Chg = isset($row['week2volChange']) ? (float)$row['week2volChange'] : null;
            $ltp = isset($row['ltp']) ? (float)$row['ltp'] : null;
            $pChg = isset($row['pChange']) ? (float)$row['pChange'] : null;
            $turnover = isset($row['turnover']) ? (float)$row['turnover'] : null;

            DailyVolumeGainer::updateOrCreate(
                [
                    'stock_id' => $stock->id,
                    'trade_date' => $tradeDate,
                ],
                [
                    'volume' => $vol ?: $existing?->volume,
                    'week1_avg_volume' => $w1Avg ?: $existing?->week1_avg_volume,
                    'week1_change' => $w1Chg ?: $existing?->week1_change,
                    'week2_avg_volume' => $w2Avg ?: $existing?->week2_avg_volume,
                    'week2_change' => $w2Chg ?: $existing?->week2_change,
                    'ltp' => $ltp ?: $existing?->ltp,
                    'p_change' => $pChg ?: $existing?->p_change,
                    'turnover_lakhs' => $turnover ?: $existing?->turnover_lakhs,
                    'sessions_seen' => $sessions,
                    'confidence' => $confidence,
                ]
            );

            $count++;
        }

        return $count;
    }

    /**
     * Compute market-wide Volume Gainers across ALL equities in the database
     * using rolling historical averages from ohlcv_bars.
     * This breaks the 25-row ceiling and discovers all genuine market volume breakouts!
     */
    public function computeMarketWideVolumeGainers(
        string $tradeDate,
        float $minMultiplier = 1.50,
        int $minVolume = 20000
    ): int {
        // Query today's bars and join with rolling 5-day (1W) and 10-day (2W) average volumes
        $sql = "
            SELECT 
                b.stock_id,
                b.close AS ltp,
                b.volume,
                COALESCE(w1.avg_vol_5d, 0) AS week1_avg_volume,
                CASE 
                    WHEN COALESCE(w1.avg_vol_5d, 0) > 0 THEN ROUND(b.volume / w1.avg_vol_5d, 2)
                    ELSE 0 
                END AS week1_change,
                COALESCE(w2.avg_vol_10d, 0) AS week2_avg_volume,
                CASE 
                    WHEN COALESCE(w2.avg_vol_10d, 0) > 0 THEN ROUND(b.volume / w2.avg_vol_10d, 2)
                    ELSE 0 
                END AS week2_change,
                ROUND((b.volume * b.close) / 100000, 2) AS turnover_lakhs
            FROM ohlcv_bars b
            -- 5-day rolling average volume prior to today
            LEFT JOIN (
                SELECT 
                    stock_id,
                    ROUND(AVG(volume)) AS avg_vol_5d
                FROM (
                    SELECT 
                        stock_id, 
                        volume,
                        ROW_NUMBER() OVER (PARTITION BY stock_id ORDER BY bar_date DESC) as rn
                    FROM ohlcv_bars
                    WHERE bar_date < ? AND volume > 0
                ) sub
                WHERE rn <= 5
                GROUP BY stock_id
            ) w1 ON w1.stock_id = b.stock_id
            -- 10-day rolling average volume prior to today
            LEFT JOIN (
                SELECT 
                    stock_id,
                    ROUND(AVG(volume)) AS avg_vol_10d
                FROM (
                    SELECT 
                        stock_id, 
                        volume,
                        ROW_NUMBER() OVER (PARTITION BY stock_id ORDER BY bar_date DESC) as rn
                    FROM ohlcv_bars
                    WHERE bar_date < ? AND volume > 0
                ) sub
                WHERE rn <= 10
                GROUP BY stock_id
            ) w2 ON w2.stock_id = b.stock_id
            WHERE b.bar_date = ?
              AND b.volume >= ?
              AND (w1.avg_vol_5d > 0 AND (b.volume / w1.avg_vol_5d) >= ?)
            ORDER BY week1_change DESC
        ";

        $rows = DB::select($sql, [
            $tradeDate,
            $tradeDate,
            $tradeDate,
            $minVolume,
            $minMultiplier,
        ]);

        $count = 0;
        foreach ($rows as $row) {
            $existing = DailyVolumeGainer::where('stock_id', $row->stock_id)
                ->where('trade_date', $tradeDate)
                ->first();

            DailyVolumeGainer::updateOrCreate(
                [
                    'stock_id' => $row->stock_id,
                    'trade_date' => $tradeDate,
                ],
                [
                    'volume' => $row->volume,
                    'week1_avg_volume' => $row->week1_avg_volume,
                    'week1_change' => $row->week1_change,
                    'week2_avg_volume' => $row->week2_avg_volume,
                    'week2_change' => $row->week2_change,
                    'ltp' => $row->ltp,
                    'turnover_lakhs' => $row->turnover_lakhs,
                    'sessions_seen' => $existing ? $existing->sessions_seen : 1,
                    'confidence' => $existing ? $existing->confidence : '★',
                ]
            );

            $count++;
        }

        Log::info("Market-wide volume gainer computation for {$tradeDate}: identified {$count} stocks with >= {$minMultiplier}x volume.");
        return $count;
    }
}
