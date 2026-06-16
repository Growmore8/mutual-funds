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
                        @foreach ($clients as $c)<option value="{{ $c->id }}">{{ $c->clientCode() }} — {{ $c->name }}</option>@endforeach
                    </select>
                    @error('user_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700">Type</label>
                    <select name="type" class="mt-1 w-full border-gray-300 rounded-md">
                        @foreach (['deposit','withdrawal','reversal','adjustment'] as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                    </select>
                </div>
                <div><label class="block text-gray-700">Amount (use − for debit)</label><input type="number" step="0.01" name="amount" class="mt-1 w-full border-gray-300 rounded-md" required></div>
                <div><label class="block text-gray-700">Description</label><input name="description" class="mt-1 w-full border-gray-300 rounded-md"></div>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save</button>
            </form>
        </div>

        {{-- List --}}
        <div class="bg-white shadow rounded-xl overflow-x-auto lg:col-span-2" x-data="{ edit:false, f:{id:null,type:'profit',amount:0,description:''} }">
            <div class="p-4 border-b">
                <form method="GET" class="flex flex-wrap gap-2 text-sm">
                    <input name="q" value="{{ $search }}" placeholder="Search by client name, email, or transaction ID…"
                           class="flex-1 min-w-[200px] border-gray-300 rounded-md">
                    <select name="type" class="border-gray-300 rounded-md">
                        <option value="">All types</option>
                        @foreach (['deposit','withdrawal','profit','fee','adjustment'] as $t)
                            <option value="{{ $t }}" @selected(request('type')===$t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                    <button class="px-4 py-2 bg-gray-800 text-white rounded-md"><i class="fa-solid fa-magnifying-glass"></i></button>
                    @if ($search || request('type'))
                        <a href="{{ route('admin.transactions.index') }}" class="px-4 py-2 border rounded-md text-gray-600">Clear</a>
                    @endif
                </form>
            </div>
            <table class="min-w-full text-sm whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-500 text-left">
                    <tr>
                        <th class="px-3 py-3">Date</th>
                        <th class="px-3 py-3">Txn #</th>
                        <th class="px-3 py-3">Client</th>
                        <th class="px-3 py-3 text-right">Debit / Credit</th>
                        <th class="px-3 py-3">Method / Details</th>
                        <th class="px-3 py-3">Note</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($transactions as $t)
                        @php
                            $src = $t->source;
                            $method = $src->method ?? null;
                            $details = $src->payout_details ?? null;
                            $st = ['completed' => ['Done','bg-emerald-100 text-emerald-800'], 'pending' => ['Pending','bg-amber-100 text-amber-800'], 'rejected' => ['Rejected','bg-red-100 text-red-700']][$t->status] ?? ['Done','bg-emerald-100 text-emerald-800'];
                        @endphp
                        <tr>
                            <td class="px-3 py-3 text-gray-400 text-xs">{{ $t->created_at->format('d M Y') }}<br>{{ $t->created_at->format('h:i A') }}</td>
                            <td class="px-3 py-3 text-gray-400">{{ $t->id }}</td>
                            <td class="px-3 py-3"><div class="font-medium text-gray-900">{{ $t->user->name ?? '—' }}</div><div class="text-gray-400 text-xs font-mono">{{ $t->user?->clientCode() }}</div></td>
                            <td class="px-3 py-3 text-right">
                                <span class="font-semibold {{ $t->amount < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ ($t->amount < 0 ? '-' : '+') . '$' . number_format(abs((float)$t->amount),2) }}</span>
                                <div class="text-[11px] text-gray-400">{{ $t->amount < 0 ? 'Debit' : 'Credit' }} · {{ ucfirst($t->type) }}</div>
                            </td>
                            <td class="px-3 py-3">
                                @if ($method)
                                    <div class="text-gray-700">{{ $method }}</div>
                                    @if ($details)<div class="text-[11px] text-gray-400 max-w-[220px] truncate" title="{{ $details }}">{{ $details }}</div>@endif
                                @else <span class="text-gray-300">—</span> @endif
                            </td>
                            <td class="px-3 py-3 text-gray-500 max-w-[180px] truncate" title="{{ $t->description }}">{{ $t->description ?? '—' }}</td>
                            <td class="px-3 py-3"><span class="text-xs px-2 py-0.5 rounded-full {{ $st[1] }}">{{ $st[0] }}</span></td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" title="Edit"
                                            @click="edit=true; f={id:{{ $t->id }}, type:'{{ $t->type }}', amount:{{ (float)$t->amount }}, description:@js($t->description)}"
                                            class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-emerald-50 hover:text-emerald-600"><i class="fa-solid fa-pen"></i></button>
                                    <form method="POST" action="{{ route('admin.transactions.destroy',$t) }}" onsubmit="return confirm('Delete this transaction? Client balances will be recalculated.')">
                                        @csrf @method('DELETE')
                                        <button title="Delete" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No transactions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $transactions->links() }}</div>

            {{-- Edit modal --}}
            <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="edit=false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Edit transaction</h3>
                    <form :action="'{{ url('admin/transactions') }}/' + f.id" method="POST" class="space-y-3 text-sm">
                        @csrf @method('PATCH')
                        <div>
                            <label class="block text-gray-700 mb-1">Type</label>
                            <select name="type" x-model="f.type" class="w-full border-gray-300 rounded-md">
                                @foreach (['deposit','withdrawal','reversal','adjustment','profit','fee'] as $ty)<option value="{{ $ty }}">{{ ucfirst($ty) }}</option>@endforeach
                            </select>
                        </div>
                        <div><label class="block text-gray-700 mb-1">Amount (use − for debit)</label><input type="number" step="0.01" name="amount" x-model="f.amount" class="w-full border-gray-300 rounded-md" required></div>
                        <div><label class="block text-gray-700 mb-1">Description</label><input name="description" x-model="f.description" class="w-full border-gray-300 rounded-md"></div>
                        <div class="flex gap-2 pt-2">
                            <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save</button>
                            <button type="button" @click="edit=false" class="px-4 py-2 border rounded-md text-gray-600">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
