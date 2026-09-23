<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeSetup extends Model
{
    use HasFactory;

    protected $table = 'trade_setups';

    protected $fillable = [
        'stock_id',
        'trade_date',
        'entry_style',
        'entry_price',
        'stop_loss',
        'stop_method',
        'target_1r',
        'target_2r',
        'target_3r',
        'prev_resistance',
        'atr_projection',
        'rr_ratio',
        'suggested_qty',
        'capital_used',
        'status',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'entry_price' => 'decimal:2',
        'stop_loss' => 'decimal:2',
        'target_1r' => 'decimal:2',
        'target_2r' => 'decimal:2',
        'target_3r' => 'decimal:2',
        'prev_resistance' => 'decimal:2',
        'atr_projection' => 'decimal:2',
        'rr_ratio' => 'decimal:2',
        'suggested_qty' => 'integer',
        'capital_used' => 'decimal:2',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
