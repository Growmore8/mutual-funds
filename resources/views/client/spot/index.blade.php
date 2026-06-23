<x-client-layout title="Spot Trading">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    <div x-data="spot()" x-init="init()">
        {{-- Balance bar --}}
        <div class="gcard rounded-2xl p-4 mb-4 flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-white/[0.04]">
            <div class="flex gap-8">
                <div><p class="text-xs text-gray-500 dark:text-gray-400">Trading Balance</p><p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $money($account->balance) }}</p></div>
                <div><p class="text-xs text-gray-500 dark:text-gray-400">Holdings</p><p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $holdings->count() }}</p></div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('client.deposit.create') }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold"><i class="fa-solid fa-arrow-down mr-1"></i> Add funds</a>
                <a href="{{ route('withdraw.create') }}" class="px-4 py-2 rounded-xl border border-gray-200 dark:border-white/15 text-sm font-semibold"><i class="fa-solid fa-arrow-up mr-1"></i> Withdraw</a>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300 text-sm rounded-lg p-3">{{ session('status') }}</div>
        @endif

        <div class="grid lg:grid-cols-[240px_1fr_280px] gap-4">
            {{-- Markets --}}
            <div class="gcard rounded-2xl p-3 order-2 lg:order-1 bg-white dark:bg-white/[0.04]">
                <h3 class="font-semibold text-sm mb-2 text-gray-900 dark:text-white">Markets</h3>
                <div class="space-y-1 max-h-[520px] overflow-y-auto pr-1">
                    @foreach ($instruments as $m)
                        <a href="{{ route('spot.index', ['symbol' => $m->symbol]) }}"
                           class="flex items-center justify-between px-2.5 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 {{ $selected && $selected->id === $m->id ? 'bg-gray-100 dark:bg-white/10' : '' }}">
                            <div><p class="text-sm font-medium text-gray-900 dark:text-white">{{ $m->symbol }}</p><p class="text-[10px] text-gray-400">{{ $m->exchange ?: ucfirst($m->market) }}</p></div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $m->last_price ? number_format((float)$m->last_price, 2) : '—' }}</p>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Chart --}}
            <div class="gcard rounded-2xl p-4 order-1 lg:order-2 bg-white dark:bg-white/[0.04]">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <p class="font-bold text-lg text-gray-900 dark:text-white">{{ $selected->symbol ?? '—' }} <span class="text-xs font-normal text-gray-400">{{ $selected->name }}</span></p>
                        <p class="text-2xl font-extrabold text-gray-900 dark:text-white"><span x-text="fmt(price)"></span> <span class="text-sm" :class="change>=0?'text-emerald-500':'text-red-500'" x-text="(change>=0?'+':'')+change.toFixed(2)+'%'"></span></p>
                    </div>
                    <div class="flex gap-1 text-xs bg-gray-100 dark:bg-white/5 rounded-lg p-1">
                        <template x-for="iv in ['1min','5min','1h','1day']" :key="iv">
                            <button @click="interval=iv; loadCandles()" :class="interval===iv?'bg-emerald-600 text-white':''" class="px-2 py-1 rounded" x-text="iv"></button>
                        </template>
                    </div>
                </div>
                <canvas id="spot-chart" class="mt-3 w-full" height="300"></canvas>
            </div>

            {{-- Order book + ticket --}}
            <div class="order-3 space-y-4">
                <div class="gcard rounded-2xl p-4 bg-white dark:bg-white/[0.04]">
                    <div class="flex items-center justify-between mb-2"><h3 class="font-semibold text-sm text-gray-900 dark:text-white">Order Book</h3><span class="text-[10px] text-gray-400">price · qty</span></div>
                    <div class="space-y-0.5 text-xs font-mono">
                        <template x-for="a in book.asks.slice().reverse()" :key="'a'+a.price"><div class="flex justify-between"><span class="text-red-500" x-text="a.price.toFixed(2)"></span><span class="text-gray-400" x-text="a.qty"></span></div></template>
                    </div>
                    <div class="text-center my-1.5 py-1 rounded bg-gray-100 dark:bg-white/5 text-sm font-bold text-gray-900 dark:text-white" x-text="fmt(book.last||price)"></div>
                    <div class="space-y-0.5 text-xs font-mono">
                        <template x-for="b in book.bids" :key="'b'+b.price"><div class="flex justify-between"><span class="text-emerald-500" x-text="b.price.toFixed(2)"></span><span class="text-gray-400" x-text="b.qty"></span></div></template>
                    </div>
                </div>

                <div class="gcard rounded-2xl p-4 bg-white dark:bg-white/[0.04]">
                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <button @click="side='buy'"  :class="side==='buy'?'bg-emerald-600 text-white':'bg-gray-100 dark:bg-white/5 text-gray-500'" class="py-2 rounded-lg font-bold text-sm">BUY</button>
                        <button @click="side='sell'" :class="side==='sell'?'bg-red-600 text-white':'bg-gray-100 dark:bg-white/5 text-gray-500'" class="py-2 rounded-lg font-bold text-sm">SELL</button>
                    </div>
                    <label class="text-xs text-gray-400">Order type</label>
                    <select x-model="otype" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2 text-sm mb-2 mt-1"><option value="market">Market</option><option value="limit">Limit</option></select>
                    <div class="grid grid-cols-2 gap-2">
                        <div x-show="otype==='limit'"><label class="text-xs text-gray-400">Price</label><input x-model="oprice" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2 text-sm mt-1"></div>
                        <div :class="otype==='market' && 'col-span-2'"><label class="text-xs text-gray-400">Quantity</label><input x-model="oqty" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-2 text-sm mt-1"></div>
                    </div>
                    <button @click="submit()" :disabled="busy" :class="side==='buy'?'bg-emerald-600 hover:bg-emerald-500':'bg-red-600 hover:bg-red-500'" class="w-full py-2.5 rounded-xl text-white font-bold text-sm mt-3 disabled:opacity-60">
                        <span x-text="busy ? 'Placing…' : (side==='buy'?'Buy ':'Sell ')+'{{ $selected->symbol ?? '' }}'"></span>
                    </button>
                    <p x-show="msg" x-cloak x-text="msg" :class="ok?'text-emerald-600 dark:text-emerald-400':'text-red-600 dark:text-red-400'" class="text-xs text-center mt-2 font-medium"></p>
                </div>
            </div>
        </div>

        {{-- Holdings / open orders / trades --}}
        <div class="grid lg:grid-cols-3 gap-4 mt-4">
            <div class="gcard rounded-2xl p-4 bg-white dark:bg-white/[0.04]">
                <h3 class="font-semibold text-sm mb-2 text-gray-900 dark:text-white">Holdings</h3>
                @forelse ($holdings as $h)
                    <div class="flex justify-between text-xs py-1"><span>{{ $h->instrument->symbol }} ×{{ rtrim(rtrim((string)$h->qty,'0'),'.') }}</span><span class="text-gray-400">avg {{ number_format((float)$h->avg_price,2) }}</span></div>
                @empty <p class="text-xs text-gray-400">No holdings yet.</p> @endforelse
            </div>
            <div class="gcard rounded-2xl p-4 bg-white dark:bg-white/[0.04]">
                <h3 class="font-semibold text-sm mb-2 text-gray-900 dark:text-white">Open Orders</h3>
                @forelse ($orders as $o)
                    <div class="flex justify-between items-center text-xs py-1">
                        <span class="{{ $o->side==='buy'?'text-emerald-500':'text-red-500' }}">{{ strtoupper($o->side) }}</span>
                        <span class="flex-1 px-2 text-gray-500">{{ $o->instrument->symbol }} {{ rtrim(rtrim((string)$o->remaining(),'0'),'.') }} @ {{ $o->price ? number_format((float)$o->price,2) : 'mkt' }}</span>
                        <form method="POST" action="{{ route('spot.cancel',$o) }}">@csrf<button class="text-gray-400 hover:text-red-500">cancel</button></form>
                    </div>
                @empty <p class="text-xs text-gray-400">No open orders.</p> @endforelse
            </div>
            <div class="gcard rounded-2xl p-4 bg-white dark:bg-white/[0.04]">
                <h3 class="font-semibold text-sm mb-2 text-gray-900 dark:text-white">Trade History</h3>
                @forelse ($trades as $t)
                    @php $isBuy = $t->buyer_id === auth()->id(); @endphp
                    <div class="flex justify-between text-xs py-1"><span class="{{ $isBuy?'text-emerald-500':'text-red-500' }}">{{ $isBuy?'BUY':'SELL' }} {{ $t->instrument->symbol }} ×{{ rtrim(rtrim((string)$t->qty,'0'),'.') }}</span><span class="text-gray-400">{{ number_format((float)$t->price,2) }}</span></div>
                @empty <p class="text-xs text-gray-400">No trades yet.</p> @endforelse
            </div>
        </div>
    </div>

    <script>
        function spot(){
            return {
                id: {{ $selected->id ?? 'null' }}, price: {{ (float)($selected->last_price ?? 0) }}, change: 0,
                interval: '1day', book: {asks:[], bids:[], last:0},
                side:'buy', otype:'market', oprice:'{{ (float)($selected->last_price ?? 0) }}', oqty:'1',
                busy:false, msg:'', ok:false, _t:null,
                fmt(n){ return (n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); },
                init(){ if(!this.id) return; this.loadCandles(); this.tick(); this._t=setInterval(()=>this.tick(), 6000); },
                async tick(){
                    try{
                        const q=await (await fetch('{{ route('spot.quote') }}?id='+this.id)).json();
                        if(q.price){ this.price=q.price; this.change=q.change; }
                        const b=await (await fetch('{{ route('spot.book') }}?id='+this.id)).json();
                        this.book=b;
                    }catch(e){}
                },
                async loadCandles(){
                    try{
                        const d=await (await fetch('{{ route('spot.candles') }}?id='+this.id+'&interval='+this.interval)).json();
                        this.draw((d.values||[]).map(v=>v.close));
                    }catch(e){}
                },
                draw(pts){
                    const c=document.getElementById('spot-chart'); if(!c||!pts.length) return;
                    const dpr=window.devicePixelRatio||1, W=c.clientWidth, H=300;
                    c.width=W*dpr; c.height=H*dpr; const x=c.getContext('2d'); x.setTransform(dpr,0,0,dpr,0,0); x.clearRect(0,0,W,H);
                    const min=Math.min(...pts), max=Math.max(...pts), pad=18;
                    const px=i=>pad+i*(W-2*pad)/(pts.length-1), py=v=>H-pad-(v-min)/(max-min||1)*(H-2*pad);
                    const g=x.createLinearGradient(0,0,0,H); g.addColorStop(0,'rgba(16,185,129,.30)'); g.addColorStop(1,'rgba(16,185,129,0)');
                    x.beginPath(); x.moveTo(px(0),py(pts[0])); pts.forEach((v,i)=>x.lineTo(px(i),py(v))); x.lineTo(px(pts.length-1),H-pad); x.lineTo(px(0),H-pad); x.closePath(); x.fillStyle=g; x.fill();
                    x.beginPath(); x.moveTo(px(0),py(pts[0])); pts.forEach((v,i)=>x.lineTo(px(i),py(v))); x.strokeStyle='#10b981'; x.lineWidth=2; x.stroke();
                },
                async submit(){
                    this.busy=true; this.msg='';
                    try{
                        const res=await fetch('{{ route('spot.order') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                            body:JSON.stringify({instrument_id:this.id,side:this.side,type:this.otype,price:this.otype==='limit'?this.oprice:null,qty:this.oqty})});
                        const d=await res.json(); this.ok=res.ok&&d.ok; this.msg=d.message||'Done';
                        if(this.ok) setTimeout(()=>location.reload(), 900);
                    }catch(e){ this.ok=false; this.msg='Could not place order.'; }
                    finally{ this.busy=false; }
                },
            };
        }
    </script>
</x-client-layout>
