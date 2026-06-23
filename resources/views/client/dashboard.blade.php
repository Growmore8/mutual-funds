<x-client-layout title="Dashboard">
    @php
        $money = fn ($n) => '$' . number_format((float) $n, 2);
        $at = $at ?? $user->accountType;
        $dailyPct = (float) ($at->daily_return_pct ?? 0);
        $perDay = round($investment * $dailyPct / 100, 2);          // client's est. daily profit
        $monthlyEst = round($perDay * 30, 2);
        $roiMonthly = round($dailyPct * 30, 2);
        $dailyPoolProfit = round((float) ($at->pool_amount ?? 0) * $dailyPct / 100, 2);
        $pts = $chart->values();
        $n = $pts->count();
        $vmax = (float) ($pts->max('net_pnl') ?? 0);
        $vmin = (float) ($pts->min('net_pnl') ?? 0);
        $vrange = max(0.01, $vmax - $vmin);
        $trendUp = $n > 1 ? ((float) $pts->last()->net_pnl >= (float) $pts->first()->net_pnl) : true;
        $lineColor = $trendUp ? '#10b981' : '#ef4444';
    @endphp

    <style>
        .atm-card{background:linear-gradient(135deg,#0a1730 0%,#0f3d2e 55%,#0e7a52 100%)}
        .atm-card .shine{position:absolute;top:0;left:-60%;width:45%;height:100%;background:linear-gradient(120deg,transparent,rgba(255,255,255,.18),transparent);transform:skewX(-20deg);animation:atmshine 6s ease-in-out infinite}
        @keyframes atmshine{0%{left:-60%}55%,100%{left:130%}}
        .glow{text-shadow:0 0 18px rgba(16,185,129,.45)}
        .glow-red{text-shadow:0 0 18px rgba(239,68,68,.4)}
        .gcard{transition:transform .2s ease, border-color .2s ease}
        .gcard:hover{transform:translateY(-2px)}
    </style>

    {{-- Admin-managed popup (maintenance / notice / offer / promotion) --}}
    @if (!empty($announcement))
        @php
            $hero = [
                'maintenance' => 'from-rose-500 to-red-700',
                'notice' => 'from-sky-500 to-indigo-700',
                'offer' => 'from-amber-400 to-orange-600',
                'promotion' => 'from-emerald-400 to-teal-700',
            ][$announcement->type] ?? 'from-emerald-400 to-teal-700';
            $heroIcon = ['maintenance'=>'fa-screwdriver-wrench','notice'=>'fa-circle-info','offer'=>'fa-tags','promotion'=>'fa-bullhorn'][$announcement->type] ?? 'fa-bullhorn';
        @endphp
        <div x-data="{ open:false, freq:@js($announcement->frequency), key:'ann_{{ $announcement->id }}',
                init(){ try{ const v=localStorage.getItem(this.key), t=new Date().toDateString(); let show=true;
                        if(this.freq==='once') show=!v; else if(this.freq==='daily') show=(v!==t);
                        if(show) setTimeout(()=>{ this.open=true; }, 500); }catch(e){ this.open=true; } },
                seal(){ this.open=false; try{ localStorage.setItem(this.key, this.freq==='once'?'seen':new Date().toDateString()); }catch(e){} } }">
            <template x-teleport="body">
                <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[70] grid place-items-center p-5">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="seal()"></div>
                    <div x-show="open" x-transition.scale.origin.center class="relative w-full max-w-sm rounded-3xl overflow-hidden shadow-2xl bg-white dark:bg-[#0f1b38]">
                        <div class="relative bg-gradient-to-br {{ $hero }} px-6 pt-7 pb-6 text-white text-center">
                            <button @click="seal()" class="absolute top-3 right-3 w-9 h-9 rounded-full bg-white/20 hover:bg-white/30 grid place-items-center text-white z-10"><i class="fa-solid fa-xmark"></i></button>
                            @if ($announcement->image_url)
                                <img src="{{ $announcement->image_url }}" alt="" class="w-full h-32 object-cover rounded-xl mb-3" onerror="this.style.display='none'">
                            @else
                                <div class="mx-auto w-16 h-16 rounded-2xl bg-white/15 grid place-items-center mb-3"><i class="fa-solid {{ $heroIcon }} text-3xl"></i></div>
                            @endif
                            <h3 class="text-xl font-extrabold">{{ $announcement->title }}</h3>
                        </div>
                        <div class="p-5 text-center">
                            @if ($announcement->body)<p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $announcement->body }}</p>@endif
                            <div class="mt-4 flex gap-2">
                                <button @click="seal()" class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300 text-sm font-medium">Close</button>
                                @if ($announcement->cta_url && $announcement->cta_label)
                                    <a href="{{ $announcement->cta_url }}" @click="seal()" class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold text-center">{{ $announcement->cta_label }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endif

    {{-- Welcome --}}
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome, {{ $user->name }}</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $user->email }} · <span class="font-mono">{{ $account?->code() ?? $user->clientCode() }}</span></p>
        </div>
    </div>

    {{-- Balance + performance chart (exchange-style hero) --}}
    @php
        $hcoords = [];
        foreach ($pts as $i => $row) {
            $hx = $n > 1 ? round(($i / ($n - 1)) * 600, 1) : 300;
            // Scale between the series min and max so the line rises AND falls.
            $hy = round(110 - (((float) $row->net_pnl - $vmin) / $vrange) * 90, 1);
            $hcoords[] = "$hx,$hy";
        }
        $hline = implode(' ', $hcoords);
        $harea = $n ? ('0,120 ' . $hline . ' 600,120') : '';
    @endphp
    <div class="mb-6 rounded-2xl p-6 bg-white dark:bg-white/[0.04] shadow-sm border border-transparent dark:border-white/[0.06] dark:backdrop-blur">
        @php $tp = $totalPnlUsd ?? 0; @endphp
        <div class="flex items-start justify-between gap-4"
             x-data="{
                show: true, curOpen: false, cur: 'USD',
                base: {{ $tp }},
                rates: @js($fxRates ?? ['USD' => 1]),
                get codes(){ return Object.keys(this.rates).filter(c => c!=='USD').sort(); },
                conv(){ return this.base * (this.rates[this.cur] || 1); },
                fmt(){ const v = this.conv(); return (v < 0 ? '-' : '+') + Math.abs(v).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}); }
             }">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">Total Portfolio P&L <span class="text-[10px]">all accounts</span>
                    <button type="button" @click="show=!show" class="text-gray-400 hover:text-emerald-400"><i class="fa-regular" :class="show?'fa-eye':'fa-eye-slash'"></i></button>
                </p>
                <p class="text-4xl font-bold mt-1 tracking-tight glow {{ $tp < 0 ? 'text-red-500' : 'text-emerald-500' }}">
                    <span x-show="show" x-text="fmt()">{{ ($tp < 0 ? '-' : '+') . $money(abs($tp)) }}</span>
                    <span x-show="!show" style="display:none">••••••</span>
                    {{-- Binance-style currency switcher --}}
                    <button type="button" @click="curOpen=true" class="text-base font-medium text-gray-400 hover:text-gray-200 inline-flex items-center gap-1 align-middle">
                        <span x-text="cur"></span><i class="fa-solid fa-chevron-down text-xs"></i>
                    </button>
                </p>

                {{-- Select a Currency sheet --}}
                <template x-teleport="body">
                    <div x-show="curOpen" x-cloak style="display:none" class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center">
                        <div class="absolute inset-0 bg-black/50" @click="curOpen=false"></div>
                        <div class="relative w-full sm:max-w-md bg-white dark:bg-[#0a1730] rounded-t-2xl sm:rounded-2xl shadow-xl p-5 max-h-[80vh] overflow-y-auto">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Select a Currency</h3>
                                <button @click="curOpen=false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
                            </div>
                            <p class="text-xs text-gray-400 mb-2">Most Used</p>
                            <button @click="cur='USD'; curOpen=false" :class="cur==='USD' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-200'" class="px-4 py-2 rounded-full border text-sm font-medium mb-4 inline-flex items-center gap-1">
                                USD <i x-show="cur==='USD'" class="fa-solid fa-check text-xs"></i>
                            </button>
                            <p class="text-xs text-gray-400 mb-2">More</p>
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="c in codes" :key="c">
                                    <button @click="cur=c; curOpen=false" :class="cur===c ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-700 dark:text-gray-200'" class="py-2.5 rounded-lg text-sm font-medium" x-text="c"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
                <p class="text-sm mt-1 text-gray-500 dark:text-gray-400">Floating P&L
                    <span id="live-floating-hero" class="font-semibold {{ $floatingShare < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ ($floatingShare < 0 ? '-' : '+') . $money(abs($floatingShare)) }}</span>
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse align-middle"></span>
                </p>
            </div>
            <a href="{{ route('withdraw.create') }}" class="shrink-0 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Withdraw</a>
        </div>

        @if ($n)
            <svg viewBox="0 0 600 120" preserveAspectRatio="none" class="w-full h-36 mt-4">
                <defs>
                    <linearGradient id="gcfill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="{{ $lineColor }}" stop-opacity="0.30"/>
                        <stop offset="100%" stop-color="{{ $lineColor }}" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                <polygon points="{{ $harea }}" fill="url(#gcfill)"/>
                <polyline points="{{ $hline }}" fill="none" stroke="{{ $lineColor }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
            </svg>
        @else
            <div class="h-36 mt-4 grid place-items-center text-sm text-gray-400 text-center">Your performance chart appears once the pool starts distributing daily profit.</div>
        @endif
        <p class="text-xs text-gray-400 mt-2">Last updated: {{ now()->format('Y-m-d H:i') }} · Running P&amp;L movements</p>
    </div>

    {{-- Spot Trading — separate section --}}
    <div class="flex items-center justify-between mb-2 mt-2">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><i class="fa-solid fa-arrow-trend-up text-blue-500 mr-1"></i> Spot Trading</h3>
        <a href="{{ route('spot.index') }}" class="text-xs text-emerald-500">Open →</a>
    </div>
    <div class="grid grid-cols-2 gap-3 mb-6">
        <a href="{{ route('spot.index') }}" class="gcard rounded-2xl p-4 bg-white dark:bg-white/[0.04] block">
            <p class="text-xs text-gray-500 dark:text-gray-400">Spot wallet <span class="text-[10px]">USD</span></p>
            <p class="text-lg font-extrabold text-gray-900 dark:text-white mt-0.5">${{ number_format($spotUsd ?? 0, 2) }}</p>
            <p class="text-[11px] {{ ($spotPnl ?? 0) < 0 ? 'text-red-500' : 'text-emerald-500' }}">P&L {{ (($spotPnl ?? 0) < 0 ? '-' : '+') . '$' . number_format(abs($spotPnl ?? 0), 2) }}</p>
        </a>
        <a href="{{ route('spot.index') }}" class="gcard rounded-2xl p-4 bg-white dark:bg-white/[0.04] block">
            <p class="text-xs text-gray-500 dark:text-gray-400">Est. spot value <span class="text-[10px]">USD</span></p>
            <p class="text-lg font-extrabold text-gray-900 dark:text-white mt-0.5">${{ number_format($spotEquity ?? 0, 2) }}</p>
            <p class="text-[11px] text-gray-400">wallet + holdings</p>
        </a>
    </div>
    <a href="{{ route('transfer.create') }}" class="block mb-6 gcard rounded-2xl p-3 bg-white dark:bg-white/[0.04] text-center text-sm font-semibold text-emerald-600 dark:text-emerald-400"><i class="fa-solid fa-right-left mr-1"></i> Transfer between Mutual Fund &amp; Spot</a>

    {{-- Mutual Fund — separate section (details below) --}}
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2"><i class="fa-solid fa-layer-group text-emerald-500 mr-1"></i> Mutual Fund</h3>

    @php
        $card = 'gcard bg-white dark:bg-white/[0.04] rounded-2xl shadow-sm dark:shadow-none border border-transparent dark:border-white/[0.06] dark:backdrop-blur';
        $sub  = 'text-gray-400 dark:text-gray-500';
        $lbl  = 'text-gray-500 dark:text-gray-400';
        $head = 'font-semibold text-gray-900 dark:text-white';
    @endphp

    {{-- Top stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="{{ $card }} p-5 flex items-start justify-between">
            <div>
                <p class="text-xs {{ $lbl }}">Total Deposit</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $money($investment) }}</p>
                <p class="text-[11px] {{ $sub }} mt-1"><i class="fa-solid fa-lock text-[9px]"></i> Principal · {{ $at->name ?? 'No plan' }}</p>
            </div>
            <span class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 dark:bg-purple-500/15 dark:text-purple-300 grid place-items-center shrink-0"><i class="fa-solid fa-wallet"></i></span>
        </div>
        <div class="{{ $card }} p-5 flex items-start justify-between">
            <div>
                <p class="text-xs {{ $lbl }} flex items-center gap-1">Floating PnL <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span></p>
                <p id="live-floating" class="text-2xl font-bold {{ $floatingShare < 0 ? 'text-red-600 dark:text-red-400 glow-red' : 'text-emerald-600 dark:text-emerald-400 glow' }} mt-1">{{ ($floatingShare < 0 ? '-' : '+') . $money(abs($floatingShare)) }}</p>
                <p class="text-[11px] {{ $sub }} mt-1">Current open P/L · live</p>
            </div>
            <span class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300 grid place-items-center shrink-0"><i class="fa-solid fa-arrow-trend-up"></i></span>
        </div>
        <div class="{{ $card }} p-5 flex items-start justify-between">
            <div>
                <p class="text-xs {{ $lbl }}">Balance (withdrawable)</p>
                <p class="text-2xl font-bold {{ $runningPnl < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }} mt-1">{{ ($runningPnl < 0 ? '-' : '') . $money(abs($runningPnl)) }}</p>
                <p class="text-[11px] {{ $sub }} mt-1">PnL − withdrawals</p>
            </div>
            <span class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300 grid place-items-center shrink-0"><i class="fa-solid fa-money-bill-wave"></i></span>
        </div>
        <div class="{{ $card }} p-5 flex items-start justify-between">
            <div>
                <p class="text-xs {{ $lbl }}">Your Profit Share</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ rtrim(rtrim(number_format($sharePct,2),'0'),'.') }}%</p>
                <p class="text-[11px] {{ $sub }} mt-1">Of total pool profit</p>
            </div>
            <span class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300 grid place-items-center shrink-0"><i class="fa-solid fa-chart-pie"></i></span>
        </div>
    </div>

    {{-- Earnings overview · How it works · Investment summary --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="{{ $card }} p-6">
            <h3 class="{{ $head }} mb-4">Your Earnings Overview</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-gray-100 dark:border-white/[0.06] p-4">
                    <div class="flex items-center justify-between"><p class="text-xs {{ $lbl }}">Today's Profit</p><span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300 grid place-items-center"><i class="fa-solid fa-dollar-sign"></i></span></div>
                    <p id="live-today" class="text-lg font-bold text-gray-900 dark:text-white mt-2">{{ $money($today) }}</p>
                    <p class="text-[11px] {{ $sub }}">From today's pool profit</p>
                </div>
                <div class="rounded-xl border border-gray-100 dark:border-white/[0.06] p-4">
                    <div class="flex items-center justify-between"><p class="text-xs {{ $lbl }}">Total Earned</p><span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300 grid place-items-center"><i class="fa-solid fa-sack-dollar"></i></span></div>
                    <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">{{ $money($totalEarned) }}</p>
                    <p class="text-[11px] {{ $sub }}">All time earnings</p>
                </div>
                <div class="rounded-xl border border-gray-100 dark:border-white/[0.06] p-4">
                    <div class="flex items-center justify-between"><p class="text-xs {{ $lbl }}">Yesterday's Profit</p><span class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300 grid place-items-center"><i class="fa-solid fa-dollar-sign"></i></span></div>
                    <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">{{ $money($yesterday) }}</p>
                    <p class="text-[11px] {{ $sub }}">From yesterday's pool profit</p>
                </div>
                <div class="rounded-xl border border-gray-100 dark:border-white/[0.06] p-4">
                    <div class="flex items-center justify-between"><p class="text-xs {{ $lbl }}">This Month's Profit</p><span class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 dark:bg-purple-500/15 dark:text-purple-300 grid place-items-center"><i class="fa-solid fa-chart-column"></i></span></div>
                    <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">{{ $money($month) }}</p>
                    <p class="text-[11px] {{ $sub }}">Total profit this month</p>
                </div>
                <div class="rounded-xl border border-gray-100 dark:border-white/[0.06] p-4 col-span-2">
                    <div class="flex items-center justify-between"><p class="text-xs {{ $lbl }}">Referral earnings</p><span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300 grid place-items-center"><i class="fa-solid fa-gift"></i></span></div>
                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400 mt-2">{{ $money($referralEarned) }}</p>
                    <p class="text-[11px] {{ $sub }}">1% of referrals' deposits · withdrawable</p>
                </div>
            </div>
        </div>

        <div class="{{ $card }} p-6">
            <h3 class="{{ $head }} mb-4">How It Works</h3>
            <ol class="space-y-4">
                @php $steps = [
                    ['Pool Account', 'Total managed pool is ' . $money($at->pool_amount ?? ($pool->capacity ?? 0))],
                    ['Daily Profit', 'Pool generates up to ' . $money($dailyPoolProfit) . ' per day'],
                    ['Profit Distribution', 'Profit is shared to clients by their % share'],
                    ['Your Share', 'You receive ' . rtrim(rtrim(number_format($sharePct,2),'0'),'.') . '% of daily profit'],
                ]; @endphp
                @foreach ($steps as $i => $s)
                    <li class="flex gap-3">
                        <span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 grid place-items-center text-sm font-bold shrink-0">{{ $i + 1 }}</span>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ $s[0] }}</p>
                            <p class="text-xs {{ $lbl }}">{{ $s[1] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="{{ $card }} p-6">
            <h3 class="{{ $head }} mb-4">Investment Summary</h3>
            <dl class="text-sm divide-y divide-gray-100 dark:divide-white/10">
                <div class="flex justify-between py-2"><dt class="{{ $lbl }}">Pool Account Size</dt><dd class="font-semibold dark:text-gray-100">{{ $money($poolsCapacity > 0 ? $poolsCapacity : ($pool->capacity ?? 0)) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="{{ $lbl }}">Daily pool profit (est.)</dt><dd class="font-semibold dark:text-gray-100">{{ $money($dailyPoolProfit) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="{{ $lbl }}"><i class="fa-solid fa-lock text-[10px] text-gray-400"></i> Principal (locked)</dt><dd class="font-semibold dark:text-gray-100">{{ $money($investment) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="{{ $lbl }}">Your Profit Share</dt><dd class="font-semibold dark:text-gray-100">{{ rtrim(rtrim(number_format($sharePct,2),'0'),'.') }}%</dd></div>
                <div class="flex justify-between py-2"><dt class="{{ $lbl }}">Running PnL <span class="text-[10px] text-gray-400">(live)</span></dt><dd id="live-running-pnl" data-val="{{ $floatingShare }}" class="font-semibold {{ $floatingShare < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ ($floatingShare < 0 ? '-' : '+') . $money(abs($floatingShare)) }}</dd></div>
                <div class="flex justify-between py-2 bg-emerald-50 dark:bg-emerald-500/10 -mx-2 px-2 rounded"><dt class="text-gray-600 dark:text-emerald-200">Withdrawable profit</dt><dd class="font-bold text-emerald-700 dark:text-emerald-300">{{ $money($withdrawable) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="{{ $lbl }}">Per Day Profit (est.)</dt><dd class="font-semibold dark:text-gray-100">{{ $money($perDay) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="{{ $lbl }}">ROI (monthly, est.)</dt><dd class="font-semibold dark:text-gray-100">{{ rtrim(rtrim(number_format($roiMonthly,2),'0'),'.') }}%</dd></div>
            </dl>
            <a href="{{ route('withdraw.create') }}" class="mt-4 block text-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold text-sm">Withdraw Profit</a>
        </div>
    </div>

    {{-- Transactions · CTA --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <div class="{{ $card }} p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="{{ $head }}">Recent Transactions</h3>
                <a href="{{ route('client.transactions') }}" class="text-xs text-emerald-600 dark:text-emerald-400 font-medium hover:underline">View all</a>
            </div>
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($recent as $t)
                        <tr>
                            <td class="py-2 {{ $sub }} text-xs">{{ $t->when->format('d M Y') }}</td>
                            <td class="py-2 text-gray-600 dark:text-gray-300">{{ $t->label }}</td>
                            <td class="py-2 text-right font-medium {{ $t->amount < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ ($t->amount<0?'-':'+') . $t->cs . number_format(abs((float)$t->amount),2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="py-6 text-center {{ $sub }}">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-[#0a1730] text-white rounded-2xl p-6 flex flex-col ring-1 ring-white/10">
            <h3 class="font-semibold mb-2">Pool · Invest · Earn Together</h3>
            <p class="text-sm text-gray-300">You earn your share of the daily profit from the managed pool ({{ $liveRef ?? '—' }}). Profit is distributed daily by your % share, and your funds always remain under your ownership.</p>
            <div class="flex-1 grid place-items-center my-4">
                <div class="w-20 h-20 rounded-full bg-emerald-500 grid place-items-center text-3xl shadow-lg shadow-emerald-500/30"><i class="fa-solid fa-dollar-sign"></i></div>
            </div>
            <p class="text-xs text-gray-400 text-center">Thank you for being part of GrowthCapital</p>
        </div>
    </div>

    <div class="mt-6 bg-blue-50 dark:bg-blue-500/10 border border-blue-100 dark:border-blue-500/20 text-blue-800 dark:text-blue-200 text-sm rounded-xl p-3 flex items-center gap-2">
        <i class="fa-solid fa-circle-info"></i> Profits are calculated daily based on the pool performance. Returns may vary with market conditions.
    </div>

    <script>
        (function () {
            const POLL = 4000; // ms — fetch latest value
            const fmt = (n, signed) => (signed ? (n < 0 ? '-' : '+') : '') + '$' + Math.abs(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

            // Registry of live values; a single rAF loop eases each toward its target.
            const reg = {};
            function track(id, target, signed) {
                if (target == null) return;
                const el = document.getElementById(id); if (!el) return;
                if (!reg[id]) {
                    let cur = parseFloat(el.dataset.val); if (isNaN(cur)) cur = target;
                    reg[id] = {el, cur, target, signed};
                } else {
                    reg[id].target = target; reg[id].signed = signed;
                }
                el.dataset.val = target;
                kick();
            }

            // rAF easing: syncs to the display, auto-pauses when the tab is hidden,
            // and does NO DOM writes once every value has reached its target.
            let raf = null;
            function frame() {
                raf = null;
                let moving = false;
                for (const id in reg) {
                    const o = reg[id];
                    const diff = o.target - o.cur;
                    if (Math.abs(diff) < 0.01) { if (o.cur !== o.target) { o.cur = o.target; } else continue; }
                    else { o.cur += diff * 0.18; moving = true; }
                    o.el.textContent = fmt(o.cur, o.signed);
                    if (o.signed) { o.el.classList.toggle('text-red-600', o.cur < 0); o.el.classList.toggle('dark:text-red-400', o.cur < 0); o.el.classList.toggle('text-emerald-600', o.cur >= 0); o.el.classList.toggle('dark:text-emerald-400', o.cur >= 0); }
                }
                if (moving && !document.hidden) raf = requestAnimationFrame(frame);
            }
            function kick() { if (!raf && !document.hidden) raf = requestAnimationFrame(frame); }
            document.addEventListener('visibilitychange', () => { if (!document.hidden) kick(); });

            async function tick() {
                if (document.hidden) return;
                try {
                    const res = await fetch('{{ route('client.live') }}', {headers: {'Accept': 'application/json'}});
                    if (!res.ok) return;
                    const d = await res.json();
                    if (d.poolFloating !== undefined) track('live-pool', d.poolFloating, true);
                    if (d.floatingShare !== undefined) { track('live-floating', d.floatingShare, true); track('live-floating-hero', d.floatingShare, true); track('live-running-pnl', d.floatingShare, true); }
                    if (d.today !== undefined) track('live-today', d.today, false);
                } catch (e) {}
            }
            tick();
            setInterval(tick, POLL);
        })();
    </script>
</x-client-layout>
