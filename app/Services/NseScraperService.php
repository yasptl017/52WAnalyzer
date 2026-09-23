<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class NseScraperService
{
    protected Client $client;
    protected CookieJar $cookieJar;
    protected bool $isWarmedUp = false;

    protected array $baseHeaders = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept-Language' => 'en-US,en;q=0.9',
        'Accept-Encoding' => 'gzip, deflate',
        'Connection' => 'keep-alive',
    ];

    public function __construct()
    {
        $this->cookieJar = new CookieJar();
        $this->client = new Client([
            'cookies' => $this->cookieJar,
            'timeout' => 30,
            'connect_timeout' => 15,
            'verify' => false, // NSE SSL certs sometimes have chain quirks on Windows
        ]);
    }

    /**
     * Warm up session cookies by hitting base and market-data pages.
     */
    public function warmUp(string $pageUrl = 'https://www.nseindia.com/market-data/52-week-high-equity-market'): bool
    {
        $warmUpHeaders = array_merge($this->baseHeaders, [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
        ]);

        // 1. Visit NSE Base
        try {
            $this->client->get('https://www.nseindia.com', [
                'headers' => $warmUpHeaders,
                'timeout' => 15,
            ]);
        } catch (\Exception $e) {
            Log::info("NSE base warm-up note (non-fatal): " . $e->getMessage());
        }

        // 2. Visit target market-data page
        for ($retry = 0; $retry < 3; $retry++) {
            try {
                $response = $this->client->get($pageUrl, [
                    'headers' => $warmUpHeaders,
                    'timeout' => 20,
                ]);
                if ($response->getStatusCode() === 200) {
                    $this->isWarmedUp = true;
                    usleep(500000); // 0.5s pause to settle Akamai cookies
                    return true;
                }
            } catch (\Exception $e) {
                Log::warning("NSE warm-up attempt {$retry} failed for {$pageUrl}: " . $e->getMessage());
                sleep(1);
            }
        }

        return false;
    }

    /**
     * Fetch daily 52-Week High list.
     * Returns ['success' => bool, 'data' => array, 'raw' => string, 'error' => ?string]
     */
    public function fetch52WeekHighs(): array
    {
        $this->warmUp('https://www.nseindia.com/market-data/52-week-high-equity-market');

        $candidateEndpoints = [
            'https://www.nseindia.com/api/live-analysis-52week-high',
            'https://www.nseindia.com/api/live-analysis-data-52weekhighstock',
            'https://www.nseindia.com/api/52WeekHighStock',
            'https://www.nseindia.com/api/live-analysis-new-52-week-high',
            'https://www.nseindia.com/json/liveAnalysis/52Weekhigh.json',
        ];

        $headers = array_merge($this->baseHeaders, [
            'Accept' => 'application/json, text/plain, */*',
            'Referer' => 'https://www.nseindia.com/market-data/52-week-high-equity-market',
            'X-Requested-With' => 'XMLHttpRequest',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
        ]);

        foreach ($candidateEndpoints as $url) {
            for ($attempt = 0; $attempt < 2; $attempt++) {
                try {
                    $response = $this->client->get($url, ['headers' => $headers]);
                    $body = (string)$response->getBody();

                    if ($response->getStatusCode() === 200 && !empty($body)) {
                        $json = json_decode($body, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $rows = $json['data'] ?? [];
                            if (!empty($rows)) {
                                return [
                                    'success' => true,
                                    'data' => $rows,
                                    'raw' => $body,
                                    'endpoint' => $url,
                                    'error' => null,
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("NSE 52WH request failed on {$url}: " . $e->getMessage());
                    usleep(500000);
                }
            }
        }

        return [
            'success' => false,
            'data' => [],
            'raw' => '',
            'error' => 'All NSE 52-Week High endpoints failed or returned empty data.',
        ];
    }

    /**
     * Fetch the live Volume Gainers snapshot from NSE.
     */
    public function fetchLiveVolumeGainers(): array
    {
        $this->warmUp('https://www.nseindia.com/market-data/volume-gainers-spurts');

        $url = 'https://www.nseindia.com/api/live-analysis-volume-gainers';
        $headers = array_merge($this->baseHeaders, [
            'Accept' => 'application/json, text/plain, */*',
            'Referer' => 'https://www.nseindia.com/market-data/volume-gainers-spurts',
            'X-Requested-With' => 'XMLHttpRequest',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
        ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $response = $this->client->get($url, ['headers' => $headers]);
                $body = (string)$response->getBody();

                if ($response->getStatusCode() === 200 && !empty($body)) {
                    $json = json_decode($body, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $rows = $json['data'] ?? [];
                        return [
                            'success' => true,
                            'data' => $rows,
                            'raw' => $body,
                            'error' => null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("NSE Volume Gainers live request failed: " . $e->getMessage());
                sleep(1);
            }
        }

        return [
            'success' => false,
            'data' => [],
            'raw' => '',
            'error' => 'NSE Volume Gainers live endpoint failed.',
        ];
    }

    /**
     * Fetch NSE Daily Security Bhavdata (sec_bhavdata_full_DDMMYYYY.csv).
     */
    public function fetchBhavcopy(?string $date = null): array
    {
        $targetDate = $date ? date_create($date) : date_create();
        $formattedDate = $targetDate->format('dmY'); // e.g. 18092026

        $url = "https://nsearchives.nseindia.com/products/content/sec_bhavdata_full_{$formattedDate}.csv";

        $headers = array_merge($this->baseHeaders, [
            'Accept' => 'text/csv,text/plain,*/*',
            'Referer' => 'https://www.nseindia.com/all-reports',
        ]);

        try {
            $response = $this->client->get($url, ['headers' => $headers]);
            if ($response->getStatusCode() === 200) {
                $content = (string)$response->getBody();
                if (!empty($content) && str_contains($content, 'SYMBOL')) {
                    return [
                        'success' => true,
                        'csv' => $content,
                        'url' => $url,
                        'error' => null,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::info("NSE direct Bhavcopy fetch failed for {$url}: " . $e->getMessage());
        }

        return [
            'success' => false,
            'csv' => '',
            'url' => $url,
            'error' => "Bhavcopy not available for date: {$targetDate->format('Y-m-d')}",
        ];
    }

    /**
     * Parse Bhavcopy CSV into structured rows.
     */
    public function parseBhavcopyCsv(string $csvContent): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csvContent));
        if (empty($lines)) {
            return [];
        }

        $headerLine = array_shift($lines);
        $headers = array_map(fn($h) => strtoupper(trim($h)), str_getcsv($headerLine));

        $symbolIdx = array_search('SYMBOL', $headers);
        $seriesIdx = array_search('SERIES', $headers);
        $openIdx = array_search('OPEN_PRICE', $headers);
        $highIdx = array_search('HIGH_PRICE', $headers);
        $lowIdx = array_search('LOW_PRICE', $headers);
        $closeIdx = array_search('CLOSE_PRICE', $headers);
        $qtyIdx = array_search('TTL_TRD_QNTY', $headers);
        $valIdx = array_search('TTL_TRD_VAL', $headers);

        if ($symbolIdx === false || $closeIdx === false) {
            return [];
        }

        $rows = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $cols = str_getcsv($line);
            if (count($cols) <= max($symbolIdx, $closeIdx)) continue;

            $symbol = strtoupper(trim($cols[$symbolIdx] ?? ''));
            $series = strtoupper(trim($cols[$seriesIdx] ?? 'EQ'));

            // Accept standard series
            if (!in_array($series, ['EQ', 'BE', 'SM', 'BZ'])) {
                continue;
            }

            $rows[] = [
                'symbol' => $symbol,
                'series' => $series,
                'open' => isset($cols[$openIdx]) && is_numeric(trim($cols[$openIdx])) ? (float)trim($cols[$openIdx]) : null,
                'high' => isset($cols[$highIdx]) && is_numeric(trim($cols[$highIdx])) ? (float)trim($cols[$highIdx]) : null,
                'low' => isset($cols[$lowIdx]) && is_numeric(trim($cols[$lowIdx])) ? (float)trim($cols[$lowIdx]) : null,
                'close' => isset($cols[$closeIdx]) && is_numeric(trim($cols[$closeIdx])) ? (float)trim($cols[$closeIdx]) : null,
                'volume' => isset($cols[$qtyIdx]) && is_numeric(trim($cols[$qtyIdx])) ? (int)trim($cols[$qtyIdx]) : 0,
                'turnover' => isset($cols[$valIdx]) && is_numeric(trim($cols[$valIdx])) ? (float)trim($cols[$valIdx]) : 0.0,
            ];
        }

        return $rows;
    }
}
