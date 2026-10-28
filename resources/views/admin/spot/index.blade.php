<x-admin-layout title="Spot Instruments">
    @if (session('status'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
    @endif

    <p class="text-sm text-gray-500 mb-4"><i class="fa-solid fa-circle-info"></i> Add/enable the markets clients can trade. Manage each client's spot <strong>wallets, holdings &amp; trades</strong> from <a href="{{ route('admin.clients.index') }}" class="text-emerald-600 hover:underline">Clients</a> → open a client → <strong>Spot Trading</strong> section.</p>

    <div class="grid lg:grid-cols-1 gap-6">
        {{-- Instruments --}}
        <div class="bg-white shadow rounded-xl p-5" x-data="{
                q: '',
                f: { id: '', symbol: '', exchange: '', name: '', market: 'india', type: 'stock' },
                edit(i) { this.f = { ...i }; this.$refs.form.scrollIntoView({ behavior: 'smooth', block: 'center' }); this.$refs.symbol.focus(); },
                reset() { this.f = { id: '', symbol: '', exchange: '', name: '', market: 'india', type: 'stock' }; },
                match(s) { return !this.q || s.includes(this.q.trim().toLowerCase()); },
            }">
            <h3 class="font-semibold text-gray-900 mb-3">Tradable instruments</h3>
            <form x-ref="form" method="POST" action="{{ route('admin.spot.instruments.store') }}" class="grid grid-cols-2 gap-2 mb-4 text-sm">
                @csrf
                <input type="hidden" name="id" x-model="f.id">
                <input x-ref="symbol" name="symbol" x-model="f.symbol" placeholder="Symbol e.g. RELIANCE" class="border-gray-300 rounded-md" required>
                <input name="exchange" x-model="f.exchange" placeholder="Exchange e.g. NSE" class="border-gray-300 rounded-md">
                <input name="name" x-model="f.name" placeholder="Name" class="border-gray-300 rounded-md col-span-2">
                <select name="market" x-model="f.market" class="border-gray-300 rounded-md"><option value="india">India</option><option value="global">Global</option><option value="crypto">Crypto</option><option value="forex">Forex</option><option value="commodity">Commodity</option></select>
                <select name="type" x-model="f.type" class="border-gray-300 rounded-md"><option value="stock">Stock</option><option value="crypto">Crypto</option><option value="forex">Forex</option><option value="commodity">Commodity</option><option value="index">Index</option></select>
                <div class="col-span-2 flex gap-2">
                    <button class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-md" x-text="f.id ? 'Update ' + f.symbol : 'Add / Update instrument'">Add / Update instrument</button>
                    <button type="button" x-show="f.id" x-cloak @click="reset()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Cancel edit</button>
                </div>
            </form>

            <div class="relative mb-3 text-sm">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="search" x-model="q" placeholder="Search symbol, name, exchange, market…" class="w-full pl-8 border-gray-300 rounded-md">
            </div>

            <table class="min-w-full text-xs">
                <thead class="text-gray-500 text-left"><tr><th class="py-1.5">Symbol</th><th>Market</th><th class="text-right">Last</th><th class="text-right">Status</th><th class="text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($instruments as $i)
                        <tr x-show="match(@js(strtolower($i->symbol . ' ' . $i->name . ' ' . $i->exchange . ' ' . $i->market . ' ' . $i->type)))">
                            <td class="py-1.5 font-medium">{{ $i->symbol }} <span class="text-gray-400">{{ $i->exchange }}</span>
                                @if ($i->name && $i->name !== $i->symbol)<div class="text-gray-400 font-normal">{{ $i->name }}</div>@endif
                            </td>
                            <td>{{ ucfirst($i->market) }}</td>
                            <td class="text-right">{{ $i->last_price ? number_format((float)$i->last_price,2) : '—' }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('admin.spot.instruments.toggle', $i) }}">@csrf
                                    <button class="px-2 py-0.5 rounded-full {{ $i->enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">{{ $i->enabled ? 'Enabled' : 'Disabled' }}</button>
                                </form>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <button type="button" title="Edit" class="px-2 py-0.5 text-blue-600 hover:text-blue-800"
                                    @click="edit(@js(['id' => $i->id, 'symbol' => $i->symbol, 'exchange' => (string) $i->exchange, 'name' => (string) $i->name, 'market' => $i->market, 'type' => $i->type]))"><i class="fa-solid fa-pen"></i></button>
                                <form method="POST" action="{{ route('admin.spot.instruments.delete', $i) }}" class="inline" onsubmit="return confirm(@js('Delete ' . $i->symbol . '? This cannot be undone.'))">@csrf
                                    <button title="Delete" class="px-2 py-0.5 text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
