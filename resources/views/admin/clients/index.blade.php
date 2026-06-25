<x-admin-layout title="Clients">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <form method="GET" class="flex flex-wrap gap-3">
            <input name="q" value="{{ request('q') }}" placeholder="Search name or email"
                   class="border-gray-300 rounded-md text-sm w-64">
            <select name="status" class="border-gray-300 rounded-md text-sm">
                <option value="">All statuses</option>
                @foreach (['pending','active','suspended','locked'] as $s)
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
                    <th class="px-3 py-2.5 text-right">Mutual Fund</th>
                    <th class="px-3 py-2.5 text-right">Spot</th>
                    <th class="px-3 py-2.5 text-right">MF PnL</th>
                    <th class="px-3 py-2.5 text-right">Spot PnL</th>
                    <th class="px-3 py-2.5">Status</th>
                    <th class="px-3 py-2.5">KYC</th>
                    <th class="px-3 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
                @forelse ($clients as $c)
                    @php $accs = $c->fundAccounts; $multi = $accs->count() > 1; @endphp
                <tbody class="divide-y divide-gray-100" x-data="{ open:false }">
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-mono text-xs text-gray-600">{{ $c->clientCode() }}</td>
                        <td class="px-3 py-2 text-gray-400 text-xs">{{ $c->created_at->format('d M Y') }}</td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900 leading-tight">{{ $c->name }}</div>
                            <div class="text-gray-400 text-xs">{{ $c->email }}</div>
                            @if ($c->phone)<div class="text-gray-400 text-xs"><i class="fa-solid fa-phone text-[9px]"></i> {{ $c->phone }}</div>@endif
                            @if ($c->country)<div class="text-gray-400 text-xs"><i class="fa-solid fa-location-dot text-[9px]"></i> {{ $c->country }}</div>@endif
                            @if ($c->referrer)<div class="text-[11px] text-emerald-600 mt-0.5"><i class="fa-solid fa-gift text-[9px]"></i> Referred by {{ $c->referrer->name }}</div>@endif
                            @if ($multi)
                                <button type="button" @click="open=!open" class="mt-1 inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full hover:bg-emerald-100">
                                    <i class="fa-solid fa-layer-group text-[9px]"></i> {{ $accs->count() }} accounts <i class="fa-solid fa-chevron-down text-[8px]" :class="open && 'rotate-180'"></i>
                                </button>
                            @endif
                        </td>
                        <td class="px-3 py-2 font-medium text-gray-700">{{ $c->poolAccount->account_ref ?? '—' }}</td>
                        <td class="px-3 py-2">
                            @if ($c->accountType)
                                <span class="px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-700">{{ $c->accountType->name }}</span>
                            @else <span class="text-gray-300">—</span> @endif
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="font-medium text-gray-900">${{ number_format($c->totalDeposited(), 2) }}</div>
                            <div class="text-[11px] text-gray-400">{{ $accs->count() }} acct</div>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="font-medium text-gray-900">${{ number_format((float)($spotBalances[$c->id] ?? 0), 2) }}</div>
                            <div class="text-[11px] text-gray-400">1 wallet</div>
                        </td>
                        @php $pnl = $c->runningPnl(); $spnl = (float) ($spotPnls[$c->id] ?? 0); @endphp
                        <td class="px-3 py-2 text-right font-semibold {{ $pnl < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($pnl < 0 ? '-' : '+') }}${{ number_format(abs($pnl), 2) }}</td>
                        <td class="px-3 py-2 text-right font-semibold {{ $spnl < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($spnl < 0 ? '-' : '+') }}${{ number_format(abs($spnl), 2) }}<div class="text-[10px] font-normal text-gray-400">floating</div></td>
                        <td class="px-3 py-2"><span class="px-2 py-0.5 rounded-full text-xs {{ ['pending'=>'bg-gray-100 text-gray-600','active'=>'bg-green-100 text-green-800','suspended'=>'bg-red-100 text-red-800','locked'=>'bg-amber-100 text-amber-800'][$c->status] ?? 'bg-gray-100' }}">{{ ucfirst($c->status) }}</span></td>
                        @php $kycText = ['approved'=>['Verified','text-emerald-600'],'submitted'=>['Pending','text-amber-600'],'rejected'=>['Rejected','text-red-600'],'not_submitted'=>['Not submitted','text-gray-400']][$c->kyc_status] ?? ['Not submitted','text-gray-400']; @endphp
                        <td class="px-3 py-2 text-xs font-medium {{ $kycText[1] }}">{{ $kycText[0] }}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.clients.show',$c) }}" title="Mutual Fund manage" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-emerald-50 hover:text-emerald-600"><i class="fa-solid fa-pen"></i></a>
                                <a href="{{ route('admin.clients.show',$c) }}#spot" title="Spot Trading manage" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-blue-50 hover:text-blue-600"><i class="fa-solid fa-arrow-trend-up"></i></a>

                                {{-- Lock / unlock (violation — view-only) --}}
                                <form method="POST" action="{{ route('admin.clients.status',$c) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $c->status === 'locked' ? 'active' : 'locked' }}">
                                    <button title="{{ $c->status === 'locked' ? 'Unlock (restore access)' : 'Lock (view-only / violation)' }}" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-amber-50 hover:text-amber-600"><i class="fa-solid {{ $c->status === 'locked' ? 'fa-lock-open' : 'fa-lock' }}"></i></button>
                                </form>

                                {{-- Deactivate / activate (suspend = forces logout) --}}
                                <form method="POST" action="{{ route('admin.clients.status',$c) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $c->status === 'suspended' ? 'active' : 'suspended' }}">
                                    <button title="{{ $c->status === 'suspended' ? 'Reactivate' : 'Deactivate (sign out)' }}" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600"><i class="fa-solid {{ $c->status === 'suspended' ? 'fa-user-check' : 'fa-user-slash' }}"></i></button>
                                </form>

                                <x-statement-modal :base-url="route('admin.clients.statement',$c)" title="Statement (PDF) — download or email" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-800"><i class="fa-solid fa-file-pdf"></i></x-statement-modal>

                                <form method="POST" action="{{ route('admin.clients.destroy',$c) }}" onsubmit="return confirm('Delete {{ $c->name }} and all their data? This cannot be undone.')">@csrf @method('DELETE')
                                    <button title="Delete" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @if ($multi)
                        <tr x-show="open" x-cloak class="bg-gray-50">
                            <td colspan="12" class="px-3 pb-3 pt-0 bg-gray-50">
                                <div class="rounded-lg border border-gray-200 overflow-hidden bg-white">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-gray-100 text-gray-500 text-left">
                                            <tr><th class="px-3 py-2 font-medium">Account</th><th class="px-3 py-2 font-medium">Plan</th><th class="px-3 py-2 font-medium">Pool ID</th><th class="px-3 py-2 font-medium text-right">Capital</th><th class="px-3 py-2 font-medium text-right">PnL</th><th class="px-3 py-2 font-medium text-right">Manage</th></tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            @foreach ($accs as $a)
                                                @php $apnl = $a->runningPnl(); @endphp
                                                <tr>
                                                    <td class="px-3 py-1.5"><span class="font-medium text-gray-800">{{ $a->label }}</span> <span class="font-mono text-gray-400">{{ $a->code() }}</span>@if($a->is_primary)<span class="ml-1 text-[9px] px-1 rounded bg-gray-100 text-gray-500">primary</span>@endif</td>
                                                    <td class="px-3 py-1.5">{{ $a->accountType->name ?? '—' }}</td>
                                                    <td class="px-3 py-1.5">{{ $a->poolAccount->account_ref ?? '—' }}</td>
                                                    <td class="px-3 py-1.5 text-right font-medium">${{ number_format($a->totalDeposited(), 2) }}</td>
                                                    <td class="px-3 py-1.5 text-right font-semibold {{ $apnl < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($apnl < 0 ? '-' : '+') }}${{ number_format(abs($apnl), 2) }}</td>
                                                    <td class="px-3 py-1.5 text-right"><a href="{{ route('admin.clients.show',$c) }}" class="text-emerald-600 hover:underline">Manage</a></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="12" class="px-4 py-8 text-center text-gray-400">No clients found.</td></tr>
                </tbody>
                @endforelse
        </table>
    </div>
    <div class="mt-4">{{ $clients->links() }}</div>
</x-admin-layout>
