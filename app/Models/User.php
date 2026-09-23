<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'subscription_tier',
        'subscription_status',
        'billing_cycle',
        'trial_ends_at',
        'subscription_ends_at',
        'razorpay_customer_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if user is Super Admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has active Elite tier (or Admin).
     */
    public function isElite(): bool
    {
        if ($this->role === 'admin' || $this->role === 'elite' || $this->subscription_tier === 'ELITE') {
            return $this->hasActiveSubscription();
        }
        return false;
    }

    /**
     * Check if user has active Pro tier (or Elite/Admin).
     */
    public function isPro(): bool
    {
        if ($this->isElite()) return true;

        if ($this->role === 'pro' || $this->subscription_tier === 'PRO') {
            return $this->hasActiveSubscription();
        }

        // Active free trial gets full Pro features for 7 days
        if ($this->isTrialActive()) return true;

        return false;
    }

    /**
     * Check if user has active Starter tier (or Pro/Elite/Admin).
     */
    public function isStarter(): bool
    {
        if ($this->isPro()) return true;

        if ($this->role === 'starter' || $this->subscription_tier === 'STARTER') {
            return $this->hasActiveSubscription();
        }

        return false;
    }

    /**
     * Check if user's free trial is currently active.
     */
    public function isTrialActive(): bool
    {
        if ($this->role === 'free_trial' || $this->subscription_status === 'TRIALING') {
            return $this->trial_ends_at && $this->trial_ends_at->isFuture();
        }
        return false;
    }

    /**
     * Check if user subscription is active (or admin).
     */
    public function hasActiveSubscription(): bool
    {
        if ($this->role === 'admin') return true;
        if ($this->isTrialActive()) return true;

        if ($this->subscription_status === 'ACTIVE') {
            if (!$this->subscription_ends_at || $this->subscription_ends_at->isFuture()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check access to specific named feature facility.
     */
    public function canAccess(string $feature): bool
    {
        if ($this->role === 'admin') return true;

        return match ($feature) {
            // Basic Free features
            'risk_calculator_basic' => true,

            // Starter features (₹999/mo)
            'momentum_dashboard', 'volume_gainers', 'date_matrix_7d', 're_emergence_5d', 'trade_journal_basic' => $this->isStarter(),

            // Pro features (₹2,499/mo)
            'date_matrix_full', 're_emergence_10d', 're_emergence_15d', 'trading_engine_candidates',
            'avoid_list', 'pattern_scanner', 'trade_journal_export' => $this->isPro(),

            // Elite features (₹4,999/mo)
            'strategy_simulation', 'batch_journal_ingestion', 'confluence_engine',
            'live_scraper_triggers', 'priority_support' => $this->isElite(),

            default => false,
        };
    }

    /**
     * Returns styled label and badge info for current tier.
     */
    public function getTierBadge(): array
    {
        if ($this->role === 'admin') {
            return ['name' => 'SUPER ADMIN', 'color' => 'red', 'icon' => '⚡', 'bg' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30'];
        }
        if ($this->isElite()) {
            return ['name' => 'ELITE SUBSCRIBER', 'color' => 'purple', 'icon' => '👑', 'bg' => 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/30'];
        }
        if ($this->isPro()) {
            return ['name' => $this->isTrialActive() ? 'PRO TRIAL (7D)' : 'PRO SWING TRADER', 'color' => 'indigo', 'icon' => '⭐', 'bg' => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/30'];
        }
        if ($this->isStarter()) {
            return ['name' => 'STARTER TRADER', 'color' => 'blue', 'icon' => '🚀', 'bg' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30'];
        }
        return ['name' => 'FREE GUEST', 'color' => 'slate', 'icon' => '👤', 'bg' => 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30'];
    }
}
