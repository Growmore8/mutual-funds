<x-client-layout title="Transactions">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Transactions</h2>
        <div class="flex items-center gap-1 text-sm">
            @foreach (['' => 'All', 'deposit' => 'Deposits', 'withdrawal' => 'Withdrawals', 'profit' => 'Profit'] as $key => $label)
                <a href="{{ route('client.transactions', array_filter(['type' => $key])) }}"
                   class="px-3 py-1.5 rounded-md {{ (string)$type === (string)$key ? 'bg-emerald-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50 dark:bg-white/5 dark:border-white/10 dark:text-gray-300' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                    <th class="px-4 py-3 text-right">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($transactions as $t)
                    <tr>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $t->created_at->format('d M Y') }}<br>{{ $t->created_at->format('h:i A') }}</td>
                        <td class="px-4 py-3 capitalize">
                            @php $ti = ['deposit'=>'fa-arrow-down text-emerald-600','withdrawal'=>'fa-arrow-up text-red-500','profit'=>'fa-chart-line text-emerald-600'][$t->type] ?? 'fa-circle text-gray-400'; @endphp
                            <i class="fa-solid {{ $ti }} mr-1"></i> {{ $t->type }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $t->description ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-medium {{ $t->amount < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($t->amount < 0 ? '' : '+') . $money($t->amount) }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $money($t->balance_after) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">No transactions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $transactions->links() }}</div>
</x-client-layout>
