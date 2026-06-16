<x-client-layout title="My Accounts">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Current accounts --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Your accounts</h2>

                {{-- Primary account --}}
                <div class="flex items-center gap-4 p-4 rounded-xl border border-emerald-200 bg-emerald-50/50">
                    <div class="w-10 h-10 rounded-full bg-emerald-600 text-white grid place-items-center"><i class="fa-solid fa-star"></i></div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">{{ $user->accountType->name ?? 'No plan selected' }}</p>
                        <p class="text-xs text-gray-500">Primary account</p>
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">Active</span>
                </div>

                {{-- Approved additional accounts --}}
                @foreach ($requests->where('status', 'approved') as $r)
                    <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 mt-3">
                        <div class="w-10 h-10 rounded-full bg-gray-700 text-white grid place-items-center"><i class="fa-solid fa-layer-group"></i></div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">{{ $r->accountType->name ?? '—' }}</p>
                            <p class="text-xs text-gray-500">Additional account · approved {{ $r->processed_at?->format('d M Y') }}</p>
                        </div>
                        <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">Active</span>
                    </div>
                @endforeach
            </div>

            {{-- Request additional account --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 mb-1">Open an additional account</h3>
                <p class="text-sm text-gray-500 mb-4">Your first account is active. Opening another account requires admin approval.</p>

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
                @endif

                @if ($hasPending)
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-4">
                        <i class="fa-solid fa-hourglass-half"></i> You have a pending request awaiting admin approval.
                    </div>
                @else
                    <form method="POST" action="{{ route('accounts.request') }}" class="space-y-4 text-sm">
                        @csrf
                        <div>
                            <label class="block text-gray-700 mb-1">Account type</label>
                            <select name="account_type_id" class="w-full border-gray-300 rounded-md" required>
                                <option value="">Select…</option>
                                @foreach ($accountTypes as $at)
                                    <option value="{{ $at->id }}">{{ $at->name }} (min ${{ number_format((float)$at->min_deposit) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-1">Reason (optional)</label>
                            <textarea name="reason" rows="2" maxlength="500" class="w-full border-gray-300 rounded-md" placeholder="Why do you want a second account?"></textarea>
                        </div>
                        <button class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-md font-medium hover:bg-emerald-700">
                            <i class="fa-solid fa-plus"></i> Request account
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Request history --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-3">Request history</h3>
            <div class="divide-y divide-gray-100 text-sm">
                @forelse ($requests as $r)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">{{ $r->accountType->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $r->created_at->format('d M Y') }}</p>
                        </div>
                        @php $b = ['pending'=>'bg-amber-100 text-amber-800','approved'=>'bg-emerald-100 text-emerald-800','rejected'=>'bg-red-100 text-red-700'][$r->status]; @endphp
                        <span class="text-xs px-2.5 py-1 rounded-full capitalize {{ $b }}">{{ $r->status }}</span>
                    </div>
                @empty
                    <p class="py-6 text-center text-gray-400">No requests yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-client-layout>
