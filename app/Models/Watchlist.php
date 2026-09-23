<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Watchlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'notes',
    ];

    public function stocks(): BelongsToMany
    {
        return $this->belongsToMany(Stock::class, 'watchlist_stocks')
            ->withPivot('notes')
            ->withTimestamps();
    }
}
