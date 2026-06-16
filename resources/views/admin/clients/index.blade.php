<x-admin-layout title="Clients">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <form method="GET" class="flex flex-wrap gap-3">
            <input name="q" value="{{ request('q') }}" placeholder="Search name or email"
                   class="border-gray-300 rounded-md text-sm w-64">
            <select name="status" class="border-gray-300 rounded-md text-sm">
                <option value="">All statuses</option>
                @foreach (['pending','active','suspended'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm">Filter</button>
        </form>
        <a href="{{ route('admin.clients.create') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm font-medium"><i class="fa-solid fa-user-plus mr-1"></i> New client</a>
    </div>

    <div class="bg-white shadow rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3">Name</th><th class="px-4 py-3">Country</th>
                    <th class="px-4 py-3">Status</th><th class="px-4 py-3">KYC</th>
                    <th class="px-4 py-3">Joined</th><th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($clients as $c)
                    <tr>
                        <td class="px-4 py-3"><div class="font-medium text-gray-900">{{ $c->name }}</div><div class="text-gray-400">{{ $c->email }}</div></td>
                        <td class="px-4 py-3">{{ $c->country ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs {{ ['pending'=>'bg-gray-100','active'=>'bg-green-100 text-green-800','suspended'=>'bg-red-100 text-red-800'][$c->status] ?? 'bg-gray-100' }}">{{ ucfirst($c->status) }}</span></td>
                        <td class="px-4 py-3">{{ ucfirst(str_replace('_',' ',$c->kyc_status)) }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $c->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.clients.show',$c) }}" class="text-emerald-600 font-medium">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No clients found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $clients->links() }}</div>
</x-admin-layout>
