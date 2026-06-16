<x-client-layout title="Profit History">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Daily profit history</h2>
            <p class="text-sm text-gray-500">Profit distributed to you each day from your pool's PnL.</p>
        </div>
        <div class="rounded-xl bg-emerald-50 px-5 py-3">
            <p class="text-xs text-gray-500">Total profit earned</p>
            <p class="text-xl font-bold text-emerald-600">{{ $money($totalProfit) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3 text-right">Eligible capital</th>
                    <th class="px-4 py-3 text-right">Your share</th>
                    <th class="px-4 py-3 text-right">Profit credited</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rows as $r)
                    <tr>
                        <td class="px-4 py-3 text-gray-600">{{ $r->allocation_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $money($r->eligible_capital) }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ number_format((float) $r->weight * 100, 2) }}%</td>
                        <td class="px-4 py-3 text-right font-medium {{ $r->net_pnl < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($r->net_pnl < 0 ? '' : '+') . $money($r->net_pnl) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">No profit recorded yet. Daily profit appears once your deposit is approved and the pool distributes PnL.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
</x-client-layout>
