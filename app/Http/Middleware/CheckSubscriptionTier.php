<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionTier
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $minTier  'starter', 'pro', 'elite', or 'admin'
     */
    public function handle(Request $request, Closure $next, string $minTier = 'starter'): Response
    {
        $user = Auth::user();

        // If not logged in, redirect to login or pricing
        if (!$user) {
            return redirect()->route('login')->with('warning', 'Please sign in or start your 7-day Free Trial to access this premium facility.');
        }

        // Admin has universal access
        if ($user->role === 'admin') {
            return $next($request);
        }

        $hasAccess = match (strtolower($minTier)) {
            'starter' => $user->isStarter(),
            'pro' => $user->isPro(),
            'elite' => $user->isElite(),
            'admin' => $user->role === 'admin',
            default => $user->isStarter(),
        };

        if (!$hasAccess) {
            $requiredTierName = strtoupper($minTier);
            return redirect()->route('pricing.index', ['required_tier' => $requiredTierName])
                ->with('error', "⚡ Premium Feature Locked: Upgrade to the [{$requiredTierName}] tier or higher to access this facility.");
        }

        return $next($request);
    }
}
