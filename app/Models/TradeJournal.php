<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeJournal extends Model
{
    use HasFactory;

    protected $table = 'trade_journal';

    protected $fillable = [
        'stock_id',
        'entry_date',
        'entry_price',
        'stop_loss',
        'target_price',
        'quantity',
        'status',
        'exit_date',
        'exit_price',
        'pnl',
        'pnl_pct',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'exit_date' => 'date',
        'entry_price' => 'decimal:2',
        'stop_loss' => 'decimal:2',
        'target_price' => 'decimal:2',
        'exit_price' => 'decimal:2',
        'quantity' => 'integer',
        'pnl' => 'decimal:2',
        'pnl_pct' => 'decimal:2',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
