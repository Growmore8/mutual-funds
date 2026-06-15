<x-admin-layout title="Payment Methods">
    <div class="flex justify-between items-center mb-5">
        <p class="text-sm text-gray-500">Deposit options shown to clients.</p>
        <a href="{{ route('admin.payment-methods.create') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm">+ New method</a>
    </div>
    <div class="bg-white shadow rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Currency</th><th class="px-4 py-3">Active</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($methods as $m)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $m->name }}</td>
                        <td class="px-4 py-3">{{ ucfirst($m->type) }}</td>
                        <td class="px-4 py-3">{{ $m->currency ?? '—' }}</td>
                        <td class="px-4 py-3">{!! $m->is_active ? '<span class="text-green-600">Yes</span>' : '<span class="text-gray-400">No</span>' !!}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.payment-methods.edit',$m) }}" class="px-3 py-1.5 bg-gray-100 rounded-md">Edit</a>
                                <form method="POST" action="{{ route('admin.payment-methods.destroy',$m) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-layout>
