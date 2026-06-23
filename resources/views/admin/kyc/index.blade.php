<x-admin-layout title="KYC Review">
    @php
        $tabs = ['submitted' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'];
        $badge = fn ($s) => match ($s) {
            'approved' => 'bg-emerald-100 text-emerald-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-amber-100 text-amber-800',
        };
    @endphp

    {{-- Search + status filter --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <form method="GET" class="flex gap-2 text-sm">
            <input type="hidden" name="status" value="{{ $status }}">
            <input name="q" value="{{ $search ?? '' }}" placeholder="Search name, email or GC ID…" class="min-w-[220px] border-gray-300 rounded-md">
            <button class="px-4 py-2 bg-gray-800 text-white rounded-md"><i class="fa-solid fa-magnifying-glass"></i></button>
            @if (!empty($search))<a href="{{ route('admin.kyc.index', ['status' => $status]) }}" class="px-4 py-2 border rounded-md text-gray-600">Clear</a>@endif
        </form>
        <div class="flex flex-wrap gap-1 text-sm">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.kyc.index', array_filter(['status' => $key, 'q' => $search ?? ''])) }}"
                   class="px-3 py-1.5 rounded-md {{ $status === $key ? 'bg-emerald-600 text-white' : 'bg-white border text-gray-600 hover:bg-gray-50' }}">
                    {{ $label }} <span class="opacity-70">({{ $counts[$key] ?? 0 }})</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="bg-white shadow rounded-xl overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr><th class="px-4 py-3">Client</th><th class="px-4 py-3">Document</th><th class="px-4 py-3">Doc #</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Submitted / Reviewed</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($documents as $d)
                    <tr>
                        <td class="px-4 py-3"><div class="font-medium text-gray-900">{{ $d->user->name }}</div><div class="text-gray-400">{{ $d->user->email }}</div></td>
                        <td class="px-4 py-3">National ID / Passport</td>
                        <td class="px-4 py-3">{{ $d->document_number ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $badge($d->status) }}">{{ ucfirst($d->status === 'submitted' ? 'pending' : $d->status) }}</span>
                            @if ($d->status === 'rejected' && $d->review_note)
                                <div class="text-[11px] text-gray-400 mt-1">{{ $d->review_note }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">
                            {{ $d->created_at->format('d M Y h:i A') }}
                            @if ($d->reviewed_at)<br><span class="text-gray-300">Reviewed {{ $d->reviewed_at->format('d M Y') }}</span>@endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                @if ($d->front_path ?? $d->file_path)
                                    <a href="{{ route('admin.kyc.file',[$d,'front']) }}" target="_blank" class="px-3 py-1.5 bg-gray-100 rounded-md">Front</a>
                                @endif
                                @if ($d->back_path)
                                    <a href="{{ route('admin.kyc.file',[$d,'back']) }}" target="_blank" class="px-3 py-1.5 bg-gray-100 rounded-md">Back</a>
                                @endif
                                @if ($d->status !== 'approved')
                                    <form method="POST" action="{{ route('admin.kyc.approve',$d) }}">@csrf<button class="px-3 py-1.5 bg-emerald-600 text-white rounded-md">Approve</button></form>
                                @endif
                                @if ($d->status !== 'rejected')
                                    <form method="POST" action="{{ route('admin.kyc.reject',$d) }}" onsubmit="return confirm('Reject this document?')">@csrf
                                        <button class="px-3 py-1.5 bg-red-600 text-white rounded-md">Reject</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No documents in this view.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $documents->links() }}</div>
</x-admin-layout>
