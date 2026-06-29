<x-client-layout title="Spot Trading">
    @php
        $usdInr = app(\App\Services\SpotTradingService::class)->usdInr();
        $selHolding = $selected ? optional($holdings->firstWhere('instrument_id', $selected->id))->qty : 0;
        $upnl = $unrealized ?? 0;
        // Everything the front-end needs to switch symbols without a page reload.
        $insJson = $instruments->map(fn ($m) => [
            'id' => $m->id, 'symbol' => $m->symbol, 'exchange' => $m->exchange, 'market' => $m->market,
            'currency' => $m->currency, 'price' => (float) $m->last_price, 'group' => $m->market === 'india' ? 'inr' : 'usd',
            'logo' => $m->logoUrl(), 'fallback' => $m->logoFallback(), 'mono' => $m->monogram(), 'color' => $m->badgeColor(),
        ])->values();
        $holdingsJson = $holdings->pluck('qty', 'instrument_id');
        $selGroup = ($selected->market ?? 'india') === 'india' ? 'inr' : 'usd';
    @endphp

    <div x-data="spot()" x-init="init()" class="-mx-1">
        {{-- Spot account summary (single USD base) --}}
        <div class="gcard rounded-2xl mb-3 mx-1 overflow-hidden bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06]">
            <div class="px-4 py-2.5 flex items-center justify-between bg-gradient-to-r from-blue-500/10 to-emerald-500/10">
                <p class="text-[11px] uppercase tracking-wider font-bold text-blue-600 dark:text-blue-300"><i class="fa-solid fa-arrow-trend-up mr-1"></i> Spot Trading Account</p>
                <a href="{{ route('transfer.create') }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 px-2.5 py-1 rounded-full transition"><i class="fa-solid fa-right-left"></i> Transfer</a>
            </div>
            <div class="grid grid-cols-3 divide-x divide-gray-100 dark:divide-white/[0.08] py-3">
                <div class="px-3 text-center">
                    <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Spot Deposit</p>
                    <p class="text-base sm:text-lg font-extrabold text-gray-900 dark:text-white">${{ number_format((float)($spotDeposited ?? 0),2) }}</p>
                </div>
                <div class="px-3 text-center">
                    <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Total P&L</p>
                    <p class="text-base sm:text-lg font-extrabold {{ ($spotTotalPnl ?? 0)<0?'text-red-500':'text-emerald-500' }}">{{ (($spotTotalPnl ?? 0)<0?'-':'+').'$'.number_format(abs($spotTotalPnl ?? 0),2) }}</p>
                </div>
                <div class="px-3 text-center">
                    <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Floating P&L</p>
                    <p class="text-base sm:text-lg font-extrabold {{ $upnl<0?'text-red-500':'text-emerald-500' }}">{{ ($upnl<0?'-':'+').'$'.number_format(abs($upnl),2) }}</p>
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-3 mx-1 bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300 text-sm rounded-lg p-3">{{ session('status') }}</div>
        @endif
        @unless (auth()->user()->spotUsable())
            <div class="mb-3 mx-1 bg-amber-50 border border-amber-200 text-amber-800 dark:bg-amber-500/10 dark:border-amber-500/30 dark:text-amber-300 text-sm rounded-lg p-3 flex items-start gap-2">
                <i class="fa-solid fa-lock mt-0.5"></i>
                <span>Spot trading is currently <strong>{{ auth()->user()->spot_active === false ? 'deactivated' : 'view-only' }}</strong> on your account. You can view prices but can't place orders. Please contact support.</span>
            </div>
        @endunless

        {{-- Market tabs: NYSE (US/Global + Crypto) | NSE (India) — switch with no reload --}}
        <div class="flex gap-2 mx-1 mb-3">
            <button @click="setGroup('usd')" :class="group==='usd' ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-500'" class="flex-1 text-center py-2.5 rounded-xl text-sm font-bold">NYSE <span class="font-normal text-[11px]">US/Global + Crypto · $</span></button>
            <button @click="setGroup('inr')" :class="group==='inr' ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-500'" class="flex-1 text-center py-2.5 rounded-xl text-sm font-bold">NSE <span class="font-normal text-[11px]">India · $</span></button>
        </div>

        {{-- ============ Terminal grid (desktop) / stacked (mobile) ============ --}}
        <div class="lg:grid lg:grid-cols-[240px_minmax(0,1fr)_320px] lg:gap-4 lg:items-start px-1">

            {{-- Markets — desktop sidebar (live prices, no reload on select) --}}
            <aside class="hidden lg:block gcard rounded-2xl p-3 bg-white dark:bg-white/[0.04]">
                <p class="text-xs font-semibold text-gray-500 mb-2" x-text="group==='inr' ? 'NSE · India' : 'NYSE · US/Global + Crypto'"></p>
                <div class="max-h-[560px] overflow-y-auto">
                    <template x-for="m in listed" :key="m.id">
                        <button @click="select(m)" class="w-full flex items-center gap-2 px-2.5 py-2 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-white/5" :class="active && active.id===m.id ? 'bg-gray-100 dark:bg-white/10' : ''">
                            <span class="relative w-6 h-6 shrink-0 rounded-full grid place-items-center text-white text-[9px] font-bold overflow-hidden" :style="'background:'+m.color">
                                <span x-text="m.mono"></span>
                                <template x-if="m.logo"><img :src="m.logo" :data-fallback="m.fallback" class="absolute inset-0 w-full h-full object-cover" @error="if($el.dataset.fallback){$el.src=$el.dataset.fallback;$el.dataset.fallback='';}else{$el.remove();}"></template>
                            </span>
                            <span class="flex-1 min-w-0 text-left text-gray-900 dark:text-white truncate" x-text="m.symbol"></span>
                            <span class="text-[10px] text-gray-400" x-text="m.exchange"></span>
                            <span class="text-gray-400 font-mono text-xs" x-text="rowPrice(m)"></span>
                        </button>
                    </template>
                </div>
            </aside>

            {{-- Center: symbol header + chart --}}
            <div class="min-w-0">
                <div class="flex items-center justify-between mb-3">
                    <div class="relative">
                        <button @click="pick=!pick" class="flex items-center gap-2">
                            <span class="text-xl font-extrabold text-gray-900 dark:text-white" x-text="sym"></span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded" :class="group==='inr' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700'" x-text="group==='inr' ? 'NSE' : 'NYSE'"></span>
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400 lg:hidden"></i>
                        </button>
                        <p class="text-sm font-semibold" :class="change>=0?'text-emerald-500':'text-red-500'"><span x-text="(change>=0?'+':'')+change.toFixed(2)+'%'"></span></p>
                        {{-- mobile symbol picker --}}
                        <div x-show="pick" @click.outside="pick=false" x-cloak class="lg:hidden absolute z-30 mt-1 w-72 bg-white dark:bg-[#0a1730] border border-gray-200 dark:border-white/10 rounded-xl shadow-xl p-2">
                            <div class="max-h-64 overflow-y-auto">
                                <template x-for="m in listed" :key="'p'+m.id">
                                    <button @click="select(m)" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-white/5">
                                        <span class="relative w-6 h-6 shrink-0 rounded-full grid place-items-center text-white text-[9px] font-bold overflow-hidden" :style="'background:'+m.color">
                                            <span x-text="m.mono"></span>
                                            <template x-if="m.logo"><img :src="m.logo" :data-fallback="m.fallback" class="absolute inset-0 w-full h-full object-cover" @error="if($el.dataset.fallback){$el.src=$el.dataset.fallback;$el.dataset.fallback='';}else{$el.remove();}"></template>
                                        </span>
                                        <span class="flex-1 min-w-0 text-left text-gray-900 dark:text-white truncate" x-text="m.symbol"></span>
                                        <span class="text-gray-400 font-mono text-xs" x-text="rowPrice(m)"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <button @click="showChart=!showChart" class="lg:hidden w-9 h-9 grid place-items-center rounded-lg bg-gray-100 dark:bg-white/5 text-gray-500"><i class="fa-solid fa-chart-simple"></i></button>
                </div>

                <div x-show="showChart" class="gcard rounded-2xl p-3 bg-white dark:bg-white/[0.04]">
                    <div class="flex gap-1 text-xs mb-2">
                        <template x-for="iv in ['1min','5min','1h','1day']" :key="iv">
                            <button @click="interval=iv; loadCandles()" :class="interval===iv?'bg-emerald-600 text-white':'bg-gray-100 dark:bg-white/5 text-gray-500'" class="px-2 py-1 rounded" x-text="iv"></button>
                        </template>
                    </div>
                    <canvas id="spot-chart" class="w-full h-56 lg:h-[440px]"></canvas>
                </div>

                {{-- Orders / Holdings / Trades — desktop (account-wide, refresh on order) --}}
                <div x-data="{ tab:'holdings' }" class="hidden lg:block mt-4">
                    <div class="flex gap-5 border-b border-gray-200 dark:border-white/10 text-sm mb-3">
                        <button @click="tab='holdings'" :class="tab==='holdings'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Holdings</button>
                        <button @click="tab='orders'" :class="tab==='orders'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Open orders</button>
                        <button @click="tab='trades'" :class="tab==='trades'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Trades</button>
                    </div>
                    @include('client.spot._tabs')
                </div>
            </div>

            {{-- Right rail: order form + order book --}}
            <div class="mt-3 lg:mt-0">
                <div class="grid grid-cols-2 lg:grid-cols-1 gap-3">
                    {{-- Order form --}}
                    <div class="lg:gcard lg:rounded-2xl lg:p-4 lg:bg-white lg:dark:bg-white/[0.04]">
                        <div class="grid grid-cols-2 rounded-lg overflow-hidden mb-3 text-sm font-bold">
                            <button @click="side='buy'"  :class="side==='buy'  ? 'bg-emerald-500 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-500'" class="py-2">Buy</button>
                            <button @click="side='sell'" :class="side==='sell' ? 'bg-red-500 text-white'     : 'bg-gray-100 dark:bg-white/5 text-gray-500'" class="py-2">Sell</button>
                        </div>
                        <div class="grid grid-cols-2 rounded-lg overflow-hidden mb-2 text-xs font-semibold border border-gray-200 dark:border-white/10">
                            <button type="button" @click="otype='market'" :class="otype==='market' ? 'bg-gray-200 dark:bg-white/10 text-gray-900 dark:text-white' : 'text-gray-400'" class="py-1.5">Market</button>
                            <button type="button" @click="otype='limit'; if(!oprice||oprice==0) oprice=(price*dispRate).toFixed(dp())" :class="otype==='limit' ? 'bg-gray-200 dark:bg-white/10 text-gray-900 dark:text-white' : 'text-gray-400'" class="py-1.5">Limit</button>
                        </div>
                        <div x-show="otype==='market'" class="w-full bg-gray-100 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2 text-xs mb-2 text-gray-400 text-center"><i class="fa-solid fa-bolt text-emerald-500 mr-1"></i> Fills at current market price</div>
                        <div x-show="otype==='limit'" x-cloak class="mb-2">
                            <label class="block text-[11px] text-gray-400 mb-1"><span x-text="'Limit price ('+curSym+')'"></span></label>
                            <input x-model="oprice" type="number" step="any" min="0" inputmode="decimal" placeholder="Price" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2.5 text-sm">
                        </div>
                        <input x-model="oqty" type="number" step="any" min="0" inputmode="decimal" :placeholder="'Quantity ('+sym+')'" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2.5 text-sm mb-2">
                        <div class="flex gap-1 mb-2">
                            <template x-for="p in [25,50,75,100]" :key="p"><button @click="setPct(p)" class="flex-1 py-1 rounded text-[11px] bg-gray-100 dark:bg-white/5 text-gray-500" x-text="p+'%'"></button></template>
                        </div>
                        <div x-show="dispRate!==1" class="flex items-center justify-between text-xs text-gray-500 mb-1">
                            <span x-text="(side==='buy' ? 'Order cost' : 'You receive')+' ('+curSym+')'"></span>
                            <span class="text-gray-700 dark:text-gray-300 font-medium" x-text="pf(cost())"></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                            <span x-text="(side==='buy' ? 'Order cost' : 'You receive')+' (USD)'"></span>
                            <span class="text-gray-700 dark:text-gray-300 font-medium" x-text="cf(cost())"></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                            <span>Available</span>
                            <span x-show="side==='buy'" class="text-gray-700 dark:text-gray-300">${{ number_format((float)$account->balance,2) }}</span>
                            <span x-show="side==='sell'" class="text-gray-700 dark:text-gray-300"><span x-text="(+holdingQty).toLocaleString(undefined,{maximumFractionDigits:6})"></span> <span x-text="sym"></span></span>
                        </div>
                        <button @click="submit()" :disabled="busy" :class="side==='buy' ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-red-500 hover:bg-red-600'" class="w-full py-3 rounded-full text-white font-bold text-sm disabled:opacity-60">
                            <span x-text="busy ? '…' : (side==='buy'?'Buy ':'Sell ')+sym"></span>
                        </button>
                        <p x-show="msg" x-cloak x-text="msg" :class="ok?'text-emerald-600 dark:text-emerald-400':'text-red-600 dark:text-red-400'" class="text-xs text-center mt-2 font-medium"></p>
                    </div>

                    {{-- Order book --}}
                    <div class="lg:gcard lg:rounded-2xl lg:p-4 lg:bg-white lg:dark:bg-white/[0.04]">
                        <div class="flex justify-between text-[10px] text-gray-400 mb-1"><span x-text="'Price ('+curSym+')'"></span><span>Qty</span></div>
                        <div class="space-y-0.5 text-[11px] font-mono">
                            <template x-for="a in book.asks.slice().reverse()" :key="'a'+a.price"><div class="flex justify-between"><span class="text-red-500" x-text="pn(a.price)"></span><span class="text-gray-400" x-text="a.qty"></span></div></template>
                        </div>
                        <div class="my-1 text-base font-extrabold transition-colors duration-300" :class="dir>0?'text-emerald-500':(dir<0?'text-red-500':'text-gray-700 dark:text-gray-200')" x-text="pf(price||book.last)"></div>
                        <div class="space-y-0.5 text-[11px] font-mono">
                            <template x-for="b in book.bids" :key="'b'+b.price"><div class="flex justify-between"><span class="text-emerald-500" x-text="pn(b.price)"></span><span class="text-gray-400" x-text="b.qty"></span></div></template>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 mt-3">
                    <a :href="'{{ route('client.deposit.create') }}?for=spot&cur='+(active?active.currency:'USD')" class="flex-1 text-center py-2 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i> Add funds</a>
                    <a :href="'{{ route('withdraw.create') }}?for=spot&cur='+(active?active.currency:'USD')" class="flex-1 text-center py-2 rounded-lg bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300 text-sm font-semibold">Withdraw</a>
                </div>
            </div>
        </div>

        {{-- Orders / Holdings / Trades — mobile --}}
        <div x-data="{ tab:'holdings' }" class="lg:hidden mt-4 px-1">
            <div class="flex gap-5 border-b border-gray-200 dark:border-white/10 text-sm mb-3">
                <button @click="tab='holdings'" :class="tab==='holdings'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Holdings</button>
                <button @click="tab='orders'" :class="tab==='orders'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Open orders</button>
                <button @click="tab='trades'" :class="tab==='trades'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Trades</button>
            </div>
            @include('client.spot._tabs')
        </div>
    </div>

    <script>
        function spot(){
            return {
                instruments: @json($insJson),
                holdings: @json($holdingsJson),
                usdInr: {{ (float)$usdInr }},
                group: '{{ $selGroup }}',
                active: null,
                id: {{ $selected->id ?? 'null' }}, price: {{ (float)($selected->last_price ?? 0) }}, change: 0,
                interval: '1min', book: {asks:[], bids:[], last:0}, showChart: true, pick:false,
                side:'buy', otype:'market', oprice:'{{ (float)($selected->last_price ?? 0) }}', oqty:'',
                avail: {{ (float)$account->balance }},
                busy:false, msg:'', ok:false, _t:null, _p:null, _c:0,
                candles: [], dir: 0,

                get curSym(){ return this.active && this.active.market==='india' ? '₹' : '$'; },
                get dispRate(){ return this.active && this.active.market==='india' ? this.usdInr : 1; },
                get sym(){ return this.active ? this.active.symbol : '—'; },
                get holdingQty(){ return this.active ? (+(this.holdings[this.active.id]||0)) : 0; },
                get listed(){ return this.instruments.filter(m => m.group===this.group); },

                rowPrice(m){
                    if(!m.price) return '—';
                    var v = m.market==='india' ? m.price*this.usdInr : m.price;
                    var sym = m.market==='india' ? '₹' : '$';
                    return sym + v.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
                },
                dp(){ var p=Math.abs((this.price||0)*this.dispRate); return p>0 && p<10 ? 4 : 2; },
                pn(usd){ var d=this.dp(); return ((usd||0)*this.dispRate).toLocaleString(undefined,{minimumFractionDigits:d,maximumFractionDigits:d}); },
                pf(usd){ return this.curSym + this.pn(usd); },
                cf(usd){ return '$' + (usd||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); },
                cost(){ const pxUsd = this.otype==='limit' ? ((parseFloat(this.oprice)||0)/this.dispRate) : this.price; return (parseFloat(this.oqty)||0) * pxUsd; },
                setPct(p){
                    if(this.side==='buy'){ let maxq= this.price>0 ? this.avail/this.price : 0; this.oqty=(maxq*p/100).toFixed(6); }
                    else { this.oqty=(this.holdingQty*p/100).toFixed(6); }
                },

                init(){
                    this.active = this.instruments.find(m=>m.id===this.id) || this.instruments.find(m=>m.group===this.group) || this.instruments[0] || null;
                    if(this.active){ this.id=this.active.id; this.group=this.active.group; this.price=this.active.price; }
                    if(this.id){ this.tick(); this._t=setInterval(()=>this.tick(), 2000); this.$nextTick(()=>this.loadCandles()); }
                    this.refreshList(); this._p=setInterval(()=>this.refreshList(), 5000);
                    this.$watch('showChart', v=>{ if(v) this.loadCandles(); });
                    window.addEventListener('resize', ()=>this.draw());
                },

                // switch symbol with NO page reload
                select(m){
                    this.active = m; this.id = m.id; this.price = m.price; this.change = 0;
                    this.oqty=''; this.msg=''; this.pick=false;
                    if(this.otype!=='limit') this.oprice = (m.price*this.dispRate);
                    this.book={asks:[],bids:[],last:0}; this.candles=[];
                    this.tick(); this.$nextTick(()=>this.loadCandles());
                },
                setGroup(g){ this.group=g; const first=this.instruments.find(m=>m.group===g); if(first) this.select(first); },

                // live prices for the whole list (from DB, refreshed each minute by spot:seed)
                async refreshList(){
                    try{ const m=await (await fetch('{{ route('spot.prices') }}',{cache:'no-store'})).json();
                        this.instruments.forEach(i=>{ if(m[i.id]!=null) i.price=+m[i.id]; });
                        if(this.active && m[this.active.id]!=null && this.otype!=='limit'){ /* keep header price fresh via tick */ }
                    }catch(e){}
                },
                async tick(){
                    if(!this.id) return;
                    try{
                        const q=await (await fetch('{{ route('spot.quote') }}?id='+this.id, {cache:'no-store'})).json();
                        if(q.price){ this.dir = q.price>this.price ? 1 : (q.price<this.price ? -1 : this.dir); this.price=q.price; this.change=q.change||0;
                            if(this.active) this.active.price=q.price;
                            if(this.otype!=='limit'){ this.oprice=(q.price*this.dispRate); }
                            if(this.showChart && this.candles.length){ this.candles[this.candles.length-1].close = q.price; this.draw(); } }
                        const b=await (await fetch('{{ route('spot.book') }}?id='+this.id, {cache:'no-store'})).json(); this.book=b;
                        this._c=(this._c||0)+1; if(this._c%30===0) this.loadCandles();
                    }catch(e){}
                },
                async loadCandles(){ if(!this.id) return; try{ const d=await (await fetch('{{ route('spot.candles') }}?id='+this.id+'&interval='+this.interval)).json(); this.candles=d.values||[]; this.draw(); }catch(e){} },
                draw(){
                    const c=document.getElementById('spot-chart'); if(!c||!this.candles.length) return;
                    const pts=this.candles.map(v=>+v.close), times=this.candles.map(v=>v.time);
                    const dpr=window.devicePixelRatio||1, W=c.clientWidth, H=c.clientHeight||220;
                    c.width=W*dpr; c.height=H*dpr; const x=c.getContext('2d'); x.setTransform(dpr,0,0,dpr,0,0); x.clearRect(0,0,W,H);
                    const padL=52, padR=10, padT=10, padB=22;
                    let min=Math.min(...pts), max=Math.max(...pts); if(min===max){ min-=1; max+=1; }
                    const d=this.dp(), dark=document.documentElement.classList.contains('dark');
                    const grid=dark?'rgba(255,255,255,.07)':'rgba(0,0,0,.06)', txt=dark?'#7d8aa0':'#94a3b8';
                    const px=i=>padL+i*(W-padL-padR)/(Math.max(1,pts.length-1)), py=v=>padT+(max-v)/(max-min)*(H-padT-padB);
                    x.font='10px sans-serif'; x.fillStyle=txt; x.strokeStyle=grid; x.lineWidth=1;
                    for(let r=0;r<=4;r++){ const v=max-(max-min)*r/4, y=py(v); x.beginPath(); x.moveTo(padL,y); x.lineTo(W-padR,y); x.stroke(); x.textAlign='right'; x.fillText(this.curSym+(v*this.dispRate).toLocaleString(undefined,{minimumFractionDigits:d,maximumFractionDigits:d}), padL-6, y+3); }
                    x.textAlign='center';
                    [0, Math.floor(pts.length/2), pts.length-1].forEach(i=>{ const t=(times[i]||'').slice(5,16); x.fillText(t, px(i), H-7); });
                    const g=x.createLinearGradient(0,padT,0,H-padB); g.addColorStop(0,'rgba(16,185,129,.28)'); g.addColorStop(1,'rgba(16,185,129,0)');
                    x.beginPath(); x.moveTo(px(0),py(pts[0])); pts.forEach((v,i)=>x.lineTo(px(i),py(v))); x.lineTo(px(pts.length-1),H-padB); x.lineTo(px(0),H-padB); x.closePath(); x.fillStyle=g; x.fill();
                    x.beginPath(); x.moveTo(px(0),py(pts[0])); pts.forEach((v,i)=>x.lineTo(px(i),py(v))); x.strokeStyle='#10b981'; x.lineWidth=2; x.stroke();
                    const last=pts[pts.length-1], ly=py(last); x.setLineDash([4,4]); x.strokeStyle='rgba(16,185,129,.6)'; x.beginPath(); x.moveTo(padL,ly); x.lineTo(W-padR,ly); x.stroke(); x.setLineDash([]);
                    x.fillStyle='#10b981'; x.beginPath(); x.arc(px(pts.length-1),ly,3,0,7); x.fill();
                },
                async submit(){
                    let qty = parseFloat(this.oqty)||0;
                    if(qty<=0){ this.ok=false; this.msg='Enter a quantity.'; return; }
                    this.busy=true; this.msg='';
                    try{
                        const res=await fetch('{{ route('spot.order') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                            body:JSON.stringify({instrument_id:this.id,side:this.side,type:this.otype,price:this.otype==='limit'?((parseFloat(this.oprice)||0)/this.dispRate):null,qty:qty})});
                        const d=await res.json(); this.ok=res.ok&&d.ok; this.msg=d.message||'Done';
                        if(this.ok) setTimeout(()=>location.reload(), 900);
                    }catch(e){ this.ok=false; this.msg='Could not place order.'; }
                    finally{ this.busy=false; }
                },
            };
        }
    </script>
</x-client-layout>
