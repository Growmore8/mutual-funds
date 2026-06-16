<x-admin-layout title="Clients">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <form method="GET" class="flex flex-wrap gap-3">
            <input name="q" value="{{ request('q') }}" placeholder="Search name or email"
                   class="border-gray-300 rounded-md text-sm w-64">
            <select name="status" class="border-gray-300 rounded-md text-sm">
                <option value="">All statuses</option>
                @foreach (['pending','active','suspended'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm">Filter</button>
        </form>
        <a href="{{ route('admin.clients.create') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm font-medium"><i class="fa-solid fa-user-plus mr-1"></i> New client</a>
    </div>

    <div class="bg-white shadow rounded-xl overflow-x-auto">
        <table class="min-w-full text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-3 py-2.5">Client ID</th>
                    <th class="px-3 py-2.5">Joined</th>
                    <th class="px-3 py-2.5">Client</th>
                    <th class="px-3 py-2.5">Pool ID</th>
                    <th class="px-3 py-2.5">Plan</th>
                    <th class="px-3 py-2.5 text-right">Capital (Balance)</th>
                    <th class="px-3 py-2.5 text-right">PnL</th>
                    <th class="px-3 py-2.5">Status</th>
                    <th class="px-3 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($clients as $c)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-mono text-xs text-gray-600">{{ $c->clientCode() }}</td>
                        <td class="px-3 py-2 text-gray-400 text-xs">{{ $c->created_at->format('d M Y') }}</td>
                        <td class="px-3 py-2"><div class="font-medium text-gray-900 leading-tight">{{ $c->name }}</div><div class="text-gray-400 text-xs">{{ $c->email }}</div></td>
                        <td class="px-3 py-2 font-medium text-gray-700">{{ $c->poolAccount->account_ref ?? '—' }}</td>
                        <td class="px-3 py-2">
                            @if ($c->accountType)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-700">{{ $c->accountType->name }}</span>
                            @else <span class="text-gray-300">—</span> @endif
                        </td>
                        <td class="px-3 py-2 text-right font-medium">${{ number_format($c->totalDeposited(), 2) }}</td>
                        @php $pnl = $c->runningPnl(); @endphp
                        <td class="px-3 py-2 text-right font-semibold {{ $pnl < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($pnl < 0 ? '-' : '+') }}${{ number_format(abs($pnl), 2) }}</td>
                        <td class="px-3 py-2"><span class="px-2 py-0.5 rounded-full text-xs {{ ['pending'=>'bg-gray-100 text-gray-600','active'=>'bg-green-100 text-green-800','suspended'=>'bg-red-100 text-red-800'][$c->status] ?? 'bg-gray-100' }}">{{ ucfirst($c->status) }}</span></td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.clients.show',$c) }}" title="Edit" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-emerald-50 hover:text-emerald-600"><i class="fa-solid fa-pen"></i></a>

                                {{-- Lock / unlock (suspend) --}}
                                <form method="POST" action="{{ route('admin.clients.status',$c) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $c->status === 'suspended' ? 'active' : 'suspended' }}">
                                    <button title="{{ $c->status === 'suspended' ? 'Unlock' : 'Lock (suspend)' }}" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-amber-50 hover:text-amber-600"><i class="fa-solid {{ $c->status === 'suspended' ? 'fa-lock-open' : 'fa-lock' }}"></i></button>
                                </form>

                                {{-- Deactivate / activate --}}
                                <form method="POST" action="{{ route('admin.clients.status',$c) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $c->status === 'pending' ? 'active' : 'pending' }}">
                                    <button title="{{ $c->status === 'pending' ? 'Activate' : 'Deactivate' }}" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-blue-50 hover:text-blue-600"><i class="fa-solid {{ $c->status === 'pending' ? 'fa-circle-check' : 'fa-user-slash' }}"></i></button>
                                </form>

                                <a href="{{ route('admin.clients.statement',$c) }}" target="_blank" title="Statement (PDF)" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-800"><i class="fa-solid fa-file-pdf"></i></a>

                                <form method="POST" action="{{ route('admin.clients.destroy',$c) }}" onsubmit="return confirm('Delete {{ $c->name }} and all their data? This cannot be undone.')">@csrf @method('DELETE')
                                    <button title="Delete" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No clients found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $clients->links() }}</div>
</x-admin-layout>
