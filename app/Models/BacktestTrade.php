<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacktestTrade extends Model
{
    use HasFactory;

    protected $table = 'backtest_trades';

    protected $fillable = [
        'backtest_run_id',
        'stock_id',
        'entry_date',
        'entry_price',
        'exit_date',
        'exit_price',
        'hold_weeks',
        'pnl',
        'pnl_pct',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'exit_date' => 'date',
        'entry_price' => 'decimal:2',
        'exit_price' => 'decimal:2',
        'hold_weeks' => 'integer',
        'pnl' => 'decimal:2',
        'pnl_pct' => 'decimal:2',
    ];

    public function backtestRun(): BelongsTo
    {
        return $this->belongsTo(BacktestRun::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
