<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Process user login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Automatic System-Wide NSE Sync on First Login of the Day
            $syncService = app(\App\Services\SystemSyncService::class);
            $syncMessage = '';
            if ($syncService->needsDailySyncToday()) {
                $syncRes = $syncService->executeGlobalSync('AUTO_FIRST_LOGIN', $user->id);
                if (!empty($syncRes['success'])) {
                    $syncMessage = ' • ⚡ Fresh NSE market data automatically synchronized for today!';
                }
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', "Welcome back, {$user->name}! Active Plan: " . ($user->getTierBadge()['name'] ?? 'Free') . $syncMessage);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Show the registration form (7-Day Free Trial).
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    /**
     * Process user registration and activate 7-Day Free Trial.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Create new user with 7-Day Free Trial (Full Pro features included)
        $user = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'free_trial',
            'subscription_tier' => 'FREE',
            'subscription_status' => 'TRIALING',
            'billing_cycle' => 'MONTHLY',
            'trial_ends_at' => Carbon::now()->addDays(7),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', "🎉 Welcome to 52WAnalyzer! Your 7-Day Pro Free Trial has been activated with full platform access.");
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('dashboard')->with('success', 'You have been signed out.');
    }

    /**
     * View user profile & active subscription management.
     */
    public function profile()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $payments = $user->payments()->paginate(10);
        $tierBadge = $user->getTierBadge();

        return view('auth.profile', compact('user', 'payments', 'tierBadge'));
    }

    /**
     * Developer / Demo Tier Switcher (for instantaneous live testing without payment friction).
     */
    public function switchTierDev(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $targetTier = strtoupper($request->get('tier', 'PRO'));

        match ($targetTier) {
            'ADMIN' => [
                $user->role = 'admin',
                $user->subscription_tier = 'ELITE',
                $user->subscription_status = 'ACTIVE',
                $user->subscription_ends_at = Carbon::now()->addYears(5),
            ],
            'ELITE' => [
                $user->role = 'elite',
                $user->subscription_tier = 'ELITE',
                $user->subscription_status = 'ACTIVE',
                $user->subscription_ends_at = Carbon::now()->addMonths(1),
            ],
            'PRO' => [
                $user->role = 'pro',
                $user->subscription_tier = 'PRO',
                $user->subscription_status = 'ACTIVE',
                $user->subscription_ends_at = Carbon::now()->addMonths(1),
            ],
            'STARTER' => [
                $user->role = 'starter',
                $user->subscription_tier = 'STARTER',
                $user->subscription_status = 'ACTIVE',
                $user->subscription_ends_at = Carbon::now()->addMonths(1),
            ],
            'TRIAL' => [
                $user->role = 'free_trial',
                $user->subscription_tier = 'FREE',
                $user->subscription_status = 'TRIALING',
                $user->trial_ends_at = Carbon::now()->addDays(7),
                $user->subscription_ends_at = null,
            ],
            default => [
                $user->role = 'guest',
                $user->subscription_tier = 'FREE',
                $user->subscription_status = 'EXPIRED',
                $user->trial_ends_at = Carbon::now()->subDay(),
                $user->subscription_ends_at = null,
            ],
        };

        $user->save();

        return back()->with('success', "Switched role to [{$targetTier}] successfully!");
    }
}
