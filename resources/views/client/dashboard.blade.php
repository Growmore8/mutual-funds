<x-client-layout title="Dashboard">
    @php
        $money = fn ($n) => '$' . number_format((float) $n, 2);
        $at = $user->accountType;
        $dailyPct = (float) ($at->daily_return_pct ?? 0);
        $perDay = round($investment * $dailyPct / 100, 2);          // client's est. daily profit
        $monthlyEst = round($perDay * 30, 2);
        $roiMonthly = round($dailyPct * 30, 2);
        $dailyPoolProfit = round((float) ($at->pool_amount ?? 0) * $dailyPct / 100, 2);
        $pts = $chart->values();
        $n = $pts->count();
        $max = max(1.0, (float) ($pts->max('net_pnl') ?: 0));
    @endphp

    <style>
        .atm-card{background:linear-gradient(135deg,#0a1730 0%,#0f3d2e 55%,#0e7a52 100%)}
        .atm-card .shine{position:absolute;top:0;left:-60%;width:45%;height:100%;background:linear-gradient(120deg,transparent,rgba(255,255,255,.22),transparent);transform:skewX(-20deg);animation:atmshine 4.5s ease-in-out infinite}
        @keyframes atmshine{0%{left:-60%}55%,100%{left:130%}}
        .atm-card{transition:transform .25s ease}
        .atm-card:hover{transform:translateY(-3px) scale(1.01)}
    </style>

    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Welcome, {{ $user->name }}</h2>
            <p class="text-gray-500 text-sm">Here's your pool account overview.</p>
        </div>
    </div>

    {{-- Animated balance card --}}
    <div class="mb-6 max-w-md">
        <div class="atm-card relative overflow-hidden rounded-2xl p-6 text-white shadow-xl">
            <div class="shine"></div>
            <div class="relative flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <img src="/logo.png" alt="" class="w-7 h-7" onerror="this.style.display='none'">
                    <span class="font-bold text-lg">Growth<span class="text-emerald-300">Capital</span></span>
                </div>
                <i class="fa-solid fa-wifi rotate-90 opacity-70"></i>
            </div>
            <div class="relative w-12 h-9 rounded-md mt-5" style="background:linear-gradient(135deg,#f6d365,#d4af37)"></div>
            <p class="relative text-xs text-white/60 mt-4">Capital (Balance)</p>
            <p class="relative text-3xl font-bold tracking-wide">{{ $money($investment) }}</p>
            <p class="relative text-[11px] text-white/70 mt-1">PnL {{ ($runningPnl < 0 ? '-' : '+') . $money(abs($runningPnl)) }} · Withdrawable {{ $money($withdrawable) }}</p>
            <div class="relative flex items-center justify-between mt-5 text-sm">
                <span class="tracking-[0.25em] text-white/80">{{ $liveRef ? '•••• ' . substr($liveRef, -4) : '•••• ••••' }}</span>
                <span class="uppercase tracking-wide text-white/90">{{ $user->name }}</span>
            </div>
        </div>
    </div>

    {{-- Top stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm p-5 flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500">Capital (Balance)</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $money($investment) }}</p>
                <p class="text-[11px] text-gray-400 mt-1"><i class="fa-solid fa-lock text-[9px]"></i> Principal · {{ $at->name ?? 'No plan' }}</p>
            </div>
            <span class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 grid place-items-center shrink-0"><i class="fa-solid fa-wallet"></i></span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5 flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500">Running PnL</p>
                <p class="text-2xl font-bold {{ $runningPnl < 0 ? 'text-red-600' : 'text-emerald-600' }} mt-1">{{ ($runningPnl < 0 ? '-' : '+') . $money(abs($runningPnl)) }}</p>
                <p class="text-[11px] text-gray-400 mt-1">Open: <span id="live-floating" class="{{ $floatingShare < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($floatingShare < 0 ? '-' : '+') . $money(abs($floatingShare)) }}</span></p>
            </div>
            <span class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 grid place-items-center shrink-0"><i class="fa-solid fa-arrow-trend-up"></i></span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5 flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500">Withdrawable</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $money($withdrawable) }}</p>
                <p class="text-[11px] text-gray-400 mt-1">Positive PnL only</p>
            </div>
            <span class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 grid place-items-center shrink-0"><i class="fa-solid fa-money-bill-wave"></i></span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5 flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500">Your Profit Share</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ rtrim(rtrim(number_format($sharePct,2),'0'),'.') }}%</p>
                <p class="text-[11px] text-gray-400 mt-1">Of total pool profit</p>
            </div>
            <span class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 grid place-items-center shrink-0"><i class="fa-solid fa-chart-pie"></i></span>
        </div>
    </div>

    {{-- Earnings overview · How it works · Investment summary --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Your Earnings Overview</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between"><p class="text-xs text-gray-500">Today's Profit</p><span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 grid place-items-center"><i class="fa-solid fa-dollar-sign"></i></span></div>
                    <p id="live-today" class="text-lg font-bold text-gray-900 mt-2">{{ $money($today) }}</p>
                    <p class="text-[11px] text-gray-400">From today's pool profit</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between"><p class="text-xs text-gray-500">Total Earned</p><span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 grid place-items-center"><i class="fa-solid fa-sack-dollar"></i></span></div>
                    <p class="text-lg font-bold text-gray-900 mt-2">{{ $money($totalEarned) }}</p>
                    <p class="text-[11px] text-gray-400">All time earnings</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between"><p class="text-xs text-gray-500">Yesterday's Profit</p><span class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 grid place-items-center"><i class="fa-solid fa-dollar-sign"></i></span></div>
                    <p class="text-lg font-bold text-gray-900 mt-2">{{ $money($yesterday) }}</p>
                    <p class="text-[11px] text-gray-400">From yesterday's pool profit</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between"><p class="text-xs text-gray-500">This Month's Profit</p><span class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 grid place-items-center"><i class="fa-solid fa-chart-column"></i></span></div>
                    <p class="text-lg font-bold text-gray-900 mt-2">{{ $money($month) }}</p>
                    <p class="text-[11px] text-gray-400">Total profit this month</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4">How It Works</h3>
            <ol class="space-y-4">
                @php $steps = [
                    ['Pool Account', 'Total managed pool is ' . $money($at->pool_amount ?? ($pool->capacity ?? 0))],
                    ['Daily Profit', 'Pool generates up to ' . $money($dailyPoolProfit) . ' per day'],
                    ['Profit Distribution', 'Profit is shared to clients by their % share'],
                    ['Your Share', 'You receive ' . rtrim(rtrim(number_format($sharePct,2),'0'),'.') . '% of daily profit'],
                ]; @endphp
                @foreach ($steps as $i => $s)
                    <li class="flex gap-3">
                        <span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 grid place-items-center text-sm font-bold shrink-0">{{ $i + 1 }}</span>
                        <div>
                            <p class="font-medium text-gray-900 text-sm">{{ $s[0] }}</p>
                            <p class="text-xs text-gray-500">{{ $s[1] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Investment Summary</h3>
            <dl class="text-sm divide-y divide-gray-100">
                <div class="flex justify-between py-2"><dt class="text-gray-500">Pool Account Size</dt><dd class="font-semibold">{{ $money($poolsCapacity > 0 ? $poolsCapacity : ($pool->capacity ?? 0)) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Daily Pool Profit</dt><dd class="font-semibold">{{ $money($dailyPoolProfit) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500"><i class="fa-solid fa-lock text-[10px] text-gray-400"></i> Principal (locked)</dt><dd class="font-semibold">{{ $money($investment) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Your Profit Share</dt><dd class="font-semibold">{{ rtrim(rtrim(number_format($sharePct,2),'0'),'.') }}%</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Running PnL</dt><dd class="font-semibold {{ $runningPnl < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($runningPnl < 0 ? '-' : '+') . $money(abs($runningPnl)) }}</dd></div>
                <div class="flex justify-between py-2 bg-emerald-50 -mx-2 px-2 rounded"><dt class="text-gray-600">Withdrawable profit</dt><dd class="font-bold text-emerald-700">{{ $money($withdrawable) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Per Day Profit (est.)</dt><dd class="font-semibold">{{ $money($perDay) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">ROI (monthly, est.)</dt><dd class="font-semibold">{{ rtrim(rtrim(number_format($roiMonthly,2),'0'),'.') }}%</dd></div>
            </dl>
            <a href="{{ route('withdraw.create') }}" class="mt-4 block text-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold text-sm">Withdraw Profit</a>
        </div>
    </div>

    {{-- Chart · transactions · CTA --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Earnings (last 14 days)</h3>
            @if ($n)
                @php
                    $coords = [];
                    foreach ($pts as $i => $row) {
                        $x = $n > 1 ? round(($i / ($n - 1)) * 300, 1) : 150;
                        $y = round(95 - (max(0, (float) $row->net_pnl) / $max) * 80, 1);
                        $coords[] = "$x,$y";
                    }
                    $line = implode(' ', $coords);
                    $area = '0,100 ' . $line . ' 300,100';
                @endphp
                <svg viewBox="0 0 300 100" preserveAspectRatio="none" class="w-full h-32">
                    <polygon points="{{ $area }}" fill="rgba(16,185,129,0.12)"/>
                    <polyline points="{{ $line }}" fill="none" stroke="#16c784" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                </svg>
            @else
                <div class="h-32 grid place-items-center text-sm text-gray-400 text-center">No earnings yet — they appear once your deposit is approved and the pool distributes daily profit.</div>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-900">Recent Transactions</h3>
                <a href="{{ route('client.transactions') }}" class="text-xs text-emerald-600 font-medium hover:underline">View all</a>
            </div>
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recent as $t)
                        <tr>
                            <td class="py-2 text-gray-400 text-xs">{{ $t->created_at->format('d M Y') }}</td>
                            <td class="py-2 text-gray-600">{{ $t->description ?? ucfirst($t->type) }}</td>
                            <td class="py-2 text-right font-medium {{ $t->amount < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($t->amount<0?'':'+') . $money($t->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td class="py-6 text-center text-gray-400">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-[#0a1730] text-white rounded-2xl p-6 flex flex-col">
            <h3 class="font-semibold mb-2">Pool · Invest · Earn Together</h3>
            <p class="text-sm text-gray-300">You earn your share of the daily profit from the managed pool ({{ $liveRef ?? '—' }}). Profit is distributed daily by your % share, and your funds always remain under your ownership.</p>
            <div class="flex-1 grid place-items-center my-4">
                <div class="w-20 h-20 rounded-full bg-emerald-500 grid place-items-center text-3xl"><i class="fa-solid fa-dollar-sign"></i></div>
            </div>
            <p class="text-xs text-gray-400 text-center">Thank you for being part of GrowthCapital</p>
        </div>
    </div>

    <div class="mt-6 bg-blue-50 border border-blue-100 text-blue-800 text-sm rounded-xl p-3 flex items-center gap-2">
        <i class="fa-solid fa-circle-info"></i> Profits are calculated daily based on the pool performance. Returns may vary with market conditions.
    </div>

    <script>
        (function () {
            const POLL = 6000; // ms
            const fmt = (n, signed) => (signed ? (n < 0 ? '-' : '+') : '') + '$' + Math.abs(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            function animate(id, to, signed) {
                const el = document.getElementById(id); if (el == null || to == null) return;
                let from = parseFloat(el.dataset.val); if (isNaN(from)) from = to;
                el.dataset.val = to;
                if (Math.abs(to - from) < 0.005) { el.textContent = fmt(to, signed); return; }
                const dur = 800, t0 = performance.now();
                function step(t) {
                    const p = Math.min(1, (t - t0) / dur);
                    const v = from + (to - from) * p;
                    el.textContent = fmt(v, signed);
                    if (signed) { el.classList.toggle('text-red-600', v < 0); el.classList.toggle('text-emerald-600', v >= 0); }
                    if (p < 1) requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
            }
            async function tick() {
                if (document.hidden) return;
                try {
                    const res = await fetch('{{ route('client.live') }}', {headers: {'Accept': 'application/json'}});
                    if (!res.ok) return;
                    const d = await res.json();
                    if (d.poolFloating !== undefined) animate('live-pool', d.poolFloating, true);
                    if (d.floatingShare !== undefined) animate('live-floating', d.floatingShare, true);
                    if (d.today !== undefined) animate('live-today', d.today, false);
                } catch (e) {}
            }
            tick();
            setInterval(tick, POLL);
        })();
    </script>
</x-client-layout>
