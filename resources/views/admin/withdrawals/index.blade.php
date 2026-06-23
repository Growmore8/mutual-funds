<x-admin-layout title="Requests · Withdrawals">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    @include('admin.partials.request-tabs')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <form method="GET" class="flex gap-2 text-sm">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input name="q" value="{{ $search ?? '' }}" placeholder="Search name, email or GC ID…" class="min-w-[220px] border-gray-300 rounded-md">
            <button class="px-4 py-2 bg-gray-800 text-white rounded-md"><i class="fa-solid fa-magnifying-glass"></i></button>
            @if (!empty($search))<a href="{{ route('admin.withdrawals.index', array_filter(['status' => request('status')])) }}" class="px-4 py-2 border rounded-md text-gray-600">Clear</a>@endif
        </form>
        <div class="flex gap-1 text-sm">
            @foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.withdrawals.index', array_filter(['status' => $key, 'q' => $search ?? ''])) }}"
                   class="px-3 py-1.5 rounded-md {{ (string)request('status') === (string)$key ? 'bg-emerald-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="bg-white shadow rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Method / Details</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($withdrawals as $w)
                    <tr>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $w->created_at->format('d M Y') }}<br>{{ $w->created_at->format('h:i A') }}</td>
                        <td class="px-4 py-3"><div class="font-medium text-gray-900">{{ $w->user->name ?? '—' }}</div><div class="text-gray-400 text-xs font-mono">{{ $w->user?->clientCode() }}</div><div class="text-gray-400 text-xs">{{ $w->user->email ?? '' }}</div>@if (($w->purpose ?? 'fund') === 'spot')<div class="mt-1 inline-block text-[11px] font-semibold px-1.5 py-0.5 rounded {{ $w->currency==='INR' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}"><i class="fa-solid fa-arrow-trend-up"></i> Spot Trading · {{ $w->currency }}</div>@elseif ($w->fundAccount)<div class="mt-1 inline-block text-[11px] font-mono px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">{{ $w->fundAccount->code() }} · {{ $w->fundAccount->label }}</div>@endif</td>
                        <td class="px-4 py-3 font-semibold">{{ $money($w->amount) }}</td>
                        <td class="px-4 py-3 text-gray-600"><div>{{ $w->method }}</div><div class="text-xs text-gray-400 max-w-xs whitespace-pre-line">{{ $w->payout_details }}</div></td>
                        <td class="px-4 py-3">
                            @php $b = ['pending'=>'bg-amber-100 text-amber-800','approved'=>'bg-emerald-100 text-emerald-800','rejected'=>'bg-red-100 text-red-700'][$w->status]; @endphp
                            <span class="text-xs px-2.5 py-1 rounded-full capitalize {{ $b }}">{{ $w->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($w->status === 'pending')
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.withdrawals.approve', $w) }}" onsubmit="return confirm('Approve and debit {{ $money($w->amount) }}?')">@csrf
                                        <button class="px-3 py-1.5 bg-emerald-600 text-white rounded-md">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.withdrawals.reject', $w) }}" onsubmit="this.admin_note.value=prompt('Reason for rejecting (optional):')||''">@csrf
                                        <input type="hidden" name="admin_note">
                                        <button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Reject</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No withdrawal requests.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $withdrawals->links() }}</div>
    </div>
</x-admin-layout>
