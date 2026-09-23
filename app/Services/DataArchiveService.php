<?php

namespace App\Services;

use App\Models\DataArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class DataArchiveService
{
    /**
     * Store raw daily snapshot files on disk and register in data_archives table.
     */
    public function archiveDay(
        string $tradeDate,
        ?string $raw52whJson = null,
        ?string $rawVgJson = null,
        ?string $rawBhavcopyCsv = null,
        int $total52wh = 0,
        int $totalVg = 0,
        string $status = 'COMPLETE',
        ?string $notes = null
    ): DataArchive {
        $dateStamp = date_create($tradeDate)->format('Ymd');
        $dirPath = storage_path("app/archives/{$tradeDate}");

        if (!File::exists($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        $file52wh = null;
        if ($raw52whJson) {
            $file52wh = "archives/{$tradeDate}/52wh_{$dateStamp}.json";
            File::put(storage_path("app/{$file52wh}"), $raw52whJson);
        }

        $fileVg = null;
        if ($rawVgJson) {
            $fileVg = "archives/{$tradeDate}/volume_gainers_{$dateStamp}.json";
            File::put(storage_path("app/{$fileVg}"), $rawVgJson);
        }

        $fileBhav = null;
        if ($rawBhavcopyCsv) {
            $fileBhav = "archives/{$tradeDate}/bhavcopy_{$dateStamp}.csv";
            File::put(storage_path("app/{$fileBhav}"), $rawBhavcopyCsv);
        }

        return DataArchive::updateOrCreate(
            ['trade_date' => $tradeDate],
            [
                'source' => 'NSE',
                'total_52wh' => $total52wh,
                'total_vg' => $totalVg,
                'status' => $status,
                'raw_file_52wh' => $file52wh,
                'raw_file_vg' => $fileVg,
                'raw_file_bhavcopy' => $fileBhav,
                'notes' => $notes,
            ]
        );
    }

    /**
     * Retrieve list of archives with pagination / ordering.
     */
    public function getArchives(int $limit = 60)
    {
        return DataArchive::orderBy('trade_date', 'desc')->paginate($limit);
    }

    /**
     * Find single archive by date.
     */
    public function findByDate(string $tradeDate): ?DataArchive
    {
        return DataArchive::where('trade_date', $tradeDate)->first();
    }

    /**
     * Read raw archived content from disk.
     */
    public function getRawContent(string $tradeDate, string $type): ?string
    {
        $archive = $this->findByDate($tradeDate);
        if (!$archive) {
            return null;
        }

        $relPath = match ($type) {
            '52wh' => $archive->raw_file_52wh,
            'vg' => $archive->raw_file_vg,
            'bhavcopy' => $archive->raw_file_bhavcopy,
            default => null,
        };

        if ($relPath && File::exists(storage_path("app/{$relPath}"))) {
            return File::get(storage_path("app/{$relPath}"));
        }

        return null;
    }
}
