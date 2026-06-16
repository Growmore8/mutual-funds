<x-admin-layout title="Pool / PnL">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-gray-500">
            Data source:
            <span class="px-2 py-0.5 rounded-full text-xs {{ $isLive ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                {{ $isLive ? 'LIVE CubeX API' : 'SIMULATED (set POOL_API_URL in .env for live data)' }}
            </span>
        </p>
        <form method="POST" action="{{ route('admin.pool.sync') }}">@csrf
            <button class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm">Sync now</button>
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
                    <tr><th class="px-4 py-3">Account</th><th class="px-4 py-3">Capacity</th><th class="px-4 py-3">Balance</th><th class="px-4 py-3">Synced</th><th class="px-4 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pools as $p)
                        <tr>
                            <td class="px-4 py-3"><div class="font-medium text-gray-900">{{ $p->account_ref }}</div><div class="text-gray-400">{{ $p->name }}</div></td>
                            <td class="px-4 py-3">${{ number_format((float)$p->capacity) }}</td>
                            <td class="px-4 py-3">${{ number_format((float)$p->balance,2) }}</td>
                            <td class="px-4 py-3 text-gray-400">{{ $p->last_synced_at?->diffForHumans() ?? 'never' }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.pool.destroy',$p) }}" class="text-right" onsubmit="return confirm('Delete this pool account?')">@csrf @method('DELETE')
                                    <button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No pool accounts. Add your CubeX account on the left.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Snapshots --}}
    <h3 class="font-semibold text-gray-900 mt-8 mb-3">Recent daily snapshots</h3>
    <div class="bg-white shadow rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Pool</th><th class="px-4 py-3">PnL</th><th class="px-4 py-3">%</th><th class="px-4 py-3">Distributed</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($snapshots as $s)
                    <tr>
                        <td class="px-4 py-3">{{ $s->snapshot_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $s->poolAccount->account_ref ?? '—' }}</td>
                        <td class="px-4 py-3 {{ $s->pnl < 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format((float)$s->pnl,2) }}</td>
                        <td class="px-4 py-3">{{ number_format((float)$s->pnl_pct,2) }}%</td>
                        <td class="px-4 py-3">{!! $s->distributed ? '<span class="text-green-600">Yes</span>' : '<span class="text-gray-400">No</span>' !!} ({{ $s->allocations()->count() }})</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No snapshots yet — add a pool, then “Sync now”.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
