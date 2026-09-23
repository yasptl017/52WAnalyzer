<?php

namespace App\Services;

use Carbon\Carbon;

class TradingCalendarService
{
    /**
     * Official Indian Stock Market (NSE/BSE) Trading Holidays.
     * Format: 'YYYY-MM-DD' => 'Holiday Name'
     */
    protected array $holidays = [
        // 2025 Holidays
        '2025-01-26' => 'Republic Day',
        '2025-02-26' => 'Mahashivratri',
        '2025-03-14' => 'Holi',
        '2025-03-31' => 'Id-Ul-Fitr (Ramzan Id)',
        '2025-04-10' => 'Shri Mahavir Jayanti',
        '2025-04-14' => 'Dr. Baba Saheb Ambedkar Jayanti',
        '2025-04-18' => 'Good Friday',
        '2025-05-01' => 'Maharashtra Day',
        '2025-06-07' => 'Bakri Id / Eid-ul-Adha',
        '2025-08-15' => 'Independence Day',
        '2025-08-27' => 'Ganesh Chaturthi',
        '2025-10-02' => 'Mahatma Gandhi Jayanti',
        '2025-10-21' => 'Dussehra',
        '2025-10-22' => 'Diwali-Balipratipada',
        '2025-11-05' => 'Gurunanak Jayanti',
        '2025-12-25' => 'Christmas',

        // 2026 Holidays
        '2026-01-26' => 'Republic Day',
        '2026-02-16' => 'Mahashivratri',
        '2026-03-03' => 'Holi',
        '2026-03-20' => 'Id-Ul-Fitr (Ramzan Id)',
        '2026-03-31' => 'Mahavir Jayanti',
        '2026-04-03' => 'Good Friday',
        '2026-04-14' => 'Dr. Ambedkar Jayanti',
        '2026-05-01' => 'Maharashtra Day',
        '2026-05-27' => 'Bakri Id',
        '2026-06-25' => 'Muharram',
        '2026-08-15' => 'Independence Day',
        '2026-09-15' => 'Ganesh Chaturthi',
        '2026-10-02' => 'Mahatma Gandhi Jayanti',
        '2026-10-20' => 'Dussehra',
        '2026-11-09' => 'Diwali Balipratipada',
        '2026-11-24' => 'Gurunanak Jayanti',
        '2026-12-25' => 'Christmas',

        // 2027 Holidays
        '2027-01-26' => 'Republic Day',
        '2027-03-08' => 'Mahashivratri',
        '2027-03-22' => 'Holi',
        '2027-03-26' => 'Good Friday',
        '2027-04-14' => 'Dr. Ambedkar Jayanti',
        '2027-04-19' => 'Mahavir Jayanti',
        '2027-05-01' => 'Maharashtra Day',
        '2027-08-15' => 'Independence Day',
        '2027-10-02' => 'Mahatma Gandhi Jayanti',
        '2027-10-11' => 'Dussehra',
        '2027-10-30' => 'Diwali',
        '2027-11-13' => 'Gurunanak Jayanti',
        '2027-12-25' => 'Christmas',
    ];

    /**
     * Check if a given date is a valid NSE trading day.
     */
    public function isTradingDay(?string $date = null): bool
    {
        $carbon = $date ? Carbon::parse($date) : Carbon::now('Asia/Kolkata');
        $ymd = $carbon->format('Y-m-d');

        // Check weekend (Saturday = 6, Sunday = 0)
        if ($carbon->isWeekend()) {
            return false;
        }

        // Check official holiday
        if (isset($this->holidays[$ymd])) {
            return false;
        }

        return true;
    }

    /**
     * Get the reason why a date is NOT a trading day.
     */
    public function getNonTradingReason(?string $date = null): ?string
    {
        $carbon = $date ? Carbon::parse($date) : Carbon::now('Asia/Kolkata');
        $ymd = $carbon->format('Y-m-d');

        if ($carbon->isSaturday()) {
            return 'Saturday (Weekend Market Closed)';
        }

        if ($carbon->isSunday()) {
            return 'Sunday (Weekend Market Closed)';
        }

        if (isset($this->holidays[$ymd])) {
            return "Trading Holiday: " . $this->holidays[$ymd];
        }

        return null;
    }

