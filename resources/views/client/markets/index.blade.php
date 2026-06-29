<x-client-layout title="Markets">
    @php
        $grp = fn ($m) => $m === 'india' ? 'NSE' : ($m === 'crypto' ? 'Crypto' : 'NYSE');
        $usdInr = app(\App\Services\SpotTradingService::class)->usdInr();
        $initQuotes = $instruments->mapWithKeys(fn ($m) => [$m->id => ['price' => (float) $m->last_price, 'change' => 0]]);
        $inrIds = $instruments->where('market', 'india')->pluck('id')->values();
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
        <div class="flex items-center px-3 py-2 text-[10px] uppercase tracking-wide text-gray-400">
            <span class="flex-1">Name</span>
            <span class="w-28 text-right">Price</span>
            <span class="w-20"></span>
        </div>

        {{-- Rows --}}
        @php $chip = ['NSE'=>'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300','NYSE'=>'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300','Crypto'=>'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300']; @endphp
        <div class="gcard rounded-2xl bg-white dark:bg-white/[0.04] divide-y divide-gray-100 dark:divide-white/5 mx-1 overflow-hidden">
            @foreach ($instruments as $m)
                @php $g = $grp($m->market); @endphp
                <a href="{{ route('spot.index', ['symbol' => $m->symbol]) }}"
                   x-show="(grp==='All' || grp==='{{ $g }}') && '{{ strtolower($m->symbol.' '.$m->name) }}'.includes(q.toLowerCase())"
                   class="group flex items-center gap-3 px-3 py-3 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <span class="relative w-10 h-10 shrink-0 rounded-full grid place-items-center text-white text-xs font-bold overflow-hidden ring-1 ring-black/5 dark:ring-white/10" style="background:{{ $m->badgeColor() }}">
                        {{ $m->monogram() }}
                        @if ($m->logoUrl())
                            <img src="{{ $m->logoUrl() }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover"
                                 data-fallback="{{ $m->logoFallback() }}"
                                 onerror="if(this.dataset.fallback){this.src=this.dataset.fallback;this.dataset.fallback='';}else{this.remove();}">
                        @endif
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-1.5">
                            <span class="truncate">{{ $m->symbol }}</span>
                            <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded {{ $chip[$g] ?? 'bg-gray-100 text-gray-500' }}">{{ $g }}</span>
                        </p>
                        <p class="text-[11px] text-gray-400 truncate">{{ $m->name }}</p>
                    </div>
                    <div class="w-28 text-right">
                        <p class="font-bold text-sm tabular-nums transition-colors duration-300" :class="dir({{ $m->id }})>0?'text-emerald-500':(dir({{ $m->id }})<0?'text-red-500':'text-gray-900 dark:text-white')" x-text="fmt({{ $m->id }})">{{ $m->market==='india' ? '₹'.number_format((float)$m->last_price*$usdInr,2) : '$'.number_format((float)$m->last_price,2) }}</p>
                        <p class="text-[10px] font-semibold flex items-center justify-end gap-0.5 transition-colors duration-300" :class="dir({{ $m->id }})>0?'text-emerald-500':(dir({{ $m->id }})<0?'text-red-500':'text-gray-300 dark:text-gray-600')">
                            <span x-text="dir({{ $m->id }})<0?'▼':(dir({{ $m->id }})>0?'▲':'•')"></span> <span>live</span>
                        </p>
                    </div>
                    <span class="w-16 text-center shrink-0 px-3 py-2 rounded-lg bg-emerald-500 group-hover:bg-emerald-600 text-white text-xs font-bold">Trade</span>
                </a>
            @endforeach
            <div x-show="!Object.keys(quotes).length" class="px-4 py-10 text-center text-gray-400 text-sm">Loading markets…</div>
        </div>
    </div>

    <script>
        function markets(){
            return {
                q: '', grp: 'All', rate: {{ (float) $usdInr }}, inrIds: {{ Illuminate\Support\Js::from($inrIds) }}, quotes: {{ Illuminate\Support\Js::from($initQuotes) }}, dirs: {},
                init(){ this.tick(); this._t = setInterval(()=>this.tick(), 5000); },
                async tick(){
                    try {
                        const q = await (await fetch('{{ route('markets.quotes') }}', {cache:'no-store'})).json();
                        const old = this.quotes; const nd = {};
                        for (const id in q){ const np = q[id].price, op = old[id] ? old[id].price : np; nd[id] = np>op ? 1 : (np<op ? -1 : (this.dirs[id]||0)); }
                        this.dirs = nd; this.quotes = q;
                    } catch(e){}
                },
                fmt(id){ const x = this.quotes[id]; if(!x) return null; const inr = this.inrIds.includes(id); const p = inr ? x.price*this.rate : x.price; return (inr?'₹':'$') + p.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits: p<10?4:2}); },
                dir(id){ return this.dirs[id] || 0; },
            };
        }
    </script>
</x-client-layout>
