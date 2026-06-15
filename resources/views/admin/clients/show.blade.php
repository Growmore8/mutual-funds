<x-admin-layout title="Client · {{ $client->name }}">
    <a href="{{ route('admin.clients.index') }}" class="text-sm text-gray-500">&larr; Back to clients</a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
        <div class="bg-white shadow rounded-xl p-6 lg:col-span-1 space-y-2 text-sm">
            <h3 class="text-lg font-semibold text-gray-900">{{ $client->name }}</h3>
            <p class="text-gray-500">{{ $client->email }}</p>
            <p><span class="text-gray-400">Phone:</span> {{ $client->phone ?? '—' }}</p>
            <p><span class="text-gray-400">Country:</span> {{ $client->country ?? '—' }}</p>
            <p><span class="text-gray-400">Account type:</span> {{ $client->accountType->name ?? '—' }}</p>
            <p><span class="text-gray-400">KYC:</span> {{ ucfirst(str_replace('_',' ',$client->kyc_status)) }}</p>

            <form method="POST" action="{{ route('admin.clients.status',$client) }}" class="pt-3 flex gap-2">
                @csrf @method('PATCH')
                <select name="status" class="border-gray-300 rounded-md text-sm flex-1">
                    @foreach (['pending','active','suspended'] as $s)
                        <option value="{{ $s }}" @selected($client->status===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button class="px-3 py-2 bg-gray-900 text-white rounded-md text-sm">Update</button>
            </form>
        </div>

        <div class="bg-white shadow rounded-xl p-6 lg:col-span-2">
            <h3 class="font-semibold text-gray-900 mb-3">Recent transactions</h3>
            <table class="min-w-full text-sm">
                <thead class="text-gray-500 text-left"><tr><th class="py-2">Date</th><th>Type</th><th>Amount</th><th>Balance</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($client->transactions as $t)
                        <tr><td class="py-2 text-gray-400">{{ $t->created_at->format('d M Y') }}</td>
                            <td>{{ ucfirst($t->type) }}</td>
                            <td class="{{ $t->amount < 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format((float)$t->amount,2) }}</td>
                            <td>{{ number_format((float)$t->balance_after,2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-gray-400">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
