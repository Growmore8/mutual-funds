<x-admin-layout title="Requests · Account Requests">
    @include('admin.partials.request-tabs')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-gray-500">Clients requesting an additional account (the 1st account is free at registration).</p>
        <div class="flex gap-1 text-sm">
            @foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                <a href="{{ route('admin.account-requests.index', array_filter(['status' => $key])) }}"
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
                    <th class="px-4 py-3">Requested account</th>
                    <th class="px-4 py-3">Reason</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($requests as $r)
                    <tr>
                        <td class="px-4 py-3 text-gray-400">{{ $r->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.clients.show', $r->user) }}" class="font-medium text-gray-900 hover:underline">{{ $r->user->name ?? '—' }}</a>
                            <div class="text-gray-400 text-xs">{{ $r->user->email ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $r->accountType->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 max-w-xs">{{ $r->reason ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @php $b = ['pending'=>'bg-amber-100 text-amber-800','approved'=>'bg-emerald-100 text-emerald-800','rejected'=>'bg-red-100 text-red-700'][$r->status]; @endphp
                            <span class="text-xs px-2.5 py-1 rounded-full capitalize {{ $b }}">{{ $r->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($r->status === 'pending')
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.account-requests.approve', $r) }}">@csrf<button class="px-3 py-1.5 bg-emerald-600 text-white rounded-md">Approve</button></form>
                                    <form method="POST" action="{{ route('admin.account-requests.reject', $r) }}">@csrf<button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Reject</button></form>
                                </div>
                            @else
                                <span class="text-gray-400 text-xs">{{ $r->processed_at?->format('d M Y') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No account requests.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $requests->links() }}</div>
    </div>
</x-admin-layout>
