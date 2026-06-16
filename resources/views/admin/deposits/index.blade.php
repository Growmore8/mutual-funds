<x-admin-layout title="Deposits">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Add deposit --}}
        <div class="bg-white shadow rounded-xl p-6 lg:col-span-1">
            <h3 class="font-semibold text-gray-900 mb-3">Add deposit (assign to pool)</h3>
            <form method="POST" action="{{ route('admin.deposits.store') }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block text-gray-700">Client</label>
                    <select name="user_id" class="mt-1 w-full border-gray-300 rounded-md" required>
                        <option value="">Select…</option>
                        @foreach ($clients as $c)<option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>@endforeach
                    </select>
                    @error('user_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700">Pool account</label>
                    <select name="pool_account_id" class="mt-1 w-full border-gray-300 rounded-md" required>
                        @foreach ($pools as $p)<option value="{{ $p->id }}">{{ $p->account_ref }} — cap ${{ number_format((float)$p->capacity) }}</option>@endforeach
                    </select>
                </div>
                <div><label class="block text-gray-700">Amount ($)</label><input type="number" step="0.01" name="amount" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                <div><label class="block text-gray-700">Value / joining date</label><input type="date" name="value_date" value="{{ date('Y-m-d') }}" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                <div>
                    <label class="block text-gray-700">Status</label>
                    <select name="status" class="mt-1 w-full border-gray-300 rounded-md">
                        <option value="approved">Approved (credit now)</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div><label class="block text-gray-700">Reference (optional)</label><input name="reference" class="mt-1 w-full border-gray-300 rounded-md"></div>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save deposit</button>
            </form>
        </div>

        {{-- List --}}
        <div class="bg-white shadow rounded-xl overflow-hidden lg:col-span-2">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-left">
                    <tr><th class="px-4 py-3">Client</th><th class="px-4 py-3">Pool</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Joined</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($deposits as $d)
                        <tr>
                            <td class="px-4 py-3">{{ $d->user->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $d->poolAccount->account_ref ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium">${{ number_format((float)$d->amount,2) }}</td>
                            <td class="px-4 py-3 text-gray-400">{{ $d->value_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs {{ ['pending'=>'bg-amber-100 text-amber-800','approved'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800'][$d->status] ?? 'bg-gray-100' }}">{{ ucfirst($d->status) }}</span></td>
                            <td class="px-4 py-3 text-right">
                                @if ($d->status === 'pending')
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.deposits.approve',$d) }}">@csrf<button class="px-3 py-1.5 bg-emerald-600 text-white rounded-md">Approve</button></form>
                                        <form method="POST" action="{{ route('admin.deposits.reject',$d) }}">@csrf<button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Reject</button></form>
                                    </div>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No deposits yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $deposits->links() }}</div>
        </div>
    </div>
</x-admin-layout>
