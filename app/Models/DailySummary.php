<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySummary extends Model
{
    use HasFactory;

    protected $table = 'daily_summaries';

    protected $fillable = [
        'trade_date',
        'category',
        'total_count',
        'new_count',
        'repeated_count',
        'dropped_count',
        'top_stock',
        'top_score',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'total_count' => 'integer',
        'new_count' => 'integer',
        'repeated_count' => 'integer',
        'dropped_count' => 'integer',
        'top_score' => 'decimal:2',
    ];
}
