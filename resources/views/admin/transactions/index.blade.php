<x-admin-layout title="Transactions">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Add transaction --}}
        <div class="bg-white shadow rounded-xl p-6 lg:col-span-1">
            <h3 class="font-semibold text-gray-900 mb-3">Record transaction</h3>
            <form method="POST" action="{{ route('admin.transactions.store') }}" class="space-y-3 text-sm">
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
                    <label class="block text-gray-700">Type</label>
                    <select name="type" class="mt-1 w-full border-gray-300 rounded-md">
                        @foreach (['deposit','withdrawal','profit','fee','adjustment'] as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                    </select>
                </div>
                <div><label class="block text-gray-700">Amount (use − for debit)</label><input type="number" step="0.01" name="amount" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                <div><label class="block text-gray-700">Description</label><input name="description" class="mt-1 w-full border-gray-300 rounded-md"></div>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save</button>
            </form>
        </div>

        {{-- List --}}
        <div class="bg-white shadow rounded-xl overflow-hidden lg:col-span-2">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-left">
                    <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Balance</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($transactions as $t)
                        <tr>
                            <td class="px-4 py-3 text-gray-400">{{ $t->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $t->user->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ ucfirst($t->type) }}</td>
                            <td class="px-4 py-3 {{ $t->amount < 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format((float)$t->amount,2) }}</td>
                            <td class="px-4 py-3">{{ number_format((float)$t->balance_after,2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $transactions->links() }}</div>
        </div>
    </div>
</x-admin-layout>
