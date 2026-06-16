<x-client-layout :title="$ticket->subject">
    <div class="max-w-3xl">
        <a href="{{ route('support.index') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fa-solid fa-arrow-left"></i> Back to tickets</a>

        <div class="bg-white shadow rounded-xl mt-4 overflow-hidden">
            {{-- header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b">
                <div>
                    <h2 class="font-semibold text-gray-900">{{ $ticket->subject }}</h2>
                    <p class="text-xs text-gray-400 capitalize">{{ $ticket->category }} · ticket #{{ $ticket->id }}</p>
                </div>
                @php $badge = ['open'=>'bg-amber-100 text-amber-800','answered'=>'bg-emerald-100 text-emerald-800','closed'=>'bg-gray-100 text-gray-500'][$ticket->status]; @endphp
                <span class="text-xs px-2.5 py-1 rounded-full capitalize {{ $badge }}">{{ $ticket->status }}</span>
            </div>

            {{-- thread --}}
            <div class="p-5 space-y-4 bg-gray-50">
                @foreach ($ticket->messages as $m)
                    <div class="flex {{ $m->is_admin ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-[80%]">
                            <div class="rounded-2xl px-4 py-2.5 text-sm {{ $m->is_admin ? 'bg-white border border-gray-200 text-gray-800' : 'bg-emerald-600 text-white' }}">
                                {!! nl2br(e($m->body)) !!}
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1 {{ $m->is_admin ? '' : 'text-right' }}">
                                {{ $m->is_admin ? 'GrowthCapital Support' : 'You' }} · {{ $m->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- reply --}}
            @if ($ticket->status === 'closed')
                <div class="px-5 py-4 border-t text-sm text-gray-400 text-center">This ticket is closed.</div>
            @else
                <form method="POST" action="{{ route('support.reply', $ticket) }}" class="px-5 py-4 border-t flex items-end gap-3">
                    @csrf
                    <textarea name="body" rows="2" required maxlength="5000"
                              class="flex-1 border-gray-300 rounded-md text-sm" placeholder="Write a reply…"></textarea>
                    <button class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-md text-sm font-medium hover:bg-emerald-700">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-client-layout>
