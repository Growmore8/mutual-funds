<x-admin-layout title="Requests · Withdrawals">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    @include('admin.partials.request-tabs')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-gray-500">Client withdrawal requests (profit only). Approving debits the client's balance.</p>
        <div class="flex gap-1 text-sm">
            @foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.withdrawals.index', array_filter(['status' => $key])) }}"
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
                        <td class="px-4 py-3"><div class="font-medium text-gray-900">{{ $w->user->name ?? '—' }}</div><div class="text-gray-400 text-xs font-mono">{{ $w->user?->clientCode() }}</div><div class="text-gray-400 text-xs">{{ $w->user->email ?? '' }}</div></td>
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
