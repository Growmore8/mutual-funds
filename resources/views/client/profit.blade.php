<x-client-layout title="Profit History">
    @php
        $money = fn ($n) => '$' . number_format(abs((float) $n), 2);
        $filters = ['all' => 'All time', 'today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'year' => 'This year'];
    @endphp

    <div class="w-full" x-data="{ tab: 'fund' }">
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('client.dashboard') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-emerald-500"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Profit history</h2>
            <span class="w-10"></span>
        </div>

        {{-- Product tabs --}}
        <div class="flex gap-5 border-b border-gray-200 dark:border-white/10 text-sm mb-4">
            <button @click="tab='fund'" :class="tab==='fund'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2"><i class="fa-solid fa-layer-group mr-1"></i> Mutual Fund</button>
            <button @click="tab='spot'" :class="tab==='spot'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2"><i class="fa-solid fa-arrow-trend-up mr-1"></i> Spot Trading</button>
        </div>

        {{-- ===== Mutual Fund ===== --}}
        <div x-show="tab==='fund'">
            <div class="rounded-2xl bg-emerald-600 text-white px-5 py-4 mb-4 flex items-center justify-between">
                <span class="text-sm text-white/80">Total profit earned (all time)</span>
                <span class="text-2xl font-bold">{{ ($totalProfit < 0 ? '-' : '') . $money($totalProfit) }}</span>
            </div>

            <div class="flex items-center gap-1.5 text-sm mb-4 overflow-x-auto pb-1">
                @foreach ($filters as $key => $label)
                    <a href="{{ route('client.profit', ['period' => $key]) }}"
                       class="px-3 py-1.5 rounded-full whitespace-nowrap {{ $period === $key ? 'bg-emerald-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50 dark:bg-white/5 dark:border-white/10 dark:text-gray-300' }}">{{ $label }}</a>
                @endforeach
                <form method="GET" action="{{ route('client.profit') }}" class="flex items-center gap-1.5 ml-1">
                    <input type="hidden" name="period" value="custom">
                    <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border-gray-300 text-xs dark:bg-white/5 dark:border-white/10 dark:text-gray-200 py-1.5">
                    <span class="text-gray-400 text-xs">to</span>
                    <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border-gray-300 text-xs dark:bg-white/5 dark:border-white/10 dark:text-gray-200 py-1.5">
                    <button class="px-3 py-1.5 rounded-full bg-[#0e1a35] text-white text-xs border border-white/10">Go</button>
                </form>
            </div>

            <div class="space-y-2.5">
                @forelse ($rows as $r)
                    @php $gain = (float) $r->amount >= 0; @endphp
                    <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] px-4 py-3 flex items-center gap-3">
                        <span class="w-11 h-11 rounded-xl grid place-items-center shrink-0 {{ $gain ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300' }}">
                            <i class="fa-solid {{ $gain ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white truncate leading-tight">{{ $gain ? 'Profit credited' : 'Trading loss' }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $r->created_at->format('d-M-Y') }} : {{ $r->created_at->format('H:i:s') }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-bold {{ $gain ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}"><span class="text-[10px] text-gray-400 font-normal mr-1">USD</span>{{ $money($r->amount) }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $gain ? 'Credit' : 'Debit' }}</p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] px-4 py-12 text-center text-gray-400">No profit/loss in this period.</div>
                @endforelse
            </div>
            <div class="mt-4">{{ $rows->links() }}</div>
        </div>

        {{-- ===== Spot Trading (realized profit per sell) ===== --}}
        <div x-show="tab==='spot'" x-cloak>
            <div class="space-y-2.5">
                @forelse ($spotProfits as $s)
                    @php $gain = (float) $s->realized >= 0; @endphp
                    <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] px-4 py-3 flex items-center gap-3">
                        <span class="w-11 h-11 rounded-xl grid place-items-center shrink-0 {{ $gain ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300' }}">
                            <i class="fa-solid {{ $gain ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white truncate leading-tight">{{ $s->symbol }} · sold {{ rtrim(rtrim((string)$s->qty,'0'),'.') }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $s->when->format('d-M-Y') }} : {{ $s->when->format('H:i') }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-bold {{ $gain ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ ($gain ? '+' : '-') . $s->cs . number_format(abs((float)$s->realized), 2) }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Realized</p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] px-4 py-12 text-center text-gray-400">No realized spot profit yet. It appears when you sell a holding.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-client-layout>
