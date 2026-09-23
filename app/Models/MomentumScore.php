<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MomentumScore extends Model
{
    use HasFactory;

    protected $table = 'momentum_scores';

    protected $fillable = [
        'stock_id',
        'trade_date',
        'momentum_score',
        'institutional_score',
        'trade_quality_score',
        'rank_tier',
        'confidence_pct',
        'reasons',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'momentum_score' => 'decimal:2',
        'institutional_score' => 'decimal:2',
        'trade_quality_score' => 'decimal:2',
        'confidence_pct' => 'decimal:2',
        'reasons' => 'array',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
