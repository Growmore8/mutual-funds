<x-admin-layout :title="'Ticket #'.$ticket->id">
    <a href="{{ route('admin.messages.index') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fa-solid fa-arrow-left"></i> Back to message center</a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
        {{-- thread --}}
        <div class="lg:col-span-2 bg-white shadow rounded-xl overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b">
                <div>
                    <h2 class="font-semibold text-gray-900">{{ $ticket->subject }}</h2>
                    <p class="text-xs text-gray-400 capitalize">{{ $ticket->category }}</p>
                </div>
                @php $badge = ['open'=>'bg-amber-100 text-amber-800','answered'=>'bg-emerald-100 text-emerald-800','closed'=>'bg-gray-100 text-gray-500'][$ticket->status]; @endphp
                <span class="text-xs px-2.5 py-1 rounded-full capitalize {{ $badge }}">{{ $ticket->status }}</span>
            </div>

            <div class="p-5 space-y-4 bg-gray-50 max-h-[60vh] overflow-y-auto">
                @foreach ($ticket->messages as $m)
                    <div class="flex {{ $m->is_admin ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%]">
                            <div class="rounded-2xl px-4 py-2.5 text-sm {{ $m->is_admin ? 'bg-emerald-600 text-white' : 'bg-white border border-gray-200 text-gray-800' }}">
                                {!! nl2br(e($m->body)) !!}
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1 {{ $m->is_admin ? 'text-right' : '' }}">
                                {{ $m->is_admin ? 'You (Support)' : $ticket->user->name }} · {{ $m->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($ticket->status === 'closed')
                <div class="px-5 py-4 border-t text-sm text-gray-400 text-center">This ticket is closed. Reopen it on the right to reply.</div>
            @else
                <form method="POST" action="{{ route('admin.messages.reply', $ticket) }}" class="px-5 py-4 border-t flex items-end gap-3">
                    @csrf
                    <textarea name="body" rows="2" required maxlength="5000"
                              class="flex-1 border-gray-300 rounded-md text-sm" placeholder="Reply to client…"></textarea>
                    <button class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-md text-sm font-medium hover:bg-emerald-700">
                        <i class="fa-solid fa-paper-plane"></i> Send
                    </button>
                </form>
            @endif
        </div>

        {{-- client + actions --}}
        <div class="space-y-6">
            <div class="bg-white shadow rounded-xl p-5 text-sm">
                <h3 class="font-semibold text-gray-900 mb-3">Client</h3>
                <dl class="space-y-2 text-gray-600">
                    <div class="flex justify-between gap-3"><dt class="text-gray-400">Name</dt><dd>{{ $ticket->user->name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-400">Email</dt><dd class="truncate">{{ $ticket->user->email }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-400">KYC</dt><dd class="capitalize">{{ $ticket->user->kyc_status }}</dd></div>
                </dl>
                <a href="{{ route('admin.clients.show', $ticket->user) }}" class="mt-4 inline-block text-emerald-600 hover:underline">View client →</a>
            </div>

            <div class="bg-white shadow rounded-xl p-5 text-sm">
                <h3 class="font-semibold text-gray-900 mb-3">Set status</h3>
                <form method="POST" action="{{ route('admin.messages.status', $ticket) }}" class="flex gap-2">
                    @csrf @method('PATCH')
                    <select name="status" class="flex-1 border-gray-300 rounded-md">
                        <option value="open" @selected($ticket->status==='open')>Open</option>
                        <option value="answered" @selected($ticket->status==='answered')>Answered</option>
                        <option value="closed" @selected($ticket->status==='closed')>Closed</option>
                    </select>
                    <button class="px-4 py-2 bg-gray-800 text-white rounded-md">Save</button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
