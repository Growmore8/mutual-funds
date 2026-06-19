<x-admin-layout title="Popups">
    <div class="flex items-center justify-between mb-5">
        <p class="text-sm text-gray-500">Popups shown to clients when they open the app (maintenance, notices, offers, promotions).</p>
        <a href="{{ route('admin.announcements.create') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm font-medium"><i class="fa-solid fa-plus mr-1"></i> New popup</a>
    </div>

    <div class="bg-white shadow rounded-xl overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr><th class="px-4 py-3">Title</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Frequency</th><th class="px-4 py-3">Window</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($announcements as $a)
                    @php $tc = ['maintenance'=>'bg-red-100 text-red-700','notice'=>'bg-blue-100 text-blue-700','offer'=>'bg-amber-100 text-amber-800','promotion'=>'bg-emerald-100 text-emerald-800'][$a->type] ?? 'bg-gray-100'; @endphp
                    <tr>
                        <td class="px-4 py-3"><div class="font-medium text-gray-900">{{ $a->title }}</div><div class="text-gray-400 text-xs truncate max-w-xs">{{ $a->body }}</div></td>
                        <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full capitalize {{ $tc }}">{{ $a->type }}</span></td>
                        <td class="px-4 py-3 capitalize text-gray-600">{{ $a->frequency }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $a->starts_at?->format('d M') ?? '—' }} → {{ $a->ends_at?->format('d M') ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full {{ $a->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">{{ $a->is_active ? 'Active' : 'Off' }}</span></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.announcements.edit',$a) }}" class="px-3 py-1.5 bg-gray-100 rounded-md">Edit</a>
                                <form method="POST" action="{{ route('admin.announcements.destroy',$a) }}" onsubmit="return confirm('Delete this popup?')">@csrf @method('DELETE')
                                    <button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No popups yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
