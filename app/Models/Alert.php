<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $table = 'alerts';

    protected $fillable = [
        'stock_id',
        'trade_date',
        'rule_code',
        'message',
        'is_acknowledged',
        'raised_at',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'is_acknowledged' => 'boolean',
        'raised_at' => 'datetime',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
