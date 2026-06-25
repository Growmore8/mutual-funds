<x-admin-layout title="Requests · Deposits">
    @include('admin.partials.request-tabs')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <form method="GET" class="flex gap-2 text-sm">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input name="q" value="{{ $search ?? '' }}" placeholder="Search name, email or GC ID…" class="min-w-[220px] border-gray-300 rounded-md">
            <button class="px-4 py-2 bg-gray-800 text-white rounded-md"><i class="fa-solid fa-magnifying-glass"></i></button>
            @if (!empty($search))<a href="{{ route('admin.deposits.index', array_filter(['status' => request('status')])) }}" class="px-4 py-2 border rounded-md text-gray-600">Clear</a>@endif
        </form>
        <div class="flex gap-1 text-sm">
            @foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.deposits.index', array_filter(['status' => $key, 'q' => $search ?? ''])) }}"
                   class="px-3 py-1.5 rounded-md {{ (string)request('status') === (string)$key ? 'bg-emerald-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="bg-white shadow rounded-xl overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Slip</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($deposits as $d)
                    <tr>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $d->created_at->format('d M Y') }}<br>{{ $d->created_at->format('h:i A') }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $d->user->name ?? '—' }}</div>
                            <div class="text-gray-400 text-xs font-mono">{{ $d->user?->clientCode() }}</div>
                            <div class="text-gray-400 text-xs">{{ $d->user->email ?? '' }}</div>
                            @if (($d->purpose ?? 'fund') === 'spot')
                                <div class="mt-1 inline-block text-[11px] font-semibold px-1.5 py-0.5 rounded {{ $d->currency==='INR' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}" title="Spot is a single USD wallet; INR is only the entry currency and is converted to USD."><i class="fa-solid fa-arrow-trend-up"></i> Spot Trading (USD){{ $d->currency==='INR' ? ' · entered ₹' : '' }}</div>
                            @elseif ($d->fundAccount)
                                <div class="mt-1 inline-block text-[11px] font-mono px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">{{ $d->fundAccount->code() }} · {{ $d->fundAccount->label }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $d->method ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium">${{ number_format((float)($d->usd_amount ?? $d->amount),2) }}@if($d->currency && $d->currency!=='USD' && $d->usd_amount)<div class="text-[11px] text-gray-400 font-normal">{{ ($d->currency==='INR'?'₹':$d->currency.' ').number_format((float)$d->amount,2) }} @ {{ number_format((float)$d->amount/max(0.0001,(float)$d->usd_amount),2) }}/$</div>@endif</td>
                        <td class="px-4 py-3">
                            @if ($d->proof_path)
                                <a href="{{ route('admin.deposits.slip',$d) }}" target="_blank" class="text-emerald-600 hover:underline"><i class="fa-regular fa-image"></i> View</a>
                            @else <span class="text-gray-300">—</span> @endif
                        </td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs {{ ['pending'=>'bg-amber-100 text-amber-800','approved'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800'][$d->status] ?? 'bg-gray-100' }}">{{ ucfirst($d->status) }}</span></td>
                        <td class="px-4 py-3 text-right">
                            @if ($d->status === 'pending')
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.deposits.approve',$d) }}">@csrf<button class="px-3 py-1.5 bg-emerald-600 text-white rounded-md">Approve</button></form>
                                    <form method="POST" action="{{ route('admin.deposits.reject',$d) }}" onsubmit="this.admin_note.value=prompt('Reason for rejecting (optional):')||''">@csrf<input type="hidden" name="admin_note"><button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Reject</button></form>
                                </div>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No deposit requests.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $deposits->links() }}</div>
    </div>
</x-admin-layout>
