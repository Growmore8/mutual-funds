<x-client-layout title="Spot Trading">
    @php
        $money = fn ($n) => '$' . number_format((float) $n, 2);
        $selHolding = $selected ? optional($holdings->firstWhere('instrument_id', $selected->id))->qty : 0;
    @endphp

    <div x-data="spot()" x-init="init()" class="-mx-1">
        {{-- Spot account summary — SEPARATE from the mutual-fund pool --}}
        @php $upnl = $unrealized ?? 0; @endphp
        <div class="gcard rounded-2xl p-4 mb-3 mx-1 bg-white dark:bg-white/[0.04]">
            <p class="text-[11px] uppercase tracking-wider text-blue-500 dark:text-blue-300 font-semibold mb-1"><i class="fa-solid fa-arrow-trend-up"></i> Spot Trading Account</p>
            <div class="grid grid-cols-3 gap-2">
                <div><p class="text-xs text-gray-500 dark:text-gray-400">Balance (cash)</p><p class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $money($account->balance) }}</p></div>
                <div><p class="text-xs text-gray-500 dark:text-gray-400">Holdings value</p><p class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $money($holdingsValue ?? 0) }}</p></div>
                <div><p class="text-xs text-gray-500 dark:text-gray-400">Spot P&L</p><p class="text-lg font-extrabold {{ $upnl < 0 ? 'text-red-500' : 'text-emerald-500' }}">{{ ($upnl < 0 ? '-' : '+') . $money(abs($upnl)) }}</p></div>
            </div>
            <p class="text-[10px] text-gray-400 mt-1">Equity {{ $money($equity ?? 0) }} · Not linked to your Mutual Fund pool.</p>
        </div>

        {{-- Symbol header --}}
        <div class="flex items-center justify-between px-1 mb-3" x-data="{ pick:false }">
            <div class="relative">
                <button @click="pick=!pick" class="flex items-center gap-2">
                    <span class="text-xl font-extrabold text-gray-900 dark:text-white">{{ $selected->symbol ?? '—' }}</span>
                    <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                </button>
                <p class="text-sm font-semibold" :class="change>=0?'text-emerald-500':'text-red-500'"><span x-text="(change>=0?'+':'')+change.toFixed(2)+'%'"></span></p>
                <div x-show="pick" @click.outside="pick=false" x-cloak class="absolute z-30 mt-1 w-56 max-h-72 overflow-y-auto bg-white dark:bg-[#0a1730] border border-gray-200 dark:border-white/10 rounded-xl shadow-xl p-1">
                    @foreach ($instruments as $m)
                        <a href="{{ route('spot.index', ['symbol' => $m->symbol]) }}" class="flex justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-white/5 {{ $selected && $selected->id===$m->id ? 'bg-gray-100 dark:bg-white/10' : '' }}">
                            <span class="text-gray-900 dark:text-white">{{ $m->symbol }}</span>
                            <span class="text-gray-400">{{ $m->last_price ? number_format((float)$m->last_price,2) : '—' }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            <button @click="showChart=!showChart" class="w-9 h-9 grid place-items-center rounded-lg bg-gray-100 dark:bg-white/5 text-gray-500"><i class="fa-solid fa-chart-simple"></i></button>
        </div>

        @if (session('status'))
            <div class="mb-3 mx-1 bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300 text-sm rounded-lg p-3">{{ session('status') }}</div>
        @endif

        {{-- Form + Order book --}}
        <div class="grid grid-cols-2 gap-3 px-1">
            {{-- LEFT: order form --}}
            <div>
                <div class="grid grid-cols-2 rounded-lg overflow-hidden mb-3 text-sm font-bold">
                    <button @click="side='buy'"  :class="side==='buy'  ? 'bg-emerald-500 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-500'" class="py-2">Buy</button>
                    <button @click="side='sell'" :class="side==='sell' ? 'bg-red-500 text-white'     : 'bg-gray-100 dark:bg-white/5 text-gray-500'" class="py-2">Sell</button>
                </div>

                <select x-model="otype" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2.5 text-sm mb-2">
                    <option value="market">Market</option>
                    <option value="limit">Limit</option>
                </select>

                {{-- price (limit) --}}
                <input x-show="otype==='limit'" x-model="oprice" type="number" placeholder="Price"
                       class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2.5 text-sm mb-2">
                <div x-show="otype==='market'" class="w-full bg-gray-100 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2.5 text-sm mb-2 text-gray-400">Fill at market price</div>

                {{-- amount: buy=Total (cash), sell=Qty --}}
                <template x-if="side==='buy'">
                    <input x-model="ototal" type="number" placeholder="Total (USD)" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2.5 text-sm mb-2">
                </template>
                <template x-if="side==='sell'">
                    <input x-model="oqty" type="number" placeholder="Quantity" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2.5 text-sm mb-2">
                </template>

                {{-- % slider --}}
                <div class="flex gap-1 mb-2">
                    <template x-for="p in [25,50,75,100]" :key="p">
                        <button @click="setPct(p)" class="flex-1 py-1 rounded text-[11px] bg-gray-100 dark:bg-white/5 text-gray-500" x-text="p+'%'"></button>
                    </template>
                </div>

                <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                    <span>Available</span>
                    <span x-show="side==='buy'" class="text-gray-700 dark:text-gray-300">{{ $money($account->balance) }}</span>
                    <span x-show="side==='sell'" class="text-gray-700 dark:text-gray-300">{{ rtrim(rtrim((string)($selHolding ?? 0),'0'),'.') ?: '0' }} {{ $selected->symbol ?? '' }}</span>
                </div>

                <button @click="submit()" :disabled="busy" :class="side==='buy' ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-red-500 hover:bg-red-600'" class="w-full py-3 rounded-full text-white font-bold text-sm disabled:opacity-60">
                    <span x-text="busy ? '…' : (side==='buy'?'Buy ':'Sell ')+'{{ $selected->symbol ?? '' }}'"></span>
                </button>
                <p x-show="msg" x-cloak x-text="msg" :class="ok?'text-emerald-600 dark:text-emerald-400':'text-red-600 dark:text-red-400'" class="text-xs text-center mt-2 font-medium"></p>
            </div>

            {{-- RIGHT: order book --}}
            <div>
                <div class="flex justify-between text-[10px] text-gray-400 mb-1"><span>Price</span><span>Qty</span></div>
                <div class="space-y-0.5 text-[11px] font-mono">
                    <template x-for="a in book.asks.slice().reverse()" :key="'a'+a.price">
                        <div class="flex justify-between"><span class="text-red-500" x-text="a.price.toFixed(2)"></span><span class="text-gray-400" x-text="a.qty"></span></div>
                    </template>
                </div>
                <div class="my-1 text-base font-extrabold" :class="change>=0?'text-emerald-500':'text-red-500'" x-text="fmt(book.last||price)"></div>
                <div class="space-y-0.5 text-[11px] font-mono">
                    <template x-for="b in book.bids" :key="'b'+b.price">
                        <div class="flex justify-between"><span class="text-emerald-500" x-text="b.price.toFixed(2)"></span><span class="text-gray-400" x-text="b.qty"></span></div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Funds shortcut --}}
        <div class="flex gap-2 px-1 mt-3">
            <a href="{{ route('client.deposit.create', ['for' => 'spot']) }}" class="flex-1 text-center py-2 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 text-sm font-semibold"><i class="fa-solid fa-plus mr-1"></i> Add funds</a>
            <a href="{{ route('withdraw.create', ['for' => 'spot']) }}" class="flex-1 text-center py-2 rounded-lg bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300 text-sm font-semibold">Withdraw</a>
        </div>

        {{-- Collapsible chart --}}
        <div x-show="showChart" x-cloak class="gcard rounded-2xl p-3 mt-3 mx-1 bg-white dark:bg-white/[0.04]">
            <div class="flex gap-1 text-xs mb-2">
                <template x-for="iv in ['1min','5min','1h','1day']" :key="iv">
                    <button @click="interval=iv; loadCandles()" :class="interval===iv?'bg-emerald-600 text-white':'bg-gray-100 dark:bg-white/5 text-gray-500'" class="px-2 py-1 rounded" x-text="iv"></button>
                </template>
            </div>
            <canvas id="spot-chart" class="w-full" height="220"></canvas>
        </div>

        {{-- Tabs: Orders / Holdings / Trades --}}
        <div x-data="{ tab:'orders' }" class="mt-4 px-1">
            <div class="flex gap-5 border-b border-gray-200 dark:border-white/10 text-sm mb-3">
                <button @click="tab='orders'" :class="tab==='orders'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Orders ({{ $orders->count() }})</button>
                <button @click="tab='holdings'" :class="tab==='holdings'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Holdings</button>
                <button @click="tab='trades'" :class="tab==='trades'?'text-emerald-500 border-emerald-500':'text-gray-400 border-transparent'" class="pb-2 border-b-2">Trades</button>
            </div>

            <div x-show="tab==='orders'">
                @forelse ($orders as $o)
                    <div class="flex justify-between items-center text-xs py-1.5">
                        <span class="{{ $o->side==='buy'?'text-emerald-500':'text-red-500' }} font-semibold">{{ strtoupper($o->side) }}</span>
                        <span class="flex-1 px-2 text-gray-500">{{ $o->instrument->symbol }} {{ rtrim(rtrim((string)$o->remaining(),'0'),'.') }} @ {{ $o->price ? number_format((float)$o->price,2) : 'mkt' }}</span>
                        <form method="POST" action="{{ route('spot.cancel',$o) }}">@csrf<button class="text-gray-400 hover:text-red-500">cancel</button></form>
                    </div>
                @empty <p class="text-xs text-gray-400 py-3 text-center">No open orders.</p> @endforelse
            </div>
            <div x-show="tab==='holdings'" x-cloak>
                @forelse ($holdings as $h)
                    <div class="flex justify-between text-xs py-1.5"><span>{{ $h->instrument->symbol }} ×{{ rtrim(rtrim((string)$h->qty,'0'),'.') }}</span><span class="text-gray-400">avg {{ number_format((float)$h->avg_price,2) }}</span></div>
                @empty <p class="text-xs text-gray-400 py-3 text-center">No holdings yet.</p> @endforelse
            </div>
            <div x-show="tab==='trades'" x-cloak>
                @forelse ($trades as $t)
                    @php $isBuy = $t->buyer_id === auth()->id(); @endphp
                    <div class="flex justify-between text-xs py-1.5"><span class="{{ $isBuy?'text-emerald-500':'text-red-500' }}">{{ $isBuy?'BUY':'SELL' }} {{ $t->instrument->symbol }} ×{{ rtrim(rtrim((string)$t->qty,'0'),'.') }}</span><span class="text-gray-400">{{ number_format((float)$t->price,2) }} · {{ $t->created_at->format('d M H:i') }}</span></div>
                @empty <p class="text-xs text-gray-400 py-3 text-center">No trades yet.</p> @endforelse
            </div>
        </div>
    </div>

    <script>
        function spot(){
            return {
                id: {{ $selected->id ?? 'null' }}, price: {{ (float)($selected->last_price ?? 0) }}, change: 0,
                interval: '1day', book: {asks:[], bids:[], last:0}, showChart: false,
                side:'buy', otype:'market', oprice:'{{ (float)($selected->last_price ?? 0) }}', ototal:'', oqty:'',
                avail: {{ (float)$account->balance }}, holdingQty: {{ (float)($selHolding ?? 0) }},
                busy:false, msg:'', ok:false, _t:null,
                fmt(n){ return (n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); },
                init(){ if(!this.id) return; this.tick(); this._t=setInterval(()=>this.tick(), 6000);
                        this.$watch('showChart', v=>{ if(v) this.loadCandles(); }); },
                setPct(p){ if(this.side==='buy'){ this.ototal=(this.avail*p/100).toFixed(2); } else { this.oqty=(this.holdingQty*p/100).toFixed(6); } },
                async tick(){
                    try{
                        const q=await (await fetch('{{ route('spot.quote') }}?id='+this.id)).json();
                        if(q.price){ this.price=q.price; this.change=q.change; if(this.otype==='market') this.oprice=q.price; }
                        const b=await (await fetch('{{ route('spot.book') }}?id='+this.id)).json(); this.book=b;
                    }catch(e){}
                },
                async loadCandles(){
                    try{ const d=await (await fetch('{{ route('spot.candles') }}?id='+this.id+'&interval='+this.interval)).json(); this.draw((d.values||[]).map(v=>v.close)); }catch(e){}
                },
                draw(pts){
                    const c=document.getElementById('spot-chart'); if(!c||!pts.length) return;
                    const dpr=window.devicePixelRatio||1, W=c.clientWidth, H=220;
                    c.width=W*dpr; c.height=H*dpr; const x=c.getContext('2d'); x.setTransform(dpr,0,0,dpr,0,0); x.clearRect(0,0,W,H);
                    const min=Math.min(...pts), max=Math.max(...pts), pad=16;
                    const px=i=>pad+i*(W-2*pad)/(pts.length-1), py=v=>H-pad-(v-min)/(max-min||1)*(H-2*pad);
                    const g=x.createLinearGradient(0,0,0,H); g.addColorStop(0,'rgba(16,185,129,.30)'); g.addColorStop(1,'rgba(16,185,129,0)');
                    x.beginPath(); x.moveTo(px(0),py(pts[0])); pts.forEach((v,i)=>x.lineTo(px(i),py(v))); x.lineTo(px(pts.length-1),H-pad); x.lineTo(px(0),H-pad); x.closePath(); x.fillStyle=g; x.fill();
                    x.beginPath(); x.moveTo(px(0),py(pts[0])); pts.forEach((v,i)=>x.lineTo(px(i),py(v))); x.strokeStyle='#10b981'; x.lineWidth=2; x.stroke();
                },
                async submit(){
                    let qty;
                    if(this.side==='sell'){ qty=parseFloat(this.oqty)||0; }
                    else { let p=(this.otype==='limit')?parseFloat(this.oprice):this.price; qty=p>0?((parseFloat(this.ototal)||0)/p):0; }
                    if(qty<=0){ this.ok=false; this.msg='Enter an amount.'; return; }
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
