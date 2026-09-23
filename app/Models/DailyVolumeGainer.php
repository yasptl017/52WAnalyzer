<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyVolumeGainer extends Model
{
    use HasFactory;

    protected $table = 'daily_volume_gainers';

    protected $fillable = [
        'stock_id',
        'trade_date',
        'volume',
        'week1_avg_volume',
        'week1_change',
        'week2_avg_volume',
        'week2_change',
        'ltp',
        'p_change',
        'turnover_lakhs',
        'sessions_seen',
        'confidence',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'volume' => 'integer',
        'week1_avg_volume' => 'integer',
        'week1_change' => 'decimal:2',
        'week2_avg_volume' => 'integer',
        'week2_change' => 'decimal:2',
        'ltp' => 'decimal:2',
        'p_change' => 'decimal:2',
        'turnover_lakhs' => 'decimal:2',
        'sessions_seen' => 'integer',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
