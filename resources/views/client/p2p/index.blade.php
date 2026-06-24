<x-client-layout title="P2P" :embed="request()->boolean('embed')">
    <div class="-mx-1" x-data="{ buyId:null, amt:'' }">
        <div class="flex items-center justify-between mb-3 px-1">
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">P2P Trading</h1>
        </div>

        @if (session('status'))
            <div class="mb-3 mx-1 bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300 text-sm rounded-lg p-3">{{ session('status') }}</div>
        @endif

        {{-- Buy / Sell tabs --}}
        <div class="flex gap-2 mx-1 mb-4">
            <a href="{{ route('p2p.index', ['side' => 'buy']) }}" class="flex-1 text-center py-2.5 rounded-xl text-sm font-bold {{ $side==='buy' ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-500' }}">Buy</a>
            <a href="{{ route('p2p.index', ['side' => 'sell']) }}" class="flex-1 text-center py-2.5 rounded-xl text-sm font-bold {{ $side==='sell' ? 'bg-red-600 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-500' }}">Sell</a>
        </div>

        {{-- Merchant ads --}}
        <div class="space-y-3 px-1">
            @forelse ($merchants as $m)
                <div class="gcard rounded-2xl p-4 bg-white dark:bg-white/[0.04]">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-8 h-8 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 grid place-items-center font-bold text-xs">{{ strtoupper(substr($m->name,0,1)) }}</span>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white text-sm leading-tight">{{ $m->name }}</p>
                            <p class="text-[11px] text-gray-400">{{ number_format($m->orders_30d) }} orders · {{ number_format($m->completion,1) }}% completion</p>
                        </div>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $m->curSym() }}{{ number_format($m->price,2) }} <span class="text-[11px] font-normal text-gray-400">/ {{ $m->asset }}</span></p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Available <span class="text-gray-600 dark:text-gray-300">{{ number_format($m->available,2) }} {{ $m->asset }}</span></p>
                            <p class="text-[11px] text-gray-400">Limit {{ $m->curSym() }}{{ number_format($m->min_limit,0) }} – {{ $m->curSym() }}{{ number_format($m->max_limit,0) }}</p>
                            <div class="flex flex-wrap gap-1 mt-1.5">
                                @foreach (array_filter(array_map('trim', explode(',', (string)$m->pay_methods))) as $pm)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-gray-300">{{ $pm }}</span>
                                @endforeach
                            </div>
                        </div>
                        <button @click="buyId = (buyId==={{ $m->id }} ? null : {{ $m->id }}); amt=''"
                                class="px-5 py-2 rounded-lg text-white text-sm font-bold {{ $side==='buy' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700' }}">{{ ucfirst($side) }} {{ $m->asset }}</button>
                    </div>

                    {{-- Inline order form --}}
                    <div x-show="buyId==={{ $m->id }}" x-cloak class="mt-3 pt-3 border-t border-gray-100 dark:border-white/10">
                        <form method="POST" action="{{ route('p2p.order') }}" class="flex items-end gap-2">
                            @csrf
                            <input type="hidden" name="p2p_merchant_id" value="{{ $m->id }}">
                            <div class="flex-1">
                                <label class="block text-[11px] text-gray-400 mb-1">Amount ({{ $m->currency }})</label>
                                <input type="number" step="0.01" name="fiat_amount" x-model="amt" min="{{ $m->min_limit }}" max="{{ $m->max_limit }}" required style="font-size:16px"
                                       class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2 text-sm" placeholder="{{ $m->curSym() }}0.00">
                                <p class="text-[11px] text-gray-400 mt-1" x-show="parseFloat(amt)>0" x-cloak>≈ <span x-text="(parseFloat(amt)/{{ $m->price }}).toFixed(4)"></span> {{ $m->asset }}</p>
                            </div>
                            <button class="px-4 py-2 rounded-lg text-white text-sm font-semibold {{ $side==='buy' ? 'bg-emerald-600' : 'bg-red-600' }}">Place order</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-400 py-10 text-sm">No {{ $side }} merchants available right now.</p>
            @endforelse
        </div>

        {{-- My recent P2P orders --}}
        @if ($orders->count())
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mt-6 mb-2 px-1">My P2P orders</h3>
            <div class="gcard rounded-2xl bg-white dark:bg-white/[0.04] divide-y divide-gray-100 dark:divide-white/5 mx-1">
                @foreach ($orders as $o)
                    <div class="flex items-center justify-between px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ ucfirst($o->side) }} {{ number_format($o->asset_amount,4) }} {{ $o->asset }}</p>
                            <p class="text-[11px] text-gray-400">{{ $o->merchant->name ?? '—' }} · {{ $o->created_at->format('d M H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-700 dark:text-gray-200">{{ $o->currency==='INR'?'₹':'$' }}{{ number_format($o->fiat_amount,2) }}</p>
                            <span class="text-[10px] px-2 py-0.5 rounded-full {{ ['pending'=>'bg-amber-100 text-amber-800','completed'=>'bg-emerald-100 text-emerald-800','cancelled'=>'bg-red-100 text-red-700'][$o->status] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($o->status) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-client-layout>
