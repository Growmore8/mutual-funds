<x-admin-layout title="PnL">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-gray-500">Closed &amp; running profit/loss records, newest first. Distribution to clients happens automatically when a trade closes.</p>
        <form method="POST" action="{{ route('admin.pool.sync') }}">@csrf
            <button class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm">Sync &amp; distribute</button>
        </form>
    </div>

    <div class="bg-white shadow rounded-xl overflow-x-auto">
        <table class="min-w-full text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr><th class="px-4 py-3">Date &amp; time</th><th class="px-4 py-3">ID</th><th class="px-4 py-3">Pool account</th><th class="px-4 py-3">Net PnL (day)</th><th class="px-4 py-3">Floating (running)</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($snapshots as $s)
                    <tr>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $s->snapshot_date->format('d M Y') }}<br>{{ $s->updated_at->format('h:i A') }}</td>
                        <td class="px-4 py-3 text-gray-400 font-mono">PNL{{ str_pad($s->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3">{{ $s->poolAccount->account_ref ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium {{ $s->pnl < 0 ? 'text-red-600' : 'text-green-600' }}">{{ ($s->pnl < 0 ? '-' : '+') . '$' . number_format(abs((float)$s->pnl),2) }}</td>
                        <td class="px-4 py-3 {{ (float)$s->floating_pnl < 0 ? 'text-red-600' : 'text-green-600' }}">{{ ((float)$s->floating_pnl < 0 ? '-' : '+') . '$' . number_format(abs((float)$s->floating_pnl),2) }}</td>
                        <td class="px-4 py-3">
                            @php $allocCount = $s->allocations()->count(); $isClosed = abs((float) $s->floating_pnl) < 0.005; @endphp
                            @if ($isClosed)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Closed · distributed ({{ $allocCount }})</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Open · running ({{ $allocCount }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.pool.pnl.destroy', $s) }}" onsubmit="return confirm('Delete this PnL record? The profit/loss distributed to clients for this day will be reversed and their balances recalculated.')">
                                @csrf @method('DELETE')
                                <button class="px-3 py-1.5 bg-red-600 text-white rounded-md text-xs">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No P&L records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
