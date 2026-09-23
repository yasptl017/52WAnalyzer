@extends('layouts.app')

@section('title', 'Sign In - 52WAnalyzer')

@section('content')
<div class="max-w-md mx-auto py-10">
    <div class="glass-panel p-8 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl space-y-6">
        
        <!-- Header & Logo -->
        <div class="text-center space-y-2">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-emerald-400 flex items-center justify-center text-white mx-auto shadow-lg shadow-indigo-500/25">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                Welcome to 52W<span class="text-indigo-600 dark:text-indigo-400">Analyzer</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Sign in to your algorithmic trading terminal
            </p>
        </div>

        <!-- 1-Click Demo Logins Banner -->
        <div class="p-3.5 rounded-2xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 space-y-2">
            <div class="flex items-center justify-between text-[11px]">
                <span class="font-bold text-indigo-900 dark:text-indigo-200">🚀 1-Click Demo Accounts</span>
                <span class="font-mono text-[10px] text-indigo-600 dark:text-indigo-400">Pass: <strong>admin@123</strong></span>
            </div>
            <div class="grid grid-cols-3 gap-1.5 text-[10px] font-bold font-sans">
                <button type="button" onclick="fillDemoLogin('admin@algodhara.com', 'admin@123')" class="px-2 py-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-purple-100 dark:hover:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60 transition text-center shadow-xs">
                    🛡️ Admin
                </button>
                <button type="button" onclick="fillDemoLogin('elite@algodhara.com', 'admin@123')" class="px-2 py-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-purple-100 dark:hover:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60 transition text-center shadow-xs">
                    👑 Elite
                </button>
                <button type="button" onclick="fillDemoLogin('pro@algodhara.com', 'admin@123')" class="px-2 py-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-indigo-100 dark:hover:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 transition text-center shadow-xs">
                    ⭐ Pro
                </button>
                <button type="button" onclick="fillDemoLogin('starter@algodhara.com', 'admin@123')" class="px-2 py-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-teal-100 dark:hover:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800/60 transition text-center shadow-xs">
                    🚀 Starter
                </button>
                <button type="button" onclick="fillDemoLogin('trial@algodhara.com', 'admin@123')" class="px-2 py-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 transition text-center shadow-xs">
                    🎁 Trial
                </button>
                <button type="button" onclick="fillDemoLogin('expired@algodhara.com', 'admin@123')" class="px-2 py-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-rose-100 dark:hover:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 transition text-center shadow-xs">
                    🔒 Expired
                </button>
            </div>
        </div>

        <!-- Login Form -->
        <form id="login-form" method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf

            <!-- Email Address -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    Email Address
                </label>
                <input type="email" id="login-email" name="email" value="{{ old('email') }}" required autofocus
                       placeholder="trader@algodhara.com"
                       class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                @error('email')
                    <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
                        Password
                    </label>
                </div>
                <input type="password" id="login-password" name="password" required placeholder="••••••••"
                       class="w-full px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm">
                @error('password')
                    <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 cursor-pointer">
                    <input type="checkbox" name="remember" checked class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                    <span>Remember me on this device</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-500/25 transition">
                Sign In to Terminal
            </button>
        </form>

        <!-- Divider & Register Link -->
        <div class="text-center pt-2 border-t border-slate-100 dark:border-slate-800 space-y-3">
            <p class="text-xs text-slate-600 dark:text-slate-400">
                Don't have an account?
                <a href="{{ route('register') }}" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                    Start 7-Day Free Trial →
                </a>
            </p>
        </div>

    </div>
</div>

<script>
function fillDemoLogin(email, password) {
    const emailInput = document.getElementById('login-email');
    const passInput = document.getElementById('login-password');
    if (emailInput && passInput) {
        emailInput.value = email;
        passInput.value = password;
        // Visual indicator
        emailInput.classList.add('ring-2', 'ring-indigo-500');
        passInput.classList.add('ring-2', 'ring-indigo-500');
        setTimeout(() => {
            emailInput.classList.remove('ring-2', 'ring-indigo-500');
            passInput.classList.remove('ring-2', 'ring-indigo-500');
        }, 600);
    }
}
</script>
@endsection
