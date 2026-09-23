<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatternMatch extends Model
{
    use HasFactory;

    protected $table = 'pattern_matches';

    protected $fillable = [
        'stock_id',
        'trade_date',
        'pattern_code',
        'pattern_name',
        'confidence',
        'evidence',
        'details',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'confidence' => 'decimal:2',
        'details' => 'array',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
