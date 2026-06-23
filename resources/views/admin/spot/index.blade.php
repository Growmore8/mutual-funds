<x-admin-layout title="Spot Instruments">
    @if (session('status'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
    @endif

    <p class="text-sm text-gray-500 mb-4"><i class="fa-solid fa-circle-info"></i> Add/enable the markets clients can trade. Manage each client's spot <strong>wallets, holdings &amp; trades</strong> from <a href="{{ route('admin.clients.index') }}" class="text-emerald-600 hover:underline">Clients</a> → open a client → <strong>Spot Trading</strong> section.</p>

    <div class="grid lg:grid-cols-1 gap-6">
        {{-- Instruments --}}
        <div class="bg-white shadow rounded-xl p-5">
            <h3 class="font-semibold text-gray-900 mb-3">Tradable instruments</h3>
            <form method="POST" action="{{ route('admin.spot.instruments.store') }}" class="grid grid-cols-2 gap-2 mb-4 text-sm">
                @csrf
                <input name="symbol" placeholder="Symbol e.g. RELIANCE" class="border-gray-300 rounded-md" required>
                <input name="exchange" placeholder="Exchange e.g. NSE" class="border-gray-300 rounded-md">
                <input name="name" placeholder="Name" class="border-gray-300 rounded-md col-span-2">
                <select name="market" class="border-gray-300 rounded-md"><option value="india">India</option><option value="global">Global</option><option value="crypto">Crypto</option><option value="forex">Forex</option><option value="commodity">Commodity</option></select>
                <select name="type" class="border-gray-300 rounded-md"><option value="stock">Stock</option><option value="crypto">Crypto</option><option value="forex">Forex</option><option value="commodity">Commodity</option><option value="index">Index</option></select>
                <button class="col-span-2 px-4 py-2 bg-emerald-600 text-white rounded-md">Add / Update instrument</button>
            </form>
            <table class="min-w-full text-xs">
                <thead class="text-gray-500 text-left"><tr><th class="py-1.5">Symbol</th><th>Market</th><th class="text-right">Last</th><th class="text-right">Status</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($instruments as $i)
                        <tr>
                            <td class="py-1.5 font-medium">{{ $i->symbol }} <span class="text-gray-400">{{ $i->exchange }}</span></td>
                            <td>{{ ucfirst($i->market) }}</td>
                            <td class="text-right">{{ $i->last_price ? number_format((float)$i->last_price,2) : '—' }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('admin.spot.instruments.toggle', $i) }}">@csrf
                                    <button class="px-2 py-0.5 rounded-full {{ $i->enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">{{ $i->enabled ? 'Enabled' : 'Disabled' }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
