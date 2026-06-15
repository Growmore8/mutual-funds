<x-admin-layout title="Pool / PnL">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-gray-500">
            Mode:
            <span class="px-2 py-0.5 rounded-full text-xs {{ $isLive ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                {{ $isLive ? 'LIVE API' : 'STUB (set POOL_API_URL for live data)' }}
            </span>
        </p>
        <form method="POST" action="{{ route('admin.pool.sync') }}">@csrf
            <button class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm">Sync now</button>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 mb-6">
        <div class="bg-white shadow rounded-xl p-6"><p class="text-sm text-gray-500">Account</p><p class="text-2xl font-bold">{{ $pool?->account_ref ?? '—' }}</p></div>
        <div class="bg-white shadow rounded-xl p-6"><p class="text-sm text-gray-500">Capacity</p><p class="text-2xl font-bold">${{ number_format((float)($pool?->capacity ?? 0)) }}</p></div>
        <div class="bg-white shadow rounded-xl p-6"><p class="text-sm text-gray-500">Balance</p><p class="text-2xl font-bold">${{ number_format((float)($pool?->balance ?? 0), 2) }}</p></div>
        <div class="bg-white shadow rounded-xl p-6"><p class="text-sm text-gray-500">Last synced</p><p class="text-sm font-semibold">{{ $pool?->last_synced_at?->diffForHumans() ?? 'never' }}</p></div>
    </div>

    <div class="bg-white shadow rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Opening</th><th class="px-4 py-3">PnL</th><th class="px-4 py-3">%</th><th class="px-4 py-3">Closing</th><th class="px-4 py-3">Distributed</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($snapshots as $s)
                    <tr>
                        <td class="px-4 py-3">{{ $s->snapshot_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">${{ number_format((float)$s->opening_balance,2) }}</td>
                        <td class="px-4 py-3 {{ $s->pnl < 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format((float)$s->pnl,2) }}</td>
                        <td class="px-4 py-3">{{ number_format((float)$s->pnl_pct,2) }}%</td>
                        <td class="px-4 py-3">${{ number_format((float)$s->closing_balance,2) }}</td>
                        <td class="px-4 py-3">{!! $s->distributed ? '<span class="text-green-600">Yes</span>' : '<span class="text-gray-400">No</span>' !!} ({{ $s->allocations()->count() }})</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No snapshots yet. Click “Sync now”.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
