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
                    <th class="px-4 py-2.5">Name</th>
                    <th class="px-4 py-2.5">Pool / Live ID</th>
                    <th class="px-4 py-2.5">Status</th>
                    <th class="px-4 py-2.5">KYC</th>
                    <th class="px-4 py-2.5">Joined</th>
                    <th class="px-4 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($clients as $c)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2"><div class="font-medium text-gray-900 leading-tight">{{ $c->name }}</div><div class="text-gray-400 text-xs">{{ $c->email }}</div></td>
                        <td class="px-4 py-2">
                            @if ($c->poolAccount)
                                <span class="font-medium text-gray-700">{{ $c->poolAccount->account_ref }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2"><span class="px-2 py-0.5 rounded-full text-xs {{ ['pending'=>'bg-gray-100 text-gray-600','active'=>'bg-green-100 text-green-800','suspended'=>'bg-red-100 text-red-800'][$c->status] ?? 'bg-gray-100' }}">{{ ucfirst($c->status) }}</span></td>
                        <td class="px-4 py-2">
                            @php $kc = ['not_submitted'=>'bg-gray-100 text-gray-600','submitted'=>'bg-amber-100 text-amber-800','approved'=>'bg-emerald-100 text-emerald-800','rejected'=>'bg-red-100 text-red-700'][$c->kyc_status] ?? 'bg-gray-100'; @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $kc }}">{{ ucfirst(str_replace('_',' ',$c->kyc_status)) }}</span>
                        </td>
                        <td class="px-4 py-2 text-gray-400 text-xs">{{ $c->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-2">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('admin.clients.show',$c) }}" title="Edit"
                                   class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-emerald-50 hover:text-emerald-600"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="{{ route('admin.clients.destroy',$c) }}" onsubmit="return confirm('Delete {{ $c->name }} and all their data? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button title="Delete" class="w-8 h-8 grid place-items-center rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No clients found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $clients->links() }}</div>
</x-admin-layout>