    /**
     * Get the most recent valid past trading day (stepping backwards if today is weekend/holiday).
     */
    public function getLatestTradingDay(?string $fromDate = null): string
    {
        $carbon = $fromDate ? Carbon::parse($fromDate) : Carbon::now('Asia/Kolkata');

        // If today is a trading day and market has not yet opened (before 09:15) or no data yet,
        // callers can still check, but isTradingDay($carbon) determines day validity.
        while (!$this->isTradingDay($carbon->format('Y-m-d'))) {
            $carbon->subDay();
        }

        return $carbon->format('Y-m-d');
    }

    /**
     * Get the next upcoming valid trading day.
     */
    public function getNextTradingDay(?string $fromDate = null): string
    {
        $carbon = $fromDate ? Carbon::parse($fromDate) : Carbon::now('Asia/Kolkata');
        $carbon->addDay();

        while (!$this->isTradingDay($carbon->format('Y-m-d'))) {
            $carbon->addDay();
        }

        return $carbon->format('Y-m-d');
    }

    /**
     * Get comprehensive real-time market status for Indian Markets (IST).
     */
    public function getMarketStatus(?string $date = null): array
    {
        $now = Carbon::now('Asia/Kolkata');
        $checkDate = $date ? Carbon::parse($date) : $now;
        $ymd = $checkDate->format('Y-m-d');

        $isTradingDay = $this->isTradingDay($ymd);
        $reason = $this->getNonTradingReason($ymd);

        if (!$isTradingDay) {
            return [
                'is_open' => false,
                'is_trading_day' => false,
                'status_text' => 'Market Closed',
                'reason' => $reason,
                'badge_color' => 'rose',
                'latest_trading_day' => $this->getLatestTradingDay($ymd),
                'next_trading_day' => $this->getNextTradingDay($ymd),
            ];
        }

        // It is a trading day; check trading session timing (09:15 to 15:30 IST)
        $timeStr = $now->format('H:i');
        $isMarketHours = ($timeStr >= '09:15' && $timeStr <= '15:30');
        $isPostMarket = ($timeStr > '15:30');
        $isPreMarket = ($timeStr >= '09:00' && $timeStr < '09:15');

        if ($isMarketHours) {
            return [
                'is_open' => true,
                'is_trading_day' => true,
                'status_text' => 'Market Live (09:15 - 15:30)',
                'reason' => 'Active Live Trading Session',
                'badge_color' => 'emerald',
                'latest_trading_day' => $ymd,
                'next_trading_day' => $this->getNextTradingDay($ymd),
            ];
        }

        if ($isPreMarket) {
            return [
                'is_open' => true,
                'is_trading_day' => true,
                'status_text' => 'Pre-Market (09:00 - 09:15)',
                'reason' => 'Pre-Open Price Discovery',
                'badge_color' => 'amber',
                'latest_trading_day' => $ymd,
                'next_trading_day' => $this->getNextTradingDay($ymd),
            ];
        }

        if ($isPostMarket) {
            return [
                'is_open' => false,
                'is_trading_day' => true,
                'status_text' => 'Market Closed (Post-Market)',
                'reason' => 'EOD Session Finalized & Bhavcopy Available',
                'badge_color' => 'slate',
                'latest_trading_day' => $ymd,
                'next_trading_day' => $this->getNextTradingDay($ymd),
            ];
        }

        return [
            'is_open' => false,
            'is_trading_day' => true,
            'status_text' => 'Pre-Market (Opening at 09:15)',
            'reason' => 'Market opens at 09:15 IST',
            'badge_color' => 'slate',
            'latest_trading_day' => $this->getLatestTradingDay($checkDate->copy()->subDay()->format('Y-m-d')),
            'next_trading_day' => $ymd,
        ];
    }
}
