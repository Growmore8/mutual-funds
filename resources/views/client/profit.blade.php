<x-client-layout title="Profit History">
    @php $money = fn ($n) => '$' . number_format(abs((float) $n), 2); @endphp

    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Daily profit history</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Profit distributed to you each day from your pool's PnL.</p>
            </div>
        </div>

        {{-- Total earned chip --}}
        <div class="rounded-2xl bg-emerald-600 text-white px-5 py-4 mb-4 flex items-center justify-between">
            <span class="text-sm text-white/80">Total profit earned</span>
            <span class="text-2xl font-bold">{{ ($totalProfit < 0 ? '-' : '') . $money($totalProfit) }}</span>
        </div>

        {{-- Compact list --}}
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
                <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] px-4 py-12 text-center text-gray-400">No profit recorded yet. Daily profit appears once your deposit is approved and the pool distributes PnL.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $rows->links() }}</div>
    </div>
</x-client-layout>
