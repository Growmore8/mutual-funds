<x-admin-layout title="Spot Trading">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    @if (session('status'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
    @endif

    <div class="grid sm:grid-cols-4 gap-3 mb-5">
        <div class="bg-white shadow rounded-xl p-4"><p class="text-xs text-gray-500">Active traders</p><p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['traders'] }}</p></div>
        <div class="bg-white shadow rounded-xl p-4"><p class="text-xs text-gray-500">Open client orders</p><p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['open_orders'] }}</p></div>
        <div class="bg-white shadow rounded-xl p-4"><p class="text-xs text-gray-500">Trades today</p><p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['trades_today'] }}</p></div>
        <div class="bg-white shadow rounded-xl p-4"><p class="text-xs text-gray-500">Total balance</p><p class="text-2xl font-bold text-gray-900 mt-1">{{ $money($stats['balance']) }}</p></div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Trading accounts --}}
        <div class="bg-white shadow rounded-xl p-5">
            <h3 class="font-semibold text-gray-900 mb-3">Client trading accounts</h3>
            <table class="min-w-full text-sm">
                <thead class="text-gray-500 text-left"><tr><th class="py-2">Client</th><th>Currency</th><th class="text-right">Balance</th><th class="text-right">Manage</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($accounts as $a)
                        <tr>
                            <td class="py-2">{{ $a->user->name ?? '—' }} <span class="text-gray-400 text-xs font-mono">{{ $a->user?->clientCode() }}</span></td>
                            <td class="py-2"><span class="px-2 py-0.5 rounded-full text-xs {{ $a->currency==='INR' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">{{ $a->currency }}</span></td>
                            <td class="py-2 text-right font-medium">{{ $a->currency==='INR' ? '₹' : '$' }}{{ number_format((float)$a->balance,2) }}</td>
                            <td class="py-2 text-right"><a href="{{ route('admin.spot.client', $a->user_id) }}" class="text-emerald-600 hover:underline">Manage</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-gray-400">No spot accounts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Instruments --}}
        <div class="bg-white shadow rounded-xl p-5">
            <h3 class="font-semibold text-gray-900 mb-3">Instruments</h3>
            <form method="POST" action="{{ route('admin.spot.instruments.store') }}" class="grid grid-cols-2 gap-2 mb-4 text-sm">
                @csrf
                <input name="symbol" placeholder="Symbol e.g. RELIANCE" class="border-gray-300 rounded-md" required>
                <input name="exchange" placeholder="Exchange e.g. NSE" class="border-gray-300 rounded-md">
                <input name="name" placeholder="Name" class="border-gray-300 rounded-md col-span-2">
                <select name="market" class="border-gray-300 rounded-md"><option value="india">India</option><option value="global">Global</option><option value="crypto">Crypto</option><option value="forex">Forex</option><option value="commodity">Commodity</option></select>
                <select name="type" class="border-gray-300 rounded-md"><option value="stock">Stock</option><option value="crypto">Crypto</option><option value="forex">Forex</option><option value="commodity">Commodity</option><option value="index">Index</option></select>
                <button class="col-span-2 px-4 py-2 bg-emerald-600 text-white rounded-md">Add / Update instrument</button>
            </form>
            <table class="min-w-full text-xs">
                <thead class="text-gray-500 text-left"><tr><th class="py-1.5">Symbol</th><th>Market</th><th class="text-right">Last</th><th class="text-right">Status</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($instruments as $i)
                        <tr>
                            <td class="py-1.5 font-medium">{{ $i->symbol }} <span class="text-gray-400">{{ $i->exchange }}</span></td>
                            <td>{{ ucfirst($i->market) }}</td>
                            <td class="text-right">{{ $i->last_price ? number_format((float)$i->last_price,2) : '—' }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('admin.spot.instruments.toggle', $i) }}">@csrf
                                    <button class="px-2 py-0.5 rounded-full {{ $i->enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">{{ $i->enabled ? 'Enabled' : 'Disabled' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
