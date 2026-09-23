<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OhlcvBar extends Model
{
    use HasFactory;

    protected $table = 'ohlcv_bars';

    protected $fillable = [
        'stock_id',
        'bar_date',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'source',
    ];

    protected $casts = [
        'bar_date' => 'date',
        'open' => 'decimal:2',
        'high' => 'decimal:2',
        'low' => 'decimal:2',
        'close' => 'decimal:2',
        'volume' => 'integer',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
