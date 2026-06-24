<x-client-layout title="P2P" :embed="request()->boolean('embed')">
    @php
        $card = function ($m, $side) {
            return $m;
        };
    @endphp
    <div class="-mx-1" x-data="{ side: 'buy' }">
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white px-1 mb-3">P2P Trading</h1>

        {{-- Buy / Sell toggle (client-side, no reload) --}}
        <div class="flex gap-2 mx-1 mb-4">
            <button type="button" @click="side='buy'" :class="side==='buy' ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-500'" class="flex-1 py-2.5 rounded-xl text-sm font-bold">Buy</button>
            <button type="button" @click="side='sell'" :class="side==='sell' ? 'bg-red-600 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-500'" class="flex-1 py-2.5 rounded-xl text-sm font-bold">Sell</button>
        </div>

        @foreach (['buy' => $buyMerchants, 'sell' => $sellMerchants] as $tab => $list)
            <div x-show="side==='{{ $tab }}'" x-cloak class="space-y-3 px-1">
                @forelse ($list as $m)
                    <div class="gcard rounded-2xl p-4 bg-white dark:bg-white/[0.04]">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-8 h-8 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 grid place-items-center font-bold text-xs">{{ strtoupper(substr($m->name,0,1)) }}</span>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white text-sm leading-tight">{{ $m->name }} <i class="fa-solid fa-circle-check text-emerald-500 text-[11px]"></i></p>
                                <p class="text-[11px] text-gray-400">{{ number_format($m->orders_30d) }} orders · {{ number_format($m->completion,1) }}%</p>
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
                            {{-- Locked button --}}
                            <button type="button" disabled title="Coming soon"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold cursor-not-allowed text-white opacity-60 {{ $tab==='buy' ? 'bg-emerald-600' : 'bg-red-600' }}">
                                <i class="fa-solid fa-lock text-xs"></i> {{ ucfirst($tab) }}
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-400 py-10 text-sm">No {{ $tab }} merchants available right now.</p>
                @endforelse
            </div>
        @endforeach

        <p class="text-center text-[11px] text-gray-400 mt-5 px-6"><i class="fa-solid fa-lock mr-1"></i> P2P trading is opening soon. Browse verified merchants now — buy &amp; sell unlock shortly.</p>
    </div>
</x-client-layout>
