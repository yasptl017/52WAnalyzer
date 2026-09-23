@extends('layouts.app')

@section('title', 'Secure Checkout - 52WAnalyzer')

@section('content')
<div class="max-w-3xl mx-auto py-6 space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                🔒 256-Bit SSL Encrypted Checkout
            </span>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-0.5">
                Complete Your Subscription
            </h1>
        </div>
        <a href="{{ route('pricing.index') }}" class="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-white">
            ← Change Plan
        </a>
    </div>

    <!-- Checkout Grid -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        
        <!-- Payment Method Selection Form (3 cols) -->
        <div class="md:col-span-3 space-y-4">
            <div class="glass-panel p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Select Payment Method
                </h3>

                <form method="POST" action="{{ route('payment.verify') }}" id="paymentForm" class="space-y-4">
                    @csrf
                    <input type="hidden" name="tier" value="{{ $tier }}">
                    <input type="hidden" name="billing_cycle" value="{{ $billingCycle }}">
                    <input type="hidden" name="amount" value="{{ $totalAmount }}">
                    <input type="hidden" name="payment_method" id="selectedMethod" value="UPI">

                    <!-- Method Tabs -->
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="selectPaymentMethod('UPI')" id="btn-upi"
                                class="p-3 rounded-xl border-2 border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/30 text-center transition">
                            <span class="text-lg block mb-1">📱</span>
                            <span class="text-xs font-bold text-slate-900 dark:text-white">UPI / QR</span>
                        </button>

                        <button type="button" onclick="selectPaymentMethod('CARD')" id="btn-card"
                                class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-center transition">
                            <span class="text-lg block mb-1">💳</span>
                            <span class="text-xs font-bold text-slate-900 dark:text-white">Cards</span>
                        </button>

                        <button type="button" onclick="selectPaymentMethod('NETBANKING')" id="btn-netbanking"
                                class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-center transition">
                            <span class="text-lg block mb-1">🏦</span>
                            <span class="text-xs font-bold text-slate-900 dark:text-white">NetBanking</span>
                        </button>
                    </div>

                    <!-- UPI Details Box -->
                    <div id="box-upi" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 space-y-3">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">Instant UPI Payment</span>
                        <div class="flex items-center gap-2">
                            <input type="text" placeholder="yourname@upi or mobile@okhdfcbank"
                                   class="flex-1 px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="flex items-center gap-2 text-[11px] text-slate-500">
                            <span>Supported apps:</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300">GPay, PhonePe, Paytm, BHIM, CRED</span>
                        </div>
                    </div>

                    <!-- Card Details Box (Hidden by default) -->
                    <div id="box-card" class="hidden p-4 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 space-y-3">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">Credit / Debit Card</span>
                        <input type="text" placeholder="Card Number (4000 1234 5678 9010)"
                               class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" placeholder="MM/YY" class="px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                            <input type="text" placeholder="CVV" class="px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <!-- NetBanking Details Box (Hidden by default) -->
                    <div id="box-netbanking" class="hidden p-4 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 space-y-2">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">Select Your Bank</span>
                        <select class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white">
                            <option>HDFC Bank</option>
                            <option>ICICI Bank</option>
                            <option>State Bank of India (SBI)</option>
                            <option>Axis Bank</option>
                            <option>Kotak Mahindra Bank</option>
                        </select>
                    </div>

                    <!-- Submit Pay Button -->
                    <button type="submit"
                            class="w-full py-3 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-500/25 transition flex items-center justify-center gap-2">
                        <span>🔒</span> Pay ₹{{ number_format($totalAmount, 2) }} & Activate {{ $tier }} Plan
                    </button>
                </form>
            </div>
        </div>

        <!-- Order Summary Card (2 cols) -->
        <div class="md:col-span-2 space-y-4">
            <div class="glass-panel p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Order Summary
                </h3>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Selected Plan:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $planData['name'] }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Billing Cycle:</span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $billingCycle }}</span>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500">Plan Base Price:</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">₹{{ number_format($amount, 2) }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">GST (18%):</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">₹{{ number_format($gstAmount, 2) }}</span>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t-2 border-slate-200 dark:border-slate-800 text-sm">
                        <span class="font-extrabold text-slate-900 dark:text-white">Total Payable:</span>
                        <span class="font-black text-emerald-600 dark:text-emerald-400">₹{{ number_format($totalAmount, 2) }}</span>
                    </div>
                </div>

                <!-- Guarantee & Security Badge -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 space-y-2">
                    <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold">
                        <span>✓</span> Instant Account Upgrade
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span>✓</span> GST Invoice Sent via Email
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span>✓</span> Cancel anytime from Profile
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    function selectPaymentMethod(method) {
        document.getElementById('selectedMethod').value = method;
        ['upi', 'card', 'netbanking'].forEach(m => {
            document.getElementById('box-' + m).classList.add('hidden');
            document.getElementById('btn-' + m).className = 'p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-center transition';
        });

        document.getElementById('box-' + method.toLowerCase()).classList.remove('hidden');
        document.getElementById('btn-' + method.toLowerCase()).className = 'p-3 rounded-xl border-2 border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/30 text-center transition';
    }
</script>
@endsection
