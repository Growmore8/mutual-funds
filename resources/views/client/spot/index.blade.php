<x-client-layout title="Spot Trading">
    @php
        $cs = $selected?->currencySymbol() ?? '$';
        $sym = fn ($n, $s) => $s . number_format((float) $n, 2);
        $selHolding = $selected ? optional($holdings->firstWhere('instrument_id', $selected->id))->qty : 0;
        $upnl = $unrealized ?? 0;
        $marketTabs = ['india' => 'India (₹)', 'global' => 'US / Global ($)', 'crypto' => 'Crypto', 'forex' => 'Forex', 'commodity' => 'Commodity'];
    @endphp

    <div x-data="spot()" x-init="init()" class="-mx-1">
        {{-- Spot account summary — both wallets --}}
        <div class="gcard rounded-2xl p-4 mb-3 mx-1 bg-white dark:bg-white/[0.04]">
            <p class="text-[11px] uppercase tracking-wider text-blue-500 dark:text-blue-300 font-semibold mb-2"><i class="fa-solid fa-arrow-trend-up"></i> Spot Trading Account</p>
            <div class="flex flex-wrap gap-x-6 gap-y-2">
                <div><p class="text-xs text-gray-500 dark:text-gray-400">USD wallet</p><p class="text-lg font-extrabold text-gray-900 dark:text-white">${{ number_format((float)$usd->balance,2) }}</p></div>
                <div><p class="text-xs text-gray-500 dark:text-gray-400">INR wallet</p><p class="text-lg font-extrabold text-gray-900 dark:text-white">₹{{ number_format((float)$inr->balance,2) }}</p></div>
                <div><p class="text-xs text-gray-500 dark:text-gray-400">{{ $selected->symbol ?? '' }} P&L</p><p class="text-lg font-extrabold {{ $upnl<0?'text-red-500':'text-emerald-500' }}">{{ ($upnl<0?'-':'+').$sym(abs($upnl),$cs) }}</p></div>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-3 mx-1 bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300 text-sm rounded-lg p-3">{{ session('status') }}</div>
        @endif

        {{-- ============ Terminal grid (desktop) / stacked (mobile) ============ --}}
        <div class="lg:grid lg:grid-cols-[240px_minmax(0,1fr)_320px] lg:gap-4 lg:items-start px-1">

            {{-- Markets — desktop sidebar --}}
            <aside class="hidden lg:block gcard rounded-2xl p-3 bg-white dark:bg-white/[0.04]" x-data="{ flt:'{{ $selected->market ?? 'india' }}' }">
                <div class="flex flex-wrap gap-1 mb-2">
                    @foreach ($marketTabs as $k => $label)
                        <button type="button" @click="flt='{{ $k }}'" :class="flt==='{{ $k }}' ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-500'" class="px-2 py-1 rounded text-[11px]">{{ $label }}</button>
                    @endforeach
                </div>
                <div class="max-h-[560px] overflow-y-auto">
                    @foreach ($instruments as $m)
                        <a href="{{ route('spot.index', ['symbol' => $m->symbol]) }}" x-show="flt==='{{ $m->market }}'"
                           class="flex justify-between px-2.5 py-2 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-white/5 {{ $selected && $selected->id===$m->id ? 'bg-gray-100 dark:bg-white/10' : '' }}">
                            <span class="text-gray-900 dark:text-white">{{ $m->symbol }} <span class="text-[10px] text-gray-400">{{ $m->exchange }}</span></span>
                            <span class="text-gray-400">{{ $m->currencySymbol() }}{{ $m->last_price ? number_format((float)$m->last_price,2) : '—' }}</span>
                        </a>
                    @endforeach
                </div>
            </aside>

            {{-- Center: symbol header + chart --}}
            <div class="min-w-0">
                <div class="flex items-center justify-between mb-3" x-data="{ pick:false, flt:'{{ $selected->market ?? 'india' }}' }">
                    <div class="relative">
                        <button @click="pick=!pick" class="flex items-center gap-2">
                            <span class="text-xl font-extrabold text-gray-900 dark:text-white">{{ $selected->symbol ?? '—' }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded {{ $cs==='₹' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">{{ $selected->currency ?? 'USD' }}</span>
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400 lg:hidden"></i>
                        </button>
                        <p class="text-sm font-semibold" :class="change>=0?'text-emerald-500':'text-red-500'"><span x-text="(change>=0?'+':'')+change.toFixed(2)+'%'"></span></p>
                        {{-- mobile market picker --}}
                        <div x-show="pick" @click.outside="pick=false" x-cloak class="lg:hidden absolute z-30 mt-1 w-72 bg-white dark:bg-[#0a1730] border border-gray-200 dark:border-white/10 rounded-xl shadow-xl p-2">
                            <div class="flex flex-wrap gap-1 mb-2">
                                @foreach ($marketTabs as $k => $label)
                                    <button type="button" @click="flt='{{ $k }}'" :class="flt==='{{ $k }}' ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-500'" class="px-2 py-1 rounded text-[11px]">{{ $label }}</button>
                                @endforeach
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                @foreach ($instruments as $m)
                                    <a href="{{ route('spot.index', ['symbol' => $m->symbol]) }}" x-show="flt==='{{ $m->market }}'" class="flex justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-white/5">
                                        <span class="text-gray-900 dark:text-white">{{ $m->symbol }}</span>
                                        <span class="text-gray-400">{{ $m->currencySymbol() }}{{ $m->last_price ? number_format((float)$m->last_price,2) : '—' }}</span>
                                    </a>
                                @endforeach
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

                {{-- Orders / Holdings / Trades — desktop shows here under the chart --}}
                <div x-data="{ tab:'holdings' }" class="hidden lg:block mt-4">
                    <div class="flex gap-5 border-b border-gray-200 dark:border-white/10 text-sm mb-3">
                        <button @click="tab='holdings'" :class="tab==='holdings'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Holdings</button>
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
                        <div class="w-full bg-gray-100 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2.5 text-sm mb-2 text-gray-400 text-center"><i class="fa-solid fa-bolt text-emerald-500 mr-1"></i> Market order · fills at current price</div>
                        <input x-model="oqty" type="number" step="any" min="0" inputmode="decimal" placeholder="Quantity ({{ $selected->symbol ?? '' }})" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2.5 text-sm mb-2">
                        <div class="flex gap-1 mb-2">
                            <template x-for="p in [25,50,75,100]" :key="p"><button @click="setPct(p)" class="flex-1 py-1 rounded text-[11px] bg-gray-100 dark:bg-white/5 text-gray-500" x-text="p+'%'"></button></template>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                            <span x-text="side==='buy' ? 'Order cost' : 'You receive'"></span>
                            <span class="text-gray-700 dark:text-gray-300 font-medium" x-text="fmt(cost())"></span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                            <span>Available</span>
                            <span x-show="side==='buy'" class="text-gray-700 dark:text-gray-300">{{ $sym($account->balance, $cs) }}</span>
                            <span x-show="side==='sell'" class="text-gray-700 dark:text-gray-300">{{ rtrim(rtrim((string)($selHolding ?? 0),'0'),'.') ?: '0' }} {{ $selected->symbol ?? '' }}</span>
                        </div>
                        <button @click="submit()" :disabled="busy" :class="side==='buy' ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-red-500 hover:bg-red-600'" class="w-full py-3 rounded-full text-white font-bold text-sm disabled:opacity-60">
                            <span x-text="busy ? '…' : (side==='buy'?'Buy ':'Sell ')+'{{ $selected->symbol ?? '' }}'"></span>
                        </button>
                        <p x-show="msg" x-cloak x-text="msg" :class="ok?'text-emerald-600 dark:text-emerald-400':'text-red-600 dark:text-red-400'" class="text-xs text-center mt-2 font-medium"></p>
                    </div>

                    {{-- Order book --}}
                    <div class="lg:gcard lg:rounded-2xl lg:p-4 lg:bg-white lg:dark:bg-white/[0.04]">
                        <div class="flex justify-between text-[10px] text-gray-400 mb-1"><span>Price ({{ $cs }})</span><span>Qty</span></div>
                        <div class="space-y-0.5 text-[11px] font-mono">
                            <template x-for="a in book.asks.slice().reverse()" :key="'a'+a.price"><div class="flex justify-between"><span class="text-red-500" x-text="a.price.toFixed(2)"></span><span class="text-gray-400" x-text="a.qty"></span></div></template>
                        </div>
                        <div class="my-1 text-base font-extrabold" :class="change>=0?'text-emerald-500':'text-red-500'" x-text="fmt(book.last||price)"></div>
                        <div class="space-y-0.5 text-[11px] font-mono">
                            <template x-for="b in book.bids" :key="'b'+b.price"><div class="flex justify-between"><span class="text-emerald-500" x-text="b.price.toFixed(2)"></span><span class="text-gray-400" x-text="b.qty"></span></div></template>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 mt-3">
                    <a href="{{ route('client.deposit.create', ['for' => 'spot', 'cur' => $selected->currency ?? 'USD']) }}" class="flex-1 text-center py-2 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i> Add funds</a>
                    <a href="{{ route('withdraw.create', ['for' => 'spot', 'cur' => $selected->currency ?? 'USD']) }}" class="flex-1 text-center py-2 rounded-lg bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300 text-sm font-semibold">Withdraw</a>
                </div>
            </div>
        </div>

        {{-- Orders / Holdings / Trades — mobile (below everything) --}}
        <div x-data="{ tab:'holdings' }" class="lg:hidden mt-4 px-1">
            <div class="flex gap-5 border-b border-gray-200 dark:border-white/10 text-sm mb-3">
                <button @click="tab='orders'" :class="tab==='orders'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Orders ({{ $orders->count() }})</button>
                <button @click="tab='holdings'" :class="tab==='holdings'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Holdings</button>
                <button @click="tab='trades'" :class="tab==='trades'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Trades</button>
            </div>
            @include('client.spot._tabs')
        </div>
    </div>

    <script>
        function spot(){
            return {
                id: {{ $selected->id ?? 'null' }}, price: {{ (float)($selected->last_price ?? 0) }}, change: 0,
                curSym: '{{ $cs }}', interval: '1day', book: {asks:[], bids:[], last:0}, showChart: false,
                side:'buy', otype:'market', oprice:'{{ (float)($selected->last_price ?? 0) }}', ototal:'', oqty:'',
                avail: {{ (float)$account->balance }}, holdingQty: {{ (float)($selHolding ?? 0) }},
                busy:false, msg:'', ok:false, _t:null,
                fmt(n){ return this.curSym + (n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); },
                init(){ if(!this.id) return; if(window.innerWidth>=1024){ this.showChart=true; } this.tick(); this._t=setInterval(()=>this.tick(), 6000); this.$nextTick(()=>{ if(this.showChart) this.loadCandles(); }); this.$watch('showChart', v=>{ if(v) this.loadCandles(); }); },
                cost(){ let p=(this.otype==='limit')?(parseFloat(this.oprice)||0):this.price; return (parseFloat(this.oqty)||0)*p; },
                setPct(p){
                    if(this.side==='buy'){ let pr=(this.otype==='limit')?(parseFloat(this.oprice)||this.price):this.price; let maxq= pr>0 ? this.avail/pr : 0; this.oqty=(maxq*p/100).toFixed(6); }
                    else { this.oqty=(this.holdingQty*p/100).toFixed(6); }
                },
                async tick(){
                    try{
                        const q=await (await fetch('{{ route('spot.quote') }}?id='+this.id)).json();
                        if(q.price){ this.price=q.price; this.change=q.change; if(this.otype==='market') this.oprice=q.price; }
                        const b=await (await fetch('{{ route('spot.book') }}?id='+this.id)).json(); this.book=b;
                    }catch(e){}
                },
                async loadCandles(){ try{ const d=await (await fetch('{{ route('spot.candles') }}?id='+this.id+'&interval='+this.interval)).json(); this.draw((d.values||[]).map(v=>v.close)); }catch(e){} },
                draw(pts){
                    const c=document.getElementById('spot-chart'); if(!c||!pts.length) return;
                    const dpr=window.devicePixelRatio||1, W=c.clientWidth, H=c.clientHeight||220;
                    c.width=W*dpr; c.height=H*dpr; const x=c.getContext('2d'); x.setTransform(dpr,0,0,dpr,0,0); x.clearRect(0,0,W,H);
                    const min=Math.min(...pts), max=Math.max(...pts), pad=16;
                    const px=i=>pad+i*(W-2*pad)/(pts.length-1), py=v=>H-pad-(v-min)/(max-min||1)*(H-2*pad);
                    const g=x.createLinearGradient(0,0,0,H); g.addColorStop(0,'rgba(16,185,129,.30)'); g.addColorStop(1,'rgba(16,185,129,0)');
                    x.beginPath(); x.moveTo(px(0),py(pts[0])); pts.forEach((v,i)=>x.lineTo(px(i),py(v))); x.lineTo(px(pts.length-1),H-pad); x.lineTo(px(0),H-pad); x.closePath(); x.fillStyle=g; x.fill();
                    x.beginPath(); x.moveTo(px(0),py(pts[0])); pts.forEach((v,i)=>x.lineTo(px(i),py(v))); x.strokeStyle='#10b981'; x.lineWidth=2; x.stroke();
                },
                async submit(){
                    let qty = parseFloat(this.oqty)||0;
                    if(qty<=0){ this.ok=false; this.msg='Enter a quantity.'; return; }
                    this.busy=true; this.msg='';
                    try{
                        const res=await fetch('{{ route('spot.order') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                            body:JSON.stringify({instrument_id:this.id,side:this.side,type:this.otype,price:this.otype==='limit'?this.oprice:null,qty:qty})});
                        const d=await res.json(); this.ok=res.ok&&d.ok; this.msg=d.message||'Done';
                        if(this.ok) setTimeout(()=>location.reload(), 900);
                    }catch(e){ this.ok=false; this.msg='Could not place order.'; }
                    finally{ this.busy=false; }
                },
            };
        }
    </script>
</x-client-layout>
