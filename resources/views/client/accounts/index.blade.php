<x-client-layout title="My Account">
    @php
        $money = fn ($n) => '$' . number_format((float) $n, 2);
        $at = $user->accountType;
        $card = 'bg-white dark:bg-white/[0.04] dark:border dark:border-white/[0.06] rounded-2xl shadow-sm';
    @endphp

    <div class="max-w-5xl space-y-6">
        <x-back-link />
        {{-- Current account --}}
        <div class="{{ $card }} p-6">
            <div class="flex items-center gap-4 mb-5">
                <div class="w-12 h-12 rounded-full bg-emerald-600 text-white grid place-items-center text-lg"><i class="fa-solid fa-star"></i></div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 dark:text-white text-lg">{{ $at->name ?? 'No plan assigned' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Your investment plan</p>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">Active</span>
            </div>

            <dl class="text-sm divide-y divide-gray-100 dark:divide-white/[0.06]">
                <div class="flex justify-between py-2.5"><dt class="text-gray-500 dark:text-gray-400">Live ID / Pool account</dt><dd class="font-semibold dark:text-white">{{ $user->poolAccount->account_ref ?? '—' }}</dd></div>
                <div class="flex justify-between py-2.5"><dt class="text-gray-500 dark:text-gray-400">Pool account size</dt><dd class="font-semibold dark:text-white">{{ $money($at->pool_amount ?? 0) }}</dd></div>
                <div class="flex justify-between py-2.5"><dt class="text-gray-500 dark:text-gray-400">Eligible deposit</dt><dd class="font-semibold dark:text-white">{{ $at ? $money($at->min_deposit) . ($at->max_deposit ? ' – ' . $money($at->max_deposit) : '+') : '—' }}</dd></div>
                <div class="flex justify-between py-2.5"><dt class="text-gray-500 dark:text-gray-400">Daily profit</dt><dd class="font-semibold dark:text-white">{{ $at ? rtrim(rtrim(number_format($at->daily_return_pct,2),'0'),'.') . '%' : '—' }}</dd></div>
                <div class="flex justify-between py-2.5 bg-emerald-50 dark:bg-emerald-500/10 -mx-2 px-2 rounded"><dt class="text-gray-600 dark:text-emerald-200">Your invested amount</dt><dd class="font-bold text-emerald-700 dark:text-emerald-300">{{ $money($investment) }}</dd></div>
            </dl>
        </div>

        {{-- Open another account --}}
        <div class="{{ $card }} p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-1"><i class="fa-solid fa-circle-plus text-emerald-500 mr-1"></i> Open another account</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Request an additional investment account. Our team will review and set it up for you.</p>

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{{ $errors->first() }}</div>
            @endif

            @if ($pendingRequest)
                <div class="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-amber-800 dark:text-amber-200 text-sm rounded-lg p-3 flex items-start gap-2">
                    <i class="fa-solid fa-clock mt-0.5"></i>
                    <span>You have a pending request for <strong>{{ $pendingRequest->accountType->name ?? 'an account' }}</strong> submitted {{ $pendingRequest->created_at->format('d M Y') }}. An admin will review it shortly.</span>
                </div>
            @else
                <form method="POST" action="{{ route('accounts.store') }}" class="space-y-3 text-sm">
                    @csrf
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 mb-1">Account type / plan</label>
                        <select name="account_type_id" required class="w-full border-gray-300 rounded-lg dark:bg-white/10 dark:border-white/10 dark:text-white">
                            <option value="">Select a plan…</option>
                            @foreach ($accountTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }} — {{ $money($type->min_deposit) }}{{ $type->max_deposit ? ' – ' . $money($type->max_deposit) : '+' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 mb-1">Note (optional)</label>
                        <textarea name="reason" rows="2" maxlength="500" class="w-full border-gray-300 rounded-lg dark:bg-white/10 dark:border-white/10 dark:text-white" placeholder="Anything we should know?"></textarea>
                    </div>
                    <button class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold"><i class="fa-solid fa-paper-plane mr-1"></i> Submit request</button>
                </form>
            @endif

            @if ($pastRequests->isNotEmpty())
                <div class="mt-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Previous requests</p>
                    <div class="divide-y divide-gray-100 dark:divide-white/[0.06] text-sm">
                        @foreach ($pastRequests as $r)
                            <div class="flex items-center justify-between py-2">
                                <span class="text-gray-600 dark:text-gray-300">{{ $r->accountType->name ?? '—' }} · {{ $r->created_at->format('d M Y') }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $r->status === 'approved' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300' }}">{{ ucfirst($r->status) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-client-layout>
