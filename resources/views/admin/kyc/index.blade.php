<x-admin-layout title="KYC Review">
    <div class="bg-white shadow rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr><th class="px-4 py-3">Client</th><th class="px-4 py-3">Document</th><th class="px-4 py-3">Doc #</th><th class="px-4 py-3">Submitted</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($documents as $d)
                    <tr>
                        <td class="px-4 py-3"><div class="font-medium text-gray-900">{{ $d->user->name }}</div><div class="text-gray-400">{{ $d->user->email }}</div></td>
                        <td class="px-4 py-3">National ID / Passport</td>
                        <td class="px-4 py-3">{{ $d->document_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $d->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                @if ($d->front_path ?? $d->file_path)
                                    <a href="{{ route('admin.kyc.file',[$d,'front']) }}" target="_blank" class="px-3 py-1.5 bg-gray-100 rounded-md">Front</a>
                                @endif
                                @if ($d->back_path)
                                    <a href="{{ route('admin.kyc.file',[$d,'back']) }}" target="_blank" class="px-3 py-1.5 bg-gray-100 rounded-md">Back</a>
                                @endif
                                <form method="POST" action="{{ route('admin.kyc.approve',$d) }}">@csrf<button class="px-3 py-1.5 bg-emerald-600 text-white rounded-md">Approve</button></form>
                                <form method="POST" action="{{ route('admin.kyc.reject',$d) }}" onsubmit="return confirm('Reject this document?')">@csrf
                                    <button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No documents awaiting review.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $documents->links() }}</div>
</x-admin-layout>
