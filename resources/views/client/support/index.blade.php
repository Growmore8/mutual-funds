<x-client-layout title="Support">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Support tickets</h2>
            <p class="text-sm text-gray-500">Questions about deposits, withdrawals, KYC or anything else.</p>
        </div>
        <a href="{{ route('support.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-md text-sm font-medium hover:bg-emerald-700">
            <i class="fa-solid fa-plus"></i> New ticket
        </a>
    </div>

    <div class="bg-white shadow rounded-xl divide-y divide-gray-100">
        @forelse ($tickets as $t)
            <a href="{{ route('support.show', $t) }}" class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50">
                <div class="w-10 h-10 rounded-full grid place-items-center text-emerald-600 bg-emerald-50 shrink-0">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="font-medium text-gray-900 truncate">{{ $t->subject }}</p>
                        @if ($t->unread_count)
                            <span class="text-[11px] bg-emerald-600 text-white rounded-full px-2 py-0.5">{{ $t->unread_count }} new</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 capitalize">{{ $t->category }} · updated {{ $t->last_reply_at?->diffForHumans() }}</p>
                </div>
                @php $badge = ['open'=>'bg-amber-100 text-amber-800','answered'=>'bg-emerald-100 text-emerald-800','closed'=>'bg-gray-100 text-gray-500'][$t->status]; @endphp
                <span class="text-xs px-2.5 py-1 rounded-full capitalize {{ $badge }}">{{ $t->status }}</span>
            </a>
        @empty
            <div class="px-5 py-12 text-center text-gray-400">
                <i class="fa-regular fa-comments text-3xl mb-2"></i>
                <p>No tickets yet. Need help? Create your first ticket.</p>
            </div>
        @endforelse
    </div>
</x-client-layout>
