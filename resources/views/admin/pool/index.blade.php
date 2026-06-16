<x-admin-layout title="Pool">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-gray-500">
            Data source:
            <span class="px-2 py-0.5 rounded-full text-xs {{ $isLive ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                {{ $isLive ? 'LIVE CubeX API' : 'SIMULATED (set POOL_API_URL in .env for live data)' }}
            </span>
            <span class="ml-2 inline-flex items-center gap-1 text-xs text-gray-400">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                live <span id="live-at">—</span>
            </span>
        </p>
        <form method="POST" action="{{ route('admin.pool.sync') }}">@csrf
            <button class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm">Sync &amp; distribute</button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Add pool account --}}
        <div class="bg-white shadow rounded-xl p-6 lg:col-span-1">
            <h3 class="font-semibold text-gray-900 mb-3">Add pool account</h3>
            <p class="text-xs text-gray-500 mb-3">Use the real account ID from CubeX (e.g. 800120).</p>
            <form method="POST" action="{{ route('admin.pool.store') }}" class="space-y-3 text-sm">
                @csrf
                <div><label class="block text-gray-700">CubeX Account ID</label><input name="account_ref" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                <div><label class="block text-gray-700">Name (optional)</label><input name="name" class="mt-1 w-full border-gray-300 rounded-md"></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="block text-gray-700">Capacity</label><input type="number" step="0.01" name="capacity" value="0" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                    <div><label class="block text-gray-700">Currency</label><input name="currency" value="USD" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                </div>
                <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_active" value="1" checked class="rounded"> Active</label>
                <button class="block px-4 py-2 bg-emerald-600 text-white rounded-md">Add pool</button>
            </form>
        </div>

        {{-- Pool accounts list --}}
        <div class="bg-white shadow rounded-xl overflow-hidden lg:col-span-2">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-left">
                    <tr><th class="px-4 py-3">Account</th><th class="px-4 py-3">Capacity</th><th class="px-4 py-3">Balance</th><th class="px-4 py-3">Floating P/L</th><th class="px-4 py-3">Synced</th><th class="px-4 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pools as $p)
                        <tr>
                            <td class="px-4 py-3"><div class="font-medium text-gray-900">{{ $p->account_ref }}</div><div class="text-gray-400">{{ $p->name }}</div></td>
                            <td class="px-4 py-3">${{ number_format((float)$p->capacity) }}</td>
                            <td class="px-4 py-3" data-pool-balance="{{ $p->id }}">${{ number_format((float)$p->balance,2) }}</td>
                            <td class="px-4 py-3 {{ (float)$p->floating_pnl < 0 ? 'text-red-600' : 'text-green-600' }}" data-pool-float="{{ $p->id }}">{{ ((float)$p->floating_pnl < 0 ? '' : '+') . '$' . number_format((float)$p->floating_pnl,2) }}</td>
                            <td class="px-4 py-3 text-gray-400">{{ $p->last_synced_at?->diffForHumans() ?? 'never' }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.pool.destroy',$p) }}" class="text-right" onsubmit="return confirm('Delete this pool account?')">@csrf @method('DELETE')
                                    <button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No pool accounts. Add your CubeX account on the left.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function () {
            const POLL = 6000;
            const fmt = (n, signed) => (signed ? (n < 0 ? '-' : '+') : '') + '$' + Math.abs(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            function animate(el, to, signed) {
                if (!el || to == null) return;
                let from = parseFloat(el.dataset.val); if (isNaN(from)) from = to;
                el.dataset.val = to;
                if (Math.abs(to - from) < 0.005) { el.textContent = fmt(to, signed); return; }
                const dur = 800, t0 = performance.now();
                function step(t) {
                    const p = Math.min(1, (t - t0) / dur);
                    const v = from + (to - from) * p;
                    el.textContent = fmt(v, signed);
                    if (signed) { el.classList.toggle('text-red-600', v < 0); el.classList.toggle('text-green-600', v >= 0); }
                    if (p < 1) requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
            }
            async function tick() {
                if (document.hidden) return;
                try {
                    const res = await fetch('{{ route('admin.pool.live') }}', {headers: {'Accept': 'application/json'}});
                    if (!res.ok) return;
                    const json = await res.json();
                    (json.data || []).forEach((p) => {
                        animate(document.querySelector('[data-pool-float="' + p.id + '"]'), Number(p.floating), true);
                        animate(document.querySelector('[data-pool-balance="' + p.id + '"]'), Number(p.balance), false);
                    });
                    const at = document.getElementById('live-at');
                    if (at) at.textContent = json.at;
                } catch (e) {}
            }
            tick();
            setInterval(tick, POLL);
        })();
    </script>
</x-admin-layout>
