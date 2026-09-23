<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'company_name',
        'series',
        'asset_class',
        'sector',
        'market_cap_cr',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'market_cap_cr' => 'decimal:2',
    ];

    public function daily52wHighs(): HasMany
    {
        return $this->hasMany(Daily52wHigh::class)->orderBy('trade_date', 'desc');
    }

    public function dailyVolumeGainers(): HasMany
    {
        return $this->hasMany(DailyVolumeGainer::class)->orderBy('trade_date', 'desc');
    }

    public function ohlcvBars(): HasMany
    {
        return $this->hasMany(OhlcvBar::class)->orderBy('bar_date', 'asc');
    }

    public function patternMatches(): HasMany
    {
        return $this->hasMany(PatternMatch::class)->orderBy('trade_date', 'desc');
    }

    public function momentumScores(): HasMany
    {
        return $this->hasMany(MomentumScore::class)->orderBy('trade_date', 'desc');
    }

    public function tradeSetups(): HasMany
    {
        return $this->hasMany(TradeSetup::class)->orderBy('trade_date', 'desc');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class)->orderBy('trade_date', 'desc');
    }

    public function tradeJournalEntries(): HasMany
    {
        return $this->hasMany(TradeJournal::class)->orderBy('entry_date', 'desc');
    }

    public function watchlists(): BelongsToMany
    {
        return $this->belongsToMany(Watchlist::class, 'watchlist_stocks')
            ->withPivot('notes')
            ->withTimestamps();
    }
}
