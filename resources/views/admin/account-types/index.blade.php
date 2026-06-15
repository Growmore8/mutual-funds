<x-admin-layout title="Account Types">
    <div class="flex justify-between items-center mb-5">
        <p class="text-sm text-gray-500">Manage the mutual-fund account tiers shown to clients.</p>
        <a href="{{ route('admin.account-types.create') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm">+ New type</a>
    </div>
    <div class="bg-white shadow rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Min deposit</th><th class="px-4 py-3">Profit share</th><th class="px-4 py-3">Lock-in</th><th class="px-4 py-3">Active</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($types as $t)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $t->name }}</td>
                        <td class="px-4 py-3">${{ number_format((float)$t->min_deposit) }}</td>
                        <td class="px-4 py-3">{{ rtrim(rtrim((string)$t->profit_share_pct,'0'),'.') }}%</td>
                        <td class="px-4 py-3">{{ $t->lock_in_months }} mo</td>
                        <td class="px-4 py-3">{!! $t->is_active ? '<span class="text-green-600">Yes</span>' : '<span class="text-gray-400">No</span>' !!}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.account-types.edit',$t) }}" class="px-3 py-1.5 bg-gray-100 rounded-md">Edit</a>
                                <form method="POST" action="{{ route('admin.account-types.destroy',$t) }}" onsubmit="return confirm('Delete this type?')">@csrf @method('DELETE')<button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-layout>
