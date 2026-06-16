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

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Welcome, {{ $user->name }}</h2>
        <p class="text-gray-500 text-sm">Here's your pool account overview.</p>
    </div>

    {{-- Top stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm p-5 flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500">Pool Account Size</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $money($poolsCapacity > 0 ? $poolsCapacity : ($pool->capacity ?? 0)) }}</p>
                <p class="text-[11px] text-gray-400 mt-1">Live ID: <span class="font-medium text-gray-500">{{ $liveRef ?? '—' }}</span></p>
            </div>
            <span class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 grid place-items-center shrink-0"><i class="fa-solid fa-users"></i></span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5 flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500 flex items-center gap-1">Pool P/L (Live) <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span></p>
                <p id="live-pool" class="text-2xl font-bold {{ $poolsFloating < 0 ? 'text-red-600' : 'text-emerald-600' }} mt-1">{{ ($poolsFloating < 0 ? '-' : '+') . $money(abs($poolsFloating)) }}</p>
                <p class="text-[11px] text-gray-400 mt-1">Running, updates live</p>
            </div>
            <span class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 grid place-items-center shrink-0"><i class="fa-solid fa-arrow-trend-up"></i></span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5 flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500">Your Investment</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $money($investment) }}</p>
                <p class="text-[11px] text-gray-400 mt-1">{{ $at->name ?? 'No plan' }}</p>
            </div>
            <span class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 grid place-items-center shrink-0"><i class="fa-solid fa-wallet"></i></span>
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
                    <div class="flex items-center justify-between"><p class="text-xs text-gray-500">Today's Profit</p><span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 grid place-items-center text-xs"><i class="fa-solid fa-dollar-sign"></i></span></div>
                    <p id="live-today" class="text-lg font-bold text-emerald-600 mt-2">{{ $money($today) }}</p>
                    <p class="text-[11px] text-gray-400">From today's pool profit</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between"><p class="text-xs text-gray-500">Total Earned</p><span class="w-7 h-7 rounded-full bg-blue-100 text-blue-600 grid place-items-center text-xs"><i class="fa-solid fa-sack-dollar"></i></span></div>
                    <p class="text-lg font-bold text-gray-900 mt-2">{{ $money($totalEarned) }}</p>
                    <p class="text-[11px] text-gray-400">All-time earnings</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between"><p class="text-xs text-gray-500">Open P/L (unrealized)</p><span class="w-7 h-7 rounded-full bg-amber-100 text-amber-600 grid place-items-center text-xs"><i class="fa-solid fa-coins"></i></span></div>
                    <p id="live-floating" class="text-lg font-bold {{ $floatingShare < 0 ? 'text-red-600' : 'text-emerald-600' }} mt-2">{{ ($floatingShare < 0 ? '-' : '+') . $money(abs($floatingShare)) }}</p>
                    <p class="text-[11px] text-gray-400">Your share, live</p>
                </div>
                <div class="rounded-xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between"><p class="text-xs text-gray-500">This Month</p><span class="w-7 h-7 rounded-full bg-purple-100 text-purple-600 grid place-items-center text-xs"><i class="fa-solid fa-chart-column"></i></span></div>
                    <p class="text-lg font-bold text-gray-900 mt-2">{{ $money($month) }}</p>
                    <p class="text-[11px] text-gray-400">Profit this month</p>
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
                <div class="flex justify-between py-2 bg-emerald-50 -mx-2 px-2 rounded"><dt class="text-gray-600">Your Investment</dt><dd class="font-bold text-emerald-700">{{ $money($investment) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Your Profit Share</dt><dd class="font-semibold">{{ rtrim(rtrim(number_format($sharePct,2),'0'),'.') }}%</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Per Day Profit (est.)</dt><dd class="font-semibold">{{ $money($perDay) }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Monthly Profit (est.)</dt><dd class="font-semibold">{{ $money($monthlyEst) }}</dd></div>
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
            const money = (n) => '$' + Math.abs(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            async function tick() {
                try {
                    const res = await fetch('{{ route('client.live') }}', {headers: {'Accept': 'application/json'}});
                    if (!res.ok) return;
                    const d = await res.json();
                    const set = (id, val, signed) => {
                        const el = document.getElementById(id); if (!el) return;
                        el.textContent = (signed ? (val < 0 ? '-' : '+') : '') + money(val);
                        el.classList.toggle('text-red-600', val < 0);
                        el.classList.toggle('text-emerald-600', val >= 0);
                    };
                    if (d.poolFloating !== undefined) set('live-pool', d.poolFloating, true);
                    if (d.floatingShare !== undefined) set('live-floating', d.floatingShare, true);
                    const td = document.getElementById('live-today');
                    if (td) td.textContent = '$' + Number(d.today).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                } catch (e) {}
            }
            tick();
            setInterval(tick, 30000);
        })();
    </script>
</x-client-layout>
