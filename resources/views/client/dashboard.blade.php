<x-client-layout title="Dashboard">
    @php
        $money = fn ($n) => '$' . number_format((float) $n, 2);
        $maxChart = max(1, (float) ($chart->max('net_pnl') ?? 0));
    @endphp

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Welcome, {{ $user->name }}</h2>
        <p class="text-gray-500 text-sm">Here's your pool account overview.</p>
    </div>

    {{-- Top stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Pool Account Size</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $money($pool->capacity ?? 0) }}</p>
            <p class="text-[11px] text-gray-400 mt-1">Total managed pool</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Pool Profit (Today)</p>
            <p class="text-2xl font-bold {{ $poolToday < 0 ? 'text-red-600' : 'text-emerald-600' }} mt-1">{{ $money($poolToday) }}</p>
            <p class="text-[11px] text-gray-400 mt-1">{{ $latestSnap?->snapshot_date?->format('d M Y') ?? '—' }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Your Investment</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $money($investment) }}</p>
            <p class="text-[11px] text-gray-400 mt-1">{{ $user->accountType->name ?? 'No plan' }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Your Profit Share</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ rtrim(rtrim(number_format($sharePct,2),'0'),'.') }}%</p>
            <p class="text-[11px] text-gray-400 mt-1">Of your pool PnL</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Earnings overview --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Your Earnings Overview</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-xl bg-emerald-50 p-4"><p class="text-xs text-gray-500">Today's Profit</p><p class="text-xl font-bold text-emerald-600">{{ $money($today) }}</p></div>
                <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs text-gray-500">Total Earned</p><p class="text-xl font-bold text-gray-900">{{ $money($totalEarned) }}</p></div>
                <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs text-gray-500">Yesterday's Profit</p><p class="text-xl font-bold text-gray-900">{{ $money($yesterday) }}</p></div>
                <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs text-gray-500">This Month</p><p class="text-xl font-bold text-gray-900">{{ $money($month) }}</p></div>
            </div>

            {{-- Mini earnings chart --}}
            <h3 class="font-semibold text-gray-900 mt-6 mb-3">Earnings (last 14 days)</h3>
            <div class="flex items-end gap-1.5 h-32">
                @forelse ($chart as $row)
                    <div class="flex-1 bg-emerald-400/80 rounded-t hover:bg-emerald-500 transition"
                         style="height: {{ max(4, ($row->net_pnl / $maxChart) * 100) }}%"
                         title="{{ $row->allocation_date->format('d M') }}: {{ $money($row->net_pnl) }}"></div>
                @empty
                    <p class="text-sm text-gray-400">No earnings yet — they appear once your deposit is approved and the pool distributes daily profit.</p>
                @endforelse
            </div>
        </div>

        {{-- Investment summary --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Investment Summary</h3>
            <dl class="text-sm divide-y divide-gray-100">
                <div class="flex justify-between py-2"><dt class="text-gray-500">Pool Account Size</dt><dd class="font-semibold">{{ $money($pool->capacity ?? 0) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Pool Profit (today)</dt><dd class="font-semibold">{{ $money($poolToday) }}</dd></div>
                <div class="flex justify-between py-2 bg-emerald-50 -mx-2 px-2 rounded"><dt class="text-gray-600">Your Investment</dt><dd class="font-bold text-emerald-700">{{ $money($investment) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Your Profit Share</dt><dd class="font-semibold">{{ rtrim(rtrim(number_format($sharePct,2),'0'),'.') }}%</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Account Balance</dt><dd class="font-semibold">{{ $money($balanceAfter) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">This Month Profit</dt><dd class="font-semibold">{{ $money($month) }}</dd></div>
            </dl>
            <a href="#invest" class="mt-4 block text-center px-4 py-2.5 bg-emerald-600 text-white rounded-xl font-semibold text-sm">Invest More</a>
        </div>
    </div>

    {{-- Recent transactions --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mt-6" id="transactions">
        <h3 class="font-semibold text-gray-900 mb-3">Recent Transactions</h3>
        <table class="min-w-full text-sm">
            <thead class="text-gray-400 text-left"><tr><th class="py-2">Date</th><th>Description</th><th class="text-right">Amount</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($recent as $t)
                    <tr>
                        <td class="py-2 text-gray-400">{{ $t->created_at->format('d M Y') }}</td>
                        <td>{{ $t->description ?? ucfirst($t->type) }}</td>
                        <td class="text-right font-medium {{ $t->amount < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($t->amount<0?'':'+') . $money($t->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-6 text-center text-gray-400">No transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- How it works --}}
    <div class="bg-[#0a1730] text-white rounded-2xl p-6 mt-6">
        <h3 class="font-semibold mb-2">Pool · Invest · Earn Together</h3>
        <p class="text-sm text-gray-300">Your capital joins the managed pool ({{ $pool->account_ref ?? '—' }}). Each day the pool's profit is distributed to investors in proportion to their share, adjusted by your plan's profit-share rate. Your funds always remain under your ownership.</p>
    </div>
</x-client-layout>
