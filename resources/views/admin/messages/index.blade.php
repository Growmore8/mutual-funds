<x-admin-layout title="Message Center">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-gray-500">Client support tickets — read and reply.</p>
        <div class="flex gap-1 text-sm">
            @foreach (['all'=>'All','open'=>'Open','answered'=>'Answered','closed'=>'Closed'] as $key => $label)
                <a href="{{ route('admin.messages.index', ['status' => $key]) }}"
                   class="px-3 py-1.5 rounded-md {{ $filter === $key ? 'bg-emerald-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="bg-white shadow rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Subject</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Updated</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($tickets as $t)
                    <tr class="{{ $t->unread_count ? 'bg-emerald-50/40' : '' }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $t->user->name }}</div>
                            <div class="text-gray-400">{{ $t->user->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-gray-900">{{ $t->subject }}</span>
                            @if ($t->unread_count)
                                <span class="ml-1 text-[11px] bg-emerald-600 text-white rounded-full px-2 py-0.5">{{ $t->unread_count }} new</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 capitalize text-gray-600">{{ $t->category }}</td>
                        <td class="px-4 py-3">
                            @php $badge = ['open'=>'bg-amber-100 text-amber-800','answered'=>'bg-emerald-100 text-emerald-800','closed'=>'bg-gray-100 text-gray-500'][$t->status]; @endphp
                            <span class="text-xs px-2.5 py-1 rounded-full capitalize {{ $badge }}">{{ $t->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-400">{{ $t->last_reply_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.messages.show', $t) }}" class="text-emerald-600 hover:underline">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No tickets.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
