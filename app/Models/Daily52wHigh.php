<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Daily52wHigh extends Model
{
    use HasFactory;

    protected $table = 'daily_52w_highs';

    protected $fillable = [
        'stock_id',
        'trade_date',
        'ltp',
        'high_price',
        'prev_close',
        'change_val',
        'p_change',
        'prev_52w_high',
        'prev_52w_date',
        'consecutive_days',
        'total_appearances_30d',
        'is_new',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'ltp' => 'decimal:2',
        'high_price' => 'decimal:2',
        'prev_close' => 'decimal:2',
        'change_val' => 'decimal:2',
        'p_change' => 'decimal:2',
        'prev_52w_high' => 'decimal:2',
        'consecutive_days' => 'integer',
        'total_appearances_30d' => 'integer',
        'is_new' => 'boolean',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
