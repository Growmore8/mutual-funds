<x-client-layout title="Withdraw">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    <a href="{{ route('client.dashboard') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-emerald-600 mb-3"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Request form --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                @php
                    $isSpot = ($purpose ?? 'fund') === 'spot';
                    $cur = $currency ?? 'USD';
                    $csym = $cur === 'INR' ? '₹' : '$';
                    $isInr = $isSpot && $cur === 'INR';
                    $isUsdSpot = $isSpot && $cur === 'USD';
                @endphp
                <h2 class="text-lg font-semibold text-gray-900 mb-3">{{ $isSpot ? 'Withdraw — Spot Trading' : 'Withdraw profit' }}</h2>

                {{-- Withdraw from --}}
                <div class="grid grid-cols-3 gap-2 mb-4 text-center">
                    <a href="{{ route('withdraw.create') }}" class="py-2.5 rounded-xl border text-xs font-semibold leading-tight {{ !$isSpot ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-500' }}"><i class="fa-solid fa-layer-group"></i><br>Mutual Fund<br><span class="text-[10px] opacity-70">USD</span></a>
                    <a href="{{ route('withdraw.create', ['for'=>'spot','cur'=>'USD']) }}" class="py-2.5 rounded-xl border text-xs font-semibold leading-tight {{ $isUsdSpot ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-500' }}"><i class="fa-solid fa-arrow-trend-up"></i><br>Spot · US/Global<br><span class="text-[10px] opacity-70">USD</span></a>
                    <a href="{{ route('withdraw.create', ['for'=>'spot','cur'=>'INR']) }}" class="py-2.5 rounded-xl border text-xs font-semibold leading-tight {{ $isInr ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 text-gray-500' }}"><i class="fa-solid fa-arrow-trend-up"></i><br>Spot · India<br><span class="text-[10px] opacity-70">INR</span></a>
                </div>

                <p class="text-sm text-gray-500 mb-5">{{ $isSpot ? 'Withdraw your Spot ' . $cur . ' profit. Your deposited capital stays available for trading.' : 'You can withdraw your accumulated profit. Your capital stays invested.' }} Requests are reviewed before payout.</p>

                <div class="rounded-xl {{ $isSpot ? 'bg-blue-50' : 'bg-emerald-50' }} p-4 mb-5">
                    <p class="text-xs text-gray-500">{{ $isSpot ? 'Spot '.$cur.' profit available' : 'Available to withdraw' }}</p>
                    <p class="text-2xl font-bold {{ $isSpot ? 'text-blue-600' : 'text-emerald-600' }}">{{ $csym }}{{ number_format((float)$available,2) }}</p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
                @endif

                @if ($available <= 0)
                    <div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-3">
                        <i class="fa-solid fa-circle-info"></i> You can submit a withdrawal once you have profit. Your capital stays locked — only profit is withdrawable.
                    </div>
                @endif
                <form method="POST" action="{{ route('withdraw.store') }}" class="space-y-4 text-sm">
                    @csrf
                    <input type="hidden" name="purpose" value="{{ $purpose ?? 'fund' }}">
                    <input type="hidden" name="currency" value="{{ $currency ?? 'USD' }}">
                    <div>
                        <label class="block text-gray-700 mb-1">Amount ({{ $cur }})</label>
                        <input type="number" step="0.01" min="1" @if($available > 0) max="{{ $available }}" @endif name="amount" value="{{ old('amount') }}" required
                               class="w-full border-gray-300 rounded-md" placeholder="{{ $cur === 'INR' ? '₹ 0.00' : '$ 0.00' }}" {{ $available <= 0 ? 'disabled' : '' }}>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-lg p-3 flex items-start gap-2">
                        <i class="fa-solid fa-shield-halved mt-0.5"></i>
                        <span>For your security, withdrawals are sent only to an account in <strong>your own name</strong>. Third-party accounts are not allowed.</span>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-gray-700">Send to</label>
                            <a href="{{ route('payout.index') }}" class="text-xs text-emerald-600 font-medium">Manage withdrawal methods</a>
                        </div>
                        @forelse ($payoutMethods as $pm)
                            <label class="flex items-center gap-3 p-3 mb-2 rounded-xl border border-gray-200 hover:border-emerald-400 cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/40">
                                <input type="radio" name="withdrawal_method_id" value="{{ $pm->id }}" required {{ $loop->first ? 'checked' : '' }} class="text-emerald-600">
                                <span class="w-9 h-9 rounded-lg grid place-items-center text-white shrink-0 {{ $pm->type==='crypto' ? 'bg-amber-500' : ($pm->type==='upi' ? 'bg-purple-500' : 'bg-blue-600') }}">
                                    <i class="fa-solid {{ $pm->type==='crypto' ? 'fa-coins' : ($pm->type==='upi' ? 'fa-mobile-screen' : 'fa-building-columns') }} text-sm"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900">{{ $pm->title() }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $pm->summary() }}</p>
                                </div>
                            </label>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">
                                No payout methods saved. <a href="{{ route('payout.index') }}" class="text-emerald-600 font-medium">Add one first</a> to withdraw.
                            </div>
                        @endforelse
                    </div>
                    <button {{ $available <= 0 || $payoutMethods->isEmpty() ? 'disabled' : '' }} class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-md font-medium hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-money-bill-transfer"></i> Submit request
                    </button>
                </form>
                @if ($available <= 0)
                    <p class="text-xs text-gray-400 mt-2">Withdrawable profit: $0.00 — submit enabled once profit is credited.</p>
                @endif
            </div>
        </div>

        {{-- Recent requests --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-3">Recent requests</h3>
            <div class="divide-y divide-gray-100 text-sm">
                @forelse ($withdrawals as $w)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">{{ $money($w->amount) }}</p>
                            <p class="text-xs text-gray-400">{{ $w->method }} · {{ $w->created_at->format('d M Y') }}</p>
                        </div>
                        @php $b = ['pending'=>'bg-amber-100 text-amber-800','approved'=>'bg-emerald-100 text-emerald-800','rejected'=>'bg-red-100 text-red-700'][$w->status]; @endphp
                        <span class="text-xs px-2.5 py-1 rounded-full capitalize {{ $b }}">{{ $w->status }}</span>
                    </div>
                @empty
                    <p class="py-6 text-center text-gray-400">No withdrawal requests yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-client-layout>
