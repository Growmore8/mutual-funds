<x-client-layout title="Transactions">
    @php $money = fn ($n) => '$' . number_format(abs((float) $n), 2); @endphp

    <div class="w-full">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Transaction history</h2>
        </div>

        {{-- Filter chips --}}
        <div class="flex items-center gap-1.5 text-sm mb-4 overflow-x-auto pb-1">
            @foreach (['' => 'All', 'deposit' => 'Deposits', 'withdrawal' => 'Withdrawals', 'profit' => 'Profit'] as $key => $label)
                <a href="{{ route('client.transactions', array_filter(['type' => $key])) }}"
                   class="px-3 py-1.5 rounded-full whitespace-nowrap {{ (string)$type === (string)$key ? 'bg-emerald-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50 dark:bg-white/5 dark:border-white/10 dark:text-gray-300' }}">{{ $label }}</a>
            @endforeach
        </div>

        {{-- Compact list --}}
        <div class="space-y-2.5">
            @forelse ($transactions as $t)
                @php
                    $credit = (float) $t->amount >= 0;
                    $loss = $t->type === 'profit' && ! $credit;
                    [$icon, $tone] = match (true) {
                        $t->type === 'deposit' => ['fa-arrow-down-to-bracket', 'emerald'],
                        $t->type === 'withdrawal' => ['fa-arrow-up-from-bracket', 'amber'],
                        $t->type === 'profit' && $credit => ['fa-arrow-trend-up', 'emerald'],
                        $loss => ['fa-arrow-trend-down', 'rose'],
                        $t->type === 'referral' => ['fa-gift', 'emerald'],
                        $t->type === 'fee' => ['fa-receipt', 'gray'],
                        $t->type === 'reversal' => ['fa-rotate-left', 'gray'],
                        $t->type === 'adjustment' => ['fa-sliders', 'gray'],
                        default => [$credit ? 'fa-arrow-down-left' : 'fa-arrow-up-right', $credit ? 'emerald' : 'violet'],
                    };
                    $toneCls = [
                        'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
                        'amber' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
                        'rose' => 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
                        'violet' => 'bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300',
                        'gray' => 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-300',
                    ][$tone];
                @endphp
                <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] px-4 py-3 flex items-center gap-3">
                    <span class="w-11 h-11 rounded-xl grid place-items-center shrink-0 {{ $toneCls }}">
                        <i class="fa-solid {{ $icon }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900 dark:text-white truncate leading-tight">{{ $t->description ?? ucfirst($t->type) }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $t->created_at->format('d-M-Y') }} : {{ $t->created_at->format('H:i:s') }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-bold {{ $credit ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-800 dark:text-gray-100' }}"><span class="text-[10px] text-gray-400 font-normal mr-1">USD</span>{{ $money($t->amount) }}</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $credit ? 'Credit' : 'Debit' }}</p>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] px-4 py-12 text-center text-gray-400">No transactions yet.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $transactions->links() }}</div>
    </div>
</x-client-layout>
