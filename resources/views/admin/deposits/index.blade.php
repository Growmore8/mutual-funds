<x-admin-layout title="Deposits">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-gray-500">Client deposit requests — review the slip, then approve or reject.</p>
        <div class="flex gap-1 text-sm">
            @foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.deposits.index', array_filter(['status' => $key])) }}"
                   class="px-3 py-1.5 rounded-md {{ (string)request('status') === (string)$key ? 'bg-emerald-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="bg-white shadow rounded-xl overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr><th class="px-4 py-3">Client</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Slip</th><th class="px-4 py-3">Joined</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($deposits as $d)
                    <tr>
                        <td class="px-4 py-3"><div>{{ $d->user->name ?? '—' }}</div><div class="text-gray-400 text-xs">{{ $d->poolAccount->account_ref ?? 'no pool' }}</div></td>
                        <td class="px-4 py-3 text-gray-600">{{ $d->method ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium">${{ number_format((float)$d->amount,2) }}</td>
                        <td class="px-4 py-3">
                            @if ($d->proof_path)
                                <a href="{{ route('admin.deposits.slip',$d) }}" target="_blank" class="text-emerald-600 hover:underline"><i class="fa-regular fa-image"></i> View</a>
                            @else <span class="text-gray-300">—</span> @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400">{{ $d->value_date?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs {{ ['pending'=>'bg-amber-100 text-amber-800','approved'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800'][$d->status] ?? 'bg-gray-100' }}">{{ ucfirst($d->status) }}</span></td>
                        <td class="px-4 py-3 text-right">
                            @if ($d->status === 'pending')
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.deposits.approve',$d) }}">@csrf<button class="px-3 py-1.5 bg-emerald-600 text-white rounded-md">Approve</button></form>
                                    <form method="POST" action="{{ route('admin.deposits.reject',$d) }}" onsubmit="this.admin_note.value=prompt('Reason for rejecting (optional):')||''">@csrf<input type="hidden" name="admin_note"><button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Reject</button></form>
                                </div>
                            @else
                                <span class="text-gray-300 text-xs">{{ $d->approved_at?->format('d M Y') ?? ($d->admin_note ? 'rejected' : '—') }}</span>
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
