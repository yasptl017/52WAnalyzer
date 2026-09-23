<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSyncLog extends Model
{
    protected $fillable = [
        'trade_date',
        'synced_at',
        'triggered_by',
        'user_id',
        'status',
        'records_52wh',
        'records_vg',
        'records_bhavcopy',
        'notes',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'synced_at' => 'datetime',
        'records_52wh' => 'integer',
        'records_vg' => 'integer',
        'records_bhavcopy' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a successful sync has already been performed for a specific trading date.
     */
    public static function hasSyncedFor(string $tradeDate): bool
    {
        return static::where('trade_date', $tradeDate)
            ->where('status', 'SUCCESS')
            ->exists();
    }

    /**
     * Get the most recent successful sync log.
     */
    public static function latestSuccessful(): ?self
    {
        return static::where('status', 'SUCCESS')
            ->orderBy('synced_at', 'desc')
            ->first();
    }
}
