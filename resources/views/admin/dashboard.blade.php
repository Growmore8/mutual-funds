<x-admin-layout title="Dashboard">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    {{-- Mutual Fund --}}
    <h3 class="text-sm font-semibold text-gray-500 mb-2"><i class="fa-solid fa-layer-group text-emerald-500 mr-1"></i> Mutual Fund</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        <a href="{{ route('admin.clients.index') }}" class="bg-white shadow rounded-xl p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-users text-gray-400 mr-1"></i> Total clients</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $clients }}</p>
        </a>
        <a href="{{ route('admin.deposits.index') }}" class="bg-white shadow rounded-xl p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-arrow-down-to-bracket text-gray-400 mr-1"></i> Total deposits</p>
            <p class="text-3xl font-bold text-emerald-600 mt-1">{{ $money($totalDeposits) }}</p>
        </a>
        <a href="{{ route('admin.withdrawals.index') }}" class="bg-white shadow rounded-xl p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-money-bill-transfer text-gray-400 mr-1"></i> Total withdrawals</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $money($totalWithdrawals) }}</p>
        </a>
        <a href="{{ route('admin.pool.index') }}" class="bg-white shadow rounded-xl p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-layer-group text-gray-400 mr-1"></i> Pool accounts</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $poolCount }}</p>
        </a>
        <a href="{{ route('admin.deposits.index') }}" class="bg-white shadow rounded-xl p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-inbox text-gray-400 mr-1"></i> Requests pending</p>
            <p class="text-3xl font-bold text-amber-600 mt-1">{{ $pendingRequests }}</p>
            <p class="text-[11px] text-gray-400 mt-1">KYC {{ $pendingKyc }} · Dep {{ $pendingDeposits }} · Wd {{ $pendingWithdrawals }} · Acc {{ $pendingAccountRequests }}</p>
        </a>
    </div>

    {{-- Spot Trading --}}
    <h3 class="text-sm font-semibold text-gray-500 mb-2 mt-6"><i class="fa-solid fa-arrow-trend-up text-blue-500 mr-1"></i> Spot Trading</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="{{ route('admin.spot.index') }}" class="bg-white shadow rounded-xl p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-dollar-sign text-gray-400 mr-1"></i> Spot wallets · USD</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">${{ number_format($spotUsdTotal ?? 0, 2) }}</p>
        </a>
        <a href="{{ route('admin.spot.index') }}" class="bg-white shadow rounded-xl p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-indian-rupee-sign text-gray-400 mr-1"></i> Spot wallets · INR</p>
            <p class="text-3xl font-bold text-orange-600 mt-1">₹{{ number_format($spotInrTotal ?? 0, 2) }}</p>
        </a>
        <a href="{{ route('admin.spot.index') }}" class="bg-white shadow rounded-xl p-5 hover:shadow-md transition">
            <p class="text-sm text-gray-500"><i class="fa-solid fa-users text-gray-400 mr-1"></i> Spot traders</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $spotTraders ?? 0 }}</p>
        </a>
    </div>

    {{-- Pool-account PnL growth chart (last 14 days) --}}
    <div class="bg-white shadow rounded-xl p-6 mt-6" x-data="poolChart()">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="font-semibold text-gray-900">Pool PnL growth</h3>
                <p class="text-xs text-gray-400">Cumulative closed PnL · last 14 days</p>
            </div>
            <select x-model="sel" @change="draw()" class="border-gray-300 rounded-md text-sm">
                <option value="all">All pools (combined)</option>
                @foreach ($chartSeries as $s)
                    <option value="{{ $s['id'] }}">{{ $s['ref'] }}{{ $s['name'] ? ' · ' . $s['name'] : '' }}</option>
                @endforeach
            </select>
        </div>

        <div class="relative">
            <svg viewBox="0 0 600 200" preserveAspectRatio="none" class="w-full h-56">
                <defs>
                    <linearGradient id="poolfill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="rgba(16,185,129,.25)"/>
                        <stop offset="100%" stop-color="rgba(16,185,129,0)"/>
                    </linearGradient>
                </defs>
                <polygon :points="area" fill="url(#poolfill)"></polygon>
                <polyline :points="line" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"></polyline>
            </svg>
        </div>
        <div class="flex justify-between text-[11px] text-gray-400 mt-2">
            <span>{{ $chartLabels->first() }}</span>
            <span x-text="'High: ' + hi + '  ·  Low: ' + lo"></span>
            <span>{{ $chartLabels->last() }}</span>
        </div>
    </div>

    <script>
        function poolChart() {
            const series = @json($chartSeries);
            const W = 600, H = 200, PAD = 8;
            return {
                sel: 'all', line: '', area: '', hi: '0', lo: '0',
                init() { this.draw(); },
                points() {
                    if (this.sel === 'all') {
                        const n = series.length ? series[0].points.length : 0;
                        const sum = Array(n).fill(0);
                        series.forEach(s => s.points.forEach((v, i) => sum[i] += Number(v)));
                        return sum;
                    }
                    const s = series.find(x => String(x.id) === String(this.sel));
                    return s ? s.points.map(Number) : [];
                },
                draw() {
                    const pts = this.points();
                    if (!pts.length) { this.line = ''; this.area = ''; return; }
                    const max = Math.max(...pts), min = Math.min(...pts);
                    const range = (max - min) || 1;
                    this.hi = '$' + max.toLocaleString(); this.lo = '$' + min.toLocaleString();
                    const coords = pts.map((v, i) => {
                        const x = pts.length > 1 ? (i / (pts.length - 1)) * W : W / 2;
                        const y = PAD + (1 - (v - min) / range) * (H - PAD * 2);
                        return x.toFixed(1) + ',' + y.toFixed(1);
                    });
                    this.line = coords.join(' ');
                    this.area = '0,' + H + ' ' + this.line + ' ' + W + ',' + H;
                },
            };
        }
    </script>
</x-admin-layout>
