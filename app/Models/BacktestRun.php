<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BacktestRun extends Model
{
    use HasFactory;

    protected $table = 'backtest_runs';

    protected $fillable = [
        'strategy_name',
        'from_date',
        'to_date',
        'sizing_per_stock',
        'total_trades',
        'win_rate_pct',
        'avg_gain_pct',
        'avg_loss_pct',
        'profit_factor',
        'max_drawdown_pct',
        'sharpe_ratio',
        'parameters',
        'hold_period_metrics',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'sizing_per_stock' => 'decimal:2',
        'total_trades' => 'integer',
        'win_rate_pct' => 'decimal:2',
        'avg_gain_pct' => 'decimal:2',
        'avg_loss_pct' => 'decimal:2',
        'profit_factor' => 'decimal:2',
        'max_drawdown_pct' => 'decimal:2',
        'sharpe_ratio' => 'decimal:2',
        'parameters' => 'array',
        'hold_period_metrics' => 'array',
    ];

    public function trades(): HasMany
    {
        return $this->hasMany(BacktestTrade::class);
    }
}
