<x-client-layout title="Profit History">
    @php
        $money = fn ($n) => '$' . number_format(abs((float) $n), 2);
        $filters = ['all' => 'All time', 'today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'year' => 'This year'];
    @endphp

    <div class="w-full">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Profit history</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Every day's profit distributed to you from your pool's PnL.</p>
        </div>

        {{-- Total earned chip --}}
        <div class="rounded-2xl bg-emerald-600 text-white px-5 py-4 mb-4 flex items-center justify-between">
            <span class="text-sm text-white/80">Total profit earned (all time)</span>
            <span class="text-2xl font-bold">{{ ($totalProfit < 0 ? '-' : '') . $money($totalProfit) }}</span>
        </div>

        {{-- Filters --}}
        <div class="flex items-center gap-1.5 text-sm mb-4 overflow-x-auto pb-1">
            @foreach ($filters as $key => $label)
                <a href="{{ route('client.profit', ['period' => $key]) }}"
                   class="px-3 py-1.5 rounded-full whitespace-nowrap {{ $period === $key ? 'bg-emerald-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50 dark:bg-white/5 dark:border-white/10 dark:text-gray-300' }}">{{ $label }}</a>
            @endforeach
            {{-- Date range --}}
            <form method="GET" action="{{ route('client.profit') }}" class="flex items-center gap-1.5 ml-1">
                <input type="hidden" name="period" value="custom">
                <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border-gray-300 text-xs dark:bg-white/5 dark:border-white/10 dark:text-gray-200 py-1.5">
                <span class="text-gray-400 text-xs">to</span>
                <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border-gray-300 text-xs dark:bg-white/5 dark:border-white/10 dark:text-gray-200 py-1.5">
                <button class="px-3 py-1.5 rounded-full bg-[#0e1a35] text-white text-xs border border-white/10">Go</button>
            </form>
        </div>

        {{-- Full list --}}
        <div class="space-y-2.5">
            @forelse ($rows as $r)
                @php $gain = (float) $r->net_pnl >= 0; @endphp
                <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] px-4 py-3 flex items-center gap-3">
                    <span class="w-11 h-11 rounded-xl grid place-items-center shrink-0 {{ $gain ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300' }}">
                        <i class="fa-solid {{ $gain ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900 dark:text-white truncate leading-tight">Daily profit · {{ number_format((float) $r->weight * 100, 2) }}% share</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $r->allocation_date->format('d-M-Y') }} · capital {{ $money($r->eligible_capital) }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-bold {{ $gain ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}"><span class="text-[10px] text-gray-400 font-normal mr-1">USD</span>{{ $money($r->net_pnl) }}</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $gain ? 'Credit' : 'Debit' }}</p>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] px-4 py-12 text-center text-gray-400">No profit in this period.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $rows->links() }}</div>
    </div>
</x-client-layout>
