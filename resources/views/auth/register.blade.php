@extends('layouts.app')

@section('title', 'Start 7-Day Free Trial - 52WAnalyzer')

@section('content')
<div class="max-w-md mx-auto py-8">
    <div class="glass-panel p-8 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl space-y-6">
        
        <!-- Header & Trial Badge -->
        <div class="text-center space-y-2">
            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 inline-flex items-center gap-1.5">
                <span>🎁</span> 7-DAY FULL PRO ACCESS TRIAL
            </span>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-2">
                Create Trader Account
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Instant access to 52W High breakouts, Volume Gainers, and Pro analytics. No credit card required.
            </p>
        </div>

        <!-- Registration Form -->
        <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
            @csrf

            <!-- Full Name -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    Full Name
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       placeholder="Jitendra Patel"
                       class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                @error('name')
                    <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Email Address -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    Email Address
                </label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       placeholder="trader@algodhara.com"
                       class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                @error('email')
                    <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Mobile Phone (Optional for WhatsApp alerts) -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    Mobile Number <span class="text-slate-400 font-normal">(for breakout WhatsApp alerts)</span>
                </label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                       placeholder="+91 98765 43210"
                       class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    Password (Min. 6 characters)
                </label>
                <input type="password" name="password" required placeholder="••••••••"
                       class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                @error('password')
                    <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Password Confirmation -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    Confirm Password
                </label>
                <input type="password" name="password_confirmation" required placeholder="••••••••"
                       class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full py-2.5 px-4 bg-gradient-to-r from-indigo-600 to-emerald-600 hover:from-indigo-500 hover:to-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-500/25 transition">
                🚀 Activate 7-Day Free Trial
            </button>
        </form>

        <!-- Divider & Sign In Link -->
        <div class="text-center pt-2 border-t border-slate-100 dark:border-slate-800 space-y-3">
            <p class="text-xs text-slate-600 dark:text-slate-400">
                Already have an account?
                <a href="{{ route('login') }}" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                    Sign In →
                </a>
            </p>
        </div>

    </div>
</div>
@endsection
