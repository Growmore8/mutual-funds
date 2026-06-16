<x-client-layout title="Withdraw">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Request form --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-lg font-semibold text-gray-900">Withdraw profit</h2>
                    <span class="text-xs text-gray-400"><i class="fa-solid fa-lock"></i> Capital stays invested</span>
                </div>
                <p class="text-sm text-gray-500 mb-5">You can withdraw your accumulated <strong>profit</strong>. Requests are reviewed by our team before payout.</p>

                <div class="rounded-xl bg-emerald-50 p-4 mb-5">
                    <p class="text-xs text-gray-500">Available to withdraw</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ $money($available) }}</p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
                @endif

                @if ($available <= 0)
                    <p class="text-sm text-gray-400">You have no profit available to withdraw yet. Profit accrues daily once your deposit is approved.</p>
                @else
                    <form method="POST" action="{{ route('withdraw.store') }}" class="space-y-4 text-sm">
                        @csrf
                        <div>
                            <label class="block text-gray-700 mb-1">Amount (USD)</label>
                            <input type="number" step="0.01" min="1" max="{{ $available }}" name="amount" value="{{ old('amount') }}" required
                                   class="w-full border-gray-300 rounded-md" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-1">Payout method</label>
                            <select name="method" class="w-full border-gray-300 rounded-md" required>
                                @forelse ($methods as $m)
                                    <option value="{{ $m->name }}">{{ $m->name }} ({{ $m->currency }})</option>
                                @empty
                                    <option value="Bank Wire">Bank Wire</option>
                                @endforelse
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-1">Payout details</label>
                            <textarea name="payout_details" rows="3" required maxlength="1000"
                                      class="w-full border-gray-300 rounded-md"
                                      placeholder="Bank account / IBAN / SWIFT, or your USDT wallet address">{{ old('payout_details') }}</textarea>
                        </div>
                        <button class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-md font-medium hover:bg-emerald-700">
                            <i class="fa-solid fa-money-bill-transfer"></i> Submit request
                        </button>
                    </form>
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
