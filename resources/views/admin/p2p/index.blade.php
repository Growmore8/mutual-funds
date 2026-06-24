<x-admin-layout title="P2P">
    <div x-data="{ open:false, m:{} }">
        @if (session('status'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
        @endif

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold text-gray-900">P2P Merchants</h1>
            <button @click="m={id:null,name:'',side:'buy',asset:'USDT',currency:'INR',price:97,available:10000,min_limit:500,max_limit:200000,pay_methods:'UPI, Bank Transfer',completion:98,orders_30d:0}; open=true" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm"><i class="fa-solid fa-plus mr-1"></i> Add merchant</button>
        </div>

        {{-- Merchants table --}}
        <div class="bg-white shadow rounded-xl overflow-x-auto mb-8">
            <table class="min-w-full text-sm whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-500 text-left">
                    <tr><th class="px-3 py-3">Merchant</th><th class="px-3 py-3">Side</th><th class="px-3 py-3 text-right">Price</th><th class="px-3 py-3 text-right">Available</th><th class="px-3 py-3">Limits</th><th class="px-3 py-3">Pay methods</th><th class="px-3 py-3">Status</th><th class="px-3 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($merchants as $m)
                        <tr>
                            <td class="px-3 py-3"><div class="font-medium text-gray-900">{{ $m->name }}</div><div class="text-[11px] text-gray-400">{{ number_format($m->orders_30d) }} orders · {{ number_format($m->completion,1) }}%</div></td>
                            <td class="px-3 py-3"><span class="text-xs px-2 py-0.5 rounded-full {{ $m->side==='buy' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700' }}">{{ ucfirst($m->side) }}</span></td>
                            <td class="px-3 py-3 text-right">{{ $m->curSym() }}{{ number_format($m->price,2) }}</td>
                            <td class="px-3 py-3 text-right">{{ number_format($m->available,2) }} {{ $m->asset }}</td>
                            <td class="px-3 py-3 text-gray-500">{{ $m->curSym() }}{{ number_format($m->min_limit,0) }}–{{ $m->curSym() }}{{ number_format($m->max_limit,0) }}</td>
                            <td class="px-3 py-3 text-gray-500 max-w-[200px] truncate">{{ $m->pay_methods }}</td>
                            <td class="px-3 py-3">
                                <form method="POST" action="{{ route('admin.p2p.toggle', $m) }}">@csrf
                                    <button class="text-xs px-2 py-0.5 rounded-full {{ $m->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">{{ $m->is_active ? 'Active' : 'Off' }}</button>
                                </form>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="m={id:{{ $m->id }},name:@js($m->name),side:'{{ $m->side }}',asset:'{{ $m->asset }}',currency:'{{ $m->currency }}',price:{{ (float)$m->price }},available:{{ (float)$m->available }},min_limit:{{ (float)$m->min_limit }},max_limit:{{ (float)$m->max_limit }},pay_methods:@js($m->pay_methods),completion:{{ (float)$m->completion }},orders_30d:{{ (int)$m->orders_30d }}}; open=true" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-emerald-50 hover:text-emerald-600"><i class="fa-solid fa-pen"></i></button>
                                    <form method="POST" action="{{ route('admin.p2p.destroy', $m) }}" onsubmit="return confirm('Delete this merchant?')">@csrf @method('DELETE')
                                        <button class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600 inline-grid"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No merchants yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Orders --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-3">P2P Orders</h2>
        <div class="bg-white shadow rounded-xl overflow-x-auto">
            <table class="min-w-full text-sm whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-500 text-left">
                    <tr><th class="px-3 py-3">Date</th><th class="px-3 py-3">Client</th><th class="px-3 py-3">Merchant</th><th class="px-3 py-3">Side</th><th class="px-3 py-3 text-right">Amount</th><th class="px-3 py-3">Status</th><th class="px-3 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $o)
                        <tr>
                            <td class="px-3 py-3 text-gray-400 text-xs">{{ $o->created_at->format('d M Y') }}<br>{{ $o->created_at->format('h:i A') }}</td>
                            <td class="px-3 py-3"><div class="font-medium text-gray-900">{{ $o->user->name ?? '—' }}</div><div class="text-[11px] text-gray-400 font-mono">{{ $o->user?->clientCode() }}</div></td>
                            <td class="px-3 py-3 text-gray-600">{{ $o->merchant->name ?? '—' }}</td>
                            <td class="px-3 py-3"><span class="text-xs px-2 py-0.5 rounded-full {{ $o->side==='buy' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700' }}">{{ ucfirst($o->side) }}</span></td>
                            <td class="px-3 py-3 text-right">{{ $o->currency==='INR'?'₹':'$' }}{{ number_format($o->fiat_amount,2) }}<div class="text-[11px] text-gray-400">{{ number_format($o->asset_amount,4) }} {{ $o->asset }}</div></td>
                            <td class="px-3 py-3"><span class="text-xs px-2 py-0.5 rounded-full {{ ['pending'=>'bg-amber-100 text-amber-800','completed'=>'bg-emerald-100 text-emerald-800','cancelled'=>'bg-red-100 text-red-700'][$o->status] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($o->status) }}</span></td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @foreach (['completed' => 'Complete', 'cancelled' => 'Cancel'] as $st => $lbl)
                                        <form method="POST" action="{{ route('admin.p2p.order.status', $o) }}">@csrf
                                            <input type="hidden" name="status" value="{{ $st }}">
                                            <button class="text-xs px-2 py-1 rounded {{ $st==='completed' ? 'text-emerald-700 hover:bg-emerald-50' : 'text-red-600 hover:bg-red-50' }}">{{ $lbl }}</button>
                                        </form>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No P2P orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Add / edit merchant modal --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="open=false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4" x-text="m.id ? 'Edit merchant' : 'Add merchant'"></h3>
                <form :action="m.id ? '{{ url('admin/p2p') }}/'+m.id+'/update' : '{{ route('admin.p2p.store') }}'" method="POST" class="grid grid-cols-2 gap-3 text-sm">
                    @csrf
                    <div class="col-span-2"><label class="block text-gray-600 mb-1">Merchant name</label><input name="name" x-model="m.name" class="w-full border-gray-300 rounded-md" required></div>
                    <div><label class="block text-gray-600 mb-1">Side</label><select name="side" x-model="m.side" class="w-full border-gray-300 rounded-md"><option value="buy">Buy (client buys)</option><option value="sell">Sell (client sells)</option></select></div>
                    <div><label class="block text-gray-600 mb-1">Asset</label><input name="asset" x-model="m.asset" class="w-full border-gray-300 rounded-md" required></div>
                    <div><label class="block text-gray-600 mb-1">Currency</label><input name="currency" x-model="m.currency" class="w-full border-gray-300 rounded-md" required></div>
                    <div><label class="block text-gray-600 mb-1">Price / unit</label><input type="number" step="0.0001" name="price" x-model="m.price" class="w-full border-gray-300 rounded-md" required></div>
                    <div><label class="block text-gray-600 mb-1">Available</label><input type="number" step="0.01" name="available" x-model="m.available" class="w-full border-gray-300 rounded-md" required></div>
                    <div><label class="block text-gray-600 mb-1">Min limit</label><input type="number" step="0.01" name="min_limit" x-model="m.min_limit" class="w-full border-gray-300 rounded-md" required></div>
                    <div><label class="block text-gray-600 mb-1">Max limit</label><input type="number" step="0.01" name="max_limit" x-model="m.max_limit" class="w-full border-gray-300 rounded-md" required></div>
                    <div><label class="block text-gray-600 mb-1">Completion %</label><input type="number" step="0.1" name="completion" x-model="m.completion" class="w-full border-gray-300 rounded-md"></div>
                    <div><label class="block text-gray-600 mb-1">Orders (30d)</label><input type="number" name="orders_30d" x-model="m.orders_30d" class="w-full border-gray-300 rounded-md"></div>
                    <div class="col-span-2"><label class="block text-gray-600 mb-1">Pay methods (comma separated)</label><input name="pay_methods" x-model="m.pay_methods" class="w-full border-gray-300 rounded-md" placeholder="UPI, Bank Transfer, IMPS"></div>
                    <div class="col-span-2 flex gap-2 pt-1">
                        <button class="px-4 py-2 bg-emerald-600 text-white rounded-md">Save</button>
                        <button type="button" @click="open=false" class="px-4 py-2 border rounded-md text-gray-600">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
