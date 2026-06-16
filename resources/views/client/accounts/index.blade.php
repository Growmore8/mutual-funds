<x-client-layout title="My Account">
    @php
        $money = fn ($n) => '$' . number_format((float) $n, 2);
        $at = $user->accountType;
    @endphp

    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center gap-4 mb-5">
                <div class="w-12 h-12 rounded-full bg-emerald-600 text-white grid place-items-center text-lg"><i class="fa-solid fa-star"></i></div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 text-lg">{{ $at->name ?? 'No plan assigned' }}</p>
                    <p class="text-xs text-gray-500">Your investment plan</p>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">Active</span>
            </div>

            <dl class="text-sm divide-y divide-gray-100">
                <div class="flex justify-between py-2.5"><dt class="text-gray-500">Live ID / Pool account</dt><dd class="font-semibold">{{ $user->poolAccount->account_ref ?? '—' }}</dd></div>
                <div class="flex justify-between py-2.5"><dt class="text-gray-500">Pool account size</dt><dd class="font-semibold">{{ $money($at->pool_amount ?? 0) }}</dd></div>
                <div class="flex justify-between py-2.5"><dt class="text-gray-500">Eligible deposit</dt><dd class="font-semibold">{{ $at ? $money($at->min_deposit) . ($at->max_deposit ? ' – ' . $money($at->max_deposit) : '+') : '—' }}</dd></div>
                <div class="flex justify-between py-2.5"><dt class="text-gray-500">Daily profit</dt><dd class="font-semibold">{{ $at ? rtrim(rtrim(number_format($at->daily_return_pct,2),'0'),'.') . '%' : '—' }}</dd></div>
                <div class="flex justify-between py-2.5 bg-emerald-50 -mx-2 px-2 rounded"><dt class="text-gray-600">Your invested amount</dt><dd class="font-bold text-emerald-700">{{ $money($investment) }}</dd></div>
            </dl>

            <div class="mt-5 bg-blue-50 border border-blue-100 text-blue-800 text-sm rounded-lg p-3 flex items-start gap-2">
                <i class="fa-solid fa-circle-info mt-0.5"></i>
                <span>Your plan is managed by GrowthCapital. To change your plan or open another account, please contact us via <a href="{{ route('support.index') }}" class="font-semibold underline">Support</a>.</span>
            </div>
        </div>
    </div>
</x-client-layout>
