<x-admin-layout title="Transactions">
    <div class="bg-white shadow rounded-xl overflow-x-auto" x-data="{ edit:false, add:false, transfer:false, tab:'{{ request('tab') === 'spot' ? 'spot' : 'fund' }}', spotKind:'all', f:{id:null,type:'profit',amount:0,description:''} }">
        {{-- Tabs on top --}}
        <div class="p-4 pb-0 flex items-center gap-2">
            <button type="button" @click="tab='fund'" :class="tab==='fund' ? 'bg-emerald-600 text-white shadow' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap"><i class="fa-solid fa-layer-group mr-1"></i> Mutual Fund</button>
            <button type="button" @click="tab='spot'" :class="tab==='spot' ? 'bg-emerald-600 text-white shadow' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap"><i class="fa-solid fa-arrow-trend-up mr-1"></i> Spot Trading</button>
        </div>

        {{-- Search + filter below the tabs --}}
        <div class="p-4 border-b flex flex-wrap items-center gap-2">
            <form method="GET" class="flex flex-wrap gap-2 text-sm flex-1">
                <input type="hidden" name="tab" :value="tab">
                <input name="q" value="{{ $search }}" placeholder="Search by client name, email, ID or txn #…"
                       class="flex-1 min-w-[200px] border-gray-300 rounded-md">
                <select name="type" class="border-gray-300 rounded-md" x-show="tab==='fund'">
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
            <button type="button" @click="transfer=true" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm"><i class="fa-solid fa-right-left mr-1"></i> Transfer</button>
            <button type="button" @click="add=true" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm"><i class="fa-solid fa-plus mr-1"></i> Add transaction</button>
        </div>

        {{-- ===== Mutual Fund tab ===== --}}
        <div x-show="tab==='fund'">
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
        </div>

        {{-- ===== Spot Trading tab ===== --}}
        <div x-show="tab==='spot'" x-cloak>
            <div class="px-4 pt-4 flex flex-wrap items-center gap-1 text-sm">
                @foreach (['all' => 'All', 'trade' => 'Trades', 'deposit' => 'Deposits', 'withdrawal' => 'Withdrawals'] as $k => $lbl)
                    <button type="button" @click="spotKind='{{ $k }}'" :class="spotKind==='{{ $k }}' ? 'bg-emerald-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 rounded-md">{{ $lbl }}</button>
                @endforeach
            </div>
            <div class="p-4">
                <table class="min-w-full text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-500 text-left">
                        <tr><th class="px-3 py-2.5">Date</th><th class="px-3 py-2.5">Client</th><th class="px-3 py-2.5">Kind</th><th class="px-3 py-2.5">Detail</th><th class="px-3 py-2.5 text-right">Amount</th><th class="px-3 py-2.5 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($spotItems as $s)
                            <tr x-show="spotKind==='all' || spotKind==='{{ strtolower($s->kind) }}'">
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
                          x-data="{ q:'', open:false, sel:null, accs:@js($accounts), rates:@js($fxMap), dest:'fund', ecur:'USD', amt:'', type:'deposit',
                                    get localCur(){ return this.sel && this.sel.localCur ? this.sel.localCur : 'USD'; },
                                    get rate(){ return this.ecur==='USD' ? 1 : (this.rates[this.ecur] || 1); },
                                    get usd(){ const a=parseFloat(this.amt)||0; return this.ecur==='USD' ? a : (this.rate>0 ? a/this.rate : a); },
                                    get isDebit(){ return this.type==='withdrawal' || this.type==='fee' || (parseFloat(this.amt)||0) < 0; },
                                    pickAcc(a){ this.sel=a; this.q=''; this.open=false; this.ecur='USD'; } }">
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
                                    <button type="button" @click="pickAcc(a)"
                                            class="w-full text-left px-3 py-2 text-sm text-gray-800 dark:text-gray-100 hover:bg-emerald-100 dark:hover:bg-white/10" x-text="a.label"></button>
                                </template>
                                <div x-show="accs.filter(x => x.search.includes(q.toLowerCase())).length === 0" class="px-3 py-2 text-gray-400 text-sm">No match</div>
                            </div>
                            <p x-show="sel" x-cloak class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Selected: <span x-text="sel?.label"></span></p>
                        </div>

                        {{-- Area: Mutual Fund / Spot --}}
                        <input type="hidden" name="destination" :value="dest">
                        <div>
                            <label class="block text-gray-700 mb-1">Account area</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="dest='fund'" :class="dest==='fund' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600'" class="py-2 rounded-lg font-semibold">Mutual Fund</button>
                                <button type="button" @click="dest='spot_usd'" :class="dest==='spot_usd' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600'" class="py-2 rounded-lg font-semibold">Spot</button>
                            </div>
                        </div>

                        {{-- Action --}}
                        <div>
                            <label class="block text-gray-700 mb-1">Deposit / Withdrawal</label>
                            <select name="type" x-model="type" class="w-full border-gray-300 rounded-md">
                                @foreach (['deposit','withdrawal','reversal','adjustment'] as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                            </select>
                        </div>

                        {{-- Amount + currency (USD or client's fiat) --}}
                        <input type="hidden" name="entered_currency" :value="ecur">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-gray-700">Amount</label>
                                <div class="inline-flex rounded-lg overflow-hidden border border-gray-200 text-[11px] font-semibold">
                                    <button type="button" @click="ecur='USD'" :class="ecur==='USD' ? 'bg-gray-800 text-white' : 'text-gray-500'" class="px-2 py-1">USD</button>
                                    <button type="button" x-show="localCur!=='USD'" @click="ecur=localCur" :class="ecur!=='USD' ? 'bg-gray-800 text-white' : 'text-gray-500'" class="px-2 py-1" x-text="localCur"></button>
                                </div>
                            </div>
                            <input type="number" step="0.01" name="amount" x-model="amt" class="w-full border-gray-300 rounded-md" required :placeholder="(ecur==='USD'?'$':ecur+' ')+'0.00 (use − for debit)'">
                            <p x-show="ecur!=='USD' && parseFloat(amt)" x-cloak class="mt-1 text-xs text-emerald-600">
                                ≈ <span class="font-semibold" x-text="'$'+usd.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})"></span>
                                will be <span x-text="isDebit ? 'debited' : 'credited'"></span> (at <span x-text="ecur+' '+rate.toLocaleString()"></span>/$)
                            </p>
                        </div>
                        <div><label class="block text-gray-700 mb-1">Description (optional note)</label><input name="description" class="w-full border-gray-300 rounded-md" placeholder="Adds to the auto fiat note"></div>
                        <div class="flex gap-2 pt-2">
                            <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save</button>
                            <button type="button" @click="add=false" class="px-4 py-2 border rounded-md text-gray-600">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Within-account Transfer modal (Mutual Fund ⟷ Spot) --}}
            <div x-show="transfer" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="transfer=false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                    <h3 class="font-semibold text-gray-900 mb-4"><i class="fa-solid fa-right-left text-blue-600 mr-1"></i> Within Account Transfer</h3>
                    <form method="POST" action="{{ route('admin.transactions.transfer') }}" class="space-y-3 text-sm"
                          x-data="{ q:'', open:false, sel:null, accs:@js($accounts), dir:'mf_to_spot',
                                    get fromLabel(){ return this.dir==='mf_to_spot' ? 'Mutual Fund (profit)' : 'Spot wallet'; },
                                    get toLabel(){ return this.dir==='mf_to_spot' ? 'Spot wallet' : 'Mutual Fund'; },
                                    flip(){ this.dir = this.dir==='mf_to_spot' ? 'spot_to_mf' : 'mf_to_spot'; } }">
                        @csrf
                        <input type="hidden" name="direction" :value="dir">
                        <div class="relative">
                            <label class="block text-gray-700 mb-1">Client account</label>
                            <input type="hidden" name="fund_account_id" :value="sel ? sel.id : ''" required>
                            <input type="text" x-model="q" @focus="open=true" @click="open=true"
                                   :placeholder="sel ? sel.label : 'Type a name, GC ID or GCA account…'"
                                   class="w-full border-gray-300 rounded-md" autocomplete="off">
                            <div x-show="open" @click.outside="open=false" x-cloak
                                 class="absolute z-10 mt-1 w-full max-h-56 overflow-y-auto bg-white dark:bg-[#0a1730] border border-gray-200 dark:border-white/10 rounded-md shadow-lg">
                                <template x-for="a in accs.filter(x => x.search.includes(q.toLowerCase()))" :key="a.id">
                                    <button type="button" @click="sel=a; q=''; open=false" class="w-full text-left px-3 py-2 text-sm text-gray-800 dark:text-gray-100 hover:bg-emerald-100 dark:hover:bg-white/10" x-text="a.label"></button>
                                </template>
                            </div>
                            <p x-show="sel" x-cloak class="text-xs text-emerald-600 mt-1">Selected: <span x-text="sel?.label"></span></p>
                        </div>

                        {{-- From → To with flip --}}
                        <div class="relative border border-gray-200 rounded-lg px-3">
                            <div class="flex items-center justify-between py-3 border-b border-gray-100"><span class="text-xs text-gray-400 w-12">From</span><span class="font-semibold text-gray-900" x-text="fromLabel"></span></div>
                            <button type="button" @click="flip()" class="absolute left-1/2 -translate-x-1/2 -translate-y-1/2 top-1/2 w-8 h-8 grid place-items-center rounded-full bg-blue-600 text-white shadow"><i class="fa-solid fa-arrow-down-up-across-line text-xs"></i></button>
                            <div class="flex items-center justify-between py-3"><span class="text-xs text-gray-400 w-12">To</span><span class="font-semibold text-gray-900" x-text="toLabel"></span></div>
                        </div>
                        <p class="text-[11px] text-amber-600" x-show="dir==='mf_to_spot'" x-cloak><i class="fa-solid fa-circle-info"></i> Only mutual-fund profit can be moved (not invested capital).</p>

                        <div><label class="block text-gray-700 mb-1">Amount (USD)</label><input type="number" step="0.01" min="0.01" name="amount" class="w-full border-gray-300 rounded-md" placeholder="$ 0.00" required></div>
                        <div class="flex gap-2 pt-2">
                            <button class="px-4 py-2 bg-blue-600 text-white rounded-md">Transfer</button>
                            <button type="button" @click="transfer=false" class="px-4 py-2 border rounded-md text-gray-600">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</x-admin-layout>
