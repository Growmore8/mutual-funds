<x-admin-layout title="Transactions">
    <div class="bg-white shadow rounded-xl overflow-x-auto" x-data="{ edit:false, add:false, f:{id:null,type:'profit',amount:0,description:''} }">
        <div class="p-4 border-b flex flex-wrap items-center gap-2">
            <form method="GET" class="flex flex-wrap gap-2 text-sm flex-1">
                <input name="q" value="{{ $search }}" placeholder="Search by client name, email, ID or txn #…"
                       class="flex-1 min-w-[200px] border-gray-300 rounded-md">
                <select name="type" class="border-gray-300 rounded-md">
                    <option value="">All types</option>
                    @foreach (['deposit','withdrawal','profit','fee','reversal','adjustment'] as $t)
                        <option value="{{ $t }}" @selected(request('type')===$t)>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 bg-gray-800 text-white rounded-md"><i class="fa-solid fa-magnifying-glass"></i></button>
                @if ($search || request('type'))
                    <a href="{{ route('admin.transactions.index') }}" class="px-4 py-2 border rounded-md text-gray-600">Clear</a>
                @endif
            </form>
            <button type="button" @click="add=true" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm"><i class="fa-solid fa-plus mr-1"></i> Add transaction</button>
        </div>
            <table class="min-w-full text-sm whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-500 text-left">
                    <tr>
                        <th class="px-3 py-3">Date</th>
                        <th class="px-3 py-3">Txn #</th>
                        <th class="px-3 py-3">Client</th>
                        <th class="px-3 py-3">Account</th>
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
                            <td class="px-3 py-3">@if ($t->fundAccount)<div class="font-mono text-xs text-gray-700">{{ $t->fundAccount->code() }}</div><div class="text-[11px] text-gray-400">{{ $t->fundAccount->label }}</div>@else<span class="text-gray-300">—</span>@endif</td>
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
                        <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No transactions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $transactions->links() }}</div>

            {{-- Spot Trading transactions --}}
            <div class="p-4 border-t border-gray-100">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3"><i class="fa-solid fa-arrow-trend-up text-blue-500 mr-1"></i> Spot Trading transactions</h3>
                <table class="min-w-full text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-500 text-left">
                        <tr><th class="px-3 py-2.5">Date</th><th class="px-3 py-2.5">Client</th><th class="px-3 py-2.5">Kind</th><th class="px-3 py-2.5">Detail</th><th class="px-3 py-2.5 text-right">Amount</th><th class="px-3 py-2.5 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($spotItems as $s)
                            <tr>
                                <td class="px-3 py-3 text-gray-400 text-xs">{{ $s->when->format('d M Y') }}<br>{{ $s->when->format('h:i A') }}</td>
                                <td class="px-3 py-3 font-medium text-gray-900">{{ $s->client }}</td>
                                <td class="px-3 py-3"><span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $s->kind }}</span></td>
                                <td class="px-3 py-3 text-gray-600">{{ $s->detail }}</td>
                                <td class="px-3 py-3 text-right font-semibold {{ $s->credit ? 'text-emerald-600' : 'text-red-600' }}">{{ ($s->credit ? '+' : '-') . $s->cs . number_format(abs((float)$s->amount), 2) }}</td>
                                <td class="px-3 py-3 text-right">
                                    @if ($s->del)
                                        <form method="POST" action="{{ $s->del }}" onsubmit="return confirm('Delete this spot trade and reverse its balance/holding effect?')">@csrf
                                            <button title="Delete" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600 inline-grid"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @else <span class="text-gray-300">—</span> @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No spot transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

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

            {{-- Add transaction modal --}}
            <div x-show="add" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="add=false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Add transaction</h3>
                    <form method="POST" action="{{ route('admin.transactions.store') }}" class="space-y-3 text-sm"
                          x-data="{ q:'', open:false, sel:null, accs:@js($accounts), dest:'fund' }">
                        @csrf
                        <div class="relative">
                            <label class="block text-gray-700 mb-1">Account (search by name / account ID)</label>
                            <input type="hidden" name="fund_account_id" :value="sel ? sel.id : ''" required>
                            <input type="text" x-model="q" @focus="open=true" @click="open=true"
                                   :placeholder="sel ? sel.label : 'Type a name, GC ID or GCA account…'"
                                   class="w-full border-gray-300 rounded-md" autocomplete="off">
                            <div x-show="open" @click.outside="open=false" x-cloak
                                 class="absolute z-10 mt-1 w-full max-h-56 overflow-y-auto bg-white dark:bg-[#0a1730] border border-gray-200 dark:border-white/10 rounded-md shadow-lg">
                                <template x-for="a in accs.filter(x => x.search.includes(q.toLowerCase()))" :key="a.id">
                                    <button type="button" @click="sel=a; q=''; open=false"
                                            class="w-full text-left px-3 py-2 text-sm text-gray-800 dark:text-gray-100 hover:bg-emerald-100 dark:hover:bg-white/10" x-text="a.label"></button>
                                </template>
                                <div x-show="accs.filter(x => x.search.includes(q.toLowerCase())).length === 0" class="px-3 py-2 text-gray-400 text-sm">No match</div>
                            </div>
                            <p x-show="sel" x-cloak class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Selected: <span x-text="sel?.label"></span></p>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-1">Book to (currency follows the market)</label>
                            <select name="destination" x-model="dest" class="w-full border-gray-300 rounded-md">
                                <option value="fund">Mutual Fund (USD)</option>
                                <option value="spot_usd">Spot wallet (USD)</option>
                                <option value="spot_inr">Spot wallet — enter INR (auto-converts to USD)</option>
                            </select>
                            <p class="text-xs mt-1" :class="dest==='spot_inr' ? 'text-orange-600' : 'text-blue-600'">
                                <span x-show="dest==='spot_inr'">Enter the amount in <span class="font-semibold">INR (₹)</span> — it converts to USD on the wallet.</span>
                                <span x-show="dest!=='spot_inr'">Amount is in <span class="font-semibold">USD ($)</span>.</span>
                            </p>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-1">Type</label>
                            <select name="type" class="w-full border-gray-300 rounded-md">
                                @foreach (['deposit','withdrawal','reversal','adjustment'] as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                            </select>
                        </div>
                        <div><label class="block text-gray-700 mb-1">Amount <span x-text="dest==='spot_inr' ? '(₹)' : '($)'"></span> <span class="text-gray-400">(use − for debit)</span></label><input type="number" step="0.01" name="amount" class="w-full border-gray-300 rounded-md" required></div>
                        <div><label class="block text-gray-700 mb-1">Description</label><input name="description" class="w-full border-gray-300 rounded-md"></div>
                        <div class="flex gap-2 pt-2">
                            <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save</button>
                            <button type="button" @click="add=false" class="px-4 py-2 border rounded-md text-gray-600">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</x-admin-layout>
