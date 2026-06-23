<x-admin-layout title="Spot · {{ $client->name }}">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    <a href="{{ route('admin.spot.index') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fa-solid fa-arrow-left"></i> Back to spot trading</a>

    @if (session('status'))
        <div class="my-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6 mt-4">
        {{-- Balance + adjust --}}
        <div class="bg-white shadow rounded-xl p-6">
            <h3 class="font-semibold text-gray-900">{{ $client->name }} <span class="text-xs font-mono text-gray-400">{{ $client->clientCode() }}</span></h3>
            <p class="text-xs text-gray-400 mb-3">{{ $client->email }}</p>
            <p class="text-sm text-gray-500">Spot Trading balance</p>
            <p class="text-3xl font-bold text-gray-900 mb-4">{{ $money($account->balance) }}</p>

            <form method="POST" action="{{ route('admin.spot.adjust', $client) }}" class="space-y-2 text-sm border-t border-gray-100 pt-4">
                @csrf
                <label class="block text-gray-700">Adjust balance</label>
                <div class="flex gap-2">
                    <select name="direction" class="border-gray-300 rounded-md"><option value="credit">Credit (+)</option><option value="debit">Debit (−)</option></select>
                    <input type="number" step="0.01" name="amount" placeholder="0.00" class="flex-1 border-gray-300 rounded-md" required>
                </div>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md w-full">Apply</button>
                <p class="text-[11px] text-gray-400">Use this to fund/correct the trading wallet. Client deposits marked “Spot” also credit here automatically on approval.</p>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-6">
            {{-- Holdings --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Holdings</h3>
                <table class="min-w-full text-sm">
                    <thead class="text-gray-500 text-left"><tr><th class="py-2">Symbol</th><th class="text-right">Qty</th><th class="text-right">Avg price</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($holdings as $h)
                            <tr><td class="py-2">{{ $h->instrument->symbol }}</td><td class="text-right">{{ rtrim(rtrim((string)$h->qty,'0'),'.') }}</td><td class="text-right">{{ number_format((float)$h->avg_price,2) }}</td></tr>
                        @empty <tr><td colspan="3" class="py-5 text-center text-gray-400">No holdings.</td></tr> @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Open orders --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Open orders</h3>
                <table class="min-w-full text-sm">
                    <thead class="text-gray-500 text-left"><tr><th class="py-2">Side</th><th>Symbol</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Action</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orders as $o)
                            <tr>
                                <td class="py-2 {{ $o->side==='buy'?'text-emerald-600':'text-red-600' }}">{{ ucfirst($o->side) }}</td>
                                <td>{{ $o->instrument->symbol }}</td>
                                <td class="text-right">{{ rtrim(rtrim((string)$o->remaining(),'0'),'.') }}</td>
                                <td class="text-right">{{ $o->price ? number_format((float)$o->price,2) : 'market' }}</td>
                                <td class="text-right"><form method="POST" action="{{ route('admin.spot.order.cancel', $o) }}">@csrf<button class="text-red-600 hover:underline">cancel</button></form></td>
                            </tr>
                        @empty <tr><td colspan="5" class="py-5 text-center text-gray-400">No open orders.</td></tr> @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Trades --}}
            <div class="bg-white shadow rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Recent trades</h3>
                <table class="min-w-full text-sm">
                    <thead class="text-gray-500 text-left"><tr><th class="py-2">Date</th><th>Side</th><th>Symbol</th><th class="text-right">Qty</th><th class="text-right">Price</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($trades as $t)
                            @php $isBuy = $t->buyer_id === $client->id; @endphp
                            <tr><td class="py-2 text-gray-400 text-xs">{{ $t->created_at->format('d M H:i') }}</td><td class="{{ $isBuy?'text-emerald-600':'text-red-600' }}">{{ $isBuy?'Buy':'Sell' }}</td><td>{{ $t->instrument->symbol }}</td><td class="text-right">{{ rtrim(rtrim((string)$t->qty,'0'),'.') }}</td><td class="text-right">{{ number_format((float)$t->price,2) }}</td></tr>
                        @empty <tr><td colspan="5" class="py-5 text-center text-gray-400">No trades.</td></tr> @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
