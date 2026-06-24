<x-client-layout title="Markets">
    @php
        $grp = fn ($m) => $m === 'india' ? 'NSE' : ($m === 'crypto' ? 'Crypto' : 'NYSE');
        $initQuotes = $instruments->mapWithKeys(fn ($m) => [$m->id => ['price' => (float) $m->last_price, 'change' => 0]]);
    @endphp
    <div class="-mx-1" x-data="markets()" x-init="init()">
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white px-1 mb-3">Markets</h1>

        {{-- Search --}}
        <div class="px-1 mb-3">
            <div class="flex items-center gap-2 bg-gray-100 dark:bg-white/5 rounded-xl px-3 py-2.5">
                <i class="fa-solid fa-magnifying-glass text-gray-400 text-sm"></i>
                <input x-model="q" placeholder="Search coin or stock" style="font-size:16px" class="flex-1 bg-transparent border-0 focus:ring-0 p-0 text-gray-900 dark:text-white">
            </div>
        </div>

        {{-- Group tabs --}}
        <div class="flex gap-2 px-1 mb-3 text-sm overflow-x-auto">
            @foreach (['All' => 'All', 'NYSE' => 'NYSE', 'Crypto' => 'Crypto', 'NSE' => 'NSE'] as $k => $label)
                <button type="button" @click="grp='{{ $k }}'" :class="grp==='{{ $k }}' ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-500'" class="px-3 py-1.5 rounded-lg font-semibold whitespace-nowrap">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Header row --}}
        <div class="flex items-center px-3 py-2 text-[11px] text-gray-400">
            <span class="flex-1">Name</span>
            <span class="w-28 text-right">Last Price</span>
            <span class="w-20 text-right">24h %</span>
        </div>

        {{-- Rows --}}
        <div class="gcard rounded-2xl bg-white dark:bg-white/[0.04] divide-y divide-gray-100 dark:divide-white/5 mx-1 overflow-hidden">
            @foreach ($instruments as $m)
                @php $g = $grp($m->market); @endphp
                <a href="{{ route('spot.index', ['symbol' => $m->symbol]) }}"
                   x-show="(grp==='All' || grp==='{{ $g }}') && '{{ strtolower($m->symbol.' '.$m->name) }}'.includes(q.toLowerCase())"
                   class="flex items-center gap-3 px-3 py-3 hover:bg-gray-50 dark:hover:bg-white/5">
                    <span class="relative w-9 h-9 shrink-0 rounded-full grid place-items-center text-white text-[11px] font-bold overflow-hidden" style="background:{{ $m->badgeColor() }}">
                        {{ $m->monogram() }}
                        @if ($m->logoUrl())
                            <img src="{{ $m->logoUrl() }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover"
                                 data-fallback="{{ $m->logoFallback() }}"
                                 onerror="if(this.dataset.fallback){this.src=this.dataset.fallback;this.dataset.fallback='';}else{this.remove();}">
                        @endif
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-gray-900 dark:text-white text-sm">{{ $m->symbol }} <span class="text-[10px] font-normal text-gray-400">{{ $g }}</span></p>
                        <p class="text-[11px] text-gray-400 truncate">{{ $m->name }}</p>
                    </div>
                    <div class="w-28 text-right">
                        <p class="font-semibold text-gray-900 dark:text-white text-sm" x-text="fmt({{ $m->id }})">${{ number_format((float)$m->last_price, 2) }}</p>
                    </div>
                    <div class="w-20 text-right">
                        <span class="inline-block px-2 py-1 rounded-md text-xs font-semibold text-white"
                              :class="chg({{ $m->id }}) < 0 ? 'bg-red-500' : 'bg-emerald-500'"
                              x-text="chgTxt({{ $m->id }})">—</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <script>
        function markets(){
            return {
                q: '', grp: 'All', quotes: {{ Illuminate\Support\Js::from($initQuotes) }},
                init(){ this.tick(); this._t = setInterval(()=>this.tick(), 15000); },
                async tick(){
                    try { this.quotes = await (await fetch('{{ route('markets.quotes') }}', {cache:'no-store'})).json(); } catch(e){}
                },
                fmt(id){ const x = this.quotes[id]; if(!x) return null; const p = x.price; return '$' + p.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits: p<10?4:2}); },
                chg(id){ return this.quotes[id] ? this.quotes[id].change : 0; },
                chgTxt(id){ const c = this.chg(id); return (c>=0?'+':'') + c.toFixed(2) + '%'; },
            };
        }
    </script>
</x-client-layout>
